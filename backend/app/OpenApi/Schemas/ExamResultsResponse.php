<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** An exam with its results roster (roster only for staff and the section lecturers). */
#[OA\Schema(
    schema: 'ExamResultsResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/Exam'),
        new OA\Property(property: 'results', type: 'array', items: new OA\Items(ref: '#/components/schemas/ExamResultRow')),
    ]
)]
class ExamResultsResponse {}
