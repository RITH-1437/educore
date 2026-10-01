<?php

namespace App\Models;

use Database\Factories\ExamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * An exam of a section (`skills/examinations/SKILL.md`). The schedule is visible
 * to enrolled students; `is_published` releases the results to them.
 *
 * @property int $id
 * @property int $section_id
 * @property string $exam_type
 * @property string $title
 * @property numeric-string $weight
 * @property numeric-string $max_score
 * @property Carbon|null $scheduled_date
 * @property string|null $start_time
 * @property string|null $end_time
 * @property string|null $location
 * @property bool $is_published
 */
class Exam extends Model
{
    /** @use HasFactory<ExamFactory> */
    use HasFactory;

    public const TYPES = ['midterm', 'final', 'quiz', 'practical', 'other'];

    protected $fillable = [
        'section_id', 'exam_type', 'title', 'weight', 'max_score',
        'scheduled_date', 'start_time', 'end_time', 'location', 'is_published',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['weight' => 0, 'max_score' => 100, 'is_published' => false];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'max_score' => 'decimal:2',
            'scheduled_date' => 'date',
            'is_published' => 'boolean',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }
}
