<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A generated, privately stored PDF — an immutable snapshot of the data at
 * generation time. `verification_token` backs the public verification page
 * (module 9.17); staff may revoke a document (e.g. a transcript after a grade
 * change) and the student requests a fresh one.
 *
 * @property int $id
 * @property int $document_request_id
 * @property string $file_key
 * @property string $file_name
 * @property string|null $mime_type
 * @property int|null $file_size
 * @property string|null $checksum
 * @property string $verification_token
 * @property int|null $generated_by
 * @property Carbon $generated_at
 * @property string $status
 * @property-read DocumentRequest $request
 */
class Document extends Model
{
    use SoftDeletes;

    public const STATUS_VALID = 'valid';

    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'document_request_id', 'file_key', 'file_name', 'mime_type', 'file_size', 'checksum',
        'verification_token', 'generated_by', 'generated_at', 'status',
    ];

    /** The storage key never leaves the server; downloads go through the policy-checked route. */
    protected $hidden = ['file_key'];

    protected function casts(): array
    {
        return ['generated_at' => 'datetime', 'file_size' => 'integer'];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(DocumentRequest::class, 'document_request_id');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(DocumentVerification::class);
    }
}
