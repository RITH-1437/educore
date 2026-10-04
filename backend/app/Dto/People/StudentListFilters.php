<?php

namespace App\Dto\People;

use App\Models\Student;

/**
 * Whitelisted filter, sort and pagination input for student listings.
 *
 * Department and program filters both go through the student's
 * *current* (active) program.
 */
final readonly class StudentListFilters
{
    public const DEFAULT_PER_PAGE = 15;

    public const MAX_PER_PAGE = 100;

    /** Columns a client may sort by — see `skills/api/SKILL.md`. */
    public const SORTABLE = ['id', 'student_number', 'first_name', 'last_name', 'enrollment_date', 'status', 'created_at'];

    public function __construct(
        public ?string $search = null,
        public ?int $departmentId = null,
        public ?int $programId = null,
        public ?string $status = null,
        public string $sortBy = 'student_number',
        public string $sortDir = 'asc',
        public int $perPage = self::DEFAULT_PER_PAGE,
    ) {}

    /**
     * @param  array<string, mixed>  $input  `search`, `filters[department_id|program_id|status]`, `sort_by`, `sort_dir`, `per_page`
     */
    public static function fromInput(array $input): self
    {
        $filters = is_array($input['filters'] ?? null) ? $input['filters'] : [];
        $search = trim((string) ($input['search'] ?? ''));
        $sortBy = (string) ($input['sort_by'] ?? 'student_number');
        $sortDir = strtolower((string) ($input['sort_dir'] ?? 'asc'));
        $perPage = (int) ($input['per_page'] ?? 0);
        $status = (string) ($filters['status'] ?? '');

        return new self(
            search: $search === '' ? null : $search,
            departmentId: self::toNullableInt($filters['department_id'] ?? null),
            programId: self::toNullableInt($filters['program_id'] ?? null),
            status: in_array($status, Student::STATUSES, true) ? $status : null,
            sortBy: in_array($sortBy, self::SORTABLE, true) ? $sortBy : 'student_number',
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
            'program_id' => $this->programId,
            'status' => $this->status,
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
}
