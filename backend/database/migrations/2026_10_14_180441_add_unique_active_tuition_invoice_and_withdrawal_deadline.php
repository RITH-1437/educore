<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add optional withdrawal_deadline date to semesters (defaults to end_date if null)
        Schema::table('semesters', function (Blueprint $table) {
            $table->date('withdrawal_deadline')->nullable()->after('enrollment_end');
        });

        // 2. Partial unique index on invoices guaranteeing only one non-cancelled active tuition invoice per student per semester
        DB::statement(
            "CREATE UNIQUE INDEX uq_invoices_student_semester_active ON invoices (student_id, semester_id) WHERE semester_id IS NOT NULL AND status != 'cancelled' AND deleted_at IS NULL"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS uq_invoices_student_semester_active');

        Schema::table('semesters', function (Blueprint $table) {
            $table->dropColumn('withdrawal_deadline');
        });
    }
};
