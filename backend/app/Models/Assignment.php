<?php

namespace App\Models;

use Database\Factories\AssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Coursework published in a section (`skills/assignments/SKILL.md`).
 * Students only see it once `is_published`.
 *
 * @property int $id
 * @property int $section_id
 * @property string $title
 * @property string|null $description
 * @property string|null $instructions
 * @property numeric-string $max_score
 * @property Carbon $due_at
 * @property string $assignment_type
 * @property numeric-string|null $weight_override
 * @property bool $is_published
 * @property Carbon|null $published_at
 */
class Assignment extends Model
{
    /** @use HasFactory<AssignmentFactory> */
    use HasFactory;

    public const TYPES = ['homework', 'quiz', 'project', 'presentation', 'other'];

    protected $fillable = [
        'section_id', 'title', 'description', 'instructions', 'max_score', 'due_at',
        'assignment_type', 'weight_override', 'is_published', 'published_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['assignment_type' => 'homework', 'max_score' => 100, 'is_published' => false];

    protected function casts(): array
    {
        return [
            'max_score' => 'decimal:2',
            'weight_override' => 'decimal:2',
            'due_at' => 'datetime',
            'published_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function isPastDue(): bool
    {
        return now()->gt($this->due_at);
    }
}
