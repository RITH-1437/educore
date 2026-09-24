---
name: educore-assignments
description: EduCore assignments - creating assignments, deadlines, submissions, grading, file uploads. Consult for any assignment work.
---

# EduCore — Assignments

## 1. Purpose

Lecturers publish assignments in a section; students submit work; lecturers
grade submissions (results feed into the grade components in
`skills/grading-gpa/SKILL.md`).

## 2. Main entities

- `assignment` — belongs to a section (title, description, due date, points,
  file attach optional).
- `assignment_submission` — one per student per assignment (content and/or
  file, submitted_at, grade, feedback).

## 3. Relationships

- Assignment → Section (N–1) → Course Offering.
- Assignment → Submission (1–N) → Enrollment (student).
- Submission is keyed to the enrollment, so the same student belongs to the
  section (see `academic-domain`).

## 4. Business rules

- One submission per student per assignment (updateable up to the deadline;
  `updateOrCreate` keyed on `[assignment_id, enrollment_id]`).
- Late submission handling policy is explicit (allow with flag vs block).
- Only the section's lecturer creates/grades assignments.
- Assignment points map into the course grading weights (see `grading-gpa`).
- Files stored in MinIO (see `file-storage`); validated type/size.

## 5. API responsibilities

- `GET/POST/PATCH/DELETE /api/sections/{section}/assignments`.
- `GET /api/assignments/{assignment}` (with submissions for lecturer; own
  submission for each student).
- `POST /api/assignments/{assignment}/submissions` (student upload/update).
- `POST /api/submissions/{submission}/grade` (lecturer grade + feedback).

## 6. Backend responsibilities

- `AssignmentService`: create/update with validation (due date sane, points >
  0, section semester open).
- Submission service: enforce deadline, one-per-student, store file to MinIO,
  transaction.
- Grading service updates submission grade + marks grade pipeline for
  recompute opinions (aggregate totals computed with grades, see `grading-gpa`).

## 7. Frontend responsibilities

- Assignment list per section (lecturer: create/edit/delete; student: submit).
- Submission view with file preview/download + grade/feedback display.
- Deadline-aware UI (late badge, closed-after-deadline states).

## 8. Authorization rules

- Lecturer: manage assignments + submissions of their section.
- Student: view assignments; submit only own (enrollment) submission.
- Others: read-only where visible (e.g. view-only class materials).

## 9. Validation rules

- Title required; due date in the future (or semester); points > 0; section
  exists; submission file validated (type/size); no duplicate submission key.

## 10. Important edge cases

- Submission at deadline exactly — use server time, not client.
- Student tries to submit for another student.
- Lecturer edits an assignment after submissions exist.
- Late submission toggle changes behavior predictably; audited.

## 11. Testing requirements

- CRUD; one-submission rule; deadline enforcement; file validation; grading
  updates; authorization (student only own, lecturer own section).

## 12. Must NOT

- Must NOT allow multiple submissions from the same student key.
- Must NOT store submission files on ephemeral disk.
- Must NOT let a student set the grade/feedback on their own submission.
- Must NOT invent a "classwork" tier separate from assignments.

## Cross-references

- `skills/file-storage/SKILL.md`, `skills/grading-gpa/SKILL.md`,
  `skills/timetable/SKILL.md`, `skills/authorization/SKILL.md`.

## Agent behavior (mandatory)

1. Inspect existing implementation first. 2. Follow established conventions.
3. Do not rewrite working code. 4. No tech outside the stack. 5. No unnecessary
abstractions. 6. No duplicate business logic. 7. No invented relationships.
8. No bypassing authorization. 9. No hardcoded secrets. 10. No unrelated
module changes. 11. Run tests. 12. Explain architectural decisions.