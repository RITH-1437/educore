<?php

namespace App\Http\Resources;

use App\Models\Student;
use App\Models\StudentProgram;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Never exposes credentials: only the linked account's public fields.
 *
 * @mixin Student
 */
class StudentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
                'is_active' => (bool) $this->user->is_active,
            ] : null),
            'student_number' => $this->student_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->fullName(),
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'address' => $this->address,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'national_id' => $this->national_id,
            'enrollment_date' => $this->enrollment_date?->toDateString(),
            'status' => $this->status,
            'allowed_statuses' => Student::TRANSITIONS[$this->status] ?? [],
            'current_program' => $this->whenLoaded('currentProgram', fn () => $this->currentProgram
                ? self::programRow($this->currentProgram)
                : null),
            'program_history' => $this->whenLoaded('programHistory', fn () => $this->programHistory
                ->map(fn (StudentProgram $row) => self::programRow($row))
                ->values()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function programRow(StudentProgram $row): array
    {
        $program = $row->relationLoaded('program') ? $row->program : null;
        $department = $program?->relationLoaded('department') ? $program->department : null;

        return [
            'id' => $row->id,
            'program_id' => $row->program_id,
            'program' => $program ? [
                'id' => $program->id,
                'code' => $program->code,
                'name' => $program->name,
                'department' => $department ? [
                    'id' => $department->id,
                    'code' => $department->code,
                    'name' => $department->name,
                ] : null,
            ] : null,
            'started_on' => $row->started_on?->toDateString(),
            'ended_on' => $row->ended_on?->toDateString(),
            'status' => $row->status,
            'notes' => $row->notes,
        ];
    }
}
