<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Bulk result entry. */
#[OA\Schema(
    schema: 'RecordExamResultsRequest',
    type: 'object',
    required: ['results'],
    properties: [
        new OA\Property(
            property: 'results',
            type: 'array',
            description: 'Rows with neither score nor remarks are ignored.',
            items: new OA\Items(type: 'object', required: ['enrollment_id'], properties: [
                new OA\Property(property: 'enrollment_id', type: 'integer', format: 'int64'),
                new OA\Property(property: 'score', type: 'number', format: 'float', minimum: 0, nullable: true, description: 'Not above the exam max_score.'),
                new OA\Property(property: 'remarks', type: 'string', nullable: true),
            ])
        ),
    ]
)]
class RecordExamResultsRequest {}
