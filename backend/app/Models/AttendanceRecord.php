<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A student's status for one session (unique per session and enrollment).
 *
 * @property int $id
 * @property int $attendance_session_id
 * @property int $enrollment_id
 * @property string $status
 * @property string|null $remarks
 * @property int|null $marked_by
 */
class AttendanceRecord extends Model
{
    public const STATUSES = ['present', 'absent', 'late', 'excused'];

    protected $fillable = ['attendance_session_id', 'enrollment_id', 'status', 'remarks', 'marked_by'];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}
