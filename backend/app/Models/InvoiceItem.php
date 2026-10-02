<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of an invoice; `amount = quantity × unit_price` (DB check).
 *
 * @property int $id
 * @property int $invoice_id
 * @property string $description
 * @property numeric-string $quantity
 * @property numeric-string $unit_price
 * @property numeric-string $amount
 * @property string|null $fee_category
 */
class InvoiceItem extends Model
{
    public const CATEGORIES = ['tuition', 'registration', 'exam', 'library', 'laboratory', 'document', 'other'];

    protected $fillable = ['invoice_id', 'description', 'quantity', 'unit_price', 'amount', 'fee_category'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'unit_price' => 'decimal:2', 'amount' => 'decimal:2'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
