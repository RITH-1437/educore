<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `DepartmentPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        $facultyId = $this->route('faculty')?->getKey() ?? $this->input('faculty_id');

        return [
            'faculty_id' => ['sometimes', 'integer', Rule::exists('faculties', 'id')],
            'code' => ['required', 'string', 'max:50', Rule::unique('departments', 'code')],
            'name' => [
                'required', 'string', 'max:255',
                // Unique within the parent faculty — see
                // `skills/faculty-department/SKILL.md` §4.
                Rule::unique('departments', 'name')->where(
                    fn ($query) => $query->where('faculty_id', $facultyId)
                ),
            ],
            'head_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * The faculty comes from the URL for nested routes and from the payload for
     * the flat `/api/departments` endpoint.
     */
    public function facultyKey(): ?int
    {
        $faculty = $this->route('faculty');

        if ($faculty !== null) {
            return $faculty->getKey();
        }

        $facultyId = $this->input('faculty_id');

        return $facultyId === null ? null : (int) $facultyId;
    }
}
