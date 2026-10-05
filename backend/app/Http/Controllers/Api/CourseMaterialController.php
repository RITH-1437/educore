<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseMaterialRequest;
use App\Http\Requests\UpdateCourseMaterialRequest;
use App\Http\Resources\CourseMaterialResource;
use App\Models\CourseMaterial;
use App\Models\Section;
use App\Services\CourseMaterialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Course materials of a section (`docs/44_Course-Materials-Report.md`):
 * `CourseMaterialPolicy` — the section's lecturers and managers share and
 * remove; a Department Admin over the section and its students read.
 */
class CourseMaterialController extends Controller
{
    public function __construct(private readonly CourseMaterialService $materials) {}

    #[OA\Get(
        path: '/sections/{section}/materials',
        summary: 'Materials of a section',
        description: 'Newest first. Section lecturers, managers, a Department Admin over the section, and students enrolled in it.',
        operationId: 'listCourseMaterials',
        tags: ['Assignments'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'section', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Materials.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/CourseMaterial'))])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Section $section): AnonymousResourceCollection
    {
        $this->authorize('viewSection', [CourseMaterial::class, $section]);

        return CourseMaterialResource::collection($this->materials->listFor($section));
    }

    #[OA\Post(
        path: '/sections/{section}/materials',
        summary: 'Share a material with a section',
        description: 'Section lecturers and managers. `kind=file` with a multipart `file` (pdf, docx, pptx, xlsx, txt, zip, png, jpg; max 20 MB) or `kind=link` with an http(s) `url`. Students enrolled in the section are notified.',
        operationId: 'storeCourseMaterial',
        tags: ['Assignments'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'section', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(required: ['title', 'kind'], properties: [
            new OA\Property(property: 'title', type: 'string', maxLength: 255),
            new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 2000),
            new OA\Property(property: 'kind', type: 'string', enum: CourseMaterial::KINDS),
            new OA\Property(property: 'url', type: 'string', format: 'uri', description: 'Required for kind=link.'),
            new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'Required for kind=file.'),
        ]))),
        responses: [
            new OA\Response(response: 201, description: 'Shared.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/CourseMaterial')])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a lecturer of the section or a manager.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function store(StoreCourseMaterialRequest $request, Section $section): JsonResponse
    {
        $this->authorize('create', [CourseMaterial::class, $section]);

        $material = $this->materials->create($section, $request->safe()->except('file'), $request->file('file'), $request->user());

        return (new CourseMaterialResource($material))->response()->setStatusCode(201);
    }

    #[OA\Put(
        path: '/materials/{material}',
        summary: 'Edit a material',
        description: 'Title and note; a link material also its `url`. A file is replaced by removing the material and sharing a new one.',
        operationId: 'updateCourseMaterial',
        tags: ['Assignments'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'material', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['title'], properties: [
            new OA\Property(property: 'title', type: 'string', maxLength: 255),
            new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 2000),
            new OA\Property(property: 'url', type: 'string', format: 'uri', description: 'Link materials only.'),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Saved.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/CourseMaterial')])),
            new OA\Response(response: 403, description: 'Not allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    #[OA\Patch(
        path: '/materials/{material}',
        summary: 'Edit a material (PATCH)',
        operationId: 'patchCourseMaterial',
        tags: ['Assignments'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'material', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['title'], properties: [
            new OA\Property(property: 'title', type: 'string', maxLength: 255),
            new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 2000),
            new OA\Property(property: 'url', type: 'string', format: 'uri', description: 'Link materials only.'),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Saved.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/CourseMaterial')])),
            new OA\Response(response: 403, description: 'Not allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function update(UpdateCourseMaterialRequest $request, CourseMaterial $material): CourseMaterialResource
    {
        $this->authorize('update', $material);

        return new CourseMaterialResource($this->materials->update($material, $request->validated(), $request->user()));
    }

    #[OA\Delete(
        path: '/materials/{material}',
        summary: 'Remove a material',
        description: 'Deletes the material and its stored file.',
        operationId: 'deleteCourseMaterial',
        tags: ['Assignments'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'material', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 204, description: 'Removed.'),
            new OA\Response(response: 403, description: 'Not allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Request $request, CourseMaterial $material): Response
    {
        $this->authorize('delete', $material);
        $this->materials->delete($material, $request->user());

        return response()->noContent();
    }

    #[OA\Get(
        path: '/materials/{material}/file',
        summary: 'Download a material file',
        description: 'Streams the private object to anyone who may read the section\'s materials.',
        operationId: 'downloadCourseMaterial',
        tags: ['Assignments'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'material', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'The file (attachment).'),
            new OA\Response(response: 403, description: 'Not allowed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'A link material has no file.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function download(CourseMaterial $material): StreamedResponse
    {
        $this->authorize('view', $material);

        return $this->materials->download($material);
    }
}
