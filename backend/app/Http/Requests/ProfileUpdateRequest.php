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
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
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
