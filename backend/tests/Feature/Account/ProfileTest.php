<?php

namespace Tests\Feature\Account;

use App\Models\Department;
use App\Models\Lecturer;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_profile_page_or_api(): void
    {
        $this->get('/account/profile')->assertRedirect('/login');
        $this->put('/account/profile', ['name' => 'New Name'])->assertRedirect('/login');
        $this->getJson('/api/profile')->assertUnauthorized();
        $this->putJson('/api/profile', ['name' => 'New Name'])->assertUnauthorized();
    }

    public function test_profile_redirects_permanently_to_account_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/profile')->assertRedirect('/account/profile');
    }

    public function test_user_can_view_profile_screen(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'phone' => '+85512345678',
        ]);

        $this->actingAs($user)
            ->get('/account/profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Profile')
                ->where('profile.id', $user->id)
                ->where('profile.name', 'Test User')
                ->where('profile.phone', '+85512345678')
            );
    }

    public function test_student_sees_academic_summary_in_profile(): void
    {
        $department = Department::factory()->create();
        $program = Program::factory()->create(['department_id' => $department->id]);
        $student = Student::factory()->inProgram($program)->create([
            'address' => 'Phnom Penh St 2004',
            'emergency_contact_name' => 'Jane Doe',
            'emergency_contact_phone' => '+85598765432',
        ]);

        $this->actingAs($student->user)
            ->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $student->user->id)
            ->assertJsonPath('data.student.student_number', $student->student_number)
            ->assertJsonPath('data.student.address', 'Phnom Penh St 2004')
            ->assertJsonPath('data.student.emergency_contact_name', 'Jane Doe')
            ->assertJsonPath('data.student.emergency_contact_phone', '+85598765432')
            ->assertJsonPath('data.student.program.id', $program->id);
    }

    public function test_lecturer_sees_teaching_summary_in_profile(): void
    {
        $department = Department::factory()->create();
        $lecturer = Lecturer::factory()->create([
            'department_id' => $department->id,
            'specialization' => 'Artificial Intelligence',
        ]);

        $this->actingAs($lecturer->user)
            ->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $lecturer->user->id)
            ->assertJsonPath('data.lecturer.staff_number', $lecturer->staff_number)
            ->assertJsonPath('data.lecturer.specialization', 'Artificial Intelligence')
            ->assertJsonPath('data.department.id', $department->id);
    }

    public function test_user_can_update_profile_over_web(): void
    {
        $user = User::factory()->create([
            'name' => 'Initial Name',
            'phone' => '+85511111111',
        ]);

        $this->actingAs($user)
            ->put('/account/profile', [
                'name' => 'Updated Name',
                'phone' => '+85522222222',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Profile updated successfully.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'phone' => '+85522222222',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'profile.updated',
            'actor_id' => $user->id,
            'auditable_type' => $user->getMorphClass(),
            'auditable_id' => $user->id,
        ]);
    }

    public function test_student_can_update_contact_details(): void
    {
        $student = Student::factory()->create([
            'address' => 'Old Address',
            'emergency_contact_name' => 'Old Contact',
            'emergency_contact_phone' => '+85510000000',
        ]);

        $this->actingAs($student->user)
            ->putJson('/api/profile', [
                'name' => 'Student Updated',
                'phone' => '+85512999999',
                'address' => 'New Street 123',
                'emergency_contact_name' => 'New Contact Name',
                'emergency_contact_phone' => '+85599887766',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Profile updated successfully.')
            ->assertJsonPath('data.student.address', 'New Street 123')
            ->assertJsonPath('data.student.emergency_contact_name', 'New Contact Name');

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'address' => 'New Street 123',
            'emergency_contact_name' => 'New Contact Name',
            'emergency_contact_phone' => '+85599887766',
        ]);
    }

    public function test_lecturer_can_update_specialization(): void
    {
        $lecturer = Lecturer::factory()->create([
            'specialization' => 'Old Topic',
        ]);

        $this->actingAs($lecturer->user)
            ->putJson('/api/profile', [
                'name' => 'Lecturer Updated',
                'phone' => '+85512888888',
                'specialization' => 'Cybersecurity & Cloud',
            ])
            ->assertOk()
            ->assertJsonPath('data.lecturer.specialization', 'Cybersecurity & Cloud');

        $this->assertDatabaseHas('lecturers', [
            'id' => $lecturer->id,
            'specialization' => 'Cybersecurity & Cloud',
        ]);
    }

    public function test_profile_update_validates_inputs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/profile', [
                'name' => '',
                'phone' => str_repeat('a', 55),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'phone']);
    }

    public function test_user_can_set_avatar_from_url(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/profile', [
                'name' => 'Avatar URL User',
                'avatar_url' => 'https://images.example.com/profiles/avatar.png',
            ])
            ->assertOk()
            ->assertJsonPath('data.avatar_key', 'https://images.example.com/profiles/avatar.png')
            ->assertJsonPath('data.avatar_url', 'https://images.example.com/profiles/avatar.png');

        $this->assertSame('https://images.example.com/profiles/avatar.png', $user->refresh()->avatar_key);
        $this->assertSame('https://images.example.com/profiles/avatar.png', $user->avatarUrl());
    }

    public function test_user_can_upload_avatar_from_device(): void
    {
        Storage::fake('s3');
        $user = User::factory()->create();

        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $file = UploadedFile::fake()->createWithContent('my-photo.png', $pngBytes);

        $this->actingAs($user)
            ->post('/account/profile', [
                'name' => 'Device Upload User',
                'avatar' => $file,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertNotNull($user->avatar_key);
        $this->assertStringStartsWith("avatars/{$user->id}/", $user->avatar_key);
        Storage::disk('s3')->assertExists($user->avatar_key);

        // Avatar endpoint serves the file
        $response = $this->get("/users/{$user->id}/avatar");
        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_stored_avatar_extension_follows_content_and_replaced_file_is_deleted(): void
    {
        Storage::fake('s3');
        $user = User::factory()->create(['avatar_key' => 'avatars/old.png']);
        Storage::disk('s3')->put('avatars/old.png', 'old image');

        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        // A PNG whose client-side name claims HTML. Real uploads are validated on
        // their content only, so the stored key must not keep the client extension.
        $file = UploadedFile::fake()->createWithContent('photo.html', $pngBytes)->mimeType('image/png');

        $this->actingAs($user)
            ->post('/api/profile', ['name' => $user->name, 'avatar' => $file], ['Accept' => 'application/json'])
            ->assertOk();

        $key = $user->refresh()->avatar_key;
        $this->assertStringEndsWith('.png', $key);
        Storage::disk('s3')->assertExists($key);
        Storage::disk('s3')->assertMissing('avatars/old.png');

        // A new upload gets a new URL, so the day-long browser cache cannot show the old image.
        $this->assertNotSame(
            $user->avatarUrl(),
            (clone $user)->forceFill(['avatar_key' => 'avatars/old.png'])->avatarUrl(),
        );
    }

    public function test_external_avatar_is_never_redirected_to(): void
    {
        $user = User::factory()->create(['avatar_key' => 'https://images.example.com/a.png']);

        // The browser loads the URL from avatar_url directly; the stream routes
        // must not bounce visitors to an address a user typed in (open redirect).
        $this->get("/users/{$user->id}/avatar")->assertNotFound()->assertHeaderMissing('Location');
        $this->getJson("/api/users/{$user->id}/avatar")->assertNotFound()->assertHeaderMissing('Location');
    }

    public function test_user_can_remove_avatar(): void
    {
        Storage::fake('s3');
        $user = User::factory()->create(['avatar_key' => 'avatars/1/test.png']);
        Storage::disk('s3')->put('avatars/1/test.png', 'fake image content');

        $this->actingAs($user)
            ->putJson('/api/profile', [
                'name' => 'No Avatar User',
                'remove_avatar' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.avatar_key', null)
            ->assertJsonPath('data.avatar_url', null);

        $this->assertNull($user->refresh()->avatar_key);
        Storage::disk('s3')->assertMissing('avatars/1/test.png');
    }

    public function test_avatar_validation_rejects_invalid_file_and_url(): void
    {
        $user = User::factory()->create();

        $fakePdf = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->actingAs($user)
            ->postJson('/api/profile', [
                'name' => 'Bad Avatar User',
                'avatar' => $fakePdf,
                'avatar_url' => 'not-a-valid-url',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['avatar', 'avatar_url']);
    }
}
