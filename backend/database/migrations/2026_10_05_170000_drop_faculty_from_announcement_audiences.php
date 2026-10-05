<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Report 39 removed the faculty level and moved faculty-addressed
 * announcements to `staff`, but left `faculty` in the audience CHECK. The
 * database now refuses it too, so nothing outside the validated API can store
 * an audience that no longer exists (`docs/39_Department-Only-Structure-Report.md` §7).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Same mapping as report 39, for any row written since.
        DB::table('announcements')->where('audience_type', 'faculty')->update(['audience_type' => 'staff', 'audience_id' => null]);

        DB::statement('ALTER TABLE announcements DROP CONSTRAINT ck_announcements_audience');
        DB::statement("ALTER TABLE announcements ADD CONSTRAINT ck_announcements_audience CHECK (audience_type IN ('all', 'students', 'lecturers', 'staff', 'department', 'program', 'section', 'course'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE announcements DROP CONSTRAINT ck_announcements_audience');
        DB::statement("ALTER TABLE announcements ADD CONSTRAINT ck_announcements_audience CHECK (audience_type IN ('all', 'students', 'lecturers', 'staff', 'faculty', 'department', 'program', 'section', 'course'))");
    }
};
