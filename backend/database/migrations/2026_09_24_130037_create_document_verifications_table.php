<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id');
            $table->string('verification_token', 64);
            $table->string('result', 20);
            $table->timestampTz('verified_at')->useCurrent();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index('document_id', 'idx_document_verifications_document');
            $table->index('verification_token', 'idx_document_verifications_token');
            $table->index('verified_at', 'idx_document_verifications_verified');

            $table->foreign('document_id', 'fk_document_verifications_document')
                ->references('id')->on('documents')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE document_verifications ADD CONSTRAINT ck_document_verifications_result CHECK (result IN (\'valid\', \'invalid\', \'revoked\', \'expired\'))');
    }

    public function down(): void
    {
        Schema::dropIfExists('document_verifications');
    }
};