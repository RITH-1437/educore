<?php

namespace App\Http\Resources;

use App\Models\University;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin University
 */
class UniversityResource extends JsonResource
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
            'short_name' => $this->short_name,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'logo_key' => $this->logo_key,
            'website' => $this->website,
            'is_current' => $this->is_current,
            'departments_count' => $this->whenCounted('departments'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
