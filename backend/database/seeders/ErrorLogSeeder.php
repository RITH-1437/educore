<?php

namespace Database\Seeders;

use App\Models\ErrorLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Seeds a realistic slice of recorded failures so the Error Logs screens are not
 * empty on a fresh install.
 *
 * Deliberately mixed: mostly 404s (stale links, wrong IDs) plus a handful of 5xx
 * that carry an exception class and message, since those are the rows an admin
 * actually reads. Attributed rows reuse existing Super Admin / University Admin
 * accounts so the "who hit it" column is populated.
 *
 * Idempotent: keyed on `(status_code, url)`, so re-running `db:seed` refreshes
 * timestamps rather than duplicating rows.
 */
class ErrorLogSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::query()->whereHas('role', fn ($query) => $query->where('slug', 'super-admin'))->first();
        $universityAdmin = User::query()->whereHas('role', fn ($query) => $query->where('slug', 'university-admin'))->first();

        $now = CarbonImmutable::now();

        $rows = [
            // 404s — anonymous, the most common shape.
            [
                'status_code' => 404,
                'method' => 'GET',
                'url' => '/faculties/9999',
                'exception_class' => null,
                'message' => null,
                'user' => null,
                'minutes_ago' => 12,
                'ip_address' => '203.0.113.24',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/141.0 Safari/537.36',
            ],
            [
                'status_code' => 404,
                'method' => 'GET',
                'url' => '/departments/4412',
                'exception_class' => null,
                'message' => null,
                'user' => null,
                'minutes_ago' => 47,
                'ip_address' => '203.0.113.88',
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_1 like Mac OS X) Safari/605.1',
            ],
            [
                'status_code' => 404,
                'method' => 'GET',
                'url' => '/academic-years/2020',
                'exception_class' => null,
                'message' => null,
                'user' => $universityAdmin,
                'minutes_ago' => 95,
                'ip_address' => '198.51.100.12',
                'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) Chrome/140.0 Safari/537.36',
            ],
            [
                'status_code' => 404,
                'method' => 'POST',
                'url' => '/universities',
                'exception_class' => null,
                'message' => null,
                'user' => $superAdmin,
                'minutes_ago' => 180,
                'ip_address' => '198.51.100.5',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Firefox/143.0',
            ],
            [
                'status_code' => 404,
                'method' => 'GET',
                'url' => '/reports/enrollment-2024',
                'exception_class' => null,
                'message' => null,
                'user' => null,
                'minutes_ago' => 320,
                'ip_address' => '203.0.113.201',
                'user_agent' => 'curl/8.4.0',
            ],
            [
                'status_code' => 404,
                'method' => 'GET',
                'url' => '/error-logs',
                'exception_class' => null,
                'message' => null,
                'user' => null,
                'minutes_ago' => 410,
                'ip_address' => '192.0.2.66',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_6) Safari/605.1',
            ],

            // 5xx — these are the rows worth diagnosing.
            [
                'status_code' => 500,
                'method' => 'GET',
                'url' => '/admin/dashboard',
                'exception_class' => 'Illuminate\\Database\\QueryException',
                'message' => 'SQLSTATE[42P01]: Undefined table: enrollments. Connection: pgsql. SQL: select count(*) from "enrollments"',
                'user' => $superAdmin,
                'minutes_ago' => 8,
                'ip_address' => '198.51.100.5',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/141.0 Safari/537.36',
            ],
            [
                'status_code' => 500,
                'method' => 'POST',
                'url' => '/faculties/1/departments',
                'exception_class' => 'Illuminate\\Validation\\ValidationException',
                'message' => 'The given data was invalid.',
                'user' => $universityAdmin,
                'minutes_ago' => 140,
                'ip_address' => '198.51.100.12',
                'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) Chrome/140.0 Safari/537.36',
            ],
            [
                'status_code' => 503,
                'method' => 'GET',
                'url' => '/academic-years',
                'exception_class' => 'Illuminate\\Database\\QueryException',
                'message' => 'SQLSTATE[08006]: Connection failure: could not connect to server: Connection refused',
                'user' => $superAdmin,
                'minutes_ago' => 260,
                'ip_address' => '198.51.100.5',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/141.0 Safari/537.36',
            ],
            [
                'status_code' => 500,
                'method' => 'GET',
                'url' => '/users/42',
                'exception_class' => 'RuntimeException',
                'message' => 'Profile avatar storage is not configured.',
                'user' => $superAdmin,
                'minutes_ago' => 400,
                'ip_address' => '198.51.100.5',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/141.0 Safari/537.36',
            ],
            [
                'status_code' => 502,
                'method' => 'GET',
                'url' => '/api/faculties',
                'exception_class' => 'Symfony\\Component\\HttpClient\\Exception\\TransportException',
                'message' => 'HTTP/2 502 received from upstream service.',
                'user' => null,
                'minutes_ago' => 520,
                'ip_address' => '192.0.2.14',
                'user_agent' => 'axios/1.7.7',
            ],
        ];

        foreach ($rows as $row) {
            $user = $row['user'];
            $createdAt = $now->subMinutes($row['minutes_ago']);

            ErrorLog::query()->updateOrCreate(
                ['status_code' => $row['status_code'], 'url' => $row['url']],
                [
                    'user_id' => $user?->id,
                    'method' => $row['method'],
                    'route_name' => null,
                    'exception_class' => $row['exception_class'],
                    'message' => $row['message'],
                    'ip_address' => $row['ip_address'],
                    'user_agent' => $row['user_agent'],
                    'context' => null,
                    'created_at' => $createdAt,
                ],
            );
        }
    }
}
