---
name: educore-database
description: PostgreSQL conventions for EduCore - migrations, indexes, constraints, naming, normalization, soft deletes, transactions, query optimization. Consult before any schema change.
---

# EduCore PostgreSQL Conventions

Database: **PostgreSQL 16**, run inside Docker Compose (`educore-postgres`,
port `5433` on host, `5432` internal). Schema changes come only through Laravel
migrations. Agents never modify the database by hand.

## When to use

- Creating or modifying tables, columns, indexes, constraints, or seeders.
- Writing queries or Eloquent relationships that touch the schema.

## Golden rules

- **Every table must represent a real business concept.** Never add tables just
  to increase table count.
- **Prefer normalized relational design.** Only denormalize for
  performance-sensitive read models when clearly justified and documented.
- Think in the academic domain: see `skills/academic-domain/SKILL.md` before
  touching academic entities and relationships.

## Approved doc/vs-migration equivalences (do NOT "fix")

Verified 2026-09-25 against `docs/database/schema-tables.sql` (49 tables,
70 FK, 57 CHECK, 54 plain + 3 partial-unique indexes match exactly):

- **PKs are `bigserial`** (`$table->id()`) in migrations vs `IDENTITY` in the
  SQL doc — approved, auto-increment in both.
- **Timestamps are `timestamp(0)` + `CURRENT_TIMESTAMP`** (Laravel default) vs
  doc's `TIMESTAMPTZ` precision 6 + `now()` — approved; app never writes
  microseconds.
- **`uq_gpa_records`** (`UNIQUE NULLS NOT DISTINCT` on student/year/semester)
  is a table constraint in the doc but a unique INDEX in the migration —
  identical enforcement.
- **`users.email`** unique is `users_email_unique` (scaffold) in migrations vs
  `uq_users_email` in the doc.
- Numeric/smallint defaults differ only in catalog notation.

If any new migration does NOT match the doc's field surface, or a *new* table
diverges in types/nullability/defaults, fix it — but leave the equivalence
list above alone.

## Migrations

- One migration per logical change; run `php artisan make:migration`.
- Table/column changes are additive (new nullable column, new table) unless the
  data is unrecoverable garbage.
- Always define FKs for relational columns.
- Down migrations should reverse the up (or at least drop added tables/columns).
- Use `->change()` / `doctrine` only when necessary; prefer add-with-backfill.

## Naming conventions

| Item | Convention | Example |
| --- | --- | --- |
| Tables | snake_case plural | `students`, `course_enrollments` |
| Columns | snake_case | `first_name`, `academic_year_id` |
| FK column | `<table_singular>_id` | `program_id`, `section_id` |
| PK | `id` (bigint autoincrement) unless business key is required | `id` |
| Pivot tables | alphabetical | `course_prerequisite` → `course_prerequisites` |
| Indexes | `<table>_<column>_index` (Laravel auto) | `course_enrollments_student_id_index` |
| Unique constraints | semantic | `unique(['academic_year_id','name'])` |
| Boolean columns | `is_*` / `has_*` | `is_active`, `has_graduated` |
| Timestamps | `created_at`, `updated_at` | default |
| Soft delete | `deleted_at` | default |

## Foreign keys & indexes

- Always create FK constraints with `->constrained()->cascadeOnDelete()` or
  `->restrictOnDelete()` chosen deliberately.
  - **Restrict** for reference data (don't silently delete a faculty that has
    departments).
  - **Cascade** only where child rows are meaningless without parent (line items).
- Index every FK column involved in lookups and any column used in `WHERE`,
  `ORDER BY`, or `JOIN` frequently.
- Composite indexes for common multi-column filters (e.g.
  `[academic_year_id, semester_id]`).

## Unique constraints

- Uniqueness that the business guarantees goes in the DB:
  - student ID (unique), email (unique), username (unique)
  - `(academic_year_id, semester_id, section_id, student_id)` for enrollment
  - timetable slots: `(scheduleable_type, scheduleable_id, day_of_week, start_time,
    room_id)` — see `skills/timetable/SKILL.md`.
  - grade uniqueness: `(section_id, student_id)` — see `skills/grading-gpa/SKILL.md`.

## Nullable fields

- Only nullable when the value genuinely may be absent.
- Student/lecturer profile fields often nullable at the start (filled later).
- A field that will always have a value on records the business creates should
  be NOT NULL with a default or set in code.

## Timestamps & soft deletes

- All tables get `timestamps()`.
- Soft deletes (`deleted_at`) ONLY where history matters:
  - enrollments (keep history), grades, documents, invoices, attendance?? not
    needed — attendance is immutable history already.
  - Never soft-delete pure lookup/reference data (roles, room lists, file types).
- Consider an `audit` trail instead of soft delete for high-value records — see
  `skills/audit-logging/SKILL.md`.

## Normalization & relationships

- Multi-valued attributes become related tables (e.g. a student's many
  enrollments, a course's many sections → `sections` table).
- Use pivot/polymorphic tables where the business model is many-to-many.
- Don't stuff comma-separated lists in columns.
- Relationship shapes and allowed hierarchies live in
  `skills/academic-domain/SKILL.md` — follow them exactly; do NOT invent
  relationships.

## Transactional integrity

- All multi-step writes are wrapped in `DB::transaction(...)` (see
  `skills/laravel/SKILL.md`).
- Enrollment flow (enrollment + seat decrement + optional assignment) must be
  atomic.
- Never rely on two separate queries without a transaction to look atomic.

## Query optimization

- Use Eloquent with eager loading; avoid N+1 (see `skills/laravel/SKILL.md`).
- Use `withCount`/`withSum` for aggregates instead of loading rows.
- Add indexes after analyzing the common query paths; do not index everything.
- For complex aggregate reports use optimized queries, grouped select, or
  precomputed summary tables rather than iterating in PHP (see
  `skills/analytics-reporting/SKILL.md`).
- Never `SELECT *` unbounded in the API; use pagination (see `skills/api`).

## Seeders & factories

- Use Laravel factories for tests and dev seeders.
- Seeders produce a realistic university structure: faculty → department →
  program → course → semester, a few sections, students, lecturers, and
  enrollments.
- Credentials in seeded admin/lecturer/student accounts: dev-only, documented;
  never a production secret.

## Prohibitions

- DO NOT create lookup tables with zero business meaning.
- DO NOT invent FKs/relationships not in the academic model.
- DO NOT drop or rename columns casually on tables with data.
- DO NOT disable constraints or foreign keys in app code.
- DO NOT make everything `nullable` or `unique` reflexively.
- DO NOT add an index for every column.

## Validation checklist

1. New table maps to a real business concept; relationship mirrors
   `academic-domain`.
2. FK constraints chosen deliberately (restrict vs cascade).
3. Unique constraints added for business-guaranteed uniqueness.
4. Indexes cover common query paths.
5. Multi-step writes are transactional.
6. Migration tested via `php artisan migrate:fresh` in dev.
7. Related skills respected: `academic-domain`, `laravel`, `api`.

## Agent behavior (mandatory everywhere)

1. Inspect the existing implementation before modifying it.
2. Follow existing project conventions already established.
3. Do not rewrite working code unnecessarily.
4. Do not introduce technologies outside the EduCore stack.
5. Do not create unnecessary abstractions.
6. Do not create duplicate business logic.
7. Do not invent database relationships.
8. Do not bypass authorization.
9. Do not hardcode secrets.
10. Do not modify unrelated modules.
11. Run appropriate tests after changes.
12. Explain important architectural decisions.