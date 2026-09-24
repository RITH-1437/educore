<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id');
            $table->string('phone', 50)->nullable();
            $table->string('avatar_key', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('last_login_at')->nullable();
            $table->softDeletesTz();

            $table->index('role_id', 'idx_users_role');

            $table->foreign('role_id', 'fk_users_role')
                ->references('id')->on('roles')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE users ALTER COLUMN email_verified_at TYPE TIMESTAMP(0) WITH TIME ZONE');
        DB::statement('ALTER TABLE users ALTER COLUMN created_at TYPE TIMESTAMP(0) WITH TIME ZONE, ALTER COLUMN created_at SET DEFAULT CURRENT_TIMESTAMP, ALTER COLUMN created_at SET NOT NULL');
        DB::statement('ALTER TABLE users ALTER COLUMN updated_at TYPE TIMESTAMP(0) WITH TIME ZONE, ALTER COLUMN updated_at SET DEFAULT CURRENT_TIMESTAMP, ALTER COLUMN updated_at SET NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users ALTER COLUMN email_verified_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        DB::statement('ALTER TABLE users ALTER COLUMN created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE, ALTER COLUMN created_at DROP DEFAULT, ALTER COLUMN created_at DROP NOT NULL');
        DB::statement('ALTER TABLE users ALTER COLUMN updated_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE, ALTER COLUMN updated_at DROP DEFAULT, ALTER COLUMN updated_at DROP NOT NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('fk_users_role');
            $table->dropIndex('idx_users_role');
            $table->dropColumn(['role_id', 'phone', 'avatar_key', 'is_active', 'last_login_at', 'deleted_at']);
        });
    }
};