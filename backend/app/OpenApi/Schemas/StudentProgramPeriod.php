<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** One period of a student's program history. */
#[OA\Schema(
    schema: 'StudentProgramPeriod',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'program_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'program', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'code', type: 'string', example: 'BSCS'),
            new OA\Property(property: 'name', type: 'string', example: 'Bachelor of Computer Science'),
            new OA\Property(property: 'department', type: 'object', nullable: true, properties: [
                new OA\Property(property: 'id', type: 'integer', format: 'int64'),
                new OA\Property(property: 'code', type: 'string', example: 'CSE'),
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'faculty', type: 'object', nullable: true, properties: [
                    new OA\Property(property: 'id', type: 'integer', format: 'int64'),
                    new OA\Property(property: 'code', type: 'string', example: 'ENG'),
                    new OA\Property(property: 'name', type: 'string'),
                ]),
            ]),
        ]),
        new OA\Property(property: 'started_on', type: 'string', format: 'date'),
        new OA\Property(property: 'ended_on', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'completed', 'withdrawn', 'transferred']),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
    ]
)]
class StudentProgramPeriod {}
