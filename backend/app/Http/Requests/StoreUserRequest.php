<?php

namespace App\Http\Requests;

use App\Enums\Role as RoleSlug;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:50'],
            'role_id' => ['required', Rule::exists('roles', 'id')],
            'is_active' => ['sometimes', 'boolean'],
            // Only a Department Admin has a department; it limits what they can see.
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->whereNull('deleted_at'), Rule::prohibitedIf(fn () => ! $this->isDepartmentAdminRole())],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    private function isDepartmentAdminRole(): bool
    {
        return Role::query()->whereKey($this->input('role_id'))->value('slug') === RoleSlug::DepartmentAdmin->value;
    }
}
