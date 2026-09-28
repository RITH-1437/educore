<?php

namespace App\Http\Requests;

use App\Enums\AcademicYearStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeAcademicYearStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(AcademicYearStatus::class)],
        ];
    }
}
