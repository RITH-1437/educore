<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Staff must name the student; a student may not — their own profile is used
 * (`skills/enrollment/SKILL.md` §12: never trust a client student_id).
 */
class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `EnrollmentPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        $isStudent = (bool) $this->user()?->isRole(Role::Student->value);

        return [
            'section_id' => ['required', 'integer', Rule::exists('sections', 'id')],
            'student_id' => $isStudent
                ? ['prohibited']
                : ['required', 'integer', Rule::exists('students', 'id')],
        ];
    }
}
