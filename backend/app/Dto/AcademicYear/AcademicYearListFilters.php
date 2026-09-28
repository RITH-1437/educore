<?php

namespace App\Dto\AcademicYear;

/**
 * Whitelisted filter, sort and pagination input for academic year listings.
 *
 * Built once from the request so no controller decides how filtering works and
 * no arbitrary column name ever reaches the query.
 */
final readonly class AcademicYearListFilters
{
    public const DEFAULT_PER_PAGE = 15;

    public const MAX_PER_PAGE = 100;

    /**
     * Columns a client may sort by — see `skills/api/SKILL.md`.
     */
    public const SORTABLE = ['id', 'code', 'name', 'start_date', 'end_date', 'status'];

    public function __construct(
        public ?string $search = null,
        public ?string $status = null,
        public string $sortBy = 'start_date',
        public string $sortDir = 'desc',
        public int $perPage = self::DEFAULT_PER_PAGE,
    ) {}

    /**
     * Build filters from a plain input array (`search`, `filters[status]`,
     * `sort_by`, `sort_dir`, `per_page`).
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromInput(array $input): self
    {
        $search = trim((string) ($input['search'] ?? ''));
        $status = trim((string) (is_array($input['filters'] ?? null) ? ($input['filters']['status'] ?? '') : ''));
        $sortBy = (string) ($input['sort_by'] ?? 'start_date');
        $sortDir = strtolower((string) ($input['sort_dir'] ?? 'desc'));
        $perPage = (int) ($input['per_page'] ?? 0);

        return new self(
            search: $search === '' ? null : $search,
            status: $status === '' ? null : $status,
            sortBy: in_array($sortBy, self::SORTABLE, true) ? $sortBy : 'start_date',
            sortDir: in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'desc',
            perPage: min(max($perPage ?: self::DEFAULT_PER_PAGE, 1), self::MAX_PER_PAGE),
        );
    }

    /**
     * Query string parameters for pagination links.
     *
     * @return array<string, string>
     */
    public function toQueryString(): array
    {
        $query = array_filter([
            'search' => $this->search,
            'per_page' => $this->perPage !== self::DEFAULT_PER_PAGE ? $this->perPage : null,
        ], fn ($value) => $value !== null);

        if ($this->status !== null) {
            $query['filters'] = ['status' => $this->status];
        }

        return $query;
    }
}
