<?php

namespace App\Http\Resources;

use App\Models\DocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A document request with its generated document (if any). Only callers who
 * may view the request receive it, so the verification code is included.
 *
 * @mixin DocumentRequest
 */
class DocumentRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'type' => $this->whenLoaded('type', fn () => [
                'id' => $this->type->id,
                'code' => $this->type->code,
                'name' => $this->type->name,
                'requires_fee' => $this->type->requires_fee,
                'fee_amount' => (float) $this->type->fee_amount,
            ]),
            'semester' => $this->whenLoaded('semester', fn () => $this->semester ? ['id' => $this->semester->id, 'name' => trim(($this->semester->academicYear?->code ?? '').' '.$this->semester->name)] : null),
            'student' => $this->whenLoaded('student', fn () => ['id' => $this->student->id, 'student_number' => $this->student->student_number, 'full_name' => $this->student->fullName()]),
            'reason' => $this->reason,
            'rejection_reason' => $this->rejection_reason,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'processed_at' => $this->processed_at?->toIso8601String(),
            'document' => $this->whenLoaded('document', fn () => $this->document ? [
                'id' => $this->document->id,
                'status' => $this->document->status,
                'file_name' => $this->document->file_name,
                'file_size' => $this->document->file_size,
                'generated_at' => $this->document->generated_at->toIso8601String(),
                'verification_token' => $this->document->verification_token,
                'checksum' => $this->document->checksum,
            ] : null),
            'invoice' => $this->whenLoaded('invoice', fn () => $this->invoice ? [
                'id' => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
                'total' => (float) $this->invoice->total,
                'amount_paid' => (float) $this->invoice->amount_paid,
                'status' => $this->invoice->status,
                'due_date' => $this->invoice->due_date?->toDateString(),
            ] : null),
        ];
    }
}
