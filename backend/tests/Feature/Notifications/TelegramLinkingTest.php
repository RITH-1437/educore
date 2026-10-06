<?php

namespace Tests\Feature\Notifications;

use App\Models\AuditLog;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * One-tap Telegram linking through the bot webhook
 * (`docs/48_Class-Reminders-and-Telegram-Linking-Report.md`).
 */
class TelegramLinkingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.telegram.bot_token' => 'test-token', 'services.telegram.bot_username' => 'EduCoreBot', 'services.telegram.webhook_secret' => 'hook-secret']);
        $this->user = User::factory()->create(['name' => 'Sokha Chan']);
    }

    public function test_pressing_start_with_a_fresh_link_connects_the_chat(): void
    {
        $url = $this->actingAs($this->user)->postJson('/api/notification-preferences/telegram-link')->assertCreated()->json('data.url');
        $this->assertMatchesRegularExpression('#^https://t\.me/EduCoreBot\?start=[A-Za-z0-9]{40}$#', $url);

        $this->webhook($this->update('/start '.$this->token($url)))->assertOk()
            ->assertJsonPath('method', 'sendMessage')->assertJsonPath('chat_id', '555000111')
            ->assertJsonPath('text', fn (string $text) => str_contains($text, 'Connected') && str_contains($text, 'Sokha Chan'));

        $preference = NotificationPreference::query()->where('user_id', $this->user->id)->sole();
        $this->assertSame('555000111', $preference->telegram_chat_id);
        $this->assertTrue($preference->notify_by_telegram);
        $audit = AuditLog::query()->where('action', 'notifications.telegram_linked')->sole();
        $this->assertSame('…0111', $audit->after_values['telegram_chat']);
        $this->assertSame($this->user->id, $audit->actor_id);
    }

    public function test_a_link_works_once_and_only_in_a_private_chat(): void
    {
        $token = $this->token($this->actingAs($this->user)->postJson('/api/notification-preferences/telegram-link')->json('data.url'));

        $this->webhook($this->update("/start {$token}", type: 'group'))->assertJsonPath('text', fn ($t) => str_contains($t, 'private'));
        $this->assertNull($this->user->refresh()->preferences()->telegram_chat_id);

        $this->webhook($this->update("/start {$token}"))->assertJsonPath('text', fn ($t) => str_contains($t, 'Connected'));
        $this->webhook($this->update("/start {$token}", chatId: 777))->assertJsonPath('text', fn ($t) => str_contains($t, 'expired'));
        $this->assertSame('555000111', $this->user->refresh()->preferences()->telegram_chat_id);

        $this->webhook($this->update('/start '.str_repeat('x', 40)))->assertJsonPath('text', fn ($t) => str_contains($t, 'expired'));
        $this->webhook($this->update('/start'))->assertJsonPath('text', fn ($t) => str_contains($t, 'Connect Telegram'));
        $this->webhook(['update_id' => 1, 'edited_message' => []])->assertOk()->assertExactJson(['ok' => true]);
    }

    public function test_stop_from_the_chat_or_disconnect_in_settings_unlinks_it(): void
    {
        NotificationPreference::query()->create(['user_id' => $this->user->id, 'notify_by_telegram' => true, 'telegram_chat_id' => '555000111']);

        $this->webhook($this->update('/stop'))->assertJsonPath('text', fn ($t) => str_contains($t, 'Disconnected'));
        $this->assertNull($this->user->refresh()->preferences()->telegram_chat_id);
        $this->webhook($this->update('/stop'))->assertJsonPath('text', fn ($t) => str_contains($t, 'not linked'));

        NotificationPreference::query()->where('user_id', $this->user->id)->update(['telegram_chat_id' => '555000111']);
        $this->actingAs($this->user)->delete('/notifications/telegram')->assertSessionHas('success');
        $this->assertNull($this->user->refresh()->preferences()->telegram_chat_id);
        $this->assertSame(2, AuditLog::query()->where('action', 'notifications.telegram_unlinked')->count());
    }

    public function test_the_webhook_needs_the_shared_secret(): void
    {
        $this->postJson('/api/telegram/webhook', $this->update('/stop'))->assertForbidden();
        $this->postJson('/api/telegram/webhook', $this->update('/stop'), ['X-Telegram-Bot-Api-Secret-Token' => 'wrong'])->assertForbidden();

        config(['services.telegram.webhook_secret' => null]);
        $this->postJson('/api/telegram/webhook', $this->update('/stop'), ['X-Telegram-Bot-Api-Secret-Token' => ''])->assertNotFound();
    }

    public function test_the_settings_page_sends_people_to_telegram(): void
    {
        $this->actingAs($this->user)->get('/notifications')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('preferences.telegram_linkable', true)->where('preferences.class_reminders', true)->where('preferences.class_reminder_minutes', 30));

        $this->actingAs($this->user)->post('/notifications/telegram/link', [], ['X-Inertia' => 'true'])
            ->assertStatus(409)->assertHeader('X-Inertia-Location');

        config(['services.telegram.bot_username' => null]);
        $this->actingAs($this->user)->from('/notifications')->post('/notifications/telegram/link')->assertRedirect('/notifications')->assertSessionHas('error');
        $this->actingAs($this->user)->postJson('/api/notification-preferences/telegram-link')->assertStatus(409);
        $this->actingAs($this->user)->getJson('/api/notification-preferences')->assertJsonPath('data.telegram_linkable', false);
    }

    public function test_class_reminders_can_be_turned_off_and_links_are_rate_limited(): void
    {
        $payload = ['notify_by_email' => true, 'notify_by_telegram' => true, 'telegram_chat_id' => '555000111'];
        $this->actingAs($this->user)->putJson('/api/notification-preferences', [...$payload, 'class_reminders' => false])->assertOk()->assertJsonPath('data.class_reminders', false);
        // Older clients that do not send the toggle keep the saved value.
        $this->actingAs($this->user)->putJson('/api/notification-preferences', $payload)->assertOk()->assertJsonPath('data.class_reminders', false);
        $this->assertFalse($this->user->refresh()->preferences()->wantsClassReminders());

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->user)->postJson('/api/notification-preferences/telegram-link')->assertCreated();
        }
        $this->actingAs($this->user)->postJson('/api/notification-preferences/telegram-link')->assertStatus(429);
    }

    public function test_the_webhook_command_registers_the_secret(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        config(['app.url' => 'https://educore.example.edu']);

        $this->artisan('telegram:webhook')->expectsOutputToContain('https://educore.example.edu/api/telegram/webhook')->assertSuccessful();
        Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://api.telegram.org/bottest-token/setWebhook'
            && $request['url'] === 'https://educore.example.edu/api/telegram/webhook'
            && $request['secret_token'] === 'hook-secret' && $request['allowed_updates'] === ['message']);

        $this->artisan('telegram:webhook --delete')->assertSuccessful();
        Http::assertSent(fn (HttpRequest $request) => str_ends_with($request->url(), '/deleteWebhook'));

        config(['services.telegram.webhook_secret' => null]);
        $this->artisan('telegram:webhook')->assertFailed();
    }

    private function webhook(array $update): TestResponse
    {
        return $this->postJson('/api/telegram/webhook', $update, ['X-Telegram-Bot-Api-Secret-Token' => 'hook-secret']);
    }

    /**
     * @return array<string, mixed>
     */
    private function update(string $text, int $chatId = 555000111, string $type = 'private'): array
    {
        return ['update_id' => random_int(1, 1_000_000), 'message' => ['message_id' => 1, 'chat' => ['id' => $chatId, 'type' => $type], 'text' => $text]];
    }

    private function token(string $url): string
    {
        return substr($url, strpos($url, 'start=') + 6);
    }
}
