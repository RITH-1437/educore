<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the faculty level (`docs/39_Department-Only-Structure-Report.md`):
 * University → Department → Program.
 *
 * - Departments move under the university (`university_id`, backfilled from
 *   their faculty). Names become unique per university; a name that two
 *   faculties shared gets its code appended instead of failing.
 * - Unit admins are scoped to a department: `users.faculty_id` is replaced by
 *   `users.department_id` (left empty — a faculty cannot be mapped to one of
 *   its departments, so Department Admins are re-assigned in the UI).
 * - The `faculty-admin` role becomes `department-admin` (same id, so every
 *   account keeps its role).
 * - Announcements addressed to a faculty go to administrative staff only
 *   (the narrowest existing audience) rather than disappearing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('university_id')->nullable()->after('id');
        });

        DB::statement('update departments d set university_id = f.university_id from faculties f where f.id = d.faculty_id');
        // Defensive: a department whose faculty vanished goes to the current (or first) university.
        DB::statement('update departments set university_id = (select id from universities order by is_current desc, id limit 1) where university_id is null');

        // Department names were unique per faculty; make them unique per university.
        DB::statement(<<<'SQL'
            update departments d set name = d.name || ' (' || d.code || ')'
            where exists (
                select 1 from departments o
                where o.university_id = d.university_id and o.name = d.name and o.id < d.id
            )
        SQL);

        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique('uq_departments_faculty_id_name');
            $table->dropForeign('fk_departments_faculty');
            $table->dropIndex('idx_departments_faculty');
            $table->dropColumn('faculty_id');
        });

        if (DB::table('departments')->whereNull('university_id')->exists()) {
            throw new RuntimeException('Departments without a university remain; create a university first.');
        }

        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('university_id')->nullable(false)->change();
            $table->unique(['university_id', 'name'], 'uq_departments_university_id_name');
            $table->index('university_id', 'idx_departments_university');
            $table->foreign('university_id', 'fk_departments_university')
                ->references('id')->on('universities')->restrictOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('fk_users_faculty');
            $table->dropIndex('idx_users_faculty');
            $table->dropColumn('faculty_id');
            $table->foreignId('department_id')->nullable()->after('role_id');
            $table->index('department_id', 'idx_users_department');
            $table->foreign('department_id', 'fk_users_department')->references('id')->on('departments')->nullOnDelete();
        });

        DB::table('roles')->where('slug', 'faculty-admin')->update([
            'slug' => 'department-admin',
            'name' => 'Department Admin',
            'description' => 'Administration within an assigned department.',
        ]);

        DB::table('announcements')->where('audience_type', 'faculty')->update(['audience_type' => 'staff', 'audience_id' => null]);

        // Internship evaluations by the university side were typed `faculty`; they are `academic` now.
        DB::statement('ALTER TABLE internship_evaluations DROP CONSTRAINT ck_internship_evaluations_type');
        DB::table('internship_evaluations')->where('evaluator_type', 'faculty')->update(['evaluator_type' => 'academic']);
        DB::statement("ALTER TABLE internship_evaluations ADD CONSTRAINT ck_internship_evaluations_type CHECK (evaluator_type IN ('supervisor', 'academic'))");

        Schema::drop('faculties');
    }

    /**
     * Restores the structure (not the data): faculties come back empty, and the
     * column links are left null.
     */
    public function down(): void
    {
        Schema::create('faculties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id');
            $table->string('code', 50);
            $table->string('name', 255);
            $table->string('dean_name', 255)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->softDeletesTz();

            $table->unique('code', 'uq_faculties_code');
            $table->unique('name', 'uq_faculties_name');
            $table->index('university_id', 'idx_faculties_university');
            $table->foreign('university_id', 'fk_faculties_university')
                ->references('id')->on('universities')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE internship_evaluations DROP CONSTRAINT ck_internship_evaluations_type');
        DB::table('internship_evaluations')->where('evaluator_type', 'academic')->update(['evaluator_type' => 'faculty']);
        DB::statement("ALTER TABLE internship_evaluations ADD CONSTRAINT ck_internship_evaluations_type CHECK (evaluator_type IN ('supervisor', 'faculty'))");

        DB::table('roles')->where('slug', 'department-admin')->update([
            'slug' => 'faculty-admin',
            'name' => 'Faculty / Department Admin',
            'description' => 'Administration within an assigned faculty or department.',
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('fk_users_department');
            $table->dropIndex('idx_users_department');
            $table->dropColumn('department_id');
            $table->foreignId('faculty_id')->nullable()->after('role_id');
            $table->index('faculty_id', 'idx_users_faculty');
            $table->foreign('faculty_id', 'fk_users_faculty')->references('id')->on('faculties')->nullOnDelete();
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign('fk_departments_university');
            $table->dropIndex('idx_departments_university');
            $table->dropUnique('uq_departments_university_id_name');
            $table->dropColumn('university_id');
            $table->foreignId('faculty_id')->nullable()->after('id');
            $table->index('faculty_id', 'idx_departments_faculty');
            $table->unique(['faculty_id', 'name'], 'uq_departments_faculty_id_name');
            $table->foreign('faculty_id', 'fk_departments_faculty')
                ->references('id')->on('faculties')->restrictOnDelete();
        });
    }
};
