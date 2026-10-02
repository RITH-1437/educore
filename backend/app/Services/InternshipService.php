<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Internship;
use App\Models\InternshipCompany;
use App\Models\InternshipEvaluation;
use App\Models\InternshipReport;
use App\Models\Student;
use App\Models\User;
use App\Notifications\InternshipStatusChanged;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Internship workflow (module 9.22, `skills/internship`).
 *
 * | From                           | Action   | To           | Who                   |
 * |--------------------------------|----------|--------------|-----------------------|
 * | —                              | apply    | draft        | student               |
 * | draft                          | submit   | submitted    | student               |
 * | submitted                      | review   | under_review | manager               |
 * | submitted, under_review        | approve  | approved     | manager               |
 * | submitted, under_review        | reject   | rejected     | manager (reason)      |
 * | approved                       | start    | in_progress  | manager               |
 * | in_progress                    | complete | completed    | manager (final report)|
 * | draft, submitted, under_review | cancel   | cancelled    | student               |
 * | any non-final                  | cancel   | cancelled    | manager (reason)      |
 *
 * One open application (draft or active) per student; the DB also enforces
 * one active (`uq_internships_active`). A rejected student applies anew.
 * Decisions are appended to `notes`; nothing is deleted.
 */
class InternshipService
{
    // -------------------------------------------------------------- companies

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveCompany(?InternshipCompany $company, array $data): InternshipCompany
    {
        return DB::transaction(function () use ($company, $data) {
            $company ??= new InternshipCompany(['is_active' => true]);
            $company->fill($data)->save();

            return $company->refresh();
        });
    }

    // ------------------------------------------------------------ application

    /**
     * @param  array<string, mixed>  $data
     */
    public function apply(Student $student, array $data): Internship
    {
        return DB::transaction(function () use ($student, $data) {
            Student::query()->lockForUpdate()->findOrFail($student->getKey());

            if (Internship::query()->where('student_id', $student->getKey())->whereNotIn('status', Internship::FINAL_STATUSES)->exists()) {
                throw new BusinessRuleException('You already have an open internship application; finish or cancel it first.');
            }

            $this->assertCompany($data['company_id']);

            return Internship::query()->create([...$this->fields($data), 'student_id' => $student->getKey(), 'status' => Internship::STATUS_DRAFT])->refresh();
        });
    }

