<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The campus map button on every page reads the place from the shared
 * `campus.map` prop (`docs/49_Campus-Map-Report.md`).
 */
class CampusMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_pages_share_the_default_campus_place(): void
    {
        foreach (['/', '/login'] as $url) {
            $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('campus.map.name', 'ITC Conference Hall')
                ->where('campus.map.address', 'HVCX+6F6, Russian Federation Blvd (110), Phnom Penh, Cambodia')
                ->where('campus.map.coordinates', '11.5705439,104.8986445')
                ->where('campus.map.url', fn (string $url) => str_starts_with($url, 'https://www.google.com/maps/place/ITC+Conference+Hall/')));
        }
    }

    public function test_signed_in_pages_share_the_configured_place(): void
    {
        config(['academics.campus_map' => [
            'name' => 'North Campus',
            'address' => 'Street 1, Phnom Penh',
            'coordinates' => '11.6,104.9',
            'url' => 'https://www.google.com/maps/place/North+Campus',
        ]]);

        $this->actingAs(User::factory()->superAdmin()->create())->get('/admin/dashboard')->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('campus.map.name', 'North Campus')
                ->where('campus.map.address', 'Street 1, Phnom Penh')
                ->where('campus.map.coordinates', '11.6,104.9')
                ->where('campus.map.url', 'https://www.google.com/maps/place/North+Campus'));
    }
}
