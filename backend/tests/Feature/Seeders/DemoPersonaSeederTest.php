<?php

namespace Tests\Feature\Seeders;

use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Lecturer;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DemoPersonaSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DemoPersonaSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed base roles first to ensure role slugs and IDs exist
        $this->seed(RoleSeeder::class);
    }

    public function test_demo_persona_seeder_executes_and_populates_all_personas(): void
    {
        $this->seed(DemoPersonaSeeder::class);

        // Verify users created
        $this->assertDatabaseHas('users', ['email' => 'admin@educore.kh']);
        $this->assertDatabaseHas('users', ['email' => 'university@educore.kh']);
        $this->assertDatabaseHas('users', ['email' => 'department@educore.kh']);
        $this->assertDatabaseHas('users', ['email' => 'lecturer@educore.kh']);
        $this->assertDatabaseHas('users', ['email' => 'student@educore.kh']);
        $this->assertDatabaseHas('users', ['email' => 'peer.student@educore.kh']);

        // Verify departmental link
        $deptUser = User::where('email', 'department@educore.kh')->firstOrFail();
        $this->assertNotNull($deptUser->department_id);
        $this->assertEquals('CSE', $deptUser->department->code);

        // Verify lecturer profile & sections
        $lecturerUser = User::where('email', 'lecturer@educore.kh')->firstOrFail();
        $lecturer = Lecturer::where('user_id', $lecturerUser->id)->firstOrFail();
        $this->assertGreaterThanOrEqual(2, $lecturer->sections()->count());

        // Verify student profile, enrollments, and invoices
        $studentUser = User::where('email', 'student@educore.kh')->firstOrFail();
        $student = Student::where('user_id', $studentUser->id)->firstOrFail();
        $this->assertGreaterThanOrEqual(2, Enrollment::where('student_id', $student->id)->count());
        $this->assertGreaterThanOrEqual(1, Invoice::where('student_id', $student->id)->count());
    }

    public function test_demo_persona_seeder_is_idempotent(): void
    {
        // First run
        $this->seed(DemoPersonaSeeder::class);
        $userCountFirst = User::count();
        $deptCountFirst = Department::count();
        $sectionCountFirst = Section::count();

        // Second run
        $this->seed(DemoPersonaSeeder::class);
        $this->assertEquals($userCountFirst, User::count());
        $this->assertEquals($deptCountFirst, Department::count());
        $this->assertEquals($sectionCountFirst, Section::count());
    }

    public function test_all_demo_personas_can_authenticate(): void
    {
        $this->seed(DemoPersonaSeeder::class);

        $personas = [
            'admin@educore.kh' => 'admin@123',
            'university@educore.kh' => 'university@123',
            'department@educore.kh' => 'department@123',
            'lecturer@educore.kh' => 'lecturer@123',
            'student@educore.kh' => 'student@123',
        ];

        foreach ($personas as $email => $password) {
            $response = $this->post('/login', [
                'email' => $email,
                'password' => $password,
            ]);

            $response->assertSessionHasNoErrors();
            $this->assertAuthenticatedAs(User::where('email', $email)->first());

            // Logout for the next test iteration
            $this->post('/logout');
            $this->assertGuest();
        }
    }

    public function test_super_admin_sees_admin_dashboard(): void
    {
        $this->seed(DemoPersonaSeeder::class);
        $adminUser = User::where('email', 'admin@educore.kh')->firstOrFail();

        $this->actingAs($adminUser)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->has('stats')
            );
    }

    public function test_university_admin_sees_university_dashboard(): void
    {
        $this->seed(DemoPersonaSeeder::class);
        $uniUser = User::where('email', 'university@educore.kh')->firstOrFail();

        $this->actingAs($uniUser)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('UniversityAdmin/Dashboard')
                ->has('dashboard.overview')
                ->has('dashboard.waiting')
            );
    }

    public function test_department_admin_sees_populated_cse_department_dashboard(): void
    {
        $this->seed(DemoPersonaSeeder::class);
        $deptUser = User::where('email', 'department@educore.kh')->firstOrFail();

        $this->actingAs($deptUser)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('DepartmentAdmin/Dashboard')
                ->where('dashboard.department.code', 'CSE')
            );
    }

    public function test_lecturer_sees_populated_teaching_dashboard(): void
    {
        $this->seed(DemoPersonaSeeder::class);
        $lecturerUser = User::where('email', 'lecturer@educore.kh')->firstOrFail();

        $this->actingAs($lecturerUser)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Lecturer/Dashboard')
                ->has('dashboard.sections', 2)
            );
    }

    public function test_student_sees_populated_academic_dashboard(): void
    {
        $this->seed(DemoPersonaSeeder::class);
        $studentUser = User::where('email', 'student@educore.kh')->firstOrFail();

        $this->actingAs($studentUser)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Dashboard')
                ->where('dashboard.student.student_number', 'STU-2025-001')
            );
    }

    public function test_artisan_demo_seed_command_succeeds(): void
    {
        $this->artisan('demo:seed')
            ->expectsOutputToContain('Seeding realistic demo personas')
            ->expectsOutputToContain('Demo persona seeding complete')
            ->assertExitCode(0);
    }
}
