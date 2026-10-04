<?php

namespace App\Dto\User;

/**
 * Validated input for creating a user.
 */
final readonly class CreateUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public int $roleId,
        public string $password,
        public ?string $phone = null,
        public bool $isActive = true,
        public ?int $departmentId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            name: (string) $validated['name'],
            email: (string) $validated['email'],
            roleId: (int) $validated['role_id'],
            password: (string) $validated['password'],
            phone: $validated['phone'] ?? null,
            isActive: (bool) ($validated['is_active'] ?? true),
            departmentId: isset($validated['department_id']) ? (int) $validated['department_id'] : null,
        );
    }

    /**
     * Attributes handed to the model layer.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'role_id' => $this->roleId,
            'password' => $this->password,
            'phone' => $this->phone,
            'is_active' => $this->isActive,
            'department_id' => $this->departmentId,
        ];
    }
}
