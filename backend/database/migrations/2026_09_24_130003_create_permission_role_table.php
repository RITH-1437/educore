<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id');
            $table->foreignId('permission_id');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['role_id', 'permission_id'], 'uq_permission_role');
            $table->index('permission_id', 'idx_permission_role_permission');

            $table->foreign('role_id', 'fk_permission_role_role')
                ->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('permission_id', 'fk_permission_role_permission')
                ->references('id')->on('permissions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_role');
    }
};