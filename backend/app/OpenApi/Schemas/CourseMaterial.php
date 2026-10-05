<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Course materials (`docs/44_Course-Materials-Report.md`). */
#[OA\Schema(
    schema: 'CourseMaterial',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'section_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'title', type: 'string', example: 'Week 3 slides'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'kind', type: 'string', enum: ['file', 'link']),
        new OA\Property(property: 'url', type: 'string', format: 'uri', nullable: true, description: 'Link materials only.'),
        new OA\Property(property: 'file', type: 'object', nullable: true, description: 'File materials only; fetch it from /materials/{id}/file.', properties: [
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'mime_type', type: 'string', nullable: true),
            new OA\Property(property: 'size', type: 'integer'),
        ]),
        new OA\Property(property: 'shared_by', type: 'string', nullable: true),
        new OA\Property(property: 'course', type: 'object', nullable: true, description: 'Included in the student list.', properties: [
            new OA\Property(property: 'code', type: 'string'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'section', type: 'string'),
        ]),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class CourseMaterial {}
