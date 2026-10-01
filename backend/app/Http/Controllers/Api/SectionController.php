<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignSectionLecturerRequest;
use App\Http\Requests\UpdateSectionRequest;
use App\Http\Resources\SectionResource;
use App\Models\Lecturer;
use App\Models\Section;
use App\Services\CourseOfferingService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Section endpoints (module 9.8). Authorized through the parent offering.
 */
class SectionController extends Controller
{
    private const DETAIL = ['offering.course:id,code,name,credits', 'offering.semester.academicYear:id,code', 'lecturers'];

    public function __construct(
        private readonly CourseOfferingService $offerings,
    ) {}

    #[OA\Get(
        path: '/sections/{section}',
        summary: 'Fetch a section with its offering and lecturers',
        operationId: 'getSection',
        tags: ['Academics'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'section', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'The section.', content: new OA\JsonContent(ref: '#/components/schemas/SectionResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Role may not view sections.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Section $section): SectionResource
    {
        $this->authorize('view', $section->offering);

        return new SectionResource($section->load(self::DETAIL));
    }

    #[OA\Put(
        path: '/sections/{section}',
        summary: 'Update a section',
        description: 'Capacity cannot drop below the open (pending/confirmed) enrollments.',
        operationId: 'updateSection',
        tags: ['Academics'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'section', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateSectionRequest')),
        responses: [
            new OA\Response(response: 200, description: 'The updated section.', content: new OA\JsonContent(ref: '#/components/schemas/SectionResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function update(UpdateSectionRequest $request, Section $section): SectionResource
    {
        $this->authorize('update', $section->offering);

        $this->offerings->updateSection($section, $request->validated());

        return new SectionResource($section->refresh()->load(self::DETAIL));
    }

    #[OA\Delete(
        path: '/sections/{section}',
        summary: 'Delete a section',
        description: 'Refused with 409 once it has schedule entries, enrollments, attendance, assignments or exams. Lecturer assignments are removed with it.',
        operationId: 'deleteSection',
        tags: ['Academics'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'section', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 204, description: 'Deleted.'),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'The section has academic history.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Section $section): JsonResponse
    {
        $this->authorize('update', $section->offering);

        $this->offerings->deleteSection($section);

        return response()->json(null, 204);
    }

    #[OA\Post(
        path: '/sections/{section}/lecturers',
        summary: 'Assign a lecturer to a section',
        description: 'Active lecturers only, no duplicates, at most one primary (422). Refused (409) in a completed semester.',
        operationId: 'assignSectionLecturer',
        tags: ['Academics'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'section', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AssignSectionLecturerRequest')),
        responses: [
            new OA\Response(response: 201, description: 'The section with its lecturers.', content: new OA\JsonContent(ref: '#/components/schemas/SectionResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Semester completed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function assignLecturer(AssignSectionLecturerRequest $request, Section $section): JsonResponse
    {
        $this->authorize('update', $section->offering);

        $this->offerings->assignLecturer(
            $section,
            Lecturer::query()->findOrFail($request->validated('lecturer_id')),
            $request->validated('role', 'primary'),
        );

        return (new SectionResource($section->load(self::DETAIL)))->response()->setStatusCode(201);
    }

    #[OA\Delete(
        path: '/sections/{section}/lecturers/{lecturer}',
        summary: 'Remove a lecturer from a section',
        operationId: 'removeSectionLecturer',
        tags: ['Academics'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'section', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
            new OA\PathParameter(name: 'lecturer', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Removed.'),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found or not assigned.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function removeLecturer(Section $section, Lecturer $lecturer): JsonResponse
    {
        $this->authorize('update', $section->offering);
        abort_unless($section->lecturers()->whereKey($lecturer->getKey())->exists(), 404, 'Lecturer is not assigned to this section.');

        $this->offerings->removeLecturer($section, $lecturer);

        return response()->json(null, 204);
    }
}
