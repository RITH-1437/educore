<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** A student's exam schedule with released results. */
#[OA\Schema(
    schema: 'StudentExamsResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(allOf: [
            new OA\Schema(ref: '#/components/schemas/Exam'),
            new OA\Schema(properties: [
                new OA\Property(property: 'course', type: 'object', properties: [new OA\Property(property: 'code', type: 'string'), new OA\Property(property: 'name', type: 'string')]),
                new OA\Property(property: 'section_code', type: 'string'),
                new OA\Property(property: 'semester', type: 'string'),
                new OA\Property(property: 'my_result', type: 'object', nullable: true, description: 'Only once results are released.', properties: [
                    new OA\Property(property: 'score', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'remarks', type: 'string', nullable: true),
                ]),
            ]),
        ])),
    ]
)]
class StudentExamsResponse {}
