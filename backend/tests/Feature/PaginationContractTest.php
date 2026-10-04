<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Paginated index pages pass an API Resource collection, whose `links` is the
 * `{first, last, prev, next}` object; the page-number array the `Pagination`
 * component renders lives in `meta.links`. Pages read `<prop>.meta.links` —
 * this pins that contract so the control cannot silently disappear again.
 */
class PaginationContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_pages_expose_meta_links(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $pages = [
            '/users' => 'users',
            '/students' => 'students',
            '/lecturers' => 'lecturers',
            '/courses' => 'courses',
            '/programs' => 'programs',
            '/departments' => 'departments',
            '/universities' => 'universities',
            '/academic-years' => 'academicYears',
            '/offerings' => 'offerings',
            '/rooms' => 'rooms',
            '/enrollments' => 'enrollments',
            '/error-logs' => 'errorLogs',
            '/documents' => 'requests',
            '/invoices' => 'invoices',
        ];

        foreach ($pages as $url => $prop) {
            $this->actingAs($admin)->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page
                ->has("{$prop}.meta.links")
                ->where("{$prop}.links", fn ($links) => ! array_is_list((array) $links)));
        }
    }
}
