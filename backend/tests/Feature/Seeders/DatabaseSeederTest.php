<?php

namespace Tests\Feature\Seeders;

use App\Models\Course;
use App\Models\Department;
use App\Models\Student;
use App\Models\University;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * A fresh installation seeds five accounts (one per role) and system
 * configuration only — no demo records (`docs/database/seed-strategy.md`).
 */
class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_one_account_per_role_and_no_demo_records(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class); // idempotent

        $accounts = User::query()->with('role')->orderBy('id')->get();
        $this->assertSame(
            ['admin@educore.kh' => 'super-admin', 'university@educore.kh' => 'university-admin', 'department@educore.kh' => 'department-admin', 'lecturer@educore.kh' => 'lecturer', 'student@educore.kh' => 'student'],
            $accounts->mapWithKeys(fn (User $user) => [$user->email => $user->role->slug])->all(),
        );
        foreach ($accounts as $user) {
            $this->assertTrue(Hash::check(strtok($user->email, '@').'@123', $user->password));
            $this->assertTrue($user->is_active);
        }

        $this->assertSame(0, University::query()->count());
        $this->assertSame(0, Department::query()->count());
        $this->assertSame(0, Course::query()->count());
        $this->assertSame(0, Student::query()->count());
    }

    public function test_every_seeded_account_can_sign_in_and_open_its_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (User::query()->get() as $user) {
            $this->post('/login', ['email' => $user->email, 'password' => strtok($user->email, '@').'@123'])->assertRedirect();
            $this->assertAuthenticatedAs($user);
            $this->followingRedirects()->get('/')->assertOk();
            $this->post('/logout');
        }
    }
}
