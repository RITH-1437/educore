<?php

namespace App\Services;

use App\Enums\Role;
use App\Exceptions\BusinessRuleException;
use App\Jobs\SendAnnouncementNotifications;
use App\Models\Announcement;
use App\Models\Course;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Announcements (module 9.19, `skills/announcements`).
 *
 * Audiences: `all`, `students`, `lecturers`, `staff` (administrators), or one
 * `department` / `program` / `section` / `course`. Membership is
 * resolved from authoritative relations when the feed is read:
 *
 * | Audience   | Students                                   | Lecturers                         |
 * |------------|--------------------------------------------|-----------------------------------|
 * | department | current program's department               | own department                    |
 * | program    | current program                            | —                                 |
 * | section    | open (pending / confirmed) enrollment      | assigned to the section           |
 * | course     | open enrollment in any section of it       | teaches a section of it           |
 *
 * Super Admin / University Admin target any audience; an active lecturer only
 * sections they teach or courses they teach a section of. A user joining an
 * audience later sees earlier announcements (the feed is not retroactive-only).
 */
class AnnouncementService
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Published announcements the user belongs to, newest first.
     *
     * @return Builder<Announcement>
     */
    public function feedFor(User $user): Builder
    {
        $m = $this->memberships($user);

        return Announcement::query()
            ->where('publish_state', Announcement::STATE_PUBLISHED)
            ->where(function (Builder $q) use ($m) {
                $q->where('audience_type', 'all')
                    ->when($m['student'], fn ($w) => $w->orWhere('audience_type', 'students'))
                    ->when($m['lecturer'], fn ($w) => $w->orWhere('audience_type', 'lecturers'))
                    ->when($m['staff'], fn ($w) => $w->orWhere('audience_type', 'staff'));

                foreach (['department', 'program', 'section', 'course'] as $type) {
                    if ($m[$type] !== []) {
                        $q->orWhere(fn ($w) => $w->where('audience_type', $type)->whereIn('audience_id', $m[$type]));
                    }
                }
            })
            ->with(['author:id,name', 'attachments'])
            ->latest('published_at')
            ->latest('id');
    }

    /**
     * Active users an announcement reaches — the inverse of `feedFor`, used to
     * deliver notifications at publish time (later joiners see it in their
     * feed but are not notified retroactively).
     *
     * @return Builder<User>
     */
    public function recipients(Announcement $announcement): Builder
    {
        $id = $announcement->audience_id;
        // Sub-selects of user ids for students / active lecturers in a set.
        $studentUsers = fn ($studentIds) => DB::table('students')->whereIn('id', $studentIds)->select('user_id');
        $lecturerUsers = fn ($lecturerIds) => DB::table('lecturers')->where('is_active', true)->whereIn('id', $lecturerIds)->select('user_id');
        $inPrograms = fn ($programs) => DB::table('student_programs')->where('status', 'active')->whereIn('program_id', $programs)->select('student_id');
        $openIn = fn ($sections) => DB::table('enrollments')->whereIn('status', Enrollment::OPEN_STATUSES)->whereNull('deleted_at')->whereIn('section_id', $sections)->select('student_id');
        $teaching = fn ($sections) => DB::table('section_lecturers')->whereIn('section_id', $sections)->select('lecturer_id');
        $courseSections = fn () => DB::table('sections')->join('course_offerings', 'course_offerings.id', '=', 'sections.course_offering_id')->where('course_offerings.course_id', $id)->select('sections.id');
        $roles = fn (array $slugs) => fn (Builder $q) => $q->whereHas('role', fn ($r) => $r->whereIn('slug', $slugs));
        $members = fn ($students, $lecturers = null) => fn (Builder $q) => $q->whereIn('id', $studentUsers($students))
            ->when($lecturers !== null, fn ($w) => $w->orWhereIn('id', $lecturerUsers($lecturers)));

        $scope = match ($announcement->audience_type) {
            'all' => fn (Builder $q) => $q,
            'students' => $roles([Role::Student->value]),
            'lecturers' => $roles([Role::Lecturer->value]),
            'staff' => $roles([Role::SuperAdmin->value, Role::UniversityAdmin->value, Role::DepartmentAdmin->value]),
            'program' => $members($inPrograms([$id])),
            'department' => $members($inPrograms(DB::table('programs')->where('department_id', $id)->select('id')), DB::table('lecturers')->where('department_id', $id)->select('id')),
            'section' => $members($openIn([$id]), $teaching([$id])),
            'course' => $members($openIn($courseSections()), $teaching($courseSections())),
            default => fn (Builder $q) => $q->whereRaw('false'),
        };

        return User::query()->where('is_active', true)->where(fn (Builder $q) => $scope($q));
    }

    /**
     * Announcements a user manages: everything for managers, own for lecturers.
     *
     * @return Builder<Announcement>
     */
    public function managedBy(User $user, ?string $state): Builder
    {
        return Announcement::query()
            ->when(! $this->manages($user), fn ($q) => $q->where('author_id', $user->getKey()))
            ->when($state, fn ($q) => $q->where('publish_state', $state))
            ->with(['author:id,name', 'attachments'])
            ->orderByRaw("case publish_state when 'draft' then 0 when 'published' then 1 else 2 end")
            ->latest('updated_at')
            ->latest('id');
    }

    /**
     * @param  array<string, mixed>  $data  title, body, announcement_type, audience_type, audience_id
     */
    public function create(User $author, array $data, bool $publish = false): Announcement
    {
        return DB::transaction(function () use ($author, $data, $publish) {
            $audience = $this->assertAudience($author, $data['audience_type'], $data['audience_id'] ?? null);

            $announcement = Announcement::query()->create([
                'author_id' => $author->getKey(),
                'title' => $data['title'],
                'body' => $data['body'],
                'announcement_type' => $data['announcement_type'] ?? 'general',
                ...$audience,
                'publish_state' => $publish ? Announcement::STATE_PUBLISHED : Announcement::STATE_DRAFT,
                'published_at' => $publish ? now() : null,
            ])->refresh();

            if ($publish) {
                $this->audit->record('announcement.published', $announcement, after: $announcement->only(['title', 'audience_type', 'audience_id']));
                SendAnnouncementNotifications::dispatch($announcement);
            }

            return $announcement;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Announcement $announcement, User $by, array $data): Announcement
    {
        return DB::transaction(function () use ($announcement, $by, $data) {
            $this->assertDraft($announcement, 'edited');
            $audience = $this->assertAudience($by, $data['audience_type'], $data['audience_id'] ?? null);

            $announcement->update([
                'title' => $data['title'],
                'body' => $data['body'],
                'announcement_type' => $data['announcement_type'] ?? $announcement->announcement_type,
                ...$audience,
            ]);

            return $announcement->refresh();
        });
    }

    public function publish(Announcement $announcement, User $by): Announcement
    {
        return DB::transaction(function () use ($announcement, $by) {
            $this->assertDraft($announcement, 'published');
            // The publisher must still be allowed to reach the audience.
            $this->assertAudience($by, $announcement->audience_type, $announcement->audience_id);
            $announcement->update(['publish_state' => Announcement::STATE_PUBLISHED, 'published_at' => now()]);
            $this->audit->record('announcement.published', $announcement, after: $announcement->only(['title', 'audience_type', 'audience_id']));
            // Delivery to the audience is queued and runs after commit (9.20 / 9.21).
            SendAnnouncementNotifications::dispatch($announcement);

            return $announcement->refresh();
        });
    }

    public function archive(Announcement $announcement): Announcement
    {
        if ($announcement->publish_state !== Announcement::STATE_PUBLISHED) {
            throw new BusinessRuleException('Only a published announcement can be archived.');
        }

        $announcement->update(['publish_state' => Announcement::STATE_ARCHIVED]);
        $this->audit->record('announcement.archived', $announcement, ['publish_state' => Announcement::STATE_PUBLISHED], ['publish_state' => Announcement::STATE_ARCHIVED]);

        return $announcement->refresh();
    }

    public function delete(Announcement $announcement): void
    {
        $this->assertDraft($announcement, 'deleted');

        foreach ($announcement->attachments as $attachment) {
            $this->deleteAttachment($announcement, $attachment);
        }

        $announcement->delete();
    }

    public function disk(): string
    {
        return (string) config('academics.uploads_disk', 's3');
    }

    /**
     * Attach an uploaded file to an announcement.
     */
    public function attachFile(Announcement $announcement, UploadedFile $file, User $uploader): StoredFile
    {
        $this->assertDraft($announcement, 'modified');

        $disk = $this->disk();
        $uuid = (string) Str::uuid();
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $storageKey = "announcements/{$announcement->id}/{$uuid}.{$ext}";

        Storage::disk($disk)->put($storageKey, (string) file_get_contents($file->getRealPath()));

        return StoredFile::query()->create([
            'fileable_type' => Announcement::class,
            'fileable_id' => $announcement->id,
            'uploader_id' => $uploader->id,
            'file_name' => "{$uuid}.{$ext}",
            'original_name' => $file->getClientOriginalName(),
            'storage_key' => $storageKey,
            'bucket' => (string) config("filesystems.disks.{$disk}.bucket", 'educore'),
            'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
            'size' => $file->getSize(),
            'visibility' => 'public',
            'checksum' => hash_file('sha256', $file->getRealPath()),
        ]);
    }

    /**
     * Delete an attachment.
     */
    public function deleteAttachment(Announcement $announcement, StoredFile $attachment): void
    {
        $this->assertDraft($announcement, 'modified');

        if ($attachment->fileable_type !== Announcement::class || (int) $attachment->fileable_id !== (int) $announcement->id) {
            throw new BusinessRuleException('Attachment does not belong to this announcement.');
        }

        Storage::disk($this->disk())->delete($attachment->storage_key);
        $attachment->delete();
    }

    /**
     * Download an attachment.
     */
    public function downloadAttachment(Announcement $announcement, StoredFile $attachment): StreamedResponse
    {
        if ($attachment->fileable_type !== Announcement::class || (int) $attachment->fileable_id !== (int) $announcement->id) {
            abort(404, 'Attachment not found.');
        }

        return Storage::disk($this->disk())->download($attachment->storage_key, $attachment->original_name);
    }

    /**
     * Audiences the user may target, for the compose form.
     *
     * @return array<string, list<array{id: int, label: string}>>
     */
    public function targetOptions(User $user): array
    {
        $label = fn (string $type, Model $m) => match ($type) {
            'section' => $m->offering->course->code.' · Section '.$m->code.' ('.$m->offering->semester->name.')',
            'course' => $m->code.' · '.$m->name,
            default => trim(($m->code ?? '').' · '.$m->name, ' ·'),
        };

        if ($this->manages($user)) {
            $sections = Section::query()->whereIn('status', ['open', 'active'])->with('offering.course:id,code', 'offering.semester:id,name')->get();

            return [
                'department' => Department::query()->orderBy('name')->get()->map(fn ($m) => ['id' => $m->id, 'label' => $label('department', $m)])->all(),
                'program' => Program::query()->orderBy('name')->get()->map(fn ($m) => ['id' => $m->id, 'label' => $label('program', $m)])->all(),
                'section' => $sections->map(fn ($m) => ['id' => $m->id, 'label' => $label('section', $m)])->sortBy('label')->values()->all(),
                'course' => Course::query()->where('status', Course::STATUS_ACTIVE)->orderBy('code')->get()->map(fn ($m) => ['id' => $m->id, 'label' => $label('course', $m)])->all(),
            ];
        }

        $sections = $user->lecturer?->sections()->with('offering.course:id,code,name', 'offering.semester:id,name')->get() ?? collect();

        return [
            'section' => $sections->map(fn ($m) => ['id' => $m->id, 'label' => $label('section', $m)])->sortBy('label')->values()->all(),
            'course' => $sections->pluck('offering.course')->unique('id')->map(fn ($m) => ['id' => $m->id, 'label' => $label('course', $m)])->sortBy('label')->values()->all(),
        ];
    }

    /** Human label of an announcement's audience. */
    public function audienceLabel(Announcement $announcement): string
    {
        return match ($announcement->audience_type) {
            'all' => 'Everyone',
            'students' => 'All students',
            'lecturers' => 'All lecturers',
            'staff' => 'Administrative staff',
            default => ucfirst($announcement->audience_type).': '.($this->targetName(
                $announcement->relationLoaded('target') ? $announcement->getRelation('target') : $announcement->target(),
                $announcement->audience_type,
            ) ?? 'removed'),
        };
    }

    /**
     * Load the targets of a page of announcements in one query per audience
     * type (no N+1 when labelling a list).
     *
     * @param  iterable<Announcement>  $announcements
     * @return iterable<Announcement>
     */
    public function preloadTargets(iterable $announcements): iterable
    {
        $items = collect($announcements);

        foreach (Announcement::UNIT_AUDIENCES as $type => $class) {
            $ofType = $items->where('audience_type', $type);

            if ($ofType->isEmpty()) {
                continue;
            }

            $targets = $class::query()
                ->when($type === 'section', fn ($q) => $q->with('offering.course:id,code'))
                ->whereKey($ofType->pluck('audience_id')->filter()->unique()->all())
                ->get()
                ->keyBy('id');

            $ofType->each(fn (Announcement $a) => $a->setRelation('target', $targets->get($a->audience_id)));
        }

        return $announcements;
    }

    public function manages(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin->value) || $user->isRole(Role::UniversityAdmin->value);
    }

    // ---------------------------------------------------------------- helpers

    /**
     * @return array{audience_type: string, audience_id: int|null}
     */
    private function assertAudience(User $user, string $type, mixed $id): array
    {
        if (in_array($type, Announcement::GROUP_AUDIENCES, true)) {
            if (! $this->manages($user)) {
                throw ValidationException::withMessages(['audience_type' => 'Lecturers can announce only to sections or courses they teach.']);
            }

            return ['audience_type' => $type, 'audience_id' => null];
        }

        $class = Announcement::UNIT_AUDIENCES[$type] ?? null;

        if ($class === null) {
            throw ValidationException::withMessages(['audience_type' => 'Unknown audience.']);
        }

        if ($id === null || ! $class::query()->whereKey($id)->exists()) {
            throw ValidationException::withMessages(['audience_id' => "Choose the {$type} to announce to."]);
        }

        if (! $this->manages($user)) {
            $lecturer = $user->lecturer;
            $teaches = $lecturer !== null && $lecturer->is_active && match ($type) {
                'section' => $lecturer->sections()->whereKey($id)->exists(),
                'course' => $lecturer->sections()->whereHas('offering', fn ($q) => $q->where('course_id', $id))->exists(),
                default => false,
            };

            if (! $teaches) {
                throw ValidationException::withMessages(['audience_id' => 'Lecturers can announce only to sections or courses they teach.']);
            }
        }

        return ['audience_type' => $type, 'audience_id' => (int) $id];
    }

    private function assertDraft(Announcement $announcement, string $verb): void
    {
        if ($announcement->publish_state !== Announcement::STATE_DRAFT) {
            throw new BusinessRuleException("A {$announcement->publish_state} announcement cannot be {$verb}; archive it and publish a correction instead.");
        }
    }

    private function targetName(?Model $target, string $type): ?string
    {
        if ($target === null) {
            return null;
        }

        return match ($type) {
            'section' => $target->loadMissing('offering.course:id,code')->offering->course->code.' · Section '.$target->code,
            'course' => $target->code.' · '.$target->name,
            default => $target->name,
        };
    }

    /**
     * @return array{student: bool, lecturer: bool, staff: bool, department: list<int>, program: list<int>, section: list<int>, course: list<int>}
     */
    private function memberships(User $user): array
    {
        $m = ['student' => false, 'lecturer' => false, 'staff' => false, 'department' => [], 'program' => [], 'section' => [], 'course' => []];

        if ($user->isRole(Role::Student->value)) {
            $m['student'] = true;
            if (($student = $user->student) !== null) {
                $program = $student->currentProgram()->with('program:id,department_id')->first()?->program;

                if ($program !== null) {
                    $m['program'] = [$program->id];
                    $m['department'] = [$program->department_id];
                }

                $sections = Enrollment::query()->where('student_id', $student->getKey())->whereIn('status', Enrollment::OPEN_STATUSES)->pluck('section_id');
                $m['section'] = $sections->map(fn ($id) => (int) $id)->all();
                $m['course'] = $this->coursesOf($sections->all());
            }
        } elseif ($user->isRole(Role::Lecturer->value)) {
            $m['lecturer'] = true;
            if (($lecturer = $user->lecturer) !== null) {
                $m['department'] = [$lecturer->department_id];
                $sections = $lecturer->sections()->pluck('sections.id')->all();
                $m['section'] = array_map('intval', $sections);
                $m['course'] = $this->coursesOf($sections);
            }
        } elseif ($user->isRole(Role::SuperAdmin->value) || $user->isRole(Role::UniversityAdmin->value) || $user->isRole(Role::DepartmentAdmin->value)) {
            $m['staff'] = true;
        }

        return $m;
    }

    /**
     * @param  list<int|string>  $sectionIds
     * @return list<int>
     */
    private function coursesOf(array $sectionIds): array
    {
        return DB::table('sections')->join('course_offerings', 'course_offerings.id', '=', 'sections.course_offering_id')
            ->whereIn('sections.id', $sectionIds)->distinct()->pluck('course_offerings.course_id')->map(fn ($id) => (int) $id)->all();
    }
}
