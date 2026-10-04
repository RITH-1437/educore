<?php

namespace Tests\Feature\UniversityAdmin;

use App\Models\Department;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Internship;
use App\Models\InternshipCompany;
use App\Models\Invoice;
use App\Models\Lecturer;
use App\Models\Role as RoleModel;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * University Admin dashboard (`docs/36_University-Admin-Dashboard-Report.md`):
 * a University Admin's /dashboard shows institution-wide waiting requests,
 * internships, overdue invoices, and academic numbers; the API serves
 * Super Admin and University Admin.
 */
class UniversityAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $universityAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->universityAdmin = $this->userWithRole('university-admin');
    }

    public function test_dashboard_counts_institution_wide_waiting_items_and_academic_overview(): void
    {
        $this->seed(DocumentTypeSeeder::class);
        $type = DocumentType::query()->first();
        $semester = Semester::factory()->create(['status' => 'open', 'start_date' => '2026-02-01', 'end_date' => '2026-06-30']);

        $student1 = Student::factory()->create(['status' => 'active']);
        $student2 = Student::factory()->create(['status' => 'active']);
        Lecturer::factory()->count(3)->create(['is_active' => true]);

        // Document requests across students
        DocumentRequest::query()->create(['student_id' => $student1->id, 'document_type_id' => $type->id, 'semester_id' => $semester->id, 'status' => 'pending']);
        DocumentRequest::query()->create(['student_id' => $student2->id, 'document_type_id' => $type->id, 'semester_id' => $semester->id, 'status' => 'approved']);

        // Internships
        $company = InternshipCompany::query()->create(['name' => 'Smart Axiata', 'industry' => 'Telecom', 'is_active' => true]);
        Internship::query()->create(['student_id' => $student1->id, 'company_id' => $company->id, 'position_title' => 'Software Intern', 'status' => 'submitted']);
        Internship::query()->create(['student_id' => $student2->id, 'company_id' => $company->id, 'position_title' => 'Software Intern', 'status' => 'under_review']);

        // Invoices
        Invoice::query()->create([
            'student_id' => $student1->id,
            'invoice_number' => 'INV-2026-00001',
            'title' => 'Tuition',
            'currency' => 'USD',
            'subtotal' => 500,
            'discount' => 0,
            'total' => 500,
            'amount_paid' => 0,
            'status' => 'overdue',
            'issued_date' => '2026-01-01',
            'due_date' => '2026-01-15',
        ]);
        Invoice::query()->create([
            'student_id' => $student2->id,
            'invoice_number' => 'INV-2026-00002',
            'title' => 'Tuition',
            'currency' => 'USD',
            'subtotal' => 500,
            'discount' => 0,
            'total' => 500,
            'amount_paid' => 500,
            'status' => 'paid',
            'issued_date' => '2026-01-01',
            'due_date' => '2026-01-15',
        ]);

        $waitingCounts = [
            'document_requests_pending' => 1,
            'document_requests_approved' => 1,
            'internships_submitted' => 1,
            'internships_under_review' => 1,
            'invoices_overdue' => 1,
        ];

        $this->actingAs($this->universityAdmin)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('UniversityAdmin/Dashboard')
                ->where('dashboard.semester.id', $semester->id)
                ->where('dashboard.waiting', $waitingCounts)
                ->where('dashboard.overview.students_active', 2)
                ->where('dashboard.overview.lecturers_active', 3));

        // The API answers with the same data for University Admin and Super Admin
        $this->actingAs($this->universityAdmin)
            ->getJson('/api/university/dashboard')
            ->assertOk()
            ->assertJsonPath('data.waiting', $waitingCounts)
            ->assertJsonPath('data.overview.students_active', 2);

        $superAdmin = $this->userWithRole('super-admin');
        $this->actingAs($superAdmin)
            ->getJson('/api/university/dashboard')
            ->assertOk()
            ->assertJsonPath('data.waiting', $waitingCounts);
    }

    public function test_access_and_empty_states(): void
    {
        $this->getJson('/api/university/dashboard')->assertUnauthorized();

        // Students, lecturers, and department admins cannot access the endpoint
        foreach (['student', 'lecturer'] as $role) {
            $this->actingAs($this->userWithRole($role))->getJson('/api/university/dashboard')->assertForbidden();
        }
        $departmentAdmin = $this->departmentAdminFor(Department::factory()->create());
        $this->actingAs($departmentAdmin)->getJson('/api/university/dashboard')->assertForbidden();

        // Without any semester, overview figures are safe nulls
        $this->actingAs($this->universityAdmin)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('UniversityAdmin/Dashboard')
                ->where('dashboard.semester', null)
                ->where('dashboard.overview.sections', null)
                ->where('dashboard.overview.students_enrolled', null));
    }

    private function userWithRole(string $slug): User
    {
        $role = RoleModel::query()->firstWhere('slug', $slug) ?? RoleModel::factory()->withSlug($slug)->create();

        return User::factory()->create(['role_id' => $role->id]);
    }
}
