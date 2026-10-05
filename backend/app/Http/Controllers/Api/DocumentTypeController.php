<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentTypeRequest;
use App\Http\Requests\UpdateDocumentTypeRequest;
use App\Http\Resources\DocumentTypeResource;
use App\Models\DocumentType;
use App\Services\DocumentTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class DocumentTypeController extends Controller
{
    public function __construct(
        private readonly DocumentTypeService $documentTypes,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', DocumentType::class);

        $search = $request->input('search');
        $types = $this->documentTypes->paginate(15, $search);

        return DocumentTypeResource::collection($types);
    }

    #[OA\Get(
        path: '/document-types/{documentType}',
        summary: 'Get document type details',
        operationId: 'getDocumentType',
        tags: ['Documents'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'documentType', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Document type details with requests count.', content: new OA\JsonContent(ref: '#/components/schemas/DocumentTypeResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(DocumentType $documentType): DocumentTypeResource
    {
        $this->authorize('view', $documentType);

        return new DocumentTypeResource($documentType->loadCount('requests'));
    }

    #[OA\Post(
        path: '/document-types',
        summary: 'Create document type',
        description: 'Super Admin and University Admin only.',
        operationId: 'storeDocumentType',
        tags: ['Documents'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreDocumentTypeRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Document type created.', content: new OA\JsonContent(ref: '#/components/schemas/DocumentTypeResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function store(StoreDocumentTypeRequest $request): JsonResponse
    {
        $this->authorize('create', DocumentType::class);

        $created = $this->documentTypes->create($request->validated());

        return (new DocumentTypeResource($created))->response()->setStatusCode(201);
    }

    #[OA\Put(
        path: '/document-types/{documentType}',
        summary: 'Update document type',
        description: 'Super Admin and University Admin only.',
        operationId: 'updateDocumentType',
        tags: ['Documents'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'documentType', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateDocumentTypeRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Document type updated.', content: new OA\JsonContent(ref: '#/components/schemas/DocumentTypeResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    #[OA\Patch(
        path: '/document-types/{documentType}',
        summary: 'Update document type (PATCH)',
        description: 'Super Admin and University Admin only.',
        operationId: 'patchDocumentType',
        tags: ['Documents'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'documentType', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateDocumentTypeRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Document type updated.', content: new OA\JsonContent(ref: '#/components/schemas/DocumentTypeResourceResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function update(UpdateDocumentTypeRequest $request, DocumentType $documentType): DocumentTypeResource
    {
        $this->authorize('update', $documentType);

        $updated = $this->documentTypes->update($documentType, $request->validated());

        return new DocumentTypeResource($updated);
    }

    #[OA\Delete(
        path: '/document-types/{documentType}',
        summary: 'Delete document type',
        description: 'Super Admin and University Admin only. Refused with 409 if document requests reference this type.',
        operationId: 'deleteDocumentType',
        tags: ['Documents'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'documentType', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Document type deleted.'),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Type referenced by existing requests.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(DocumentType $documentType): Response
    {
        $this->authorize('delete', $documentType);

        $this->documentTypes->delete($documentType);

        return response()->noContent();
    }
}
