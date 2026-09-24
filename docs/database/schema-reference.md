# EduCore Database Schema Reference

Per-table reference for all 49 business tables. Column rules are authoritative
here and in `schema-tables.sql`. Type conventions: see
`database-conventions.md`. Status sets are `CHECK` constraints.

Legend: PK = primary key · FK = foreign key · UQ = unique · IDX = index ·
ts = `created_at` / `updated_at`.

---

## Module 1 — Identity & Access

### Table: users

Purpose: user account with login credentials, role, and activation state.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| name | varchar(255) | no | | Display name |
| email | varchar(255) | no | UQ | Login identity |
| email_verified_at | timestamptz | yes | | When email verified |
| password | varchar(255) | no | | Hashed |
| remember_token | varchar(100) | yes | | Sanctum/session remember |
| role_id | bigint | no | | FK → roles.id |
| phone | varchar(50) | yes | | Contact |
| avatar_key | varchar(255) | yes | | MinIO key |
| is_active | boolean | no | true | Account usable |
| last_login_at | timestamptz | yes | | |
| deleted_at | timestamptz | yes | | Soft delete for accounts |

Relationships:

- belongs to `roles` (1–1)
- has one `students`, has one `lecturers`
- has many `audit_logs`, `announcements` (author), `document_requests.processed_by`, `payments.received_by`

Constraints:

- UQ `email`
- FK `role_id` → `roles.id` RESTRICT
- `is_active` not null

Indexes:

- PK `id`; UQ `email`; IDX `role_id`

Business Rules:

- Password always hashed; never plaintext.
- One role per user in the MVP (see `decisions.md`).
- A user may have a `students` row, a `lecturers` row, or neither (admins) — never both.

---

### Table: roles

Purpose: the five system roles.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| name | varchar(100) | no | UQ | e.g. "University Admin" |
| slug | varchar(100) | no | UQ | e.g. `university_admin` |
| description | text | yes | | |
| is_system | boolean | no | false | System role cannot be deleted |
| created_at/updated_at | timestamptz | no | ts | |

Relationships:

- has many `users`
- belongs to many `permissions` via `permission_role`

Constraints:

- UQ `name`, UQ `slug`

Indexes:

- PK `id`; UQ `name`; UQ `slug`

Business Rules:

- System roles: `super_admin`, `university_admin`, `faculty_admin`, `lecturer`, `student`.
- `is_system = true` rows are never hard-deleted.

---

### Table: permissions

Purpose: granular permission catalog for RBAC.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| name | varchar(100) | no | UQ | `manage.students` |
| slug | varchar(100) | no | UQ | `manage_students` |
| module | varchar(100) | yes | | Domain grouping |
| description | text | yes | | |

Relationships:

- belongs to many `roles` via `permission_role`

Constraints:

- UQ `name`, UQ `slug`

Indexes:

- PK `id`; UQ `name`; UQ `slug`; IDX `module`

---

### Table: permission_role

Purpose: pivot linking roles to permissions.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| role_id | bigint | no | | FK → roles.id |
| permission_id | bigint | no | | FK → permissions.id |

Constraints:

- UQ `(role_id, permission_id)`
- FK `role_id` → `roles.id` CASCADE; FK `permission_id` → `permissions.id` CASCADE

Indexes:

- PK `id`; UQ `(role_id, permission_id)`; IDX `permission_id`

---

### Table: settings

Purpose: institution/system key-value configuration.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| key | varchar(100) | no | UQ | e.g. `semester.credit_limit` |
| value | text | yes | | JSON-encoded when structured |
| group | varchar(50) | no | `general` | Config group |
| type | varchar(20) | no | `string` | `string`·`integer`·`boolean`·`json` |
| is_public | boolean | no | false | Safe to expose |
| description | text | yes | | |

Constraints:

- UQ `key`

Indexes:

- PK `id`; UQ `key`; IDX `group`

---

## Module 2 — University Structure

### Table: universities

Purpose: the institution itself (system-level root of the hierarchy).

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| code | varchar(50) | no | UQ | Institution code |
| name | varchar(255) | no | | Full name |
| short_name | varchar(100) | yes | | Abbreviation |
| address | text | yes | | |
| phone | varchar(50) | yes | | |
| email | varchar(150) | yes | | |
| logo_key | varchar(255) | yes | | MinIO key |
| website | varchar(255) | yes | | |
| is_current | boolean | no | false | Active-institution flag |

Relationships:

- has many `faculties`

Constraints:

- UQ `code`

Indexes:

- PK `id`; UQ `code`

---

### Table: faculties

Purpose: broad academic division (e.g. Faculty of Engineering).

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| university_id | bigint | no | | FK → universities.id |
| code | varchar(50) | no | UQ | |
| name | varchar(255) | no | | |
| dean_name | varchar(255) | yes | | |
| description | text | yes | | |
| is_active | boolean | no | true | |
| created_at/updated_at/deleted_at | | | ts | soft delete allowed |

Relationships:

- belongs to `universities`
- has many `departments`

Constraints:

- UQ `code`
- FK `university_id` → `universities.id` RESTRICT

Indexes:

- PK `id`; UQ `code`; IDX `university_id`

---

### Table: departments

