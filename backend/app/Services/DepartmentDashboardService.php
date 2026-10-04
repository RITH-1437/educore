<?php

namespace App\Services;

use App\Models\Department;
use App\Models\DocumentRequest;
use App\Models\Internship;
use Illuminate\Database\Eloquent\Builder;

/**
 * Department dashboard (`docs/39_Department-Only-Structure-Report.md`, formerly
 * the faculty dashboard of report 34): the document requests and internships of
 * the department's students that wait for processing, and the department's
 * headline numbers for the current semester on the analytics definitions
 * (module 9.23). Computed live.
 */
class DepartmentDashboardService
{
    public function __construct(
        private readonly AnalyticsService $analytics,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Department $department): array
    {
        $semester = $this->analytics->defaultSemester();
        $requests = $this->countByStatus(DocumentRequest::query()->inDepartment($department->id));
        $internships = $this->countByStatus(Internship::query()->inDepartment($department->id));

        return [
            'department' => ['id' => $department->id, 'code' => $department->code, 'name' => $department->name],
            'semester' => $semester ? ['id' => $semester->id, 'name' => trim(($semester->academicYear?->code ?? '').' '.$semester->name)] : null,
            'waiting' => [
                'document_requests_pending' => $requests[DocumentRequest::STATUS_PENDING] ?? 0,
                'document_requests_approved' => $requests[DocumentRequest::STATUS_APPROVED] ?? 0,
                'internships_submitted' => $internships[Internship::STATUS_SUBMITTED] ?? 0,
                'internships_under_review' => $internships[Internship::STATUS_UNDER_REVIEW] ?? 0,
            ],
            'overview' => $this->analytics->departmentOverview($department->id, $semester),
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
