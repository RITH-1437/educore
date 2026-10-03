<?php

namespace App\Services;

use App\Models\DocumentRequest;
use App\Models\Faculty;
use App\Models\Internship;
use Illuminate\Database\Eloquent\Builder;

/**
 * Faculty dashboard (`docs/34_Faculty-Admin-Dashboard-Report.md`): the
 * document requests and internships of the faculty's students that wait for
 * processing (report 33), and the faculty's headline numbers for the current
 * semester on the analytics definitions (module 9.23). Computed live.
 */
class FacultyDashboardService
{
    public function __construct(
        private readonly AnalyticsService $analytics,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Faculty $faculty): array
    {
        $semester = $this->analytics->defaultSemester();
        $requests = $this->countByStatus(DocumentRequest::query()->inFaculty($faculty->id));
        $internships = $this->countByStatus(Internship::query()->inFaculty($faculty->id));

        return [
            'faculty' => ['id' => $faculty->id, 'code' => $faculty->code, 'name' => $faculty->name],
            'semester' => $semester ? ['id' => $semester->id, 'name' => trim(($semester->academicYear?->code ?? '').' '.$semester->name)] : null,
            'waiting' => [
                'document_requests_pending' => $requests[DocumentRequest::STATUS_PENDING] ?? 0,
                'document_requests_approved' => $requests[DocumentRequest::STATUS_APPROVED] ?? 0,
                'internships_submitted' => $internships[Internship::STATUS_SUBMITTED] ?? 0,
                'internships_under_review' => $internships[Internship::STATUS_UNDER_REVIEW] ?? 0,
            ],
            'overview' => $this->analytics->facultyOverview($faculty->id, $semester),
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
