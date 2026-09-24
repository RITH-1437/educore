<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->string('module', 100)->nullable();
            $table->text('description')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique('name', 'uq_permissions_name');
            $table->unique('slug', 'uq_permissions_slug');
            $table->index('module', 'idx_permissions_module');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};