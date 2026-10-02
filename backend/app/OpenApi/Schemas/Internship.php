<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Internships (module 9.22). */
#[OA\Schema(
    schema: 'InternshipCompany',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'industry', type: 'string', nullable: true),
        new OA\Property(property: 'contact_name', type: 'string', nullable: true),
        new OA\Property(property: 'contact_email', type: 'string', nullable: true),
        new OA\Property(property: 'contact_phone', type: 'string', nullable: true),
        new OA\Property(property: 'address', type: 'string', nullable: true),
        new OA\Property(property: 'website', type: 'string', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'internships_count', type: 'integer'),
    ]
)]
#[OA\Schema(
    schema: 'InternshipCompanyRequest',
    type: 'object',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, description: 'Unique.'),
        new OA\Property(property: 'industry', type: 'string', nullable: true),
        new OA\Property(property: 'contact_name', type: 'string', nullable: true),
        new OA\Property(property: 'contact_email', type: 'string', format: 'email', nullable: true),
        new OA\Property(property: 'contact_phone', type: 'string', nullable: true),
        new OA\Property(property: 'address', type: 'string', nullable: true),
        new OA\Property(property: 'website', type: 'string', format: 'uri', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean'),
    ]
)]
#[OA\Schema(
    schema: 'Internship',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'submitted', 'under_review', 'approved', 'rejected', 'in_progress', 'completed', 'cancelled']),
        new OA\Property(property: 'position_title', type: 'string'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'start_date', type: 'string', format: 'date'),
        new OA\Property(property: 'end_date', type: 'string', format: 'date'),
        new OA\Property(property: 'supervisor_name', type: 'string'),
        new OA\Property(property: 'supervisor_email', type: 'string', nullable: true),
        new OA\Property(property: 'supervisor_phone', type: 'string', nullable: true),
        new OA\Property(property: 'submitted_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'reviewed_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'notes', type: 'string', nullable: true, description: 'Append-only log of review decisions.'),
        new OA\Property(property: 'company', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'industry', type: 'string', nullable: true),
        ]),
        new OA\Property(property: 'student', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'student_number', type: 'string'),
            new OA\Property(property: 'full_name', type: 'string'),
        ]),
        new OA\Property(property: 'reports', type: 'array', items: new OA\Items(properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'report_type', type: 'string', enum: ['initial', 'progress', 'final']),
            new OA\Property(property: 'title', type: 'string'),
            new OA\Property(property: 'summary', type: 'string', nullable: true),
            new OA\Property(property: 'status', type: 'string', enum: ['submitted', 'reviewed']),
            new OA\Property(property: 'reviewer_comment', type: 'string', nullable: true),
            new OA\Property(property: 'submitted_at', type: 'string', format: 'date-time'),
            new OA\Property(property: 'file', type: 'object', nullable: true, properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'size', type: 'integer'),
            ]),
        ])),
        new OA\Property(property: 'evaluations', type: 'array', items: new OA\Items(properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'evaluator_type', type: 'string', enum: ['supervisor', 'faculty']),
            new OA\Property(property: 'evaluator_name', type: 'string', nullable: true),
            new OA\Property(property: 'score', type: 'number', nullable: true),
            new OA\Property(property: 'rating', type: 'string', nullable: true),
            new OA\Property(property: 'comments', type: 'string', nullable: true),
            new OA\Property(property: 'evaluated_at', type: 'string', format: 'date-time', nullable: true),
        ])),
    ]
)]
#[OA\Schema(
    schema: 'InternshipCollection',
    type: 'object',
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Internship')),
        new OA\Property(property: 'links', type: 'object'),
        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
    ]
)]
#[OA\Schema(
    schema: 'InternshipResourceResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Internship')]
)]
#[OA\Schema(
    schema: 'InternshipRequest',
    type: 'object',
    required: ['company_id', 'position_title', 'start_date', 'end_date', 'supervisor_name'],
    properties: [
        new OA\Property(property: 'company_id', type: 'integer', format: 'int64', description: 'An active company.'),
        new OA\Property(property: 'position_title', type: 'string', maxLength: 255),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'start_date', type: 'string', format: 'date'),
        new OA\Property(property: 'end_date', type: 'string', format: 'date', description: 'On or after start_date.'),
        new OA\Property(property: 'supervisor_name', type: 'string', maxLength: 150),
        new OA\Property(property: 'supervisor_email', type: 'string', format: 'email', nullable: true),
        new OA\Property(property: 'supervisor_phone', type: 'string', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'InternshipEvaluationRequest',
    type: 'object',
    required: ['evaluator_type', 'score'],
    properties: [
        new OA\Property(property: 'evaluator_type', type: 'string', enum: ['supervisor', 'faculty']),
        new OA\Property(property: 'evaluator_name', type: 'string', nullable: true, description: 'Defaults to the supervisor name / the signed-in staff member.'),
        new OA\Property(property: 'score', type: 'number', minimum: 0, maximum: 100),
        new OA\Property(property: 'rating', type: 'string', enum: ['excellent', 'good', 'satisfactory', 'needs_improvement', 'poor'], nullable: true),
        new OA\Property(property: 'comments', type: 'string', nullable: true),
    ]
)]
class Internship {}
