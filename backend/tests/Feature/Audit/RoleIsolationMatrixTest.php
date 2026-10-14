<?php

namespace Tests\Feature\Audit;

use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Internship;
use App\Models\InternshipCompany;
use App\Models\Lecturer;
use App\Models\Program;
use App\Models\Role as RoleModel;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\InvoiceService;
use App\Support\AcademicClock;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Role isolation matrix (`docs/audits/route-api-permission-matrix.md`).
 *
 * Two departments, each with a program, course, section, lecturer, student
 * and the records a semester produces. Every probe is sent twice by the same
 * actor: once with the ids of their own department (must be authorized — this
 * proves the URL and payload are right, so a 403 on the other side is not a
 * typo) and once with the other department's ids (must be refused with 403).
 * Function-level probes check what a role may never do, whatever the record.
 */
class RoleIsolationMatrixTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, array<string, int>> world => key => id */
    private array $world = [];

    private User $departmentAdmin;

    private User $universityAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake((string) config('academics.uploads_disk'));
        $this->seed([RoleSeeder::class, DocumentTypeSeeder::class]);

        $semester = Semester::factory()->forYear(AcademicYear::factory()->create())->open()->create();
        $company = InternshipCompany::query()->create(['name' => 'Smart Axiata', 'industry' => 'Telecom', 'is_active' => true]);

        $this->world['A'] = $this->buildWorld('A', $semester, $company);
        $this->world['B'] = $this->buildWorld('B', $semester, $company);

        $this->departmentAdmin = $this->userWithRole(Role::DepartmentAdmin, ['department_id' => $this->world['A']['department']]);
        $this->universityAdmin = $this->userWithRole(Role::UniversityAdmin);
    }

    public function test_department_admins_stay_inside_their_department(): void
    {
        $this->assertMatrix($this->departmentAdmin, [
            ['GET', '/api/students/{student}'],
            ['GET', '/api/students/{student}/grades'],
            ['GET', '/api/students/{student}/gpa'],
            ['GET', '/api/students/{student}/attendance'],
            ['GET', '/api/students/{student}/exams'],
            ['GET', '/api/students/{student}/enrollments'],
            ['GET', '/api/students/{student}/dashboard'],
            ['GET', '/api/timetable/student/{student}'],
            ['GET', '/api/enrollments/{enrollment}'],
            ['GET', '/api/sections/{section}'],
            ['GET', '/api/sections/{section}/grades'],
            ['GET', '/api/sections/{section}/attendance/summary'],
            ['GET', '/api/sections/{section}/attendance?date={today}'],
            ['GET', '/api/sections/{section}/assignments'],
            ['GET', '/api/sections/{section}/exams'],
            ['GET', '/api/sections/{section}/materials'],
            ['GET', '/api/sections/{section}/schedule'],
            ['GET', '/api/assignments/{assignment}'],
            ['GET', '/api/assignments/{assignment}/submissions'],
            ['GET', '/api/exams/{exam}'],
            ['GET', '/api/document-requests/{documentRequest}'],
            ['GET', '/api/internships/{internship}'],
            ['GET', '/api/lecturers/{lecturer}'],
            ['GET', '/api/lecturers/{lecturer}/dashboard'],
            ['GET', '/api/lecturers/{lecturer}/sections'],
            ['GET', '/api/timetable/lecturer/{lecturer}'],
            ['GET', '/api/courses/{course}'],
            ['GET', '/api/courses/{course}/grading-config'],
            ['GET', '/api/offerings/{offering}'],
            ['GET', '/api/programs/{program}'],
            ['GET', '/api/departments/{department}'],
            ['GET', '/api/departments/{department}/dashboard'],
            ['POST', '/api/internships/{internship}/review'],
            ['POST', '/api/document-requests/{documentRequest}/reject', ['rejection_reason' => 'Incomplete request.']],
        ]);
    }

    public function test_lecturers_reach_only_the_sections_they_teach(): void
    {
        $this->assertMatrix($this->user('A', 'lecturerUser'), [
            ['GET', '/api/lecturers/{lecturer}'],
            ['GET', '/api/lecturers/{lecturer}/dashboard'],
            ['GET', '/api/lecturers/{lecturer}/sections'],
            ['GET', '/api/timetable/lecturer/{lecturer}'],
            ['GET', '/api/sections/{section}/grades'],
            ['GET', '/api/sections/{section}/attendance/summary'],
            ['GET', '/api/sections/{section}/attendance?date={today}'],
            ['GET', '/api/sections/{section}/assignments'],
            ['GET', '/api/sections/{section}/exams'],
            ['GET', '/api/sections/{section}/materials'],
            ['GET', '/api/assignments/{assignment}'],
            ['GET', '/api/assignments/{assignment}/submissions'],
            ['GET', '/api/exams/{exam}'],
            ['POST', '/api/sections/{section}/attendance', fn (array $w) => ['session_date' => AcademicClock::today()->toDateString(), 'records' => [['enrollment_id' => $w['enrollment'], 'status' => 'present']]]],
            ['POST', '/api/sections/{section}/assignments', ['title' => 'Essay', 'max_score' => 20, 'due_at' => now()->addWeek()->toIso8601String(), 'assignment_type' => 'homework']],
            ['PUT', '/api/assignments/{assignment}', ['title' => 'Essay (revised)', 'max_score' => 20, 'due_at' => now()->addWeek()->toIso8601String(), 'assignment_type' => 'homework']],
            ['POST', '/api/sections/{section}/exams', ['exam_type' => 'quiz', 'title' => 'Quiz 1', 'weight' => 10, 'max_score' => 10]],
            ['POST', '/api/exams/{exam}/results', fn (array $w) => ['results' => [['enrollment_id' => $w['enrollment'], 'score' => 50]]]],
            ['POST', '/api/sections/{section}/materials', ['title' => 'Slides', 'kind' => CourseMaterial::KIND_LINK, 'url' => 'https://example.org/slides']],
            ['POST', '/api/submissions/{submission}/grade', ['score' => 15]],
            ['POST', '/api/sections/{section}/grades'],
        ]);

        // Files: authorization runs before the file lookup.
        $this->actingAs($this->user('A', 'lecturerUser'))->getJson("/api/submissions/{$this->world['B']['submission']}/file")->assertForbidden();
    }

    public function test_students_reach_only_their_own_records(): void
    {
        $this->assertMatrix($this->user('A', 'studentUser'), [
            ['GET', '/api/students/{student}'],
            ['GET', '/api/students/{student}/grades'],
            ['GET', '/api/students/{student}/gpa'],
            ['GET', '/api/students/{student}/attendance'],
            ['GET', '/api/students/{student}/exams'],
            ['GET', '/api/students/{student}/enrollments'],
            ['GET', '/api/students/{student}/dashboard'],
            ['GET', '/api/students/{student}/invoices'],
            ['GET', '/api/timetable/student/{student}'],
            ['GET', '/api/enrollments/{enrollment}'],
            ['GET', '/api/document-requests/{documentRequest}'],
            ['GET', '/api/internships/{internship}'],
            ['GET', '/api/invoices/{invoice}'],
            ['GET', '/api/invoices/{invoice}/download'],
            ['GET', '/api/assignments/{assignment}'],
            ['GET', '/api/exams/{exam}'],
            ['GET', '/api/sections/{section}/assignments'],
            ['GET', '/api/sections/{section}/exams'],
            ['GET', '/api/sections/{section}/materials'],
            ['POST', '/api/assignments/{assignment}/submissions', fn () => ['file' => UploadedFile::fake()->create('work.pdf', 20, 'application/pdf')], 'multipart'],
        ]);

        $student = $this->user('A', 'studentUser');
        $this->actingAs($student)->getJson("/api/submissions/{$this->world['B']['submission']}/file")->assertForbidden();
        $this->actingAs($student)->deleteJson("/api/enrollments/{$this->world['B']['enrollment']}")->assertForbidden();
    }

    public function test_no_role_reaches_actions_above_it(): void
    {
        $a = $this->world['A'];
        $refused = [
            'student' => [$this->user('A', 'studentUser'), [
                ['GET', '/api/students'], ['GET', '/api/audit-logs'], ['GET', '/api/invoices'],
                ['POST', "/api/sections/{$a['section']}/grades"],
                ['POST', "/api/sections/{$a['section']}/attendance", ['session_date' => AcademicClock::today()->toDateString(), 'records' => [['enrollment_id' => $a['enrollment'], 'status' => 'present']]]],
                ['POST', "/api/document-requests/{$a['documentRequest']}/approve"],
                ['POST', "/api/invoices/{$a['invoice']}/payments", ['amount' => 1, 'paid_on' => now()->toDateString(), 'method' => 'cash']],
                ['POST', "/api/internships/{$a['internship']}/approve"],
                ['GET', "/api/assignments/{$a['assignment']}/submissions"],
            ]],
            'lecturer' => [$this->user('A', 'lecturerUser'), [
                ['GET', '/api/students'], ['GET', '/api/users'], ['GET', '/api/invoices'], ['GET', '/api/audit-logs'],
                ['POST', "/api/sections/{$a['section']}/grades/approve"],
                ['GET', "/api/document-requests/{$a['documentRequest']}"],
                ['GET', "/api/students/{$a['student']}/grades"],
            ]],
            'department admin' => [$this->departmentAdmin, [
                ['GET', '/api/users'], ['GET', '/api/audit-logs'], ['GET', '/api/invoices'],
                ['GET', "/api/invoices/{$a['invoice']}"],
                ['POST', "/api/sections/{$a['section']}/grades/approve"],
                ['POST', "/api/document-requests/{$a['documentRequest']}/waive-fee"],
                ['POST', '/api/invoices', ['student_id' => $a['student'], 'title' => 'Fee', 'currency' => 'USD', 'issued_date' => now()->toDateString(), 'due_date' => now()->addMonth()->toDateString(), 'items' => [['description' => 'Fee', 'quantity' => 1, 'unit_price' => 5, 'fee_category' => 'other']]]],
            ]],
            'university admin' => [$this->universityAdmin, [
                ['GET', '/api/users'], ['GET', '/api/audit-logs'], ['GET', '/api/error-logs'],
                ['POST', "/api/sections/{$a['section']}/grades/reopen", ['reason' => 'Correction']],
            ]],
        ];

        $failures = [];
        foreach ($refused as $role => [$user, $probes]) {
            foreach ($probes as $probe) {
                $status = $this->send($user, $probe[0], $probe[1], $probe[2] ?? [])->status();
                if ($status !== 403) {
                    $failures[] = "{$role} {$probe[0]} {$probe[1]} → {$status} (expected 403)";
                }
            }
        }

        // Without a session or token nothing protected answers.
        $this->app['auth']->forgetGuards();
        foreach (['/api/students', "/api/students/{$a['student']}/grades", "/api/sections/{$a['section']}/grades", '/api/invoices'] as $uri) {
            $status = $this->getJson($uri)->status();
            if ($status !== 401) {
                $failures[] = "guest GET {$uri} → {$status} (expected 401)";
            }
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }

    // ------------------------------------------------------------------ helpers

    /**
     * @param  list<array{0: string, 1: string, 2?: array|callable, 3?: string}>  $probes
     */
    private function assertMatrix(User $actor, array $probes): void
    {
        $failures = [];

        foreach ($probes as $probe) {
            [$method, $template] = $probe;
            $payload = $probe[2] ?? [];
            $multipart = ($probe[3] ?? null) === 'multipart';

            foreach (['A' => 'own', 'B' => 'other'] as $side => $label) {
                $uri = $this->fill($template, $this->world[$side]);
                $body = is_callable($payload) ? $payload($this->world[$side]) : $payload;
                $status = $this->send($actor, $method, $uri, $body, $multipart)->status();

                $ok = $label === 'own'
                    ? ! in_array($status, [401, 403, 404, 405], true) && $status < 500
                    : $status === 403;

                if (! $ok) {
                    $failures[] = "{$label} {$method} {$uri} → {$status}".($label === 'own' ? ' (expected authorized)' : ' (expected 403)');
                }
            }
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }

    private function send(User $actor, string $method, string $uri, array $body = [], bool $multipart = false)
    {
        $this->actingAs($actor);

        return $multipart
            ? $this->call($method, $uri, $body, [], array_filter($body, fn ($v) => $v instanceof UploadedFile), ['HTTP_ACCEPT' => 'application/json'])
            : $this->json($method, $uri, $body);
    }

    private function fill(string $template, array $world): string
    {
        return preg_replace_callback('/\{(\w+)\}/', fn ($m) => $m[1] === 'today' ? AcademicClock::today()->toDateString() : (string) $world[$m[1]], $template);
    }

    private function user(string $world, string $key): User
    {
        return User::query()->findOrFail($this->world[$world][$key]);
    }

    private function userWithRole(Role $role, array $attributes = []): User
    {
        return User::factory()->create([...$attributes, 'role_id' => RoleModel::query()->where('slug', $role->value)->value('id')]);
    }

    /**
     * One department's records for a semester.
     *
     * @return array<string, int>
     */
    private function buildWorld(string $name, Semester $semester, InternshipCompany $company): array
    {
        $department = Department::factory()->create(['name' => "Department {$name}"]);
        $program = Program::factory()->create(['department_id' => $department->id]);
        $course = Course::factory()->create(['department_id' => $department->id]);
        $offering = CourseOffering::factory()->create(['course_id' => $course->id, 'semester_id' => $semester->id, 'status' => 'open']);
        $section = Section::factory()->create(['course_offering_id' => $offering->id, 'status' => 'open']);

        $lecturer = Lecturer::factory()->create(['department_id' => $department->id]);
        $section->lecturers()->attach($lecturer->id, ['role' => 'primary']);

        $student = Student::factory()->inProgram($program)->create();
        $enrollment = Enrollment::factory()->create(['student_id' => $student->id, 'section_id' => $section->id]);

        $assignment = Assignment::factory()->create(['section_id' => $section->id]);
        $submission = AssignmentSubmission::query()->create(['assignment_id' => $assignment->id, 'enrollment_id' => $enrollment->id, 'submitted_at' => now(), 'status' => 'submitted']);
        $exam = Exam::factory()->create(['section_id' => $section->id]);
        $material = CourseMaterial::query()->create(['section_id' => $section->id, 'title' => 'Syllabus', 'kind' => CourseMaterial::KIND_LINK, 'url' => 'https://example.org/syllabus', 'created_by' => $lecturer->user_id]);

        $documentRequest = DocumentRequest::query()->create(['student_id' => $student->id, 'document_type_id' => DocumentType::query()->value('id'), 'reason' => 'Scholarship', 'submitted_at' => now()]);
        $internship = Internship::query()->create(['student_id' => $student->id, 'company_id' => $company->id, 'position_title' => 'Intern', 'status' => Internship::STATUS_SUBMITTED, 'submitted_at' => now()]);
        $invoice = app(InvoiceService::class)->create([
            'student_id' => $student->id, 'title' => 'Tuition', 'currency' => 'USD',
            'issued_date' => now()->toDateString(), 'due_date' => now()->addMonth()->toDateString(), 'discount' => 0,
            'items' => [['description' => 'Tuition', 'quantity' => 1, 'unit_price' => 100, 'fee_category' => 'tuition']],
        ]);

        return [
            'department' => $department->id, 'program' => $program->id, 'course' => $course->id, 'offering' => $offering->id,
            'section' => $section->id, 'lecturer' => $lecturer->id, 'lecturerUser' => $lecturer->user_id,
            'student' => $student->id, 'studentUser' => $student->user_id, 'enrollment' => $enrollment->id,
            'assignment' => $assignment->id, 'submission' => $submission->id, 'exam' => $exam->id, 'material' => $material->id,
            'documentRequest' => $documentRequest->id, 'internship' => $internship->id, 'invoice' => $invoice->id,
        ];
    }
}
