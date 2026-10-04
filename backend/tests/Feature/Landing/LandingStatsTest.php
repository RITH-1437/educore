<?php

namespace Tests\Feature\Landing;

use App\Models\Lecturer;
use App\Models\Student;
use Database\Seeders\LecturerSeeder;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\StudentSeeder;
use Database\Seeders\UniversityStructureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The public landing page shows live, aggregate-only figures
 * (`docs/37_Landing-Page-Redesign-Report.md`): nothing invented when the
 * database is empty, and what is inserted appears.
 */
class LandingStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_empty_database_shows_no_figures(): void
    {
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Landing')
            ->where('stats.semester', null)
            ->where('stats.overview.students_active', 0)
            ->where('stats.overview.lecturers_active', 0)
            ->where('stats.overview.students_enrolled', null)
            ->where('stats.overview.attendance_rate', null)
            ->where('stats.waiting.document_requests_pending', 0)
            ->where('stats.enrollment_by_program', [])
            ->where('stats.grade_distribution', [])
            ->where('stats.courses', [])
        );
    }

    public function test_inserted_records_appear_in_the_figures(): void
    {
        $this->seedPeople();

        $students = Student::query()->where('status', Student::STATUS_ACTIVE)->count();
        $lecturers = Lecturer::query()->where('is_active', true)->count();
        $this->assertGreaterThan(0, $students);
        $this->assertGreaterThan(0, $lecturers);

        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('stats.overview.students_active', $students)
            ->where('stats.overview.lecturers_active', $lecturers)
        );
    }

    public function test_the_public_payload_contains_no_personal_data(): void
    {
        $this->seedPeople();
        $student = Student::query()->with('user')->firstOrFail();
        $lecturer = Lecturer::query()->with('user')->firstOrFail();

        $stats = json_encode($this->get('/')->assertOk()->viewData('page')['props']['stats']);

        foreach ([$student->user->email, $student->fullName(), $student->student_number, $lecturer->user->email, $lecturer->fullName()] as $personal) {
            $this->assertStringNotContainsString($personal, $stats);
        }
    }

    public function test_signed_in_users_are_still_sent_to_their_dashboard(): void
    {
        $this->seedPeople();
        $student = Student::query()->with('user')->firstOrFail();

        $this->actingAs($student->user)->get('/')->assertRedirect(route('role-dashboard'));
    }

    private function seedPeople(): void
    {
        foreach ([RoleSeeder::class, UniversityStructureSeeder::class, ProgramSeeder::class, LecturerSeeder::class, StudentSeeder::class] as $seeder) {
            $this->seed($seeder);
        }
    }
}
