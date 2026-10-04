# EduCore Database Seed Strategy

`db:seed` (`make seed`) prepares a **fresh installation**: roles, one account per
role, and the system configuration that has no create screen. It seeds **no demo
people or records** — every screen, including the public landing page's figures,
shows what is actually in the database (updated 2026-10-05, report 37).

## 1. Principles

- **Idempotent** — re-running never duplicates rows (upsert by `slug` / `email` / `code`).
- **Accounts only** — the seeded accounts have no lecturer/student profile or
  faculty assignment; each role signs in to its dashboard's empty state until
  real records are linked in the application.
- **Development credentials** — documented below; change or remove them before
  any real deployment.

## 2. What `DatabaseSeeder` runs

| Step | Seeder | Data |
|---|---|---|
| 1 | `RoleSeeder` | the five platform roles (`super-admin`, `university-admin`, `faculty-admin`, `lecturer`, `student`) |
| 2 | `UserSeeder` | five accounts, one per role (table below) |
| 3 | `GradingScaleSeeder` | the default letter scale grades are mapped against (the UI edits it but cannot create it) |
| 4 | `DocumentTypeSeeder` | the requestable official document types (no create screen) |

| Account | Password | Role |
|---|---|---|
| `admin@educore.kh` | `admin@123` | Super Admin |
| `university@educore.kh` | `university@123` | University Admin |
| `faculty@educore.kh` | `faculty@123` | Faculty Admin |
| `lecturer@educore.kh` | `lecturer@123` | Lecturer |
| `student@educore.kh` | `student@123` | Student |

Removed (2026-10-05): the demo-data seeders `AnnouncementSeeder`,
`AssignmentSeeder`, `AttendanceSeeder`, `ExamSeeder`, `InternshipSeeder`,
`InvoiceSeeder`.

## 3. Test fixture seeders (not run by `db:seed`)

These classes stay in `backend/database/seeders/` because feature tests load
them as fixtures (`$this->seed(...)`): `UniversityStructureSeeder`,
`ProgramSeeder`, `CourseSeeder`, `LecturerSeeder`, `StudentSeeder`,
`AcademicYearSeeder`, `CourseOfferingSeeder`, `RoomSeeder`, `ScheduleSeeder`,
`EnrollmentSeeder`, `ErrorLogSeeder`. Their behaviour is described below.

### Fixture details — `UniversityStructureSeeder` (Module 9.4) and dependants

The seeder is idempotent — re-running it matches rows on natural keys rather
than inserting duplicates, so `migrate:fresh --seed` and repeated `db:seed`
both converge on 1 university / 3 faculties / 6 departments (when a test seeds it).

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
- `ProgramSeeder` (runs after the structure) upserts 7 degree programs by their
  unique `code`, attached to the seeded departments by department `code`;
  missing departments are skipped, never created.
- `CourseSeeder` (after programs) upserts 10 courses by unique `code`, builds an
  acyclic prerequisite chain and curricula for the seeded programs using
  `syncWithoutDetaching`, so re-running never duplicates links. Missing
  departments/programs are skipped.
- `LecturerSeeder` (needs the Lecturer role and departments) upserts 6 lecturer
  accounts by email (dev password `lecturer@123`) and their profiles by
  `staff_number`.
- `StudentSeeder` upserts 10 student accounts by email (dev password
  `student@123`) and profiles by `student_number`, opening an active program
  period only when none exists.
- `CourseOfferingSeeder` (after the academic calendar) offers 6 courses in the
  open semester with 8 sections and a primary lecturer each; upserts by
  `(course_id, semester_id)` and `(course_offering_id, code)`.
- `RoomSeeder` upserts 7 rooms by `code`; `ScheduleSeeder` gives each open
  section two weekly meetings through `TimetableService` (conflict-free by
  construction; sections that already have meetings are skipped). Both run
  before `EnrollmentSeeder` so student clashes are checked.
- `EnrollmentSeeder` enrolls active students in open sections of their
  curriculum through `EnrollmentService`, so every rule applies; refused or
  duplicate enrollments are skipped (idempotent).

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