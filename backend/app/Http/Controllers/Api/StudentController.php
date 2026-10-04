<?php

namespace App\Http\Controllers\Api;

use App\Dto\People\StudentListFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeStudentProgramRequest;
use App\Http\Requests\ChangeStudentStatusRequest;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Student endpoints (module 9.2).
 *
 * List: Super Admin, University Admin, Department Admin. Show: the same, plus a
 * student reading their own profile. Writes: Super Admin and University Admin.
 */
class StudentController extends Controller
{
    private const DETAIL = ['user', 'currentProgram.program.department:id,code,name', 'programHistory.program.department'];

    public function __construct(
        private readonly StudentService $students,
    ) {}

    #[OA\Get(
        path: '/students',
        summary: 'List students',
        description: 'Paginated student profiles with account and current program. Search covers student ID, names, national ID and email; department/program filters use the current program.',
        operationId: 'listStudents',
        tags: ['People'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'search', description: 'Partial match on student ID, first/last name, national ID or email.', schema: new OA\Schema(type: 'string'), example: 'Sok'),
            new OA\QueryParameter(name: 'filters[department_id]', description: 'Current program belongs to this department.', schema: new OA\Schema(type: 'integer', format: 'int64')),
            new OA\QueryParameter(name: 'filters[program_id]', description: 'Currently in this program.', schema: new OA\Schema(type: 'integer', format: 'int64')),
            new OA\QueryParameter(name: 'filters[status]', description: 'Student status.', schema: new OA\Schema(type: 'string', enum: Student::STATUSES)),
            new OA\QueryParameter(name: 'sort_by', description: 'Whitelisted sort column.', schema: new OA\Schema(type: 'string', enum: StudentListFilters::SORTABLE, default: 'student_number')),
            new OA\QueryParameter(name: 'sort_dir', description: 'Sort direction.', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'asc')),
            new OA\QueryParameter(name: 'per_page', description: 'Records per page (default 15, max 100).', schema: new OA\Schema(type: 'integer', format: 'int32', default: 15, maximum: 100)),
            new OA\QueryParameter(name: 'page', description: 'Page number.', schema: new OA\Schema(type: 'integer', format: 'int32', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated collection of students.', content: new OA\JsonContent(ref: '#/components/schemas/StudentCollection')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Role may not list students.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Student::class);

        return StudentResource::collection(
            $this->students->paginate(StudentListFilters::fromInput($request->query()), $request->user())
        );
    }

    #[OA\Post(
        path: '/students',
        summary: 'Create a student',
        description: 'Send `user_id` to attach a profile to an existing Student-role account without one, or `email` + `password` (+ `password_confirmation`) to create the account. `program_id` opens the first program period.',
        operationId: 'createStudent',
        tags: ['People'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreStudentRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Student created.', content: new OA\JsonContent(ref: '#/components/schemas/StudentResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'The Student role is not configured.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function store(StoreStudentRequest $request): JsonResponse
    {
        $this->authorize('create', Student::class);

        $student = $this->students->create($request->validated());

        return (new StudentResource($student->load(self::DETAIL)))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/students/{student}',
        summary: 'Fetch a student',
        description: 'Includes the current program and the program history. Staff may fetch any student; a student may fetch only their own profile.',
        operationId: 'getStudent',
        tags: ['People'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'student', description: 'Identifier of the student profile.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        responses: [
            new OA\Response(response: 200, description: 'The requested student.', content: new OA\JsonContent(ref: '#/components/schemas/StudentResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not allowed to view this student.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Student not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Student $student): StudentResource
    {
        $this->authorize('view', $student);

        return new StudentResource($student->load(self::DETAIL));
    }

    #[OA\Put(
        path: '/students/{student}',
        summary: 'Update a student profile',
        description: 'Profile and contact fields; the account name follows first/last name. Status and program have their own endpoints.',
        operationId: 'updateStudent',
        tags: ['People'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'student', description: 'Identifier of the student profile.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateStudentRequest')),
        responses: [
            new OA\Response(response: 200, description: 'The updated student.', content: new OA\JsonContent(ref: '#/components/schemas/StudentResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Student not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    #[OA\Patch(
        path: '/students/{student}',
        summary: 'Update a student profile (PATCH)',
        description: 'Same validation as PUT.',
        operationId: 'patchStudent',
        tags: ['People'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'student', description: 'Identifier of the student profile.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateStudentRequest')),
        responses: [
            new OA\Response(response: 200, description: 'The updated student.', content: new OA\JsonContent(ref: '#/components/schemas/StudentResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Student not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function update(UpdateStudentRequest $request, Student $student): StudentResource
    {
        $this->authorize('update', $student);

        $this->students->update($student, $request->validated());

        return new StudentResource($student->refresh()->load(self::DETAIL));
    }

    #[OA\Post(
        path: '/students/{student}/status',
        summary: 'Change a student status',
        description: 'Allowed: active → inactive|suspended|graduated|withdrawn; inactive|suspended → active|withdrawn. Graduated and withdrawn are final. Graduation/withdrawal closes the active program period. The account may sign in only while active or graduated.',
        operationId: 'changeStudentStatus',
        tags: ['People'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'student', description: 'Identifier of the student profile.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ChangeStudentStatusRequest')),
        responses: [
            new OA\Response(response: 200, description: 'The student with the new status.', content: new OA\JsonContent(ref: '#/components/schemas/StudentResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Student not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Transition not allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed (e.g. date before the program started).', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function changeStatus(ChangeStudentStatusRequest $request, Student $student): StudentResource
    {
        $this->authorize('changeStatus', $student);

        $data = $request->validated();
        $this->students->changeStatus($student, $data['status'], $data['effective_on'] ?? null, $data['notes'] ?? null);

        return new StudentResource($student->refresh()->load(self::DETAIL));
    }

    #[OA\Post(
        path: '/students/{student}/program',
        summary: 'Transfer a student to another program',
        description: 'Closes the active program period as `transferred` and opens a new active one on `effective_on` (default today). Only for active students; refused with 409 while enrollments are pending or confirmed.',
        operationId: 'changeStudentProgram',
        tags: ['People'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'student', description: 'Identifier of the student profile.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ChangeStudentProgramRequest')),
        responses: [
            new OA\Response(response: 200, description: 'The student with the new program.', content: new OA\JsonContent(ref: '#/components/schemas/StudentResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Student not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Student not active, or has open enrollments.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed (inactive program, same program, date before the current period).', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function changeProgram(ChangeStudentProgramRequest $request, Student $student): StudentResource
    {
        $this->authorize('update', $student);

        $data = $request->validated();
        $this->students->changeProgram($student, (int) $data['program_id'], $data['effective_on'] ?? null, $data['notes'] ?? null);

        return new StudentResource($student->refresh()->load(self::DETAIL));
    }

    #[OA\Delete(
        path: '/students/{student}',
        summary: 'Delete a student profile',
        description: 'Only for profiles without academic history (enrollments, GPA, documents, invoices, internships) — otherwise 409; change the status instead. The program assignment rows go with the profile; the account is kept but cannot sign in.',
        operationId: 'deleteStudent',
        tags: ['People'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'student', description: 'Identifier of the student profile.', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'), example: 1)],
        responses: [
            new OA\Response(response: 204, description: 'Profile deleted.'),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Student not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'The student has academic history.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Student $student): JsonResponse
    {
        $this->authorize('delete', $student);

        $this->students->delete($student);

        return response()->json(null, 204);
    }
}
