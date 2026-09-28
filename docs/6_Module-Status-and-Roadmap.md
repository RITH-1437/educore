# EduCore — Module Status & Build Roadmap Report

- **Date:** 2026-09-26
- **Source of truth:** `docs/3_business-overview.md` §9 (module list), `docs/5_Build-Steps-Report.md` (build workflow)
- **Status labels:** `[Implemented]` = shipped and tested · `[Planned]` = next, follows this playbook · `[Future]` = later
- **Database:** the full 49-table schema already exists as migrations — **every** module below builds on top of already-migrated tables (see `docs/4_Database-Migration-Report.md`).

---

## 1. Status at a glance — which modules are implemented vs planned

| # | Module (business-overview §9) | Status |
| --- | --- | --- |
| 9.1 | Authentication & Authorization | `[Implemented]` |
| — | Users management (extra, part of 9.1 scope) | `[Implemented]` |
| — | Database schema (49 domain tables) | `[Implemented]` |
| — | Login UI (branding, ITC background/logo) | `[In Progress]` |
| 9.2 | Student Management | `[Planned]` |
| 9.3 | Lecturer Management | `[Planned]` |
| 9.4 | Faculty & Department Management | `[Planned]` |
| 9.5 | Program Management | `[Planned]` |
| 9.6 | Academic Year & Semester Management | `[Implemented]` |
| 9.7 | Course Management | `[Planned]` |
| 9.8 | Class / Section Management | `[Planned]` |
| 9.9 | Course Registration / Enrollment | `[Planned]` |
| 9.10 | Timetable Management | `[Planned]` |
| 9.11 | Attendance | `[Planned]` |
| 9.12 | Assignments | `[Planned]` |
| 9.13 | Examinations | `[Planned]` |
| 9.14 | Grades & GPA | `[Planned]` |
| 9.15 | Student Academic Dashboard | `[Planned]` |
| 9.16 | Document Management | `[Planned]` |
| 9.17 | Digital Document Verification | `[Planned]` |
| 9.18 | Invoices & Payment Records | `[Planned]` |
| 9.19 | Announcements | `[Planned]` |
| 9.20 | Email Notifications | `[Planned]` |
| 9.21 | Telegram Notifications | `[Planned]` |
| 9.22 | Internship Management | `[Planned]` |
| 9.23 | Analytics & Reporting | `[Future]` |
| 9.24 | Audit Logs & Security | `[Future]` |

> No module is documented as implemented unless it is genuinely tested and running
> (the documentation skill forbids overclaiming).

```mermaid
pie showData
  title Module delivery status
  "Implemented (incl. schema)" : 26
  "Planned / In progress" : 23
```

---

## 2. The shared step-by-step recipe (use for EVERY module)

Read the matching `skills/<module>/SKILL.md` FIRST (mandatory — see `AGENTS.md`),
plus `database`, `api`, `authorization`, `frontend-ui`, `testing`. Then:

1. **Confirm schema** — the table(s) already exist as migrations. Do NOT re-create
   them; add a new migration only for a genuinely new column/table (verify against
   `docs/database/schema-tables.sql`).
2. **Model** — `app/Models/<Entity>.php`: fillable, casts, relationships, scopes
   (`belongsTo` parents, `hasMany` children, soft delete only where history
   matters). Follow the exact relationships in `skills/academic-domain`.
3. **Seeding** — add realistic data to the relevant seeder or a new
   `<Module>Seeder::class`; register it in `DatabaseSeeder`.
4. **Service** (only when logic is non-trivial) — plain PHP class in
   `app/Services/<Module>/`; wrap multi-step writes in `DB::transaction`.
5. **Controller + Form Request + Policy** — thin controllers; validation in
   Requests; authorization in `app/Policies/<Entity>Policy` (never trust the
   client; return `403`).
6. **API Resource + routes** — JSON via `api.users`-namespaced resources under
   `/api/<resource>` (REST conventions in `skills/api`); reuse the existing
   `UserResource` pattern. Name collision rule: give every module's API resource
   `['names' => 'api.<module>']`.
