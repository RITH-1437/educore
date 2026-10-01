<?php

namespace App\Models;

use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A concrete class instance of an offering (Section A, B…) with a capacity
 * and assigned lecturers (`section_lecturers`, role primary/assistant/tutor).
 * Weekly meetings live in `schedule_entries` (9.10 Timetable).
 *
 * @property int $id
 * @property int $course_offering_id
 * @property string $code
 * @property string|null $name
 * @property int $capacity
 * @property string $status
 */
class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use HasFactory;

    public const STATUSES = ['draft', 'open', 'active', 'closed', 'archived'];

    public const LECTURER_ROLES = ['primary', 'assistant', 'tutor'];

    protected $fillable = ['course_offering_id', 'code', 'name', 'capacity', 'status'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['status' => 'draft', 'capacity' => 30];

    protected function casts(): array
    {
        return ['capacity' => 'integer'];
    }

    public function scheduleEntries(): HasMany
    {
        return $this->hasMany(ScheduleEntry::class)->orderBy('day_of_week')->orderBy('start_time');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class, 'course_offering_id');
    }

    /**
     * @return BelongsToMany<Lecturer, $this>
     */
    public function lecturers(): BelongsToMany
    {
        return $this->belongsToMany(Lecturer::class, 'section_lecturers')
            ->withPivot('role')
            ->withTimestamps();
    }
}
