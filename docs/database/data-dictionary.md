# EduCore Data Dictionary

Business meaning of the important fields across the schema. Field names are
snake_case; types follow `database-conventions.md`.

## Universal patterns

| Field | Type | Meaning |
|---|---|---|
| `id` | `bigint` | Internal primary key (never exposed in QR codes or public URLs) |
| `created_at` / `updated_at` | `timestamptz` | Row lifecycle |
| `deleted_at` | `timestamptz` | Soft delete (only where designed) |
| `*_by` (e.g. `processed_by`, `recorded_by`, `generated_by`, `received_by`, `author_id`, `actor_id`) | `bigint` → `users.id` | Who performed the action; nullable for system/legacy |

## Identifiers

| Field | Business meaning | Uniqueness |
|---|---|---|
| `users.email` | Login identity | UNIQUE |
| `students.student_number` | The university's official student ID | UNIQUE |
| `lecturers.staff_number` | Official staff ID | UNIQUE |
| `courses.code` | Course code (e.g. `CS101`) | UNIQUE |
| `universities.code` | Institution code | UNIQUE |
| `faculties.code` | Faculty code | UNIQUE |
| `departments.code` | Department code | UNIQUE |
| `programs.code` | Program code | UNIQUE |
| `academic_years.code` | e.g. `2026-2027` | UNIQUE |
| `rooms.code` | Room code | UNIQUE |
| `invoices.invoice_number` | Human-facing invoice number | UNIQUE |
| `documents.verification_token` | **Public** QR verification token — random, never the `id` | UNIQUE |
| `personal_access_tokens.token` | Sanctum API token (hashed) | UNIQUE |

## Statuses (varchar + CHECK)

### `users.is_active` (boolean)
`true` = account usable; `false` = blocked.

### `students.status`
`active` · `inactive` · `suspended` · `graduated` · `withdrawn`
- **active** = may enroll and attend; **suspended** = blocked from new
  enrollments (existing kept); **graduated/withdrawn/inactive** = no longer a
  current student.

### `student_programs.status`
`active` · `completed` · `withdrawn` · `transferred`
- Exactly **one** `active` row per student (partial unique index).

### `academic_years.status`
`planned` · `active` · `completed`

### `semesters.status`
`planned` · `open` · `closed` · `completed`
- `open` = enrollment window active; enrollment is blocked outside it.

### `courses.status`
`draft` · `active` · `archived`

### `course_offerings.status`
`draft` · `published` · `open` · `closed`

### `sections.status`
`draft` · `open` · `active` · `closed` · `archived`

### `enrollments.status`
`pending` · `confirmed` · `completed` · `dropped` · `withdrawn`
- `pending` = reserved but not confirmed; `confirmed` = seat held; `failed`
  courses are re-enrollable (new row); `completed` = finished semester.

### `attendance_records.status`
`present` · `absent` · `late` · `excused`
- Percentages are **derived** from these raw rows — never stored.

### `assignment_submissions.status`
`submitted` · `late` · `graded` · `returned`

### `documents.status` (generated document)
`valid` · `revoked` · `expired`

### `document_requests.status`
`pending` · `approved` · `rejected` · `generated`
- `rejected` keeps `rejection_reason` for resubmission.

### `invoices.status`
`pending` · `partial` · `paid` · `overdue` · `cancelled`
- `partial`/`overdue` are **recomputed** from payments + due date on each
  payment event, then persisted consistently.

### `internships.status`
`draft` → `submitted` → `under_review` → `approved` | `rejected` → `in_progress`
→ `completed` | `cancelled`
- One open application per student (partial unique index).

### `audit_logs.action`
Free-form snake_case (e.g. `grade.finalized`, `document.approved`).
`audit_logs` is append-only.

## Dates & academic periods

| Field | Meaning |
|---|---|
| `academic_years.start_date` / `end_date` | Year span (CHECK start < end) |
| `semesters.start_date` / `end_date` | Teaching span |
| `semesters.enrollment_start` / `enrollment_end` | Registration window (controls enrollment) |
| `semesters.exam_start` / `exam_end` | Examination window |
| `enrollments.enrolled_at` (`timestamptz`) | When the registration was created |
| `enrollments.dropped_at` (`timestamptz`) | When dropped/withdrawn |
| `assignments.due_at` (`timestamptz`) | Submission deadline |
| `invoices.due_date` (`date`) | Payment due; drives `overdue` |
| `invoices.issued_date` (`date`) | Not the creation timestamp |
| `payments.paid_on` (`date`) | Actual payment date |
| `documents.generated_at` (`timestamptz`) | Generation instant |

## Scores, grades, GPA (numeric discipline)

| Field | Type | Meaning & constraints |
|---|---|---|
| `exam_results.score` | `numeric(6,2)` | 0–max (max on the exam); validated ≤ max by app |
| `grades.total_score` | `numeric(6,2)` | Weighted total per enrollment |
| `grades.letter_grade` | `varchar(5)` | e.g. `A`, `B+`, `F` — from `grading_scales` |
| `grades.grade_point` | `numeric(3,2)` | Points for the letter (4.0 scale) |
| `grading_scales.min_%` / `max_%` | `numeric(5,2)` | Percentage band; CHECK min ≤ max |
| `grading_scales.grade_point` | `numeric(3,2)` | Points for that band |
| `course_grading_configs.*_weight` | `numeric(5,2)` | Weights sum to 100 (CHECK) |
| `gpa_records.gpa_value` | `numeric(4,3)` | e.g. `3.750` — **computed**, cached snapshot |
| `gpa_records.cumulative` | `boolean` | `false` = semester GPA, `true` = cumulative |

GPA is always derived: `Σ(points × credits) / Σ(credits)` over graded
enrollments; stored only in `gpa_records` as a recomputed snapshot
(see `decisions.md`).

## Money

| Field | Type | Meaning |
|---|---|---|
| `invoices.subtotal` | `numeric(12,2)` | Sum before discount |
| `invoices.discount` | `numeric(12,2)` | ≥ 0 |
| `invoices.total` | `numeric(12,2)` | subtotal − discount (CHECK ≥ 0) |
| `invoices.currency` | `varchar(10)` | e.g. `USD`, `KHR` |
| `invoice_items.unit_price` / `amount` | `numeric(12,2)` | amount = quantity × unit_price (CHECK) |
| `payments.amount` | `numeric(12,2)` | CHECK > 0; never exceeds unpaid balance |
| `payments.method` | `varchar(20)` | `cash` · `bank_transfer` · `cheque` · `other` (no gateway) |

## Document verification semantics

- `documents.verification_token` is a **high-entropy random token** encoded into
  the QR code. It reveals no internal `id`.
- `document_verifications` stores each check: `result` in `valid` · `invalid` ·
  `revoked` · `expired`, plus `verified_at`, `ip_address`, `user_agent`.

## File storage semantics

- `files.storage_key` = object key in MinIO (e.g.
  `assignments/12/materials/spec.pdf`), **never** a URL.
- `files.bucket` default `educore`; `files.visibility` `private`|`public`.
- Uploader in `files.uploader_id`; the related business record in
  `fileable_type`/`fileable_id`.
- `documents` keeps its own `file_key` + `checksum` because generated documents
  are system facts tied to verification, not user uploads.