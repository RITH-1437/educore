<?php

namespace Database\Seeders;

use App\Enums\AcademicYearStatus;
use App\Enums\SemesterStatus;
use App\Models\AcademicYear;
use App\Models\Semester;
use Illuminate\Database\Seeder;

/**
 * Seeds a realistic academic calendar: one completed year, the current year
 * (active + current) and one planned year, each with two semesters.
 */
class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        $calendar = [
            ['2024-2025', '2024-09-01', '2025-08-31', AcademicYearStatus::Completed, false, [
                ['Semester 1', 'S1', 1, '2024-09-01', '2025-01-31', SemesterStatus::Completed],
                ['Semester 2', 'S2', 2, '2025-02-01', '2025-08-31', SemesterStatus::Completed],
            ]],
            ['2025-2026', '2025-09-01', '2026-08-31', AcademicYearStatus::Active, true, [
                ['Semester 1', 'S1', 1, '2025-09-01', '2026-01-31', SemesterStatus::Completed],
                ['Semester 2', 'S2', 2, '2026-02-01', '2026-08-31', SemesterStatus::Open],
            ]],
            ['2026-2027', '2026-09-01', '2027-08-31', AcademicYearStatus::Planned, false, [
                ['Semester 1', 'S1', 1, '2026-09-01', '2027-01-31', SemesterStatus::Planned],
                ['Semester 2', 'S2', 2, '2027-02-01', '2027-08-31', SemesterStatus::Planned],
            ]],
        ];

        foreach ($calendar as [$code, $startDate, $endDate, $status, $isCurrent, $semesters]) {
            $academicYear = AcademicYear::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => "Academic Year {$code}",
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => $status,
                    'is_current' => $isCurrent,
                ],
            );

            foreach ($semesters as [$name, $semesterCode, $sequence, $semesterStart, $semesterEnd, $semesterStatus]) {
                Semester::query()->updateOrCreate(
                    ['academic_year_id' => $academicYear->id, 'sequence' => $sequence],
                    [
                        'name' => $name,
                        'code' => $semesterCode,
                        'start_date' => $semesterStart,
                        'end_date' => $semesterEnd,
                        'status' => $semesterStatus,
                    ],
                );
            }
        }
    }
}
