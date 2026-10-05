<?php

namespace App\Http\Resources;

use App\Models\CourseMaterial;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A course material (`docs/44_Course-Materials-Report.md`). File details carry
 * the name, type and size only — never the storage key; the file is fetched
 * through the authorized download route.
 *
 * @mixin CourseMaterial
 */
class CourseMaterialResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'section_id' => $this->section_id,
            'title' => $this->title,
            'description' => $this->description,
            'kind' => $this->kind,
            'url' => $this->url,
            'file' => $this->whenLoaded('file', fn () => $this->file === null ? null : [
                'name' => $this->file->original_name,
                'mime_type' => $this->file->mime_type,
                'size' => $this->file->size,
            ]),
            'shared_by' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'course' => $this->when($this->relationLoaded('section') && $this->section->relationLoaded('offering'), fn () => [
                'code' => $this->section->offering->course->code,
                'name' => $this->section->offering->course->name,
                'section' => $this->section->code,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
