<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A charge to a student (`skills/invoices-payments`). Totals come from the
 * line items; `amount_paid` and `status` are kept consistent by
 * `InvoiceService` on every change. Never deleted — cancelled instead.
 *
 * @property int $id
 * @property int $student_id
 * @property string $invoice_number
 * @property string $title
 * @property string|null $description
 * @property string $currency
 * @property numeric-string $subtotal
 * @property numeric-string $discount
 * @property numeric-string $total
 * @property numeric-string $amount_paid
 * @property string $status
 * @property Carbon $issued_date
 * @property Carbon $due_date
 * @property string|null $notes
 * @property-read Student $student
 */
class Invoice extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [self::STATUS_PENDING, self::STATUS_PARTIAL, self::STATUS_PAID, self::STATUS_OVERDUE, self::STATUS_CANCELLED];

    /** Statuses with money still owed. */
    public const UNPAID_STATUSES = [self::STATUS_PENDING, self::STATUS_PARTIAL, self::STATUS_OVERDUE];

    public const CURRENCIES = ['USD', 'KHR'];

    protected $fillable = [
        'student_id', 'invoice_number', 'title', 'description', 'currency', 'subtotal', 'discount',
        'total', 'amount_paid', 'status', 'issued_date', 'due_date', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'issued_date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('paid_on')->orderBy('id');
    }

    public function documentRequest(): HasOne
    {
        return $this->hasOne(DocumentRequest::class);
    }

    /** Remaining balance in cents (exact). */
    public function balanceCents(): int
    {
        return self::cents($this->total) - self::cents($this->amount_paid);
    }

    /** Remaining balance formatted as standard 2-decimal string. */
    public function balance(): string
    {
        return number_format($this->balanceCents() / 100, 2, '.', '');
    }

    public static function cents(string|int|float|null $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
