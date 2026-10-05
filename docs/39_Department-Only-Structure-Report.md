# 39 — Department-Only Academic Structure Report

- **Date:** 2026-10-05
- **Scope:** removal of the faculty level in the academic hierarchy, flattening structure to University → Department → Program; migration of `faculty-admin` to `department-admin`; direct assignment of unit admins to departments via `users.department_id`
- **Status:** `[Implemented]`
- **Depends on:** `docs/7_Faculty-and-Department-Report.md`, `docs/32_Faculty-Admin-Scoping-Report.md`, `docs/33_Faculty-Admin-Request-Handling-Report.md`, `docs/34_Faculty-Admin-Dashboard-Report.md`
- **Schema change:** `2026_10_05_120000_remove_faculty_level.php` (table `faculties` dropped, `departments.university_id` added, `users.faculty_id` replaced by `users.department_id`, internship evaluator constraint updated); follow-up `2026_10_05_170000_drop_faculty_from_announcement_audiences.php` (§7)

---

## 1. Goal & Architecture Rationale

In the original academic hierarchy (University → Faculty → Department → Program), faculties introduced an extra layer of organizational hierarchy between the university and departments. For most modern institutions and department-first administrative structures, departments operate as the direct academic and operational units under the university.

Removing the faculty tier simplifies the model:
1. **Direct Hierarchy:** `University` → `Department` → `Program` → `Course` / `Student`.
2. **Simplified Administration:** Unit administrators manage a specific department (`department-admin` role, assigned via `users.department_id`).
3. **Streamlined Navigation & UI:** Departments sit directly under Academic Structure in the navigation and top-level admin forms.

```mermaid
flowchart TD
  U[University] --> D1[Department of Computer Science]
  U --> D2[Department of Engineering]
  U --> D3[Department of Business]
  D1 --> P1[Bachelor of Computer Science]
  D1 --> P2[Bachelor of Software Engineering]
  D2 --> P3[Bachelor of Electrical Engineering]
  D3 --> P4[Bachelor of Business Administration]
  D1 --> L1[Lecturers]
  D1 --> C1[Courses]
  P1 --> S1[Students]
```

---

## 2. Database Migration

Migration `2026_10_05_120000_remove_faculty_level.php` performs the complete transformation:

1. **Departments to University:**
   - Adds `departments.university_id` foreign key referencing `universities(id)`.
   - Backfills `departments.university_id` from parent `faculties.university_id`.
   - Resolves any name collisions across former faculties by appending department code.
   - Drops `departments.faculty_id` foreign key and constraint.
   - Adds unique constraint `uq_departments_university_id_name` on `(university_id, name)`.

2. **Users & Role Mapping:**
   - Replaces `users.faculty_id` with `users.department_id` referencing `departments(id)`.
   - Updates role `roles.slug = 'faculty-admin'` to `'department-admin'` ("Department Admin").

3. **Announcements & Evaluations:**
   - Announcements targeting `audience_type = 'faculty'` are redirected to `'staff'`.
   - Table `internship_evaluations` constraint updated: `evaluator_type IN ('supervisor', 'academic')` (migrating `'faculty'` to `'academic'`).

4. **Dropping Faculties:**
   - Drops the `faculties` table entirely.

```mermaid
erDiagram
  UNIVERSITIES ||--o{ DEPARTMENTS : owns
  DEPARTMENTS ||--o{ PROGRAMS : offers
  DEPARTMENTS ||--o{ COURSES : delivers
  DEPARTMENTS ||--o{ LECTURERS : employs
  DEPARTMENTS ||--o{ USERS : assigns_admin
  PROGRAMS ||--o{ STUDENT_PROGRAMS : enrolls
```

---

## 3. Scoping & Authorization

Unit scoping rules previously implemented in `FacultyScope` are now consolidated in `App\Support\DepartmentScope`:

- **Department Admin Scope:** A `department-admin` user is scoped to their assigned `users.department_id`.
  - Unassigned `department-admin` (`department_id === null`) sees zero unit records.
  - Assigned `department-admin` can view their department, its programs, courses, lecturers, student records enrolled in its programs, and operational queues.
- **Request Processing:** Department Admins approve, reject, and generate document requests, and review and evaluate internships for students in their department's programs.
- **Role Policies:**
  - `DepartmentPolicy`: Super Admin and University Admin manage departments; Department Admin can view their own department.
  - `CoursePolicy`, `ProgramPolicy`, `LecturerPolicy`, `StudentPolicy`: Department Admin has scoped view permissions.

---

## 4. Operational Dashboard

The Department Admin dashboard (`frontend/src/pages/DepartmentAdmin/Dashboard.vue`) serves the real-time operational queues and academic overview for the assigned department:

| Section | Metric | Meaning | Filtered Queue Link |
|---|---|---|---|
| **Waiting for you** | Requests to approve | Document requests in `pending` status | `/documents?filters[status]=pending` |
| | PDFs to generate | Document requests in `approved` status | `/documents?filters[status]=approved` |
| | Applications to review | Internships in `submitted` status | `/internships?filters[status]=submitted` |
| | Decisions to make | Internships in `under_review` status | `/internships?filters[status]=under_review` |
| **Your department** | Active students | Students with status `active` in department programs | `/students?filters[status]=active` |
| | Active lecturers | Active lecturers affiliated with the department | `/lecturers?filters[is_active]=1` |
| | Sections | Running sections for courses in the department | `/offerings?semester_id=...` |
| | Students enrolled | Students in department programs enrolled this semester | `/enrollments?semester_id=...` |

