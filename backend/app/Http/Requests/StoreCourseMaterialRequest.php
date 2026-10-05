<?php

namespace App\Http\Requests;

use App\Models\CourseMaterial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Share a course material: a file (type and size limited) or an http(s) link (report 44). */
class StoreCourseMaterialRequest extends FormRequest
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
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'kind' => ['required', Rule::in(CourseMaterial::KINDS)],
            'url' => ['nullable', 'required_if:kind,link', 'prohibited_if:kind,file', 'url:http,https', 'max:2048'],
            'file' => [
                'nullable', 'required_if:kind,file', 'prohibited_if:kind,link', 'file',
                'mimes:'.implode(',', config('academics.material_mimes')),
                'max:'.config('academics.material_max_kb'),
            ],
        ];
    }
}
