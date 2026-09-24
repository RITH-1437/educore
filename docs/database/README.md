# EduCore Database Architecture

PostgreSQL 16 design for the EduCore University Digital Administration Platform
(Laravel 12 modular monolith, Vue 3 frontend, Docker).

This folder is the **single source of truth** for the data model. It contains the
architecture, ERDs, DDL, and decision records. Nothing here has been applied to a
running database yet — see
[`migration-strategy.md`](./migration-strategy.md) and
[`decisions.md`](./decisions.md).

> **Mandatory rule (see `AGENTS.md`):** before any schema change, read
> `skills/database/SKILL.md` and `skills/academic-domain/SKILL.md`, then follow
> the conventions in this folder. Never alter tables by hand — schema changes go
> through Laravel migrations only.

---

## Contents

| File | What it holds |
|---|---|
| [`README.md`](./README.md) | Index, overview, scope, status labels |
| [`database-conventions.md`](./database-conventions.md) | Naming, PK/FK, types, statuses, soft deletes, timestamps |
| [`table-catalog.md`](./table-catalog.md) | One row per table: purpose, module, entity, relationships |
| [`module-map.md`](./module-map.md) | Tree of tables grouped by business module |
| [`relationships.md`](./relationships.md) | Every important relationship (From → To) |
| [`data-dictionary.md`](./data-dictionary.md) | Business meaning of key fields (IDs, statuses, dates, grades, GPA, tokens) |
| [`schema-reference.md`](./schema-reference.md) | Per-table columns, constraints, indexes, business rules |
| [`schema-tables.sql`](./schema-tables.sql) | Final PostgreSQL DDL (documentation form) |
| [`erd.md`](./erd.md) | High-level + full ERD, links to per-domain ERDs |
| [`erd/`](./erd/) | 11 per-domain Mermaid ERDs |
| [`migration-strategy.md`](./migration-strategy.md) | Dependency-safe Laravel migration order |
| [`seed-strategy.md`](./seed-strategy.md) | Safe dev seed data (roles, structure, sample people) |
| [`decisions.md`](./decisions.md) | Decision records (PK strategy, statuses, GPA, audit, …) |

---

## Design targets

- **Database:** PostgreSQL 16 (see `skills/database/SKILL.md`).
- **Normalization:** 3NF as the baseline; JSON only for genuinely
  semi-structured data (audit before/after snapshots, notification payload).
- **Primary keys:** `bigint` auto-increment `id` (consistent throughout; see
  [`decisions.md`](./decisions.md) §1).
- **Statuses:** `varchar` + `CHECK` constraints (never PostgreSQL `enum`).
- **History:** soft delete only where the skill allows; immutable/append-only
  tables for audit, attendance, and payments.
- **Files:** MinIO metadata/references in the DB — never large binaries.

## Scale (design estimate, not a target)

| Category | Count |
|---|---|
| **Business (domain) tables** | **49** |
| Laravel/Sanctum framework tables (already migrated) | 8 |
| **Total physical tables** | **57** |

The count is the natural result of the normalized design, not a goal.

## Modules / domains (14)

1. Identity & Access · 2. University Structure · 3. Academic Management ·
4. People · 5. Enrollment · 6. Attendance · 7. Assessment ·
8. Examination & Grading · 9. Documents & File Storage · 10. Finance ·
11. Communication · 12. Internship · 13. Reporting (derived) ·
14. Audit & Security

See [`module-map.md`](./module-map.md).

## Status labels

Every table and feature in this design is labelled:

- **[Designed]** — modeled here, ready to implement as migrations.
- **[Planned]** — intentionally designed but built later in the roadmap.
- **[Future]** — outside the initial release.
- **[Out of Scope]** — deliberately excluded.

Nothing in this folder is claimed as **[Implemented]** unless the corresponding
Laravel migration exists (currently none of the domain tables).

## Quick start for developers

1. Read [`decisions.md`](./decisions.md) first (PK, statuses, soft delete, GPA).
2. Read [`database-conventions.md`](./database-conventions.md).
3. Consult `skills/academic-domain/SKILL.md` for the canonical hierarchy.
4. Use [`schema-reference.md`](./schema-reference.md) as the column reference.
5. Implement with Laravel migrations in the order in
   [`migration-strategy.md`](./migration-strategy.md).
6. Verify with `php artisan migrate:fresh` (dev) — see `skills/database/SKILL.md`.

## Related skills

`skills/database`, `skills/academic-domain`, `skills/laravel`,
`skills/enrollment`, `skills/attendance`, `skills/examinations`,
`skills/grading-gpa`, `skills/timetable`, `skills/documents`,
`skills/invoices-payments`, `skills/notifications`, `skills/internship`,
`skills/audit-logging`, `skills/analytics-reporting`, `skills/documentation`.
