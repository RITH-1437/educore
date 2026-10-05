<?php

namespace Tests\Feature\Notifications;

use App\Enums\Role;
use App\Models\Department;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Internship;
use App\Models\InternshipCompany;
use App\Models\Program;
use App\Models\Role as RoleModel;
use App\Models\Student;
use App\Models\StudentProgram;
use App\Models\User;
use App\Notifications\DocumentRequestSubmitted;
use App\Notifications\InternshipSubmitted;
use App\Services\StaffNotifier;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Staff request notices (`docs/43_Staff-Request-Notices-Report.md`): a new
 * document request or internship application reaches exactly the staff who
 * may process it — Super Admins, University Admins and the Department Admins
 * of the student's department — and nobody else.
 */
class StaffRequestNoticeTest extends TestCase
{
    use RefreshDatabase;

    private Department $mine;

    private Department $theirs;

    private Student $student;

    /** @var list<User> */
    private array $handlers;

    /** @var list<User> */
    private array $bystanders;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->mine = Department::factory()->create();
        $this->theirs = Department::factory()->create();
        $this->student = Student::factory()->create();
        $this->placeInDepartment($this->student, $this->mine);

        $this->handlers = [
            User::factory()->superAdmin()->create(),
            $this->userWithRole(Role::UniversityAdmin),
            $this->departmentAdminFor($this->mine),
        ];
        $this->bystanders = [
            $this->departmentAdminFor($this->theirs),
            $this->departmentAdminFor(null),
            $this->userWithRole(Role::UniversityAdmin, ['is_active' => false]),
            tap($this->departmentAdminFor($this->mine))->update(['is_active' => false]),
            $this->userWithRole(Role::Lecturer),
            $this->student->user,
        ];
    }

    public function test_a_document_request_notifies_the_staff_who_can_approve_it(): void
    {
        $this->seed(DocumentTypeSeeder::class);
        $type = DocumentType::query()->where('code', DocumentType::STUDENT_CERTIFICATE)->value('id');

        $id = $this->actingAs($this->student->user)->postJson('/api/document-requests', ['document_type_id' => $type])->assertCreated()->json('data.id');

        Notification::assertSentTo($this->handlers, DocumentRequestSubmitted::class, fn (DocumentRequestSubmitted $n) => $n->request->id === $id);
        Notification::assertNotSentTo($this->bystanders, DocumentRequestSubmitted::class);

        // A refused duplicate request notifies nobody again.
        $this->actingAs($this->student->user)->postJson('/api/document-requests', ['document_type_id' => $type])->assertStatus(409);
        Notification::assertSentToTimes($this->handlers[2], DocumentRequestSubmitted::class, 1);

        // The message names the student and opens the pending queue; it lands in the inbox too.
        $notice = new DocumentRequestSubmitted(DocumentRequest::query()->findOrFail($id));
        $inbox = $notice->toInbox($this->handlers[2]);
        $this->assertSame(['document', '/documents?filters[status]=pending'], [$inbox['kind'], $inbox['url']]);
        $this->assertStringContainsString($this->student->student_number, $inbox['body']);
        $this->assertContains('database', $notice->via($this->handlers[2]));
        $this->assertContains('mail', $notice->via($this->handlers[2]));
    }

    public function test_an_internship_application_notifies_the_staff_who_review_it_once_submitted(): void
    {
        $company = InternshipCompany::query()->create(['name' => 'Smart Axiata', 'industry' => 'Telecom', 'is_active' => true]);
        $id = $this->actingAs($this->student->user)->postJson('/api/internships', [
            'company_id' => $company->id, 'position_title' => 'Software Intern', 'start_date' => '2026-06-01', 'end_date' => '2026-08-15', 'supervisor_name' => 'Sok Dara',
        ])->assertCreated()->json('data.id');

        // A draft is the student's own business.
        Notification::assertNothingSentTo($this->handlers[2]);

        $this->actingAs($this->student->user)->postJson("/api/internships/{$id}/submit")->assertOk();

        Notification::assertSentTo($this->handlers, InternshipSubmitted::class, fn (InternshipSubmitted $n) => $n->internship->id === $id);
        Notification::assertNotSentTo($this->bystanders, InternshipSubmitted::class);

        $inbox = (new InternshipSubmitted(Internship::query()->findOrFail($id)))->toInbox($this->handlers[2]);
        $this->assertSame(['internship', "/internships/{$id}"], [$inbox['kind'], $inbox['url']]);
        $this->assertStringContainsString('Software Intern', $inbox['body']);
        $this->assertStringContainsString('2026-06-01 to 2026-08-15', $inbox['body']);
    }

    /** A transferred student still belongs to the department of their earlier program (`DepartmentScope`). */
    public function test_a_student_of_two_departments_reaches_both_department_admins(): void
    {
        StudentProgram::query()->create([
            'student_id' => $this->student->id, 'program_id' => Program::factory()->create(['department_id' => $this->theirs->id])->id,
            'started_on' => '2024-09-01', 'ended_on' => '2025-08-31', 'status' => StudentProgram::STATUS_TRANSFERRED,
        ]);

        $this->assertEqualsCanonicalizing(
            [...array_map(fn (User $u) => $u->id, $this->handlers), $this->bystanders[0]->id],
            app(StaffNotifier::class)->handlersOf($this->student)->modelKeys(),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function userWithRole(Role $role, array $attributes = []): User
    {
        $model = RoleModel::query()->firstWhere('slug', $role->value) ?? RoleModel::factory()->withSlug($role->value)->create();

        return User::factory()->create(['role_id' => $model->id, ...$attributes]);
    }
}
