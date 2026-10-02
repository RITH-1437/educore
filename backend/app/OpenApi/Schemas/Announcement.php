<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Announcements (module 9.19). */
#[OA\Schema(
    schema: 'Announcement',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'body', type: 'string'),
        new OA\Property(property: 'announcement_type', type: 'string', enum: ['general', 'academic', 'administrative', 'event'], nullable: true),
        new OA\Property(property: 'audience_type', type: 'string', enum: ['all', 'students', 'lecturers', 'staff', 'faculty', 'department', 'program', 'section', 'course']),
        new OA\Property(property: 'audience_id', type: 'integer', format: 'int64', nullable: true),
        new OA\Property(property: 'audience', type: 'string', example: 'Section: CS101 · Section A'),
        new OA\Property(property: 'publish_state', type: 'string', enum: ['draft', 'published', 'archived']),
        new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'author', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'name', type: 'string'),
        ]),
    ]
)]
#[OA\Schema(
    schema: 'AnnouncementCollection',
    type: 'object',
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Announcement')),
        new OA\Property(property: 'links', type: 'object'),
        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
    ]
)]
#[OA\Schema(
    schema: 'AnnouncementResourceResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Announcement')]
)]
#[OA\Schema(
    schema: 'AnnouncementRequest',
    type: 'object',
    required: ['title', 'body', 'audience_type'],
    properties: [
        new OA\Property(property: 'title', type: 'string', maxLength: 255),
        new OA\Property(property: 'body', type: 'string', maxLength: 10000),
        new OA\Property(property: 'announcement_type', type: 'string', enum: ['general', 'academic', 'administrative', 'event'], nullable: true),
        new OA\Property(property: 'audience_type', type: 'string', enum: ['all', 'students', 'lecturers', 'staff', 'faculty', 'department', 'program', 'section', 'course']),
        new OA\Property(property: 'audience_id', type: 'integer', format: 'int64', nullable: true, description: 'Required for faculty / department / program / section / course.'),
        new OA\Property(property: 'publish', type: 'boolean', description: 'Create only: publish immediately.'),
    ]
)]
class Announcement {}