Purpose: sub-division of a faculty (e.g. Computer Science).

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| faculty_id | bigint | no | | FK → faculties.id |
| code | varchar(50) | no | UQ | |
| name | varchar(255) | no | | |
| head_name | varchar(255) | yes | | |
| description | text | yes | | |
| is_active | boolean | no | true | |

Relationships:

- belongs to `faculties`
- has many `programs`, `courses`, `lecturers`

Constraints:

- UQ `code`
- FK `faculty_id` → `faculties.id` RESTRICT

Indexes:

- PK `id`; UQ `code`; IDX `faculty_id`

---

### Table: programs

Purpose: a degree track within a department (e.g. BSc Computer Science).

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| department_id | bigint | no | | FK → departments.id |
| code | varchar(50) | no | UQ | |
| name | varchar(255) | no | | |
| degree_level | varchar(50) | no | | bachelor/master/phd/… |
| duration_years | smallint | yes | | |
| credits_required | numeric(5,1) | yes | | Graduation credits |
| is_active | boolean | no | true | |

Relationships:

- belongs to `departments`
- belongs to many `courses` via `course_programs`
- belongs to many `students` via `student_programs`

Constraints:

- UQ `code`
- FK `department_id` → `departments.id` RESTRICT

Indexes:

- PK `id`; UQ `code`; IDX `department_id`

---

## Module 3 — Academic Management

### Table: academic_years

Purpose: an academic year (e.g. 2026–2027).

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| code | varchar(50) | no | UQ | `2026-2027` |
| name | varchar(100) | no | | Display |
| start_date | date | no | | |
| end_date | date | no | | |
| status | varchar(20) | no | `planned` | planned/active/completed |
| is_current | boolean | no | false | |

Relationships:

- has many `semesters`, and is snapshotted on `enrollments`

Constraints:

- UQ `code`; CHECK status; CHECK `start_date < end_date`

Indexes:

- PK `id`; UQ `code`

---

### Table: semesters

Purpose: a term within an academic year.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| academic_year_id | bigint | no | | FK → academic_years.id |
| name | varchar(50) | no | | "Semester 1" |
| code | varchar(20) | no | | `S1` |
| sequence | smallint | no | | 1, 2, … |
| start_date | date | yes | | Teaching span |
| end_date | date | yes | | |
| enrollment_start | date | yes | | Registration window |
| enrollment_end | date | yes | | |
| exam_start | date | yes | | |
| exam_end | date | yes | | |
| status | varchar(20) | no | `planned` | planned/open/closed/completed |

Relationships:

- belongs to `academic_years`
- has many `course_offerings`
- snapshotted on `enrollments`

Constraints:

- UQ `(academic_year_id, sequence)`; CHECK status; CHECK dates sane

Indexes:

- PK `id`; UQ `(academic_year_id, sequence)`; IDX `academic_year_id`

---

### Table: courses

Purpose: stable curriculum unit (code, name, credits).

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| department_id | bigint | no | | FK → departments.id |
| code | varchar(20) | no | UQ | `CS101` |
| name | varchar(255) | no | | |
| credits | numeric(4,2) | no | | CHECK > 0 |
| lecture_hours | smallint | yes | | |
| lab_hours | smallint | yes | | |
| description | text | yes | | |
| course_level | varchar(20) | yes | | 1st/2nd/… |
| status | varchar(20) | no | `active` | draft/active/archived |

Relationships:

- belongs to `departments`
- belongs to many `programs` via `course_programs`
- has self-prerequisites via `course_prerequisites`
- has many `course_offerings`
- has one `course_grading_configs`

Constraints:

- UQ `code`; CHECK `credits > 0`; CHECK status
- FK `department_id` → `departments.id` RESTRICT

Indexes:

- PK `id`; UQ `code`; IDX `department_id`; IDX `status`

---

### Table: course_programs

Purpose: pivot mapping courses to programs.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| course_id | bigint | no | | FK → courses.id |
| program_id | bigint | no | | FK → programs.id |
| is_required | boolean | no | false | Required degree course |
| suggested_semester | smallint | yes | | Curriculum position |

Constraints:

- UQ `(course_id, program_id)`
- FK course/program CASCADE

Indexes:

- PK `id`; UQ `(course_id, program_id)`; IDX `program_id`

---

### Table: course_prerequisites

Purpose: prerequisite edges between courses.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| course_id | bigint | no | | FK → courses.id (the dependent) |
| prerequisite_course_id | bigint | no | | FK → courses.id (required first) |
| is_strict | boolean | no | true | |

Constraints:

- UQ `(course_id, prerequisite_course_id)`
- CHECK `course_id <> prerequisite_course_id`
- FK both → `courses.id` RESTRICT

Indexes:

- PK `id`; UQ `(course_id, prerequisite_course_id)`; IDX `prerequisite_course_id`

---

### Table: course_offerings

Purpose: a course taught in one semester (offering = course × semester).

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| course_id | bigint | no | | FK → courses.id |
| semester_id | bigint | no | | FK → semesters.id |
| status | varchar(20) | no | `draft` | draft/published/open/closed |
| max_enrollments | integer | yes | | Optional global cap |
| notes | text | yes | | |

Relationships:

