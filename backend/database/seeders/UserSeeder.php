<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Role as RoleModel;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roles = RoleModel::pluck('id', 'slug');

        User::query()->updateOrCreate(
            ['email' => 'admin@educore.kh'],
            [
                'name' => 'Super Admin',
                'password' => 'admin@123',
                'role_id' => $roles[Role::SuperAdmin->value],
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );
    }
}
