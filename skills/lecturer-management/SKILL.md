---
name: educore-lecturer-management
description: EduCore lecturer management - lecturer profiles, departments, assigned sections, and API/UI/authorization rules. Consult for any lecturer CRUD work.
---

# EduCore — Lecturer Management

## 1. Purpose

Manage lecturers: profiles, employee info, department affiliation, and
assignment to sections they teach.

## 2. Main entities

- `lecturer` (profile) — links to users (role `lecturer`) for login.
- `department_lecturer` or `lecturer.department_id` — affiliation (N–1 or N–M
  if a lecturer can belong to multiple departments; be explicit and consistent).
- `section_lecturer` / `section.lecturer_id` — which sections they teach
  (see `skills/course-management`, `skills/timetable`).

## 3. Relationships

- Lecturer → Department/Faculty (N–1).
- Lecturer → Sections taught (1–N or N–M).
- Lecturer → Courses via sections.
- Lecturer → Attendance / Assignments / Exams / Grades in their sections.

## 4. Business rules

- A lecturer only manages the sections explicitly assigned to them.
- Availability/schedule conflicts prevented at the timetable level (see
  `skills/timetable/SKILL.md`).
- Status (active/inactive) controls whether they can be assigned new sections.
- Employee types/positions (lecturer, senior, etc.) configurable — do not build
  a deep HR system; keep it a profile field.

## 5. API responsibilities

- `GET /api/lecturers` (+ search/filter/pagination).
- `GET /api/lecturers/{lecturer}` with their sections/courses.
- `POST/PATCH/DELETE /api/lecturers` (soft delete to keep history).
- `GET /api/lecturers/{lecturer}/sections` for their teaching load.

## 6. Backend responsibilities

- Thin controller + `LecturerService`; Form Requests validate required fields,
  unique email, valid department, section assignment rules.
- Enforce "assign only existing sections" and "no double-booked lecturer" at
  the service level (with timetable check).
- Scope queries: staff all; lecturer sees own profile/sections only.

## 7. Frontend responsibilities

- Lecturers list + create/edit form.
- Lecturer detail page with assigned sections and course load.
- Status badges (Active/Inactive).

## 8. Authorization rules

- Univ Admin / Faculty-Dept Admin manage lecturers in scope.
- Lecturer: view/edit own profile, manage their assigned sections.

## 9. Validation rules

- Email unique/valid; department exists; section assignment valid.
- Cannot assign inactive lecturer to new sections.
- Duplicate assignment to same section prevented.

## 10. Important edge cases

- Removing a lecturer who has active sections/grades — block or reassign first.
- Lecturer also a user: deactivating lecturer should deactivate login.
- A lecturer assigned to multiple departments (if supported) — keep clear.

## 11. Testing requirements

- CRUD + 404/403/422.
- Section assignment constraints (duplicate, schedule conflict).
- Lecturer scoping (can't see other lecturers' private data).
- Inactive lecturer cannot be assigned.

## 12. Must NOT

- Must NOT build full HR/employee management beyond profile + assignment.
- Must NOT let a lecturer access sections they are not assigned to.
- Must NOT cascade-delete sections/grades with the lecturer.
- Must NOT invent lecturer-specific features beyond the scope.

## Cross-references

- `skills/academic-domain/SKILL.md`, `skills/timetable/SKILL.md`,
  `skills/authorization/SKILL.md`.

## Agent behavior (mandatory)

1. Inspect existing implementation first. 2. Follow established conventions.
3. Do not rewrite working code. 4. No tech outside the stack. 5. No unnecessary
abstractions. 6. No duplicate business logic. 7. No invented relationships.
8. No bypassing authorization. 9. No hardcoded secrets. 10. No unrelated
module changes. 11. Run tests. 12. Explain architectural decisions.