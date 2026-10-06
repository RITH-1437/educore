<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Enrollment;
use App\Models\ScheduleEntry;
use App\Models\Section;
use App\Models\User;
use App\Notifications\AssignmentDueSoon;
use App\Notifications\ClassStartingSoon;
use App\Support\AcademicClock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Scheduled reminders (modules 9.20 / 9.21). Assignments: run daily by
 * `notifications:assignment-reminders`; the 24-hour window and the daily
 * cadence mean each assignment is reminded once. Classes: run every five
 * minutes by `notifications:class-reminders` (report 48).
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

    /**
     * Remind the students and lecturers of the class meetings starting within
     * the next `academics.class_reminder_minutes` (default 30), read on the
     * institution's clock (`academics.timezone`). Only running sections
     * (open / active / closed) of a semester that is not completed and whose
     * dates include today. Each meeting is claimed once per day with an atomic
     * cache key, so a doubled or late run neither repeats nor drops a reminder
     * while the class is still ahead. Returns the number of reminders queued.
     */
    public function classesStartingSoon(): int
    {
        $local = AcademicClock::now();
        $until = $local->copy()->addMinutes(max(1, (int) config('academics.class_reminder_minutes', 30)));
        $until = $until->isSameDay($local) ? $until : $local->copy()->endOfDay();
        $today = $local->toDateString();
        $sent = 0;

        ScheduleEntry::query()
            ->where('day_of_week', $local->dayOfWeekIso)
            ->where('start_time', '>', $local->format('H:i:s'))
            ->where('start_time', '<=', $until->format('H:i:s'))
            ->whereHas('section', fn ($section) => $section->whereIn('status', ['open', 'active', 'closed'])
                ->whereHas('offering.semester', fn ($semester) => $semester->where('status', '!=', 'completed')
                    ->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)))
            ->with(['section.offering.course:id,code,name', 'room:id,code,name'])
            ->orderBy('start_time')
            ->each(function (ScheduleEntry $entry) use ($today, &$sent) {
                if (! Cache::add("class-reminder:{$entry->getKey()}:{$today}", true, now()->addDay())) {
                    return;
                }

                $users = $this->classRecipients($entry->section);
                Notification::send($users, new ClassStartingSoon($entry));
                $sent += $users->count();
            });

        return $sent;
    }

    /**
     * Active students with an open enrollment in the section and its active
     * lecturers — only those who linked Telegram and kept class reminders on.
     *
     * @return Collection<int, User>
     */
    private function classRecipients(Section $section): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where(fn ($users) => $users
                ->whereIn('id', Enrollment::query()
                    ->join('students', 'students.id', '=', 'enrollments.student_id')
                    ->where('enrollments.section_id', $section->getKey())
                    ->whereIn('enrollments.status', Enrollment::OPEN_STATUSES)
                    ->select('students.user_id'))
                ->orWhereIn('id', DB::table('section_lecturers')
                    ->join('lecturers', 'lecturers.id', '=', 'section_lecturers.lecturer_id')
                    ->where('section_lecturers.section_id', $section->getKey())
                    ->where('lecturers.is_active', true)
                    ->select('lecturers.user_id')))
            ->with('notificationPreference')
            ->get()
            ->filter(fn (User $user) => $user->preferences()->wantsClassReminders())
            ->values();
    }
}
