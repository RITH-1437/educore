<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('planned');
            $table->boolean('is_current')->default(false);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique('code', 'uq_academic_years_code');
        });

        DB::statement('ALTER TABLE academic_years ADD CONSTRAINT ck_academic_years_status CHECK (status IN (\'planned\', \'active\', \'completed\'))');
        DB::statement('ALTER TABLE academic_years ADD CONSTRAINT ck_academic_years_range CHECK (start_date < end_date)');
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};