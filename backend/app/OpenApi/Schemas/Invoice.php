<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Invoices and payment records (module 9.18). */
#[OA\Schema(
    schema: 'Invoice',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'invoice_number', type: 'string', example: 'INV-2026-00001'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'currency', type: 'string', enum: ['USD', 'KHR']),
        new OA\Property(property: 'subtotal', type: 'number'),
        new OA\Property(property: 'discount', type: 'number'),
        new OA\Property(property: 'total', type: 'number'),
        new OA\Property(property: 'amount_paid', type: 'number'),
        new OA\Property(property: 'balance', type: 'number'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'partial', 'paid', 'overdue', 'cancelled']),
        new OA\Property(property: 'issued_date', type: 'string', format: 'date'),
        new OA\Property(property: 'due_date', type: 'string', format: 'date'),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
        new OA\Property(property: 'student', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'student_number', type: 'string'),
            new OA\Property(property: 'full_name', type: 'string'),
        ]),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'description', type: 'string'),
            new OA\Property(property: 'quantity', type: 'number'),
            new OA\Property(property: 'unit_price', type: 'number'),
            new OA\Property(property: 'amount', type: 'number'),
            new OA\Property(property: 'fee_category', type: 'string', nullable: true),
        ])),
        new OA\Property(property: 'payments', type: 'array', items: new OA\Items(properties: [
            new OA\Property(property: 'id', type: 'integer', format: 'int64'),
            new OA\Property(property: 'amount', type: 'number'),
            new OA\Property(property: 'paid_on', type: 'string', format: 'date'),
            new OA\Property(property: 'method', type: 'string', enum: ['cash', 'bank_transfer', 'cheque', 'other']),
            new OA\Property(property: 'reference', type: 'string', nullable: true),
            new OA\Property(property: 'notes', type: 'string', nullable: true),
            new OA\Property(property: 'is_reversal', type: 'boolean'),
            new OA\Property(property: 'reversal_of', type: 'integer', format: 'int64', nullable: true),
            new OA\Property(property: 'reversed', type: 'boolean'),
            new OA\Property(property: 'received_by', type: 'string', nullable: true),
        ])),
    ]
)]
#[OA\Schema(
    schema: 'InvoiceCollection',
    type: 'object',
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Invoice')),
        new OA\Property(property: 'links', type: 'object'),
        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
    ]
)]
#[OA\Schema(
    schema: 'InvoiceResourceResponse',
    type: 'object',
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Invoice')]
)]
#[OA\Schema(
    schema: 'StudentInvoicesResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Invoice')),
        new OA\Property(property: 'summary', type: 'array', description: 'One row per currency.', items: new OA\Items(properties: [
            new OA\Property(property: 'currency', type: 'string'),
            new OA\Property(property: 'invoiced', type: 'number'),
            new OA\Property(property: 'paid', type: 'number'),
            new OA\Property(property: 'balance', type: 'number'),
            new OA\Property(property: 'overdue', type: 'number'),
            new OA\Property(property: 'count', type: 'integer'),
        ])),
    ]
)]
#[OA\Schema(
    schema: 'InvoiceRequest',
    type: 'object',
    required: ['title', 'due_date', 'items'],
    properties: [
        new OA\Property(property: 'student_id', type: 'integer', format: 'int64', description: 'Required on create; prohibited on edit.'),
        new OA\Property(property: 'title', type: 'string', maxLength: 255),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'currency', type: 'string', enum: ['USD', 'KHR'], nullable: true),
        new OA\Property(property: 'issued_date', type: 'string', format: 'date', nullable: true, description: 'Defaults to today.'),
        new OA\Property(property: 'due_date', type: 'string', format: 'date'),
        new OA\Property(property: 'discount', type: 'number', minimum: 0, nullable: true),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
        new OA\Property(property: 'items', type: 'array', minItems: 1, maxItems: 50, items: new OA\Items(
            required: ['description', 'quantity', 'unit_price'],
            properties: [
                new OA\Property(property: 'description', type: 'string', maxLength: 255),
                new OA\Property(property: 'quantity', type: 'number', minimum: 0.01),
                new OA\Property(property: 'unit_price', type: 'number', minimum: 0),
                new OA\Property(property: 'fee_category', type: 'string', enum: ['tuition', 'registration', 'exam', 'library', 'laboratory', 'document', 'other'], nullable: true),
            ]
        )),
    ]
)]
#[OA\Schema(
    schema: 'RecordPaymentRequest',
    type: 'object',
    required: ['amount', 'paid_on', 'method'],
    properties: [
        new OA\Property(property: 'amount', type: 'number', minimum: 0.01),
        new OA\Property(property: 'paid_on', type: 'string', format: 'date'),
        new OA\Property(property: 'method', type: 'string', enum: ['cash', 'bank_transfer', 'cheque', 'other']),
        new OA\Property(property: 'reference', type: 'string', maxLength: 100, nullable: true),
        new OA\Property(property: 'notes', type: 'string', maxLength: 1000, nullable: true),
    ]
)]
class Invoice {}
