<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Append-only log of public verification lookups (module 9.17).
 *
 * @property int $id
 * @property int $document_id
 * @property string $verification_token
 * @property string $result
 * @property Carbon $verified_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 */
class DocumentVerification extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['document_id', 'verification_token', 'result', 'verified_at', 'ip_address', 'user_agent'];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
