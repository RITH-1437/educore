<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds fee waiver fields to document requests
 * (`docs/41_Document-Fee-Waiver-and-Tuition-Invoicing-Report.md`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->boolean('is_fee_waived')->default(false)->after('invoice_id');
            $table->foreignId('waived_by')->nullable()->after('is_fee_waived')
                ->references('id')->on('users')->nullOnDelete();
            $table->timestamp('waived_at')->nullable()->after('waived_by');
            $table->string('waiver_reason', 500)->nullable()->after('waived_at');
        });
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropForeign(['waived_by']);
            $table->dropColumn(['is_fee_waived', 'waived_by', 'waived_at', 'waiver_reason']);
        });
    }
};