- belongs to `courses`, `semesters`
- has many `sections`

Constraints:

- UQ `(course_id, semester_id)`; CHECK status
- FK `course_id` RESTRICT; FK `semester_id` RESTRICT

Indexes:

- PK `id`; UQ `(course_id, semester_id)`; IDX `semester_id`

---

### Table: sections

Purpose: a concrete class instance of an offering (Section A, B, …).

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| course_offering_id | bigint | no | | FK → course_offerings.id |
| code | varchar(20) | no | | `A`, `B` |
| name | varchar(100) | yes | | Optional display |
| capacity | smallint | no | 30 | CHECK >= 0 |
| status | varchar(20) | no | `draft` | draft/open/active/closed/archived |

Relationships:

- belongs to `course_offerings`
- belongs to many `lecturers` via `section_lecturers`
- has many `enrollments`, `schedule_entries`, `attendance_sessions`, `assignments`, `exams`

Constraints:

- UQ `(course_offering_id, code)`; CHECK `capacity >= 0`; CHECK status
- FK `course_offering_id` RESTRICT

Indexes:

- PK `id`; UQ `(course_offering_id, code)`; IDX `course_offering_id`

---

### Table: rooms

Purpose: physical room.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| code | varchar(50) | no | UQ | |
| name | varchar(100) | no | | |
| building | varchar(100) | yes | | |
| floor | varchar(20) | yes | | |
| capacity | smallint | no | | CHECK > 0 |
| room_type | varchar(20) | no | `lecture` | lecture/lab/seminar/other |
| is_active | boolean | no | true | |

Relationships:

- has many `schedule_entries`

Constraints:

- UQ `code`; CHECK `capacity > 0`; CHECK `room_type`

Indexes:

- PK `id`; UQ `code`

---

### Table: schedule_entries

Purpose: a recurring weekly meeting slot for a section.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| section_id | bigint | no | | FK → sections.id |
| room_id | bigint | no | | FK → rooms.id |
| day_of_week | smallint | no | | 1=Mon … 7=Sun (CHECK 1–7) |
| start_time | time | no | | |
| end_time | time | no | | |
| created_at/updated_at | timestamptz | no | ts | |

Relationships:

- belongs to `sections`, `rooms`

Constraints:

- UQ `(section_id, day_of_week, start_time)` — one meeting per slot per section
- UQ `(room_id, day_of_week, start_time, end_time)` — no equal-window room double-booking
- CHECK `day_of_week BETWEEN 1 AND 7`; CHECK `start_time < end_time`
- FK `section_id` RESTRICT; FK `room_id` RESTRICT

Indexes:

- PK `id`; UQ pairs; IDX `room_id`

Business Rules:

- Partial time overlaps are rejected by `TimetableService` (DB backstop handles
  equal start windows only).
- Lecturer conflicts are prevented in the service layer.

---

### Table: section_lecturers

Purpose: teaching assignment pivot (a section can have multiple staff).

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| section_id | bigint | no | | FK → sections.id |
| lecturer_id | bigint | no | | FK → lecturers.id |
| role | varchar(20) | no | `primary` | primary/assistant/tutor |

Constraints:

- UQ `(section_id, lecturer_id)`
- FK `section_id` CASCADE; FK `lecturer_id` RESTRICT

Indexes:

- PK `id`; UQ `(section_id, lecturer_id)`; IDX `lecturer_id`

---

## Module 4 — People

### Table: students

Purpose: student profile extending a user account.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| user_id | bigint | no | UQ | FK → users.id |
| student_number | varchar(50) | no | UQ | Official student ID |
| first_name | varchar(100) | no | | |
| last_name | varchar(100) | no | | |
| gender | varchar(20) | yes | | male/female/other |
| date_of_birth | date | yes | | |
| address | text | yes | | |
| emergency_contact_name | varchar(150) | yes | | |
| emergency_contact_phone | varchar(50) | yes | | |
| national_id | varchar(50) | yes | UQ | Gov ID (optional) |
| enrollment_date | date | yes | | Initial admission |
| status | varchar(20) | no | `active` | active/inactive/suspended/graduated/withdrawn |

Relationships:

- belongs to `users` (1–1)
- belongs to many `programs` via `student_programs`
- has many `enrollments`, `document_requests`, `invoices`, `internships`

Constraints:

- UQ `user_id`, UQ `student_number`, UQ `national_id`; CHECK `gender`; CHECK `status`
- FK `user_id` → `users.id` RESTRICT

Indexes:

- PK `id`; UQ `student_number`; UQ `user_id`; IDX `status`

---

### Table: student_programs

Purpose: history of a student's program assignments (changes over time).

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| student_id | bigint | no | | FK → students.id |
| program_id | bigint | no | | FK → programs.id |
| started_on | date | no | | Effective start |
| ended_on | date | yes | | Effective end |
| status | varchar(20) | no | `active` | active/completed/withdrawn/transferred |
| notes | text | yes | | |

Constraints:

- UQ `(student_id, program_id, started_on)`
- CHECK `status`; CHECK `ended_on IS NULL OR ended_on >= started_on`
- FK `student_id` RESTRICT; FK `program_id` RESTRICT

Indexes:

- PK `id`; UQ `(student_id, program_id, started_on)`; IDX `program_id`

