# EduCore — User Flows & Process Documentation

Welcome to the comprehensive User Flow and Business Process documentation for **EduCore**, a University Digital Administration Platform designed for Cambodian universities.

- **Last Reviewed Date:** 2026-10-14
- **Technical Stack:** Laravel 12 (Modular Monolith) · Vue 3 + TypeScript (Inertia.js) · PostgreSQL 16 · Redis · MinIO · Tailwind CSS · Pinia
- **Diagram Standards:** Dual-syntax specification — embedded **Mermaid `flowchart TD` / `flowchart LR`** in all Markdown documents for direct GitHub/browser rendering, plus dedicated standalone **PlantUML (`.puml`)** source files in [`puml/`](./puml/).

---

## 1. Purpose & Scope

This documentation suite serves as the single architectural and behavioral source of truth for all actors interacting with EduCore. Every diagram and accompanying specification is derived directly from:
1. Audited Laravel routes (`backend/routes/web.php` and `backend/routes/api.php`),
2. Eloquent models, policies, and service layer domain logic (`backend/app/`),
3. The 49-table PostgreSQL relational schema (`docs/database/schema-tables.sql` and migrations),
4. Vue 3 Inertia frontend pages, layouts, and role-based navigation bars (`frontend/src/composables/useNavigation.js`).

### How to Read the Diagrams
- **Rectangles `[Text]`:** Actions, user inputs, or automated backend jobs.
- **Diamonds `{Condition?}`:** Decisions, permission checks, form validations, or business rule evaluations.
- **Rounded Rectangles `([Text])`:** Workflow start, terminal endpoints, or success states.
- **Subgraphs `subgraph Name ... end`:** Logical system boundaries (Browser UI, Backend Middleware/Controller, Database, Storage/Queue).
- **Explicit Branches:** Every decision path explicitly labels `Yes`/`No`, `Valid`/`Invalid`, or error recovery paths.

---

## 2. Implementation Status Legend

To maintain strict documentation integrity without overclaiming:
- **`[Implemented]`**: Shipped, supported by actual code, covered by database tables/policies, and tested in the test suite.
- **`[Partially Implemented]`**: Core CRUD or foundational steps exist, but specific advanced lifecycle actions remain manual or planned.
- **`[Planned]`**: Documented requirement or roadmap item with schema preparation, but not yet implemented.
- **`[Out of Scope]`**: Intentionally excluded from the current MVP (e.g., online payment gateways, biometric scanners, AI bots).

---

## 3. Documentation Index & Navigation

### A. System Overview & Security Foundations
- [System Architecture & System Overview](system-overview.md) — Platform overview, component topology, role-to-module authorization matrix.
- [Authentication & Two-Factor Flow](authentication-flow.md) — Login, password reset, 2FA challenge, rate limiting, and session lifecycle.
- [Authorization & RBAC Flow](authorization-rbac-flow.md) — Gatekeeper checks, Inertia front-guards, Policy evaluation, and department boundary isolation.
- [Audit & Security Flow](audit-and-security-flow.md) — Security event logging, sensitive record auditing, CSV export, and log retention.
- [Error & Exception Handling Flows](error-and-exception-flows.md) — 403 Forbidden, 404 Not Found, 409 Conflict, 422 Validation, and 500 System Error diagnostics.

### B. Role-Specific User Journeys
- [Super Admin User Flow](super-admin-flow.md) — Global university management, unit administration, user provisioning, role assignments, system-wide analytics, and audit oversight.
- [University Admin User Flow](university-admin-flow.md) — University-wide academic structure, academic calendar, course offerings, admissions, invoice billing, and institutional reporting.
- [Department Admin User Flow](faculty-department-admin-flow.md) — Scoped department dashboard, program oversight, lecturer assignment, section scheduling, room conflict resolution, grade approvals, and student requests.
- [Lecturer User Flow](lecturer-flow.md) — Section teaching, class attendance recording, assignment publishing & grading, exam administration, grade calculation & submission.
- [Student User Flow](student-flow.md) — Student portal, course registration & prerequisite checks, personal timetable, material downloads, assignment submissions, exam schedule, published grades & GPA, document requests, and tuition invoice tracking.

