<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id');
            $table->decimal('amount', 12, 2);
            $table->date('paid_on');
            $table->string('method', 20);
            $table->string('reference', 100)->nullable();
            $table->foreignId('received_by')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_reversal')->default(false);
            $table->foreignId('reversal_of')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('invoice_id', 'idx_payments_invoice');
            $table->index('paid_on', 'idx_payments_paid_on');

            $table->foreign('invoice_id', 'fk_payments_invoice')
                ->references('id')->on('invoices')->restrictOnDelete();
            $table->foreign('received_by', 'fk_payments_received_by')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('reversal_of', 'fk_payments_reversal_of')
                ->references('id')->on('payments')->nullOnDelete();
        });

        DB::statement('ALTER TABLE payments ADD CONSTRAINT ck_payments_amount CHECK (amount > 0)');
        DB::statement('ALTER TABLE payments ADD CONSTRAINT ck_payments_method CHECK (method IN (\'cash\', \'bank_transfer\', \'cheque\', \'other\'))');
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};