<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Invoice {{ $invoice->invoice_number }} — {{ $student->student_number }}</title>
<style>
    /* dompdf supports CSS 2.1 only; brand colours from docs/branding (navy #0F172A, primary #2563EB, muted #64748B). */
    @page { margin: 24mm 18mm 22mm 18mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1E293B; line-height: 1.45; }
    header { position: fixed; top: -18mm; left: 0; right: 0; border-bottom: 2px solid #2563EB; padding-bottom: 6px; }
    header .name { font-size: 13pt; font-weight: bold; color: #0F172A; }
    header .meta { font-size: 8pt; color: #64748B; }
    footer { position: fixed; bottom: -16mm; left: 0; right: 0; border-top: 1px solid #CBD5E1; padding-top: 5px; font-size: 8pt; color: #64748B; }
    footer .page-number:before { content: counter(page); }

    .invoice-header { width: 100%; margin-top: 4px; margin-bottom: 16px; border-collapse: collapse; }
    .invoice-header td { vertical-align: top; border: none; padding: 0; }
    .doc-title { font-size: 16pt; font-weight: bold; color: #0F172A; letter-spacing: 0.5px; margin: 0 0 4px 0; }
    .badge { display: inline-block; padding: 3px 8px; font-size: 8pt; font-weight: bold; text-transform: uppercase; border-radius: 3px; }
    .badge-paid { background: #DCFCE7; color: #166534; }
    .badge-partial { background: #FEF9C3; color: #854D0E; }
    .badge-pending { background: #E0F2FE; color: #0369A1; }
    .badge-overdue { background: #FEE2E2; color: #991B1B; }
    .badge-cancelled { background: #F1F5F9; color: #475569; }

    .info-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .info-table td { vertical-align: top; border: none; padding: 0; }
    .info-box { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 4px; padding: 10px 12px; }
    .box-title { font-size: 8.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #64748B; margin: 0 0 6px 0; border-bottom: 1px solid #E2E8F0; padding-bottom: 4px; }
    .facts-list { width: 100%; border-collapse: collapse; font-size: 9pt; }
    .facts-list td { padding: 2px 0; vertical-align: top; border: none; }
    .facts-list td.lbl { width: 38%; color: #64748B; }
    .facts-list td.val { font-weight: 500; color: #0F172A; }

    .grid { width: 100%; border-collapse: collapse; margin-top: 8px; margin-bottom: 12px; }
    .grid th { background: #F1F5F9; color: #0F172A; text-align: left; font-size: 8.5pt; text-transform: uppercase; letter-spacing: 0.3px; padding: 6px 8px; border-bottom: 1.5px solid #CBD5E1; }
    .grid td { padding: 5px 8px; border-bottom: 1px solid #E2E8F0; font-size: 9pt; vertical-align: middle; }
    .grid .num { text-align: right; }
    .grid .center { text-align: center; }

    .totals-table { width: 45%; margin-left: auto; border-collapse: collapse; margin-top: 4px; margin-bottom: 16px; font-size: 9.5pt; }
    .totals-table td { padding: 3px 6px; border: none; }
    .totals-table td.lbl { color: #64748B; }
    .totals-table td.val { text-align: right; font-weight: 500; }
    .totals-table tr.grand-total td { border-top: 1.5px solid #0F172A; border-bottom: 1.5px solid #0F172A; font-weight: bold; font-size: 10.5pt; color: #0F172A; padding: 5px 6px; }

    .section-title { font-size: 10.5pt; font-weight: bold; color: #0F172A; margin: 16px 0 6px 0; }
    .notes-box { background: #F8FAFC; border-left: 3px solid #2563EB; padding: 8px 12px; font-size: 8.5pt; color: #334155; margin-bottom: 16px; }

    .signature-area { width: 100%; border-collapse: collapse; margin-top: 28px; }
    .signature-area td { vertical-align: top; border: none; padding: 0; }
    .signature-line { width: 200px; border-top: 1px solid #94A3B8; padding-top: 4px; font-size: 8.5pt; color: #64748B; }
</style>
</head>
<body>
<header>
    <div class="name">{{ $university?->name ?? 'EduCore University' }}</div>
    <div class="meta">
        {{ collect([$university?->address, $university?->phone, $university?->email, $university?->website])->filter()->join(' · ') }}
    </div>
</header>

<footer>
    <table style="width: 100%; border-collapse: collapse; border: none; margin: 0; padding: 0;">
        <tr>
            <td style="text-align: left; border: none; padding: 0;">
                Official Invoice & Receipt · Issued {{ $issuedOn }} by EduCore University
            </td>
            <td style="text-align: right; border: none; padding: 0;">
                Page <span class="page-number"></span>
            </td>
        </tr>
    </table>
</footer>

<main>
    <table class="invoice-header">
        <tr>
            <td style="width: 55%;">
                <div class="doc-title">INVOICE & RECEIPT</div>
                <div style="font-size: 9.5pt; color: #64748B; margin-top: 2px;">
                    Invoice Ref: <strong style="color: #0F172A;">{{ $invoice->invoice_number }}</strong>
                </div>
            </td>
            <td style="width: 45%; text-align: right;">
                <div style="margin-bottom: 4px;">
                    @php
                        $badgeClass = match($invoice->status) {
                            'paid' => 'badge-paid',
                            'partial' => 'badge-partial',
                            'pending' => 'badge-pending',
                            'overdue' => 'badge-overdue',
                            'cancelled' => 'badge-cancelled',
                            default => 'badge-pending',
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{ strtoupper($invoice->status) }}</span>
                </div>
                <div style="font-size: 8.5pt; color: #64748B;">
                    Issue Date: <strong style="color: #0F172A;">{{ $invoice->issued_date?->format('Y-m-d') }}</strong><br>
                    Due Date: <strong style="color: #0F172A;">{{ $invoice->due_date?->format('Y-m-d') }}</strong>
                </div>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td style="width: 49%; padding-right: 8px;">
                <div class="info-box">
                    <div class="box-title">Billed To (Student)</div>
                    <table class="facts-list">
                        <tr><td class="lbl">Student Name:</td><td class="val">{{ $student->fullName() }}</td></tr>
                        <tr><td class="lbl">Student ID:</td><td class="val">{{ $student->student_number }}</td></tr>
                        @php
                            $program = $student->currentProgram?->program;
                        @endphp
                        @if($program)
                        <tr><td class="lbl">Program:</td><td class="val">{{ $program->name }}</td></tr>
                        @if($program->department)
                        <tr><td class="lbl">Department:</td><td class="val">{{ $program->department->name }}</td></tr>
                        @endif
                        @endif
                        @if($student->user?->email)
                        <tr><td class="lbl">Email:</td><td class="val">{{ $student->user->email }}</td></tr>
                        @endif
                    </table>
                </div>
            </td>
            <td style="width: 49%; padding-left: 8px;">
                <div class="info-box">
                    <div class="box-title">Invoice Summary</div>
                    <table class="facts-list">
                        <tr><td class="lbl">Title:</td><td class="val">{{ $invoice->title }}</td></tr>
                        <tr><td class="lbl">Currency:</td><td class="val">{{ $invoice->currency }}</td></tr>
                        <tr><td class="lbl">Total Invoiced:</td><td class="val">{{ number_format((float) $invoice->total, 2) }} {{ $invoice->currency }}</td></tr>
                        <tr><td class="lbl">Amount Paid:</td><td class="val" style="color: #166534;">{{ number_format((float) $invoice->amount_paid, 2) }} {{ $invoice->currency }}</td></tr>
                        <tr>
                            <td class="lbl">Balance Due:</td>
                            <td class="val" style="{{ $invoice->balanceCents() > 0 ? 'color: #991B1B; font-weight: bold;' : 'color: #166534;' }}">
                                {{ $invoice->status === 'cancelled' ? '0.00' : number_format((float) $invoice->balance(), 2) }} {{ $invoice->currency }}
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Itemized Charges</div>
    <table class="grid">
        <thead>
            <tr>
                <th style="width: 6%;" class="center">#</th>
                <th style="width: 46%;">Description</th>
                <th style="width: 18%;">Category</th>
                <th style="width: 8%;" class="center">Qty</th>
                <th style="width: 11%;" class="num">Unit Price</th>
                <th style="width: 11%;" class="num">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $idx => $item)
            <tr>
                <td class="center" style="color: #64748B;">{{ $idx + 1 }}</td>
                <td><strong>{{ $item->description }}</strong></td>
                <td style="color: #475569; text-transform: capitalize;">{{ str_replace('_', ' ', $item->fee_category) }}</td>
                <td class="center">{{ rtrim(rtrim((string)$item->quantity, '0'), '.') }}</td>
                <td class="num">{{ number_format((float) $item->unit_price, 2) }}</td>
                <td class="num" style="font-weight: 500;">{{ number_format((float) $item->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="lbl">Subtotal:</td>
            <td class="val">{{ number_format((float) $invoice->subtotal, 2) }} {{ $invoice->currency }}</td>
        </tr>
        @if((float)$invoice->discount > 0)
        <tr>
            <td class="lbl">Discount:</td>
            <td class="val" style="color: #166534;">-{{ number_format((float) $invoice->discount, 2) }} {{ $invoice->currency }}</td>
        </tr>
        @endif
        <tr class="grand-total">
            <td>Total Amount:</td>
            <td class="val">{{ number_format((float) $invoice->total, 2) }} {{ $invoice->currency }}</td>
        </tr>
        <tr>
            <td class="lbl" style="padding-top: 6px;">Total Paid:</td>
            <td class="val" style="padding-top: 6px; color: #166534;">{{ number_format((float) $invoice->amount_paid, 2) }} {{ $invoice->currency }}</td>
        </tr>
        <tr>
            <td class="lbl" style="font-weight: bold;">Balance Due:</td>
            <td class="val" style="font-weight: bold; {{ $invoice->balanceCents() > 0 ? 'color: #991B1B;' : 'color: #166534;' }}">
                {{ $invoice->status === 'cancelled' ? '0.00' : number_format((float) $invoice->balance(), 2) }} {{ $invoice->currency }}
            </td>
        </tr>
    </table>

    @if($invoice->payments->isNotEmpty())
    <div class="section-title">Payment History & Receipts</div>
    <table class="grid">
        <thead>
            <tr>
                <th style="width: 16%;">Date</th>
                <th style="width: 18%;">Method</th>
                <th style="width: 26%;">Reference / Notes</th>
                <th style="width: 24%;">Received By</th>
                <th style="width: 16%;" class="num">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->payments as $payment)
            <tr style="{{ $payment->is_reversal ? 'color: #991B1B; background: #FEF2F2;' : '' }}">
                <td>{{ $payment->paid_on?->format('Y-m-d') }}</td>
                <td style="text-transform: capitalize;">{{ str_replace('_', ' ', $payment->method) }}</td>
                <td style="font-size: 8pt; color: #475569;">
                    @if($payment->is_reversal)
                        <em>Reversal: {{ $payment->notes ?? 'Reversed' }}</em>
                    @else
                        {{ $payment->reference ?: '—' }}
                    @endif
                </td>
                <td>{{ $payment->receiver?->name ?? 'Accounts Office' }}</td>
                <td class="num" style="font-weight: 500; {{ $payment->is_reversal ? 'color: #991B1B;' : 'color: #166534;' }}">
                    {{ $payment->is_reversal ? '-' : '+' }}{{ number_format((float) $payment->amount, 2) }} {{ $invoice->currency }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($invoice->notes)
    <div class="notes-box">
        <strong>Notes:</strong> {{ $invoice->notes }}
    </div>
    @endif

    <table class="signature-area">
        <tr>
            <td style="width: 60%;">
                <div style="font-size: 8.5pt; color: #64748B;">
                    For billing inquiries, please contact the University Accounts Office.<br>
                    Payments must be accompanied by the official invoice reference number.
                </div>
            </td>
            <td style="width: 40%; text-align: right;">
                <div class="signature-line" style="margin-left: auto;">
                    Authorized Signature<br>
                    Accounts & Finance Office<br>
                    {{ $university?->name ?? 'EduCore University' }}
                </div>
            </td>
        </tr>
    </table>
</main>
</body>
</html>
