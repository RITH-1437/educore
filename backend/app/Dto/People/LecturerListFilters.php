<?php

namespace App\Dto\People;

use App\Models\Lecturer;

/**
 * Whitelisted filter, sort and pagination input for lecturer listings.
 */
final readonly class LecturerListFilters
{
    public const DEFAULT_PER_PAGE = 15;

    public const MAX_PER_PAGE = 100;

    /** Columns a client may sort by — see `skills/api/SKILL.md`. */
    public const SORTABLE = ['id', 'staff_number', 'first_name', 'last_name', 'employment_type', 'created_at'];

    public function __construct(
        public ?string $search = null,
        public ?int $departmentId = null,
        public ?string $employmentType = null,
        public ?bool $isActive = null,
        public string $sortBy = 'last_name',
        public string $sortDir = 'asc',
        public int $perPage = self::DEFAULT_PER_PAGE,
    ) {}

    /**
     * @param  array<string, mixed>  $input  `search`, `filters[department_id|employment_type|is_active]`, `sort_by`, `sort_dir`, `per_page`
     */
    public static function fromInput(array $input): self
    {
        $filters = is_array($input['filters'] ?? null) ? $input['filters'] : [];
        $search = trim((string) ($input['search'] ?? ''));
        $sortBy = (string) ($input['sort_by'] ?? 'last_name');
        $sortDir = strtolower((string) ($input['sort_dir'] ?? 'asc'));
        $perPage = (int) ($input['per_page'] ?? 0);
        $type = (string) ($filters['employment_type'] ?? '');

        return new self(
            search: $search === '' ? null : $search,
            departmentId: self::toNullableInt($filters['department_id'] ?? null),
            employmentType: in_array($type, Lecturer::EMPLOYMENT_TYPES, true) ? $type : null,
            isActive: self::toNullableBool($filters['is_active'] ?? null),
            sortBy: in_array($sortBy, self::SORTABLE, true) ? $sortBy : 'last_name',
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

        $filters = array_filter([
            'department_id' => $this->departmentId,
            'employment_type' => $this->employmentType,
            'is_active' => $this->isActive === null ? null : ($this->isActive ? '1' : '0'),
        ], fn ($value) => $value !== null);

        if ($filters !== []) {
            $query['filters'] = $filters;
        }

        return $query;
    }

    private static function toNullableInt(mixed $value): ?int
    {
        return ($value === null || $value === '') ? null : (int) $value;
    }

    private static function toNullableBool(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? null;
    }
}
