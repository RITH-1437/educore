<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * The university's wall clock (`academics.timezone`, report 48). Timetable
 * times are local wall-clock times ("08:00" at the university), while the
 * application timezone — and every stored timestamp — stays UTC.
 */
class AcademicClock
{
    /** The current moment on the university's clock (for its hour and minute). */
    public static function now(): Carbon
    {
        return Carbon::now()->setTimezone(self::timezone());
    }

    /**
     * The university's calendar date as a date in the application timezone, so
     * it compares correctly with date columns (`Semester::covers()`) and its
     * `dayOfWeekIso` is the local weekday.
     */
    public static function today(): Carbon
    {
        return Carbon::parse(self::now()->toDateString());
    }

    public static function timezone(): string
    {
        return (string) config('academics.timezone', 'UTC');
    }
}
