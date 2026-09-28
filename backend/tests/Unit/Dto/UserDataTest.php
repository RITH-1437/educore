<?php

namespace Tests\Unit\Dto;

use App\Dto\User\UpdateUserData;
use App\Dto\User\UserListFilters;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserDataTest extends TestCase
{
    #[Test]
    public function it_applies_defaults_and_caps_the_page_size(): void
    {
        $filters = UserListFilters::fromInput([]);

        $this->assertNull($filters->search);
        $this->assertNull($filters->roleId);
        $this->assertSame(UserListFilters::DEFAULT_PER_PAGE, $filters->perPage);

        $capped = UserListFilters::fromInput(['search' => '  ', 'role_id' => '0', 'per_page' => '500']);

        $this->assertNull($capped->search);
        $this->assertNull($capped->roleId);
        $this->assertSame(UserListFilters::MAX_PER_PAGE, $capped->perPage);
    }

    #[Test]
    public function it_keeps_the_current_password_when_none_is_submitted(): void
    {
        $data = UpdateUserData::fromValidated([
            'name' => 'Sokha',
            'email' => 'sokha@test.test',
            'role_id' => 3,
        ]);

        $this->assertArrayNotHasKey('password', $data->toAttributes());
        $this->assertArrayNotHasKey('is_active', $data->toAttributes());
        $this->assertSame('Sokha', $data->toAttributes()['name']);
    }

    #[Test]
    public function it_applies_a_submitted_password(): void
    {
        $data = UpdateUserData::fromValidated([
            'name' => 'Sokha',
            'email' => 'sokha@test.test',
            'role_id' => 3,
            'password' => 'new-password',
            'is_active' => false,
        ]);

        $this->assertSame('new-password', $data->toAttributes()['password']);
        $this->assertFalse($data->toAttributes()['is_active']);
    }
}
