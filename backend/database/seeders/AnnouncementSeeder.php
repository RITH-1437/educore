<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Program;
use App\Models\Section;
use App\Models\User;
use App\Services\AnnouncementService;
use Illuminate\Database\Seeder;

/**
 * Demo announcements (module 9.19) through `AnnouncementService`: a published
 * notice to everyone, one to all students, one to the first program, one to
 * the first open section, and a draft. Skipped when announcements exist.
 */
class AnnouncementSeeder extends Seeder
{
    public function run(AnnouncementService $announcements): void
    {
        $admin = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'super-admin'))->first();

        if ($admin === null || Announcement::query()->exists()) {
            return;
        }

        $announcements->create($admin, ['title' => 'Welcome to the new semester', 'body' => "Classes begin this week. Check your timetable and course registration in EduCore.\nThe registrar's office is open Monday to Friday, 8:00–17:00.", 'announcement_type' => 'general', 'audience_type' => 'all'], true);
        $announcements->create($admin, ['title' => 'Midterm examination period', 'body' => 'Midterm exams run during week 8. Your exam schedule is under My exams; bring your student card.', 'announcement_type' => 'academic', 'audience_type' => 'students'], true);

        if ($program = Program::query()->orderBy('id')->first()) {
            $announcements->create($admin, ['title' => "{$program->code} orientation session", 'body' => "All {$program->name} students are invited to the program orientation on Friday at 14:00 in the main hall.", 'announcement_type' => 'event', 'audience_type' => 'program', 'audience_id' => $program->id], true);
        }

        if ($section = Section::query()->whereIn('status', ['open', 'active'])->orderBy('id')->first()) {
            $announcements->create($admin, ['title' => 'Room change this week', 'body' => 'This week\'s class meets in the computer lab instead of the usual room.', 'announcement_type' => 'administrative', 'audience_type' => 'section', 'audience_id' => $section->id], true);
        }

        $announcements->create($admin, ['title' => 'Library extended hours (draft)', 'body' => 'During the exam period the library will stay open until 21:00.', 'announcement_type' => 'general', 'audience_type' => 'all']);
    }
}
