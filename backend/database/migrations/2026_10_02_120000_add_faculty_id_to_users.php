<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The faculty a Faculty / Department Admin administers
     * (`skills/authorization/SKILL.md`: "within their assigned faculty or
     * department only"). Only set for that role; null elsewhere. A Faculty
     * Admin without a faculty sees no unit data (fail closed), so removing a
     * faculty nulls the link rather than blocking the delete.
     *
     * Additive only: no column is dropped, renamed or retyped.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('faculty_id')->nullable()->after('role_id');
            $table->index('faculty_id', 'idx_users_faculty');
            $table->foreign('faculty_id', 'fk_users_faculty')->references('id')->on('faculties')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('fk_users_faculty');
            $table->dropIndex('idx_users_faculty');
            $table->dropColumn('faculty_id');
        });
    }
};
