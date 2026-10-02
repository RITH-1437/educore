<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** A section's grade sheet (module 9.14). */
#[OA\Schema(
    schema: 'GradeSheetResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'saved', type: 'integer', description: 'Rows changed (write actions only).'),
        new OA\Property(property: 'weights', type: 'object', description: 'Component weights in % (attendance, assignment, midterm, final, practical).', additionalProperties: new OA\AdditionalProperties(type: 'number')),
        new OA\Property(property: 'components', type: 'object', description: 'Whether each component has something to measure in the section.', additionalProperties: new OA\AdditionalProperties(type: 'boolean')),
        new OA\Property(property: 'counts', type: 'object', properties: [
            new OA\Property(property: 'draft', type: 'integer'),
            new OA\Property(property: 'submitted', type: 'integer'),
            new OA\Property(property: 'approved', type: 'integer'),
            new OA\Property(property: 'students', type: 'integer'),
        ]),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GradeSheetRow')),
    ]
)]
class GradeSheetResponse {}
