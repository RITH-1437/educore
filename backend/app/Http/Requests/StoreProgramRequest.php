<?php

namespace App\Http\Requests;

use App\Models\Program;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `ProgramPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->whereNull('deleted_at')],
            'code' => ['required', 'string', 'max:50', Rule::unique('programs', 'code')],
            'name' => [
                'required', 'string', 'max:255',
                // Unique within the department — `skills/program-management/SKILL.md` §4.
                Rule::unique('programs', 'name')->where(
                    fn ($query) => $query->where('department_id', $this->input('department_id'))
                ),
            ],
            'degree_level' => ['required', 'string', Rule::in(Program::DEGREE_LEVELS)],
            'duration_years' => ['nullable', 'integer', 'min:1', 'max:10'],
            'credits_required' => ['nullable', 'numeric', 'min:0', 'max:9999.9'],
        ];
    }
}
