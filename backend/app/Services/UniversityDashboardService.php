<?php

namespace App\Services;

use App\Models\DocumentRequest;
use App\Models\Internship;
use App\Models\Invoice;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * University Admin dashboard (`docs/36_University-Admin-Dashboard-Report.md`):
 * the document requests, internships, and overdue invoices waiting for institutional
 * processing or attention, plus the university's headline numbers for the current
 * semester based on institutional analytics (module 9.23). Computed live.
 */
class UniversityDashboardService
{
    public function __construct(
        private readonly AnalyticsService $analytics,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $semester = $this->analytics->defaultSemester();
        $requests = $this->countByStatus(DocumentRequest::query());
        $internships = $this->countByStatus(Internship::query());
        $invoices = Invoice::query()->toBase()->groupBy('status')->selectRaw('status, count(*) as total')
            ->pluck('total', 'status')->map(fn ($total) => (int) $total)->all();

        return [
            'semester' => $semester ? ['id' => $semester->id, 'name' => trim(($semester->academicYear?->code ?? '').' '.$semester->name)] : null,
            'waiting' => [
                'document_requests_pending' => $requests[DocumentRequest::STATUS_PENDING] ?? 0,
                'document_requests_approved' => $requests[DocumentRequest::STATUS_APPROVED] ?? 0,
                'internships_submitted' => $internships[Internship::STATUS_SUBMITTED] ?? 0,
                'internships_under_review' => $internships[Internship::STATUS_UNDER_REVIEW] ?? 0,
                'invoices_overdue' => $invoices[Invoice::STATUS_OVERDUE] ?? 0,
            ],
            'overview' => $semester ? $this->analytics->overview($semester) : [
                'students_active' => Student::query()->where('status', Student::STATUS_ACTIVE)->count(),
                'lecturers_active' => DB::table('lecturers')->where('is_active', true)->count(),
                'sections' => null,
                'enrollments' => null,
                'students_enrolled' => null,
                'attendance_rate' => null,
                'grades_approved' => null,
                'pass_rate' => null,
                'average_gpa' => null,
            ],
        ];
    }

    /**
     * @param  Builder<DocumentRequest>|Builder<Internship>  $query
     * @return array<string, int>
     */
    private function countByStatus(Builder $query): array
    {
        return $query->toBase()->groupBy('status')->selectRaw('status, count(*) as total')
            ->pluck('total', 'status')->map(fn ($total) => (int) $total)->all();
    }
}
