---
name: educore-analytics-reporting
description: EduCore analytics and reporting - student/academic/enrollment/administrative dashboards, Chart.js, aggregation query hygiene. Consult for any analytics or report work.
---

# EduCore — Analytics & Reporting

## 1. Purpose

Give admins dashboards with institutional insight: student counts, enrollment,
academic performance, attendance, documents, and financials. Dashboards read
from existing data (no new source-of-truth structures unless truly needed).

## 2. Main entities

- **No dedicated fact tables for MVP analytics.** Compute aggregates from the
  domain tables (students, enrollments, grades, attendance, invoices,
  documents) via optimized queries.
- Optional materialized/summary cache later if a report is too slow — only when
  justified (see `skills/database/SKILL.md`).

## 3. Relationships

- Derived from: Students → programs/departments/faculties; Enrollments →
  sections/semesters; Grades & GPA → academic performance; Attendance →
  percentages; Documents/Invoices → administrative workload.

## 4. Business rules

- Charts/numbers are computed server-side from current data; respect RBAC
  scope (a faculty admin sees their faculty's numbers only).
- All aggregates bounded in time (academic year/semester) — provide the
  filter, don't return "all time" unbounded by default.
- Reuse existing computed values (e.g. GPA from `grading-gpa`) — do not
  duplicate calculation logic.

## 5. API responsibilities

- `GET /api/dashboard/overview` — cards (total students, active, with
  semester enrollments, attendance average, pending requests, outstanding
  invoices).
- `GET /api/analytics/enrollment?year=&semester=&faculty_id=&department_id=`
  — enrollment series/per-program.
- `GET /api/analytics/academic` — GPA distribution, course pass rate,
  attendance.
- `GET /api/analytics/administrative` — pending requests, documents generated,
  invoices overdue/outstanding.
- All paginated/scoped; return cached numbers where cheap.

## 6. Backend responsibilities

- Thin controllers + aggregation services/repositories (complex reused
  queries justify a repository — see `skills/architecture`).
- Use grouped queries / `withCount` / `withSum`; avoid per-row PHP loops.
- Scope by role filters (auth must not leak other units' data).

## 7. Frontend responsibilities

- Dashboard pages with summary cards (see `frontend-ui`), charts via Chart.js,
  filters (year/semester/unit) applied via API query params.
- Consistent empty/loading/error states for charts.

## 8. Authorization rules

- Univ Admin: whole-university.
- Faculty/Dept Admin: scoped to their unit.
- Lecturers/Students: their own limited views (their section/performance) or
  none.

## 9. Validation rules

- Filter params validated (existent year/semester/unit); unknown filters
  rejected (see `skills/api`).

## 10. Important edge cases

- Zero-division (no students in a program) — return 0/null gracefully.
- Large datasets — aggregate in SQL, add indexes where the query needs them
  (see `skills/database`).
- Stale dashboards after a grade edit — since computed live, no staleness by
  construction; if caching added, define invalidation.

## 11. Testing requirements

- Each dashboard endpoint returns correct aggregates against seeded
  fixtures + respects role scope filters.
- Zero/edge data handled without errors.

## 12. Must NOT

- Must NOT invent surprise fact tables or new entities for analytics.
- Must NOT duplicate grade/GPA calculation from `grading-gpa`.
- Must NOT return unscoped (unfiltered) institution-wide data to a faculty
  admin.
- Must NOT compute aggregates in the frontend as the source of truth.

## Cross-references

- `skills/academic-domain/SKILL.md`, `skills/database/SKILL.md`,
  `skills/grading-gpa/SKILL.md`, `skills/frontend-ui/SKILL.md`.

## Agent behavior (mandatory)

1. Inspect existing implementation first. 2. Follow established conventions.
3. Do not rewrite working code. 4. No tech outside the stack. 5. No unnecessary
abstractions. 6. No duplicate business logic. 7. No invented relationships.
8. No bypassing authorization. 9. No hardcoded secrets. 10. No unrelated
module changes. 11. Run tests. 12. Explain architectural decisions.