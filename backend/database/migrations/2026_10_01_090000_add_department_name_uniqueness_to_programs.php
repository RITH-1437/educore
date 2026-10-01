<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Program names are unique *within a department*
     * (`skills/program-management/SKILL.md` §4). The base `programs` table only
     * enforced a globally unique `code`, so two programs could share a name
     * inside one department.
     *
     * Additive only: no column is dropped, renamed or retyped.
     */
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->unique(['department_id', 'name'], 'uq_programs_department_id_name');
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropUnique('uq_programs_department_id_name');
        });
    }
};
