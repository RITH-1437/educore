<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\Lecturer;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Either `user_id` (link an existing lecturer account without a profile) or
 * `email` + `password` (create the account) must be given.
 */
class StoreLecturerRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `LecturerPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'nullable', 'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
                Rule::unique('lecturers', 'user_id'),
                function (string $attribute, mixed $value, Closure $fail) {
                    $user = User::query()->with('role')->find($value);

                    if ($user !== null && ! $user->isRole(Role::Lecturer->value)) {
                        $fail('The selected account does not have the Lecturer role.');
                    }
                },
            ],
            'email' => ['required_without:user_id', 'prohibits:user_id', 'nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required_without:user_id', 'nullable', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:50'],
            'staff_number' => ['required', 'string', 'max:50', Rule::unique('lecturers', 'staff_number')],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:50'],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->whereNull('deleted_at')],
            'position' => ['nullable', 'string', 'max:100'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['sometimes', 'string', Rule::in(Lecturer::EMPLOYMENT_TYPES)],
        ];
    }
}
