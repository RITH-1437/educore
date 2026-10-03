<?php

namespace App\Services;

use App\Enums\SemesterStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Assignments and submissions (module 9.12).
 *
 * - Due dates are server time; a new/changed due date must be in the future and
 *   not after the semester end. Completed semesters are frozen.
 * - Max score > 0 and never below a score already awarded.
 * - One submission per student (enrollment) per assignment; re-submitting
 *   replaces the file until graded. Late work is accepted and flagged `late`.
 * - Files are private objects on the uploads disk under a server-generated key
 *   (`skills/file-storage`); the old object is deleted on replace.
 */
class AssignmentService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Section $section, array $data): Assignment
    {
        return DB::transaction(function () use ($section, $data) {
            $section->loadMissing('offering.semester');
            $this->assertOpenSemester($section);
            $this->assertDueDate($section, Carbon::parse($data['due_at']));

            return $section->assignments()->create([...$data, 'is_published' => false])->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Assignment $assignment, array $data): Assignment
    {
        return DB::transaction(function () use ($assignment, $data) {
            $section = $assignment->section->loadMissing('offering.semester');
            $this->assertOpenSemester($section);

            if (isset($data['due_at']) && ! Carbon::parse($data['due_at'])->equalTo($assignment->due_at)) {
                $this->assertDueDate($section, Carbon::parse($data['due_at']));
            }

            if (isset($data['max_score'])) {
                $highest = (float) $assignment->submissions()->max('score');

                if ((float) $data['max_score'] < $highest) {
                    throw ValidationException::withMessages(['max_score' => "A score of {$highest} has already been awarded; the maximum cannot be lower."]);
                }
            }

            $assignment->update($data);

            return $assignment->refresh();
        });
    }

    public function publish(Assignment $assignment, bool $published = true): Assignment
    {
        return DB::transaction(function () use ($assignment, $published) {
            if (! $published && $assignment->submissions()->exists()) {
                throw new BusinessRuleException('Students have already submitted; the assignment cannot be unpublished.');
            }

            $assignment->update(['is_published' => $published, 'published_at' => $published ? ($assignment->published_at ?? now()) : null]);

            return $assignment->refresh();
        });
    }

    public function delete(Assignment $assignment): void
    {
        DB::transaction(function () use ($assignment) {
            if ($assignment->submissions()->exists()) {
                throw new BusinessRuleException('This assignment has submissions and cannot be deleted.');
            }

            $assignment->delete();
        });
    }

    public function submit(Assignment $assignment, Student $student, UploadedFile $file, User $by): AssignmentSubmission
    {
        $enrollment = Enrollment::query()
            ->where('section_id', $assignment->section_id)
            ->where('student_id', $student->getKey())
            ->whereIn('status', [Enrollment::STATUS_PENDING, Enrollment::STATUS_CONFIRMED])
            ->first();

        if ($enrollment === null) {
            throw new BusinessRuleException('Only students currently enrolled in this section can submit.');
        }

        $this->assertOpenSemester($assignment->section->loadMissing('offering.semester'));

        $existing = AssignmentSubmission::query()->where('assignment_id', $assignment->getKey())->where('enrollment_id', $enrollment->getKey())->first();

        if ($existing !== null && in_array($existing->status, ['graded', 'returned'], true)) {
            throw new BusinessRuleException('This submission has been graded and can no longer be replaced.');
        }

        $course = $assignment->section->offering->course_id;
        $key = "assignments/{$course}/{$assignment->getKey()}/submissions/{$student->getKey()}/".Str::uuid().'.'.strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $disk = $this->disk();

        // Upload first; if the database work fails the orphan object is removed.
        Storage::disk($disk)->putFileAs(dirname($key), $file, basename($key), ['visibility' => 'private']);

        try {
            return DB::transaction(function () use ($assignment, $enrollment, $existing, $file, $key, $by) {
                $now = now();
                $submission = $existing ?? new AssignmentSubmission(['assignment_id' => $assignment->getKey(), 'enrollment_id' => $enrollment->getKey()]);
                $submission->fill(['submitted_at' => $now, 'status' => $now->gt($assignment->due_at) ? 'late' : 'submitted'])->save();

                $old = $submission->file;
                $submission->file()->create([
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

                if ($old !== null) {
                    $oldKey = $old->storage_key;
                    $old->delete();
                    DB::afterCommit(fn () => Storage::disk($this->disk())->delete($oldKey));
                }

                return $submission->refresh();
            });
        } catch (\Throwable $e) {
            Storage::disk($disk)->delete($key);

            throw $e;
        }
    }

    public function grade(AssignmentSubmission $submission, float $score, ?string $feedback, User $by): AssignmentSubmission
    {
        return DB::transaction(function () use ($submission, $score, $feedback, $by) {
            $max = (float) $submission->assignment->max_score;

            if ($score > $max) {
                throw ValidationException::withMessages(['score' => "The score cannot exceed the maximum of {$max}."]);
            }

            $submission->update(['score' => $score, 'feedback' => $feedback, 'status' => 'graded', 'graded_by' => $by->getKey(), 'graded_at' => now()]);

            return $submission->refresh();
        });
    }

    /**
     * Submissions waiting for a grade (submitted or late; graded and returned
     * ones are done), counted per section.
     *
     * @param  list<int>  $sectionIds
     * @return Collection<int, int> section id => count
     */
    public function toGradeCounts(array $sectionIds): Collection
    {
        return AssignmentSubmission::query()
            ->join('assignments', 'assignments.id', '=', 'assignment_submissions.assignment_id')
            ->whereIn('assignments.section_id', $sectionIds)
            ->whereIn('assignment_submissions.status', ['submitted', 'late'])
            ->groupBy('assignments.section_id')
            ->selectRaw('assignments.section_id, count(*) as total')
            ->pluck('total', 'section_id')
            ->map(fn ($total) => (int) $total);
    }

    public function download(AssignmentSubmission $submission): StreamedResponse
    {
        $file = $submission->file;
        abort_if($file === null, 404, 'No file is attached to this submission.');

        return Storage::disk($this->disk())->download($file->storage_key, $file->original_name);
    }

    public function disk(): string
    {
        return (string) config('academics.uploads_disk', 's3');
    }

    private function assertOpenSemester(Section $section): void
    {
        if ($section->offering->semester->status === SemesterStatus::Completed) {
            throw new BusinessRuleException('The semester is completed; coursework can no longer change.');
        }
    }

    private function assertDueDate(Section $section, Carbon $due): void
    {
        if ($due->lte(now())) {
            throw ValidationException::withMessages(['due_at' => 'The due date must be in the future.']);
        }

        $end = $section->offering->semester->end_date;
        if ($end !== null && $due->gt($end->copy()->endOfDay())) {
            throw ValidationException::withMessages(['due_at' => 'The due date must be within the semester (ends '.$end->toDateString().').']);
        }
    }
}
