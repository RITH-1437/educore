<?php

namespace Database\Seeders;

use App\Models\GradingScale;
use App\Services\GradingService;
use Illuminate\Database\Seeder;

/**
 * Seeds the default "Standard" grading scale (A=4.0 … F=0.0,
 * `skills/grading-gpa/SKILL.md` §4) through `GradingService::saveScale()` so
 * the bands are gap-free. Skipped when a scale already exists, so an edited
 * scale is never overwritten.
 *
 * Course weights are not seeded: a course without saved weights uses the
 * defaults (10 / 25 / 20 / 40 / 5).
 */
class GradingScaleSeeder extends Seeder
{
    public function run(GradingService $grading): void
    {
        if (GradingScale::query()->exists()) {
            return;
        }

        $grading->saveScale(GradingService::DEFAULT_BANDS);
    }
}
