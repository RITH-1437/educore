<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enrollment
    |--------------------------------------------------------------------------
    |
    | Maximum credits a student may carry in one semester across pending and
    | confirmed enrollments (`skills/enrollment/SKILL.md` §4 credit limit).
    |
    */

    'max_semester_credits' => (float) env('MAX_SEMESTER_CREDITS', 24),

    /*
    |--------------------------------------------------------------------------
    | Institution time
    |--------------------------------------------------------------------------
    |
    | Class times in the timetable are wall-clock times at the university. This
    | timezone reads them: "today's classes" and class-start reminders (report
    | 48). It is not the application timezone — timestamps stay stored in UTC.
    | Set ACADEMIC_TIMEZONE (for example Asia/Phnom_Penh) in production.
    |
    */

    'timezone' => env('ACADEMIC_TIMEZONE', 'UTC'),

    // Minutes before a class starts that its students and lecturers are reminded.
    'class_reminder_minutes' => (int) env('CLASS_REMINDER_MINUTES', 30),

    /*
    |--------------------------------------------------------------------------
    | Uploads
    |--------------------------------------------------------------------------
    |
    | Disk for private academic files (assignment submissions). MinIO/S3 in
    | every environment (`skills/file-storage`); tests fake it.
    |
    */

    'uploads_disk' => env('UPLOADS_DISK', 's3'),

    'submission_max_kb' => (int) env('SUBMISSION_MAX_KB', 10240),

    'submission_mimes' => ['pdf', 'docx', 'zip', 'png', 'jpg', 'jpeg'],

    // Course materials lecturers share (report 44): documents, slides, sheets, archives, images.
    'material_max_kb' => (int) env('MATERIAL_MAX_KB', 20480),

    'material_mimes' => ['pdf', 'docx', 'pptx', 'xlsx', 'txt', 'zip', 'png', 'jpg', 'jpeg'],

];
