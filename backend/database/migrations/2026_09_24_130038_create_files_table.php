<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('fileable', 'idx_files_fileable');
            $table->foreignId('uploader_id')->nullable();
            $table->string('file_name', 255);
            $table->string('original_name', 255);
            $table->string('storage_key', 255);
            $table->string('bucket', 100)->default('educore');
            $table->string('mime_type', 100)->nullable();
            $table->bigInteger('size');
            $table->string('visibility', 20)->default('private');
            $table->string('checksum', 64)->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('uploader_id', 'idx_files_uploader');

            $table->foreign('uploader_id', 'fk_files_uploader')
                ->references('id')->on('users')->nullOnDelete();
        });

        DB::statement('ALTER TABLE files ADD CONSTRAINT ck_files_visibility CHECK (visibility IN (\'private\', \'public\'))');
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};