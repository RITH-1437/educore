<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Role as RoleModel;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One account per role, development only (`<name>@educore.kh` / `<name>@123`).
 * Accounts only — no lecturer/student profiles are created and no department
 * is assigned, so each role signs in to its dashboard's empty state until real
 * records are linked through the application.
 */
class UserSeeder extends Seeder
{
    public const ACCOUNTS = [
        ['admin', 'Super Admin', Role::SuperAdmin],
        ['university', 'University Admin', Role::UniversityAdmin],
        ['department', 'Department Admin', Role::DepartmentAdmin],
        ['lecturer', 'Lecturer', Role::Lecturer],
        ['student', 'Student', Role::Student],
    ];

    public function run(): void
    {
        $roles = RoleModel::pluck('id', 'slug');

        foreach (self::ACCOUNTS as [$login, $name, $role]) {
            User::query()->updateOrCreate(
                ['email' => "{$login}@educore.kh"],
                [
                    'name' => $name,
                    'password' => "{$login}@123",
                    'role_id' => $roles[$role->value],
                    'email_verified_at' => now(),
                    'is_active' => true,
                ],
            );
        }
    }
}
