<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\Role as RoleModel;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds lecturer accounts and their profiles for the seeded departments.
 *
 * Deterministic and idempotent (`docs/database/seed-strategy.md`): accounts
 * upsert by email, profiles by `staff_number`. Development-only password
 * `lecturer@123`, matching the documented admin seed pattern. Departments come
 * from `UniversityStructureSeeder`; missing ones are skipped.
 */
class LecturerSeeder extends Seeder
{
    /**
     * @var list<array{staff: string, first: string, last: string, title: string, email: string, department: string, position: string, specialization: string, type: string}>
     */
    private const LECTURERS = [
        ['staff' => 'LEC-0001', 'first' => 'Dara', 'last' => 'Lim', 'title' => 'Dr.', 'email' => 'dara.lim@educore.kh', 'department' => 'CSE', 'position' => 'Head of Department', 'specialization' => 'Distributed systems', 'type' => 'full_time'],
        ['staff' => 'LEC-0002', 'first' => 'Sophea', 'last' => 'Chan', 'title' => 'Dr.', 'email' => 'sophea.chan@educore.kh', 'department' => 'CSE', 'position' => 'Senior Lecturer', 'specialization' => 'Databases', 'type' => 'full_time'],
        ['staff' => 'LEC-0003', 'first' => 'Vibol', 'last' => 'Keo', 'title' => 'Mr.', 'email' => 'vibol.keo@educore.kh', 'department' => 'CSE', 'position' => 'Lecturer', 'specialization' => 'Software engineering', 'type' => 'part_time'],
        ['staff' => 'LEC-0004', 'first' => 'Bopha', 'last' => 'Nou', 'title' => 'Dr.', 'email' => 'bopha.nou@educore.kh', 'department' => 'EEE', 'position' => 'Head of Department', 'specialization' => 'Power electronics', 'type' => 'full_time'],
        ['staff' => 'LEC-0005', 'first' => 'Arun', 'last' => 'Sam', 'title' => 'Dr.', 'email' => 'arun.sam@educore.kh', 'department' => 'MTH', 'position' => 'Senior Lecturer', 'specialization' => 'Discrete mathematics', 'type' => 'full_time'],
        ['staff' => 'LEC-0006', 'first' => 'Vichea', 'last' => 'Sim', 'title' => 'Dr.', 'email' => 'vichea.sim@educore.kh', 'department' => 'PHY', 'position' => 'Lecturer', 'specialization' => 'Applied physics', 'type' => 'contract'],
    ];

    public function run(): void
    {
        $roleId = RoleModel::query()->where('slug', Role::Lecturer->value)->value('id');

        if ($roleId === null) {
            return;
        }

        foreach (self::LECTURERS as $definition) {
            $department = Department::query()->where('code', $definition['department'])->first();

            if ($department === null) {
                continue;
            }

            $user = User::query()->updateOrCreate(
                ['email' => $definition['email']],
                [
                    'name' => $definition['first'].' '.$definition['last'],
                    'password' => 'lecturer@123',
                    'role_id' => $roleId,
                    'email_verified_at' => now(),
                    'is_active' => true,
                ],
            );

            Lecturer::query()->updateOrCreate(
                ['staff_number' => $definition['staff']],
                [
                    'user_id' => $user->id,
                    'first_name' => $definition['first'],
                    'last_name' => $definition['last'],
                    'title' => $definition['title'],
                    'department_id' => $department->id,
                    'position' => $definition['position'],
                    'specialization' => $definition['specialization'],
                    'employment_type' => $definition['type'],
                    'is_active' => true,
                ],
            );
        }
    }
}
