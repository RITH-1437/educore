<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\DocumentType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Document type management (`docs/40_Document-Fee-Billing-and-Type-Management-Report.md`).
 *
 * Super Admin and University Admin manage available document types, fees, and active states.
 * Deleting is guarded when requests exist for that document type.
 */
class DocumentTypeService
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return LengthAwarePaginator<int, DocumentType>
     */
    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return DocumentType::query()
            ->withCount('requests')
            ->when($search, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'ilike', "%{$s}%")
                ->orWhere('code', 'ilike', "%{$s}%")
                ->orWhere('description', 'ilike', "%{$s}%")))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, DocumentType>
     */
    public function all(): Collection
    {
        return DocumentType::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): DocumentType
    {
        return DB::transaction(function () use ($data) {
            $type = DocumentType::query()->create([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'requires_fee' => (bool) ($data['requires_fee'] ?? false),
                'fee_amount' => (float) ($data['fee_amount'] ?? 0),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
            ]);

            $this->audit->record('document_type.created', $type, after: $type->toArray());

            return $type;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(DocumentType $type, array $data): DocumentType
    {
        return DB::transaction(function () use ($type, $data) {
            $before = $type->only(['name', 'description', 'requires_fee', 'fee_amount', 'is_active', 'sort_order']);

            $type->update([
                'name' => $data['name'] ?? $type->name,
                'description' => array_key_exists('description', $data) ? $data['description'] : $type->description,
                'requires_fee' => array_key_exists('requires_fee', $data) ? (bool) $data['requires_fee'] : $type->requires_fee,
                'fee_amount' => array_key_exists('fee_amount', $data) ? (float) $data['fee_amount'] : $type->fee_amount,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $type->is_active,
                'sort_order' => array_key_exists('sort_order', $data) ? (int) $data['sort_order'] : $type->sort_order,
            ]);

            $this->audit->record('document_type.updated', $type, before: $before, after: $type->only(array_keys($before)));

            return $type->refresh();
        });
    }

    public function delete(DocumentType $type): void
    {
        DB::transaction(function () use ($type) {
            if ($type->requests()->exists()) {
                throw new BusinessRuleException("Cannot delete '{$type->name}' because existing student requests reference it. Deactivate it instead.");
            }

            $before = $type->toArray();
            $type->delete();

            $this->audit->record('document_type.deleted', $type, before: $before);
        });
    }
}
