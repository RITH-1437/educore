---
name: educore-faculty-department
description: EduCore faculty and department management - academic structure CRUD, cascading constraints, scoping. Consult for any faculty/department work.
---

# EduCore — Faculty & Department Management

## 1. Purpose

Manage the top of the academic hierarchy: faculties and their departments.

## 2. Main entities

- `faculty` — e.g. Faculty of Engineering.
- `department` — belongs to a faculty; e.g. Dept of Computer Science.

## 3. Relationships

- Faculty **has many** Departments (1–N).
- Department **has many** Programs (see `program-management`).
- Department **has many** Lecturers / Students (via program or affiliation).
- Deleting must respect children (see Business rules).

## 4. Business rules

- Department names unique within a faculty (DB unique constraint
  `[faculty_id, name]`).
- Faculty names unique.
- Deleting a faculty/department with children is **restricted** (FK restrict)
  — reassign or archive instead. Use soft delete/archived status rather than
  hard delete when history matters.
- Faculty/Department Admin scope is defined by their assigned
  faculty/department.

## 5. API responsibilities

- `GET /api/faculties` (with departments, optionally programs).
- `POST/PATCH/DELETE /api/faculties`.
- `GET/POST/PATCH/DELETE /api/faculties/{faculty}/departments` or flat
  `/api/departments` with `filters[faculty_id]`.

## 6. Backend responsibilities

- Thin controller + service; Form Requests validate uniqueness against parent.
- On delete: check child existence and refuse (or offer archive).
- Provide tree-shaped returns for dropdowns (faculty → departments).

## 7. Frontend responsibilities

- Faculty list with expandable departments; create/edit forms.
- Cascading dropdowns in forms elsewhere (program select → department →
  faculty) — reuse a shared component.

## 8. Authorization rules

- Super Admin / Univ Admin: manage faculties & departments.
- Faculty/Department Admin: view (and manage within) their own unit.

## 9. Validation rules

- Required name; unique name within parent.
- Cannot delete a faculty that still has departments (or must confirm archive).

## 10. Important edge cases

- Renaming a faculty/department must not break programs/students/lecturers
  (referential integrity via FK).
- Deleting the last department of a faculty.
- Moving a department between faculties — validate program/lecturer/student
  references remain valid.

## 11. Testing requirements

- CRUD + uniqueness (within parent).
- Delete restricted when children exist.
- Dropdown/tree endpoint returns correct nesting.

## 12. Must NOT

- Must NOT cascade-delete programs/departments silently.
- Must NOT create a faculty/department tree deeper than allowed.
- Must NOT invent new structure levels beyond Faculty → Department.

## Cross-references

- `skills/academic-domain/SKILL.md`, `skills/program-management/SKILL.md`,
  `skills/authorization/SKILL.md`, `skills/database/SKILL.md`.

## Agent behavior (mandatory)

1. Inspect existing implementation first. 2. Follow established conventions.
3. Do not rewrite working code. 4. No tech outside the stack. 5. No unnecessary
abstractions. 6. No duplicate business logic. 7. No invented relationships.
8. No bypassing authorization. 9. No hardcoded secrets. 10. No unrelated
module changes. 11. Run tests. 12. Explain architectural decisions.