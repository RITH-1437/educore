# Super Admin Flow

- **Validated against the code:** 2026-10-14 · **Status:** `[Implemented]`
- **Entry:** `/admin/dashboard` (`Admin/Dashboard`) · **Policies:** `UserPolicy`, `AuditLogPolicy`,
  `ErrorLogPolicy`, `GradePolicy::reopen`, plus every University Admin ability
- **Scope:** the whole platform. Matrix: [role permission matrix](../audits/role-permission-matrix.md)

## 1. Dashboard and navigation

```mermaid
flowchart TD
    S([Signed in · Super Admin]) --> D[/admin/dashboard<br/>platform overview: users, active users, roles,<br/>years and semesters, departments, programs, courses,<br/>lecturers, students, offerings, sections, held sessions,<br/>open enrollments · recent users · needs attention/]
    D --> N{Sidebar}
    N --> O[Overview: Dashboard · Analytics]
    N --> AS[Academic structure: University · Departments ·<br/>Programs · Academic years & semesters]
    N --> P[People: Users & roles · Students · Lecturers]
    N --> A[Academics: Courses · Offerings & sections ·<br/>Enrollments · Rooms · Grades · Grading scale]
    N --> OP[Operations: Announcements · Documents ·<br/>Document types · Internships · Invoices]
    N --> SY[System: Audit logs · Error logs]
    D --> T[Top bar: inbox bell · theme · account menu<br/>profile · password · two-factor · sign out]
```

Only the Super Admin uses `/admin/dashboard`; `/dashboard` is for the other four roles.

## 2. Users and roles (Super Admin only)

```mermaid
flowchart TD
    U([/users]) --> A{Action}
    A -- create --> C[Name, email, role, password;<br/>department only for a Department Admin]
    C --> CV{Valid? email unique,<br/>department exists}
    CV -- no --> CE[Field errors]
    CV -- yes --> CS[Account created · audit user.created]
    A -- edit / deactivate --> E[Change name, email, role, department, active]
    E --> G{Guards}
    G -- removes your own Super Admin role<br/>or deactivates yourself --> R1[409]
    G -- would leave no active Super Admin<br/>(active Super Admins locked) --> R2[409]
    G -- role change of an account holding<br/>a student / lecturer profile --> R3[409 · use Students / Lecturers]
    G -- ok --> ES[Saved · audited · a deactivated account<br/>loses its sessions and tokens at once]
    A -- delete --> DL{Own account, last Super Admin,<br/>or holds a profile?}
    DL -- yes --> R4[403 / 409]
    DL -- no --> DS[Deleted · audit user.deleted]
    A -- reset two-factor --> TF{Another account?}
    TF -- no --> R5[403]
    TF -- yes --> TS[Two-factor off · audit auth.two_factor_reset · owner emailed]
```

## 3. Structure and calendar

Same as the University Admin ([university-admin-flow](university-admin-flow.md) §2): universities
(one current), departments (archive vs delete with guards), programs, courses, rooms, academic years
(`planned → active → completed`) and semesters (`planned → open → closed → completed`, forward only).

## 4. Audit and error logs

```mermaid
flowchart TD
    L([System]) --> AU[/audit-logs<br/>filter by area, action, actor · search description, actor or action/]
    AU --> AD[Entry: actor, action, record,<br/>before / after values, IP, user agent]
    AU --> AX[CSV export · the export itself is audited]
    L --> ER[/error-logs<br/>every 404 and 5xx: status, method, path,<br/>exception class and message/]
    ER --> EF[Filter by status code, group, method]
    ER -. daily 01:10 .-> PR[Rows older than 90 days pruned]
```

The audit log is append-only (database trigger); nothing can edit or delete it. Error logs have no
write endpoint.

## 5. Grades: reopening finalized grades

Only the Super Admin may move a section's **finalized** grades back to approved, with a reason
(`grades.reopened` audit). See [exam-and-grading-flow](exam-and-grading-flow.md).

## 6. Sign-out

Account menu → *Sign out* (`POST /logout`). See [authentication-flow](authentication-flow.md) §6.
