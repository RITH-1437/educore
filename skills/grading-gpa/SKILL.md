---
name: educore-grading-gpa
description: EduCore grading and GPA - grade components, letter grades, grade points, semester/cumulative GPA, transcripts. Consult for any grading or GPA work.
---

# EduCore — Grading & GPA

## 1. Purpose

Convert course results into grades and grade points, compute GPA, and produce
transcripts.

## 2. Main entities

- `grade` — final grade for an enrollment (letter, points, maybe numeric
  score), likely via a `grades` table keyed `[enrollment_id]` (unique).
- `grading_scale` — configurable letter ↔ points mapping (e.g. A=4.0,
  B+=3.5 …) — see `skills/database` (config table or config file; prefer DB
  config for university configurability).
- `course_grading_config` — weights (midterm %, final %, assignments %,
  attendance %) per course/offering.
- GPA computed (not stored as source of truth): a semester/academic-year GPA
  and cumulative GPA per student (can be cached on student, recomputed on
  grade change — see Business rules).

## 3. Relationships

- Grade → Enrollment (1–1).
- Grade → Course (via enrollment → section → offering → course).
- Course → Grading config (1–1 or per-offering).
- Grade components → Exams, Assignments, Attendance (aggregates).

## 4. Business rules

- **Weights sum to 100%**; validate on config save.
- Letter/point mapping is **configurable** (institution-defined). Default:
  A=4.0, B+=3.5, B=3.0, C+=2.5, C=2.0, D=1.0, F=0.0 — subject to change only
  through configuration, not hard-coded everywhere.
- Numeric sub-cores: exams/assignments/attendance weighted per config.
- A grade is final only after lecturer submits AND (per workflow) admin
  approves/finalize.
- **GPA is credit-weighted**: Σ(points × credits) / Σ(credits) across graded
  enrollments (exclude in-progress/unapproved).
- If a course's credits or a grade changes, GPA must be **recomputed**
  (service method; triggered by event or after-save hook) — never stored stale.
- One final grade per enrollment (unique `[enrollment_id]`).

## 5. API responsibilities

- `GET/POST/PATCH /api/sections/{section}/grades` (lecturer submits).
- `POST /api/sections/{section}/grades/finalize` (admin approval,
  optional workflow).
- `GET /api/students/{student}/gpa` (semester + cumulative) and
  `GET /api/students/{student}/transcript` (full record — see `documents` for
  the downloadable transcript).
- `GET/POST /api/grading-scales` and `.../grading-configs` (admin).

## 6. Backend responsibilities

- `GradingService`: compute component totals, map to letter/points, persist
  grade, then `recalculateGpa(student)` in the same transaction (see
  `skills/laravel` — atomicity).
- GPA methods: read-only computations used by student dashboard, analytics,
  documents.
- Audit grade changes (who/when/from/to) — see `audit-logging`.

## 7. Frontend responsibilities

- Grades entry grid (students × scores/components) with live totals.
- Student dashboard GPA card (semester + cumulative) and per-course grades.
- Admin grading scale/config editor.

## 8. Authorization rules

- Lecturer: submit/compute grades for their section.
- University Admin: finalize/approve and manage scale/config.
- Student: view own grades/GPA/transcript only.

## 9. Validation rules

- Numeric components within range; weights sum to 100%; mapping points sane;
  one grade per enrollment; only enrolled students.

## 10. Important edge cases

- Mid-semester shows interim GPA (in-progress courses excluded or clearly
  marked).
- Grade change after finalization — requires permission + audit trail.
- Failed course (F) counts toward GPA; retake replaces the grade (policy:
  keep both or replace — be explicit and document).
- Zero credits due to data error — guard division by zero.
- Multiple graders / late submissions — sequential, last-saved wins (audited).

## 11. Testing requirements

- Weighted GPA correctness (hand-computed fixtures).
- Weight validation; unique grade per enrollment.
- Recomputation on grade change/credit change.
- Division-by-zero guard; F handling; retake policy.
- Authorization (lecturer-own-section, student-own-only).

## 12. Must NOT

- Must NOT hard-code the grade scale in multiple places.
- Must NOT store GPA that is not recomputed after underlying changes.
- Must NOT let students write/change grades.
- Must NOT compute GPA in the frontend as truth.

## Cross-references

- `skills/academic-domain/SKILL.md`, `skills/examinations/SKILL.md`,
  `skills/assignments/SKILL.md`, `skills/attendance/SKILL.md`,
  `skills/documents/SKILL.md` (transcript), `skills/analytics-reporting/SKILL.md`.

## Agent behavior (mandatory)

1. Inspect existing implementation first. 2. Follow established conventions.
3. Do not rewrite working code. 4. No tech outside the stack. 5. No unnecessary
abstractions. 6. No duplicate business logic. 7. No invented relationships.
8. No bypassing authorization. 9. No hardcoded secrets. 10. No unrelated
module changes. 11. Run tests. 12. Explain architectural decisions.