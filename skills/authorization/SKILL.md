---
name: educore-authorization
description: EduCore RBAC - roles (Super Admin, University Admin, Faculty/Dept Admin, Lecturer, Student), policies, gates, route protection, permission-aware UI. Consult for any access-control work.
---

# EduCore Authorization (RBAC)

Authorization decides what an authenticated user may do. Identity is handled in
`skills/authentication/SKILL.md`.

## Roles (fixed)

| Role | Scope |
| --- | --- |
| **Super Admin** | Entire platform; system settings; audit log; user/role management. |
| **University Admin** | University-level: faculties, departments, programs, students, lecturers, courses, semesters, announcements, docs, payments, reports. |
| **Faculty / Department Admin** | Students/lecturers/courses/classes/schedules/attendance/reviews within their assigned faculty or department only. |
| **Lecturer** | Their assigned courses: attendance, assignments, exams, grades, materials, announcements. |
| **Student** | Their own data only: profile, registration, timetable, attendance, grades, GPA, document requests, invoices, announcements. |

- Roles are stored as data (roles table / enum on user) — resolve via
  `$user->role`. Permission sets follow the roles above.
- Extend a role's permissions only through explicit project decisions, not ad
  hoc per-endpoint special-casing.

## Enforcement (backend is authoritative)

- Use **Policies** (`app/Policies`) for every resource/action beyond simple
  authentication.
- Authorize in controllers/Form Requests: `$this->authorize('update', $model)`
  or `Gate::authorize(...)`.
- Scope queries to the user where relevant (a lecturer only sees their
  sections; a student only their own records) — use query scopes +
  policies, not filters the client can tamper with.
- **Never trust frontend authorization.** Hiding a button is UX only; the API
  must reject unauthorized access with `403`.

## Gates vs Policies

- Policies: per-resource actions (`viewAny`, `view`, `create`, `update`,
  `delete`, plus domain actions like `grade`, `approve`, `withdraw`).
- Gates: global checks not tied to a single model instance (e.g.
  `can:view-audit-log`), sparingly.

## Route / API protection

- All API routes: `auth:sanctum`.
- Domain authorization via Policy in the controller (or Form Request
  `authorize()`).
- List endpoints must filter server-side so users only receive data they may
  see (no leaking other students' grades via `?id=`).

## Frontend (permission-aware UI)

- Route guards: `meta: { requiresAuth, roles: [...] }` in
  `frontend/src/router` block navigation (defense in depth).
- UI hides actions the user cannot perform (menus, buttons) — improve UX.
- **Hiding ≠ security** — backend still enforces everything.

## Validation checklist

1. New endpoint/action has a Policy check (or Gate) before returning data.
2. List queries scoped to the caller's role/ownership.
3. `403` returned for denied access (standard error shape).
4. Frontend hides unauthorized actions but does not rely on that for security.
5. Tests cover allowed + denied cases for the flow (see `skills/testing`).

## Prohibitions

- DO NOT bypass authorization (`skipAuthorization`, `Gate::before` catch-alls,
  trusting a `role` field from the request).
- DO NOT expose other users' records by trusting client-side filters.
- DO NOT grant a role more scope than defined above.

## Cross-references

- `skills/student-management/SKILL.md`, `skills/lecturer-management/SKILL.md`
  — per-role data ownership.
- `skills/audit-logging/SKILL.md` — record sensitive access/changes.
- `skills/security/SKILL.md` — OWASP A01 broken access control.

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