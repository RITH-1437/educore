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
    | Campus map
    |--------------------------------------------------------------------------
    |
    | The map button on every page (report 49). The embedded Google map
    | searches `name` at `coordinates` ("lat,lng"), which pin and centre it;
    | `url` is the place's own Google Maps link, opened in a new tab.
    |
    */

    'campus_map' => [
        'name' => env('CAMPUS_MAP_NAME', 'ITC Conference Hall'),
        'address' => env('CAMPUS_MAP_ADDRESS', 'HVCX+6F6, Russian Federation Blvd (110), Phnom Penh, Cambodia'),
        'coordinates' => env('CAMPUS_MAP_COORDINATES', '11.5705439,104.8986445'),
        'url' => env('CAMPUS_MAP_URL', 'https://www.google.com/maps/place/ITC+Conference+Hall/@11.5703117,104.8990476,18.11z/data=!4m6!3m5!1s0x310951738deaaaab:0x6e806285f01ccb0b!8m2!3d11.5705439!4d104.8986445!16s%2Fg%2F11kj8_wkbd'),
    ],

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
