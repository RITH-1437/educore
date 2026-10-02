<?php

namespace App\Dto\User;

/**
 * Validated input for updating a user.
 *
 * An empty password means "keep the current password", so it is never part of
 * the attribute payload in that case.
 */
final readonly class UpdateUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public int $roleId,
        public ?string $password = null,
        public ?string $phone = null,
        public ?bool $isActive = null,
        public ?int $facultyId = null,
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
            password: $validated['password'] ?? null,
            phone: $validated['phone'] ?? null,
            isActive: isset($validated['is_active']) ? (bool) $validated['is_active'] : null,
            facultyId: isset($validated['faculty_id']) ? (int) $validated['faculty_id'] : null,
        );
    }

    /**
     * Attributes handed to the model layer.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        $attributes = [
            'name' => $this->name,
            'email' => $this->email,
            'role_id' => $this->roleId,
            'phone' => $this->phone,
            // Always written: a full update without `faculty_id` (or a role
            // change away from Faculty Admin) clears it.
            'faculty_id' => $this->facultyId,
        ];

        if ($this->isActive !== null) {
            $attributes['is_active'] = $this->isActive;
        }

        if ($this->password !== null && $this->password !== '') {
            $attributes['password'] = $this->password;
        }

        return $attributes;
    }
}
