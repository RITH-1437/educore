<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUniversityRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `UniversityPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        $universityId = $this->route('university')?->getKey();

        return [
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('universities', 'code')->ignore($universityId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'logo_key' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'is_current' => ['sometimes', 'boolean'],
        ];
    }
}
