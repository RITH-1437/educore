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

];
