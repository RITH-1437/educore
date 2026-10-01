<?php

namespace App\Dto\ErrorLogs;

use Illuminate\Http\Request;

/**
 * Whitelisted filter, sort and pagination input for error-log listings.
 *
 * `statusGroup` mirrors the two ways an admin actually thinks about this table:
 * "everything that broke" (`server`) versus "pages people could not find"
 * (`not_found`). It never bypasses authorization — `ErrorLogPolicy` is checked
 * by the controller before the service runs.
 */
final readonly class ErrorLogListFilters
{
    public const DEFAULT_PER_PAGE = 20;

    public const MAX_PER_PAGE = 100;

    /**
     * Columns a client may sort by — see `skills/api/SKILL.md`.
     */
    public const SORTABLE = ['id', 'status_code', 'method', 'created_at'];

    public const GROUP_SERVER = 'server';

    public const GROUP_NOT_FOUND = 'not_found';

    public function __construct(
        public ?string $search = null,
        public ?int $statusCode = null,
        public ?string $statusGroup = null,
        public ?string $method = null,
        public string $sortBy = 'created_at',
        public string $sortDir = 'desc',
        public int $perPage = self::DEFAULT_PER_PAGE,
    ) {}

    /**
     * Build filters from a plain input array (`search`, `filters[status_code]`,
     * `filters[status_group]`, `filters[method]`, `sort_by`, `sort_dir`,
     * `per_page`).
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromInput(array $input): self
    {
        $search = trim((string) ($input['search'] ?? ''));
        $statusCode = $input['filters']['status_code'] ?? null;
        $statusGroup = (string) ($input['filters']['status_group'] ?? '');
        $method = strtoupper(trim((string) ($input['filters']['method'] ?? '')));
        $sortBy = (string) ($input['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($input['sort_dir'] ?? 'desc'));
        $perPage = (int) ($input['per_page'] ?? 0);

        return new self(
            search: $search === '' ? null : $search,
            statusCode: ($statusCode === null || $statusCode === '')
                ? null
                : (int) $statusCode,
            statusGroup: in_array($statusGroup, [self::GROUP_SERVER, self::GROUP_NOT_FOUND], true)
                ? $statusGroup
                : null,
            method: in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)
                ? $method
                : null,
            sortBy: in_array($sortBy, self::SORTABLE, true) ? $sortBy : 'created_at',
            sortDir: in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'desc',
            perPage: min(max($perPage ?: self::DEFAULT_PER_PAGE, 1), self::MAX_PER_PAGE),
        );
    }

    public static function fromRequest(Request $request): self
    {
        return self::fromInput($request->query());
    }

    /**
     * Query string parameters for pagination links.
     *
     * @return array<string, mixed>
     */
    public function toQueryString(): array
    {
        $query = array_filter([
            'search' => $this->search,
            'per_page' => $this->perPage !== self::DEFAULT_PER_PAGE ? $this->perPage : null,
        ], fn ($value) => $value !== null);

        $filters = [];

        if ($this->statusCode !== null) {
            $filters['status_code'] = $this->statusCode;
        }

        if ($this->statusGroup !== null) {
            $filters['status_group'] = $this->statusGroup;
        }

        if ($this->method !== null) {
            $filters['method'] = $this->method;
        }

        if ($filters !== []) {
            $query['filters'] = $filters;
        }

        return $query;
    }
}
