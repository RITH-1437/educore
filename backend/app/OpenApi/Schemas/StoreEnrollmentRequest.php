<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Request body for enrolling. */
#[OA\Schema(
    schema: 'StoreEnrollmentRequest',
    type: 'object',
    required: ['section_id'],
    properties: [
        new OA\Property(property: 'section_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'student_id', type: 'integer', format: 'int64', description: 'Required for staff; prohibited for students (their own profile is used).'),
    ]
)]
class StoreEnrollmentRequest {}
