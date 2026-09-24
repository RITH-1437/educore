<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_request_id');
            $table->string('file_key', 255);
            $table->string('file_name', 255);
            $table->string('mime_type', 100)->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->string('verification_token', 64);
            $table->foreignId('generated_by')->nullable();
            $table->timestampTz('generated_at')->useCurrent();
            $table->string('status', 20)->default('valid');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->softDeletesTz();

            $table->unique('document_request_id', 'uq_documents_request');
            $table->unique('verification_token', 'uq_documents_verification_token');

            $table->foreign('document_request_id', 'fk_documents_request')
                ->references('id')->on('document_requests')->restrictOnDelete();
            $table->foreign('generated_by', 'fk_documents_generator')
                ->references('id')->on('users')->nullOnDelete();
        });

        DB::statement('ALTER TABLE documents ADD CONSTRAINT ck_documents_status CHECK (status IN (\'valid\', \'revoked\', \'expired\'))');
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};