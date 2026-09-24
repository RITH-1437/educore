<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('student_number', 50);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('gender', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact_name', 150)->nullable();
            $table->string('emergency_contact_phone', 50)->nullable();
            $table->string('national_id', 50)->nullable();
            $table->date('enrollment_date')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique('user_id', 'uq_students_user');
            $table->unique('student_number', 'uq_students_student_number');
            $table->unique('national_id', 'uq_students_national_id');
            $table->index('status', 'idx_students_status');

            $table->foreign('user_id', 'fk_students_user')
                ->references('id')->on('users')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE students ADD CONSTRAINT ck_students_gender CHECK (gender IN (\'male\', \'female\', \'other\'))');
        DB::statement('ALTER TABLE students ADD CONSTRAINT ck_students_status CHECK (status IN (\'active\', \'inactive\', \'suspended\', \'graduated\', \'withdrawn\'))');
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};