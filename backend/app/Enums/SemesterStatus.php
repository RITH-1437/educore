<?php

namespace App\Enums;

/**
 * Mirrors `ck_semesters_status` on the `semesters` table.
 */
enum SemesterStatus: string
{
    case Planned = 'planned';
    case Open = 'open';
    case Closed = 'closed';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planned',
            self::Open => 'Open',
            self::Closed => 'Closed',
            self::Completed => 'Completed',
        };
    }

    /**
     * A semester only moves forward: planned -> open -> closed -> completed.
     *
     * `closed` already stops new registrations, and `completed` freezes
     * grading, so neither is reversible.
     */
    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Planned => $next === self::Open,
            self::Open => $next === self::Closed,
            self::Closed => $next === self::Completed,
            self::Completed => false,
        };
    }
}