Business Rules:

- Partial unique index: one `status = 'active'` row per student.

---

### Table: lecturers

Purpose: lecturer profile extending a user account.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| user_id | bigint | no | UQ | FK → users.id |
| staff_number | varchar(50) | no | UQ | |
| first_name | varchar(100) | no | | |
| last_name | varchar(100) | no | | |
| title | varchar(50) | yes | | Dr/Prof/… |
| department_id | bigint | no | | FK → departments.id |
| position | varchar(100) | yes | | |
| specialization | varchar(255) | yes | | |
| employment_type | varchar(20) | no | `full_time` | full_time/part_time/contract/visiting |
| is_active | boolean | no | true | |

Relationships:

- belongs to `users` (1–1), `departments`
- belongs to many `sections` via `section_lecturers`

Constraints:

- UQ `user_id`, UQ `staff_number`; CHECK `employment_type`
- FK `user_id` RESTRICT; FK `department_id` RESTRICT

Indexes:

- PK `id`; UQ `staff_number`; UQ `user_id`; IDX `department_id`

---

## Module 5 — Enrollment

### Table: enrollments

Purpose: a student's registration in a section for a semester. Central anchor
for attendance, assignments, exams, and grades.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| student_id | bigint | no | | FK → students.id |
| section_id | bigint | no | | FK → sections.id |
| academic_year_id | bigint | no | | FK snapshot → academic_years.id |
| semester_id | bigint | no | | FK snapshot → semesters.id |
| status | varchar(20) | no | `pending` | pending/confirmed/completed/dropped/withdrawn |
| enrolled_at | timestamptz | no | now | |
| dropped_at | timestamptz | yes | | |
| deleted_at | timestamptz | yes | | soft delete keeps history |

Relationships:

- belongs to `students`, `sections`, `academic_years`, `semesters`
- has many `attendance_records`, `assignment_submissions`, `exam_results`
- has one `grades`

Constraints:

- UQ `(student_id, section_id)` — prevents double enrollment in the same section
- CHECK `status`
- FK `student_id` RESTRICT, `section_id` RESTRICT, `academic_year_id` RESTRICT, `semester_id` RESTRICT

Indexes:

- PK `id`; UQ `(student_id, section_id)`
- IDX `(academic_year_id, semester_id)` — semester reporting
- IDX `(section_id, status)` — roster views
- IDX `(student_id, academic_year_id)` — student term views

Business Rules (enforced by `EnrollmentService` + DB):

- Prerequisites satisfied; capacity available; student `active`; semester `open`.
- Credit limit from `settings`.
- Same-semester time conflicts rejected by `TimetableService`.

---

## Module 6 — Attendance

### Table: attendance_sessions

Purpose: a dated class session in a section.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| section_id | bigint | no | | FK → sections.id |
| session_date | date | no | | |
| start_time | time | yes | | |
| end_time | time | yes | | |
| topic | varchar(255) | yes | | |
| status | varchar(20) | no | `held` | scheduled/held/cancelled |
| recorded_by | bigint | yes | | FK → users.id (lecturer) |

Relationships:

- belongs to `sections`
- has many `attendance_records`

Constraints:

- UQ `(section_id, session_date)` — one session per day per section
- CHECK `status`
- FK `section_id` RESTRICT; FK `recorded_by` → `users.id` SET NULL

Indexes:

- PK `id`; UQ `(section_id, session_date)`; IDX `recorded_by`

---

### Table: attendance_records

Purpose: raw per-student presence status for a session (immutable history).

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| attendance_session_id | bigint | no | | FK → attendance_sessions.id |
| enrollment_id | bigint | no | | FK → enrollments.id |
| status | varchar(20) | no | | present/absent/late/excused |
| remarks | text | yes | | |
| marked_by | bigint | yes | | FK → users.id |

Relationships:

- belongs to `attendance_sessions`, `enrollments`

Constraints:

- UQ `(attendance_session_id, enrollment_id)` — one status per student per session
- CHECK `status`
- FK `attendance_session_id` RESTRICT; FK `enrollment_id` RESTRICT; FK `marked_by` SET NULL

Indexes:

- PK `id`; UQ `(attendance_session_id, enrollment_id)`; IDX `enrollment_id`

Business Rules:

- Percentages are **derived** by aggregation; never stored.
- Only enrolled students can have records.

---

## Module 7 — Assessment

### Table: assignments

Purpose: a graded task in a section.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| section_id | bigint | no | | FK → sections.id |
| title | varchar(255) | no | | |
| description | text | yes | | |
| instructions | text | yes | | |
| max_score | numeric(6,2) | no | 100 | CHECK > 0 |
| due_at | timestamptz | no | | |
| assignment_type | varchar(20) | no | `homework` | homework/quiz/project/presentation/other |
| weight_override | numeric(5,2) | yes | | Optional weight |
| is_published | boolean | no | false | |
| published_at | timestamptz | yes | | |

Relationships:

- belongs to `sections`
- has many `assignment_submissions`
- may have `files` (materials)

Constraints:

- CHECK `max_score > 0`; CHECK `assignment_type`
- FK `section_id` RESTRICT

Indexes:

- PK `id`; IDX `(section_id, due_at)`

