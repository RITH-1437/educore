<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

/**
 * The requestable document types (module 9.16). Only types with a template
 * are seeded. Idempotent by `code`: re-run it to add new types to an
 * existing database.
 */
class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [DocumentType::ENROLLMENT_CERTIFICATE, 'Enrollment certificate', 'Confirms current enrollment, program and registered courses.', false, 0.00],
            [DocumentType::STUDENT_CERTIFICATE, 'Student certificate', 'Confirms student status, program and date of admission (active or graduated students).', false, 0.00],
            [DocumentType::TRANSCRIPT, 'Academic transcript', 'All approved course grades with semester and cumulative GPA.', true, 10.00],
            [DocumentType::ACADEMIC_RESULT, 'Academic result', 'Approved grades and GPA for one semester.', true, 5.00],
            [DocumentType::INTERNSHIP_LETTER, 'Internship letter', 'Confirms your approved, ongoing or completed internship placement.', false, 0.00],
        ];

        foreach ($types as $i => [$code, $name, $description, $requiresFee, $feeAmount]) {
            DocumentType::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'description' => $description,
                    'requires_fee' => $requiresFee,
                    'fee_amount' => $feeAmount,
                    'is_active' => true,
                    'sort_order' => $i + 1,
                ]
            );
        }
    }
}
