<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id');
            $table->string('invoice_number', 50);
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('currency', 10)->default('USD');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->date('issued_date')->default(DB::raw('CURRENT_DATE'));
            $table->date('due_date');
            $table->text('notes')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->softDeletesTz();

            $table->unique('invoice_number', 'uq_invoices_number');
            $table->index(['student_id', 'status'], 'idx_invoices_student_status');
            $table->index('due_date', 'idx_invoices_due_date');

            $table->foreign('student_id', 'fk_invoices_student')
                ->references('id')->on('students')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE invoices ADD CONSTRAINT ck_invoices_status CHECK (status IN (\'pending\', \'partial\', \'paid\', \'overdue\', \'cancelled\'))');
        DB::statement('ALTER TABLE invoices ADD CONSTRAINT ck_invoices_total CHECK (total >= 0)');
        DB::statement('ALTER TABLE invoices ADD CONSTRAINT ck_invoices_paid CHECK (amount_paid >= 0 AND amount_paid <= total)');
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};