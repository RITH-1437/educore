<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Brings the university-structure tables in line with
 * `skills/faculty-department/SKILL.md` §4 and §9:
 *
 * - faculty names are unique (`skills/faculty-department/SKILL.md` §4);
 * - department names are unique within their faculty (§4, `[faculty_id, name]`);
 * - departments gain soft deletes so they can be archived instead of hard
 *   deleted once programs, courses and lecturers reference them (§4).
 *
 * Additive only: no existing column is dropped, renamed or retyped. Existing
 * rows cannot violate the new constraints because uniqueness is enforced in the
 * form requests first, and the tables hold no conflicting data.
 *
 * `universities` deliberately stays without `deleted_at` — it is single-tenant
 * reference data with an `is_current` flag, not archivable history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            $table->unique('name', 'uq_faculties_name');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->softDeletesTz();

            $table->unique(['faculty_id', 'name'], 'uq_departments_faculty_id_name');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique('uq_departments_faculty_id_name');
            $table->dropColumn('deleted_at');
        });

        Schema::table('faculties', function (Blueprint $table) {
            $table->dropUnique('uq_faculties_name');
        });
    }
};
