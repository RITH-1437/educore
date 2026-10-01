<?php

namespace App\Http\Requests;

use App\Models\Program;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by `ProgramPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        $programId = $this->route('program')?->getKey();

        // Scope uniqueness to the department the program will END UP in; when
        // none is submitted that is the one it already belongs to.
        $departmentId = $this->input('department_id') ?? $this->route('program')?->department_id;

        return [
            'department_id' => ['sometimes', 'integer', Rule::exists('departments', 'id')->whereNull('deleted_at')],
            'code' => ['required', 'string', 'max:50', Rule::unique('programs', 'code')->ignore($programId)],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('programs', 'name')
                    ->where(fn ($query) => $query->where('department_id', $departmentId))
                    ->ignore($programId),
            ],
            'degree_level' => ['required', 'string', Rule::in(Program::DEGREE_LEVELS)],
            'duration_years' => ['nullable', 'integer', 'min:1', 'max:10'],
            'credits_required' => ['nullable', 'numeric', 'min:0', 'max:9999.9'],
        ];
    }
}
