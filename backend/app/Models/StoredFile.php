<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Metadata of an object stored in MinIO/S3 (`files` table,
 * `skills/file-storage/SKILL.md`): the key, not a URL, is stored; private
 * files are only served through authorized routes.
 *
 * Named `StoredFile` to avoid clashing with Laravel's `File` facade.
 *
 * @property int $id
 * @property string|null $fileable_type
 * @property int|null $fileable_id
 * @property int|null $uploader_id
 * @property string $file_name
 * @property string $original_name
 * @property string $storage_key
 * @property string $bucket
 * @property string|null $mime_type
 * @property int $size
 * @property string $visibility
 * @property string|null $checksum
 */
class StoredFile extends Model
{
    protected $table = 'files';

    protected $fillable = [
        'fileable_type', 'fileable_id', 'uploader_id', 'file_name', 'original_name',
        'storage_key', 'bucket', 'mime_type', 'size', 'visibility', 'checksum',
    ];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }
}