---

### Table: assignment_submissions

Purpose: a student's submission with status, score, and feedback.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| assignment_id | bigint | no | | FK → assignments.id |
| enrollment_id | bigint | no | | FK → enrollments.id |
| submitted_at | timestamptz | no | now | |
| status | varchar(20) | no | `submitted` | submitted/late/graded/returned |
| score | numeric(6,2) | yes | | CHECK >= 0 |
| feedback | text | yes | | |
| graded_by | bigint | yes | | FK → users.id |
| graded_at | timestamptz | yes | | |

Relationships:

- belongs to `assignments`, `enrollments`
- may have `files`

Constraints:

- UQ `(assignment_id, enrollment_id)` — one submission per student per assignment
- CHECK `score >= 0`; CHECK `status`
- FK `assignment_id` RESTRICT; FK `enrollment_id` RESTRICT; FK `graded_by` SET NULL

Indexes:

- PK `id`; UQ `(assignment_id, enrollment_id)`; IDX `enrollment_id`

---

## Module 8 — Examination & Grading

### Table: exams

Purpose: an assessment (midterm/final/quiz/practical) in a section. Schedule
fields are embedded (simple dates/times/location).

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| section_id | bigint | no | | FK → sections.id |
| exam_type | varchar(20) | no | | midterm/final/quiz/practical/other |
| title | varchar(255) | no | | |
| weight | numeric(5,2) | no | 0 | CHECK >= 0 |
| max_score | numeric(6,2) | no | 100 | CHECK > 0 |
| scheduled_date | date | yes | | |
| start_time | time | yes | | |
| end_time | time | yes | | |
| location | varchar(255) | yes | | |
| is_published | boolean | no | false | |

Relationships:

- belongs to `sections`
- has many `exam_results`

Constraints:

- CHECK `exam_type`, `weight >= 0`, `max_score > 0`
- FK `section_id` RESTRICT

Indexes:

- PK `id`; IDX `(section_id, exam_type)`

---

### Table: exam_results

Purpose: a student's score for one exam.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| exam_id | bigint | no | | FK → exams.id |
| enrollment_id | bigint | no | | FK → enrollments.id |
| score | numeric(6,2) | yes | | CHECK >= 0 |
| remarks | text | yes | | |
| recorded_by | bigint | yes | | FK → users.id |

Relationships:

- belongs to `exams`, `enrollments`

Constraints:

- UQ `(exam_id, enrollment_id)` — one result per student per exam
- CHECK `score >= 0`
- FK `exam_id` RESTRICT; FK `enrollment_id` RESTRICT; FK `recorded_by` SET NULL

Indexes:

- PK `id`; UQ `(exam_id, enrollment_id)`; IDX `enrollment_id`

---

### Table: grades

Purpose: the final grade for one enrollment (one per enrollment).

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| enrollment_id | bigint | no | UQ | FK → enrollments.id |
| letter_grade | varchar(5) | yes | | e.g. `A`, `B+`, `F` |
| grade_point | numeric(3,2) | yes | | Points for the letter |
| total_score | numeric(6,2) | yes | | Weighted total |
| status | varchar(20) | no | `draft` | draft/submitted/approved/finalized |
| graded_by | bigint | yes | | FK → users.id |
| submitted_at | timestamptz | yes | | |
| approved_at | timestamptz | yes | | |
| remarks | text | yes | | |
| deleted_at | timestamptz | yes | | soft delete keeps original |

Relationships:

- belongs to `enrollments` (1–1)

Constraints:

- UQ `enrollment_id` — exactly one final grade per enrollment
- CHECK `status`
- FK `enrollment_id` RESTRICT

Indexes:

- PK `id`; UQ `enrollment_id`; IDX `(status, approved_at)`

Business Rules:

- Letter/points come from `grading_scales` by configured weights.
- Any change to a finalized grade requires permission + audit.
- Grade changes trigger GPA recomputation.

---

### Table: grading_scales

Purpose: configurable letter ↔ grade-point mapping per institution.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| name | varchar(100) | no | | Scale name (e.g. "Standard 4.0") |
| grade | varchar(5) | no | | `A`, `B+`, … |
| min_percentage | numeric(5,2) | no | | Band lower bound |
| max_percentage | numeric(5,2) | no | | Band upper bound |
| grade_point | numeric(3,2) | no | | Points |
| is_pass | boolean | no | true | Passing grade? |
| is_active | boolean | no | true | |

Relationships:

- referenced by `grades` mapping logic

Constraints:

- UQ `(name, grade)`; CHECK `min_percentage <= max_percentage`

Indexes:

- PK `id`; UQ `(name, grade)`

---

### Table: course_grading_configs

Purpose: default weighting of grade components per course.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| course_id | bigint | no | UQ | FK → courses.id |
| attendance_weight | numeric(5,2) | no | 10 | |
| assignment_weight | numeric(5,2) | no | 25 | |
| midterm_weight | numeric(5,2) | no | 20 | |
| final_weight | numeric(5,2) | no | 40 | |
| practical_weight | numeric(5,2) | no | 5 | |

Relationships:

- belongs to `courses` (1–1)

Constraints:

