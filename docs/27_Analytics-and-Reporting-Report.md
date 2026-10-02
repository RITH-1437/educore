# 27 — Analytics & Reporting Report (Module 9.23)

- **Date:** 2026-10-02
- **Module:** 9.23 Analytics & Reporting (business-overview §9.23)
- **Status:** `[Implemented]` (CSV exports in `docs/31_CSV-Exports-Report.md`; PDF exports, faculty-scoped views and trends over time `[Future]`)
- **Depends on:** every academic and administrative module (read-only)

## 1. Scope

A management analytics page and four JSON endpoints for Super Admin and
University Admin: headline numbers, enrollment by program and status,
academic performance (grade and semester-GPA distributions, attendance and
results per course) for a chosen semester, and the current administrative
workload (documents, internships, invoices, finance per currency).

Not built: PDF exports (CSV exports were added later — `docs/31_CSV-Exports-Report.md`), a Faculty Admin view scoped to their unit (needs
unit scoping on the user record — `skills/analytics-reporting` §12 forbids
returning institution-wide data to them), multi-semester trend charts,
predictive analytics (out of scope by the business overview).

## 2. How figures are computed

**No schema change and no fact tables.** `AnalyticsService` runs grouped SQL
over the domain tables (no PHP loops over rows) and reuses other modules'
definitions rather than re-deriving them:

| Figure | Definition | Source |
|---|---|---|
| Enrollments / students enrolled | pending + confirmed + completed enrollments of the semester (dropped / withdrawn excluded) | `enrollments` |
| Enrollment by program | grouped by the student's **current** program | `student_programs` (active) |
| Attendance rate | (present + late) / (present + late + absent) over held sessions; excused not counted (the 9.11 formula) | `attendance_records` |
| Approved grades, pass rate | approved / finalized grades only (`Grade::FINAL_STATUSES`); pass = grade point > 0 | `grades` |
| Grade distribution | counts per letter of the **active** scale, in scale order, zeros included | `grading_scales` |
| Average semester GPA, GPA bands | mean / 4 bands of the semester GPA snapshots | `gpa_records` (9.14) |
| Results per course | graded count, pass rate, average total and points | `grades` |
| Workload by status | every status listed, zeros included | `document_requests`, `internships`, `invoices` |
| Finance | invoiced, collected, outstanding, overdue, collection rate — **one row per currency**, cancelled excluded; overdue refreshed first | `invoices` |

Every ratio returns `null` (shown as "—") when there is nothing to measure, so
an empty system never divides by zero. Academic figures are bounded to one
semester (`semester_id`, default: the open semester with the latest start);
administrative figures are the current workload.

```mermaid
flowchart LR
  F[Semester filter] --> S[AnalyticsService]
  S -->|grouped SQL| T[(domain tables)]
  S --> O[overview] & E[enrollment] & A[academic]
  W[administrative: point in time] --> S
  O & E & A & W --> P[Analytics page / JSON API]
```

## 3. Visual design

Chosen with the `dataviz` skill and `docs/branding/UI-COMPONENTS.md` §10:

- Headline numbers are **KPI stat tiles**, not charts.
- Magnitude comparisons (enrollment by program, attendance by course, grade
  and GPA distributions) are **single-series bar charts in one hue** — no
  categorical palette, so no rainbow and nothing that depends on telling
  colors apart; long category lists are horizontal bars sorted by value.
- Status breakdowns are **labelled lists with status badges** (text + color),
  not multi-color charts.
- `components/charts/BarChart.vue`: thin bars, 4px rounded data end with a
  flat baseline, gap between bars, recessive grid, no legend (single series —
  the card title names it), hover tooltip over the whole band, an accessible
  name listing every value, and a **Show as table** toggle; follows dark mode
  by observing the `<html>` class; respects `prefers-reduced-motion`.
- The series color was validated with the skill's palette validator:
  `#2563EB` passes on the light surface; in dark mode `#60A5FA`
  (`dark-primary`) fails the lightness band, so the chart uses `#3B82F6`
  (blue-500), which passes. Added as tokens `chart-primary` /
  `dark-chart-primary` (`style.css`, UI-COMPONENTS §10).

## 4. Authorization

Gate `view-analytics` (Super Admin, University Admin) plus route middleware.
Faculty Admin, lecturers and students get 403; the sidebar shows *Analytics*
under *Overview* to Super Admin and University Admin only.

## 5. Endpoints

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/analytics/overview?semester_id=` | Headline numbers |
| GET | `/api/analytics/enrollment?semester_id=` | By program and by status |
| GET | `/api/analytics/academic?semester_id=` | Grade / GPA distributions, attendance and results per course |
| GET | `/api/analytics/administrative` | Workload by status, finance per currency |

Semester-bound responses are `{ semester, data }` (both null when no semester
exists). Web: `GET /analytics` (`Analytics/Index`, all sections in one page).

## 6. Tests

`backend/tests/Feature/Analytics/AnalyticsTest.php` — 4 tests: every figure
against a hand-counted fixture (5 counted enrollments of which a dropped one
and another semester's are excluded, attendance 75 % with excused ignored,
2 approved grades of 3 with 50 % pass, letter counts on the scale, GPA bands,
per-course attendance lowest first, per-course results, another semester's
own numbers, unknown semester 422); finance per currency with cancelled
invoices excluded and overdue refreshed; an empty system (no semesters) is
safe on API and page; manager-only access on all four endpoints and the page.
Full suite: **373 passed**.

Not verified: the rendered charts were not inspected in a browser in this
session (no browser tool available); the build is clean and the data contract
is tested.
