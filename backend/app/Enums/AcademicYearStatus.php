<?php

namespace App\Enums;

/**
 * Mirrors `ck_academic_years_status` on the `academic_years` table.
 */
enum AcademicYearStatus: string
{
    case Planned = 'planned';
    case Active = 'active';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planned',
            self::Active => 'Active',
            self::Completed => 'Completed',
        };
    }

    /**
     * An academic year only moves forward: planned -> active -> completed.
     *
     * Reverting a published year would silently invalidate the semesters,
     * offerings and grades that already reference it, so the transition is
     * rejected instead.
     */
    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Planned => $next === self::Active,
            self::Active => $next === self::Completed,
            self::Completed => false,
        };
    }
}
