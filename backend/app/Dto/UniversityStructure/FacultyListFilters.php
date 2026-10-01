<?php

namespace App\Dto\UniversityStructure;

/**
 * Whitelisted filter, sort and pagination input for faculty listings.
 *
 * `facultyId` scopes the list to one faculty when the client is browsing
 * departments inline; it never bypasses authorization — the policy is checked
 * by the controller before the service runs.
 */
final readonly class FacultyListFilters
{
    public const DEFAULT_PER_PAGE = 15;

    public const MAX_PER_PAGE = 100;

    /**
     * Columns a client may sort by — see `skills/api/SKILL.md`.
     */
    public const SORTABLE = ['id', 'code', 'name', 'created_at'];

    public function __construct(
        public ?string $search = null,
        public ?int $universityId = null,
        public ?bool $isActive = null,
        public string $sortBy = 'name',
        public string $sortDir = 'asc',
        public int $perPage = self::DEFAULT_PER_PAGE,
    ) {}

    /**
     * Build filters from a plain input array (`search`, `filters[university_id]`,
     * `filters[is_active]`, `sort_by`, `sort_dir`, `per_page`).
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromInput(array $input): self
    {
        $search = trim((string) ($input['search'] ?? ''));
        $universityId = $input['filters']['university_id'] ?? null;
        $isActive = $input['filters']['is_active'] ?? null;
        $sortBy = (string) ($input['sort_by'] ?? 'name');
        $sortDir = strtolower((string) ($input['sort_dir'] ?? 'asc'));
        $perPage = (int) ($input['per_page'] ?? 0);

        return new self(
            search: $search === '' ? null : $search,
            universityId: ($universityId === null || $universityId === '')
                ? null
                : (int) $universityId,
            isActive: self::toNullableBool($isActive),
            sortBy: in_array($sortBy, self::SORTABLE, true) ? $sortBy : 'name',
            sortDir: in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'asc',
            perPage: min(max($perPage ?: self::DEFAULT_PER_PAGE, 1), self::MAX_PER_PAGE),
        );
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

        if ($this->universityId !== null) {
            $filters['university_id'] = $this->universityId;
        }

        if ($this->isActive !== null) {
            $filters['is_active'] = $this->isActive ? '1' : '0';
        }

        if ($filters !== []) {
            $query['filters'] = $filters;
        }

        return $query;
    }

    private static function toNullableBool(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? null;
    }
}
