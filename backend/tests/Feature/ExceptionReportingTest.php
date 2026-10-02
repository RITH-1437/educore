<?php

namespace Tests\Feature;

use App\Exceptions\BusinessRuleException;
use App\Models\Room;
use App\Models\ScheduleEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use RuntimeException;
use Tests\TestCase;

/**
 * Business-rule refusals are answered (409) but never reported to the log;
 * genuine failures still are.
 */
class ExceptionReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_rule_refusals_are_not_reported(): void
    {
        Exceptions::fake();
        $room = Room::factory()->create();
        ScheduleEntry::factory()->create(['room_id' => $room->id]);

        $this->actingAs(User::factory()->superAdmin()->create())->deleteJson("/api/rooms/{$room->id}")->assertStatus(409);

        Exceptions::assertNotReported(BusinessRuleException::class);
    }

    public function test_unexpected_exceptions_are_still_reported(): void
    {
        Exceptions::fake();

        report(new RuntimeException('Something broke'));

        Exceptions::assertReported(RuntimeException::class);
    }
}
