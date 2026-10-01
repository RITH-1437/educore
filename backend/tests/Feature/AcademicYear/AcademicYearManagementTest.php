<?php

namespace Tests\Feature\AcademicYear;

use App\Enums\AcademicYearStatus;
use App\Enums\Role;
use App\Enums\SemesterStatus;
use App\Models\AcademicYear;
use App\Models\Role as RoleModel;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Module 9.6 — Academic Year & Semester management.
 *
 * Covers the happy paths plus the domain rules that matter: role scoping,
 * forward-only status transitions, the single "current" year invariant and the
 * delete guards.
 */
class AcademicYearManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $universityAdmin;

    private User $lecturer;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'root@test.test']);
        $this->universityAdmin = $this->userWithRole(Role::UniversityAdmin->value, 'dean@test.test');
        $this->lecturer = $this->userWithRole(Role::Lecturer->value, 'teacher@test.test');
        $this->student = $this->userWithRole(Role::Student->value, 'pupil@test.test');
    }

    public function test_unauthenticated_api_request_is_rejected(): void
    {
        $this->getJson('/api/academic-years')->assertUnauthorized();
    }

    public function test_student_is_forbidden_from_the_academic_years_index(): void
    {
        $this->actingAs($this->student)
            ->get('/academic-years')
            ->assertForbidden();
    }

    public function test_student_is_forbidden_from_the_academic_years_api(): void
    {
        $this->actingAs($this->student)
            ->getJson('/api/academic-years')
            ->assertForbidden();
    }

    public function test_lecturer_is_forbidden_from_the_academic_years_api(): void
    {
        $this->actingAs($this->lecturer)
            ->getJson('/api/academic-years')
            ->assertForbidden();
    }

    public function test_student_cannot_create_an_academic_year(): void
    {
        $this->actingAs($this->student)
            ->postJson('/api/academic-years', $this->yearPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('academic_years', 0);
    }

    public function test_super_admin_can_view_the_academic_years_index(): void
    {
        $this->makeYear(['code' => '2025-2026']);

        $this->actingAs($this->superAdmin)
            ->get('/academic-years')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('AcademicYears/Index')
                ->has('academicYears.data', 1));
    }

    public function test_super_admin_can_view_the_academic_year_edit_screen(): void
    {
        $year = $this->makeYear(['code' => '2025-2026']);

        // Guards against the single-resource nesting trap: a bare
        // `JsonResource` prop is serialised under `data`, and the Vue page reads
        // `props.academicYear.code`.
        $this->actingAs($this->superAdmin)
            ->get("/academic-years/{$year->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('AcademicYears/Edit')
                ->where('academicYear.id', $year->id)
                ->where('academicYear.code', '2025-2026')
                ->has('semesters.data', 0));
    }

    public function test_university_admin_can_manage_the_academic_calendar(): void
    {
        $this->actingAs($this->universityAdmin)
            ->getJson('/api/academic-years')
            ->assertOk();

        $this->actingAs($this->universityAdmin)
            ->postJson('/api/academic-years', $this->yearPayload())
            ->assertCreated();

        $this->assertDatabaseHas('academic_years', ['code' => '2026-2027']);
    }

    public function test_super_admin_can_create_an_academic_year(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('/api/academic-years', $this->yearPayload())
            ->assertCreated()
            ->assertJsonPath('data.code', '2026-2027')
            ->assertJsonPath('data.status', AcademicYearStatus::Planned->value)
            ->assertJsonPath('data.is_current', false);

        $this->assertDatabaseHas('academic_years', ['code' => '2026-2027']);
    }

    public function test_creating_an_academic_year_validates_its_input(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('/api/academic-years', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code', 'name', 'start_date', 'end_date']);

        // end_date must follow start_date.
        $this->actingAs($this->superAdmin)
            ->postJson('/api/academic-years', $this->yearPayload([
                'start_date' => '2026-09-01',
                'end_date' => '2026-08-31',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);

        $this->assertDatabaseCount('academic_years', 0);
    }

    public function test_academic_year_code_must_be_unique(): void
    {
        $this->makeYear(['code' => '2026-2027']);

        $this->actingAs($this->superAdmin)
            ->postJson('/api/academic-years', $this->yearPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);

        $this->assertDatabaseCount('academic_years', 1);
    }

    public function test_unknown_status_value_is_rejected(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('/api/academic-years', $this->yearPayload(['status' => 'archived']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_super_admin_can_update_an_academic_year(): void
    {
        $academicYear = $this->makeYear(['code' => '2026-2027']);

        $this->actingAs($this->superAdmin)
            ->putJson("/api/academic-years/{$academicYear->id}", [
                'code' => '2026-2028',
                'name' => 'Renamed year',
                'start_date' => '2026-09-01',
                'end_date' => '2028-08-31',
            ])
            ->assertOk()
            ->assertJsonPath('data.code', '2026-2028');

        $this->assertDatabaseHas('academic_years', [
            'id' => $academicYear->id,
            'code' => '2026-2028',
            'name' => 'Renamed year',
        ]);
    }

    public function test_updating_a_year_keeps_its_own_code_available(): void
    {
        $academicYear = $this->makeYear(['code' => '2026-2027']);

        // A no-op code change must not trip the uniqueness rule against itself.
        $this->actingAs($this->superAdmin)
            ->putJson("/api/academic-years/{$academicYear->id}", $this->yearPayload())
            ->assertOk()
            ->assertJsonPath('data.code', '2026-2027');
    }

    public function test_status_advances_from_planned_to_active_to_completed(): void
    {
        $academicYear = $this->makeYear();

        $this->actingAs($this->superAdmin)
            ->postJson("/api/academic-years/{$academicYear->id}/status", ['status' => 'active'])
            ->assertOk()
            ->assertJsonPath('data.status', AcademicYearStatus::Active->value);

        $this->actingAs($this->superAdmin)
            ->postJson("/api/academic-years/{$academicYear->id}/status", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.status', AcademicYearStatus::Completed->value);
    }

    public function test_status_cannot_skip_or_rewind_a_step(): void
    {
        $academicYear = $this->makeYear(['status' => AcademicYearStatus::Planned]);

        // planned -> completed skips active.
        $this->actingAs($this->superAdmin)
            ->postJson("/api/academic-years/{$academicYear->id}/status", ['status' => 'completed'])
            ->assertStatus(409);

        $academicYear->update(['status' => AcademicYearStatus::Completed]);

        // completed is terminal.
        $this->actingAs($this->superAdmin)
            ->postJson("/api/academic-years/{$academicYear->id}/status", ['status' => 'active'])
            ->assertStatus(409);

        $this->assertDatabaseHas('academic_years', [
            'id' => $academicYear->id,
            'status' => AcademicYearStatus::Completed->value,
        ]);
    }

    public function test_a_planned_update_cannot_jump_the_status(): void
    {
        $academicYear = $this->makeYear(['status' => AcademicYearStatus::Planned]);

        $this->actingAs($this->superAdmin)
            ->putJson("/api/academic-years/{$academicYear->id}", $this->yearPayload(['status' => 'completed']))
            ->assertStatus(409);

        $this->assertDatabaseHas('academic_years', [
            'id' => $academicYear->id,
            'status' => AcademicYearStatus::Planned->value,
        ]);
    }

    public function test_only_one_academic_year_can_be_current(): void
    {
        $first = $this->makeYear(['code' => '2024-2025', 'status' => AcademicYearStatus::Active]);
        $first->update(['is_current' => true]);

        $second = $this->makeYear(['code' => '2025-2026', 'status' => AcademicYearStatus::Active]);

        $this->actingAs($this->superAdmin)
            ->patchJson("/api/academic-years/{$second->id}", $this->yearPayload([
                'code' => $second->code,
                'name' => $second->name,
                'start_date' => $second->start_date->toDateString(),
                'end_date' => $second->end_date->toDateString(),
                'is_current' => true,
            ]))
            ->assertOk();

        $this->assertFalse((bool) $first->fresh()->is_current);
        $this->assertTrue((bool) $second->fresh()->is_current);
        $this->assertSame(
            1,
            AcademicYear::query()->where('is_current', true)->count(),
            'Exactly one academic year may be current.',
        );
    }

    public function test_a_planned_year_cannot_be_made_current(): void
    {
        $academicYear = $this->makeYear(['status' => AcademicYearStatus::Planned]);

        $this->actingAs($this->superAdmin)
            ->patchJson("/api/academic-years/{$academicYear->id}", $this->yearPayload([
                'code' => $academicYear->code,
                'name' => $academicYear->name,
                'start_date' => $academicYear->start_date->toDateString(),
                'end_date' => $academicYear->end_date->toDateString(),
                'is_current' => true,
            ]))
            ->assertStatus(409);

        $this->assertFalse((bool) $academicYear->fresh()->is_current);
    }

    public function test_completing_the_current_year_clears_the_flag(): void
    {
        $academicYear = $this->makeYear(['status' => AcademicYearStatus::Active]);
        $academicYear->update(['is_current' => true]);

        $this->actingAs($this->superAdmin)
            ->postJson("/api/academic-years/{$academicYear->id}/status", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.is_current', false);

        $this->assertFalse((bool) $academicYear->fresh()->is_current);
    }

    public function test_an_academic_year_with_semesters_cannot_be_deleted(): void
    {
        $academicYear = $this->makeYear();
        Semester::factory()->forYear($academicYear)->create(['sequence' => 1]);

        $this->actingAs($this->superAdmin)
            ->deleteJson("/api/academic-years/{$academicYear->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('academic_years', ['id' => $academicYear->id]);
    }

    public function test_the_current_academic_year_cannot_be_deleted(): void
    {
        $academicYear = $this->makeYear(['status' => AcademicYearStatus::Active]);
        $academicYear->update(['is_current' => true]);

        $this->actingAs($this->superAdmin)
            ->deleteJson("/api/academic-years/{$academicYear->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('academic_years', ['id' => $academicYear->id]);
    }

    public function test_an_empty_academic_year_can_be_deleted(): void
    {
        $academicYear = $this->makeYear();

        $this->actingAs($this->superAdmin)
            ->deleteJson("/api/academic-years/{$academicYear->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('academic_years', ['id' => $academicYear->id]);
    }

    public function test_a_semester_can_be_added_to_an_academic_year(): void
    {
        $academicYear = $this->makeYear();

        $this->actingAs($this->superAdmin)
            ->postJson("/api/academic-years/{$academicYear->id}/semesters", [
                'name' => 'Semester 1',
                'code' => 'S1',
                'sequence' => 1,
                'start_date' => '2026-09-01',
                'end_date' => '2027-01-31',
            ])
            ->assertCreated()
            ->assertJsonPath('data.sequence', 1)
            ->assertJsonPath('data.status', SemesterStatus::Planned->value);

        $this->assertDatabaseHas('semesters', [
            'academic_year_id' => $academicYear->id,
            'sequence' => 1,
        ]);
    }

    public function test_semester_sequence_must_be_unique_within_its_year(): void
    {
        $academicYear = $this->makeYear();
        Semester::factory()->forYear($academicYear)->create(['sequence' => 1]);

        $this->actingAs($this->superAdmin)
            ->postJson("/api/academic-years/{$academicYear->id}/semesters", [
                'name' => 'Semester 1 again',
                'code' => 'X1',
                'sequence' => 1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sequence']);
    }

    public function test_student_cannot_add_a_semester(): void
    {
        $academicYear = $this->makeYear();

        $this->actingAs($this->student)
            ->postJson("/api/academic-years/{$academicYear->id}/semesters", [
                'name' => 'Semester 1',
                'code' => 'S1',
                'sequence' => 1,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('semesters', 0);
    }

    public function test_a_completed_year_cannot_receive_new_semesters(): void
    {
        $academicYear = $this->makeYear(['status' => AcademicYearStatus::Completed]);

        $this->actingAs($this->superAdmin)
            ->postJson("/api/academic-years/{$academicYear->id}/semesters", [
                'name' => 'Semester 3',
                'code' => 'S3',
                'sequence' => 3,
            ])
            ->assertStatus(409);

        $this->assertDatabaseCount('semesters', 0);
    }

    public function test_a_new_semester_cannot_be_created_as_completed(): void
    {
        $academicYear = $this->makeYear();

        $this->actingAs($this->superAdmin)
            ->postJson("/api/academic-years/{$academicYear->id}/semesters", [
                'name' => 'Semester 1',
                'code' => 'S1',
                'sequence' => 1,
                'status' => 'completed',
            ])
            ->assertStatus(409);
    }

    public function test_semester_dates_must_stay_inside_the_academic_year(): void
    {
        $academicYear = $this->makeYear();

        // January sits before the September start of the 2026-2027 year.
        $this->actingAs($this->superAdmin)
            ->postJson("/api/academic-years/{$academicYear->id}/semesters", [
                'name' => 'Semester 1',
                'code' => 'S1',
                'sequence' => 1,
                'start_date' => '2026-01-15',
                'end_date' => '2026-06-15',
            ])
            ->assertStatus(409);

        $this->assertDatabaseCount('semesters', 0);
    }

    public function test_semester_status_advances_forward_only(): void
    {
        $academicYear = $this->makeYear();
        $semester = Semester::factory()->forYear($academicYear)->create(['sequence' => 1]);

        $this->actingAs($this->superAdmin)
            ->postJson("/api/academic-years/{$academicYear->id}/semesters/{$semester->id}/status", ['status' => 'open'])
            ->assertOk()
            ->assertJsonPath('data.status', SemesterStatus::Open->value);

        // open -> planned would re-open registration on a live term.
        $this->actingAs($this->superAdmin)
            ->postJson("/api/academic-years/{$academicYear->id}/semesters/{$semester->id}/status", ['status' => 'planned'])
            ->assertStatus(409);

        $this->assertDatabaseHas('semesters', [
            'id' => $semester->id,
            'status' => SemesterStatus::Open->value,
        ]);
    }

    public function test_a_semester_with_course_offerings_cannot_be_deleted(): void
    {
        $academicYear = $this->makeYear();
        $semester = Semester::factory()->forYear($academicYear)->create(['sequence' => 1]);

        DB::table('course_offerings')->insert([
            'course_id' => $this->seedCourse(),
            'semester_id' => $semester->id,
            'status' => 'draft',
        ]);

        $this->actingAs($this->superAdmin)
            ->deleteJson("/api/academic-years/{$academicYear->id}/semesters/{$semester->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('semesters', ['id' => $semester->id]);
    }

    public function test_an_unused_semester_can_be_deleted(): void
    {
        $academicYear = $this->makeYear();
        $semester = Semester::factory()->forYear($academicYear)->create(['sequence' => 1]);

        $this->actingAs($this->superAdmin)
            ->deleteJson("/api/academic-years/{$academicYear->id}/semesters/{$semester->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('semesters', ['id' => $semester->id]);
    }

    public function test_a_semester_cannot_be_reached_through_the_wrong_year(): void
    {
        $firstYear = $this->makeYear(['code' => '2024-2025']);
        $secondYear = $this->makeYear(['code' => '2025-2026']);
        $semester = Semester::factory()->forYear($firstYear)->create(['sequence' => 1]);

        $this->actingAs($this->superAdmin)
            ->deleteJson("/api/academic-years/{$secondYear->id}/semesters/{$semester->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('semesters', ['id' => $semester->id]);
    }

    public function test_semesters_of_a_year_are_listed_in_sequence_order(): void
    {
        $academicYear = $this->makeYear();

        Semester::factory()->forYear($academicYear)->create(['name' => 'Second', 'sequence' => 2]);
        Semester::factory()->forYear($academicYear)->create(['name' => 'First', 'sequence' => 1]);

        $this->actingAs($this->superAdmin)
            ->getJson("/api/academic-years/{$academicYear->id}/semesters")
            ->assertOk()
            ->assertJsonPath('data.0.name', 'First')
            ->assertJsonPath('data.1.name', 'Second');
    }

    public function test_academic_year_list_is_paginated_and_caps_per_page(): void
    {
        $this->makeYear(['code' => '2024-2025']);
        $this->makeYear(['code' => '2025-2026']);
        $this->makeYear(['code' => '2026-2027']);

        $this->actingAs($this->superAdmin)
            ->getJson('/api/academic-years')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'code', 'name', 'status', 'is_current', 'semesters_count']],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);

        $this->actingAs($this->superAdmin)
            ->getJson('/api/academic-years?per_page=500')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }

    public function test_academic_year_list_can_be_searched_and_filtered(): void
    {
        $this->makeYear(['code' => '2024-2025', 'status' => AcademicYearStatus::Completed]);
        $this->makeYear(['code' => '2025-2026', 'status' => AcademicYearStatus::Active]);

        $this->actingAs($this->superAdmin)
            ->getJson('/api/academic-years?search=2024')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', '2024-2025');

        $this->actingAs($this->superAdmin)
            ->getJson('/api/academic-years?filters[status]=active')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', '2025-2026');
    }

    public function test_sorting_is_whitelisted(): void
    {
        $this->makeYear([
            'code' => '2024-2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-08-31',
        ]);
        $this->makeYear([
            'code' => '2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-08-31',
        ]);

        $this->actingAs($this->superAdmin)
            ->getJson('/api/academic-years?sort_by=code&sort_dir=asc')
            ->assertOk()
            ->assertJsonPath('data.0.code', '2024-2025');

        // An unknown column falls back to the default sort instead of erroring.
        $this->actingAs($this->superAdmin)
            ->getJson('/api/academic-years?sort_by=drop%20table')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_web_form_rejects_an_invalid_academic_year(): void
    {
        $this->actingAs($this->superAdmin)
            ->post('/academic-years', ['name' => 'No code'])
            ->assertSessionHasErrors(['code', 'start_date', 'end_date']);

        $this->assertDatabaseCount('academic_years', 0);
    }

    public function test_web_form_creates_an_academic_year_and_opens_its_semesters(): void
    {
        $this->actingAs($this->superAdmin)
            ->post('/academic-years', $this->yearPayload())
            ->assertRedirect();

        $academicYear = AcademicYear::query()->where('code', '2026-2027')->firstOrFail();

        $this->actingAs($this->superAdmin)
            ->post("/academic-years/{$academicYear->id}/semesters", [
                'name' => 'Semester 1',
                'code' => 'S1',
                'sequence' => 1,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('semesters', [
            'academic_year_id' => $academicYear->id,
            'sequence' => 1,
        ]);
    }

    public function test_student_cannot_add_a_semester_through_the_web_form(): void
    {
        $academicYear = $this->makeYear();

        $this->actingAs($this->student)
            ->post("/academic-years/{$academicYear->id}/semesters", [
                'name' => 'Semester 1',
                'code' => 'S1',
                'sequence' => 1,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('semesters', 0);
    }

    /**
     * Deterministic academic year: fixed code, name and span.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function makeYear(array $overrides = []): AcademicYear
    {
        $code = $overrides['code'] ?? '2026-2027';

        return AcademicYear::factory()->create(array_merge([
            'code' => $code,
            'name' => "Academic Year {$code}",
            'start_date' => '2026-09-01',
            'end_date' => '2027-08-31',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function yearPayload(array $overrides = []): array
    {
        return array_merge([
            'code' => '2026-2027',
            'name' => 'Academic Year 2026-2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-08-31',
        ], $overrides);
    }

    private function userWithRole(string $slug, string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'role_id' => RoleModel::factory()->withSlug($slug)->create()->id,
        ]);
    }

    /**
     * Minimal university -> faculty -> department -> course chain so a course
     * offering can reference a semester.
     */
    private function seedCourse(): int
    {
        $universityId = DB::table('universities')->insertGetId([
            'code' => 'ITC',
            'name' => 'ITC University',
        ]);

        $facultyId = DB::table('faculties')->insertGetId([
            'university_id' => $universityId,
            'code' => 'ENG',
            'name' => 'Faculty of Engineering',
        ]);

        $departmentId = DB::table('departments')->insertGetId([
            'faculty_id' => $facultyId,
            'code' => 'CS',
            'name' => 'Computer Science',
        ]);

        return DB::table('courses')->insertGetId([
            'department_id' => $departmentId,
            'code' => 'CS101',
            'name' => 'Introduction to Programming',
            'credits' => 3,
        ]);
    }
}
