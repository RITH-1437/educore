<?php

namespace App\Services;

use App\Models\ErrorLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Writes a row for every HTTP 404 and 5xx response.
 *
 * ## Why a response hook and not `report()`
 *
 * Laravel keeps `HttpException` (and therefore `NotFoundHttpException`) in its
 * internal `$dontReport` list, so a 404 never reaches `Exceptions::report()`.
 * The recorder therefore hooks the *response* instead, which is the only place
 * both 404s and 5xxes are observable with their final status code.
 *
 * ## What is recorded
 *
 * Only `404` and `>= 500`. The statuses the application already handles on
 * purpose — `401`, `403`, `409`, `422` — are normal control flow: a rejected
 * login, a permission check, a business-rule conflict, a validation failure.
 * Logging those would bury the real failures in noise and train admins to
 * ignore the table.
 *
 * ## Safety
 *
 * Recording an error must never turn a handled failure into a worse one, so the
 * write is wrapped: if the database is unreachable the original response is
 * still returned and the failure is reported through the regular logger.
 *
 * Nothing secret is stored. Only the request **path** is kept — query strings
 * routinely carry tokens, and `skills/audit-logging/SKILL.md` §4 forbids
 * persisting them.
 */
class ErrorLogRecorder
{
    /**
     * Statuses that represent a genuine failure worth showing to an admin.
     *
     * @var list<int>
     */
    private const RECORDED_STATUSES = [404];

    private const SERVER_ERROR_FLOOR = 500;

    /**
     * Longest exception message kept, so one pathological message cannot bloat
     * the row or blow past the column on an enormous stack trace.
     */
    private const MAX_MESSAGE_LENGTH = 2000;

    public function isRecordable(int $status): bool
    {
        return in_array($status, self::RECORDED_STATUSES, true)
            || $status >= self::SERVER_ERROR_FLOOR;
    }

    /**
     * Record a failed response. Returns the row, or null when the status is not
     * one we record or the write itself failed.
     */
    public function record(Request $request, int $status, ?Throwable $exception = null): ?ErrorLog
    {
        if (! $this->isRecordable($status)) {
            return null;
        }

        try {
            return ErrorLog::query()->create([
                'user_id' => $request->user()?->getAuthIdentifier(),
                'status_code' => $status,
                'method' => strtoupper($request->method()),
                'url' => '/'.ltrim($request->path(), '/'),
                'route_name' => $request->route()?->getName(),
                'exception_class' => $exception === null ? null : $exception::class,
                'message' => $this->truncate($exception?->getMessage()),
                'ip_address' => $request->ip(),
                'user_agent' => $this->truncate($request->userAgent()),
                'context' => $this->context($exception),
            ]);
        } catch (Throwable $recordingFailure) {
            // Never let bookkeeping break the response the user is waiting for.
            Log::warning('Error log could not be recorded.', [
                'status_code' => $status,
                'reason' => $recordingFailure->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Small, non-sensitive diagnostics. No request body, no headers, no
     * session data — those are where secrets live.
     *
     * @return array<string, mixed>|null
     */
    private function context(?Throwable $exception): ?array
    {
        if ($exception === null) {
            return null;
        }

        return [
            'code' => (int) $exception->getCode(),
            'origin' => $exception->getFile().':'.$exception->getLine(),
        ];
    }

    private function truncate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return mb_strlen($value) > self::MAX_MESSAGE_LENGTH
            ? mb_substr($value, 0, self::MAX_MESSAGE_LENGTH).'…'
            : $value;
    }
}
