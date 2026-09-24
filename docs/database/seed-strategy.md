# EduCore Database Seed Strategy

Deterministic, idempotent seeding so `migrate:fresh --seed` produces a usable
demo/CI environment with zero manual steps.

## 1. Principles

- **Deterministic** — same seed input → same DB state (fixed Faker seed,
  explicit slugs/codes everywhere).
- **Idempotent** — re-running seeds must not duplicate rows (upsert by
  `code`/`slug`/`email` rather than blind `create`).
- **No real PII** — all people records are clearly fabricated demo data;
  dev credentials are documented, not guessed.
- **Environment-aware** — bypass heavy seeding in production (`--env=production`
  refuses demo records via an `EnvironmentIndicator` guard).

## 2. Seeder order

| Step | Seeder | Data |
|---|---|---|
| 1 | `RolePermissionSeeder` | roles: `super-admin`, `admin`, `registrar`, `lecturer`, `student`; permissions per module; `permission_role` matrix via RBAC blueprint |
| 2 | `AdminSeeder` | 1 super-admin (`admin@educore.local` / `password`), 1 registrar, 1 admin — all documented in README dev section |
| 3 | `UniversitySeeder` | `universities` → 1 current row; faculties (3), departments (2/faculty), programs (2/department), all with fixed codes |
| 4 | `CalendarSeeder` | academic years (e.g. 2025/2026 current), semesters (fall/spring) with enrollment windows |
| 5 | `CourseSeeder` | courses per program (fixed codes `CSE101`, `MAT101`…), `course_programs` mapping, prerequisites |
| 6 | `GradingScaleSeeder` | default A→F scale (4.0 / 3.5 / 3.0 / 2.5 / 2.0 / 1.0 / 0.0) |
| 7 | `PeopleSeeder` | ~10 lecturers (`lecturer{1..10}@educore.local`), ~60 students (`student{1..60}`) across programs; distinct `student_number` sequences |
| 8 | `OfferingSeeder` | course offerings per semester, ~2 sections each, `section_lecturers`, schedule entries in weekdays 08:00–17:00, no conflicts (validated by `TimetableService`) |
| 9 | `EnrollmentSeeder` | students enrolled in sections (≤ capacity), some `dropped`/`withdrawn` for realism |
| 10 | `AssessmentSeeder` | assignments, submissions, attendance sessions + records, exam rows, exam results, final `grades` |
| 11 | `FinanceSeeder` | invoices + items (tuition/fees) for a subset, several paid, one overdue, one reversal |
| 12 | `CommunicationSeeder` | a few announcements, notification preferences |
| 13 | `InternshipSeeder` | 3 companies, several internships across statuses, one report + evaluation |
| 14 | `DocumentSeeder` | document types, sample requests with issued documents |
| 15 | `AuditSeeder` | (skip — `audit_logs` is observational; only noisy real actions produce rows) |

## 3. Determinism utilities

- `StringGenerator`-style helpers produce `COURSE_slug`, `invoice_number`
  sequences from stored counters, so business keys never collide.
- Fixed `random seed` prevents test flakiness across runs.
- All `created_at`/`updated_at` explicitly set for calendar-sensitive rows so
  enrollment windows/attendance dates fall inside the seeded semester.

## 4. Refactor/deploy note

- Seeders live next to migrations in `backend/database/seeders/`.
- Production path: real registrar imports data via APIs, seeders are
  **disabled** for production except `RolePermissionSeeder` (upsert-based,
  safe to run on deploy).