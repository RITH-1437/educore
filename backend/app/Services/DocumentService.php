<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\DocumentVerification;
use App\Models\Enrollment;
use App\Models\Internship;
use App\Models\Invoice;
use App\Models\Semester;
use App\Models\Student;
use App\Models\University;
use App\Models\User;
use App\Notifications\DocumentRequestUpdated;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Document requests, generation and verification (modules 9.16 / 9.17,
 * `skills/documents/SKILL.md`).
 *
 * - `pending → approved → generated`, or `pending → rejected` with a reason.
 * - One open (pending / approved) request per student, type and semester.
 * - Content comes only from authoritative data: enrollments, approved grades
 *   (`GradingService`) and GPA (`GpaService`). A generated PDF is an immutable
 *   snapshot; staff revoke it when it goes stale and the student requests anew.
 * - Files are private on the uploads disk under `documents/{student}/{uuid}.pdf`.
 * - Public verification by token shows minimal data and logs every lookup.
 */
class DocumentService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly GradingService $grading,
        private readonly GpaService $gpa,
        private readonly InvoiceService $invoices,
        private readonly StaffNotifier $staff,
    ) {}

    /**
     * @param  array{document_type_id: int, semester_id?: int|null, reason?: string|null}  $data
     */
    public function request(Student $student, array $data): DocumentRequest
    {
        return DB::transaction(function () use ($student, $data) {
            $type = DocumentType::query()->findOrFail($data['document_type_id']);

            if (! $type->is_active || ! in_array($type->code, DocumentType::GENERATABLE, true)) {
                throw ValidationException::withMessages(['document_type_id' => 'This document type cannot be requested.']);
            }

            $semester = null;

            if ($type->needsSemester()) {
                $semester = Semester::query()->find($data['semester_id'] ?? null);

                if ($semester === null) {
                    throw ValidationException::withMessages(['semester_id' => 'Choose the semester for the academic result.']);
                }
            }

            $open = DocumentRequest::query()
                ->where('student_id', $student->getKey())
                ->where('document_type_id', $type->getKey())
                ->where('semester_id', $semester?->getKey())
                ->whereIn('status', [DocumentRequest::STATUS_PENDING, DocumentRequest::STATUS_APPROVED])
                ->lockForUpdate()
                ->exists();

            if ($open) {
                throw new BusinessRuleException("You already have an open request for this {$type->name}.");
            }

            $request = DocumentRequest::query()->create([
                'student_id' => $student->getKey(),
                'document_type_id' => $type->getKey(),
                'semester_id' => $semester?->getKey(),
                'academic_year_id' => $semester?->academic_year_id,
                'reason' => $data['reason'] ?? null,
                'status' => DocumentRequest::STATUS_PENDING,
                'submitted_at' => now(),
            ])->refresh();

            // Queued after commit: the staff who can approve it hear about it (report 43).
            $this->staff->documentRequested($request);

            return $request;
        });
    }

    public function approve(DocumentRequest $request, User $by): DocumentRequest
    {
        return DB::transaction(function () use ($request, $by) {
            $request->loadMissing('type', 'student');
            $invoiceId = $request->invoice_id;

            if ($request->type->requires_fee && (float) $request->type->fee_amount > 0 && ! $invoiceId) {
                $invoice = $this->invoices->create([
                    'student_id' => $request->student_id,
                    'title' => "Document Fee: {$request->type->name}",
                    'description' => "Official document fee for {$request->type->name} (Request #{$request->id})",
                    'currency' => 'USD',
                    'due_date' => now()->addDays(14)->toDateString(),
                    'issued_date' => now()->toDateString(),
                    'discount' => 0,
                    'items' => [
                        [
                            'description' => "Fee for {$request->type->name}",
                            'quantity' => 1,
                            'unit_price' => (float) $request->type->fee_amount,
                            'fee_category' => 'document',
                        ],
                    ],
                ]);
                $invoiceId = $invoice->id;
            }

            $changes = [
                'status' => DocumentRequest::STATUS_APPROVED,
                'processed_by' => $by->getKey(),
                'processed_at' => now(),
            ];

            if ($invoiceId) {
                $changes['invoice_id'] = $invoiceId;
            }

            return $this->afterTransition($this->transition($request, DocumentRequest::STATUS_PENDING, $changes));
        });
    }

    public function reject(DocumentRequest $request, User $by, string $reason): DocumentRequest
    {
        return $this->afterTransition($this->transition($request, DocumentRequest::STATUS_PENDING, ['status' => DocumentRequest::STATUS_REJECTED, 'rejection_reason' => $reason, 'processed_by' => $by->getKey(), 'processed_at' => now()]));
    }

    public function waiveFee(DocumentRequest $request, User $by, ?string $reason = null): DocumentRequest
    {
        return DB::transaction(function () use ($request, $by, $reason) {
            $locked = DocumentRequest::query()->lockForUpdate()->findOrFail($request->getKey());

            if (! in_array($locked->status, [DocumentRequest::STATUS_PENDING, DocumentRequest::STATUS_APPROVED], true)) {
                throw new BusinessRuleException("Cannot waive fee for a {$locked->status} request.");
            }

            if ($locked->is_fee_waived) {
                return $locked;
            }

            $locked->loadMissing('invoice');
            if ($locked->invoice && $locked->invoice->status === Invoice::STATUS_PAID) {
                throw new BusinessRuleException('Cannot waive fee: the invoice is already paid.');
            }

            if ($locked->invoice && $locked->invoice->status !== Invoice::STATUS_CANCELLED) {
                $this->invoices->cancel($locked->invoice, "Fee waived by {$by->name}".($reason ? ": {$reason}" : ''));
            }

            $locked->update([
                'is_fee_waived' => true,
                'waived_by' => $by->getKey(),
                'waived_at' => now(),
                'waiver_reason' => $reason,
            ]);

            $this->audit->record('document_request.fee_waived', $locked, after: [
                'is_fee_waived' => true,
                'waiver_reason' => $reason,
                'waived_by' => $by->getKey(),
            ]);

            return $locked->refresh();
        });
    }

    /**
     * Render, store and register the PDF. If storing fails the request stays
     * `approved` so generation can be retried, and no orphan file is left.
     */
    public function generate(DocumentRequest $request, User $by): Document
    {
        if ($request->status !== DocumentRequest::STATUS_APPROVED) {
            throw new BusinessRuleException("A {$request->status} request cannot be generated; approve it first.");
        }

        $request->loadMissing('type', 'semester.academicYear', 'student', 'invoice');

        if (! $request->is_fee_waived && $request->invoice_id && $request->invoice && $request->invoice->status !== Invoice::STATUS_PAID) {
            throw new BusinessRuleException("Document generation requires fee payment: invoice {$request->invoice->invoice_number} is {$request->invoice->status}.");
        }

        $token = bin2hex(random_bytes(32));
        $pdf = $this->render($request, $token);
        $key = "documents/{$request->student_id}/".Str::uuid().'.pdf';

        Storage::disk($this->disk())->put($key, $pdf);

        try {
            return DB::transaction(function () use ($request, $by, $token, $pdf, $key) {
                $locked = DocumentRequest::query()->lockForUpdate()->findOrFail($request->getKey());

                if ($locked->status !== DocumentRequest::STATUS_APPROVED) {
                    throw new BusinessRuleException('This request was processed meanwhile.');
                }

                $document = Document::query()->create([
                    'document_request_id' => $request->getKey(),
                    'file_key' => $key,
                    'file_name' => Str::slug($request->type->name.' '.$request->student->student_number).'.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => strlen($pdf),
                    'checksum' => hash('sha256', $pdf),
                    'verification_token' => $token,
                    'generated_by' => $by->getKey(),
                    'generated_at' => now(),
                    'status' => Document::STATUS_VALID,
                ]);
                $locked->update(['status' => DocumentRequest::STATUS_GENERATED]);
                $this->afterTransition($locked->refresh());

                return $document->refresh();
            });
        } catch (Throwable $e) {
            Storage::disk($this->disk())->delete($key);

            throw $e;
        }
    }

    public function revoke(Document $document): Document
    {
        if ($document->status !== Document::STATUS_VALID) {
            throw new BusinessRuleException('Only a valid document can be revoked.');
        }

        $document->update(['status' => Document::STATUS_REVOKED]);
        $this->audit->record('document.revoked', $document, ['status' => Document::STATUS_VALID], ['status' => Document::STATUS_REVOKED]);

        return $document->refresh();
    }

    public function download(Document $document): StreamedResponse
    {
        $this->audit->record('document.downloaded', $document);

        return Storage::disk($this->disk())->download($document->file_key, $document->file_name, ['Content-Type' => 'application/pdf']);
    }

    /**
     * Public lookup (9.17): minimal data only, every lookup logged.
     *
     * @return array<string, mixed>|null null when the token is unknown
     */
    public function verify(string $token, ?string $ip, ?string $userAgent): ?array
    {
        $document = Document::query()->where('verification_token', $token)->with('request.type', 'request.student', 'request.semester.academicYear')->first();

        if ($document === null) {
            return null;
        }

        DocumentVerification::query()->create([
            'document_id' => $document->id,
            'verification_token' => $token,
            'result' => $document->status,
            'verified_at' => now(),
            'ip_address' => $ip,
            'user_agent' => $userAgent ? Str::limit($userAgent, 500, '') : null,
        ]);

        $request = $document->request;

        return [
            'status' => $document->status,
            'document_type' => $request->type->name,
            'semester' => $request->semester ? trim(($request->semester->academicYear?->code ?? '').' '.$request->semester->name) : null,
            'issued_to' => $request->student->fullName(),
            'student_number' => $request->student->student_number,
            'issued_on' => $document->generated_at->toDateString(),
            'issuer' => University::query()->where('is_current', true)->value('name'),
            'checksum' => $document->checksum,
        ];
    }

    public function disk(): string
    {
        return (string) config('academics.uploads_disk', 's3');
    }

    public function verificationUrl(string $token): string
    {
        return rtrim((string) config('app.url'), '/')."/verify/{$token}";
    }

    /**
     * Generate an SVG string of the QR code pointing to the verification URL.
     */
    public function qrCodeSvg(string $url): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(120, 1),
            new SvgImageBackEnd
        );
        $writer = new Writer($renderer);

        return $writer->writeString($url);
    }

    /**
     * Generate a data URI for the SVG QR code suitable for DomPDF <img> embedding.
     */
    public function qrCodeDataUri(string $url): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->qrCodeSvg($url));
    }

    // ---------------------------------------------------------------- render

    private function render(DocumentRequest $request, string $token): string
    {
        $student = $request->student->loadMissing('currentProgram.program.department');
        $verifyUrl = $this->verificationUrl($token);
        $data = [
            'request' => $request,
            'student' => $student,
            'program' => $student->currentProgram?->program,
            'university' => University::query()->where('is_current', true)->first(),
            'issuedOn' => now()->toDateString(),
            'token' => $token,
            'verifyUrl' => $verifyUrl,
            'qrCode' => $this->qrCodeDataUri($verifyUrl),
        ];

        $view = match ($request->type->code) {
            DocumentType::TRANSCRIPT => $this->transcriptData($student, $data),
            DocumentType::ACADEMIC_RESULT => $this->resultData($student, $request->semester, $data),
            DocumentType::ENROLLMENT_CERTIFICATE => $this->enrollmentData($student, $data),
            DocumentType::STUDENT_CERTIFICATE => $this->studentCertificateData($student, $data),
            DocumentType::INTERNSHIP_LETTER => $this->internshipLetterData($student, $data),
            default => throw new BusinessRuleException('This document type has no template.'),
        };

        // Subset fonts so a one-page PDF stays small (~tens of KB, not ~1 MB).
        return Pdf::loadView($view[0], $view[1])->setPaper('a4')->setOption('isFontSubsettingEnabled', true)->output();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function transcriptData(Student $student, array $data): array
    {
        $grades = $this->grading->forStudent($student->id);

        if ($grades->isEmpty()) {
            throw new BusinessRuleException('The student has no approved grades; a transcript cannot be generated yet.');
        }

        $gpa = $this->gpa->summary($student);

        return ['documents.transcript', [...$data,
            'semesters' => $grades->groupBy('semester_id')->map(fn ($rows, $id) => [
                'name' => $rows->first()['semester'],
                'grades' => $rows->values(),
                'gpa' => collect($gpa['semesters'])->firstWhere('semester_id', $id),
            ])->values(),
            'cumulative' => $gpa['cumulative'],
        ]];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function resultData(Student $student, ?Semester $semester, array $data): array
    {
        $grades = $this->grading->forStudent($student->id)->where('semester_id', $semester?->id)->values();

        if ($grades->isEmpty()) {
            throw new BusinessRuleException('The student has no approved grades in this semester.');
        }

        return ['documents.academic-result', [...$data,
            'semesterName' => $grades->first()['semester'],
            'grades' => $grades,
            'gpa' => collect($this->gpa->summary($student)['semesters'])->firstWhere('semester_id', $semester->id),
        ]];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function enrollmentData(Student $student, array $data): array
    {
        if ($student->status !== Student::STATUS_ACTIVE) {
            throw new BusinessRuleException("An enrollment certificate is only issued to active students (this student is {$student->status}).");
        }

        $enrollments = $student->enrollments()
            ->whereIn('status', Enrollment::OPEN_STATUSES)
            ->with('section.offering.course:id,code,name,credits', 'semester.academicYear:id,code')
            ->get();

        return ['documents.enrollment-certificate', [...$data,
            'enrollments' => $enrollments,
            'semesterName' => ($s = $enrollments->first()?->semester) ? trim(($s->academicYear?->code ?? '').' '.$s->name) : null,
        ]];
    }

    /**
     * Student status certificate: active students and graduates only.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function studentCertificateData(Student $student, array $data): array
    {
        if (! in_array($student->status, [Student::STATUS_ACTIVE, Student::STATUS_GRADUATED], true)) {
            throw new BusinessRuleException("A student certificate is only issued to active or graduated students (this student is {$student->status}).");
        }

        // A graduate has no active program any more: fall back to the latest one.
        $enrolment = $student->currentProgram ?? $student->programHistory()->with('program.department')->first();

        return ['documents.student-certificate', [...$data,
            'program' => $data['program'] ?? $enrolment?->program,
            'programEnrolment' => $enrolment,
        ]];
    }

    /**
     * Internship letter for the student's latest approved, ongoing or
     * completed internship (`docs/26_Internship-Management-Report.md`).
     *
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function internshipLetterData(Student $student, array $data): array
    {
        $internship = Internship::query()
            ->where('student_id', $student->id)
            ->whereIn('status', [Internship::STATUS_APPROVED, Internship::STATUS_IN_PROGRESS, Internship::STATUS_COMPLETED])
            ->with('company')
            ->orderByRaw('start_date desc nulls last')
            ->orderByDesc('id')
            ->first();

        if ($internship === null) {
            throw new BusinessRuleException('The student has no approved, ongoing or completed internship; an internship letter cannot be generated.');
        }

        return ['documents.internship-letter', [...$data, 'internship' => $internship]];
    }

    /** Audit the decision and tell the student (critical email; queued after commit). */
    private function afterTransition(DocumentRequest $request): DocumentRequest
    {
        $this->audit->record("document_request.{$request->status}", $request, after: $request->only(['status', 'document_type_id', 'semester_id', 'rejection_reason']));
        $request->loadMissing('student.user')->student->user?->notify(new DocumentRequestUpdated($request));

        return $request;
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function transition(DocumentRequest $request, string $from, array $changes): DocumentRequest
    {
        return DB::transaction(function () use ($request, $from, $changes) {
            $locked = DocumentRequest::query()->lockForUpdate()->findOrFail($request->getKey());

            if ($locked->status !== $from) {
                throw new BusinessRuleException("A {$locked->status} request cannot change this way.");
            }

            $locked->update($changes);

            return $locked->refresh();
        });
    }
}
