<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * The single way to write the audit trail (module 9.24,
 * `skills/audit-logging`). Services call it explicitly at sensitive points,
 * inside their own transaction — a rolled-back change leaves no audit entry,
 * a committed one always has one.
 *
 * - Actor: the signed-in user unless given; IP and user agent from the
 *   current HTTP request (none in queued jobs / commands).
 * - Snapshots hold only the attributes that matter, never secrets: anything
 *   in a model's `$hidden`, passwords, tokens and storage keys are stripped.
 * - Action names are dotted: `<subject>.<verb>` (e.g. `grades.approved`).
 */
class AuditLogger
{
    /** Keys never written to a snapshot, whatever the model. */
    private const SECRET_KEYS = ['password', 'password_confirmation', 'remember_token', 'token', 'verification_token', 'file_key', 'storage_key', 'api_token', 'two_factor_secret'];

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function record(string $action, ?Model $target = null, array $before = [], array $after = [], ?string $description = null, ?User $actor = null): AuditLog
    {
        $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();

        return AuditLog::query()->create([
            'actor_id' => ($actor ?? auth()->user())?->getKey(),
            'action' => $action,
            'description' => $description === null ? null : Str::limit($description, 1000),
            'auditable_type' => $target?->getMorphClass(),
            'auditable_id' => $target?->getKey(),
            'before_values' => $this->clean($before, $target) ?: null,
            'after_values' => $this->clean($after, $target) ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent() ? Str::limit($request->userAgent(), 500, '') : null,
        ]);
    }

    /**
     * Record only what changed on a model since `$original` (its attributes
     * before the change), e.g. `$logger->changes('user.updated', $user, $before)`.
     *
     * @param  array<string, mixed>  $original
     */
    public function changes(string $action, Model $model, array $original, ?string $description = null): ?AuditLog
    {
        $current = $model->getAttributes();
        $keys = array_keys(array_filter($current, fn ($value, $key) => ! in_array($key, ['updated_at', 'created_at'], true)
            && ($original[$key] ?? null) != $value, ARRAY_FILTER_USE_BOTH));

        if ($keys === []) {
            return null;
        }

        // A secret that changed is recorded as a fact ("password changed"),
        // its values are stripped by clean().
        $secrets = array_values(array_intersect($keys, [...self::SECRET_KEYS, ...$model->getHidden()]));

        if ($secrets !== []) {
            $description = trim(($description ? $description.' ' : '').'Changed: '.implode(', ', $secrets).'.');
        }

        return $this->record($action, $model, Arr::only($original, $keys), Arr::only($current, $keys), $description);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function clean(array $values, ?Model $target): array
    {
        $hidden = $target?->getHidden() ?? [];

        return array_filter(
            $values,
            fn ($key) => ! in_array($key, self::SECRET_KEYS, true) && ! in_array($key, $hidden, true),
            ARRAY_FILTER_USE_KEY,
        );
    }
}
