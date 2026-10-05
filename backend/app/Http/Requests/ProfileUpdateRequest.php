<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'avatar' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'avatar_url' => ['nullable', 'url', 'max:2048', 'regex:/^https?:\/\//i'],
            'remove_avatar' => ['nullable', 'boolean'],
        ];

        if ($this->user()?->isRole(Role::Student->value)) {
            $rules['address'] = ['nullable', 'string', 'max:255'];
            $rules['emergency_contact_name'] = ['nullable', 'string', 'max:255'];
            $rules['emergency_contact_phone'] = ['nullable', 'string', 'max:30'];
        }

        if ($this->user()?->isRole(Role::Lecturer->value)) {
            $rules['specialization'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