### C. Core Academic & Operational Workflows
- [Academic Management Flow](academic-management-flow.md) — University → Department → Program → Academic Year → Semester → Course hierarchy and curriculum definition.
- [Course Registration & Enrollment Flow](enrollment-flow.md) — Registration periods, prerequisite validation, schedule conflict checking, capacity limits, add/drop, and administrator enrollment actions.
- [Timetable & Room Conflict Flow](timetable-flow.md) — Weekly schedule creation, room assignment, instructor availability, and 3-way conflict detection (room, lecturer, section).
- [Attendance Tracking Flow](attendance-flow.md) — Session generation, bulk student attendance recording (present/absent/late/excused), remarks, cancellation, and student attendance rate calculation.
- [Assignment & Coursework Flow](assignment-flow.md) — Assignment creation, file attachments, student submission uploading, late policies, lecturer grading & feedback.
- [Examination & Grading Flow](exam-and-grading-flow.md) — Exam schedule definition, result recording, weighted component calculation, multi-stage grade workflow (Draft → Submitted → Approved → Finalized), and GPA recalculation.
- [Document Request & Verification Flow](document-request-and-verification-flow.md) — Document catalog, student requests, administrative review/waiver, PDF certificate generation, cryptographic QR code verification, and public verification portal.
- [Invoice & Payment Record Flow](invoice-and-payment-record-flow.md) — Tuition batch generation, document fee invoicing, student fee review, offline manual payment recording, receipt generation, and status transitions (unpaid/partially_paid/paid/cancelled).
- [Announcement & Notification Flow](announcement-and-notification-flow.md) — Multi-channel broadcasting, target audience scoping (all/students/lecturers/staff), scheduled publishing, in-app notification inbox, email alerts, and Telegram bot linking.
- [Internship Management Flow](internship-flow.md) — Student internship applications, administrative approval, supervisor evaluations, weekly report submissions, and completion tracking.
- [Analytics & Reporting Flow](analytics-and-reporting-flow.md) — Administrative metrics, enrollment statistics, academic performance tracking, revenue collection summaries, departmental comparisons, and CSV/PDF data exports.
- [Cross-Role Collaborative Workflows](cross-role-workflows.md) — End-to-end multi-actor lifecycles highlighting the touchpoints between Students, Lecturers, Department Admins, University Admins, and Super Admins.

### D. PlantUML Source Archive
All diagrams in this directory are duplicated as valid, compilable PlantUML `.puml` files inside [`docs/user-flows/puml/`](./puml/).

---

## 4. Key Architectural Discoveries & Resolved Decisions

1. **Department-First Academic Structure:**
   - In earlier iterations, the platform used a 4-tier structure (`University → Faculty → Department → Program`). Following architectural refinement (Report 39), the hierarchy was flattened to `University → Department → Program`. The role `faculty-admin` was renamed to `department-admin`, and unit scoping is enforced strictly via `users.department_id`.
2. **Strict RBAC & Department Scoping:**
   - A Department Admin has administrative rights strictly scoped to their assigned `department_id`. They cannot view or mutate courses, programs, lecturers, students, or grade submissions outside their department.
   - Financial invoicing and document type definitions are restricted to `super-admin` and `university-admin`.
3. **Offline Invoicing (No Online Payment Gateway):**
   - Cambodian university tuition and document fees are recorded via university cashiers/administrators after bank transfer or in-person payment verification. No automated online card gateway is part of the MVP.
4. **Multi-Stage Grade Lock:**
   - Lecturers enter marks and calculate final scores. When satisfied, they transition grades from `draft` to `submitted`. The Department Admin or University Admin reviews and transitions them to `approved` or returns them for revision. Only authorized administrators can `finalize` (lock) grades, which triggers GPA updates and transcript availability.
