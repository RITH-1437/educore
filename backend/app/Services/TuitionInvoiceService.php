<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Generates semester tuition invoices based on enrolled course credits.
 * (`skills/invoices-payments/SKILL.md` §3, §4; Report 41).
 */
class TuitionInvoiceService
{
    public const DEFAULT_RATE_PER_CREDIT = 50.00;

    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Preview or generate tuition invoices for all enrolled students in a semester.
     *
     * @param  array{
     *     due_date?: string|null,
     *     rate_per_credit?: float|numeric-string|null,
     *     department_id?: int|null,
     *     program_id?: int|null,
     *     student_id?: int|null,
     *     dry_run?: bool,
     * }  $options
     * @return array{
     *     semester: array{id: int, name: string, code: string},
     *     dry_run: bool,
     *     total_students: int,
     *     generated_count: int,
     *     skipped_count: int,
     *     total_amount: float,
     *     total_credits: float,
     *     invoices: list<array<string, mixed>>,
     *     skipped: list<array<string, mixed>>,
     * }
     */
    public function generate(Semester $semester, array $options = [], ?User $actor = null): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $rateOverride = isset($options['rate_per_credit']) && is_numeric($options['rate_per_credit'])
            ? (float) $options['rate_per_credit']
            : null;

        // Resolve due date: custom due_date, or semester start_date if in future, or 30 days from today.
        $rawDueDate = $options['due_date'] ?? $semester->start_date?->toDateString();
        $dueDate = $rawDueDate ? Carbon::parse($rawDueDate) : today()->addDays(30);
        if ($dueDate->isBefore(today())) {
            $dueDate = today()->addDays(30);
        }
        $dueDateString = $dueDate->toDateString();

        // Retrieve enrollments in the semester that hold confirmed or completed seats.
        $enrollmentsQuery = Enrollment::query()
            ->where('semester_id', $semester->id)
            ->whereIn('status', [Enrollment::STATUS_CONFIRMED, Enrollment::STATUS_COMPLETED])
            ->with([
                'student.user:id,name,email',
                'student.currentProgram.program:id,name,code,tuition_per_credit',
                'section.offering.course:id,code,name,credits',
            ]);

        if (! empty($options['student_id'])) {
            $enrollmentsQuery->where('student_id', $options['student_id']);
        }

        if (! empty($options['department_id'])) {
            $enrollmentsQuery->whereHas('student.currentProgram.program', function ($q) use ($options) {
                $q->where('department_id', $options['department_id']);
            });
        }

        if (! empty($options['program_id'])) {
            $enrollmentsQuery->whereHas('student.currentProgram', function ($q) use ($options) {
                $q->where('program_id', $options['program_id']);
            });
        }

        $enrollments = $enrollmentsQuery->get();
        $byStudent = $enrollments->groupBy('student_id');

        $generatedInvoices = [];
        $skipped = [];
        $totalAmount = 0.0;
        $totalCreditsOverall = 0.0;

        foreach ($byStudent as $studentId => $studentEnrollments) {
            /** @var Student $student */
            $student = $studentEnrollments->first()->student;

            // Check if active tuition invoice already exists for this semester
            $existingInvoice = Invoice::query()
                ->where('student_id', $student->id)
                ->where('semester_id', $semester->id)
                ->where('status', '!=', Invoice::STATUS_CANCELLED)
                ->first();

            if ($existingInvoice) {
                $skipped[] = [
                    'student_id' => $student->id,
                    'student_number' => $student->student_number,
                    'student_name' => $student->fullName(),
                    'reason' => "Tuition invoice {$existingInvoice->invoice_number} already exists for this semester.",
                ];

                continue;
            }

            // Determine rate per credit: override > program config > default
            $programRate = $student->currentProgram?->program?->tuition_per_credit;
            $ratePerCredit = $rateOverride ?? ($programRate !== null ? (float) $programRate : self::DEFAULT_RATE_PER_CREDIT);

            $items = [];
            $studentCredits = 0.0;

            foreach ($studentEnrollments as $enrollment) {
                $course = $enrollment->section?->offering?->course;
                if (! $course) {
                    continue;
                }

                $credits = (float) $course->credits;
                if ($credits <= 0) {
                    continue;
                }

                $studentCredits += $credits;
                $amount = round($credits * $ratePerCredit, 2);

                $items[] = [
                    'description' => "Tuition: {$course->code} - {$course->name} ({$credits} credits)",
                    'quantity' => $credits,
                    'unit_price' => $ratePerCredit,
                    'amount' => $amount,
                    'fee_category' => 'tuition',
                ];
            }

            if (empty($items) || $studentCredits <= 0) {
                $skipped[] = [
                    'student_id' => $student->id,
                    'student_number' => $student->student_number,
                    'student_name' => $student->fullName(),
                    'reason' => 'No billable courses with credit hours found for this semester.',
                ];

                continue;
            }

            $invoiceTotal = array_sum(array_column($items, 'amount'));
            $totalAmount += $invoiceTotal;
            $totalCreditsOverall += $studentCredits;

            if ($dryRun) {
                $generatedInvoices[] = [
                    'student_id' => $student->id,
                    'student_number' => $student->student_number,
                    'student_name' => $student->fullName(),
                    'credits' => $studentCredits,
                    'rate_per_credit' => $ratePerCredit,
                    'total' => $invoiceTotal,
                    'due_date' => $dueDateString,
                    'status' => 'preview',
                    'item_count' => count($items),
                ];
            } else {
                $invoice = $this->invoiceService->create([
                    'student_id' => $student->id,
                    'semester_id' => $semester->id,
                    'title' => "Tuition - {$semester->name}",
                    'description' => "Tuition billing for {$semester->name} ({$studentCredits} credits enrolled)",
                    'currency' => 'USD',
                    'issued_date' => today()->toDateString(),
                    'due_date' => $dueDateString,
                    'discount' => 0,
                    'notes' => 'Automatically generated tuition invoice from semester enrollments.',
                    'items' => $items,
                ]);

                if ($actor) {
                    $this->audit->record('invoice.tuition_generated', $invoice, after: [
                        'semester_id' => $semester->id,
                        'total_credits' => $studentCredits,
                        'rate_per_credit' => $ratePerCredit,
                        'total' => (float) $invoice->total,
                    ]);
                }

                $generatedInvoices[] = [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'student_id' => $student->id,
                    'student_number' => $student->student_number,
                    'student_name' => $student->fullName(),
                    'credits' => $studentCredits,
                    'rate_per_credit' => $ratePerCredit,
                    'total' => (float) $invoice->total,
                    'due_date' => $invoice->due_date?->toDateString(),
                    'status' => $invoice->status,
                    'item_count' => count($items),
                ];
            }
        }

        return [
            'semester' => [
                'id' => $semester->id,
                'name' => $semester->name,
                'code' => $semester->code,
            ],
            'dry_run' => $dryRun,
            'total_students' => $byStudent->count(),
            'generated_count' => count($generatedInvoices),
            'skipped_count' => count($skipped),
            'total_amount' => round($totalAmount, 2),
            'total_credits' => round($totalCreditsOverall, 1),
            'invoices' => $generatedInvoices,
            'skipped' => $skipped,
        ];
    }
}
