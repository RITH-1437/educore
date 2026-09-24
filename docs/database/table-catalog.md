# EduCore Table Catalog

Every table in the EduCore schema, its purpose, module, primary entity, and its
most important relationships. 49 business tables + 8 framework tables.

## Domain tables

| # | Table | Purpose | Module | Primary Entity | Important Relationships |
|---|---|---|---|---|---|
| 1 | `users` | Accounts with login/credentials & RBAC role | Identity & Access | User | → `roles` (N–1), → `students` (1–1), → `lecturers` (1–1), → `audit_logs` (1–N as actor) |
| 2 | `roles` | The 5 system roles (super-admin, admin, registrar, lecturer, student) | Identity & Access | Role | ← `users` (1–N), → `permissions` (N–N via `permission_role`) |
| 3 | `permissions` | Granular permission catalog | Identity & Access | Permission | → `roles` (N–N via `permission_role`) |
| 4 | `permission_role` | Pivot linking roles ↔ permissions | Identity & Access | Pivot | `role_id` → `roles`, `permission_id` → `permissions` |
| 5 | `settings` | System/institution key-value config | Identity & Access | Setting | — |
| 6 | `universities` | The institution itself (system-level root) | University Structure | University | → `faculties` (1–N) |
| 7 | `faculties` | Broad academic division | University Structure | Faculty | → `universities`, ← `departments` (1–N) |
| 8 | `departments` | Sub-division of a faculty | University Structure | Department | → `faculties`, ← `programs` (1–N), ← `courses` (1–N), ← `lecturers` (1–N) |
| 9 | `programs` | A degree track within a department | University Structure | Program | → `departments`, → `courses` (N–N via `course_programs`), ← `students` (N–N via `student_programs`) |
| 10 | `academic_years` | e.g. 2026–2027 | Academic Management | Academic Year | ← `semesters` (1–N), ← `enrollments` snapshot |
| 11 | `semesters` | Term within an academic year | Academic Management | Semester | → `academic_years`, ← `course_offerings` (1–N), ← `enrollments` snapshot |
| 12 | `courses` | Stable curriculum unit (code, name, credits) | Academic Management | Course | → `departments`, → `programs` (N–N via `course_programs`), self-referencing via `course_prerequisites` |
| 13 | `course_programs` | Pivot course ↔ program | Academic Management | Pivot | `course_id`, `program_id` |
| 14 | `course_prerequisites` | Prerequisite edges between courses | Academic Management | Prerequisite | `course_id` → `courses`, `prerequisite_course_id` → `courses` (self) |
| 15 | `course_offerings` | A course taught in one semester | Academic Management | Course Offering | → `courses`, → `semesters`, ← `sections` (1–N) |
| 16 | `sections` | A concrete class instance of an offering | Academic Management | Section | → `course_offerings`, → `lecturers` (N–N via `section_lecturers`), ← `enrollments` (1–N), ← `schedule_entries` (1–N), ← `assignments`, ← `exams`, ← `attendance_sessions` |
| 17 | `rooms` | Physical room used by schedules | Academic Management | Room | → `schedule_entries` (1–N) |
| 18 | `schedule_entries` | Recurring weekly time slot for a section | Academic Management | Schedule Entry | → `sections`, → `rooms` |
| 19 | `section_lecturers` | Pivot section ↔ lecturer (teaching assignment) | Academic Management | Pivot | `section_id` → `sections`, `lecturer_id` → `lecturers` |
| 20 | `students` | Student profile (extends a user) | People | Student | → `users` (1–1), → `programs` (N–N via `student_programs`), ← `enrollments`, ← `document_requests`, ← `invoices`, ← `internships` |
| 21 | `student_programs` | Student's program history (changes, effective dates) | People | Student-Program | `student_id` → `students`, `program_id` → `programs` |
| 22 | `lecturers` | Lecturer profile (extends a user) | People | Lecturer | → `users` (1–1), → `departments`, → `sections` (N–N via `section_lecturers`) |
| 23 | `enrollments` | A student's registration in a section for a semester | Enrollment | Enrollment | → `students`, → `sections`, → `academic_years`, → `semesters`, ← `attendance_records`, ← `assignment_submissions`, ← `exam_results`, ← `grades` (1–1) |
| 24 | `attendance_sessions` | A dated class session in a section | Attendance | Attendance Session | → `sections`, → `recorded_by` (users), ← `attendance_records` |
| 25 | `attendance_records` | Raw presence record per student per session | Attendance | Attendance Record | → `attendance_sessions`, → `enrollments` |
| 26 | `assignments` | Task in a section with deadline & grading config | Assessment | Assignment | → `sections`, ← `assignment_submissions` |
| 27 | `assignment_submissions` | A student's submission + score/feedback | Assessment | Submission | → `assignments`, → `enrollments` |
| 28 | `exams` | Assessment (midterm/final/quiz/practical) in a section | Examination & Grading | Exam | → `sections`, ← `exam_results` |
| 29 | `exam_results` | Student score for one exam | Examination & Grading | Exam Result | → `exams`, → `enrollments` |
| 30 | `grades` | Final grade for an enrollment | Examination & Grading | Grade | → `enrollments` (1–1), → `grading_scales` (through letter/points) |
| 31 | `grading_scales` | Configurable letter ↔ grade-point mapping | Examination & Grading | Grading Scale | ← `grades` (referenced semantically) |
| 32 | `course_grading_configs` | Weighting (attendance/assignment/midterm/final) per course | Examination & Grading | Grading Config | → `courses` (1–1) |
| 33 | `gpa_records` | Computed semester/cumulative GPA snapshot per student | Examination & Grading | GPA | → `students`, → `academic_years`, → `semesters` |
| 34 | `document_types` | Configurable list of official document types | Documents & File Storage | Document Type | ← `document_requests` (1–N) |
| 35 | `document_requests` | A student's request for an official document | Documents & File Storage | Document Request | → `students`, → `document_types`, ← `documents` (1–1) |
| 36 | `documents` | Generated file + verification token | Documents & File Storage | Document | → `document_requests` (1–1) |
| 37 | `document_verifications` | Append-only public verification attempts | Documents & File Storage | Verification | → `documents` |
| 38 | `files` | MinIO file metadata (polymorphic uploads) | Documents & File Storage | File | `fileable` polymorphic → assignments, submissions, reports, announcements |
| 39 | `invoices` | Record of institutional charges to a student | Finance | Invoice | → `students`, ← `invoice_items` (1–N), ← `payments` (1–N) |
| 40 | `invoice_items` | Line items on an invoice | Finance | Invoice Item | → `invoices` |
| 41 | `payments` | Records of received payments | Finance | Payment | → `invoices`, → `received_by` (users) |
| 42 | `announcements` | Published messages with audience targeting | Communication | Announcement | → `author_id` (users), target via `audience_type`/`audience_id`, attachments via `files` |
| 43 | `notifications` | In-app queued notifications (Laravel) | Communication | Notification | `notifiable` polymorphic → users |
| 44 | `notification_preferences` | Per-user channel/target preferences | Communication | Preference | → `users` (1–1) |
| 45 | `internship_companies` | Lean company records | Internship | Company | ← `internships` (1–N) |
| 46 | `internships` | Student application + internship record lifecycle | Internship | Internship | → `students`, → `internship_companies`, ← `internship_reports`, ← `internship_evaluations` |
| 47 | `internship_reports` | Student-submitted internship reports | Internship | Report | → `internships`, file via `files` |
| 48 | `internship_evaluations` | Supervisor/faculty evaluations | Internship | Evaluation | → `internships` |
| 49 | `audit_logs` | Append-only record of sensitive actions | Audit & Security | Audit Log | → `actor_id` (users, nullable), `auditable` polymorphic |

## Framework tables (already migrated by Laravel 12 scaffold)

| Table | Purpose |
|---|---|
| `password_reset_tokens` | Password reset tokens |
| `sessions` | Web session store (`SESSION_DRIVER=database`) |
| `cache` | Cache store |
| `cache_locks` | Cache locks |
| `jobs` | Queue jobs (`QUEUE_CONNECTION=database` until switched to Redis) |
| `job_batches` | Queue batch metadata |
| `failed_jobs` | Failed queue jobs |
| `personal_access_tokens` | Laravel Sanctum API tokens |

> Note: `users` is both a framework table and a business table (column 1 above).
> `jobs`/`cache`/`sessions` remain in use by defaults; the queue/cache/session
> drivers switch to Redis in Docker (see README and `skills/docker`).

## Counts

- Business tables: **49**
- Framework tables: **8**
- Total physical tables: **57**
- Pivot tables: 4 (`permission_role`, `course_programs`, `course_prerequisites`,
  `section_lecturers`)
- Polymorphic tables: 3 (`files`, `notifications`, `audit_logs`)