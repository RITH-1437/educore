---
name: educore-student-management
description: EduCore student management - student entity, profiles, statuses, program assignment, and API/UI/authorization rules. Consult for any student CRUD or student-data work.
---

# EduCore — Student Management

## 1. Purpose

Manage university students: profiles, unique IDs, academic status, and their
link to programs/departments/faculties within the academic hierarchy.

## 2. Main entities

- `student` (or `students` table) — core profile
- Possibly part of the `users` table with role `student` (decide once: single
  users table + profile, or a student table that joins users). Follow what the
  implementation already established. Keep one source of truth for auth
  credentials (users) and one for academic profile (student).

## 3. Relationships

- Student → Program (N–1) — belongs to a program (through which they reach
  department/faculty).
- Student → Enrollments (1–N) — see `skills/enrollment/SKILL.md`.
- Student → Attendance / Grades / Documents / Invoices / Internships (1–N) —
  see the respective module skills.

## 4. Business rules

- Student ID is **unique** and stable (used for login — see
  `skills/authentication/SKILL.md`).
- Statuses: `active`, `inactive`, `suspended`, `graduated`, `withdrawn`.
- A `suspended`/`withdrawn` student cannot enroll or attend (enforced by
  enrollment/attendance services, not just UI).
- `graduated` keeps history (transcript) but blocks new enrollment.
- Dates (enrollment year, graduation year) tracked for analytics.

## 5. API responsibilities

- `GET /api/students` (paginated, filter by faculty/department/program/status,
  search by name/ID).
- `GET /api/students/{student}` — own profile or staff.
- `POST /api/students`, `PATCH /api/students/{student}`,
  `DELETE /api/students/{student}` (soft delete to keep history).
- `GET /api/students/{student}/enrollments`, `.../grades`,
  `.../documents`, `.../invoices` as needed.
- Response resource hides sensitive fields (no password).

## 6. Backend responsibilities

- Controller + `StudentService` (thin controller; business logic in service).
- Form Request validates: required fields, unique student ID/email, program
  exists, dates sane.
- `DeleteStudent` uses soft delete; keep user account or deactivate per rule.
- Scope queries to role (staff sees all; student sees own — see
  `skills/authorization/SKILL.md`).

## 7. Frontend responsibilities

- Students list with filters (faculty/department/program/status) + search +
  pagination (see `skills/frontend-ui`, `skills/vue`).
- Create/edit form with validation errors.
- Profile page for students (own view) + detail view for staff.
- Status badges (Active/Suspended/Graduated/...).

## 8. Authorization rules

- Univ Admin / Faculty-Dept Admin: manage students within their scope.
- Lecturer: read-only for students in their assigned sections only.
- Student: view own profile.

## 9. Validation rules

- Student ID format (consistent institution format), unique.
- Email unique/valid; contact fields optional but validated.
- Program/status must be valid; status transitions validated in service.

## 10. Important edge cases

- Changing a student's program affects enrollments — validate no active
  enrollments conflict.
- Deactivating/suspending mid-semester: block new enrollments, keep existing
  records; decide whether existing attendance/grades are retained (they are).
- Duplicate student ID / duplicate email at creation.
- Student also a user with credentials — password reset link works.

## 11. Testing requirements

- CRUD happy paths + 404/403/422.
- Unique constraints (student ID, email).
- Status transitions block/allow correctly.
- Student can only read own profile (403 for others).

## 12. Must NOT

- Must NOT store passwords on the student model without hashing.
- Must NOT expose other students' data via list endpoints.
- Must NOT cascade-delete academic history.
- Must NOT invent student fields not in the university model.

## Cross-references

- `skills/academic-domain/SKILL.md` — hierarchy & terminology.
- `skills/enrollment/SKILL.md`, `skills/authorization/SKILL.md`,
  `skills/security/SKILL.md`.

## Agent behavior (mandatory)

1. Inspect existing implementation first. 2. Follow established conventions.
3. Do not rewrite working code. 4. No tech outside the stack. 5. No unnecessary
abstractions. 6. No duplicate business logic. 7. No invented relationships.
8. No bypassing authorization. 9. No hardcoded secrets. 10. No unrelated
module changes. 11. Run tests. 12. Explain architectural decisions.