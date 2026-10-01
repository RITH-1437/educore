<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One period of a student's program history (`student_programs`).
 *
 * Rows are closed (`ended_on` + a final status), never rewritten, so the
 * history survives program changes, graduation and withdrawal.
 *
 * @property int $id
 * @property int $student_id
 * @property int $program_id
 * @property Carbon $started_on
 * @property Carbon|null $ended_on
 * @property string $status
 * @property string|null $notes
 */
class StudentProgram extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUS_TRANSFERRED = 'transferred';

    protected $fillable = [
        'student_id',
        'program_id',
        'started_on',
        'ended_on',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_on' => 'date',
            'ended_on' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
