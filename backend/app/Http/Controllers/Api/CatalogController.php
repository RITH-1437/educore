<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\CatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * The public course catalog (report 53): no sign-in, rate limited
 * (`throttle:catalog`), public data only (see `CatalogService`).
 */
class CatalogController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalog,
    ) {}

    #[OA\Get(
        path: '/catalog/departments',
        summary: 'Departments with public courses (catalog categories)',
        operationId: 'catalogDepartments',
        tags: ['Catalog'],
        security: [],
        responses: [
            new OA\Response(response: 200, description: 'Active departments with at least one active course, A–Z.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/CatalogDepartment')),
            ])),
            new OA\Response(response: 429, description: 'Rate limited.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function departments(): JsonResponse
    {
        return response()->json(['data' => $this->catalog->departments()->map(fn ($d) => [
            'id' => $d->id, 'code' => $d->code, 'name' => $d->name, 'description' => $d->description, 'courses_count' => $d->courses_count,
        ])->values()]);
    }

    #[OA\Get(
        path: '/catalog/courses',
        summary: 'Search the public course catalog',
        description: 'Active courses of active departments, A–Z by code, 12 per page. `meta.semester` is "this semester" (the latest open one); `offered` and `lecturers` refer to it. Lecturers are listed only when the catalog is configured to show them.',
        operationId: 'catalogCourses',
        tags: ['Catalog'],
        security: [],
        parameters: [
            new OA\QueryParameter(name: 'search', required: false, description: 'Code, name or description.', schema: new OA\Schema(type: 'string', maxLength: 100)),
            new OA\QueryParameter(name: 'filters[department_id]', required: false, schema: new OA\Schema(type: 'integer', format: 'int64')),
            new OA\QueryParameter(name: 'filters[offered]', required: false, description: 'Only courses taught this semester.', schema: new OA\Schema(type: 'boolean')),
            new OA\QueryParameter(name: 'page', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Courses.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/CatalogCourse')),
                new OA\Property(property: 'meta', type: 'object', properties: [
                    new OA\Property(property: 'current_page', type: 'integer'),
                    new OA\Property(property: 'last_page', type: 'integer'),
                    new OA\Property(property: 'total', type: 'integer'),
                    new OA\Property(property: 'semester', ref: '#/components/schemas/CatalogSemester', nullable: true),
                ]),
            ])),
            new OA\Response(response: 422, description: 'Invalid filter.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 429, description: 'Rate limited.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function courses(Request $request): JsonResponse
    {
        $page = $this->catalog->courses(self::filters($request));

        return response()->json([
            'data' => $page->getCollection()->map(fn (Course $course) => $this->catalog->present($course))->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(), 'semester' => $this->catalog->presentSemester($this->catalog->semester())],
        ]);
    }

    #[OA\Get(
        path: '/catalog/courses/{course}',
        summary: 'One public course',
        description: 'Description, hours, prerequisites, programs and this semester\'s sections (weekly times, rooms, lecturers). 404 for a draft or archived course.',
        operationId: 'catalogCourse',
        tags: ['Catalog'],
        security: [],
        parameters: [new OA\PathParameter(name: 'course', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'The course.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/CatalogCourseDetail'),
            ])),
            new OA\Response(response: 404, description: 'Not a public course.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 429, description: 'Rate limited.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(int $course): JsonResponse
    {
        $found = $this->catalog->find($course);
        abort_if($found === null, 404, 'Course not found.');

        return response()->json(['data' => $this->catalog->present($found, detail: true)]);
    }

    /**
     * Validated catalog filters (shared with the web page).
     *
     * @return array{search: ?string, department_id: ?int, offered: bool}
     */
    public static function filters(Request $request): array
    {
        $valid = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'filters.department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'filters.offered' => ['nullable', 'boolean'],
        ]);

        return [
            'search' => $valid['search'] ?? null,
            'department_id' => isset($valid['filters']['department_id']) ? (int) $valid['filters']['department_id'] : null,
            'offered' => filter_var($valid['filters']['offered'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }
}
