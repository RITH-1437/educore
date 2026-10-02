<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\AssignmentDueSoon;
use Illuminate\Support\Facades\Notification;

/**
 * Scheduled reminders (modules 9.20 / 9.21). Run daily by
 * `notifications:assignment-reminders`; the 24-hour window and the daily
 * cadence mean each assignment is reminded once.
 */
class ReminderService
{
    /**
     * Remind open-enrolled students of published assignments due in the next
     * 24 hours that they have not submitted. Returns the number of reminders.
     */
    public function assignmentsDueSoon(): int
    {
        $sent = 0;

        Assignment::query()
            ->where('is_published', true)
            ->whereBetween('due_at', [now(), now()->addDay()])
            ->with('section.offering.course:id,code')
            ->each(function (Assignment $assignment) use (&$sent) {
                $users = User::query()
                    ->where('is_active', true)
                    ->whereIn('id', Enrollment::query()
                        ->join('students', 'students.id', '=', 'enrollments.student_id')
                        ->where('enrollments.section_id', $assignment->section_id)
                        ->whereIn('enrollments.status', Enrollment::OPEN_STATUSES)
                        ->whereNotIn('enrollments.id', $assignment->submissions()->select('enrollment_id'))
                        ->select('students.user_id'))
                    ->with('notificationPreference')
                    ->get();

                Notification::send($users, new AssignmentDueSoon($assignment));
                $sent += $users->count();
            });

        return $sent;
    }
}
