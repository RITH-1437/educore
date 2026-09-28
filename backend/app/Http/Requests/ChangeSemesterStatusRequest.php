<?php

namespace App\Http\Requests;

use App\Enums\SemesterStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeSemesterStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(SemesterStatus::class)],
        ];
    }
}
