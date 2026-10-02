<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Audit trail entries (module 9.24). */
#[OA\Schema(
    schema: 'AuditLog',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'action', type: 'string', example: 'grades.approved'),
        new OA\Property(property: 'area', type: 'string', example: 'grades'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'actor', type: 'object', nullable: true, description: 'Null for system actions or a removed user.', properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'email', type: 'string'),
        ]),
        new OA\Property(property: 'target', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'type', type: 'string', example: 'Section'),
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        ]),
        new OA\Property(property: 'before', type: 'object', nullable: true, description: 'Changed attributes before (secrets never included).'),
        new OA\Property(property: 'after', type: 'object', nullable: true),
        new OA\Property(property: 'ip_address', type: 'string', nullable: true),
        new OA\Property(property: 'user_agent', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'AuditLogCollection',
    type: 'object',
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/AuditLog')),
        new OA\Property(property: 'links', type: 'object'),
        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
    ]
)]
class AuditLog {}
