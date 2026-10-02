<?php

namespace App\Dto\UniversityStructure;

/**
 * Whitelisted filter, sort and pagination input for university listings.
 *
 * Built once from the request so no controller decides how filtering works and
 * no arbitrary column name ever reaches the query.
 */
final readonly class UniversityListFilters
{
    public const DEFAULT_PER_PAGE = 15;

    public const MAX_PER_PAGE = 100;

    /**
     * Columns a client may sort by — see `skills/api/SKILL.md`.
     */
    public const SORTABLE = ['id', 'code', 'name', 'created_at'];

    public function __construct(
        public ?string $search = null,
        public ?bool $isCurrent = null,
        public string $sortBy = 'name',
        public string $sortDir = 'asc',
        public int $perPage = self::DEFAULT_PER_PAGE,
    ) {}

    /**
     * Build filters from a plain input array (`search`, `filters[is_current]`,
     * `sort_by`, `sort_dir`, `per_page`).
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromInput(array $input): self
    {
        $search = trim((string) ($input['search'] ?? ''));
        $rawCurrent = $input['filters']['is_current'] ?? $input['status'] ?? $input['state'] ?? $input['is_current'] ?? null;
        if (is_string($rawCurrent)) {
            $normalized = strtolower(trim($rawCurrent));
            if (in_array($normalized, ['current', 'active', 'true', '1'], true)) {
                $rawCurrent = true;
            } elseif (in_array($normalized, ['other', 'non-current', 'inactive', 'false', '0'], true)) {
                $rawCurrent = false;
            }
        }
        $isCurrent = self::toNullableBool($rawCurrent);
        $sortBy = (string) ($input['sort_by'] ?? 'name');
        $sortDir = strtolower((string) ($input['sort_dir'] ?? 'asc'));
        $perPage = (int) ($input['per_page'] ?? 0);

        return new self(
            search: $search === '' ? null : $search,
            isCurrent: $isCurrent,
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

        if ($this->isCurrent !== null) {
            $query['status'] = $this->isCurrent ? 'current' : 'other';
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
