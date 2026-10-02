<?php

namespace App\Dto\User;

/**
 * Filter and pagination input for user listings.
 *
 * Built once from the request (HTTP) or from a console command, then passed
 * down to the repository so no controller has to know how filtering works.
 */
final readonly class UserListFilters
{
    public const DEFAULT_PER_PAGE = 15;

    public const MAX_PER_PAGE = 100;

    public function __construct(
        public ?string $search = null,
        public ?int $roleId = null,
        public ?string $role = null,
        public int $perPage = self::DEFAULT_PER_PAGE,
    ) {}

    /**
     * Build filters from a plain input array (`search`, `role_id`, `role`, `per_page`).
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromInput(array $input): self
    {
        $search = trim((string) ($input['search'] ?? ''));
        $roleId = (int) ($input['role_id'] ?? 0);
        $role = trim((string) ($input['role'] ?? ''));
        $perPage = (int) ($input['per_page'] ?? 0);

        return new self(
            search: $search === '' ? null : $search,
            roleId: $roleId > 0 ? $roleId : null,
            role: $role === '' ? null : $role,
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
        return array_filter([
            'search' => $this->search,
            'role_id' => $this->roleId,
            'role' => $this->role,
            'per_page' => $this->perPage !== self::DEFAULT_PER_PAGE ? $this->perPage : null,
        ], fn ($value) => $value !== null);
    }
}
