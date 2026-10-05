<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\CourseMaterial;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Notifications\CourseMaterialAdded;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Course materials (`docs/44_Course-Materials-Report.md`).
 *
 * - A material is a file (private object under `materials/{course}/{section}/`)
 *   or a link; both carry a title and an optional note.
 * - Students currently enrolled in the section are told when one is added.
 * - Changes are audited; removing a material deletes its object after commit.
 */
class CourseMaterialService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /** @return Collection<int, CourseMaterial> */
    public function listFor(Section $section): Collection
    {
        return $section->materials()->with(['file', 'creator:id,name'])->get();
    }

    /**
     * Materials of the student's current sections (open enrollments), newest first.
     *
     * @return Collection<int, CourseMaterial>
     */
    public function forStudent(Student $student): Collection
    {
        return CourseMaterial::query()
            ->whereIn('section_id', Enrollment::query()->select('section_id')->where('student_id', $student->getKey())->whereIn('status', Enrollment::OPEN_STATUSES))
            ->with(['file', 'creator:id,name', 'section:id,code,course_offering_id', 'section.offering:id,course_id', 'section.offering.course:id,code,name'])
            ->latest('created_at')->latest('id')
            ->get();
    }

    /**
     * @param  array{title: string, description?: string|null, kind: string, url?: string|null}  $data
     */
    public function create(Section $section, array $data, ?UploadedFile $file, User $by): CourseMaterial
    {
        $isFile = $data['kind'] === CourseMaterial::KIND_FILE;
        if ($isFile && $file === null) {
            throw new BusinessRuleException('Choose a file to share.');
        }

        $key = null;
        if ($isFile) {
            $section->loadMissing('offering');
            $key = "materials/{$section->offering->course_id}/{$section->getKey()}/".Str::uuid().'.'.strtolower($file->getClientOriginalExtension() ?: $file->extension());
            // Upload first; if the database work fails the orphan object is removed.
            Storage::disk($this->disk())->putFileAs(dirname($key), $file, basename($key), ['visibility' => 'private']);
        }

        try {
            $material = DB::transaction(function () use ($section, $data, $file, $key, $isFile, $by) {
                $material = $section->materials()->create([
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'kind' => $data['kind'],
                    'url' => $isFile ? null : $data['url'],
                    'created_by' => $by->getKey(),
                ]);

                if ($isFile) {
                    $material->file()->create([
                        'uploader_id' => $by->getKey(),
                        'file_name' => basename($key),
                        'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                        'storage_key' => $key,
                        'bucket' => (string) (config("filesystems.disks.{$this->disk()}.bucket") ?: 'educore'),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'visibility' => 'private',
                        'checksum' => hash_file('sha256', $file->getRealPath()),
                    ]);
                }

                $this->audit->record('course_material.created', $material, after: ['section_id' => $section->getKey(), 'title' => $material->title, 'kind' => $material->kind], actor: $by);

                return $material;
            });
        } catch (Throwable $e) {
            if ($key !== null) {
                Storage::disk($this->disk())->delete($key);
            }

            throw $e;
        }

        // Queued after commit; only students who are in the section right now.
        Notification::send($this->recipients($section), new CourseMaterialAdded($material));

        return $material->load(['file', 'creator:id,name']);
    }

    /**
     * Title, note and (for a link) URL; a file is replaced by removing the material and sharing a new one.
     *
     * @param  array{title: string, description?: string|null, url?: string|null}  $data
     */
    public function update(CourseMaterial $material, array $data, User $by): CourseMaterial
    {
        return DB::transaction(function () use ($material, $data, $by) {
            $before = $material->only(['title', 'description', 'url']);
            $material->fill([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                ...($material->kind === CourseMaterial::KIND_LINK && isset($data['url']) ? ['url' => $data['url']] : []),
            ])->save();

            $changed = array_keys($material->getChanges());
            if ($changed !== []) {
                $this->audit->record('course_material.updated', $material, array_intersect_key($before, array_flip($changed)), $material->only(array_intersect($changed, ['title', 'description', 'url'])), actor: $by);
            }

            return $material->load(['file', 'creator:id,name']);
        });
    }

    public function delete(CourseMaterial $material, User $by): void
    {
        DB::transaction(function () use ($material, $by) {
            $key = $material->file?->storage_key;
            $material->file()->delete();
            $this->audit->record('course_material.deleted', $material, ['section_id' => $material->section_id, 'title' => $material->title, 'kind' => $material->kind], actor: $by);
            $material->delete();

            if ($key !== null) {
                DB::afterCommit(fn () => Storage::disk($this->disk())->delete($key));
            }
        });
    }

    public function download(CourseMaterial $material): StreamedResponse
    {
        $file = $material->file;
        abort_if($file === null, 404, 'This material has no file.');

        return Storage::disk($this->disk())->download($file->storage_key, $file->original_name);
    }

    /** @return \Illuminate\Support\Collection<int, User> active users of students with an open enrollment in the section */
    private function recipients(Section $section): \Illuminate\Support\Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereIn('id', Student::query()->select('user_id')->whereIn('id', $section->enrollments()->select('student_id')->whereIn('status', Enrollment::OPEN_STATUSES)))
            ->get();
    }

    private function disk(): string
    {
        return (string) config('academics.uploads_disk', 's3');
    }
}
