<?php

namespace Tests\Feature\Departments;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
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
 * Department Admin request handling (`docs/39_Department-Only-Structure-Report.md`,
 * formerly report 33 for departments): a Department Admin processes the document
 * requests and internships of their department's students; revoking documents
 * and managing companies stay with managers; other departments' students and
 * unassigned admins are refused.
 */
class DepartmentAdminRequestHandlingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $departmentAdmin;

    private Student $student;

    private Student $outsider;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
        Notification::fake();

        $mine = Department::factory()->create();
        $this->admin = User::factory()->superAdmin()->create();
        $this->departmentAdmin = $this->departmentAdminFor($mine);
        $this->student = Student::factory()->create();
        $this->placeInDepartment($this->student, $mine);
        $this->outsider = Student::factory()->create();
        $this->placeInDepartment($this->outsider, Department::factory()->create());
    }

    public function test_department_admin_processes_their_department_document_requests(): void
    {
        $this->seed(DocumentTypeSeeder::class);
        $certificate = $this->documentType(DocumentType::STUDENT_CERTIFICATE);
        $own = $this->requestDocument($this->student, $certificate);
        $foreign = $this->requestDocument($this->outsider, $certificate);

        // The queue shows only their department's requests, with processing but no revoke controls.
        $this->actingAs($this->departmentAdmin)->get('/documents')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('requests.data', 1)->where('canProcess', true)->where('canRevoke', false));

        // Approve and generate: processed by and audited as the Department Admin; the student is told.
        $this->actingAs($this->departmentAdmin)->postJson("/api/document-requests/{$own}/approve")->assertOk()->assertJsonPath('data.status', 'approved');
        $this->assertSame($this->departmentAdmin->id, DocumentRequest::query()->find($own)->processed_by);
        $this->assertSame($this->departmentAdmin->id, AuditLog::query()->where('action', 'document_request.approved')->value('actor_id'));
        Notification::assertSentTo($this->student->user, DocumentRequestUpdated::class);
        $this->actingAs($this->departmentAdmin)->postJson("/api/document-requests/{$own}/generate")->assertCreated()->assertJsonPath('data.status', 'generated');

        // Revoking an issued document stays with managers (API and web).
        $document = Document::query()->firstOrFail();
        $this->actingAs($this->departmentAdmin)->postJson("/api/documents/{$document->id}/revoke")->assertForbidden();
        $this->actingAs($this->departmentAdmin)->post("/documents/{$document->id}/revoke")->assertForbidden();
        $this->actingAs($this->admin)->postJson("/api/documents/{$document->id}/revoke")->assertOk();

        // Another department's student is refused (API and web).
        $this->actingAs($this->departmentAdmin)->postJson("/api/document-requests/{$foreign}/approve")->assertForbidden();
        $this->actingAs($this->departmentAdmin)->postJson("/api/document-requests/{$foreign}/reject", ['rejection_reason' => 'No'])->assertForbidden();
        $this->actingAs($this->departmentAdmin)->post("/document-requests/{$foreign}/approve")->assertForbidden();
        $this->assertSame(DocumentRequest::STATUS_PENDING, DocumentRequest::query()->find($foreign)->status);

        // Web reject for their own student.
        $second = $this->requestDocument($this->student, $this->documentType(DocumentType::ENROLLMENT_CERTIFICATE));
        $this->actingAs($this->departmentAdmin)->post("/document-requests/{$second}/reject", ['rejection_reason' => 'Ask for a transcript instead'])
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame(DocumentRequest::STATUS_REJECTED, DocumentRequest::query()->find($second)->status);

        // An unassigned Department Admin processes nothing.
        $nobody = $this->departmentAdminFor(null);
        $this->actingAs($nobody)->get('/documents')->assertInertia(fn (Assert $page) => $page->has('requests.data', 0)->where('canProcess', false));
        $this->actingAs($nobody)->postJson("/api/document-requests/{$foreign}/approve")->assertForbidden();
    }

    public function test_department_admin_processes_their_department_internships(): void
    {
        $company = InternshipCompany::query()->create(['name' => 'Smart Axiata', 'industry' => 'Telecom', 'is_active' => true]);
        $own = $this->submittedInternship($this->student, $company);
        $foreign = $this->submittedInternship($this->outsider, $company);
        $as = $this->actingAs($this->departmentAdmin);

        // Review → approve → start, audited as the Department Admin; the student is notified.
        $as->postJson("/api/internships/{$own}/review")->assertOk()->assertJsonPath('data.status', 'under_review');
        $as->postJson("/api/internships/{$own}/approve", ['note' => 'Good fit'])->assertOk()->assertJsonPath('data.status', 'approved');
        $as->postJson("/api/internships/{$own}/start")->assertOk()->assertJsonPath('data.status', 'in_progress');
        Notification::assertSentTo($this->student->user, InternshipStatusChanged::class);
        $this->assertSame($this->departmentAdmin->id, AuditLog::query()->where('action', 'internship.approved')->value('actor_id'));

        // Evaluate, review a report and edit the placement (a manager-only path until now).
        $as->postJson("/api/internships/{$own}/evaluations", ['evaluator_type' => 'academic', 'score' => 85, 'rating' => 'good'])->assertOk()->assertJsonCount(1, 'data.evaluations');
        $report = InternshipReport::query()->create(['internship_id' => $own, 'report_type' => 'initial', 'title' => 'Week 1', 'submitted_at' => now()]);
        $as->postJson("/api/internship-reports/{$report->id}/review", ['reviewer_comment' => 'Clear'])->assertOk();
        $as->putJson("/api/internships/{$own}", $this->internshipPayload($company, ['position_title' => 'Network Intern']))->assertOk()->assertJsonPath('data.position_title', 'Network Intern');
        $this->actingAs($this->departmentAdmin)->get("/internships/{$own}")->assertOk()->assertInertia(fn (Assert $page) => $page->where('canProcess', true));

        // Another department's student is refused (API and web).
        $as->postJson("/api/internships/{$foreign}/review")->assertForbidden();
        $as->postJson("/api/internships/{$foreign}/evaluations", ['evaluator_type' => 'academic', 'score' => 70])->assertForbidden();
        $as->putJson("/api/internships/{$foreign}", $this->internshipPayload($company))->assertForbidden();
        $this->actingAs($this->departmentAdmin)->post("/internships/{$foreign}/approve")->assertForbidden();
        $this->assertSame('submitted', Internship::query()->whereKey($foreign)->value('status'));

        // Host companies stay with managers.
        $as->postJson('/api/internship-companies', ['name' => 'ABA Bank'])->assertForbidden();
        $this->actingAs($this->departmentAdmin)->post('/internship-companies', ['name' => 'ABA Bank'])->assertForbidden();
        $this->actingAs($this->departmentAdmin)->get('/internship-companies')->assertOk()->assertInertia(fn (Assert $page) => $page->where('canManage', false));

        // Web: reject another own-department application with a reason.
        $second = $this->submittedInternship(tap(Student::factory()->create(), fn (Student $s) => $this->placeInDepartment($s, $this->departmentAdmin->department_id)), $company);
        $this->actingAs($this->departmentAdmin)->post("/internships/{$second}/reject", ['reason' => 'Dates overlap the semester'])->assertRedirect()->assertSessionHas('success');

        // An unassigned Department Admin processes nothing.
        $this->actingAs($this->departmentAdminFor(null))->postJson("/api/internships/{$foreign}/review")->assertForbidden();
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