- UQ `course_id`
- CHECK each weight >= 0 AND `attendance + assignment + midterm + final + practical = 100`
- FK `course_id` RESTRICT

Indexes:

- PK `id`; UQ `course_id`

---

### Table: gpa_records

Purpose: computed GPA snapshot per student (design says "cached, recomputed").

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| student_id | bigint | no | | FK → students.id |
| academic_year_id | bigint | no | | FK → academic_years.id |
| semester_id | bigint | yes | | NULL = cumulative |
| gpa_value | numeric(4,3) | no | | e.g. 3.750 |
| attempted_credits | numeric(6,2) | yes | | |
| earned_credits | numeric(6,2) | yes | | |
| grade_points | numeric(6,2) | yes | | |
| cumulative | boolean | no | false | false = semester GPA |
| computed_at | timestamptz | no | now | |

Relationships:

- belongs to `students`, `academic_years`, `semesters`

Constraints:

- UQ `(student_id, academic_year_id, semester_id)` — one snapshot per scope (NULLS NOT DISTINCT)
- CHECK `gpa_value >= 0`
- FK `student_id` RESTRICT; FK `academic_year_id` RESTRICT; FK `semester_id` RESTRICT

Indexes:

- PK `id`; UQ trio; IDX `student_id`

Business Rules:

- Written by `GradingService.recalculateGpa()` in the same transaction as grade
  changes. Never treated as source of truth (recomputed).
- Semester row: `semester_id` set, `cumulative = false`; year/cumulative: NULL.

---

## Module 9 — Documents & File Storage

### Table: document_types

Purpose: configurable list of official documents.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| code | varchar(50) | no | UQ | enrollment_certificate/student_certificate/transcript/academic_result/internship_letter/other |
| name | varchar(150) | no | | |
| description | text | yes | | |
| requires_fee | boolean | no | false | |
| is_active | boolean | no | true | |
| sort_order | smallint | no | 0 | |

Indexes:

- PK `id`; UQ `code`

---

### Table: document_requests

Purpose: a student's request for an official document through the workflow.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| student_id | bigint | no | | FK → students.id |
| document_type_id | bigint | no | | FK → document_types.id |
| academic_year_id | bigint | yes | | FK → academic_years.id |
| semester_id | bigint | yes | | FK → semesters.id |
| reason | text | yes | | |
| status | varchar(20) | no | `pending` | pending/approved/rejected/generated |
| submitted_at | timestamptz | no | now | |
| processed_by | bigint | yes | | FK → users.id |
| processed_at | timestamptz | yes | | |
| rejection_reason | text | yes | | |
| notes | text | yes | | |

Relationships:

- belongs to `students`, `document_types`
- has one `documents`

Constraints:

- CHECK `status`
- FK `student_id` RESTRICT; FK `document_type_id` RESTRICT; FK `processed_by` SET NULL

Indexes:

- PK `id`; IDX `(status, submitted_at)`; IDX `student_id`

---

### Table: documents

Purpose: the generated official document (MinIO metadata + verification token).

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| document_request_id | bigint | no | UQ | FK → document_requests.id |
| file_key | varchar(255) | no | | MinIO object key |
| file_name | varchar(255) | no | | |
| mime_type | varchar(100) | yes | | |
| file_size | bigint | yes | | bytes |
| checksum | varchar(64) | yes | | Integrity hash |
| verification_token | varchar(64) | no | UQ | Public QR token (random) |
| generated_by | bigint | yes | | FK → users.id |
| generated_at | timestamptz | no | now | |
| status | varchar(20) | no | `valid` | valid/revoked/expired |
| deleted_at | timestamptz | yes | | soft delete keeps history |

Relationships:

- belongs to `document_requests` (1–1)
- has many `document_verifications`

Constraints:

- UQ `document_request_id`, UQ `verification_token`; CHECK `status`
- FK `document_request_id` RESTRICT; FK `generated_by` SET NULL

Indexes:

- PK `id`; UQ `verification_token`; UQ `document_request_id`

Business Rules:

- `verification_token` is high-entropy and never derived from `id`.
- Revocation uses `status`, not deletion.

---

### Table: document_verifications

Purpose: append-only log of public verification attempts.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| document_id | bigint | no | | FK → documents.id |
| verification_token | varchar(64) | no | | Token presented |
| result | varchar(20) | no | | valid/invalid/revoked/expired |
| verified_at | timestamptz | no | now | |
| ip_address | varchar(45) | yes | | |
| user_agent | text | yes | | |
| created_at | timestamptz | no | | append-only |

Relationships:

- belongs to `documents`

Constraints:

- CHECK `result`
- FK `document_id` RESTRICT

Indexes:

- PK `id`; IDX `document_id`; IDX `verification_token`; IDX `verified_at`

---

### Table: files

Purpose: MinIO file metadata for uploads (polymorphic).

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| fileable_type | varchar(255) | yes | | Morph type |
| fileable_id | bigint | yes | | Morph id |
| uploader_id | bigint | yes | | FK → users.id |
| file_name | varchar(255) | no | | Store name |
| original_name | varchar(255) | no | | |
| storage_key | varchar(255) | no | | MinIO object key |
| bucket | varchar(100) | no | `educore` | |
| mime_type | varchar(100) | yes | | |
| size | bigint | no | | bytes |
| visibility | varchar(20) | no | `private` | private/public |
| checksum | varchar(64) | yes | | |

