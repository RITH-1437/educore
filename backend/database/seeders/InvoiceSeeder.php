<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Student;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Database\Seeder;

/**
 * Demo finance records (module 9.18) through `InvoiceService`, so totals,
 * numbering and statuses follow the real rules: one tuition invoice per active
 * student, with a full, partial or no payment by rotation. Idempotent: skipped
 * when invoices already exist.
 */
class InvoiceSeeder extends Seeder
{
    public function run(InvoiceService $invoices): void
    {
        $admin = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'super-admin'))->first();

        if ($admin === null || Invoice::query()->exists()) {
            return;
        }

        foreach (Student::query()->where('status', Student::STATUS_ACTIVE)->orderBy('id')->limit(12)->get() as $i => $student) {
            $invoice = $invoices->create([
                'student_id' => $student->id,
                'title' => 'Tuition fee — current semester',
                'currency' => 'USD',
                'issued_date' => now()->subDays(40)->toDateString(),
                'due_date' => now()->subDays(40 - ($i % 2 === 0 ? 60 : 20))->toDateString(),
                'discount' => $i % 4 === 0 ? 50 : 0,
                'items' => [
                    ['description' => 'Tuition', 'quantity' => 1, 'unit_price' => 600, 'fee_category' => 'tuition'],
                    ['description' => 'Registration fee', 'quantity' => 1, 'unit_price' => 25, 'fee_category' => 'registration'],
                    ['description' => 'Library fee', 'quantity' => 1, 'unit_price' => 15, 'fee_category' => 'library'],
                ],
            ]);

            match ($i % 3) {
                0 => $invoices->recordPayment($invoice, ['amount' => $invoice->total, 'paid_on' => now()->subDays(30)->toDateString(), 'method' => 'bank_transfer', 'reference' => 'BT-'.(1000 + $i)], $admin),
                1 => $invoices->recordPayment($invoice, ['amount' => 300, 'paid_on' => now()->subDays(25)->toDateString(), 'method' => 'cash'], $admin),
                default => null,
            };
        }
    }
}
