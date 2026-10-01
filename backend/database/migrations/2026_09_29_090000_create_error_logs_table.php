<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * System error log: one row per HTTP 404 and 5xx response.
 *
 * Deliberately distinct from `audit_logs` (`skills/audit-logging/SKILL.md`):
 *
 * - `audit_logs` records who *deliberately* changed business data, with
 *   before/after snapshots and an actor.
 * - `error_logs` records requests that *failed* — a missing route, a broken
 *   page, an unhandled exception. There is often no actor, and there is no
 *   before/after because nothing was changed.
 *
 * Only 404 and 5xx are recorded. Client mistakes that the application already
 * handles (401, 403, 409, 422) are normal control flow and must not pollute
 * this table — see `App\Services\ErrorLogRecorder`.
 *
 * Append-only: no `updated_at`, and no update/delete endpoints exist.
 *
 * `url` stores the request **path only**. Query strings are dropped because
 * they routinely carry tokens and other secrets, and `skills/audit-logging`
 * §4 forbids storing them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->smallInteger('status_code');
            $table->string('method', 10);
            $table->text('url');
            $table->string('route_name')->nullable();
            $table->string('exception_class')->nullable();
            $table->text('message')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->jsonb('context')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            // The list screen defaults to newest-first and always narrows by
            // status code, so `created_at` is the primary access path.
            $table->index('created_at', 'idx_error_logs_created');
            $table->index('status_code', 'idx_error_logs_status_code');

            $table->foreign('user_id', 'fk_error_logs_user')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
