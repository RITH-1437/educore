<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\StoredFile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Seeds two assignments per open section (one past due with graded work, one
 * upcoming draft-free assignment) and a few submissions with a tiny PDF stored
 * on the uploads disk.
 *
 * Seed data is historical, so it is written directly rather than through
 * `AssignmentService` (whose "due date in the future" rule applies to new
 * work). Idempotent: assignments by `(section_id, title)`, submissions by
 * `(assignment_id, enrollment_id)`. If object storage is unreachable the
 * submissions are skipped and the assignments remain.
 */
class AssignmentSeeder extends Seeder
{
    private const PDF = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Count 0/Kids[]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";

    public function run(): void
    {
        $disk = (string) config('academics.uploads_disk', 's3');

        foreach (Section::query()->with('offering.semester', 'offering.course')->whereIn('status', ['open', 'active'])->get() as $section) {
            $course = $section->offering->course;

            $past = Assignment::query()->updateOrCreate(
                ['section_id' => $section->id, 'title' => "{$course->code} Problem Set 1"],
                ['description' => 'Warm-up exercises on the first chapters.', 'max_score' => 20, 'due_at' => $section->offering->semester->start_date?->copy()->addWeeks(3)->setTime(23, 59) ?? now()->subWeek(), 'assignment_type' => 'homework', 'is_published' => true, 'published_at' => now()->subMonth()],
            );
            Assignment::query()->updateOrCreate(
                ['section_id' => $section->id, 'title' => "{$course->code} Term Project"],
                ['description' => 'Team project with a written report.', 'max_score' => 100, 'due_at' => now()->addWeeks(4)->setTime(23, 59), 'assignment_type' => 'project', 'is_published' => true, 'published_at' => now()->subWeek()],
            );

            $enrollments = Enrollment::query()->where('section_id', $section->id)->whereIn('status', ['confirmed', 'completed'])->orderBy('id')->take(3)->get();

            foreach ($enrollments as $i => $enrollment) {
                if (AssignmentSubmission::query()->where('assignment_id', $past->id)->where('enrollment_id', $enrollment->id)->exists()) {
                    continue;
                }

                $key = "assignments/{$course->id}/{$past->id}/submissions/{$enrollment->student_id}/seed.pdf";

                try {
                    Storage::disk($disk)->put($key, self::PDF);
                } catch (Throwable) {
                    continue;
                }

                $submission = AssignmentSubmission::query()->create([
                    'assignment_id' => $past->id,
                    'enrollment_id' => $enrollment->id,
                    'submitted_at' => $past->due_at->copy()->subDays(2),
                    'status' => $i === 0 ? 'submitted' : 'graded',
                    'score' => $i === 0 ? null : 14 + $i * 2,
                    'feedback' => $i === 0 ? null : 'Solid work; check question 3.',
                    'graded_at' => $i === 0 ? null : $past->due_at->copy()->addDays(3),
                ]);

                StoredFile::query()->create([
                    'fileable_type' => AssignmentSubmission::class,
                    'fileable_id' => $submission->id,
                    'file_name' => 'seed.pdf',
                    'original_name' => 'problem-set-1.pdf',
                    'storage_key' => $key,
                    'bucket' => (string) (config("filesystems.disks.{$disk}.bucket") ?: 'educore'),
                    'mime_type' => 'application/pdf',
                    'size' => strlen(self::PDF),
                    'visibility' => 'private',
                    'checksum' => hash('sha256', self::PDF),
                ]);
            }
        }
    }
}
