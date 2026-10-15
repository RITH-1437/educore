<?php

namespace Tests\Feature\Audit;

use App\Models\Assignment;
use App\Models\DocumentRequest;
use App\Models\Invoice;
use App\Models\Lecturer;
use App\Models\Room;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DemoPersonaSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PersonaNavigationAndSubmoduleAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-03-01 10:00:00');
        $this->seed(RoleSeeder::class);
        $this->seed(DemoPersonaSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ------------------------------------------------------------------------
    // PHASE 1: End-to-End Persona Navigation Verification
    // ------------------------------------------------------------------------

    public function test_super_admin_can_access_all_administrative_pages(): void
    {
        $user = User::where('email', 'admin@educore.kh')->firstOrFail();

        $routes = [
            '/admin/dashboard',
            '/users',
            '/universities',
            '/departments',
            '/programs',
            '/courses',
            '/offerings',
            '/academic-years',
            '/rooms',
            '/students',
            '/lecturers',
            '/enrollments',
            '/grades',
            '/document-types',
            '/documents',
            '/invoices',
            '/announcements',
            '/internships',
            '/analytics',
            '/audit-logs',
            '/error-logs',
            '/inbox',
            '/account/profile',
        ];

        foreach ($routes as $route) {
            $this->actingAs($user)
                ->get($route)
                ->assertOk();
        }
    }

    public function test_university_admin_can_access_authorized_institutional_pages(): void
    {
        $user = User::where('email', 'university@educore.kh')->firstOrFail();

        $allowedRoutes = [
            '/dashboard',
            '/universities',
            '/departments',
            '/programs',
            '/courses',
            '/offerings',
            '/academic-years',
            '/rooms',
            '/students',
            '/lecturers',
            '/enrollments',
            '/grades',
            '/document-types',
            '/documents',
            '/invoices',
            '/announcements',
            '/internships',
            '/analytics',
            '/inbox',
            '/account/profile',
        ];

        foreach ($allowedRoutes as $route) {
            $this->actingAs($user)
                ->get($route)
                ->assertOk();
        }

        // Must be forbidden from super-admin platform pages
        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($user)->get('/users')->assertForbidden();
        $this->actingAs($user)->get('/audit-logs')->assertForbidden();
        $this->actingAs($user)->get('/error-logs')->assertForbidden();
    }

    public function test_department_admin_can_access_departmental_pages(): void
    {
        $user = User::where('email', 'department@educore.kh')->firstOrFail();

        $allowedRoutes = [
            '/dashboard',
            '/universities',
            '/departments',
            '/programs',
            '/courses',
            '/offerings',
            '/rooms',
            '/students',
            '/lecturers',
            '/enrollments',
            '/grades',
            '/grading-scale',
            '/documents',
            '/announcements',
            '/internships',
            '/analytics',
            '/inbox',
            '/account/profile',
        ];

        foreach ($allowedRoutes as $route) {
            $this->actingAs($user)
                ->get($route)
                ->assertOk();
        }

        // Must be forbidden from platform management, university calendar & finance billing
        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($user)->get('/users')->assertForbidden();
        $this->actingAs($user)->get('/academic-years')->assertForbidden();
        $this->actingAs($user)->get('/invoices')->assertForbidden();
        $this->actingAs($user)->get('/document-types')->assertForbidden();
    }

    public function test_lecturer_can_access_teaching_pages(): void
    {
        $user = User::where('email', 'lecturer@educore.kh')->firstOrFail();
        $lecturer = Lecturer::where('user_id', $user->id)->firstOrFail();
        $section = $lecturer->sections()->firstOrFail();

        $allowedRoutes = [
            '/dashboard',
            '/attendance',
            "/attendance/sections/{$section->id}",
            "/coursework/sections/{$section->id}",
            "/exams/sections/{$section->id}",
            "/grades/sections/{$section->id}",
            '/timetable',
            '/announcements',
            '/inbox',
            '/account/profile',
        ];

        foreach ($allowedRoutes as $route) {
            $this->actingAs($user)
                ->get($route)
                ->assertOk();
        }

        // Must be forbidden from administrative & manager views
        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($user)->get('/users')->assertForbidden();
        $this->actingAs($user)->get('/invoices')->assertForbidden();
        $this->actingAs($user)->get('/students')->assertForbidden();
        $this->actingAs($user)->get('/analytics')->assertForbidden();
    }

    public function test_student_can_access_academic_portal_pages(): void
    {
        $user = User::where('email', 'student@educore.kh')->firstOrFail();
        $student = Student::where('user_id', $user->id)->firstOrFail();
        $invoice = Invoice::where('student_id', $student->id)->firstOrFail();

        $allowedRoutes = [
            '/dashboard',
            '/registration',
            '/timetable',
            '/my-attendance',
            '/my-assignments',
            '/my-materials',
            '/my-exams',
            '/my-grades',
            '/my-documents',
            '/my-invoices',
            "/invoices/{$invoice->id}",
            '/my-internships',
            '/announcements',
            '/inbox',
            '/account/profile',
        ];

        foreach ($allowedRoutes as $route) {
            $this->actingAs($user)
                ->get($route)
                ->assertOk();
        }

        // Must be strictly forbidden from staff & admin screens
        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($user)->get('/users')->assertForbidden();
        $this->actingAs($user)->get('/invoices')->assertForbidden();
        $this->actingAs($user)->get('/students')->assertForbidden();
        $this->actingAs($user)->get('/lecturers')->assertForbidden();
        $this->actingAs($user)->get('/departments')->assertForbidden();
        $this->actingAs($user)->get('/offerings')->assertForbidden();
    }

    // ------------------------------------------------------------------------
    // PHASE 2: Sub-module Operational Deep Dive
    // ------------------------------------------------------------------------

    public function test_timetable_detects_and_prevents_room_conflict(): void
    {
        $deptAdmin = User::where('email', 'department@educore.kh')->firstOrFail();
        $room = Room::where('code', 'A-101')->firstOrFail();
        $sections = Section::take(2)->get();
        $sec1 = $sections[0];
        $sec2 = $sections[1];

        // Schedule first section in Room A-101 on Monday 08:00 - 10:00
        $this->actingAs($deptAdmin)->post("/sections/{$sec1->id}/schedule", [
            'room_id' => $room->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '10:00',
        ])->assertRedirect();

        // Attempting to schedule second section overlapping in same room (09:00 - 11:00) must fail validation / conflict
        $response = $this->actingAs($deptAdmin)->post("/sections/{$sec2->id}/schedule", [
            'room_id' => $room->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_document_request_lifecycle_with_approval_and_fee_tracking(): void
    {
        $studentUser = User::where('email', 'student@educore.kh')->firstOrFail();
        $deptAdmin = User::where('email', 'department@educore.kh')->firstOrFail();

        // Find the seeded document request
        $docRequest = DocumentRequest::where('student_id', $studentUser->student->id)->firstOrFail();
        $this->assertEquals(DocumentRequest::STATUS_PENDING, $docRequest->status);

        // Department Admin approves the request
        $this->actingAs($deptAdmin)
            ->post("/document-requests/{$docRequest->id}/approve")
            ->assertRedirect();

        $docRequest->refresh();
        $this->assertEquals(DocumentRequest::STATUS_APPROVED, $docRequest->status);

        // Department Admin generates the document (MinIO PDF generation)
        $this->actingAs($deptAdmin)
            ->post("/document-requests/{$docRequest->id}/generate")
            ->assertRedirect();

        $docRequest->refresh();
        $this->assertEquals(DocumentRequest::STATUS_GENERATED, $docRequest->status);
        $this->assertNotNull($docRequest->document);
        $this->assertNotNull($docRequest->document->verification_token);

        // Phase 3: Guest / Public digital document verification
        $token = $docRequest->document->verification_token;
        $this->get("/verify/{$token}")
            ->assertOk();
    }

    public function test_invoice_payment_workflow_updates_invoice_status_to_paid(): void
    {
        $uniAdmin = User::where('email', 'university@educore.kh')->firstOrFail();
        $studentUser = User::where('email', 'student@educore.kh')->firstOrFail();

        // Seeded invoice has $300 total, $150 paid (partial)
        $invoice = Invoice::where('student_id', $studentUser->student->id)->firstOrFail();
        $this->assertEquals(Invoice::STATUS_PARTIAL, $invoice->status);

        // Record remaining $150 payment
        $this->actingAs($uniAdmin)->post("/invoices/{$invoice->id}/payments", [
            'amount' => 150.00,
            'paid_on' => now()->toDateString(),
            'method' => 'bank_transfer',
            'reference' => 'PAY-DEMO-002',
            'notes' => 'Settlement of remaining balance.',
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertEquals(Invoice::STATUS_PAID, $invoice->status);
    }

    public function test_lecturer_records_attendance_and_student_views_it(): void
    {
        $lecturerUser = User::where('email', 'lecturer@educore.kh')->firstOrFail();
        $studentUser = User::where('email', 'student@educore.kh')->firstOrFail();
        $lecturer = Lecturer::where('user_id', $lecturerUser->id)->firstOrFail();
        $section = $lecturer->sections()->firstOrFail();
        $enrollment = $section->enrollments()->where('student_id', $studentUser->student->id)->firstOrFail();

        $this->actingAs($lecturerUser)->post("/attendance/sections/{$section->id}", [
            'session_date' => '2026-03-02',
            'records' => [
                ['enrollment_id' => $enrollment->id, 'status' => 'present'],
            ],
            'topic' => 'Data Structures Fundamentals',
        ])->assertRedirect();

        $this->assertDatabaseHas('attendance_sessions', [
            'section_id' => $section->id,
            'session_date' => '2026-03-02',
        ]);

        $this->flushSession();
        $this->actingAs($studentUser)
            ->get('/my-attendance')
            ->assertOk();
    }

    public function test_assignment_lifecycle_and_grading(): void
    {
        $lecturerUser = User::where('email', 'lecturer@educore.kh')->firstOrFail();
        $studentUser = User::where('email', 'student@educore.kh')->firstOrFail();
        $lecturer = Lecturer::where('user_id', $lecturerUser->id)->firstOrFail();
        $section = $lecturer->sections()
            ->whereHas('offering.semester', fn ($q) => $q->where('status', 'open'))
            ->firstOrFail();

        // Lecturer creates coursework assignment
        $response = $this->actingAs($lecturerUser)->post("/coursework/sections/{$section->id}", [
            'title' => 'Lab Assignment 1: Recursion',
            'description' => 'Implement recursive algorithms in Python/Java.',
            'max_score' => 100,
            'due_at' => '2026-04-15 23:59:00',
            'assignment_type' => 'homework',
        ]);
        $response->assertSessionHas('success');
        $response->assertRedirect();

        $assignment = Assignment::where('section_id', $section->id)
            ->where('title', 'Lab Assignment 1: Recursion')
            ->firstOrFail();

        // Lecturer publishes assignment
        $this->actingAs($lecturerUser)->post("/assignments/{$assignment->id}/publish")->assertRedirect();
        $assignment->refresh();
        $this->assertTrue((bool) $assignment->is_published);

        // Student views assignments
        $this->flushSession();
        $this->actingAs($studentUser)->get('/my-assignments')->assertOk();
    }

    public function test_grades_compute_submit_and_approve_lifecycle(): void
    {
        $lecturerUser = User::where('email', 'lecturer@educore.kh')->firstOrFail();
        $uniAdmin = User::where('email', 'university@educore.kh')->firstOrFail();
        $lecturer = Lecturer::where('user_id', $lecturerUser->id)->firstOrFail();
        $section = $lecturer->sections()
            ->whereHas('offering.semester', fn ($q) => $q->where('status', 'open'))
            ->firstOrFail();

        // Lecturer computes grades
        $this->actingAs($lecturerUser)->post("/grades/sections/{$section->id}")->assertRedirect();

        // Lecturer submits grades for approval
        $this->actingAs($lecturerUser)->post("/grades/sections/{$section->id}/submit")->assertRedirect();

        // University Admin approves grades
        $this->flushSession();
        $this->actingAs($uniAdmin)->post("/grades/sections/{$section->id}/approve")->assertRedirect();
    }
}
