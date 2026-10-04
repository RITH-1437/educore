<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Document requests and generated documents (modules 9.16 / 9.17). */
#[OA\Schema(
    schema: 'DocumentRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'approved', 'rejected', 'generated']),
        new OA\Property(property: 'type', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'code', type: 'string'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'requires_fee', type: 'boolean'),
            new OA\Property(property: 'fee_amount', type: 'number', format: 'float'),
        ]),
        new OA\Property(property: 'semester', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'name', type: 'string'),
        ]),
        new OA\Property(property: 'student', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'student_number', type: 'string'),
            new OA\Property(property: 'full_name', type: 'string'),
        ]),
        new OA\Property(property: 'reason', type: 'string', nullable: true),
        new OA\Property(property: 'rejection_reason', type: 'string', nullable: true),
        new OA\Property(property: 'submitted_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'processed_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'document', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'status', type: 'string', enum: ['valid', 'revoked', 'expired']),
            new OA\Property(property: 'file_name', type: 'string'),
            new OA\Property(property: 'file_size', type: 'integer'),
            new OA\Property(property: 'generated_at', type: 'string', format: 'date-time'),
            new OA\Property(property: 'verification_token', type: 'string'),
            new OA\Property(property: 'checksum', type: 'string', description: 'SHA-256 of the PDF.'),
        ]),
        new OA\Property(property: 'invoice', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'invoice_number', type: 'string'),
            new OA\Property(property: 'total', type: 'number', format: 'float'),
            new OA\Property(property: 'amount_paid', type: 'number', format: 'float'),
            new OA\Property(property: 'status', type: 'string'),
            new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true),
        ]),
    ]
)]
#[OA\Schema(
    schema: 'DocumentRequestCollection',
    type: 'object',
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/DocumentRequest')),
        new OA\Property(property: 'links', type: 'object'),
        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
    ]
)]
#[OA\Schema(
    schema: 'DocumentRequestResourceResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/DocumentRequest')]
)]
#[OA\Schema(
    schema: 'StoreDocumentRequestRequest',
    type: 'object',
    required: ['document_type_id'],
    properties: [
        new OA\Property(property: 'document_type_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'semester_id', type: 'integer', format: 'int64', nullable: true, description: 'Required for an academic result.'),
        new OA\Property(property: 'reason', type: 'string', maxLength: 500, nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'DocumentType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'code', type: 'string'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'requires_fee', type: 'boolean'),
        new OA\Property(property: 'fee_amount', type: 'number', format: 'float'),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'sort_order', type: 'integer'),
        new OA\Property(property: 'needs_semester', type: 'boolean', nullable: true),
        new OA\Property(property: 'requests_count', type: 'integer', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'DocumentVerification',
    type: 'object',
    description: 'Minimal public data — no grades or contact details.',
    properties: [
        new OA\Property(property: 'status', type: 'string', enum: ['valid', 'revoked', 'expired']),
        new OA\Property(property: 'document_type', type: 'string'),
        new OA\Property(property: 'semester', type: 'string', nullable: true),
        new OA\Property(property: 'issued_to', type: 'string'),
        new OA\Property(property: 'student_number', type: 'string'),
        new OA\Property(property: 'issued_on', type: 'string', format: 'date'),
        new OA\Property(property: 'issuer', type: 'string', nullable: true),
        new OA\Property(property: 'checksum', type: 'string', nullable: true, description: 'SHA-256 to compare with the file in hand.'),
    ]
)]
class DocumentRequest {}
