<?php

namespace Tests\Feature\FacultyDepartment;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Faculty;
use App\Models\Internship;
use App\Models\InternshipCompany;
use App\Models\InternshipReport;
use App\Models\Student;
use App\Models\User;
use App\Notifications\DocumentRequestUpdated;
use App\Notifications\InternshipStatusChanged;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Faculty Admin request handling (`docs/33_Faculty-Admin-Request-Handling-Report.md`):
 * a Faculty Admin processes the document requests and internships of their
 * faculty's students; revoking documents and managing companies stay with
 * managers; other faculties' students and unassigned admins are refused.
 */
class FacultyAdminRequestHandlingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $facultyAdmin;

    private Student $student;

    private Student $outsider;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
        Notification::fake();

        $mine = Faculty::factory()->create();
        $this->admin = User::factory()->superAdmin()->create();
        $this->facultyAdmin = $this->facultyAdminFor($mine);
        $this->student = Student::factory()->create();
        $this->placeInFaculty($this->student, $mine);
        $this->outsider = Student::factory()->create();
        $this->placeInFaculty($this->outsider, Faculty::factory()->create());
    }

    public function test_faculty_admin_processes_their_faculty_document_requests(): void
    {
        $this->seed(DocumentTypeSeeder::class);
        $certificate = $this->documentType(DocumentType::STUDENT_CERTIFICATE);
        $own = $this->requestDocument($this->student, $certificate);
        $foreign = $this->requestDocument($this->outsider, $certificate);

        // The queue shows only their faculty's requests, with processing but no revoke controls.
        $this->actingAs($this->facultyAdmin)->get('/documents')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('requests.data', 1)->where('canProcess', true)->where('canRevoke', false));

        // Approve and generate: processed by and audited as the Faculty Admin; the student is told.
        $this->actingAs($this->facultyAdmin)->postJson("/api/document-requests/{$own}/approve")->assertOk()->assertJsonPath('data.status', 'approved');
        $this->assertSame($this->facultyAdmin->id, DocumentRequest::query()->find($own)->processed_by);
        $this->assertSame($this->facultyAdmin->id, AuditLog::query()->where('action', 'document_request.approved')->value('actor_id'));
        Notification::assertSentTo($this->student->user, DocumentRequestUpdated::class);
        $this->actingAs($this->facultyAdmin)->postJson("/api/document-requests/{$own}/generate")->assertCreated()->assertJsonPath('data.status', 'generated');

        // Revoking an issued document stays with managers (API and web).
        $document = Document::query()->firstOrFail();
        $this->actingAs($this->facultyAdmin)->postJson("/api/documents/{$document->id}/revoke")->assertForbidden();
        $this->actingAs($this->facultyAdmin)->post("/documents/{$document->id}/revoke")->assertForbidden();
        $this->actingAs($this->admin)->postJson("/api/documents/{$document->id}/revoke")->assertOk();

        // Another faculty's student is refused (API and web).
        $this->actingAs($this->facultyAdmin)->postJson("/api/document-requests/{$foreign}/approve")->assertForbidden();
        $this->actingAs($this->facultyAdmin)->postJson("/api/document-requests/{$foreign}/reject", ['rejection_reason' => 'No'])->assertForbidden();
        $this->actingAs($this->facultyAdmin)->post("/document-requests/{$foreign}/approve")->assertForbidden();
        $this->assertSame(DocumentRequest::STATUS_PENDING, DocumentRequest::query()->find($foreign)->status);

        // Web reject for their own student.
        $second = $this->requestDocument($this->student, $this->documentType(DocumentType::ENROLLMENT_CERTIFICATE));
        $this->actingAs($this->facultyAdmin)->post("/document-requests/{$second}/reject", ['rejection_reason' => 'Ask for a transcript instead'])
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame(DocumentRequest::STATUS_REJECTED, DocumentRequest::query()->find($second)->status);

        // An unassigned Faculty Admin processes nothing.
        $nobody = $this->facultyAdminFor(null);
        $this->actingAs($nobody)->get('/documents')->assertInertia(fn (Assert $page) => $page->has('requests.data', 0)->where('canProcess', false));
        $this->actingAs($nobody)->postJson("/api/document-requests/{$foreign}/approve")->assertForbidden();
    }

    public function test_faculty_admin_processes_their_faculty_internships(): void
    {
        $company = InternshipCompany::query()->create(['name' => 'Smart Axiata', 'industry' => 'Telecom', 'is_active' => true]);
        $own = $this->submittedInternship($this->student, $company);
        $foreign = $this->submittedInternship($this->outsider, $company);
        $as = $this->actingAs($this->facultyAdmin);

        // Review → approve → start, audited as the Faculty Admin; the student is notified.
        $as->postJson("/api/internships/{$own}/review")->assertOk()->assertJsonPath('data.status', 'under_review');
        $as->postJson("/api/internships/{$own}/approve", ['note' => 'Good fit'])->assertOk()->assertJsonPath('data.status', 'approved');
        $as->postJson("/api/internships/{$own}/start")->assertOk()->assertJsonPath('data.status', 'in_progress');
        Notification::assertSentTo($this->student->user, InternshipStatusChanged::class);
        $this->assertSame($this->facultyAdmin->id, AuditLog::query()->where('action', 'internship.approved')->value('actor_id'));

        // Evaluate, review a report and edit the placement (a manager-only path until now).
        $as->postJson("/api/internships/{$own}/evaluations", ['evaluator_type' => 'faculty', 'score' => 85, 'rating' => 'good'])->assertOk()->assertJsonCount(1, 'data.evaluations');
        $report = InternshipReport::query()->create(['internship_id' => $own, 'report_type' => 'initial', 'title' => 'Week 1', 'submitted_at' => now()]);
        $as->postJson("/api/internship-reports/{$report->id}/review", ['reviewer_comment' => 'Clear'])->assertOk();
        $as->putJson("/api/internships/{$own}", $this->internshipPayload($company, ['position_title' => 'Network Intern']))->assertOk()->assertJsonPath('data.position_title', 'Network Intern');
        $this->actingAs($this->facultyAdmin)->get("/internships/{$own}")->assertOk()->assertInertia(fn (Assert $page) => $page->where('canProcess', true));

        // Another faculty's student is refused (API and web).
        $as->postJson("/api/internships/{$foreign}/review")->assertForbidden();
        $as->postJson("/api/internships/{$foreign}/evaluations", ['evaluator_type' => 'faculty', 'score' => 70])->assertForbidden();
        $as->putJson("/api/internships/{$foreign}", $this->internshipPayload($company))->assertForbidden();
        $this->actingAs($this->facultyAdmin)->post("/internships/{$foreign}/approve")->assertForbidden();
        $this->assertSame('submitted', Internship::query()->whereKey($foreign)->value('status'));

        // Host companies stay with managers.
        $as->postJson('/api/internship-companies', ['name' => 'ABA Bank'])->assertForbidden();
        $this->actingAs($this->facultyAdmin)->post('/internship-companies', ['name' => 'ABA Bank'])->assertForbidden();
        $this->actingAs($this->facultyAdmin)->get('/internship-companies')->assertOk()->assertInertia(fn (Assert $page) => $page->where('canManage', false));

        // Web: reject another own-faculty application with a reason.
        $second = $this->submittedInternship(tap(Student::factory()->create(), fn (Student $s) => $this->placeInFaculty($s, $this->facultyAdmin->faculty_id)), $company);
        $this->actingAs($this->facultyAdmin)->post("/internships/{$second}/reject", ['reason' => 'Dates overlap the semester'])->assertRedirect()->assertSessionHas('success');

        // An unassigned Faculty Admin processes nothing.
        $this->actingAs($this->facultyAdminFor(null))->postJson("/api/internships/{$foreign}/review")->assertForbidden();
    }

    private function documentType(string $code): int
    {
        return DocumentType::query()->where('code', $code)->value('id');
    }

    private function requestDocument(Student $student, int $type): int
    {
        return $this->actingAs($student->user)->postJson('/api/document-requests', ['document_type_id' => $type])->assertCreated()->json('data.id');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function internshipPayload(InternshipCompany $company, array $overrides = []): array
    {
        return ['company_id' => $company->id, 'position_title' => 'Software Intern', 'start_date' => '2026-06-01', 'end_date' => '2026-08-15', 'supervisor_name' => 'Sok Dara', ...$overrides];
    }

    private function submittedInternship(Student $student, InternshipCompany $company): int
    {
        $id = $this->actingAs($student->user)->postJson('/api/internships', $this->internshipPayload($company))->assertCreated()->json('data.id');
        $this->actingAs($student->user)->postJson("/api/internships/{$id}/submit")->assertOk();

        return $id;
    }
}
