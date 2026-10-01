<?php

namespace App\Models;

use Database\Factories\ErrorLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One HTTP 404 or 5xx response.
 *
 * Append-only observational data: there is no `updated_at` and the module
 * exposes no update or delete endpoint (`skills/audit-logging/SKILL.md` §4).
 * The related user is nullable because most 404s happen before authentication
 * and because a deleted user must not erase the evidence.
 *
 * @property int $id
 * @property int|null $user_id
 * @property int $status_code
 * @property string $method
 * @property string $url
 * @property string|null $route_name
 * @property string|null $exception_class
 * @property string|null $message
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<string, mixed>|null $context
 * @property Carbon $created_at
 */
class ErrorLog extends Model
{
    /** @use HasFactory<ErrorLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'status_code',
        'method',
        'url',
        'route_name',
        'exception_class',
        'message',
        'ip_address',
        'user_agent',
        'context',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * The signed-in user who hit the failure, when there was one.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A 5xx is our fault; a 404 is usually a bad link or a stale bookmark.
     */
    public function isServerError(): bool
    {
        return $this->status_code >= 500;
    }

    public function isNotFound(): bool
    {
        return $this->status_code === 404;
    }
}
