<?php

namespace Tests\Feature\Internships;

use App\Enums\Role;
use App\Models\Internship;
use App\Models\InternshipCompany;
use App\Models\InternshipReport;
use App\Models\Role as RoleModel;
use App\Models\Student;
use App\Models\User;
use App\Notifications\InternshipStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Module 9.22 — application workflow, one open application per student,
 * reports with private files, evaluations, companies, authorization.
 */
class InternshipTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Student $student;

    private InternshipCompany $company;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
        Notification::fake();

        $this->admin = User::factory()->superAdmin()->create();
        $this->student = Student::factory()->create();
        $this->company = InternshipCompany::query()->create(['name' => 'Smart Axiata', 'industry' => 'Telecom', 'is_active' => true]);
    }

    public function test_full_workflow(): void
    {
        $id = $this->actingAs($this->student->user)->postJson('/api/internships', $this->payload())
            ->assertCreated()->assertJsonPath('data.status', 'draft')->assertJsonPath('data.company.name', 'Smart Axiata')->json('data.id');
        $url = "/api/internships/{$id}";

        $this->actingAs($this->student->user)->putJson($url, $this->payload(['position_title' => 'Network Intern']))->assertOk()->assertJsonPath('data.position_title', 'Network Intern');
        $this->actingAs($this->student->user)->postJson("{$url}/submit")->assertOk()->assertJsonPath('data.status', 'submitted');
        // No more student edits after submitting.
        $this->actingAs($this->student->user)->putJson($url, $this->payload())->assertStatus(409);

        $this->actingAs($this->admin)->postJson("{$url}/review")->assertOk()->assertJsonPath('data.status', 'under_review');
        $this->actingAs($this->admin)->postJson("{$url}/start")->assertStatus(409); // not approved yet
        $this->actingAs($this->admin)->postJson("{$url}/approve", ['note' => 'Dates confirmed'])->assertOk()->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.notes', fn ($notes) => str_contains($notes, 'approved by') && str_contains($notes, 'Dates confirmed'));
        Notification::assertSentTo($this->student->user, InternshipStatusChanged::class, fn ($n) => $n->internship->status === 'approved');

        // Reports: no final report before the internship starts.
        $this->actingAs($this->student->user)->postJson("{$url}/reports", ['report_type' => 'final', 'title' => 'Final'])->assertStatus(409);
        $this->actingAs($this->student->user)->post("{$url}/reports", ['report_type' => 'initial', 'title' => 'Week 1', 'file' => UploadedFile::fake()->create('week1.pdf', 120, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('data.reports.0.file.name', 'week1.pdf');

        $this->actingAs($this->admin)->postJson("{$url}/start")->assertOk()->assertJsonPath('data.status', 'in_progress');
        $this->actingAs($this->admin)->postJson("{$url}/complete")->assertStatus(409); // no final report yet
        $this->actingAs($this->student->user)->postJson("{$url}/reports", ['report_type' => 'final', 'title' => 'Final report', 'summary' => 'Done'])->assertCreated();

        $this->actingAs($this->admin)->postJson("{$url}/evaluations", ['evaluator_type' => 'supervisor', 'score' => 88, 'rating' => 'good'])
            ->assertOk()->assertJsonPath('data.evaluations.0.evaluator_name', $this->payload()['supervisor_name']);
        $this->actingAs($this->admin)->postJson("{$url}/evaluations", ['evaluator_type' => 'supervisor', 'score' => 91])->assertOk()->assertJsonCount(1, 'data.evaluations');
        $this->actingAs($this->admin)->postJson("{$url}/complete", ['note' => 'Well done'])->assertOk()->assertJsonPath('data.status', 'completed');

        // Final: nothing moves any more.
        $this->actingAs($this->admin)->postJson("{$url}/cancel", ['reason' => 'x'])->assertStatus(409);
        $this->actingAs($this->admin)->putJson($url, $this->payload())->assertStatus(409);
    }

    public function test_one_open_application_and_resubmission_after_rejection(): void
    {
        $id = $this->apply();
        $this->actingAs($this->student->user)->postJson('/api/internships', $this->payload())->assertStatus(409);

        $this->actingAs($this->student->user)->postJson("/api/internships/{$id}/submit");
        $this->actingAs($this->admin)->postJson("/api/internships/{$id}/reject")->assertJsonValidationErrors('reason');
        $this->actingAs($this->admin)->postJson("/api/internships/{$id}/reject", ['reason' => 'Unaccredited company'])->assertOk()->assertJsonPath('data.status', 'rejected');
        Notification::assertSentTo($this->student->user, InternshipStatusChanged::class, fn ($n) => $n->internship->status === 'rejected');

        // A rejected student starts a new application.
        $this->actingAs($this->student->user)->postJson('/api/internships', $this->payload())->assertCreated();
        $this->assertSame(2, Internship::query()->count());
    }

    public function test_cancellation_rules(): void
    {
        $id = $this->apply();
        $this->actingAs($this->student->user)->postJson("/api/internships/{$id}/submit");
        $this->actingAs($this->student->user)->postJson("/api/internships/{$id}/cancel")->assertOk()->assertJsonPath('data.status', 'cancelled');

        // After approval only staff can end it early, and only with a reason.
        $id = $this->apply();
        $this->actingAs($this->student->user)->postJson("/api/internships/{$id}/submit");
        $this->actingAs($this->admin)->postJson("/api/internships/{$id}/approve");
        $this->actingAs($this->student->user)->postJson("/api/internships/{$id}/cancel")->assertStatus(409);
        $this->actingAs($this->admin)->postJson("/api/internships/{$id}/cancel")->assertJsonValidationErrors('reason');
        $this->actingAs($this->admin)->postJson("/api/internships/{$id}/cancel", ['reason' => 'Company closed'])->assertOk()->assertJsonPath('data.status', 'cancelled');
    }

    public function test_validation_and_companies(): void
    {
        $as = $this->actingAs($this->student->user);
        $as->postJson('/api/internships', $this->payload(['end_date' => '2026-05-01']))->assertJsonValidationErrors('end_date');
        $as->postJson('/api/internships', $this->payload(['supervisor_name' => '']))->assertJsonValidationErrors('supervisor_name');

        $this->company->update(['is_active' => false]);
        $as->postJson('/api/internships', $this->payload())->assertJsonValidationErrors('company_id');
        $as->getJson('/api/internship-companies')->assertOk()->assertJsonCount(0, 'data');

        $this->actingAs($this->admin)->getJson('/api/internship-companies')->assertJsonCount(1, 'data');
        $this->actingAs($this->admin)->postJson('/api/internship-companies', ['name' => 'Smart Axiata'])->assertJsonValidationErrors('name');
        $this->actingAs($this->admin)->postJson('/api/internship-companies', ['name' => 'ABA Bank', 'website' => 'not-a-url'])->assertJsonValidationErrors('website');
        $this->actingAs($this->admin)->postJson('/api/internship-companies', ['name' => 'ABA Bank', 'industry' => 'Banking'])->assertCreated()->assertJsonPath('data.is_active', true);
        $this->actingAs($this->student->user)->postJson('/api/internship-companies', ['name' => 'Mine'])->assertForbidden();

        // Report files: PDF / DOCX only.
        $id = $this->apply(['company_id' => InternshipCompany::query()->where('name', 'ABA Bank')->value('id')]);
        $this->actingAs($this->student->user)->postJson("/api/internships/{$id}/submit");
        $this->actingAs($this->admin)->postJson("/api/internships/{$id}/approve");
        $this->actingAs($this->student->user)->post("/api/internships/{$id}/reports", ['report_type' => 'initial', 'title' => 'x', 'file' => UploadedFile::fake()->create('virus.exe', 10)], ['Accept' => 'application/json'])
            ->assertJsonValidationErrors('file');
    }

    public function test_access(): void
    {
        $id = $this->apply();
        $other = Student::factory()->create();
        $faculty = User::factory()->create(['role_id' => RoleModel::factory()->withSlug(Role::FacultyAdmin->value)->create()->id]);

        $this->actingAs($other->user)->getJson("/api/internships/{$id}")->assertForbidden();
        $this->actingAs($other->user)->postJson("/api/internships/{$id}/submit")->assertForbidden();
        $this->actingAs($other->user)->getJson('/api/internships')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($this->student->user)->postJson("/api/internships/{$id}/approve")->assertForbidden();
        $this->actingAs($this->admin)->postJson('/api/internships', $this->payload())->assertForbidden();

        $this->actingAs($faculty)->getJson('/api/internships')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($faculty)->getJson("/api/internships/{$id}")->assertOk();
        $this->actingAs($faculty)->postJson("/api/internships/{$id}/review")->assertForbidden();

        $this->actingAs($this->student->user)->postJson("/api/internships/{$id}/fly")->assertNotFound();
    }

    public function test_web_pages(): void
    {
        $this->actingAs($this->student->user)->get('/my-internships')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Internships/Mine')->where('current', null)->has('companies', 1));
        $this->actingAs($this->student->user)->post('/my-internships', $this->payload())->assertSessionHas('success');
        $internship = Internship::query()->firstOrFail();
        $this->actingAs($this->student->user)->post("/internships/{$internship->id}/submit")->assertSessionHas('success');

        $this->actingAs($this->admin)->get('/internships')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Internships/Index')->has('internships.data', 1));
        $this->actingAs($this->admin)->get("/internships/{$internship->id}")->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Internships/Show')->where('canProcess', true)->where('isOwner', false));
        $this->actingAs($this->admin)->post("/internships/{$internship->id}/approve")->assertSessionHas('success');
        $this->actingAs($this->admin)->get('/internship-companies')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Internships/Companies')->has('companies', 1));

        $this->actingAs($this->student->user)->get("/internships/{$internship->id}")->assertOk()->assertInertia(fn (Assert $page) => $page->where('isOwner', true));
        $this->actingAs($this->student->user)->get('/internships')->assertForbidden();

        $report = InternshipReport::query()->create(['internship_id' => $internship->id, 'report_type' => 'initial', 'title' => 'No file', 'submitted_at' => now()]);
        $this->actingAs($this->student->user)->get("/internship-reports/{$report->id}/file")->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'company_id' => $this->company->id,
            'position_title' => 'Software Intern',
            'description' => 'Backend team',
            'start_date' => '2026-06-01',
            'end_date' => '2026-08-15',
            'supervisor_name' => 'Sok Dara',
            'supervisor_email' => 'dara@example.com',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function apply(array $overrides = []): int
    {
        return $this->actingAs($this->student->user)->postJson('/api/internships', $this->payload($overrides))->assertCreated()->json('data.id');
    }
}
