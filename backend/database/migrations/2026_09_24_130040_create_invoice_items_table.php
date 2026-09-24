<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id');
            $table->string('description', 255);
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('amount', 12, 2);
            $table->string('fee_category', 50)->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('invoice_id', 'idx_invoice_items_invoice');

            $table->foreign('invoice_id', 'fk_invoice_items_invoice')
                ->references('id')->on('invoices')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE invoice_items ADD CONSTRAINT ck_invoice_items_quantity CHECK (quantity > 0)');
        DB::statement('ALTER TABLE invoice_items ADD CONSTRAINT ck_invoice_items_unit_price CHECK (unit_price >= 0)');
        DB::statement('ALTER TABLE invoice_items ADD CONSTRAINT ck_invoice_items_amount CHECK (amount = quantity * unit_price)');
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};