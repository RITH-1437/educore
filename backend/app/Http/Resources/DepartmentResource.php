<?php

namespace App\Http\Resources;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Department
 */
class DepartmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'university_id' => $this->university_id,
            'university' => $this->whenLoaded('university', fn () => [
                'id' => $this->university->id,
                'code' => $this->university->code,
                'name' => $this->university->name,
            ]),
            'programs_count' => $this->whenCounted('programs'),
            'code' => $this->code,
            'name' => $this->name,
            'head_name' => $this->head_name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
