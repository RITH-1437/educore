---
name: educore-attendance
description: EduCore attendance - recording attendance per class, statuses, derived percentages, and API/UI/authorization rules. Consult for any attendance work.
---

# EduCore — Attendance

## 1. Purpose

Record student presence per class session within a section and derive
attendance percentages.

## 2. Main entities

- `attendance` — one row per student per class session
  (enrollment + date/session).
- Statuses: `present`, `absent`, `late`, `excused`.
- A class "session" = a dated meeting (tied to section schedule or an explicit
  attendance-taking session).

## 3. Relationships

- Attendance → Enrollment (N–1) → Student / Section.
- Attendance → Section (via enrollment) → Schedule.

## 4. Business rules

- Attendance exists only for enrolled students in the section.
- Lecturer records status per student for a session; default `present` if
  unattended? (choose explicitly: default absent or unmarked).
- **Percentage is derived** (sum of present+late / sessions attended), never
  stored hand-entered; late counts as present or half (decide once, document).
- Only the section's assigned lecturer records attendance for that section.
- Attendance feeds grading only if the course weights it (see
  `grading-gpa` — attendance weight).

## 5. API responsibilities

- `POST /api/sections/{section}/attendance` (bulk: date + [student → status]).
- `GET /api/sections/{section}/attendance?date=` or list by enrollment.
- `GET /api/students/{student}/attendance` (own summary by course).
- `GET /api/sections/{section}/attendance/summary` (percentages + counts).

## 6. Backend responsibilities

- `AttendanceService` validates students belong to the section and writes rows
  transactionally (bulk upsert). Use `upsert`/`updateOrCreate` keyed on
  `[enrollment_id, session_date]`.
- Derive summary via aggregation (withCount), not per-row PHP loops.
- Guard against recording attendance for classes not yet held (optional, if a
  date policy is set).

## 7. Frontend responsibilities

- Attendance page per section: date picker, student list with status toggle.
- Student view: attendance % per course (from summary endpoint).
- Present/absent/late/excused status badges.

## 8. Authorization rules

- Lecturer: record attendance only for their assigned section.
- Student: view own attendance only.
- Admin: view all, may correct with audit (see `audit-logging`).

## 9. Validation rules

- Enrollment belongs to section; valid date; valid status values; no duplicate
  (student, date) per section.

## 10. Important edge cases

- Editing a past session's attendance (allowed? track audit).
- Bulk-save partial marks (only listed students updated, others untouched).
- Class cancelled — mark whole session excused or exclude from denominator.
- Student transfers sections mid-week — attendance stays per enrollment.

## 11. Testing requirements

- Bulk record success + duplicate prevention.
- Only enrolled students accepted.
- Summary percentages correct (present/late/excused/absent).
- Authorization: lecturer only for own sections; student only own data.

## 12. Must NOT

- Must NOT store percentages as editable fields (derive them).
- Must NOT let students mark their own attendance.
- Must NOT record attendance for non-enrolled students.
- Must NOT hand-maintain per-student loose dates without the section anchor.

## Cross-references

- `skills/academic-domain/SKILL.md`, `skills/enrollment/SKILL.md`,
  `skills/timetable/SKILL.md`, `skills/grading-gpa/SKILL.md`,
  `skills/authorization/SKILL.md`.

## Agent behavior (mandatory)

1. Inspect existing implementation first. 2. Follow established conventions.
3. Do not rewrite working code. 4. No tech outside the stack. 5. No unnecessary
abstractions. 6. No duplicate business logic. 7. No invented relationships.
8. No bypassing authorization. 9. No hardcoded secrets. 10. No unrelated
module changes. 11. Run tests. 12. Explain architectural decisions.