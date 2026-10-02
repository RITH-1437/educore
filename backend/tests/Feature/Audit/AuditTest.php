<?php

namespace Tests\Feature\Audit;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Grade;
use App\Models\Lecturer;
use App\Models\Role as RoleModel;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EnrollmentService;
use App\Services\GradingService;
use App\Services\InvoiceService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;
use Tests\TestCase;

/**
 * Module 9.24 — append-only trail, sensitive events recorded without secrets,
 * transactional consistency, revoked access for deactivated accounts, the
 * Super Admin viewer.
 */
class AuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Carbon::setTestNow('2026-03-10 09:00:00');
        $this->admin = User::factory()->superAdmin()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_trail_is_append_only_in_model_and_database(): void
    {
        $actor = User::factory()->create();
        $log = app(AuditLogger::class)->record('test.created', $actor, after: ['name' => 'x'], actor: $actor);

        try {
            $log->update(['action' => 'tampered']);
            $this->fail('Model update should be refused.');
        } catch (LogicException) {
        }

        try {
            $log->delete();
            $this->fail('Model delete should be refused.');
        } catch (LogicException) {
        }

        foreach (["UPDATE audit_logs SET action = 'tampered'", 'DELETE FROM audit_logs'] as $sql) {
            try {
                DB::transaction(fn () => DB::statement($sql));
                $this->fail("{$sql} should be refused by the trigger.");
            } catch (QueryException $e) {
                $this->assertStringContainsString('append-only', $e->getMessage());
            }
        }

        // The one permitted change: removing the actor nulls actor_id (FK ON DELETE SET NULL).
        $actor->forceDelete();
        $this->assertNull($log->refresh()->actor_id);
        $this->assertSame('test.created', $log->action);
    }

    public function test_user_changes_are_audited_without_secrets(): void
    {
        $user = User::factory()->create(['name' => 'Dara']);
        $lecturerRole = RoleModel::factory()->withSlug(Role::Lecturer->value)->create();

        $this->actingAs($this->admin)->putJson("/api/users/{$user->id}", [
            'name' => 'Dara', 'email' => $user->email, 'role_id' => $lecturerRole->id,
            'password' => 'NewSecret123!', 'password_confirmation' => 'NewSecret123!',
        ])->assertOk();

        $log = AuditLog::query()->where('action', 'user.updated')->firstOrFail();
        $this->assertSame($this->admin->id, $log->actor_id);
        $this->assertSame($lecturerRole->id, $log->after_values['role_id']);
        $this->assertArrayNotHasKey('password', $log->after_values);
        $this->assertStringContainsString('Changed: password', $log->description);
        $this->assertStringNotContainsString('NewSecret123', json_encode($log->toArray()));
    }

    public function test_academic_and_finance_events_are_audited(): void
    {
        $semester = Semester::factory()->create(['status' => 'open', 'start_date' => '2026-02-01', 'end_date' => '2026-06-30']);
        $offering = CourseOffering::factory()->create(['course_id' => Course::factory()->create()->id, 'semester_id' => $semester->id, 'status' => 'open']);
        $section = Section::factory()->create(['course_offering_id' => $offering->id, 'status' => 'open', 'capacity' => 30]);
        $student = Student::factory()->create();

        $enrollment = app(EnrollmentService::class)->enroll($student, $section);
        $this->assertDatabaseHas('audit_logs', ['action' => 'enrollment.created', 'auditable_id' => $enrollment->id]);

        // Exam result correction: before / after score (the 9.13 open item).
        $exam = Exam::factory()->create(['section_id' => $section->id, 'scheduled_date' => '2026-03-01']);
        $result = ExamResult::query()->create(['exam_id' => $exam->id, 'enrollment_id' => $enrollment->id, 'score' => 55]);
        $this->actingAs($this->admin)->patchJson("/api/exam-results/{$result->id}", ['score' => 72])->assertOk();
        $correction = AuditLog::query()->where('action', 'exam_result.corrected')->firstOrFail();
        $this->assertEquals(55, $correction->before_values['score']);
        $this->assertEquals(72, $correction->after_values['score']);

        // Grade approval lists who got what.
        app(GradingService::class)->saveScale(GradingService::DEFAULT_BANDS);
        Grade::query()->create(['enrollment_id' => $enrollment->id, 'letter_grade' => 'B', 'grade_point' => 3, 'total_score' => 74, 'status' => 'submitted']);
        $this->actingAs($this->admin)->postJson("/api/sections/{$section->id}/grades/approve")->assertOk();
        $approved = AuditLog::query()->where('action', 'grades.approved')->firstOrFail();
        $this->assertSame([$enrollment->id], array_column($approved->after_values['grades'], 'enrollment_id'));
        $this->assertSame('B', $approved->after_values['grades'][0]['letter_grade']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'grading_scale.updated']);

        // Finance: payment, reversal; a refused payment leaves no trace (same transaction).
        $invoices = app(InvoiceService::class);
        $this->actingAs($this->admin);
        $invoice = $invoices->create(['student_id' => $student->id, 'title' => 'Tuition', 'due_date' => '2026-04-01', 'items' => [['description' => 'Tuition', 'quantity' => 1, 'unit_price' => 100]]]);
        $payment = $invoices->recordPayment($invoice, ['amount' => 40, 'paid_on' => '2026-03-10', 'method' => 'cash'], $this->admin);
        $this->actingAs($this->admin)->postJson("/api/invoices/{$invoice->id}/payments", ['amount' => 500, 'paid_on' => '2026-03-10', 'method' => 'cash'])->assertUnprocessable();
        $invoices->reverse($payment, 'Wrong student', $this->admin);

        $this->assertSame(['invoice.created', 'payment.recorded', 'payment.reversed'], AuditLog::query()->where('action', 'like', 'invoice.%')->orWhere('action', 'like', 'payment.%')->orderBy('id')->pluck('action')->all());
        $this->assertSame('Wrong student', AuditLog::query()->where('action', 'payment.reversed')->value('description'));

        // Student status change.
        $this->actingAs($this->admin)->postJson("/api/students/{$student->id}/status", ['status' => 'suspended'])->assertOk();
        $status = AuditLog::query()->where('action', 'student.status_changed')->firstOrFail();
        $this->assertSame(['status' => 'active'], $status->before_values);
        $this->assertSame(['status' => 'suspended'], $status->after_values);
    }

    public function test_sign_in_activity_is_audited(): void
    {
        $user = User::factory()->create(['email' => 'dara@example.com']);

        $this->post('/login', ['email' => 'dara@example.com', 'password' => 'wrong-password']);
        $failed = AuditLog::query()->where('action', 'auth.failed')->firstOrFail();
        $this->assertStringContainsString('dara@example.com', $failed->description);
        $this->assertStringNotContainsString('wrong-password', json_encode($failed->toArray()));

        $this->post('/login', ['email' => 'dara@example.com', 'password' => 'password'])->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login', 'actor_id' => $user->id]);

        $this->post('/logout');
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.logout', 'actor_id' => $user->id]);

        foreach (range(1, 6) as $attempt) {
            $this->post('/login', ['email' => 'dara@example.com', 'password' => "nope-{$attempt}"]);
        }
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.lockout']);
    }

    public function test_deactivated_accounts_lose_sessions_and_tokens(): void
    {
        $user = Lecturer::factory()->create()->user;

        // Web session.
        $this->actingAs($user)->get('/dashboard')->assertOk();
        $user->update(['is_active' => false]);
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest('web');
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.access_revoked', 'actor_id' => $user->id]);
    }

    public function test_deactivated_accounts_lose_api_tokens(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/user')->assertOk();
        $user->update(['is_active' => false]);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/user')->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.');
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_viewer_is_super_admin_only_and_read_only(): void
    {
        $this->actingAs($this->admin);
        app(AuditLogger::class)->record('grades.approved', $this->admin, after: ['x' => 1], description: 'Section A');
        app(AuditLogger::class)->record('invoice.created', $this->admin, description: 'INV-1');
        $id = AuditLog::query()->where('action', 'grades.approved')->value('id');

        $this->actingAs($this->admin)->getJson('/api/audit-logs?filters[area]=grades')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.area', 'grades');
        $this->actingAs($this->admin)->getJson('/api/audit-logs?search=INV-1')->assertJsonCount(1, 'data');
        $this->actingAs($this->admin)->getJson("/api/audit-logs/{$id}")->assertOk()->assertJsonPath('data.after.x', 1)->assertJsonPath('data.actor.id', $this->admin->id);
        $this->actingAs($this->admin)->getJson('/api/audit-logs?filters[area]=DROP TABLE')->assertJsonValidationErrors('filters.area');

        $university = User::factory()->create(['role_id' => RoleModel::factory()->withSlug(Role::UniversityAdmin->value)->create()->id]);
        $this->actingAs($university)->getJson('/api/audit-logs')->assertForbidden();
        $this->actingAs(Lecturer::factory()->create()->user)->getJson("/api/audit-logs/{$id}")->assertForbidden();

        // No write endpoints exist.
        $this->actingAs($this->admin)->postJson('/api/audit-logs', ['action' => 'x'])->assertStatus(405);
        $this->actingAs($this->admin)->deleteJson("/api/audit-logs/{$id}")->assertStatus(405);

        $this->actingAs($this->admin)->get('/audit-logs')->assertOk()->assertInertia(fn (Assert $page) => $page->component('AuditLogs/Index')->has('areas'));
        $this->actingAs($this->admin)->get("/audit-logs/{$id}")->assertOk()->assertInertia(fn (Assert $page) => $page->component('AuditLogs/Show')->where('log.id', $id));
        $this->actingAs($university)->get('/audit-logs')->assertForbidden();
    }
}
