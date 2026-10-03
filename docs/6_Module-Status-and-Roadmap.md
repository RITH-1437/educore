# EduCore — Module Status & Build Roadmap Report

- **Date:** 2026-09-26 (updated 2026-10-02)
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
| — | Branding / design system + admin dashboard | `[Implemented]` |
| — | API contract & OpenAPI audit | `[Implemented]` |
| — | System Error Logs (9.25, extra operational diagnostics) | `[Implemented]` |
| 9.2 | Student Management | `[Implemented]` |
| 9.3 | Lecturer Management | `[Implemented]` (section assignment `[Planned]` with 9.8) |
| 9.4 | Faculty & Department Management | `[Implemented]` |
| 9.5 | Program Management | `[Implemented]` (curriculum editor delivered with 9.7) |
| 9.6 | Academic Year & Semester Management | `[Implemented]` |
| 9.7 | Course Management | `[Implemented]` (offerings/sections `[Planned]` with 9.8) |
| 9.8 | Class / Section Management | `[Implemented]` |
| 9.9 | Course Registration / Enrollment | `[Implemented]` |
| 9.10 | Timetable Management | `[Implemented]` |
| 9.11 | Attendance | `[Implemented]` |
| 9.12 | Assignments | `[Implemented]` |
| 9.13 | Examinations | `[Implemented]` |
| 9.14 | Grades & GPA | `[Implemented]` (transcript by 9.16; finalize lock in report 30) |
| 9.15 | Student Academic Dashboard | `[Implemented]` |
| 9.16 | Document Management | `[Implemented]` (student certificate and internship letter in report 30) |
| 9.17 | Digital Document Verification | `[Implemented]` (QR image `[Planned]`) |
| 9.18 | Invoices & Payment Records | `[Implemented]` |
| 9.19 | Announcements | `[Implemented]` |
| 9.20 | Email Notifications | `[Implemented]` (password emails `[Future]`) |
| 9.21 | Telegram Notifications | `[Implemented]` (class reminders `[Future]`) |
| 9.22 | Internship Management | `[Implemented]` (opportunity postings, letter document `[Future]`) |
| 9.23 | Analytics & Reporting | `[Implemented]` (CSV exports in report 31; PDF exports, faculty-scoped views `[Future]`) |
| 9.24 | Audit Logs & Security | `[Implemented]` (CSV export in report 31; retention `[Future]`) |

> No module is documented as implemented unless it is genuinely tested and running
> (the documentation skill forbids overclaiming).