Relationships:

- morph to `assignments`, `assignment_submissions`, `internship_reports`, `announcements`
- belongs to `users` (uploader)

Constraints:

- CHECK `visibility`
- FK `uploader_id` → `users.id` SET NULL (no FK on polymorphic pair)

Indexes:

- PK `id`; IDX `(fileable_type, fileable_id)`; IDX `uploader_id`

---

## Module 10 — Finance

### Table: invoices

Purpose: record of institutional charges to a student.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| student_id | bigint | no | | FK → students.id |
| invoice_number | varchar(50) | no | UQ | Human-facing |
| title | varchar(255) | no | | |
| description | text | yes | | |
| currency | varchar(10) | no | `USD` | |
| subtotal | numeric(12,2) | no | 0 | |
| discount | numeric(12,2) | no | 0 | |
| total | numeric(12,2) | no | | subtotal − discount |
| amount_paid | numeric(12,2) | no | 0 | maintained on payment |
| status | varchar(20) | no | `pending` | pending/partial/paid/overdue/cancelled |
| issued_date | date | no | today | |
| due_date | date | no | | |
| notes | text | yes | | |
| deleted_at | timestamptz | yes | | soft delete keeps history |

Relationships:

- belongs to `students`
- has many `invoice_items`, `payments`

Constraints:

- UQ `invoice_number`; CHECK `status`
- CHECK `total >= 0`; CHECK `0 <= amount_paid <= total`
- FK `student_id` RESTRICT

Indexes:

- PK `id`; UQ `invoice_number`; IDX `(student_id, status)`; IDX `due_date`

---

### Table: invoice_items

Purpose: line item on an invoice.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| invoice_id | bigint | no | | FK → invoices.id |
| description | varchar(255) | no | | |
| quantity | numeric(8,2) | no | 1 | CHECK > 0 |
| unit_price | numeric(12,2) | no | | CHECK >= 0 |
| amount | numeric(12,2) | no | | quantity × unit_price |
| fee_category | varchar(50) | yes | | tuition/library/lab/other |

Constraints:

- CHECK `quantity > 0`; CHECK `unit_price >= 0`; CHECK `amount = quantity * unit_price`
- FK `invoice_id` → `invoices.id` CASCADE (line item meaningless without invoice)

Indexes:

- PK `id`; IDX `invoice_id`

---

### Table: payments

Purpose: records of received payments (no online gateway).

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| invoice_id | bigint | no | | FK → invoices.id |
| amount | numeric(12,2) | no | | CHECK > 0 |
| paid_on | date | no | | |
| method | varchar(20) | no | | cash/bank_transfer/cheque/other |
| reference | varchar(100) | yes | | Bank/transfer ref |
| received_by | bigint | yes | | FK → users.id |
| notes | text | yes | | |
| is_reversal | boolean | no | false | Reversal record |
| reversal_of | bigint | yes | | FK → payments.id self |

Relationships:

- belongs to `invoices`
- optional self-reference for reversals

Constraints:

- CHECK `amount > 0`; CHECK `method`
- FK `invoice_id` RESTRICT; FK `received_by` SET NULL; FK `reversal_of` SET NULL

Indexes:

- PK `id`; IDX `invoice_id`; IDX `paid_on`

Business Rules:

- Payment never exceeds unpaid balance (validated in service).
- Corrections create audited reversals — never hard delete.

---

## Module 11 — Communication

### Table: announcements

Purpose: published messages with audience targeting.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| author_id | bigint | no | | FK → users.id |
| title | varchar(255) | no | | |
| body | text | no | | |
| announcement_type | varchar(30) | yes | `general` | general/academic/administrative/event |
| audience_type | varchar(30) | no | `all` | all/students/lecturers/staff/faculty/department/program/section/course |
| audience_id | bigint | yes | | Target entity id |
| publish_state | varchar(20) | no | `draft` | draft/published/archived |
| published_at | timestamptz | yes | | |

Relationships:

- belongs to `users` (author)
- targets `faculties`/`departments`/`programs`/`sections`/`courses` via `audience_type`+`audience_id`
- may have `files`

Constraints:

- CHECK `announcement_type`, `audience_type`, `publish_state`
- FK `author_id` → `users.id` RESTRICT

Indexes:

- PK `id`; IDX `(audience_type, audience_id)`; IDX `(publish_state, published_at)`

---

### Table: notifications

Purpose: Laravel in-app notifications (queued).

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| notifiable_type | varchar(255) | no | | Morph type |
| notifiable_id | bigint | no | | Morph id |
| type | varchar(255) | no | | Notification class |
| data | jsonb | yes | | Semi-structured payload |
| read_at | timestamptz | yes | | |
| created_at/updated_at | timestamptz | no | ts | |

Relationships:

- morph to `users`

Indexes:

- PK `id`; IDX `(notifiable_type, notifiable_id)`; IDX `read_at`

---

### Table: notification_preferences

Purpose: per-user notification channel/target preferences.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| user_id | bigint | no | UQ | FK → users.id |
| notify_by_email | boolean | no | true | |
| notify_by_telegram | boolean | no | true | |
| telegram_chat_id | varchar(100) | yes | | Resolved chat id |