---

## 5. Endpoints

| Method | Path | Description | Access |
|---|---|---|---|
| GET | `/departments` | Paginated list of departments (web) | Super Admin, University Admin, Department Admin |
| POST | `/departments` | Create department | Super Admin, University Admin |
| GET | `/departments/{department}/edit` | Department edit form | Super Admin, University Admin |
| PUT | `/departments/{department}` | Update department | Super Admin, University Admin |
| POST | `/departments/{department}/archive` | Archive department | Super Admin, University Admin |
| POST | `/departments/{department}/reactivate` | Reactivate department | Super Admin, University Admin |
| DELETE | `/departments/{department}` | Soft-delete department (guarded against children) | Super Admin, University Admin |
| GET | `/dashboard` | Department Admin dashboard | Authenticated `department-admin` |
| GET | `/api/departments` | List departments API | Sanctum (managers + Department Admin) |
| GET | `/api/departments/{department}` | Show department API | Sanctum (managers + Department Admin) |
| POST | `/api/departments` | Store department API | Sanctum (Super Admin, University Admin) |
| PUT/PATCH | `/api/departments/{department}` | Update department API | Sanctum (Super Admin, University Admin) |
| DELETE | `/api/departments/{department}` | Delete department API | Sanctum (Super Admin, University Admin) |
| GET | `/api/departments/{department}/dashboard` | Department metrics API | Sanctum (managers + assigned Department Admin) |

---

## 6. Verification & Test Suite

All tests pass without failures:
- `Tests\Feature\Departments\DepartmentManagementTest`: 12 passed (lifecycle, unique constraints, delete guards, soft delete).
- `Tests\Feature\Departments\DepartmentAdminScopingTest`: 6 passed (unit assignment, scoping across people and academic activity, unassigned isolation).
- `Tests\Feature\Departments\DepartmentAdminRequestHandlingTest`: 2 passed (document request and internship handling).
- `Tests\Feature\Departments\DepartmentAdminDashboardTest`: 2 passed (live aggregate counts, semester handling, authorization).
- Complete backend suite: **409 tests passed (3,916 assertions)**.
- Code style: `vendor/bin/pint --test` clean.
- Frontend build: `npm run build` clean.

---

## 7. Audit follow-up (2026-10-05): frontend and documentation

The migration, backend and tests above were complete, but the Vue pages and
several documents still assumed faculties. Found in a post-release audit and
fixed:

| Where | Symptom | Fix |
|---|---|---|
| `Users/Create`, `Users/Edit` | Looked for the `faculty-admin` slug and sent `faculty_id`: no department could be assigned, and **saving a Department Admin cleared their department** (`UpdateUserData` always writes `department_id`) | `department-admin` + `department_id`, options from the `departments` prop |
| `Users/Index` | No unit shown under Department Admins | Shows the assigned department |
| `EvaluationsCard` (internship) | Sent `evaluator_type: faculty` → **422** | `academic` ("Academic supervisor") |
| `Admin/Dashboard` | "Faculties" tile blank, linked to the removed `/faculties` (404) | "Departments" (`stats.total_departments`) → `/departments` |
| `Universities/Index`, `Universities/Edit` | "Faculties: 0", *Manage faculties* → 404 | `departments_count`, *Manage departments* |
| Course / Program / Lecturer / Student lists | Empty *Faculty* filter sending `faculty_id`, ignored by the backend | *Department* filter (`filters[department_id]`, already supported by the list DTOs); `/students` now echoes it |
| Course / Program / Lecturer forms | Empty *Faculty* pre-filter above the department | Removed; department full width |
| Student form | Programs narrowed by a faculty that no longer exists | Narrowed by department; a program outside it is cleared |
| Announcement pages | Copy mentioned faculty audiences | Removed |
| `/faculties` bookmarks | 404 (two were in the error log) | `/faculties` and `/faculties/*` redirect (301) to `/departments` |
| `ck_announcements_audience` | Still allowed `faculty` (validation refused it, the database did not) | Migration `2026_10_05_170000_drop_faculty_from_announcement_audiences` drops it (any stray row → `staff`, as in §2) |
| `README.md`, `docs/database/` | Seeded `faculty@educore.kh`, `/faculties` route, `faculties` table, `users.faculty_id`, evaluator `faculty` | Updated to the department-only schema |

```mermaid
flowchart LR
  A[Users/Edit before] -->|faculty_id ignored| B[department_id = null]
  B --> C[Department Admin sees no unit data]
  D[Users/Edit after] -->|department_id| E[department kept / moved]
```

Tests added to `DepartmentAdminScopingTest`: the web create / edit / update
round trip keeps a Department Admin's department, and the people and catalog
lists filter by department (with the dashboard's department count).
