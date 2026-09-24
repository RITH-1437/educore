<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('name', 100);
            $table->string('building', 100)->nullable();
            $table->string('floor', 20)->nullable();
            $table->smallInteger('capacity');
            $table->string('room_type', 20)->default('lecture');
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique('code', 'uq_rooms_code');
        });

        DB::statement('ALTER TABLE rooms ADD CONSTRAINT ck_rooms_capacity CHECK (capacity > 0)');
        DB::statement('ALTER TABLE rooms ADD CONSTRAINT ck_rooms_type CHECK (room_type IN (\'lecture\', \'lab\', \'seminar\', \'other\'))');
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};