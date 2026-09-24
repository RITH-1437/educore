---
name: educore-program-management
description: EduCore program management - academic programs/degree tracks, program-course association, and API/UI/authorization rules. Consult for any program CRUD.
---

# EduCore — Program (Degree Track) Management

## 1. Purpose

Manage academic programs (degree tracks: Bachelor of Computer Science, etc.)
inside departments, and the courses that belong to each program.

## 2. Main entities

- `program` — degree track.
- `program_course` (pivot) — which courses belong to a program (and possibly
  required year/semester for curriculum structure).

## 3. Relationships

- Program → Department (N–1).
- Program → Students (1–N).
- Program → Courses (N–M via pivot; a course can belong to multiple programs).
- Program → Academic Years / Semesters (via offerings, see
  `course-management`).

## 4. Business rules

- Program names unique within a department (`[department_id, name]` unique).
- Program code unique (business key, e.g. `BSCS`).
- Curriculum structure: a program has a list of courses plus optional
  year/semester ordering — keep simple (pivot with `academic_year`,
  `semester` nullable) unless the university model requires exam schedules.
- Deleting a program with students is restricted/archived.

## 5. API responsibilities

- `GET /api/programs` (+ `filters[faculty_id]`, `filters[department_id]`).
- `GET /api/programs/{program}` incl. courses.
- `POST/PATCH/DELETE /api/programs`.
- `POST /api/programs/{program}/courses` / `DELETE .../courses/{course}` to
  manage the curriculum.

## 6. Backend responsibilities

- Thin controller + service; validate program-course membership (no duplicate
  course in a program).
- Provide dropdown data (faculty → department → program) efficiently (no N+1).
- Deleting a program: block if students exist; offer archive.

## 7. Frontend responsibilities

- Program CRUD pages (under department).
- Curriculum editor: add/remove courses to a program.
- Cascading selects reused from `faculty-department`.

## 8. Authorization rules

- Univ Admin / Faculty-Dept Admin manage programs in scope.
- Others: read-only structure views.

## 9. Validation rules

- Required name; unique code; valid department.
- No duplicate course within a program; course must exist.

## 10. Important edge cases

- Changing a program's department — students' records still reference program
  (FK fine); validate no active enrollment conflicts.
- Removing a course from a program with existing grades/history — keep history
  via soft structures; don't cascade.

## 11. Testing requirements

- CRUD + unique code/name.
- Curriculum add/remove + duplicate prevention.
- Delete blocked when students exist.

## 12. Must NOT

- Must NOT invent "batch"/"track" entities beyond program.
- Must NOT duplicate the course list management that lives in
  `course-management`.
- Must NOT cascade-delete program history.

## Cross-references

- `skills/academic-domain/SKILL.md`, `skills/course-management/SKILL.md`,
  `skills/faculty-department/SKILL.md`.

## Agent behavior (mandatory)

1. Inspect existing implementation first. 2. Follow established conventions.
3. Do not rewrite working code. 4. No tech outside the stack. 5. No unnecessary
abstractions. 6. No duplicate business logic. 7. No invented relationships.
8. No bypassing authorization. 9. No hardcoded secrets. 10. No unrelated
module changes. 11. Run tests. 12. Explain architectural decisions.