<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * A targeted announcement (`skills/announcements`).
 *
 * `draft → published → archived`. Only drafts can be edited or deleted; a
 * published announcement is never silently rewritten — archive it and publish
 * a correction. The audience is resolved when a feed is read
 * (`AnnouncementService::feedFor`), so no recipient rows are stored.
 *
 * @property int $id
 * @property int $author_id
 * @property string $title
 * @property string $body
 * @property string|null $announcement_type
 * @property string $audience_type
 * @property int|null $audience_id
 * @property string $publish_state
 * @property Carbon|null $published_at
 * @property-read User $author
 */
class Announcement extends Model
{
    public const STATE_DRAFT = 'draft';

    public const STATE_PUBLISHED = 'published';

    public const STATE_ARCHIVED = 'archived';

    public const STATES = [self::STATE_DRAFT, self::STATE_PUBLISHED, self::STATE_ARCHIVED];

    public const TYPES = ['general', 'academic', 'administrative', 'event'];

    /** Audiences without a target id. */
    public const GROUP_AUDIENCES = ['all', 'students', 'lecturers', 'staff'];

    /** Audiences that point at one record of the given model. */
    public const UNIT_AUDIENCES = [
        'department' => Department::class,
        'program' => Program::class,
        'section' => Section::class,
        'course' => Course::class,
    ];

    protected $fillable = ['author_id', 'title', 'body', 'announcement_type', 'audience_type', 'audience_id', 'publish_state', 'published_at'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['publish_state' => self::STATE_DRAFT, 'audience_type' => 'all'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'audience_id' => 'integer'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Attachments linked to this announcement. */
    public function attachments(): MorphMany
    {
        return $this->morphMany(StoredFile::class, 'fileable');
    }

    /** The targeted record (department, program, section or course), if any. */
    public function target(): ?Model
    {
        $class = self::UNIT_AUDIENCES[$this->audience_type] ?? null;

        return $class && $this->audience_id ? $class::query()->find($this->audience_id) : null;
    }
}
