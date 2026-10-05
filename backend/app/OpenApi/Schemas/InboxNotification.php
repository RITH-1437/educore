<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** In-app inbox messages (`docs/42_In-App-Notification-Inbox-Report.md`). */
#[OA\Schema(
    schema: 'InboxNotification',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'kind', type: 'string', enum: ['announcement', 'assignment', 'document', 'enrollment', 'grade', 'internship', 'finance', 'security', 'general']),
        new OA\Property(property: 'title', type: 'string', example: 'Document request approved: transcript'),
        new OA\Property(property: 'body', type: 'string'),
        new OA\Property(property: 'url', type: 'string', nullable: true, description: 'In-app path the message points to.', example: '/my-documents'),
        new OA\Property(property: 'read_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'InboxNotificationCollection',
    type: 'object',
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/InboxNotification')),
        new OA\Property(property: 'links', type: 'object'),
        new OA\Property(property: 'meta', type: 'object', allOf: [
            new OA\Schema(ref: '#/components/schemas/PaginationMeta'),
            new OA\Schema(properties: [new OA\Property(property: 'unread_count', type: 'integer', example: 3)]),
        ]),
    ]
)]
class InboxNotification {}
