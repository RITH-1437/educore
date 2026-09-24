---
name: educore-examinations
description: EduCore examinations - midterm/final/quiz exams, schedules, results, weights. Consult for any exam work.
---

# EduCore — Examinations

## 1. Purpose

Manage assessments (midterm, final, quiz, practical) within a section, their
schedules, weights, and student results.

## 2. Main entities

- `exam` — belongs to a section (type: midterm/final/quiz, title, date/time,
  weight, max points).
- `exam_result` — student result for an exam (score, optional remarks), keyed
  to enrollment.

## 3. Relationships

- Exam → Section (N–1) → Offering.
- Exam → Result (1–N) → Enrollment (student).
- Schedule/room for exams is optional (can reuse timetable or a simple
  date/time/location field). Keep it simple unless the institution requires a
  full exam timetable.

## 4. Business rules

- Weights across assessment types sum to 100% for a course grading config
  (midterm/final/assignments/attendance) — see `grading-gpa`.
- One result per student per exam (`[exam_id, enrollment_id]` unique).
- Only section lecturer creates exams and enters/approves results.
- Exam schedule conflicts: no student (shared section enrollments) has two
  exams at the same time — check when exams share a time, similar to timetable.
- Results are numeric and validated against max points.

## 5. API responsibilities

- `GET/POST/PATCH/DELETE /api/sections/{section}/exams`.
- `GET /api/exams/{exam}` with results; `POST /api/exams/{exam}/results`
  (bulk entry or per-student).
- `PATCH /api/exam-results/{result}` (correction, audited).

## 6. Backend responsibilities

- `ExamService` overs CRUD + validation (weights, dates, conflicts).
- Result entry transaction + `updateOrCreate` keyed `[exam_id, enrollment_id]`.
- Validate student enrolled; numeric within [0, max].
- Any change that alters the course grade recomputes outputs (see `grading-gpa`).

## 7. Frontend responsibilities

- Exam list per section (lecturer manage; student view scheduled exams).
- Results entry grid (student rows + score inputs) with validation.
- Student exam schedule view.

## 8. Authorization rules

- Lecturer: manage exams/results for their section.
- Student: view own results + schedule.
- Admin: view all; corrections audited.

## 9. Validation rules

- Type within set; weight ≥ 0; max points > 0; dates valid; no duplicate
  `[exam_id, enrollment_id]`; only enrolled students.

## 10. Important edge cases

- Editing weights after results entered (recompute).
- Entering a score > max — reject.
- Uploading spreadsheet-style bulk results (optional; keep CSV/JSON simple).
- Retakes/re-sits for failed exams — record as a new exam or a corrected result.

## 11. Testing requirements

- CRUD; result constraints (unique per student, numeric range).
- Weights consistency check; conflict detection.
- Only enrolled students; authorization.

## 12. Must NOT

- Must NOT merge exam into a generic "assessment" that loses type semantics.
- Must NOT store results for unenrolled students.
- Must NOT let students edit their results.
- Must NOT duplicate grade-calculation logic that belongs to `grading-gpa`.

## Cross-references

- `skills/grading-gpa/SKILL.md`, `skills/enrollment/SKILL.md`,
  `skills/timetable/SKILL.md`, `skills/authorization/SKILL.md`.

## Agent behavior (mandatory)

1. Inspect existing implementation first. 2. Follow established conventions.
3. Do not rewrite working code. 4. No tech outside the stack. 5. No unnecessary
abstractions. 6. No duplicate business logic. 7. No invented relationships.
8. No bypassing authorization. 9. No hardcoded secrets. 10. No unrelated
module changes. 11. Run tests. 12. Explain architectural decisions.