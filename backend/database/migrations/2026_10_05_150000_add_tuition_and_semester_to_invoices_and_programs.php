<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->decimal('tuition_per_credit', 8, 2)->nullable()->default(50.00)->after('credits_required');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->after('student_id')->constrained('semesters')->nullOnDelete();
            $table->index(['student_id', 'semester_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['student_id', 'semester_id']);
            $table->dropConstrainedForeignId('semester_id');
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('tuition_per_credit');
        });
    }
};
