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

    public function show(DocumentType $documentType): DocumentTypeResource
    {
        $this->authorize('view', $documentType);

        return new DocumentTypeResource($documentType->loadCount('requests'));
    }

    public function store(StoreDocumentTypeRequest $request): JsonResponse
    {
        $this->authorize('create', DocumentType::class);

        $created = $this->documentTypes->create($request->validated());

        return (new DocumentTypeResource($created))->response()->setStatusCode(201);
    }

    public function update(UpdateDocumentTypeRequest $request, DocumentType $documentType): DocumentTypeResource
    {
        $this->authorize('update', $documentType);

        $updated = $this->documentTypes->update($documentType, $request->validated());

        return new DocumentTypeResource($updated);
    }

    public function destroy(DocumentType $documentType): Response
    {
        $this->authorize('delete', $documentType);

        $this->documentTypes->delete($documentType);

        return response()->noContent();
    }
}
