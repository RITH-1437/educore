<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly GpaService $gpa,
    ) {}

    /**
     * Build the user's profile view data.
     *
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        $user->loadMissing(['role', 'department']);

        $profile = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => [
                'id' => $user->role?->id,
                'name' => $user->role?->name,
                'slug' => $user->role?->slug,
            ],
            'department' => $user->department ? [
                'id' => $user->department->id,
                'name' => $user->department->name,
                'code' => $user->department->code,
            ] : null,
            'avatar_key' => $user->avatar_key,
            'avatar_url' => $user->avatarUrl(),
            'is_active' => (bool) $user->is_active,
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'created_at' => $user->created_at?->toIso8601String(),
            'student' => null,
            'lecturer' => null,
        ];

        if ($user->isRole(Role::Student->value) && ($student = $user->student) !== null) {
            $student->loadMissing(['currentProgram.program.department']);
            $currentProgram = $student->currentProgram?->program;
            $gpaSummary = $this->gpa->summary($student);

            if ($currentProgram?->department) {
                $profile['department'] = [
                    'id' => $currentProgram->department->id,
                    'name' => $currentProgram->department->name,
                    'code' => $currentProgram->department->code,
                ];
            }

            $profile['student'] = [
                'id' => $student->id,
                'student_number' => $student->student_number,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'gender' => $student->gender,
                'date_of_birth' => $student->date_of_birth?->toDateString(),
                'address' => $student->address,
                'emergency_contact_name' => $student->emergency_contact_name,
                'emergency_contact_phone' => $student->emergency_contact_phone,
                'national_id' => $student->national_id,
                'enrollment_date' => $student->enrollment_date?->toDateString(),
                'status' => $student->status,
                'program' => $currentProgram ? [
                    'id' => $currentProgram->id,
                    'code' => $currentProgram->code,
                    'name' => $currentProgram->name,
                ] : null,
                'academic_summary' => [
                    'cumulative_gpa' => $gpaSummary['cumulative']['gpa'] ?? null,
                    'earned_credits' => $gpaSummary['cumulative']['earned_credits'] ?? 0,
                    'attempted_credits' => $gpaSummary['cumulative']['attempted_credits'] ?? 0,
                    'current_courses_count' => $student->enrollments()->whereIn('status', Enrollment::OPEN_STATUSES)->count(),
                ],
            ];
        }

        if ($user->isRole(Role::Lecturer->value) && ($lecturer = $user->lecturer) !== null) {
            $lecturer->loadMissing('department');

            if ($lecturer->department) {
                $profile['department'] = [
                    'id' => $lecturer->department->id,
                    'name' => $lecturer->department->name,
                    'code' => $lecturer->department->code,
                ];
            }

            $profile['lecturer'] = [
                'id' => $lecturer->id,
                'staff_number' => $lecturer->staff_number,
                'first_name' => $lecturer->first_name,
                'last_name' => $lecturer->last_name,
                'title' => $lecturer->title,
                'position' => $lecturer->position,
                'specialization' => $lecturer->specialization,
                'employment_type' => $lecturer->employment_type,
                'is_active' => (bool) $lecturer->is_active,
                'teaching_summary' => [
                    'active_sections_count' => $lecturer->sections()->count(),
                ],
            ];
        }

        return $profile;
    }

    /**
     * Update user and role-specific profile details.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input, ?UploadedFile $avatarFile = null): User
    {
        return DB::transaction(function () use ($user, $input, $avatarFile) {
            $userBefore = $user->only(['name', 'phone', 'avatar_key']);

            $userUpdates = [];
            if (array_key_exists('name', $input)) {
                $userUpdates['name'] = $input['name'];
            }
            if (array_key_exists('phone', $input)) {
                $userUpdates['phone'] = $input['phone'];
            }

            // Avatar handling: remove, upload from device, or set from URL
            if (! empty($input['remove_avatar'])) {
                $this->deleteStoredAvatar($user->avatar_key);
                $userUpdates['avatar_key'] = null;
            } elseif ($avatarFile !== null) {
                $this->deleteStoredAvatar($user->avatar_key);
                $ext = strtolower($avatarFile->getClientOriginalExtension() ?: $avatarFile->extension() ?: 'jpg');
                $key = "avatars/{$user->id}/".Str::uuid().".{$ext}";
                Storage::disk($this->disk())->putFileAs(dirname($key), $avatarFile, basename($key), ['visibility' => 'public']);
                $userUpdates['avatar_key'] = $key;
            } elseif (array_key_exists('avatar_url', $input) && ! empty($input['avatar_url'])) {
                $this->deleteStoredAvatar($user->avatar_key);
                $userUpdates['avatar_key'] = $input['avatar_url'];
            }

            if (! empty($userUpdates)) {
                $user->update($userUpdates);
            }

            if ($user->isRole(Role::Student->value) && ($student = $user->student) !== null) {
                $studentData = [];
                if (array_key_exists('address', $input)) {
                    $studentData['address'] = $input['address'];
                }
                if (array_key_exists('emergency_contact_name', $input)) {
                    $studentData['emergency_contact_name'] = $input['emergency_contact_name'];
                }
                if (array_key_exists('emergency_contact_phone', $input)) {
                    $studentData['emergency_contact_phone'] = $input['emergency_contact_phone'];
                }

                if (! empty($studentData)) {
                    $student->update($studentData);
                }
            }

            if ($user->isRole(Role::Lecturer->value) && ($lecturer = $user->lecturer) !== null) {
                if (array_key_exists('specialization', $input)) {
                    $lecturer->update(['specialization' => $input['specialization']]);
                }
            }

            $this->audit->record(
                'profile.updated',
                $user,
                before: $userBefore,
                after: $user->only(['name', 'phone', 'avatar_key']),
                description: 'User profile details updated.',
                actor: $user,
            );

            return $user->refresh();
        });
    }

    public function disk(): string
    {
        return (string) config('academics.uploads_disk', 's3');
    }

    private function deleteStoredAvatar(?string $key): void
    {
        if (! empty($key) && ! str_starts_with($key, 'http://') && ! str_starts_with($key, 'https://')) {
            Storage::disk($this->disk())->delete($key);
        }
    }
}
