<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lecturers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('staff_number', 50);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('title', 50)->nullable();
            $table->foreignId('department_id');
            $table->string('position', 100)->nullable();
            $table->string('specialization', 255)->nullable();
            $table->string('employment_type', 20)->default('full_time');
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique('user_id', 'uq_lecturers_user');
            $table->unique('staff_number', 'uq_lecturers_staff_number');
            $table->index('department_id', 'idx_lecturers_department');

            $table->foreign('user_id', 'fk_lecturers_user')
                ->references('id')->on('users')->restrictOnDelete();
            $table->foreign('department_id', 'fk_lecturers_department')
                ->references('id')->on('departments')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE lecturers ADD CONSTRAINT ck_lecturers_employment CHECK (employment_type IN (\'full_time\', \'part_time\', \'contract\', \'visiting\'))');
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturers');
    }
};