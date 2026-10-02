<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * A record of money received (no online gateway — `skills/invoices-payments`
 * §4). Append-only: a correction is a reversal row (`is_reversal`,
 * `reversal_of`) carrying the same positive amount, never an edit or delete.
 *
 * @property int $id
 * @property int $invoice_id
 * @property numeric-string $amount
 * @property Carbon $paid_on
 * @property string $method
 * @property string|null $reference
 * @property int|null $received_by
 * @property string|null $notes
 * @property bool $is_reversal
 * @property int|null $reversal_of
 */
class Payment extends Model
{
    public const METHODS = ['cash', 'bank_transfer', 'cheque', 'other'];

    protected $fillable = ['invoice_id', 'amount', 'paid_on', 'method', 'reference', 'received_by', 'notes', 'is_reversal', 'reversal_of'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_on' => 'date', 'is_reversal' => 'boolean'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /** The reversal row cancelling this payment, if any. */
    public function reversal(): HasOne
    {
        return $this->hasOne(self::class, 'reversal_of');
    }
}
