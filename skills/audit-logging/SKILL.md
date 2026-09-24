---
name: educore-audit-logging
description: EduCore audit logging - sensitive action tracking, who/what/when, immutable records, admin visibility. Consult for any audit or sensitive-change work.
---

# EduCore — Audit Logging

## 1. Purpose

Record sensitive/administrative actions: who did what, to what record, when,
and from what state — especially for grades, documents, finances, approvals,
and authorization changes.

## 2. Main entities

- `audit_log` — actor, action, targetable (polymorphic), before/after
  (JSON), ip/user_agent optional, timestamp.
- Depending on the existing implementation, this may also be the Laravel
  activity-log style table — match what is already there.

## 3. Relationships

- Audit Log → actor (user) nullable; → targetable (polymorphic to any model);
  no strict FK needed but keep rows queryable.

## 4. Business rules

- **Never log secrets or passwords**: log "password changed", not the value.
- Log events that matter for accountability:
  - Login failures/blockouts (see `authentication`).
  - Grade create/change/finalize (before → after).
  - Document request transitions + generation + download.
  - Invoice/payment create/change/reverse.
  - Enrollment create/drop/withdraw.
  - Role/permission changes, student status changes.
  - Announcement publish.
- Audit rows are **append-only** (no update/delete in application code; admins
  via Super Admin see them).
- Recording must not break the primary transaction (log after save, or in the
  same transaction via a listener/model event — match existing pattern).

## 5. API responsibilities

- `GET /api/audit-logs` (Super Admin; filter by model/actor/date, paginated).
- `GET /api/audit-logs/{log}` detail.
- No create/update/delete endpoints (rows are system-generated).

## 6. Backend responsibilities

- Central audit service/helper (`AuditLogger::record($action, $model, $before,
  $after)`) with model events or explicit calls in services — pick one pattern
  and be consistent.
- Serialize before/after as JSON snapshots of relevant attributes only
  (excluding hidden/secrets).
- Scope queries; never expose other users' contexts unnecessarily.

## 7. Frontend responsibilities

- Super Admin audit viewer: filters (actor, action, model, date range),
  table + expandable before/after diff.

## 8. Authorization rules

- Super Admin reads all audit logs. Consider Univ Admin for finance/grade
  audit scoped views if needed — otherwise keep to Super Admin by default.

## 9. Validation rules

- Actions/targets validated; snapshots sanitized (no passwords/tokens).

## 10. Important edge cases

- Entity soft-deleted — keep the audit row (never cascade audit).
- Huge snapshots — store diff-relevant attributes only.
- Actor later deleted — audit row keeps actor_id nullable + display fallback.

## 11. Testing requirements

- Events produce rows with correct before/after; secrets never logged;
  append-only enforced (no update/delete endpoints); Super Admin visibility
  only.

## 12. Must NOT

- Must NOT log passwords, tokens, or full request bodies with secrets.
- Must NOT provide audit edit/delete endpoints.
- Must NOT let users clear their own audit trail.

## Cross-references

- `skills/security/SKILL.md`, `skills/authentication/SKILL.md`,
  `skills/grading-gpa/SKILL.md`, `skills/documents/SKILL.md`,
  `skills/invoices-payments/SKILL.md`.

## Agent behavior (mandatory)

1. Inspect existing implementation first. 2. Follow established conventions.
3. Do not rewrite working code. 4. No tech outside the stack. 5. No unnecessary
abstractions. 6. No duplicate business logic. 7. No invented relationships.
8. No bypassing authorization. 9. No hardcoded secrets. 10. No unrelated
module changes. 11. Run tests. 12. Explain architectural decisions.