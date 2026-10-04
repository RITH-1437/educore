<?php

namespace App\Http\Resources;

use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DocumentType
 */
class DocumentTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'requires_fee' => $this->requires_fee,
            'fee_amount' => (float) $this->fee_amount,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'needs_semester' => $this->needsSemester(),
            'is_generatable' => in_array($this->code, DocumentType::GENERATABLE, true),
            'requests_count' => $this->whenCounted('requests'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
