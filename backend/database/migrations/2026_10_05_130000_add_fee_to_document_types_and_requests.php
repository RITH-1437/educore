<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds fee amount configuration to document types and links invoices to document requests
 * (`docs/40_Document-Fee-Billing-and-Type-Management-Report.md`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->decimal('fee_amount', 10, 2)->default(0)->after('requires_fee');
        });

        Schema::table('document_requests', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('semester_id');
            $table->index('invoice_id', 'idx_document_requests_invoice');
            $table->foreign('invoice_id', 'fk_document_requests_invoice')
                ->references('id')->on('invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropForeign('fk_document_requests_invoice');
            $table->dropIndex('idx_document_requests_invoice');
            $table->dropColumn('invoice_id');
        });

        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn('fee_amount');
        });
    }
};
