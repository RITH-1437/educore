<?php

namespace App\Services;

use App\Dto\ErrorLogs\ErrorLogListFilters;
use App\Models\ErrorLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only queries over `error_logs`.
 *
 * There is deliberately no create/update/delete method: rows are produced by
 * `ErrorLogRecorder` and are append-only, exactly like `audit_logs`
 * (`skills/audit-logging/SKILL.md` §4).
 */
class ErrorLogService
{
    public function paginate(ErrorLogListFilters $filters): LengthAwarePaginator
    {
        return $this->query($filters)
            ->with('user:id,name,email')
            ->orderBy($filters->sortBy, $filters->sortDir)
            // Stable tiebreak so two rows with the same timestamp never swap
            // places between pages.
            ->orderBy('id', $filters->sortDir)
            ->paginate($filters->perPage);
    }

    public function find(int $errorLog): ErrorLog
    {
        return ErrorLog::query()
            ->with('user:id,name,email')
            ->findOrFail($errorLog);
    }

    /**
     * Counts shown on the index header, computed over the whole table rather
     * than the current page.
     *
     * @return array{server_errors: int, not_found: int, total: int}
     */
    public function summary(): array
    {
        return [
            'server_errors' => ErrorLog::query()->where('status_code', '>=', 500)->count(),
            'not_found' => ErrorLog::query()->where('status_code', 404)->count(),
            'total' => ErrorLog::query()->count(),
        ];
    }

    private function query(ErrorLogListFilters $filters): Builder
    {
        return ErrorLog::query()
            ->when($filters->search !== null, fn (Builder $query) => $query->where(
                fn (Builder $inner) => $inner
                    ->where('url', 'ilike', '%'.$filters->search.'%')
                    ->orWhere('message', 'ilike', '%'.$filters->search.'%')
                    ->orWhere('exception_class', 'ilike', '%'.$filters->search.'%')
            ))
            ->when($filters->statusCode !== null, fn (Builder $query) => $query->where('status_code', $filters->statusCode))
            ->when(
                $filters->statusGroup === ErrorLogListFilters::GROUP_SERVER,
                fn (Builder $query) => $query->where('status_code', '>=', 500)
            )
            ->when(
                $filters->statusGroup === ErrorLogListFilters::GROUP_NOT_FOUND,
                fn (Builder $query) => $query->where('status_code', 404)
            )
            ->when($filters->method !== null, fn (Builder $query) => $query->where('method', $filters->method));
    }
}
