---
name: educore-enrollment
description: EduCore enrollment - course registration rules, capacity, prerequisites, credit limits, atomicity, and API/UI/authorization. Consult for any enrollment work.
---

# EduCore — Enrollment (Course Registration)

## 1. Purpose

Manage student registration into sections for a semester: request → validate →
pass/fail → with the flow tied to the academic semester.

## 2. Main entities

- `enrollment` (or `course_enrollment`) — student ↔ section registration.
- Optional `enrollment_status` on it: `pending`, `confirmed`, `dropped`,
  `withdrawn`.

## 3. Relationships

- Enrollment → Student (N–1).
- Enrollment → Section (N–1).
- Enrollment → Attendance (1–N), → AssignmentSubmissions, → ExamResults,
  → Grade (1–1 for final grade).

## 4. Business rules (critical — enforce in service + DB)

- One enrollment per student per section per semester: unique
  `[student_id, section_id]` (or `[student_id, offering_id]`).
- **Prerequisites met**: every prerequisite course for the section's course must
  be satisfied (passed) by the student.
- **Capacity**: section seats available (count enrollments; enforce in a
  transaction with row lock or atomic counter).
- **Credit limit**: sum of credits of concurrently enrolled sections ≤
  configured max.
- **Student status**: only `active` students enroll.
- **Semester open**: enrollment period for the semester must be open.
- Duplicate/conflicting registrations rejected with `409`/`422`.

## 5. API responsibilities

- `POST /api/enrollments` (student self-service or admin-assisted).
- `GET /api/enrollments` (filtered by semester/student/section; paginated).
- `GET /api/enrollments/{enrollment}`.
- `DELETE /api/enrollments/{enrollment}` (drop/withdraw; keep history).
- `GET /api/students/{student}/enrollments` (own list).

## 6. Backend responsibilities

- `EnrollmentService` performs the full validation and **wraps reserve + insert
  in a transaction** (see `skills/laravel/SKILL.md` — atomicity).
- Prevent race conditions on capacity via transaction + pessimistic locking
  (`lockForUpdate`) or an atomic seat counter.
- On drop/withdraw: keep the record (soft) so history/grades survive; block drop
  once grades finalized or attendance recorded (configurable policy — be
  explicit).
- Return friendly, structured errors (prerequisite unmet, full, credit limit,
  period closed).

## 7. Frontend responsibilities

- Registration screen: choose semester, view available sections (with seats
  left + credits), pick, submit; show per-error feedback from the API.
- Enrollment summary on student dashboard (courses, credits total).
- Admin enrollment management list.

## 8. Authorization rules

- Student: enroll themselves only (own ID forced server-side).
- Admin: enroll any student (still runs same validations).
- Nobody may view another student's enrollment unless staff.

## 9. Validation rules

- section exists + belongs to open semester; student `active`.
- prerequisite check; capacity; credit limit; no duplicate.

## 10. Important edge cases

- Two students enrolling the last seat simultaneously — capacity race.
- Student retakes a failed course (allow re-enrollment of a course they failed).
- Dropping the last seat freeing it for others.
- Student suspended mid-semester — block enrollments, keep existing.
- Cross-department courses (electives) — allowed if prerequisite/credit rules OK.

## 11. Testing requirements

- Happy path; capacity (incl. race); prerequisite unmet; credit limit; duplicate;
  period closed; inactive student; drop/withdraw rules; authorization (student
  cannot enroll another student).

## 12. Must NOT

- Must NOT bypass capacity/prerequisite/credit checks in any path.
- Must NOT hard-delete enrollments with grades/attendance.
- Must NOT allow a student to set their own `student_id` in the request.

## Cross-references

- `skills/academic-domain/SKILL.md`, `skills/course-management/SKILL.md`,
  `skills/grading-gpa/SKILL.md`, `skills/testing/SKILL.md`.

## Agent behavior (mandatory)

1. Inspect existing implementation first. 2. Follow established conventions.
3. Do not rewrite working code. 4. No tech outside the stack. 5. No unnecessary
abstractions. 6. No duplicate business logic. 7. No invented relationships.
8. No bypassing authorization. 9. No hardcoded secrets. 10. No unrelated
module changes. 11. Run tests. 12. Explain architectural decisions.