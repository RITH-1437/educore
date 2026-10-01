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
| 1 | `RoleSeeder` | the five platform roles (`super-admin`, `university-admin`, `faculty-admin`, `lecturer`, `student`) — **[Implemented]** |
| 2 | `UserSeeder` | 1 super-admin (`admin@educore.kh`), `is_active`, email verified — **[Implemented]** |
| 3 | `UniversityStructureSeeder` | 1 current university, 3 faculties, 2 departments per faculty, fixed codes — **[Implemented]** |
| 4 | `AcademicYearSeeder` | academic years with a current year and semesters — **[Implemented]** |
| 5 | `CalendarSeeder` | (planned) additional calendar rows: enrollment windows, academic periods |
| 6 | `CourseSeeder` | (planned) courses per program (fixed codes `CSE101`, `MAT101`…), `course_programs` mapping, prerequisites |
| 7 | `GradingScaleSeeder` | (planned) default A→F scale (4.0 / 3.5 / 3.0 / 2.5 / 2.0 / 1.0 / 0.0) |
| 8 | `PeopleSeeder` | (planned) ~10 lecturers, ~60 students across programs; distinct `student_number` sequences |
| 9 | `OfferingSeeder` | (planned) course offerings per semester, ~2 sections each, `section_lecturers`, schedule entries with no timetable conflicts |
| 10 | `EnrollmentSeeder` | (planned) students enrolled in sections (≤ capacity), some `dropped`/`withdrawn` for realism |
| 11 | `AssessmentSeeder` | (planned) assignments, submissions, attendance sessions + records, exam rows, exam results, final `grades` |
| 12 | `FinanceSeeder` | (planned) invoices + items (tuition/fees) for a subset, several paid, one overdue, one reversal |
| 13 | `CommunicationSeeder` | (planned) a few announcements, notification preferences |
| 14 | `InternshipSeeder` | (planned) 3 companies, several internships across statuses, one report + evaluation |
| 15 | `DocumentSeeder` | (planned) document types, sample requests with issued documents |
| 16 | `AuditSeeder` | (skip — `audit_logs` is observational; only noisy real actions produce rows) |

### `UniversityStructureSeeder` (Module 9.4)

The seeder is idempotent — re-running it matches rows on natural keys rather
than inserting duplicates, so `migrate:fresh --seed` and repeated `db:seed`
both converge on 1 university / 3 faculties / 6 departments.

| University | Faculty | Departments |
|---|---|---|
| `ITC` — Institute of Technology Cambodia | `ENG` | `CSE`, `EEE` |
| | `SCI` | `PHY`, `MTH` |
| | `HSS` | `ENG-L`, `ECO` |

Invariants it enforces and tests assert:

- Exactly one university row holds `is_current`. Promotion goes through
  `UniversityService::makeCurrent()`, so seeding and the UI share one code path.
- Faculty `code` and `name` are matched on, honouring the new
  `uq_faculties_name` constraint.
- Department `code` is matched on inside its faculty, honouring
  `uq_departments_faculty_id_name`.
- Every row is created active and not soft-deleted, so a seeded tree is never
  hidden behind the archive filters.

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