# EduCore Database Relationships

Complete relationship catalog. Format:

```
| From | Relationship | To | Description |
```

## 1. Academic hierarchy (canonical — `skills/academic-domain`)

| From | Relationship | To | Description |
|---|---|---|---|
| `universities` | 1 → N | `faculties` | A university contains many faculties |
| `faculties` | 1 → N | `departments` | A faculty has many departments |
| `departments` | 1 → N | `programs` | A department offers many programs |
| `programs` | N ↔ N | `courses` | A program includes many courses; a course can serve many programs (via `course_programs`) |
| `courses` | N ↔ N (self) | `courses` | Prerequisite edges via `course_prerequisites` (course → prerequisite_course) |
| `departments` | 1 → N | `courses` | A course is owned by one department |
| `academic_years` | 1 → N | `semesters` | An academic year has semesters |
| `courses` | 1 → N | `course_offerings` | A course is offered per semester |
| `semesters` | 1 → N | `course_offerings` | A semester offers many courses |
| `course_offerings` | 1 → N | `sections` | An offering is delivered as sections (A, B, …) |
| `sections` | N ↔ N | `lecturers` | Teaching assignments via `section_lecturers` |
| `rooms` | 1 → N | `schedule_entries` | A room hosts many weekly slots |
| `sections` | 1 → N | `schedule_entries` | A section meets in recurring weekly slots |

## 2. People

| From | Relationship | To | Description |
|---|---|---|---|
| `users` | 1 → 1 | `students` | A student extends exactly one user account |
| `users` | 1 → 1 | `lecturers` | A lecturer extends exactly one user account |
| `users` | N → 1 | `roles` | Each user has exactly one role (MVP; see `decisions.md`) |
| `roles` | N ↔ N | `permissions` | RBAC via `permission_role` |
| `lecturers` | N → 1 | `departments` | A lecturer's primary department |
| `students` | N ↔ N | `programs` | Program history via `student_programs` (effective dates) |

## 3. Enrollment (the center of academic history)

| From | Relationship | To | Description |
|---|---|---|---|
| `students` | 1 → N | `enrollments` | A student has many section registrations |
| `sections` | 1 → N | `enrollments` | A section has many enrolled students |
| `academic_years` | 1 → N | `enrollments` | Snapshot: which year the enrollment belongs to |
| `semesters` | 1 → N | `enrollments` | Snapshot: which semester the enrollment belongs to |

Enrollment is the **single anchor** for all per-student academic activity:

```
enrollments
   ├── → attendance_records
   ├── → assignment_submissions
   ├── → exam_results
   └── → grades (1–1 final grade)
```

## 4. Attendance

| From | Relationship | To | Description |
|---|---|---|---|
| `sections` | 1 → N | `attendance_sessions` | A section's dated class sessions |
| `attendance_sessions` | 1 → N | `attendance_records` | Raw per-student records |
| `enrollments` | 1 → N | `attendance_records` | Only enrolled students have records |

## 5. Assessment

| From | Relationship | To | Description |
|---|---|---|---|
| `sections` | 1 → N | `assignments` | A section's tasks |
| `assignments` | 1 → N | `assignment_submissions` | A task's submissions |
| `enrollments` | 1 → N | `assignment_submissions` | Each submission belongs to an enrolled student |

## 6. Examination & Grading

| From | Relationship | To | Description |
|---|---|---|---|
| `sections` | 1 → N | `exams` | A section's exams |
| `exams` | 1 → N | `exam_results` | Student scores per exam |
| `enrollments` | 1 → N | `exam_results` | Results keyed to enrollment |
| `enrollments` | 1 → 1 | `grades` | One final grade per enrollment |
| `courses` | 1 → 1 | `course_grading_configs` | Default weightings per course |
| `students` | 1 → N | `gpa_records` | GPA snapshot per semester/year |
| `grades` | → | `grading_scales` | Letter/points look up to the institutional scale |

GPA path (derived, traceable):

```
grades → enrollments → courses.credits
GPA = Σ(grade_points × credits) / Σ(credits)
```

## 7. Documents & File Storage

| From | Relationship | To | Description |
|---|---|---|---|
| `students` | 1 → N | `document_requests` | A student requests documents |
| `document_types` | 1 → N | `document_requests` | A request is for one document type |
| `document_requests` | 1 → 1 | `documents` | One generated document per request |
| `documents` | 1 → N | `document_verifications` | Append-only verification attempts |
| `files` | polymorphic → | assignments, submissions, reports, announcements | `fileable_type` + `fileable_id` |

## 8. Finance

| From | Relationship | To | Description |
|---|---|---|---|
| `students` | 1 → N | `invoices` | A student's charges |
| `invoices` | 1 → N | `invoice_items` | Line items |
| `invoices` | 1 → N | `payments` | Recorded payments |

## 9. Communication

| From | Relationship | To | Description |
|---|---|---|---|
| `users` | 1 → N | `announcements` | Author |
| `announcements` | → | `faculties`/`departments`/`programs`/`sections`/`courses` | Target via `audience_type` + `audience_id` |
| `announcements` | → | `files` | Attachments |
| `users` | 1 → N | `notifications` | Laravel `notifiable` morph |
| `users` | 1 → 1 | `notification_preferences` | Channel preferences |

## 10. Internship

| From | Relationship | To | Description |
|---|---|---|---|
| `students` | 1 → N | `internships` | A student's internship lifecycle |
| `internship_companies` | 1 → N | `internships` | A company hosts many internships |
| `internships` | 1 → N | `internship_reports` | Student-submitted reports |
| `internships` | 1 → N | `internship_evaluations` | Supervisor/faculty evaluations |

## 11. Audit & Security

| From | Relationship | To | Description |
|---|---|---|---|
| `users` | 1 → N | `audit_logs` | Actor (nullable; `ON DELETE SET NULL`) |
| `audit_logs` | polymorphic → | any record | `auditable_type` + `auditable_id` |

## Summary counts by cardinality

- 1–1: `users`↔`students`, `users`↔`lecturers`, `documents`↔`document_requests`,
  `enrollments`↔`grades`, `courses`↔`course_grading_configs`,
  `users`↔`notification_preferences`
- N–N (pivots): `courses`↔`programs`, `courses`↔`courses` (prerequisites),
  `sections`↔`lecturers`, `roles`↔`permissions`, `students`↔`programs` (historical)
- Polymorphic: `files`, `notifications`, `audit_logs`
- Self-referencing: `course_prerequisites`