    /**
     * Students edit while draft; managers while the internship is not final
     * (e.g. a company change after approval).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Internship $internship, array $data, bool $asManager): Internship
    {
        return DB::transaction(function () use ($internship, $data, $asManager) {
            $internship = Internship::query()->lockForUpdate()->findOrFail($internship->getKey());
            $editable = $asManager ? ! in_array($internship->status, Internship::FINAL_STATUSES, true) : $internship->status === Internship::STATUS_DRAFT;

            if (! $editable) {
                throw new BusinessRuleException("A {$internship->status} internship cannot be edited".($asManager ? '.' : '; only drafts can.'));
            }

            if ((int) $data['company_id'] !== $internship->company_id) {
                $this->assertCompany($data['company_id']);
            }

            $internship->update($this->fields($data));

            return $internship->refresh();
        });
    }

    public function submit(Internship $internship): Internship
    {
        return $this->move($internship, [Internship::STATUS_DRAFT], Internship::STATUS_SUBMITTED, null, null, ['submitted_at' => now()], notify: false);
    }

    public function review(Internship $internship, User $by): Internship
    {
        return $this->move($internship, [Internship::STATUS_SUBMITTED], Internship::STATUS_UNDER_REVIEW, $by, null, notify: false);
    }

    public function approve(Internship $internship, User $by, ?string $note): Internship
    {
        return $this->move($internship, [Internship::STATUS_SUBMITTED, Internship::STATUS_UNDER_REVIEW], Internship::STATUS_APPROVED, $by, $note);
    }

    public function reject(Internship $internship, User $by, string $reason): Internship
    {
        return $this->move($internship, [Internship::STATUS_SUBMITTED, Internship::STATUS_UNDER_REVIEW], Internship::STATUS_REJECTED, $by, $reason);
    }

    public function start(Internship $internship, User $by): Internship
    {
        return $this->move($internship, [Internship::STATUS_APPROVED], Internship::STATUS_IN_PROGRESS, $by, null);
    }

    public function complete(Internship $internship, User $by, ?string $note): Internship
    {
        if (! $internship->reports()->where('report_type', 'final')->exists()) {
            throw new BusinessRuleException('The student has not submitted a final report yet.');
        }

        return $this->move($internship, [Internship::STATUS_IN_PROGRESS], Internship::STATUS_COMPLETED, $by, $note);
    }

    public function cancel(Internship $internship, User $by, bool $asManager, ?string $reason): Internship
    {
        $from = $asManager
            ? array_values(array_diff(Internship::STATUSES, Internship::FINAL_STATUSES))
            : [Internship::STATUS_DRAFT, Internship::STATUS_SUBMITTED, Internship::STATUS_UNDER_REVIEW];

        if ($asManager && blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'Give a reason for cancelling.']);
        }

        return $this->move($internship, $from, Internship::STATUS_CANCELLED, $asManager ? $by : null, $reason, notify: $asManager);
    }

    // ---------------------------------------------------------------- reports

    /**
     * @param  array{report_type: string, title: string, summary?: string|null}  $data
     */
    public function addReport(Internship $internship, array $data, ?UploadedFile $file, User $by): InternshipReport
    {
        $allowed = $data['report_type'] === 'final' ? [Internship::STATUS_IN_PROGRESS] : [Internship::STATUS_APPROVED, Internship::STATUS_IN_PROGRESS];

        if (! in_array($internship->status, $allowed, true)) {
            throw new BusinessRuleException($data['report_type'] === 'final'
                ? 'A final report can be submitted once the internship is in progress.'
                : 'Reports can be submitted once the internship is approved.');
        }

        $key = null;

        if ($file !== null) {
            $key = "internships/{$internship->student_id}/{$internship->getKey()}/".Str::uuid().'.'.strtolower($file->getClientOriginalExtension() ?: $file->extension());
            Storage::disk($this->disk())->putFileAs(dirname($key), $file, basename($key), ['visibility' => 'private']);
        }

        try {
            return DB::transaction(function () use ($internship, $data, $file, $key, $by) {
                $report = $internship->reports()->create([
                    'report_type' => $data['report_type'],
                    'title' => $data['title'],
                    'summary' => $data['summary'] ?? null,
                    'submitted_at' => now(),
                    'status' => 'submitted',
                ]);

                if ($file !== null) {
                    $report->file()->create([
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

                return $report->refresh();
            });
        } catch (Throwable $e) {
            if ($key !== null) {
                Storage::disk($this->disk())->delete($key);
            }

            throw $e;
        }
    }

    public function reviewReport(InternshipReport $report, ?string $comment): InternshipReport
    {
        $report->update(['status' => 'reviewed', 'reviewer_comment' => $comment]);

        return $report->refresh();
    }

    public function downloadReport(InternshipReport $report): StreamedResponse
    {
        $file = $report->file;
        abort_if($file === null, 404, 'No file is attached to this report.');

        return Storage::disk($this->disk())->download($file->storage_key, $file->original_name);
    }

    // ------------------------------------------------------------ evaluations

    /**
     * One evaluation per evaluator type; saving again replaces it.
     *
     * @param  array{evaluator_type: string, evaluator_name?: string|null, score: float|int|string, rating?: string|null, comments?: string|null}  $data
     */
    public function evaluate(Internship $internship, array $data, User $by): InternshipEvaluation
    {
        if (! in_array($internship->status, [Internship::STATUS_IN_PROGRESS, Internship::STATUS_COMPLETED], true)) {
            throw new BusinessRuleException('Evaluations are recorded once the internship has started.');
        }

        return DB::transaction(fn () => InternshipEvaluation::query()->updateOrCreate(
            ['internship_id' => $internship->getKey(), 'evaluator_type' => $data['evaluator_type']],
            [
                'evaluator_name' => $data['evaluator_name'] ?? ($data['evaluator_type'] === 'supervisor' ? $internship->supervisor_name : $by->name),
                'score' => $data['score'],
                'rating' => $data['rating'] ?? null,
                'comments' => $data['comments'] ?? null,
                'evaluated_at' => now(),
                'submitted_by' => $by->getKey(),
            ],
        )->refresh());
    }

    public function disk(): string
    {
        return (string) config('academics.uploads_disk', 's3');
    }

    // ---------------------------------------------------------------- helpers

    /**
     * @param  list<string>  $from
     * @param  array<string, mixed>  $extra
     */
    private function move(Internship $internship, array $from, string $to, ?User $by, ?string $note, array $extra = [], bool $notify = true): Internship
    {
        return DB::transaction(function () use ($internship, $from, $to, $by, $note, $extra, $notify) {
            $internship = Internship::query()->lockForUpdate()->findOrFail($internship->getKey());

            if (! in_array($internship->status, $from, true)) {
                throw new BusinessRuleException('A '.str_replace('_', ' ', $internship->status).' internship cannot be '.str_replace('_', ' ', $to).'.');
            }

            // Another open application might have been submitted meanwhile.
            if (in_array($to, Internship::ACTIVE_STATUSES, true) && Internship::query()
                ->where('student_id', $internship->student_id)->whereKeyNot($internship->getKey())
                ->whereIn('status', Internship::ACTIVE_STATUSES)->exists()) {
                throw new BusinessRuleException('The student already has an active internship.');
            }

            $log = $note !== null && $note !== ''
                ? trim(($internship->notes ? $internship->notes."\n" : '').now()->toDateString().' '.str_replace('_', ' ', $to).($by ? " by {$by->name}" : '').": {$note}")
                : $internship->notes;

            $internship->update([
                ...$extra,
                'status' => $to,
                'notes' => $log,
                ...($by ? ['reviewed_by' => $by->getKey(), 'reviewed_at' => now()] : []),
            ]);

            if ($notify) {
                $internship->student->user?->notify(new InternshipStatusChanged($internship->refresh()));
            }

            return $internship->refresh();
        });
    }

    private function assertCompany(mixed $companyId): void
    {
        if (! InternshipCompany::query()->whereKey($companyId)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['company_id' => 'Choose an active company.']);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function fields(array $data): array
    {
        return [
            'company_id' => (int) $data['company_id'],
            'position_title' => $data['position_title'],
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'supervisor_name' => $data['supervisor_name'] ?? null,
            'supervisor_email' => $data['supervisor_email'] ?? null,
            'supervisor_phone' => $data['supervisor_phone'] ?? null,
        ];
    }
}
