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
            'faculty_id' => $this->faculty_id,
            'faculty' => $this->whenLoaded('faculty', fn () => [
                'id' => $this->faculty->id,
                'code' => $this->faculty->code,
                'name' => $this->faculty->name,
                'university_id' => $this->faculty->university_id,
            ]),
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
