---
name: educore-academic-domain
description: EduCore core academic hierarchy - university to transcript. The authoritative model for academic entities and relationships. Agents MUST consult this before changing any academic schema or relationship.
---

# EduCore Academic Domain

This is the **most important domain skill**. It defines the core academic model
that almost every module builds on. **Consult this skill before changing any
academic database relationship.**

## When to use

- Any schema/relationship change involving academic entities.
- Design/implementation of any academic module (enrollment, timetable,
  attendance, grades, etc.).
- Writing seeders/factories that build the academic hierarchy.

## Core hierarchy

```text
University
  └── Faculty
        └── Department
              └── Program
                    ├── Academic Year
                    │     └── Semester
                    └── Course
                          └── Course Offering (a course in a semester)
                                └── Section (a class instance)
                                      ├── Lecturer (teaches)
                                      └── Students
                                            └── Enrollment
                                                  ├── Attendance
                                                  ├── Assignment
                                                  ├── Exam
                                                  │     └── Result
                                                  └── Grade
                                                        └── GPA → Transcript
```

## Entity meanings (fixed definitions)

| Entity | Meaning | Owner of truth |
| --- | --- | --- |
| University | The institution itself (system-level). | Super Admin |
| Faculty | Broad academic division (e.g. Faculty of Engineering). | Univ Admin |
| Department | Sub-division of a faculty (e.g. Computer Science). | Univ Admin |
| Program | A degree track within a department (e.g. BSc in CS). | Univ Admin |
| Academic Year | e.g. 2026–2027. | Univ Admin |
| Semester | Term within an academic year (1 / 2 / …). | Univ Admin |
| Course | Stable curriculum unit (code, name, credits, prerequisites). | Univ Admin |
| Course Offering | One course taught in a specific semester. | Univ Admin |
| Section | A concrete class instance of an offering (Section A, B… with lecturer/room/capacity/schedule). | Faculty/Dept Admin |
| Lecturer | Teaching staff; linked to one or more departments, assigned to sections. | Univ Admin |
| Student | Enrolled person with a unique student ID. | Univ Admin |
| Enrollment | A student's registration in a section for a semester. | Student/Admin |
| Attendance | Per-enrollment presence records for classes. | Lecturer |
| Assignment | Task in a section; submissions graded per student. | Lecturer |
| Exam | Assessment (midterm/final/quiz) in a section. | Lecturer |
| Result/Grade | Outcome of assessments → letter grade + grade points. | Lecturer → approved |
| GPA | Aggregate grade point average (semester + cumulative). | System |
| Transcript | Official academic record of grades/GPA. | System/Docs |

## Cardinality (relationships)

- Faculty **has many** Departments (1–N)
- Department **has many** Programs (1–N)
- Program **has many** Courses (course.belonging to a program, N–N via
  program_course or a `program_id`; decide per implementation but be consistent)
- Academic Year **has many** Semesters (1–N)
- Course **has many** Course Offerings *(offering = course × semester)* (1–N)
- Course Offering **has many** Sections (1–N)
- Many-to-many: Sections **&** Lecturers (a section has one primary lecturer or
  multiple; keep a single teaching assignment table `section_lecturers` if
  multi-teacher is allowed — be explicit)
- Section **has many** Enrollments (1–N)
- Student **has many** Enrollments (1–N); Enrollment belongs to one Section
- Enrollment **has many** Attendance / AssignmentSubmissions / ExamResults /
  Grades (1–N)
- A Section's Schedule entries reference rooms + time slots; a room hosts many
  sections across the week (see `skills/timetable/SKILL.md`)

## Business rules (foundational)

1. A student can enroll in a section only if prerequisites are satisfied.
2. A student cannot be enrolled twice in the same section in the same semester
   (unique constraint).
3. A student may not exceed the maximum credits for the semester.
4. A student's enrollment status gates academic activity (active → can
   register/attend; suspended/withdrawn → cannot).
5. A lecturer may only manage the sections assigned to them.
6. A section has a capacity; enforce on enrollment.
7. Grades are computed from results/weights defined per course/offering
   (midterm/final/assignments/attendance).
8. GPA is credit-weighted over graded enrollments.
9. Attendance percentage is derived, never entered manually.
10. Academic records (enrollment, grade history) are kept historically — use
    soft deletes/history where deleting must not destroy academic truth
    (see `skills/database/SKILL.md`).

## Consistent terminology (MUST match)

- Use **exactly** these terms in code, DB, API, and frontend:
  `university`, `faculty`, `department`, `program`, `academic_year`,
  `semester`, `course`, `course_offering`, `section`, `lecturer`, `student`,
  `enrollment`, `attendance`, `assignment`, `assignment_submission`, `exam`,
  `exam_result`, `grade`, `gpa`, `transcript`.
- Table names mirror these (`faculties`, `enrollments`… snake_case plural —
  see `skills/database/SKILL.md`).
- Do not introduce synonyms (e.g. "classroom" instead of "section", "course
  instance" instead of "course offering").

## Prohibitions

- DO NOT invent relationships not listed here (no leaving a section holding
  "students" without an enrollment; no connecting a student directly to a course
  without a section).
- DO NOT invent new entities (e.g. "subject" ≠ "course"; "batch" ≠ "program")
  — map concepts to the canonical terms.
- DO NOT change this model without project-level approval and an
  `academic-domain` update.

## Guardrails for other modules

- `enrollment` — uses section capacity + prerequisite rules.
- `timetable` — schedules sections to rooms/time slots without conflicts.
- `attendance` — belongs to enrollment (who was present in that section).
- `assignments`, `examinations`, `grading-gpa` — all keyed to enrollment/section.
- `student-management`, `lecturer-management` — people linked to the hierarchy.
- `documents`, `analytics-reporting` — read from enrollments/grades/transcripts.

## Validation checklist (schema changes)

1. New/changed academic relationship matches this document's model.
2. Terminology consistent everywhere (DB/API/UI).
3. Cardinality matches (1–N / N–N as defined).
4. Constraints cover unique-uniqueness and capacity/prerequisite rules in DB.
5. Migration + tests added; `php artisan migrate:fresh` behaves.

## Cross-references

- `skills/database/SKILL.md` — schema conventions & constraints.
- `skills/enrollment/SKILL.md`, `skills/timetable/SKILL.md`,
  `skills/attendance/SKILL.md`, `skills/assignments/SKILL.md`,
  `skills/examinations/SKILL.md`, `skills/grading-gpa/SKILL.md`,
  `skills/course-management/SKILL.md`, `skills/program-management/SKILL.md`,
  `skills/faculty-department/SKILL.md`.

## Agent behavior (mandatory everywhere)

1. Inspect the existing implementation before modifying it.
2. Follow existing project conventions already established.
3. Do not rewrite working code unnecessarily.
4. Do not introduce technologies outside the EduCore stack.
5. Do not create unnecessary abstractions.
6. Do not create duplicate business logic.
7. Do not invent database relationships.
8. Do not bypass authorization.
9. Do not hardcode secrets.
10. Do not modify unrelated modules.
11. Run appropriate tests after changes.
12. Explain important architectural decisions.