Constraints:

- UQ `user_id`
- FK `user_id` → `users.id` RESTRICT

Indexes:

- PK `id`; UQ `user_id`

Business Rules:

- Telegram bot token lives in env/GitHub secrets — never in DB.

---

## Module 12 — Internship

### Table: internship_companies

Purpose: lean company records.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| name | varchar(255) | no | | |
| industry | varchar(100) | yes | | |
| contact_name | varchar(150) | yes | | |
| contact_email | varchar(150) | yes | | |
| contact_phone | varchar(50) | yes | | |
| address | text | yes | | |
| website | varchar(255) | yes | | |
| is_active | boolean | no | true | |

Relationships:

- has many `internships`

Constraints:

- UQ `name`

Indexes:

- PK `id`; UQ `name`

---

### Table: internships

Purpose: a student's internship application + record lifecycle.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| student_id | bigint | no | | FK → students.id |
| company_id | bigint | no | | FK → internship_companies.id |
| position_title | varchar(255) | no | | |
| description | text | yes | | |
| start_date | date | yes | | |
| end_date | date | yes | | |
| supervisor_name | varchar(150) | yes | | |
| supervisor_email | varchar(150) | yes | | |
| supervisor_phone | varchar(50) | yes | | |
| status | varchar(30) | no | `draft` | draft/submitted/under_review/approved/rejected/in_progress/completed/cancelled |
| submitted_at | timestamptz | yes | | |
| reviewed_by | bigint | yes | | FK → users.id |
| reviewed_at | timestamptz | yes | | |
| notes | text | yes | | |

Relationships:

- belongs to `students`, `internship_companies`
- has many `internship_reports`, `internship_evaluations`
- may request `documents` (internship letter)

Constraints:

- CHECK `status`; CHECK `end_date IS NULL OR end_date >= start_date`
- FK `student_id` RESTRICT; FK `company_id` RESTRICT; FK `reviewed_by` SET NULL

Indexes:

- PK `id`; IDX `(student_id, status)`; IDX `company_id`

Business Rules:

- Partial unique index: one open application (`submitted`/`under_review`/`approved`/`in_progress`) per student.
- Staff approve/reject; students cannot self-approve.

---

### Table: internship_reports

Purpose: student-submitted internship reports.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| internship_id | bigint | no | | FK → internships.id |
| report_type | varchar(30) | no | `progress` | initial/progress/final |
| title | varchar(255) | no | | |
| summary | text | yes | | |
| submitted_at | timestamptz | no | now | |
| status | varchar(20) | no | `submitted` | draft/submitted/reviewed |
| reviewer_comment | text | yes | | |

Relationships:

- belongs to `internships`
- may have `files`

Constraints:

- CHECK `report_type`, `status`
- FK `internship_id` RESTRICT

Indexes:

- PK `id`; IDX `internship_id`

---

### Table: internship_evaluations

Purpose: supervisor/faculty evaluation of an internship.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| internship_id | bigint | no | | FK → internships.id |
| evaluator_type | varchar(30) | no | | supervisor/faculty |
| evaluator_name | varchar(150) | yes | | |
| score | numeric(5,2) | yes | | CHECK 0–100 |
| rating | varchar(20) | yes | | |
| comments | text | yes | | |
| evaluated_at | timestamptz | yes | | |
| submitted_by | bigint | yes | | FK → users.id |

Relationships:

- belongs to `internships`

Constraints:

- CHECK `evaluator_type`; CHECK `score BETWEEN 0 AND 100`
- FK `internship_id` RESTRICT; FK `submitted_by` SET NULL

Indexes:

- PK `id`; IDX `internship_id`

---

## Module 14 — Audit & Security

### Table: audit_logs

Purpose: append-only log of sensitive actions.

Columns:

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint | no | PK | |
| actor_id | bigint | yes | | FK → users.id |
| action | varchar(100) | no | | e.g. `grade.finalized` |
| description | text | yes | | |
| auditable_type | varchar(255) | yes | | Morph type |
| auditable_id | bigint | yes | | Morph id |
| before_values | jsonb | yes | | Before snapshot |
| after_values | jsonb | yes | | After snapshot |
| ip_address | varchar(45) | yes | | |
| user_agent | text | yes | | |
| created_at | timestamptz | no | | append-only, no `updated_at` |

Relationships:

- belongs to `users` (actor, nullable)
- morph to any target record

Constraints:

- FK `actor_id` → `users.id` SET NULL

Indexes:

- PK `id`; IDX `(auditable_type, auditable_id)`; IDX `actor_id`; IDX `action`; IDX `created_at`

Business Rules:

- **Append-only** — no update/delete in application code.
- Never log passwords, tokens, or full bodies with secrets (snapshots filtered).
- JSON snapshots store only diff-relevant attributes.

---

## Framework tables (Laravel/Sanctum — already migrated)

Refer to the scaffold migrations for exact columns:

- `users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`,
  `jobs`, `job_batches`, `failed_jobs`, `personal_access_tokens`.

Only `users` is customised further (add `role_id`, contact, `is_active`,
`last_login_at`, soft deletes).