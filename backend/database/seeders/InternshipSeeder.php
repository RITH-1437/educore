<?php

namespace Database\Seeders;

use App\Models\Internship;
use App\Models\InternshipCompany;
use App\Models\Student;
use App\Models\User;
use App\Services\InternshipService;
use Illuminate\Database\Seeder;

/**
 * Demo host companies and internships (module 9.22) through
 * `InternshipService`, so every status follows the real workflow: one
 * submitted, one approved, one in progress. Idempotent: companies by name;
 * internships skipped when any exist. Status notifications are queued like any
 * other (in development they land in the mail log).
 */
class InternshipSeeder extends Seeder
{
    public function run(InternshipService $internships): void
    {
        foreach ([
            ['name' => 'Smart Axiata', 'industry' => 'Telecommunications', 'contact_name' => 'HR Office', 'contact_email' => 'internships@smart.example', 'website' => 'https://www.smart.com.kh'],
            ['name' => 'ABA Bank', 'industry' => 'Banking', 'contact_name' => 'Talent Team', 'contact_email' => 'careers@aba.example'],
            ['name' => 'Ministry of Post and Telecommunications', 'industry' => 'Government', 'contact_name' => 'ICT Department'],
            ['name' => 'Khmer Software Initiative', 'industry' => 'Software', 'contact_name' => 'Engineering Manager'],
        ] as $company) {
            InternshipCompany::query()->firstOrCreate(['name' => $company['name']], [...$company, 'is_active' => true]);
        }

        $admin = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'super-admin'))->first();

        if ($admin === null || Internship::query()->exists()) {
            return;
        }

        $companies = InternshipCompany::query()->orderBy('id')->get();
        $students = Student::query()->where('status', Student::STATUS_ACTIVE)->orderByDesc('id')->limit(3)->get();

        foreach ($students as $i => $student) {
            $internship = $internships->apply($student, [
                'company_id' => $companies[$i % $companies->count()]->id,
                'position_title' => ['Network Engineering Intern', 'Data Analyst Intern', 'Software Developer Intern'][$i],
                'description' => 'Ten-week summer internship.',
                'start_date' => now()->subWeeks(2)->toDateString(),
                'end_date' => now()->addWeeks(8)->toDateString(),
                'supervisor_name' => 'Mr. Sok Dara',
                'supervisor_email' => 'supervisor@example.com',
            ]);
            $internships->submit($internship);

            if ($i >= 1) {
                $internships->approve($internship, $admin, 'Company and dates confirmed.');
            }

            if ($i >= 2) {
                $internships->start($internship, $admin);
            }
        }
    }
}
