<?php

namespace Database\Seeders;

use App\Enums\AcademicYearStatus;
use App\Enums\Role;
use App\Enums\SemesterStatus;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Grade;
use App\Models\Internship;
use App\Models\InternshipCompany;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Lecturer;
use App\Models\Payment;
use App\Models\Program;
use App\Models\Role as RoleModel;
use App\Models\Room;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentProgram;
use App\Models\University;
use App\Models\User;
use App\Services\GpaService;
use App\Services\UniversityService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds a rich, interconnected academic environment for demonstration and local manual testing.
 *
 * Ensures all 5 primary roles (Super Admin, University Admin, Department Admin, Lecturer, Student)
 * have populated, functional dashboard views, with realistic courses, sections, enrollments,
 * attendance, grades, invoices, and document requests.
 *
 * Fully idempotent: safe to run multiple times without duplicating rows.
 */
class DemoPersonaSeeder extends Seeder
{
    public function run(): void
    {
        $roles = RoleModel::query()->pluck('id', 'slug');

        // 1. Institution
        $university = University::query()->updateOrCreate(
            ['code' => 'ITC'],
            [
                'name' => 'Institute of Technology of Cambodia',
                'short_name' => 'ITC',
                'address' => 'Russian Federation Blvd (110), Phnom Penh, Cambodia',
                'phone' => '+855 23 880 370',
                'email' => 'info@itc.edu.kh',
                'website' => 'https://itc.edu.kh',
            ]
        );
        app(UniversityService::class)->makeCurrent($university);

        // 2. Departments
        $cse = Department::query()->updateOrCreate(
            ['code' => 'CSE'],
            [
                'university_id' => $university->id,
                'name' => 'Department of Computer Science and Engineering',
                'head_name' => 'Dr. Dara Lim',
                'description' => 'Software engineering, algorithms, artificial intelligence and data science.',
                'is_active' => true,
            ]
        );

        $eee = Department::query()->updateOrCreate(
            ['code' => 'EEE'],
            [
                'university_id' => $university->id,
                'name' => 'Department of Electrical and Electronics Engineering',
                'head_name' => 'Dr. Bopha Nou',
                'description' => 'Telecommunications, power systems, robotics and electronics.',
                'is_active' => true,
            ]
        );

        // 3. Degree Programs
        $bscs = Program::query()->updateOrCreate(
            ['code' => 'BSCS'],
            [
                'department_id' => $cse->id,
                'name' => 'Bachelor of Science in Computer Science',
                'degree_level' => 'bachelor',
                'duration_years' => 4,
                'credits_required' => 140.0,
                'tuition_per_credit' => 50.00,
                'is_active' => true,
            ]
        );

        // 4. Academic Calendar (Current Year & Open Semester)
        $year = AcademicYear::query()->updateOrCreate(
            ['code' => '2025-2026'],
            [
                'name' => 'Academic Year 2025-2026',
                'start_date' => '2025-09-01',
                'end_date' => '2026-08-31',
                'status' => AcademicYearStatus::Active,
                'is_current' => true,
            ]
        );

        // S1 (Past / Completed for grade history)
        $sem1 = Semester::query()->updateOrCreate(
            ['academic_year_id' => $year->id, 'sequence' => 1],
            [
                'name' => 'Semester 1',
                'code' => 'S1',
                'start_date' => '2025-09-01',
                'end_date' => '2026-01-31',
                'enrollment_start' => '2025-08-15',
                'enrollment_end' => '2025-09-15',
                'withdrawal_deadline' => '2025-11-30',
                'exam_start' => '2026-01-15',
                'exam_end' => '2026-01-30',
                'status' => SemesterStatus::Completed,
            ]
        );

        // S2 (Active / Open current semester)
        $sem2 = Semester::query()->updateOrCreate(
            ['academic_year_id' => $year->id, 'sequence' => 2],
            [
                'name' => 'Semester 2',
                'code' => 'S2',
                'start_date' => '2026-02-01',
                'end_date' => '2026-08-31',
                'enrollment_start' => '2026-01-15',
                'enrollment_end' => '2026-03-31',
                'withdrawal_deadline' => '2026-06-30',
                'exam_start' => '2026-07-01',
                'exam_end' => '2026-07-20',
                'status' => SemesterStatus::Open,
            ]
        );

        // 5. Rooms
        $room101 = Room::query()->updateOrCreate(
            ['code' => 'A-101'],
            ['name' => 'Lecture Hall A101', 'building' => 'Building A', 'floor' => '1', 'capacity' => 60, 'room_type' => 'lecture', 'is_active' => true]
        );
        $lab1 = Room::query()->updateOrCreate(
            ['code' => 'C-LAB1'],
            ['name' => 'Computer Lab 1', 'building' => 'Building C', 'floor' => 'G', 'capacity' => 40, 'room_type' => 'lab', 'is_active' => true]
        );

        // 6. Courses
        $cs101 = Course::query()->updateOrCreate(
            ['code' => 'CS101'],
            [
                'department_id' => $cse->id,
                'name' => 'Introduction to Programming',
                'credits' => 3.0,
                'course_level' => 'introductory',
                'lecture_hours' => 30,
                'lab_hours' => 30,
                'status' => Course::STATUS_ACTIVE,
            ]
        );

        $cs201 = Course::query()->updateOrCreate(
            ['code' => 'CS201'],
            [
                'department_id' => $cse->id,
                'name' => 'Data Structures & Algorithms',
                'credits' => 3.0,
                'course_level' => 'intermediate',
                'lecture_hours' => 30,
                'lab_hours' => 30,
                'status' => Course::STATUS_ACTIVE,
            ]
        );

        $cs202 = Course::query()->updateOrCreate(
            ['code' => 'CS202'],
            [
                'department_id' => $cse->id,
                'name' => 'Database Systems',
                'credits' => 3.0,
                'course_level' => 'intermediate',
                'lecture_hours' => 30,
                'lab_hours' => 15,
                'status' => Course::STATUS_ACTIVE,
            ]
        );

        $bscs->courses()->syncWithoutDetaching([
            $cs101->id => ['is_required' => true, 'suggested_semester' => 1],
            $cs201->id => ['is_required' => true, 'suggested_semester' => 2],
            $cs202->id => ['is_required' => true, 'suggested_semester' => 2],
        ]);

        // 7. Seeded Role Accounts & Profiles
        // Super Admin
        User::query()->updateOrCreate(
            ['email' => 'admin@educore.kh'],
            [
                'name' => 'System Super Admin',
                'password' => 'admin@123',
                'role_id' => $roles[Role::SuperAdmin->value],
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // University Admin
        $uniAdminUser = User::query()->updateOrCreate(
            ['email' => 'university@educore.kh'],
            [
                'name' => 'Academic Affairs Registrar',
                'password' => 'university@123',
                'role_id' => $roles[Role::UniversityAdmin->value],
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // Department Admin (assigned to Computer Science department!)
        User::query()->updateOrCreate(
            ['email' => 'department@educore.kh'],
            [
                'name' => 'Dr. Dara Lim (CSE Head)',
                'password' => 'department@123',
                'role_id' => $roles[Role::DepartmentAdmin->value],
                'department_id' => $cse->id,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // Lecturer User & Profile
        $lecturerUser = User::query()->updateOrCreate(
            ['email' => 'lecturer@educore.kh'],
            [
                'name' => 'Dr. Sophea Chan',
                'password' => 'lecturer@123',
                'role_id' => $roles[Role::Lecturer->value],
                'department_id' => $cse->id,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $lecturerProfile = Lecturer::query()->updateOrCreate(
            ['user_id' => $lecturerUser->id],
            [
                'staff_number' => 'LEC-CSE-001',
                'first_name' => 'Sophea',
                'last_name' => 'Chan',
                'title' => 'Dr.',
                'department_id' => $cse->id,
                'position' => 'Senior Lecturer',
                'specialization' => 'Database Systems & Cloud Computing',
                'employment_type' => 'full_time',
                'is_active' => true,
            ]
        );

        // Student User & Profile
        $studentUser = User::query()->updateOrCreate(
            ['email' => 'student@educore.kh'],
            [
                'name' => 'Sokha Meng',
                'password' => 'student@123',
                'role_id' => $roles[Role::Student->value],
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $studentProfile = Student::query()->updateOrCreate(
            ['user_id' => $studentUser->id],
            [
                'student_number' => 'STU-2025-001',
                'first_name' => 'Sokha',
                'last_name' => 'Meng',
                'gender' => 'male',
                'date_of_birth' => '2004-05-12',
                'address' => 'Toul Kork, Phnom Penh',
                'national_id' => '1029384756',
                'enrollment_date' => '2025-09-01',
                'status' => Student::STATUS_ACTIVE,
            ]
        );

        // Link student to BSCS program
        if (! $studentProfile->currentProgram()->exists()) {
            $studentProfile->programHistory()->create([
                'program_id' => $bscs->id,
                'started_on' => '2025-09-01',
                'status' => StudentProgram::STATUS_ACTIVE,
            ]);
        }

        // Secondary student peer (for section peer comparisons)
        $peerUser = User::query()->updateOrCreate(
            ['email' => 'peer.student@educore.kh'],
            [
                'name' => 'Bopha Vann',
                'password' => 'student@123',
                'role_id' => $roles[Role::Student->value],
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $peerProfile = Student::query()->updateOrCreate(
            ['user_id' => $peerUser->id],
            [
                'student_number' => 'STU-2025-002',
                'first_name' => 'Bopha',
                'last_name' => 'Vann',
                'gender' => 'female',
                'date_of_birth' => '2004-08-20',
                'address' => 'Chamkarmon, Phnom Penh',
                'enrollment_date' => '2025-09-01',
                'status' => Student::STATUS_ACTIVE,
            ]
        );
        if (! $peerProfile->currentProgram()->exists()) {
            $peerProfile->programHistory()->create([
                'program_id' => $bscs->id,
                'started_on' => '2025-09-01',
                'status' => StudentProgram::STATUS_ACTIVE,
            ]);
        }

        // 8. Offerings & Sections in S2 (Current Semester)
        $offering201 = CourseOffering::query()->updateOrCreate(
            ['course_id' => $cs201->id, 'semester_id' => $sem2->id],
            ['status' => 'open']
        );
        $section201A = Section::query()->updateOrCreate(
            ['course_offering_id' => $offering201->id, 'code' => 'A'],
            ['capacity' => 35, 'status' => 'open']
        );
        $section201A->lecturers()->syncWithoutDetaching([$lecturerProfile->id => ['role' => 'primary']]);

        $offering202 = CourseOffering::query()->updateOrCreate(
            ['course_id' => $cs202->id, 'semester_id' => $sem2->id],
            ['status' => 'open']
        );
        $section202A = Section::query()->updateOrCreate(
            ['course_offering_id' => $offering202->id, 'code' => 'A'],
            ['capacity' => 35, 'status' => 'open']
        );
        $section202A->lecturers()->syncWithoutDetaching([$lecturerProfile->id => ['role' => 'primary']]);

        // 9. Timetable Schedule for Section 201A & 202A
        if ($section201A->scheduleEntries()->doesntExist()) {
            $section201A->scheduleEntries()->create([
                'room_id' => $room101->id,
                'day_of_week' => 1, // Monday
                'start_time' => '08:00',
                'end_time' => '09:30',
            ]);
            $section201A->scheduleEntries()->create([
                'room_id' => $room101->id,
                'day_of_week' => 3, // Wednesday
                'start_time' => '08:00',
                'end_time' => '09:30',
            ]);
        }

        if ($section202A->scheduleEntries()->doesntExist()) {
            $section202A->scheduleEntries()->create([
                'room_id' => $lab1->id,
                'day_of_week' => 2, // Tuesday
                'start_time' => '09:45',
                'end_time' => '11:15',
            ]);
            $section202A->scheduleEntries()->create([
                'room_id' => $lab1->id,
                'day_of_week' => 4, // Thursday
                'start_time' => '09:45',
                'end_time' => '11:15',
            ]);
        }

        // 10. Completed Historical Record in S1 (CS101) with Passed Grade & GPA
        $offering101 = CourseOffering::query()->updateOrCreate(
            ['course_id' => $cs101->id, 'semester_id' => $sem1->id],
            ['status' => 'closed']
        );
        $section101A = Section::query()->updateOrCreate(
            ['course_offering_id' => $offering101->id, 'code' => 'A'],
            ['capacity' => 40, 'status' => 'closed']
        );
        $section101A->lecturers()->syncWithoutDetaching([$lecturerProfile->id => ['role' => 'primary']]);

        $pastEnrollment = Enrollment::query()->updateOrCreate(
            ['student_id' => $studentProfile->id, 'section_id' => $section101A->id],
            [
                'academic_year_id' => $year->id,
                'semester_id' => $sem1->id,
                'status' => Enrollment::STATUS_COMPLETED,
                'enrolled_at' => Carbon::parse('2025-09-02'),
            ]
        );

        Grade::query()->updateOrCreate(
            ['enrollment_id' => $pastEnrollment->id],
            [
                'total_score' => 88.5,
                'letter_grade' => 'A',
                'grade_point' => 4.0,
                'status' => Grade::STATUS_APPROVED,
                'graded_by' => $lecturerUser->id,
                'approved_at' => Carbon::parse('2026-01-28'),
            ]
        );

        // Recalculate student GPA
        app(GpaService::class)->recalculate($studentProfile);

        // 11. Current Active Enrollments in S2 for Student and Peer
        $currentEnrollment1 = Enrollment::query()->updateOrCreate(
            ['student_id' => $studentProfile->id, 'section_id' => $section201A->id],
            [
                'academic_year_id' => $year->id,
                'semester_id' => $sem2->id,
                'status' => Enrollment::STATUS_CONFIRMED,
                'enrolled_at' => Carbon::parse('2026-02-02'),
            ]
        );

        $currentEnrollment2 = Enrollment::query()->updateOrCreate(
            ['student_id' => $studentProfile->id, 'section_id' => $section202A->id],
            [
                'academic_year_id' => $year->id,
                'semester_id' => $sem2->id,
                'status' => Enrollment::STATUS_CONFIRMED,
                'enrolled_at' => Carbon::parse('2026-02-02'),
            ]
        );

        Enrollment::query()->updateOrCreate(
            ['student_id' => $peerProfile->id, 'section_id' => $section201A->id],
            [
                'academic_year_id' => $year->id,
                'semester_id' => $sem2->id,
                'status' => Enrollment::STATUS_CONFIRMED,
                'enrolled_at' => Carbon::parse('2026-02-02'),
            ]
        );

        // 12. Attendance Sessions & Records
        $session = AttendanceSession::query()->updateOrCreate(
            ['section_id' => $section201A->id, 'session_date' => Carbon::parse('2026-03-02')],
            [
                'start_time' => '08:00',
                'end_time' => '09:30',
                'topic' => 'Binary Search Trees & Balancing',
                'status' => 'held',
                'recorded_by' => $lecturerUser->id,
            ]
        );

        AttendanceRecord::query()->updateOrCreate(
            ['attendance_session_id' => $session->id, 'enrollment_id' => $currentEnrollment1->id],
            ['status' => 'present', 'marked_by' => $lecturerUser->id]
        );

        // 13. Coursework Assignments & Submissions
        $assignment = Assignment::query()->updateOrCreate(
            ['section_id' => $section201A->id, 'title' => 'Programming Assignment 1: AVL Trees'],
            [
                'description' => 'Implement self-balancing AVL trees in C++ or Java.',
                'due_at' => Carbon::parse('2026-04-15 23:59:00'),
                'max_score' => 100,
                'is_published' => true,
                'published_at' => Carbon::parse('2026-03-01 08:00:00'),
            ]
        );

        AssignmentSubmission::query()->updateOrCreate(
            ['assignment_id' => $assignment->id, 'enrollment_id' => $currentEnrollment1->id],
            [
                'status' => 'submitted',
                'submitted_at' => Carbon::parse('2026-04-10 14:30:00'),
            ]
        );

        // 14. Midterm Examination & Results
        $exam = Exam::query()->updateOrCreate(
            ['section_id' => $section201A->id, 'title' => 'Midterm Exam'],
            [
                'exam_type' => 'midterm',
                'scheduled_date' => Carbon::parse('2026-04-05'),
                'start_time' => '08:00',
                'end_time' => '10:00',
                'max_score' => 100,
                'weight' => 30,
                'is_published' => true,
            ]
        );

        ExamResult::query()->updateOrCreate(
            ['exam_id' => $exam->id, 'enrollment_id' => $currentEnrollment1->id],
            ['score' => 85.0]
        );

        // 15. Draft Course Grades for Lecturer
        Grade::query()->updateOrCreate(
            ['enrollment_id' => $currentEnrollment1->id],
            [
                'total_score' => 87.5,
                'letter_grade' => 'A',
                'grade_point' => 4.0,
                'status' => Grade::STATUS_DRAFT,
                'graded_by' => $lecturerUser->id,
                'remarks' => 'Consistent effort on algorithms and coursework.',
            ]
        );

        // 16. Tuition Invoice for S2
        $invoice = Invoice::query()->updateOrCreate(
            ['student_id' => $studentProfile->id, 'semester_id' => $sem2->id],
            [
                'invoice_number' => 'INV-2026-DEMO-001',
                'title' => "Tuition - {$sem2->name}",
                'description' => 'Tuition billing for Semester 2 (6 credits: CS201, CS202)',
                'currency' => 'USD',
                'subtotal' => 300.00,
                'discount' => 0.00,
                'total' => 300.00,
                'amount_paid' => 150.00,
                'status' => Invoice::STATUS_PARTIAL,
                'issued_date' => Carbon::parse('2026-02-05'),
                'due_date' => Carbon::parse('2026-04-30'),
                'notes' => 'Generated tuition invoice with partial initial payment.',
            ]
        );

        InvoiceItem::query()->firstOrCreate(
            ['invoice_id' => $invoice->id, 'description' => 'Tuition: CS201 - Data Structures (3 credits)'],
            ['quantity' => 3.0, 'unit_price' => 50.00, 'amount' => 150.00, 'fee_category' => 'tuition']
        );
        InvoiceItem::query()->firstOrCreate(
            ['invoice_id' => $invoice->id, 'description' => 'Tuition: CS202 - Database Systems (3 credits)'],
            ['quantity' => 3.0, 'unit_price' => 50.00, 'amount' => 150.00, 'fee_category' => 'tuition']
        );

        Payment::query()->firstOrCreate(
            ['invoice_id' => $invoice->id, 'reference' => 'PAY-DEMO-001'],
            [
                'amount' => 150.00,
                'paid_on' => Carbon::parse('2026-02-10'),
                'method' => 'bank_transfer',
                'received_by' => $uniAdminUser->id,
                'notes' => 'Half tuition installment paid via Wing/ABA.',
            ]
        );

        // 17. Document Request
        $certType = DocumentType::query()->where('code', DocumentType::STUDENT_CERTIFICATE)->first();
        if ($certType) {
            DocumentRequest::query()->updateOrCreate(
                ['student_id' => $studentProfile->id, 'document_type_id' => $certType->id],
                [
                    'academic_year_id' => $year->id,
                    'semester_id' => $sem2->id,
                    'reason' => 'Official student status verification for scholarship sponsor.',
                    'status' => DocumentRequest::STATUS_PENDING,
                    'submitted_at' => Carbon::parse('2026-03-01 10:00:00'),
                ]
            );
        }

        // 18. Internship Host Company & Application
        $company = InternshipCompany::query()->updateOrCreate(
            ['name' => 'Smart Axiata Co., Ltd.'],
            [
                'industry' => 'Telecommunications & IT',
                'contact_name' => 'Kosal Rath',
                'contact_email' => 'internships@smart.com.kh',
                'contact_phone' => '+855 10 200 300',
                'address' => 'Monivong Blvd, Phnom Penh',
                'is_active' => true,
            ]
        );

        Internship::query()->updateOrCreate(
            ['student_id' => $studentProfile->id, 'company_id' => $company->id],
            [
                'position_title' => 'Junior Cloud & Backend Intern',
                'description' => 'Assist infrastructure engineering team with API services and Docker deployments.',
                'start_date' => Carbon::parse('2026-07-01'),
                'end_date' => Carbon::parse('2026-09-30'),
                'supervisor_name' => 'Kosal Rath',
                'supervisor_email' => 'kosal.rath@smart.com.kh',
                'status' => Internship::STATUS_UNDER_REVIEW,
                'submitted_at' => Carbon::parse('2026-03-05 09:15:00'),
            ]
        );

        // 19. Departmental Announcement
        Announcement::query()->updateOrCreate(
            ['title' => 'Welcome to Semester 2 (2025-2026) — Department of CSE'],
            [
                'author_id' => User::query()->where('email', 'department@educore.kh')->value('id'),
                'body' => 'Welcome all computer science students to Semester 2! Please review your course timetables on the student portal and check lab room assignments.',
                'announcement_type' => 'academic',
                'audience_type' => 'department',
                'audience_id' => $cse->id,
                'publish_state' => Announcement::STATE_PUBLISHED,
                'published_at' => Carbon::parse('2026-02-01 08:00:00'),
            ]
        );
    }
}
