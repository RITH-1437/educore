<?php

namespace App\Http\Resources;

use App\Models\Faculty;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Faculty
 */
class FacultyResource extends JsonResource
{
    /**
     * Departments are embedded so the list screen can show the faculty →
     * department tree without a second round trip
     * (`skills/faculty-department/SKILL.md` §5, §7).
     *
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
            'code' => $this->code,
            'name' => $this->name,
            'dean_name' => $this->dean_name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'departments' => DepartmentResource::collection($this->whenLoaded('departments')),
            'departments_count' => $this->whenCounted('departments'),
            'active_departments_count' => $this->when(isset($this->active_departments_count), $this->active_departments_count),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
