<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

/**
 * The requestable document types (module 9.16). Only types with a template
 * are seeded; internship letters arrive with 9.22. Idempotent by `code`.
 */
class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [DocumentType::ENROLLMENT_CERTIFICATE, 'Enrollment certificate', 'Confirms current enrollment, program and registered courses.'],
            [DocumentType::TRANSCRIPT, 'Academic transcript', 'All approved course grades with semester and cumulative GPA.'],
            [DocumentType::ACADEMIC_RESULT, 'Academic result', 'Approved grades and GPA for one semester.'],
        ];

        foreach ($types as $i => [$code, $name, $description]) {
            DocumentType::query()->updateOrCreate(['code' => $code], ['name' => $name, 'description' => $description, 'requires_fee' => false, 'is_active' => true, 'sort_order' => $i + 1]);
        }
    }
}
