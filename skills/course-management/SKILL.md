---
name: educore-course-management
description: EduCore course management - courses, prerequisites, offerings, sections, and API/UI/authorization rules. Consult for any course work.
---

# EduCore — Course Management

## 1. Purpose

Manage curriculum units (courses): their identity, credits, prerequisites, and
their concrete classes each semester (course offerings → sections).

## 2. Main entities

- `course` — stable unit (code, name, credits, description).
- `course_prerequisite` — many-to-many: course → prerequisite courses.
- `course_offering` — a course taught in a semester/year.
- `section` — concrete class instance of an offering (lecturer, room, capacity,
  schedule).

## 3. Relationships

- Course belongs to program(s) via `program_course` (see `program-management`).
- Course **has many** Offerings (offering = course × semester).
- Offering **has many** Sections.
- Section → Lecturer(s), Section → Enrollments, Section → Schedule (see
  `timetable`), Section → Attendance/Assignments/Exams/Grades.
- Prerequisite: Course → Course (self many-to-many, acyclic).

## 4. Business rules

- Course code unique; credits positive (typical 1–6).
- Prerequisites must be enforced at enrollment (existing courses only,
  acyclic).
- An offering exists once per semester per course (unique
  `[course_id, semester_id]`); sections give it capacity/location/lecturer.
- Sections have capacity ≥ 1; enrollment enforces it (see `enrollment`).
- Deleting a course with offerings/grade history is restricted/archived.

## 5. API responsibilities

- `GET /api/courses`, `GET /api/courses/{course}` incl. prerequisites +
  offerings.
- `POST/PATCH/DELETE /api/courses`.
- `GET/POST/PATCH/DELETE /api/courses/{course}/offerings` and
  `/api/offerings/{offering}/sections`, or flat with filters.
- Prerequisites: endpoints to add/remove (`/api/courses/{course}/prerequisites`).

## 6. Backend responsibilities

- Thin controller + `CourseService`; validate credit ranges, unique code,
  prerequisite acyclicity.
- Section creation validates offering/semester and lecturer + room availability
  (delegate to timetable service).
- Eager-load offerings/prerequisites to avoid N+1 in lists/dropdowns.

## 7. Frontend responsibilities

- Course CRUD + prerequisites editor (pick list of courses).
- Offerings & sections management screens; dropdowns for semester/lecturer/room.
- Section capacity shown with current enrollment count (withCount).

## 8. Authorization rules

- Univ Admin: create/update courses.
- Faculty/Dept Admin: manage courses + sections within scope.
- Lecturer: view courses and manage only their assigned sections.
- Students: view catalog + their enrolled sections.

## 9. Validation rules

- Code required/unique; credits > 0; semester/offering exists; section
  capacity ≥ 1; prerequisites acyclic and existing.
- Offering uniqueness per course×semester.

## 10. Important edge cases

- Editing credits after enrollments exist — affect GPA calc? Recompute on
  grade changes (see `grading-gpa`).
- Removing a prerequisite that students relied on — soft archive, don't break
  existing enrollments.
- Creating sections for an offering in a past semester — block unless allowed.
- Duplicate course code.

## 11. Testing requirements

- CRUD + unique code.
- Prerequisite acyclicity + enforcement at enrollment.
- Offering uniqueness; capacity constraints.
- Section assignment conflicts (lecturer/room — see `timetable`).

## 12. Must NOT

- Must NOT merge course/offering/section into one table.
- Must NOT allow cyclic prerequisites.
- Must NOT hard-delete courses/offerings with history.
- Must NOT duplicate program-course membership logic (in `program-management`).

## Cross-references

- `skills/academic-domain/SKILL.md`, `skills/program-management/SKILL.md`,
  `skills/enrollment/SKILL.md`, `skills/timetable/SKILL.md`,
  `skills/grading-gpa/SKILL.md`.

## Agent behavior (mandatory)

1. Inspect existing implementation first. 2. Follow established conventions.
3. Do not rewrite working code. 4. No tech outside the stack. 5. No unnecessary
abstractions. 6. No duplicate business logic. 7. No invented relationships.
8. No bypassing authorization. 9. No hardcoded secrets. 10. No unrelated
module changes. 11. Run tests. 12. Explain architectural decisions.