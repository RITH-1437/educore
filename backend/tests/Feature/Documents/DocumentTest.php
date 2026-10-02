<?php

namespace Tests\Feature\Documents;

use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\DocumentVerification;
use App\Models\Enrollment;
use App\Models\Faculty;
use App\Models\Grade;
use App\Models\Internship;
use App\Models\InternshipCompany;
use App\Models\Lecturer;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\GpaService;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Modules 9.16 / 9.17 — request workflow, PDF generation from authoritative
 * data, private downloads, revocation and public verification.
 */
class DocumentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Student $student;

    private Semester $semester;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
        $this->seed(DocumentTypeSeeder::class);

        $this->admin = User::factory()->superAdmin()->create();
        $this->student = Student::factory()->create();
        $this->semester = Semester::factory()->create(['status' => 'completed']);

        // One approved grade: 3 credits, A.
        $offering = CourseOffering::factory()->create(['course_id' => Course::factory()->create(['code' => 'CS101', 'credits' => 3])->id, 'semester_id' => $this->semester->id]);
        $enrollment = Enrollment::query()->create([
            'student_id' => $this->student->id, 'section_id' => Section::factory()->create(['course_offering_id' => $offering->id])->id,
            'academic_year_id' => $this->semester->academic_year_id, 'semester_id' => $this->semester->id, 'status' => 'completed', 'enrolled_at' => now(),
        ]);
        Grade::query()->create(['enrollment_id' => $enrollment->id, 'letter_grade' => 'A', 'grade_point' => 4, 'total_score' => 91, 'status' => 'approved', 'approved_at' => now()]);
        app(GpaService::class)->recalculate($this->student);
    }

    public function test_full_flow_request_approve_generate_download_verify_revoke(): void
    {
        $transcript = $this->type(DocumentType::TRANSCRIPT);

        $id = $this->actingAs($this->student->user)->postJson('/api/document-requests', ['document_type_id' => $transcript, 'reason' => 'Scholarship'])
            ->assertCreated()->assertJsonPath('data.status', 'pending')->assertJsonPath('data.student.id', $this->student->id)->json('data.id');

        // A second open request for the same type is refused.
        $this->actingAs($this->student->user)->postJson('/api/document-requests', ['document_type_id' => $transcript])->assertStatus(409);
        // Generating before approval is refused.
        $this->actingAs($this->admin)->postJson("/api/document-requests/{$id}/generate")->assertStatus(409);

        $this->actingAs($this->admin)->postJson("/api/document-requests/{$id}/approve")->assertOk()->assertJsonPath('data.status', 'approved');
        $response = $this->actingAs($this->admin)->postJson("/api/document-requests/{$id}/generate")->assertCreated()
            ->assertJsonPath('data.status', 'generated')->assertJsonPath('data.document.status', 'valid');

        $document = Document::query()->firstOrFail();
        Storage::disk('s3')->assertExists($document->file_key);
        $this->assertStringStartsWith("documents/{$this->student->id}/", $document->file_key);
        $this->assertStringStartsWith('%PDF', Storage::disk('s3')->get($document->file_key));
        $this->assertSame(hash('sha256', Storage::disk('s3')->get($document->file_key)), $response->json('data.document.checksum'));
        $this->assertSame(64, strlen($document->verification_token));

        // Owner and staff download; another student cannot.
        $this->actingAs($this->student->user)->get("/api/documents/{$document->id}/download")->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($this->admin)->get("/api/documents/{$document->id}/download")->assertOk();
        $this->actingAs(Student::factory()->create()->user)->getJson("/api/documents/{$document->id}/download")->assertForbidden();

        // Public verification: minimal data, logged.
        $this->app['auth']->forgetGuards();
        $this->getJson("/api/verifications/{$document->verification_token}")->assertOk()
            ->assertJsonPath('data.status', 'valid')
            ->assertJsonPath('data.document_type', 'Academic transcript')
            ->assertJsonPath('data.student_number', $this->student->student_number)
            ->assertJsonMissingPath('data.grades');
        $this->getJson('/api/verifications/'.str_repeat('0', 64))->assertNotFound();
        $this->assertSame(1, DocumentVerification::query()->where('result', 'valid')->count());

        // Revoked: verification says so, and a fresh request is allowed.
        $this->actingAs($this->admin)->postJson("/api/documents/{$document->id}/revoke")->assertOk()->assertJsonPath('data.document.status', 'revoked');
        $this->actingAs($this->admin)->postJson("/api/documents/{$document->id}/revoke")->assertStatus(409);
        $this->app['auth']->forgetGuards();
        $this->getJson("/api/verifications/{$document->verification_token}")->assertOk()->assertJsonPath('data.status', 'revoked');
        $this->actingAs($this->student->user)->postJson('/api/document-requests', ['document_type_id' => $transcript])->assertCreated();
    }

    public function test_reject_requires_a_reason_and_allows_a_new_request(): void
    {
        $type = $this->type(DocumentType::ENROLLMENT_CERTIFICATE);
        $id = $this->actingAs($this->student->user)->postJson('/api/document-requests', ['document_type_id' => $type])->assertCreated()->json('data.id');

        $this->actingAs($this->admin)->postJson("/api/document-requests/{$id}/reject")->assertJsonValidationErrors('rejection_reason');
        $this->actingAs($this->admin)->postJson("/api/document-requests/{$id}/reject", ['rejection_reason' => 'Unpaid fees'])
            ->assertOk()->assertJsonPath('data.status', 'rejected')->assertJsonPath('data.rejection_reason', 'Unpaid fees');
        $this->actingAs($this->admin)->postJson("/api/document-requests/{$id}/approve")->assertStatus(409);

        $this->actingAs($this->student->user)->postJson('/api/document-requests', ['document_type_id' => $type])->assertCreated();
    }

    public function test_generation_uses_authoritative_data_and_stays_retryable(): void
    {
        // Academic result needs a semester; the semester needs approved grades.
        $result = $this->type(DocumentType::ACADEMIC_RESULT);
        $this->actingAs($this->student->user)->postJson('/api/document-requests', ['document_type_id' => $result])->assertJsonValidationErrors('semester_id');

        $empty = Semester::factory()->create(['status' => 'completed']);
        $id = $this->actingAs($this->student->user)->postJson('/api/document-requests', ['document_type_id' => $result, 'semester_id' => $empty->id])->assertCreated()->json('data.id');
        $this->actingAs($this->admin)->postJson("/api/document-requests/{$id}/approve");
        $this->actingAs($this->admin)->postJson("/api/document-requests/{$id}/generate")->assertStatus(409);
        $this->assertSame('approved', DocumentRequest::query()->find($id)->status);
        $this->assertSame([], Storage::disk('s3')->allFiles());

        $ok = $this->actingAs($this->student->user)->postJson('/api/document-requests', ['document_type_id' => $result, 'semester_id' => $this->semester->id])->assertCreated()->json('data.id');
        $this->actingAs($this->admin)->postJson("/api/document-requests/{$ok}/approve");
        $this->actingAs($this->admin)->postJson("/api/document-requests/{$ok}/generate")->assertCreated();

        // Enrollment certificates only for active students.
        $this->student->update(['status' => 'suspended']);
        $cert = $this->actingAs($this->student->user)->postJson('/api/document-requests', ['document_type_id' => $this->type(DocumentType::ENROLLMENT_CERTIFICATE)])->json('data.id');
        $this->actingAs($this->admin)->postJson("/api/document-requests/{$cert}/approve");
        $this->actingAs($this->admin)->postJson("/api/document-requests/{$cert}/generate")->assertStatus(409);
    }

    public function test_student_certificate_for_active_and_graduated_students_only(): void
    {
        $type = $this->type(DocumentType::STUDENT_CERTIFICATE);

        $this->assertSame(201, $this->generate($type));

        $this->student->update(['status' => 'graduated']);
        $this->assertSame(201, $this->generate($type));

        $this->student->update(['status' => 'suspended']);
        $this->assertSame(409, $this->generate($type));
    }

    public function test_internship_letter_needs_an_approved_ongoing_or_completed_internship(): void
    {
        $type = $this->type(DocumentType::INTERNSHIP_LETTER);
        $company = InternshipCompany::query()->create(['name' => 'Smart Axiata', 'industry' => 'Telecom', 'is_active' => true]);
        $internship = Internship::query()->create([
            'student_id' => $this->student->id, 'company_id' => $company->id, 'position_title' => 'Network intern',
            'start_date' => '2026-07-01', 'end_date' => '2026-09-30', 'status' => Internship::STATUS_SUBMITTED,
        ]);

        // A submitted (not yet approved) internship does not qualify.
        $this->assertSame(409, $this->generate($type));

        $internship->update(['status' => Internship::STATUS_APPROVED]);
        $this->assertSame(201, $this->generate($type));
        $this->assertSame(1, Document::query()->count());
    }

    public function test_role_matrix_and_listing_scope(): void
    {
        $type = $this->type(DocumentType::TRANSCRIPT);
        $id = $this->actingAs($this->student->user)->postJson('/api/document-requests', ['document_type_id' => $type])->json('data.id');
        $other = Student::factory()->create();
        $this->actingAs($other->user)->postJson('/api/document-requests', ['document_type_id' => $type])->assertCreated();
        $faculty = $this->facultyAdminFor(Faculty::factory()->create());
        $this->placeInFaculty($this->student, $faculty->faculty_id);
        $this->placeInFaculty($other, $faculty->faculty_id);
        $outsideFaculty = $this->facultyAdminFor(Faculty::factory()->create());
        $lecturer = Lecturer::factory()->create()->user;

        // A student sees only their own requests and cannot process any.
        $this->actingAs($this->student->user)->getJson('/api/document-requests')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($other->user)->getJson("/api/document-requests/{$id}")->assertForbidden();
        $this->actingAs($this->student->user)->postJson("/api/document-requests/{$id}/approve")->assertForbidden();

        // Faculty Admin reads everything but cannot process; lecturers have no access.
        $this->actingAs($faculty)->getJson('/api/document-requests?filters[status]=pending')->assertOk()->assertJsonCount(2, 'data');
        $this->actingAs($faculty)->postJson("/api/document-requests/{$id}/approve")->assertForbidden();
        // Another faculty's admin sees none of these students' requests.
        $this->actingAs($outsideFaculty)->getJson('/api/document-requests')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($outsideFaculty)->getJson("/api/document-requests/{$id}")->assertForbidden();
        $this->actingAs($lecturer)->getJson('/api/document-requests')->assertForbidden();
        $this->actingAs($this->admin)->postJson('/api/document-requests', ['document_type_id' => $type])->assertForbidden();
        $this->actingAs($this->admin)->getJson('/api/document-requests?filters[status]=bogus')->assertJsonValidationErrors('filters.status');
    }

    public function test_web_pages(): void
    {
        $type = $this->type(DocumentType::TRANSCRIPT);

        $this->actingAs($this->student->user)->get('/my-documents')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Documents/Mine')->has('types', 5)->has('semesters', 1));
        $this->actingAs($this->student->user)->post('/my-documents', ['document_type_id' => $type])->assertRedirect()->assertSessionHas('success');
        $id = DocumentRequest::query()->value('id');

        $this->actingAs($this->admin)->get('/documents')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Documents/Index')->has('requests.data', 1)->has('requests.meta.links')->where('canProcess', true));
        $this->actingAs($this->admin)->post("/document-requests/{$id}/approve")->assertSessionHas('success');
        $this->actingAs($this->admin)->post("/document-requests/{$id}/generate")->assertSessionHas('success');
        $document = Document::query()->firstOrFail();
        $this->actingAs($this->student->user)->get("/documents/{$document->id}/download")->assertOk();

        $this->app['auth']->forgetGuards();
        $this->get("/verify/{$document->verification_token}")->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Documents/Verify')->where('result.status', 'valid'));
        $this->get('/verify/unknown')->assertOk()->assertInertia(fn (Assert $page) => $page->where('result', null));
    }

    /** Request, approve and generate one document; returns the generate status code. */
    private function generate(int $type): int
    {
        $id = $this->actingAs($this->student->user)->postJson('/api/document-requests', ['document_type_id' => $type])->assertCreated()->json('data.id');
        $this->actingAs($this->admin)->postJson("/api/document-requests/{$id}/approve")->assertOk();
        $status = $this->actingAs($this->admin)->postJson("/api/document-requests/{$id}/generate")->status();

        // Close a refused request so the next attempt may open a new one.
        if ($status !== 201) {
            DocumentRequest::query()->whereKey($id)->update(['status' => 'rejected']);
        }

        return $status;
    }

    private function type(string $code): int
    {
        return DocumentType::query()->where('code', $code)->value('id');
    }
}
