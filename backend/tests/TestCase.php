<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    /**
     * Safety net for `RefreshDatabase`: never wipe a database that is not a
     * dedicated test database.
     *
     * When the configuration is cached (`php artisan optimize`), the
     * `DB_DATABASE=educore_test` override in phpunit.xml is ignored and the
     * suite would otherwise reset the development database.
     *
     * @return array<class-string, class-string>
     */
    protected function setUpTraits()
    {
        $connection = config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if (! str_ends_with($database, '_test') && $database !== ':memory:') {
            throw new RuntimeException(
                "Refusing to refresh database [{$database}]: tests must run against a *_test database. "
                .'If the configuration is cached, run `php artisan optimize:clear` first.'
            );
        }

        return parent::setUpTraits();
    }
}
