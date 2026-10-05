<?php

namespace App\Http\Requests;

use App\Models\CourseMaterial;
use Illuminate\Foundation\Http\FormRequest;

/** Edit a material's title and note, and a link's URL; files are replaced by sharing a new one (report 44). */
class UpdateCourseMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var CourseMaterial|null $material */
        $material = $this->route('material');

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'url' => $material?->kind === CourseMaterial::KIND_LINK
                ? ['required', 'url:http,https', 'max:2048']
                : ['prohibited'],
        ];
    }
}
