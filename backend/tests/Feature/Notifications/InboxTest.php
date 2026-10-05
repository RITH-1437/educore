<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\EduCoreNotification;
use App\Notifications\PasswordChanged;
use App\Notifications\ResetPasswordLink;
use App\Notifications\TestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use ReflectionClass;
use Tests\TestCase;

/**
 * In-app notification inbox (`docs/42_In-App-Notification-Inbox-Report.md`):
 * every EduCore notification is also stored for the recipient, who reads and
 * marks only their own messages, by API and on the `/inbox` page.
 */
class InboxTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $other;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-05 09:00:00');
        $this->user = User::factory()->create();
        $this->other = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // -------------------------------------------------------------- storing ---

    public function test_notifications_are_stored_in_the_recipients_inbox(): void
    {
        $this->user->notify(new TestNotification);
        $this->user->notify(new PasswordChanged('reset'));

        $stored = $this->user->notifications()->get();
        $this->assertCount(2, $stored);
        $this->assertTrue(Str::isUuid($stored->first()->id));
        $this->assertEqualsCanonicalizing(
            [
                ['kind' => 'general', 'title' => 'EduCore test notification', 'body' => 'Your in-app notifications are working.', 'url' => '/notifications'],
                ['kind' => 'security', 'title' => 'Your password was reset', 'url' => null],
            ],
            $stored->map(fn (DatabaseNotification $n) => $n->type === PasswordChanged::class ? array_diff_key($n->data, ['body' => 1]) : $n->data)->all(),
        );
        $this->assertSame(0, $this->other->notifications()->count());
    }

    public function test_reset_links_and_inactive_accounts_never_reach_the_inbox(): void
    {
        $this->user->notify(new ResetPasswordLink('secret-reset-token'));
        $this->assertSame(0, DatabaseNotification::query()->count());

        $this->user->update(['is_active' => false]);
        $this->user->refresh()->notify(new TestNotification);
        $this->assertSame(0, DatabaseNotification::query()->count());
    }

    /** A new notification class must give the inbox a message (or opt out like the reset link). */
    public function test_every_notification_has_an_inbox_message(): void
    {
        foreach (glob(app_path('Notifications/*.php')) as $file) {
            $class = 'App\\Notifications\\'.basename($file, '.php');
            $reflection = new ReflectionClass($class);
            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(EduCoreNotification::class) || $class === ResetPasswordLink::class) {
                continue;
            }

            $this->assertTrue($reflection->hasMethod('toInbox'), "{$class} has no toInbox() message.");
        }
    }

    // ------------------------------------------------------------------ API ---

    public function test_api_lists_only_my_notifications_newest_first_with_unread_count(): void
    {
        // Guests first: `actingAs` lasts for the rest of the test.
        $this->getJson('/api/notifications')->assertUnauthorized();
        $old = $this->message($this->user, 'Older', now()->subDay(), read: true);
        $new = $this->message($this->user, 'Newer', now()->subMinute());
        $this->message($this->other, 'Not mine');

        $this->actingAs($this->user)->getJson('/api/notifications')->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $new->id)
            ->assertJsonPath('data.0.title', 'Newer')
            ->assertJsonPath('data.0.kind', 'general')
            ->assertJsonPath('data.0.url', '/my-grades')
            ->assertJsonPath('data.0.read_at', null)
            ->assertJsonPath('data.1.id', $old->id)
            ->assertJsonPath('meta.unread_count', 1)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonMissingPath('data.0.type');

        $this->actingAs($this->user)->getJson('/api/notifications?filters[status]=unread')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $new->id);
        $this->actingAs($this->user)->getJson('/api/notifications?filters[status]=read')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $old->id)->assertJsonPath('meta.unread_count', 1);
        $this->actingAs($this->user)->getJson('/api/notifications?filters[status]=archived')->assertJsonValidationErrors('filters.status');
        $this->actingAs($this->user)->getJson('/api/notifications?per_page=500')->assertJsonValidationErrors('per_page');
    }

    public function test_api_marks_my_notifications_read_and_never_anyone_elses(): void
    {
        // Guests first: `actingAs` lasts for the rest of the test.
        $this->postJson('/api/notifications/read-all')->assertUnauthorized();
        $mine = $this->message($this->user, 'Mine');
        $second = $this->message($this->user, 'Also mine');
        $theirs = $this->message($this->other, 'Theirs');

        $this->actingAs($this->user)->postJson("/api/notifications/{$mine->id}/read")->assertOk()
            ->assertJsonPath('data.id', $mine->id)->assertJsonPath('data.read_at', now()->toISOString());
        $this->assertNotNull($mine->fresh()->read_at);

        // Someone else's id, or not an id at all, is simply not found.
        $this->actingAs($this->user)->postJson("/api/notifications/{$theirs->id}/read")->assertNotFound();
        $this->actingAs($this->user)->postJson('/api/notifications/123/read')->assertNotFound();
        $this->assertNull($theirs->fresh()->read_at);

        $this->actingAs($this->user)->postJson('/api/notifications/read-all')->assertOk()->assertJsonPath('marked', 1);
        $this->assertNotNull($second->fresh()->read_at);
        $this->assertNull($theirs->fresh()->read_at);
    }

    // ------------------------------------------------------------------ web ---

    public function test_inbox_page_and_bell_count(): void
    {
        // Guests first: `actingAs` lasts for the rest of the test.
        $this->get('/inbox')->assertRedirect('/login');
        $this->message($this->user, 'Unread one');
        $this->message($this->user, 'Read one', now()->subHour(), read: true);

        $this->actingAs($this->user)->get('/inbox')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Notifications/Inbox')
                ->has('notifications.data', 2)
                ->where('filters.status', null)
                ->where('auth.unread_notifications', 1));

        $this->actingAs($this->user)->get('/inbox?filters[status]=unread')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('notifications.data', 1)->where('notifications.data.0.title', 'Unread one')->where('filters.status', 'unread'));

        // Every page carries the bell count.
        $this->actingAs($this->other)->get('/notifications')->assertInertia(fn (Assert $page) => $page->where('auth.unread_notifications', 0));
    }

    public function test_opening_a_message_marks_it_read_and_follows_only_in_app_links(): void
    {
        $message = $this->message($this->user, 'Grade published');
        $this->actingAs($this->user)->post("/inbox/{$message->id}/open")->assertRedirect('/my-grades');
        $this->assertNotNull($message->fresh()->read_at);

        foreach (['//evil.example/phish', 'https://evil.example', '/\\evil.example', null] as $url) {
            $odd = $this->message($this->user, 'Odd link', url: $url);
            $this->actingAs($this->user)->post("/inbox/{$odd->id}/open")->assertRedirect('/inbox');
        }

        $theirs = $this->message($this->other, 'Theirs');
        $this->actingAs($this->user)->post("/inbox/{$theirs->id}/open")->assertNotFound();
        $this->actingAs($this->user)->post("/inbox/{$theirs->id}/read")->assertNotFound();
        $this->assertNull($theirs->fresh()->read_at);
    }

    public function test_web_mark_read_and_mark_all(): void
    {
        $first = $this->message($this->user, 'First');
        $this->message($this->user, 'Second');
        $this->message($this->user, 'Third');

        $this->actingAs($this->user)->from('/inbox')->post("/inbox/{$first->id}/read")->assertRedirect('/inbox');
        $this->assertNotNull($first->fresh()->read_at);

        $this->actingAs($this->user)->from('/inbox')->post('/inbox/read-all')->assertRedirect('/inbox')->assertSessionHas('success', '2 notifications marked as read.');
        $this->assertSame(0, $this->user->unreadNotifications()->count());
    }

    // ------------------------------------------------------------ retention ---

    public function test_prune_deletes_messages_past_the_retention_period(): void
    {
        $this->message($this->user, 'Ancient', now()->subDays(181));
        $kept = $this->message($this->user, 'Recent', now()->subDays(179));

        $this->artisan('notifications:prune')->expectsOutput('1 notification(s) deleted.')->assertSuccessful();
        $this->assertSame([$kept->id], DatabaseNotification::query()->pluck('id')->all());

        $this->artisan('notifications:prune', ['--days' => 30])->expectsOutput('1 notification(s) deleted.')->assertSuccessful();
        $this->assertSame(0, DatabaseNotification::query()->count());
    }

    private function message(User $user, string $title, ?Carbon $at = null, bool $read = false, ?string $url = '/my-grades'): DatabaseNotification
    {
        $at ??= now();

        return $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => TestNotification::class,
            'data' => ['kind' => 'general', 'title' => $title, 'body' => "{$title} body", 'url' => $url],
            'read_at' => $read ? $at : null,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }
}