7. **Inertia pages** — `frontend/src/pages/<Module>/` (`Index`, `Create`, `Edit`)
   using `BaseInput`/`BaseButton`/`Pagination`; reuse the Users module screens as
   the template. Authorization-aware UI (hide what the role can't use).
8. **Tests** — `backend/tests/Feature/<Module>/`: happy path + `403` denied per
   role + `422` validation + any domain rules (see the module-specific list in
   §4). Run them.
9. **Verify & ship** — `php artisan test` green, Pint clean, `npm run build`
   clean; update `docs/3_business-overview.md` label `[Planned] → [Implemented]`
   and this report; commit `[Feature]: <module>` per `skills/git-commit-style`.

```mermaid
flowchart LR
  A[skills first] --> B[model + seed]
  B --> C[authz: policy]
  C --> D[api resource + routes]
  D --> E[inertia pages]
  E --> F[tests happy+denied]
  F --> G[labels + commit]
```

---

## 3. Verification checklist (same for every module)

1. `docker compose --project-directory . -f docker/docker-compose.yml exec backend php artisan test` → green.
2. `... exec backend vendor/bin/pint --test` → clean.
3. `docker exec educore-frontend sh -lc 'cd /app && npm run build'` → clean.
4. Role checks: Super Admin allowed, Student → `403` on both web and `/api`.
5. Lists are paginated with standard `meta`; errors use the standard JSON shape.
6. Real `educore` DB untouched (tests isolate to `educore_test`).

---

## 4. Step-by-step per-module playbook (build order)

Executed in the order of `docs/3_business-overview.md` §28 roadmap. Each line
lists the tables (already migrated), the model(s), the key steps and tests.

### Phase A — University structure & people

**9.6 Academic Year & Semester** `[Implemented]` — tables `academic_years`,
`semesters`. Delivered: models with column-default parity, enums with
forward-only transitions (`planned → active → completed` and
`planned → open → closed → completed`), `AcademicYearService` /
`SemesterService` owning the business rules (one current year, current must be
active, completing the current year clears the flag, semester dates inside the
parent year, delete guards), `BusinessRuleException` mapped to `409`,
super-admin and university-admin policies, web pages `AcademicYears/Index`,
`AcademicYears/Create`, `AcademicYears/Edit`, resource-transformed `/api/academic-years`
(nested `/semesters`) with OpenAPI annotations, `AcademicYearSeeder`, and 39
feature tests.

**9.4 Faculty & Department** — tables `universities`, `faculties`, `departments`.
Steps: nested CRUD (departments belong to a faculty), restrict deletes while
children exist, `Father`-style soft hierarchy, pages `Faculties/`, tests deny for
non-university-admins.

**9.5 Program** — table `programs`.
Steps: CRUD scoped to a department, unique `(department_id, code)`, page
`Programs/`, tests.

**9.2 Student Management** — tables `students`, `student_programs`, `users`.
Steps: create student (creates linked `users` row with a Student ID + temporary
password, hashed), enroll student into programs, list with filters
(`filters[program_id]`, `filters[status]`), soft delete; pages `Students/`;
tests: create duplicates rejected, student sees only own profile, others `403`.

**9.3 Lecturer Management** — tables `lecturers`, `users`.
Steps: same mini-vertical as students (linked user, `role:lecturer`), assignment
to departments; pages `Lecturers/`; tests: department-scoped visibility.

### Phase B — Academic core

**9.7 Course Management** — tables `courses`, `course_programs`,
`course_prerequisites`, `course_offerings`.
Steps: course CRUD per program; prerequisite self-reference; offering per
academic year/semester; pages `Courses/`; tests: prerequisite rule enforced on
enrollment-side checks.

**9.8 Class / Section Management** — tables `sections`, `rooms`,
`section_lecturers`, `course_offerings`.
Steps: sections under an offering, room assignment, multiple lecturers (pivot);
pages `Sections/`; tests: allocation rule (lecturer-FK restrict).

**9.9 Course Registration / Enrollment** — tables `enrollments`,
`student_programs`.
Steps: **transactional** service `RegistrationService` (enrollment + seat checks
+ duplicate rejection via the unique constraint), capacity/seat decrement
according to `skills/enrollment`; pages `Enrollments/`; tests: prerequisites,
capacity, duplicates, atomicity.

**9.10 Timetable** — table `schedule_entries`.
Steps: slot CRUD validating the unique clock/room constraint
`(scheduleable_type, scheduleable_id, day_of_week, start_time, room_id)`;
pages `Timetables/`; tests: conflict rejection `409`.

**9.11 Attendance** — tables `attendance_sessions`, `attendance_records`.
Steps: lecturer creates a session for a section, marks records, percentage
calc in `AttendanceService`; pages `Attendance/`; tests: recording + percentage
calculation.

### Phase C — Assessment & student visibility

**9.12 Assignments** — tables `assignments`, `assignment_submissions`.
Steps: assignment CRUD + submission upload (MinIO via `skills/file-storage`),
late/deadline validation; tests: submission authorization.

**9.13 Examinations** — tables `exams`, `exam_results`.
Steps: exam plan + results entry, ordering of results; tests: results only by
lecturer/student owner.

**9.14 Grades & GPA** — tables `grading_scales`, `course_grading_configs`,
`grades`, `gpa_records`.
Steps: `GradingService` mapping % → letter/point, GPA computed from
`gpa_records` (never stored live — see `skills/grading-gpa`); tests: weighted
GPA correctness + recompute on grade change.

**9.15 Student Academic Dashboard** — read-only pages aggregating enrollments,
attendance %, GPA, timetable (`withCount`/`withSum`, eager load, no N+1).

### Phase D — Administration & communication

**9.16 / 9.17 Document Management & Verification** — tables `document_types`,
`document_requests`, `documents`, `document_verifications`, `files`.
Steps: request → approval → generation → QR verification flow
(`skills/documents`); tests: download authorization, verification lookup.

**9.18 Invoices & Payments** — tables `invoices`, `invoice_items`, `payments`.
Steps: append-only invoice/reversal model; totals from line items; tests:
status transitions + totals.

**9.19 Announcements** — table `announcements`.
Steps: CRUD visible by audience scope; pages `Announcements/`.

**9.20 / 9.21 Email & Telegram Notifications** — tables `notifications`,
`notification_preferences`.
Steps: Laravel Notifications, queue on Redis, preference checks before send;
tests: queued notification dispatched with correct payload.

### Phase E — Internship & hardening (`[Future]`)

**9.22 Internship** — tables `internship_companies`, `internships`,
`internship_reports`, `internship_evaluations`.
**9.23 Analytics & Reporting** — aggregation/read models (never iterate in PHP).
**9.24 Audit Logs & Security** — table `audit_logs`; log sensitive access;
hardening pass (locking, Superset-free reporting, deployment).

---

## 5. Every-module output (definition of done)

- Model + relationships per `skills/academic-domain`; schema not duplicated.
- Thin controller + Form Request + Policy (server-side `403`).
- `/api/<resource>` CRUD with `auth:sanctum` + namespaced route names.
- Inertia `Index/Create/Edit` pages reusing the Users vertical.
- Feature tests asserted happy + denied + invalid; suite stays green.
- `docs/3_business-overview.md` label flipped to `[Implemented]`; this report
  updated; single `[Feature]: ...` commit per module.

---

## 6. Open items

- Login page UI (glass card, ITC background/logo) is `[In Progress]` — still
  uncommitted together with the rest of the pre-existing WIP.
- `[Done]` Pre-existing Pint failures on the 49 migration files — fixed with a
  single trailing-newline pass; `vendor/bin/pint` is now clean (148 files).
- `[Done]` Docker build contexts resolved from the wrong directory
  (`./php`, `./frontend` → `./docker/php`, `./docker/frontend`). The stack must
  always be started from the repository root, e.g.
  `docker compose --project-directory . -f docker/docker-compose.yml up -d`;
  running it from inside `docker/` silently bind-mounts a stray `docker/backend`.
- `[Done]` Brand/design system in `docs/branding/` is now reachable by agents
  through `skills/branding/SKILL.md`, which is wired into `AGENTS.md`.
- `[Pending]` Implement the `DESIGN-TOKENS.md` §15 `@theme` block in
  `frontend/src/style.css`; until then the stock `blue`/`slate`/`emerald`/
  `amber`/`red` utilities are the token implementation.
- `[Pending]` OpenAPI schema classes referenced by the Academic Year/Semester
  annotations are not defined yet under `backend/app/OpenApi/`.
- `[Pending]` Generated Swagger assets (`backend/public/build`, and if kept
  `backend/storage/api-docs/api-docs.json`) need an explicit gitignore decision.