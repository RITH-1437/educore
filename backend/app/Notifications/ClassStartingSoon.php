<?php

namespace App\Notifications;

use App\Models\ScheduleEntry;
use App\Models\User;
use App\Notifications\Channels\TelegramChannel;

/**
 * A class starts soon (module 9.21 "class reminders", report 48). Sent to the
 * section's enrolled students and its lecturers before each weekly meeting.
 *
 * Telegram only: a reminder before every class would flood email and the
 * inbox, so this is the one notification without `toMail()` / `toInbox()`.
 * It goes to users who linked a chat and kept class reminders on.
 */
class ClassStartingSoon extends EduCoreNotification
{
    public function __construct(public ScheduleEntry $entry)
    {
        parent::__construct();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof User && $notifiable->is_active && $notifiable->preferences()->wantsClassReminders()
            ? [TelegramChannel::class]
            : [];
    }

    public function toTelegram(object $notifiable): string
    {
        $entry = $this->entry->loadMissing(['section.offering.course:id,code,name', 'room:id,code,name']);
        $section = $entry->section;
        $course = $section->offering->course;
        $room = $entry->room ? "{$entry->room->code} ({$entry->room->name})" : 'room to be announced';

        return "Class at {$entry->startsAt()}: {$course->code} {$course->name}, section {$section->code}\n"
            ."{$entry->startsAt()}–{$entry->endsAt()} in {$room}.\n\n"
            .$this->link('/timetable');
    }
}
