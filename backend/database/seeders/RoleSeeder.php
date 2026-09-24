<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Role as RoleModel;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [Role::SuperAdmin->value, 'Super Admin', 'Full platform and user/role management.'],
            [Role::UniversityAdmin->value, 'University Admin', 'University-wide administration.'],
            [Role::FacultyAdmin->value, 'Faculty / Department Admin', 'Administration within an assigned faculty or department.'],
            [Role::Lecturer->value, 'Lecturer', 'Teaching and grading their assigned courses.'],
            [Role::Student->value, 'Student', 'Own profile, registration, grades and documents.'],
        ];

        foreach ($roles as [$slug, $name, $description]) {
            RoleModel::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'description' => $description, 'is_system' => true],
            );
        }
    }
}
