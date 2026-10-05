<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * A handout, slide deck or link a section's lecturers share with its students
 * (`docs/44_Course-Materials-Report.md`). A `file` material's object lives in
 * the `files` table (private MinIO); a `link` material keeps its URL here.
 *
 * @property int $id
 * @property int $section_id
 * @property string $title
 * @property string|null $description
 * @property string $kind
 * @property string|null $url
 * @property int|null $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class CourseMaterial extends Model
{
    public const KIND_FILE = 'file';

    public const KIND_LINK = 'link';

    public const KINDS = [self::KIND_FILE, self::KIND_LINK];

    protected $fillable = ['section_id', 'title', 'description', 'kind', 'url', 'created_by'];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function file(): MorphOne
    {
        return $this->morphOne(StoredFile::class, 'fileable');
    }
}
