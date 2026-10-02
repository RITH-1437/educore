<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Module 9.24: the audit trail is append-only at the database level, not only
 * in application code (`skills/audit-logging` §4, §12).
 *
 * - DELETE is always refused.
 * - UPDATE is refused, except the one change the schema itself makes:
 *   `fk_audit_logs_actor` is ON DELETE SET NULL, so removing a user may null
 *   `actor_id` while every other column stays untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION audit_logs_append_only() RETURNS trigger AS $$
            BEGIN
                IF TG_OP = 'UPDATE'
                   AND NEW.actor_id IS NULL AND OLD.actor_id IS NOT NULL
                   AND (to_jsonb(NEW) - 'actor_id') = (to_jsonb(OLD) - 'actor_id') THEN
                    RETURN NEW;
                END IF;
                RAISE EXCEPTION 'audit_logs is append-only (% refused)', TG_OP
                    USING ERRCODE = 'insufficient_privilege';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER trg_audit_logs_append_only
                BEFORE UPDATE OR DELETE ON audit_logs
                FOR EACH ROW EXECUTE FUNCTION audit_logs_append_only();
            SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_audit_logs_append_only ON audit_logs; DROP FUNCTION IF EXISTS audit_logs_append_only();');
    }
};