```mermaid
pie showData
  title Module delivery status
  "Implemented (incl. schema)" : 49
  "Planned / In progress" : 1
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

**9.5 Program Management** — table `programs`. Delivered: `ProgramService` owning
the rules (unique code, name unique per department via the new
`uq_programs_department_id_name`, archive via `is_active`, delete refused while
`student_programs` or `course_programs` reference the program — never the
schema's silent cascade), `ProgramPolicy` (Faculty Admin read-only), web pages
`Programs/Index|Edit`, `/api/programs` with OpenAPI annotations,
`ProgramSeeder`, 24 feature tests. Curriculum (program ↔ course) is deferred to
9.7. Report: `docs/10_Program-Management-Report.md`.

**9.4 Faculty & Department** — tables `universities`, `faculties`, `departments`.
Delivered: `UniversityService` / `FacultyService` / `DepartmentService` owning the
rules (single `is_current` university, unique faculty names, department names
unique per faculty, archive-vs-delete, delete guards that count soft-deleted
children), `BusinessRuleException` mapped to `409`, read access for super-admin,
university-admin and faculty-admin with writes restricted to super-admin and
university-admin, pages `Universities/Index`, `Universities/Edit`,
`Faculties/Index` (with inline department management), `Faculties/Edit`,
resource-transformed `/api/universities`, `/api/faculties`, `/api/departments`
plus a `/api/faculties-tree` read endpoint, OpenAPI annotations, an idempotent
`UniversityStructureSeeder` (1 university / 3 faculties / 6 departments) and 49
feature tests.

**9.5 Program** — table `programs`.
Steps: CRUD scoped to a department, unique `(department_id, code)`, page
`Programs/`, tests.

**9.2 Student Management** — tables `students`, `student_programs`, `users`.
Steps: create student (creates linked `users` row with a Student ID + temporary
password, hashed), enroll student into programs, list with filters
(`filters[program_id]`, `filters[status]`), soft delete; pages `Students/`;
tests: create duplicates rejected, student sees only own profile, others `403`.

**9.3 Lecturer Management** — tables `lecturers`, `users`. Delivered:
`LecturerService` (account created via `UserRepository` or an existing
Lecturer-role account linked, in one transaction; profile/account `is_active`
mirrored; delete keeps the account inactive and is refused while
`section_lecturers` rows exist), `LecturerPolicy` (lecturer reads own profile,
Faculty Admin read-only), `Lecturers/Index|Edit`, `/api/lecturers` with OpenAPI
annotations, `LecturerSeeder`, 23 feature tests. Faculty-scoped visibility
for Faculty Admin arrived with report 32. Report:
`docs/12_Lecturer-Management-Report.md`.

### Phase B — Academic core

**9.7 Course Management** — tables `courses`, `course_programs`,
`course_prerequisites` (`course_offerings` is guarded but not managed yet).
Delivered: `CourseService` (lifecycle, unique code, acyclic prerequisites via an
iterative graph walk, delete guards for curricula / dependents / offerings),
`ProgramService` curriculum methods (membership stays with the program),
`CoursePolicy` (Faculty Admin read-only), `Courses/Index|Edit`, the curriculum
editor on `Programs/Edit`, `/api/courses` and `/api/programs/{program}/courses`
with OpenAPI annotations, `CourseSeeder`, 32 feature tests. Offerings and
sections are deferred to 9.8 (they need lecturers, rooms, timetable). Report:
`docs/11_Course-Management-Report.md`.

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

**9.14 Grades & GPA** `[Implemented]` — tables `grading_scales`,
`course_grading_configs`, `grades`, `gpa_records` (no schema change).
Delivered: `GradingService` (active scale with derived gap-free bands, course
weights that must total 100, a grade sheet computed from attendance rate,
coursework and exam results with unmeasurable components rescaled, draft →
submitted → approved / returned workflow; approval completes enrollments),
`GpaService` (credit-weighted semester and per-year cumulative snapshots rebuilt
on approve / return / course credit change, latest attempt per course counts
cumulatively), grade-based prerequisites, `GradePolicy`, pages
`Grades/Section|Index|Scale|Mine` plus the course *Grading weights* card,
`/api/sections/{section}/grades…`, `/api/students/{student}/grades|gpa`,
`/api/grading-scale`, `/api/courses/{course}/grading-config` with OpenAPI
annotations, `GradingScaleSeeder`, 12 feature tests. Report:
`docs/20_Grades-and-GPA-Report.md`.

**9.15 Student Academic Dashboard** `[Implemented]` — no tables of its own.
Delivered: `StudentDashboardService` composing `GpaService`,
`AttendanceService`, `TimetableService`, `ExamService` and `GradingService`
(no duplicated calculations), `GET /api/students/{student}/dashboard`
(`StudentPolicy::view`), the `Student/Dashboard` page replacing the student's
placeholder at `/dashboard`, 5 feature tests. Report:
`docs/21_Student-Academic-Dashboard-Report.md`.

### Phase D — Administration & communication

**9.16 / 9.17 Document Management & Verification** `[Implemented]` — tables
`document_types`, `document_requests`, `documents`, `document_verifications`
(no schema change; `files` not needed). Delivered: `DocumentService`
(pending → approved → generated / rejected, one open request per type and
semester, PDFs via dompdf from approved grades / GPA / enrollments only,
private MinIO storage, revoke, public verification with lookup log),
`DocumentRequestPolicy`, pages `Documents/Mine|Index|Verify`, 10 API
operations incl. the public `/api/verifications/{token}`, `DocumentTypeSeeder`,
5 feature tests. New dependency `barryvdh/laravel-dompdf`. Report:
`docs/22_Documents-and-Verification-Report.md`.

**9.18 Invoices & Payments** `[Implemented]` — tables `invoices`,
`invoice_items`, `payments` (no schema change). Delivered: `InvoiceService`
(cent-exact totals from items, `INV-{year}-{seq}` numbering under an advisory
lock, edit before payment only, payments ≤ balance, append-only reversals,
cancel instead of delete, one status derivation + overdue refresh on read and
`invoices:refresh-statuses` daily), `InvoicePolicy` (managers; students read
own), pages `Invoices/Index|Form|Show|Mine`, 9 API operations,
`InvoiceSeeder`, 8 feature tests. Report:
`docs/23_Invoices-and-Payments-Report.md`.

**9.19 Announcements** `[Implemented]` — table `announcements` (no schema
change). Delivered: `AnnouncementService` (nine audience types resolved live
from memberships, draft → published → archived, lecturers limited to the
sections / courses they teach, batched audience labels), `AnnouncementPolicy`,
pages `Announcements/Feed|Manage` plus the student dashboard card, 9 API
operations, `AnnouncementSeeder`, 6 feature tests. Report:
`docs/24_Announcements-Report.md`.

**9.20 / 9.21 Email & Telegram Notifications** `[Implemented]` — table
`notification_preferences` (no schema change; `notifications` unused — its
bigint id does not fit Laravel's UUID database channel). Delivered:
`EduCoreNotification` base (queue `notifications`, after commit, 3 tries,
critical vs optional email, Telegram opt-in), `TelegramChannel` (Bot API via
the HTTP client), seven notifications wired into enrollment, grading,
documents, invoices and announcements (`SendAnnouncementNotifications`
fan-out via `AnnouncementService::recipients`), daily assignment reminders,
the settings page and 3 API operations, `queue` + `scheduler` containers,
8 feature tests. Report: `docs/25_Notifications-Report.md`.

### Phase E — Internship & hardening (`[Future]`)

**9.22 Internship** `[Implemented]` — tables `internship_companies`,
`internships`, `internship_reports`, `internship_evaluations` (no schema
change). Delivered: `InternshipService` (state machine with one open
application per student, append-only decision notes, reports with private
files, one evaluation per evaluator type, final report required to complete),
`InternshipPolicy`, `InternshipStatusChanged` notification, pages
`Internships/Mine|Index|Show|Companies`, 20 API operations,
`InternshipSeeder`, 6 feature tests. Report:
`docs/26_Internship-Management-Report.md`.
**9.23 Analytics & Reporting** `[Implemented]` — no tables of its own.
Delivered: `AnalyticsService` (grouped SQL, semester-bounded academic figures,
point-in-time workload, finance per currency, null-safe ratios, reusing the
grade / attendance / GPA definitions of 9.11 and 9.14), gate `view-analytics`
(managers), `Analytics/Index` with KPI tiles and single-hue bar charts
(`components/charts/BarChart.vue`, palette-validated, table toggle), 4 API
operations, 4 feature tests. Report: `docs/27_Analytics-and-Reporting-Report.md`.
**9.24 Audit Logs & Security** `[Implemented]` — table `audit_logs` (+ an
append-only trigger migration). Delivered: `AuditLogger` called inside the
services' transactions (sign-in events, users, students, lecturers,
enrollment, exam corrections, grades and grading rules, documents, finance,
announcements, internships) with changed-attributes-only snapshots and no
secrets; `AuditLog` model + PostgreSQL trigger refusing UPDATE / DELETE;
`EnsureAccountIsActive` revoking sessions / tokens of deactivated accounts;
Super Admin viewer `AuditLogs/Index|Show`, 2 API operations, 7 feature tests.
Report: `docs/28_Audit-Logs-and-Security-Report.md`.

---

## 5. Every-module output (definition of done)

Code:

- Model + relationships per `skills/academic-domain`; schema not duplicated.
- Thin controller + Form Request + Policy (server-side `403`).
- `/api/<resource>` CRUD with `auth:sanctum` + namespaced route names.
- Inertia `Index/Create/Edit` pages reusing the Users vertical.
- Feature tests asserted happy + denied + invalid; suite stays green.

Documentation (same commit as the code — see `AGENTS.md` and
`skills/documentation/SKILL.md`):

- New numbered report `docs/N_<Module>-Report.md`: scope, schema, models,
  endpoints, authorization matrix, UI, tests, decisions, Mermaid diagrams.
- This report: the §1 status label, the §4 playbook entry, and §6 open items
  the module closed.
- `docs/3_business-overview.md` §9 label flipped to `[Implemented]`.
- `docs/api/api-audit.md` rows for every new or changed endpoint.
- `README.md` when ports, env vars, commands, or routes change;
  `docs/database/` when the schema changes.

A module is not done until its report exists and its tests pass — never label
`[Implemented]` on intent alone. Ship one `[Feature]: ...` commit per module.

---

## 6. Open items

- `[Done]` Login page UI (branding, ITC background/logo) — shipped in `7a32f08`.
- `[Done]` Pre-existing Pint failures on the 49 migration files — fixed with a
  single trailing-newline pass; `vendor/bin/pint` is now clean (148 files).
- `[Done]` Docker build contexts resolved from the wrong directory
  (`./php`, `./frontend` → `./docker/php`, `./docker/frontend`). The stack must
  always be started from the repository root, e.g.
  `docker compose --project-directory . -f docker/docker-compose.yml up -d`;
  running it from inside `docker/` silently bind-mounts a stray `docker/backend`.
- `[Done]` Brand/design system in `docs/branding/` is now reachable by agents
  through `skills/branding/SKILL.md`, which is wired into `AGENTS.md`.
- `[Done]` `DESIGN-TOKENS.md` §15 `@theme` block implemented in
  `frontend/src/style.css` — semantic tokens, dark-mode variants and animations
  are live (commit `d359090`).
- `[Done]` OpenAPI schema classes for Academic Year/Semester/Users are defined
  under `backend/app/OpenApi/Schemas/` (commit `9737dc5`).
- `[Done]` Generated Swagger assets are gitignored:
  `backend/public/build` and `backend/storage/api-docs` (commit `e3405dc`).
- `[Done]` Branded admin dashboard, reusable component library and role-aware
  redirects shipped in `d359090`; API contract audit in `9737dc5`.
- `[Done]` 9.4 Faculty & Department Management (`universities`, `faculties`,
  `departments`) — unlocks 9.5 Programs and makes the dashboard faculty and
  program metrics real. Report: `docs/7_Faculty-and-Department-Report.md`.
- `[Done]` 9.25 System Error Logs (`error_logs`) — Super Admin-only, read-only
  capture of HTTP 404/5xx responses via `$exceptions->respond()`, distinct from
  9.24's planned audit trail. Report: `docs/8_System-Error-Logs-Report.md`.
- `[Done]` 9.5 Program Management (`programs`). Report:
  `docs/10_Program-Management-Report.md`.
- `[Done]` 9.7 Course Management (`courses`, `course_prerequisites`,
  `course_programs`). Report: `docs/11_Course-Management-Report.md`.
- `[Done]` 9.3 Lecturer Management (`lecturers`). Report:
  `docs/12_Lecturer-Management-Report.md`.
- `[Done]` Sign-in refuses inactive accounts (`users.is_active`) with the
  generic error, web and API. `[Done]` (9.24) Sessions / tokens issued before
  a deactivation are revoked on the next request.
- `[Done]` 9.2 Student Management (`students`, `student_programs`). Report:
  `docs/13_Student-Management-Report.md`.
- `[Done]` 9.8 Class / Section (`course_offerings`, `sections`,
  `section_lecturers`). Report: `docs/14_Class-and-Section-Report.md`.
- `[Done]` 9.9 Enrollment (`enrollments`). Report: `docs/15_Enrollment-Report.md`.
- `[Done]` 9.10 Timetable (`rooms`, `schedule_entries`; dropped the global
  `uq_schedule_room_slot`). Report: `docs/16_Timetable-Report.md`.
- `[Done]` 9.11 Attendance (`attendance_sessions`, `attendance_records`).
  Report: `docs/17_Attendance-Report.md`.
- `[Done]` 9.12 Assignments (`assignments`, `assignment_submissions`, `files`;
  private MinIO uploads). Report: `docs/18_Assignments-Report.md`.
- `[Done]` Test-database guard (`tests/TestCase`) and production-only config
  caching in the container entrypoint, after a cached config let the suite
  reset the development database.
- `[Done]` Assignment and exam scores are weighted into course grades (9.14).
  `[Open]` Lecturer-attached materials are not built.
- `[Done]` 9.13 Examinations (`exams`, `exam_results`). Report:
  `docs/19_Examinations-Report.md`.
- `[Done]` Exam result corrections are audited (9.24).
- `[Done]` 9.14 Grades & GPA (`grading_scales`, `course_grading_configs`,
  `grades`, `gpa_records`); prerequisites now require an approved passing grade
  when a grade exists. Report: `docs/20_Grades-and-GPA-Report.md`.
- `[Done]` API re-audit 2026-10-02: six PATCH aliases documented; R-01 marked
  resolved (`docs/api/api-audit.md`).
- `[Done]` Grade submissions, approvals and returns are audited (9.24).
  `[Done]` The `finalized` grade lock step (finalize; Super Admin reopen with reason). `[Done]` The transcript document ships with 9.16.
- `[Done]` 9.15 Student Academic Dashboard. Report:
  `docs/21_Student-Academic-Dashboard-Report.md`. `[Open]` Its announcements
  card shipped with 9.19; lecturer / faculty-admin dashboards are still previews.
- `[Done]` 9.16 / 9.17 Document Management & Verification. Report:
  `docs/22_Documents-and-Verification-Report.md`. `[Open]` QR image on the PDF,
  document fees (`requires_fee`) not billed, document-type management screen,
  transition audit trail (9.24).
- `[Done]` 9.18 Invoices & Payment Records. Report:
  `docs/23_Invoices-and-Payments-Report.md`. `[Done]` The `scheduler`
  container (9.20) now runs `invoices:refresh-statuses` daily. `[Open]` Invoice PDFs / receipts, automatic tuition invoices and document-fee billing
  are not built; finance changes are not audited (9.24).
- `[Done]` Pagination never rendered on 12 list pages (Users, Students,
  Lecturers, Courses, Programs, Faculties, Universities, Academic years,
  Offerings, Rooms, Enrollments, Error logs): they passed a Resource
  collection's `links` object instead of its `meta.links` array to
  `Pagination`. All now read `meta.links`; `tests/Feature/PaginationContractTest.php`
  pins the shape for every paginated index page.
- `[Done]` 9.19 Announcements. Report: `docs/24_Announcements-Report.md`.
  `[Done]` Delivery by email / Telegram (9.20 / 9.21). `[Open]` Attachments,
  read receipts, scheduled publishing, Faculty Admin authoring (needs unit
  scoping).
- `[Done]` 9.20 / 9.21 Email & Telegram Notifications. Report:
  `docs/25_Notifications-Report.md`. `[Done]` Password-reset emails (report
  29). `[Open]` Class-start reminders, in-app inbox, automatic Telegram chat
  linking (bot webhook), surfacing repeated delivery failures in the audit log
  (9.24).
- `[Done]` 9.22 Internship Management. Report:
  `docs/26_Internship-Management-Report.md`. `[Open]` Opportunity postings,
  supervisor logins, Faculty Admin approval (unit-level writes). `[Done]`
  Internship letter document.
- `[Done]` `educore/backend:dev` rebuilt: the image predated the
  production-only config caching fix, so containers started from it cached
  config and pointed the test suite at the development database (the
  `TestCase` guard refused). README §13 documents the rebuild.
- `[Done]` Flaky `GradingTest` fixed (explicit academic-year codes moved outside
  the factory's random 1950–2099 range).
- `[Done]` 9.23 Analytics & Reporting. Report:
  `docs/27_Analytics-and-Reporting-Report.md`. `[Done]` CSV exports. `[Open]` PDF exports,
  Faculty Admin analytics (scoping exists now, report 32), trends across semesters; the charts were
  not inspected in a browser when shipped.
- `[Done]` 9.24 Audit Logs & Security. Report:
  `docs/28_Audit-Logs-and-Security-Report.md`. `[Done]` Audit CSV export. `[Open]` Audit retention,
  alerting on repeated notification failures, auditing low-risk
  structure CRUD, two-factor sign-in.
- `[Done]` Password change and emailed password reset (web + API; other
  sessions / tokens end; audited; rate limited). Report:
  `docs/29_Password-Change-and-Reset-Report.md`.
- `[Done]` Business-rule refusals (`BusinessRuleException`, answered 409) are
  no longer reported to `laravel.log` as ERROR with a stack trace — they were
  ~98% of logged errors (2,712 of 2,774) and would bury real failures
  (`tests/Feature/ExceptionReportingTest.php`).
- `[Done]` Container entrypoint: `storage:link` runs only when the link is
  missing — backend, queue and scheduler raced on the shared bind mount
  ("symlink(): File exists"). Image rebuilt.
- All modules of business-overview §9 are now implemented. Remaining open
  items are listed above.
- `[Done]` Grade finalization lock, student certificate and internship letter
  templates. Report: `docs/30_Grade-Finalization-and-Document-Templates-Report.md`. Existing databases: re-run
  `DocumentTypeSeeder` to add the two document types.
- `[Done]` CSV exports: invoices, enrollments, audit trail and analytics
  tables, streamed, same filters and access as the lists, every export
  audited, formula cells neutralized. Report: `docs/31_CSV-Exports-Report.md`.
- `[Done]` Faculty Admin unit scoping: `users.faculty_id`, read access limited
  to the assigned faculty across structure, people, academic activity,
  enrollments, documents and internships; unassigned = no unit data.
  Report: `docs/32_Faculty-Admin-Scoping-Report.md`. `[Open]` Unit-level writes for Faculty Admin,
  department-level admins, Faculty Admin analytics.
- `[Done]` Audit fix (2026-10-03): `/enrollments`, `/offerings/{offering}`,
  `/lecturers` and `/students` sent university-wide form options (students,
  open sections, lecturers, unlinked accounts) to a Faculty Admin; they are
  now sent only to users allowed to act. Report:
  `docs/32_Faculty-Admin-Scoping-Report.md` §4.
- `[Done]` Test infrastructure (2026-10-03): tests no longer write into the
  shared development log (`LOG_CHANNEL=null` in `phpunit.xml`; 3,371 test
  entries removed from `laravel.log`, development entries kept), and
  `docker/postgres/init/01-create-test-database.sh` creates `educore_test` on a
  fresh volume (previously created by hand; README §13 covers older volumes).
