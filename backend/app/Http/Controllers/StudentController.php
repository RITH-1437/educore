<?php

namespace App\Http\Controllers;

use App\Dto\People\StudentListFilters;
use App\Enums\Role as RoleSlug;
use App\Http\Requests\ChangeStudentProgramRequest;
use App\Http\Requests\ChangeStudentStatusRequest;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\FacultyResource;
use App\Http\Resources\StudentResource;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Student screens (module 9.2).
 *
 * Enrollments, grades, documents and invoices hang off students but belong to
 * their own modules; this controller manages the profile, the account link,
 * the status lifecycle and the program history.
 */
class StudentController extends Controller
{
    public function __construct(
        private readonly StudentService $students,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Student::class);

        $filters = StudentListFilters::fromInput($request->query());

        $students = $this->students->paginate($filters)
            ->withQueryString()
            ->appends($filters->toQueryString());

        return Inertia::render('Students/Index', [
            'students' => StudentResource::collection($students),
            ...$this->lookups(),
            // Student accounts created in Users management that have no
            // profile yet — offered as "link existing account" when creating.
            'unlinkedAccounts' => User::query()
                ->whereHas('role', fn ($query) => $query->where('slug', RoleSlug::Student->value))
                ->whereDoesntHave('student')
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email])
                ->values(),
            'filters' => [
                'search' => $filters->search,
                'faculty_id' => $filters->facultyId,
                'program_id' => $filters->programId,
                'status' => $filters->status,
            ],
        ]);
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $this->authorize('create', Student::class);

        $student = $this->students->create($request->validated());

        return redirect()
            ->route('students.edit', $student)
            ->with('success', 'Student created.');
    }

    public function edit(Student $student): Response
    {
        $this->authorize('update', $student);

        $student->load(['user', 'currentProgram.program.department.faculty:id,code,name', 'programHistory.program.department']);

        return Inertia::render('Students/Edit', [
            // `resolve()` so the page reads `props.student.student_number`, not `.data.…`.
            'student' => (new StudentResource($student))->resolve(),
            ...$this->lookups(),
        ]);
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $this->authorize('update', $student);

        $this->students->update($student, $request->validated());

        return back()->with('success', 'Student updated.');
    }

    public function changeStatus(ChangeStudentStatusRequest $request, Student $student): RedirectResponse
    {
        $this->authorize('changeStatus', $student);

        $data = $request->validated();
        $this->students->changeStatus($student, $data['status'], $data['effective_on'] ?? null, $data['notes'] ?? null);

        return back()->with('success', 'Student status changed to '.$data['status'].'.');
    }

    public function changeProgram(ChangeStudentProgramRequest $request, Student $student): RedirectResponse
    {
        $this->authorize('update', $student);

        $data = $request->validated();
        $this->students->changeProgram($student, (int) $data['program_id'], $data['effective_on'] ?? null, $data['notes'] ?? null);

        return back()->with('success', 'Program changed. The previous program was closed as transferred.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $this->authorize('delete', $student);

        $this->students->delete($student);

        return redirect()
            ->route('students.index')
            ->with('success', 'Student profile deleted. The account was kept but can no longer sign in.');
    }

    /**
     * @return array<string, mixed>
     */
    private function lookups(): array
    {
        return [
            'faculties' => FacultyResource::collection(
                Faculty::query()->where('is_active', true)->orderBy('name')->get()
            ),
            // Active programs with their faculty, for the faculty → program cascade.
            'programs' => Program::query()
                ->where('is_active', true)
                ->with('department:id,code,name,faculty_id')
                ->orderBy('code')
                ->get()
                ->map(fn (Program $program) => [
                    'id' => $program->id,
                    'code' => $program->code,
                    'name' => $program->name,
                    'faculty_id' => $program->department?->faculty_id,
                    'department' => $program->department?->code,
                ])
                ->values(),
            'statuses' => Student::STATUSES,
            'genders' => Student::GENDERS,
        ];
    }
}
