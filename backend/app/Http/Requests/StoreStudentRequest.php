<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\Student;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Either `user_id` (link an existing Student-role account without a profile)
 * or `email` + `password` (create the account) must be given.
 */
class StoreStudentRequest extends FormRequest
{
    /** Institution student-ID format: letters, digits and dashes, 4–50 chars. */
    public const STUDENT_NUMBER_FORMAT = '/^[A-Za-z0-9-]{4,50}$/';

    public function authorize(): bool
    {
        // Authorization is enforced by `StudentPolicy` in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'nullable', 'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
                Rule::unique('students', 'user_id'),
                function (string $attribute, mixed $value, Closure $fail) {
                    $user = User::query()->with('role')->find($value);

                    if ($user !== null && ! $user->isRole(Role::Student->value)) {
                        $fail('The selected account does not have the Student role.');
                    }
                },
            ],
            'email' => ['required_without:user_id', 'prohibits:user_id', 'nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required_without:user_id', 'nullable', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:50'],
            ...self::profileRules(),
            'student_number' => ['required', 'string', 'regex:'.self::STUDENT_NUMBER_FORMAT, Rule::unique('students', 'student_number')],
            'national_id' => ['nullable', 'string', 'max:50', Rule::unique('students', 'national_id')],
            'program_id' => ['required', 'integer', Rule::exists('programs', 'id')->where('is_active', true)],
            'program_started_on' => ['nullable', 'date'],
        ];
    }

    /**
     * Profile rules shared with `UpdateStudentRequest`.
     *
     * @return array<string, list<mixed>>
     */
    public static function profileRules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['nullable', 'string', Rule::in(Student::GENDERS)],
            'date_of_birth' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'address' => ['nullable', 'string', 'max:1000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'enrollment_date' => ['nullable', 'date', 'after:1900-01-01'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'student_number.regex' => 'The student ID may contain only letters, digits and dashes (4–50 characters).',
            'program_id.exists' => 'Choose an active program.',
        ];
    }
}
