<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentTypeRequest;
use App\Http\Requests\UpdateDocumentTypeRequest;
use App\Http\Resources\DocumentTypeResource;
use App\Models\DocumentType;
use App\Services\DocumentTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Document types management controller (`docs/40_Document-Fee-Billing-and-Type-Management-Report.md`).
 *
 * Super Admin and University Admin manage document types, fees, and rules.
 */
class DocumentTypesController extends Controller
{
    public function __construct(
        private readonly DocumentTypeService $documentTypes,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DocumentType::class);

        $search = $request->input('search');
        $types = $this->documentTypes->paginate(15, $search);

        return Inertia::render('DocumentTypes/Index', [
            'types' => DocumentTypeResource::collection($types),
            'filters' => ['search' => $search],
            'canManage' => $request->user()->can('create', DocumentType::class),
        ]);
    }

    public function store(StoreDocumentTypeRequest $request): RedirectResponse
    {
        $this->authorize('create', DocumentType::class);

        $this->documentTypes->create($request->validated());

        return back()->with('success', 'Document type created.');
    }

    public function update(UpdateDocumentTypeRequest $request, DocumentType $documentType): RedirectResponse
    {
        $this->authorize('update', $documentType);

        $this->documentTypes->update($documentType, $request->validated());

        return back()->with('success', 'Document type updated.');
    }

    public function destroy(DocumentType $documentType): RedirectResponse
    {
        $this->authorize('delete', $documentType);

        $this->documentTypes->delete($documentType);

        return back()->with('success', 'Document type deleted.');
    }
}
