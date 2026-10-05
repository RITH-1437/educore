<?php

namespace Tests\Feature\Assignments;

use App\Exceptions\BusinessRuleException;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\CourseOffering;
use App\Models\Lecturer;
use App\Models\Section;
use App\Models\Semester;
use App\Models\StoredFile;
use App\Models\Student;
use App\Models\User;
use App\Notifications\CourseMaterialAdded;
use App\Services\CourseOfferingService;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Course materials (`docs/44_Course-Materials-Report.md`): the section's
 * lecturers share files and links, its students read and download them and are
 * told, nobody else gets in; files stay private and are removed with the material.
 */
class CourseMaterialTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Section $section;

    private Lecturer $lecturer;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-03-04 12:00:00');
        Storage::fake('s3');
        Notification::fake();

        $this->admin = User::factory()->superAdmin()->create();
        $semester = Semester::factory()->create(['status' => 'open', 'start_date' => '2026-02-01', 'end_date' => '2026-06-30']);
        $offering = CourseOffering::factory()->create(['course_id' => Course::factory()->create(['code' => 'CS201'])->id, 'semester_id' => $semester->id, 'status' => 'open']);
        $this->section = Section::factory()->create(['course_offering_id' => $offering->id, 'status' => 'open', 'capacity' => 30]);
        $this->lecturer = Lecturer::factory()->create();
        $this->section->lecturers()->attach($this->lecturer->id, ['role' => 'primary']);
        $this->student = Student::factory()->create();
        app(EnrollmentService::class)->enroll($this->student, $this->section);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_lecturer_shares_a_file_students_download_it_and_are_told(): void
    {
        $id = $this->actingAs($this->lecturer->user)->post("/api/sections/{$this->section->id}/materials", [
            'title' => 'Week 3 slides', 'description' => 'Read before Thursday.', 'kind' => 'file',
            'file' => UploadedFile::fake()->create('week3.pdf', 300, 'application/pdf'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Week 3 slides')
            ->assertJsonPath('data.kind', 'file')
            ->assertJsonPath('data.file.name', 'week3.pdf')
            ->assertJsonPath('data.url', null)
            ->assertJsonMissingPath('data.file.storage_key')
            ->json('data.id');

        $stored = StoredFile::query()->where('fileable_type', CourseMaterial::class)->where('fileable_id', $id)->firstOrFail();
        $this->assertStringStartsWith("materials/{$this->section->offering->course_id}/{$this->section->id}/", $stored->storage_key);
        $this->assertSame('private', $stored->visibility);
        Storage::disk('s3')->assertExists($stored->storage_key);
        $this->assertSame('course_material.created', AuditLog::query()->latest('id')->value('action'));

        // The enrolled student is told (inbox + channels) and can read and download.
        Notification::assertSentTo($this->student->user, CourseMaterialAdded::class, function (CourseMaterialAdded $notice) use ($id) {
            $inbox = $notice->toInbox($this->student->user);

            return $notice->material->id === $id && $inbox['kind'] === 'material' && $inbox['url'] === '/my-materials' && str_contains($inbox['body'], 'CS201');
        });
        $this->actingAs($this->student->user)->getJson("/api/sections/{$this->section->id}/materials")->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($this->student->user)->get("/api/materials/{$id}/file")->assertOk()->assertDownload('week3.pdf');
    }

    public function test_link_materials_and_editing(): void
    {
        $id = $this->actingAs($this->lecturer->user)->postJson("/api/sections/{$this->section->id}/materials", ['title' => 'Course site', 'kind' => 'link', 'url' => 'https://example.edu/cs201'])
            ->assertCreated()->assertJsonPath('data.url', 'https://example.edu/cs201')->assertJsonPath('data.file', null)->json('data.id');

        $this->actingAs($this->lecturer->user)->putJson("/api/materials/{$id}", ['title' => 'Course website', 'url' => 'https://example.edu/cs201/2026'])
            ->assertOk()->assertJsonPath('data.title', 'Course website')->assertJsonPath('data.url', 'https://example.edu/cs201/2026');
        $this->assertSame('course_material.updated', AuditLog::query()->latest('id')->value('action'));
        // A link has no file to download.
        $this->actingAs($this->student->user)->getJson("/api/materials/{$id}/file")->assertNotFound();
    }

    public function test_validation(): void
    {
        $url = "/api/sections/{$this->section->id}/materials";
        $as = $this->actingAs($this->lecturer->user);

        $as->postJson($url, [])->assertJsonValidationErrors(['title', 'kind']);
        $as->postJson($url, ['title' => 'x', 'kind' => 'link'])->assertJsonValidationErrors('url');
        $as->postJson($url, ['title' => 'x', 'kind' => 'link', 'url' => 'javascript:alert(1)'])->assertJsonValidationErrors('url');
        $as->postJson($url, ['title' => 'x', 'kind' => 'file'])->assertJsonValidationErrors('file');
        $as->post($url, ['title' => 'x', 'kind' => 'file', 'file' => UploadedFile::fake()->create('run.exe', 10)], ['Accept' => 'application/json'])->assertJsonValidationErrors('file');
        $as->post($url, ['title' => 'x', 'kind' => 'file', 'file' => UploadedFile::fake()->create('huge.pdf', 30000, 'application/pdf')], ['Accept' => 'application/json'])->assertJsonValidationErrors('file');
        $as->post($url, ['title' => 'x', 'kind' => 'link', 'url' => 'https://example.edu', 'file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])->assertJsonValidationErrors('file');

        $file = $this->material('file');
        $this->actingAs($this->lecturer->user)->putJson("/api/materials/{$file->id}", ['title' => 'x', 'url' => 'https://example.edu'])->assertJsonValidationErrors('url');
        $this->assertSame(1, CourseMaterial::query()->count());
    }

    public function test_only_the_sections_people_get_in(): void
    {
        // Guests first: `actingAs` lasts for the rest of the test.
        $this->getJson("/api/sections/{$this->section->id}/materials")->assertUnauthorized();

        $material = $this->material('file');
        $outsiderStudent = Student::factory()->create();
        $otherLecturer = Lecturer::factory()->create();
        $departmentAdmin = $this->departmentAdminFor($this->section->offering->course->department_id);
        $otherDepartmentAdmin = $this->departmentAdminFor(null);
        $list = "/api/sections/{$this->section->id}/materials";

        // Readers.
        foreach ([$this->admin, $this->lecturer->user, $this->student->user, $departmentAdmin] as $reader) {
            $this->actingAs($reader)->getJson($list)->assertOk()->assertJsonCount(1, 'data');
            $this->actingAs($reader)->get("/api/materials/{$material->id}/file")->assertOk();
        }
        // Everyone else.
        foreach ([$outsiderStudent->user, $otherLecturer->user, $otherDepartmentAdmin] as $outsider) {
            $this->actingAs($outsider)->getJson($list)->assertForbidden();
            $this->actingAs($outsider)->getJson("/api/materials/{$material->id}/file")->assertForbidden();
        }
        // Only lecturers of the section and managers write.
        foreach ([$this->student->user, $departmentAdmin, $otherLecturer->user] as $reader) {
            $this->actingAs($reader)->postJson($list, ['title' => 'x', 'kind' => 'link', 'url' => 'https://example.edu'])->assertForbidden();
            $this->actingAs($reader)->deleteJson("/api/materials/{$material->id}")->assertForbidden();
        }
        $this->actingAs($this->admin)->postJson($list, ['title' => 'Syllabus', 'kind' => 'link', 'url' => 'https://example.edu/syllabus'])->assertCreated();
    }

    public function test_removing_a_material_deletes_its_file_and_a_section_keeps_its_materials(): void
    {
        $material = $this->material('file');
        $key = $material->file->storage_key;

        try {
            app(CourseOfferingService::class)->deleteSection($this->section);
            $this->fail('A section with materials must not be deleted.');
        } catch (BusinessRuleException $e) {
            $this->assertStringContainsString('course materials', $e->getMessage());
        }

        $this->actingAs($this->lecturer->user)->deleteJson("/api/materials/{$material->id}")->assertNoContent();
        $this->assertNull(CourseMaterial::query()->find($material->id));
        $this->assertSame(0, StoredFile::query()->where('fileable_type', CourseMaterial::class)->count());
        Storage::disk('s3')->assertMissing($key);
        $this->assertSame('course_material.deleted', AuditLog::query()->latest('id')->value('action'));
    }

    public function test_web_pages(): void
    {
        $this->material('file');
        $this->material('link');

        $this->actingAs($this->lecturer->user)->get("/coursework/sections/{$this->section->id}")->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Coursework/Section')->has('materials', 2)->where('canShare', true)->has('materialTypes'));
        $this->actingAs($this->student->user)->get("/coursework/sections/{$this->section->id}")->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('materials', 2)->where('canShare', false));

        $this->actingAs($this->student->user)->get('/my-materials')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Materials/Mine')->has('materials', 2)->where('materials.0.course.code', 'CS201'));
        // Another student's materials page lists nothing of this section.
        $this->actingAs(Student::factory()->create()->user)->get('/my-materials')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('materials', 0));
        $this->actingAs($this->lecturer->user)->get('/my-materials')->assertForbidden();

        $this->actingAs($this->lecturer->user)->from("/coursework/sections/{$this->section->id}")
            ->post("/coursework/sections/{$this->section->id}/materials", ['title' => 'Reading list', 'kind' => 'link', 'url' => 'https://example.edu/reading'])
            ->assertRedirect("/coursework/sections/{$this->section->id}")->assertSessionHas('success');
        $this->assertSame(3, CourseMaterial::query()->count());
    }

    private function material(string $kind): CourseMaterial
    {
        $id = $this->actingAs($this->lecturer->user)->post("/api/sections/{$this->section->id}/materials", $kind === 'file'
            ? ['title' => 'Handout', 'kind' => 'file', 'file' => UploadedFile::fake()->create('handout.pdf', 50, 'application/pdf')]
            : ['title' => 'Course site', 'kind' => 'link', 'url' => 'https://example.edu/site'], ['Accept' => 'application/json'])
            ->assertCreated()
            ->json('data.id');

        return CourseMaterial::query()->with('file')->findOrFail($id);
    }
}
