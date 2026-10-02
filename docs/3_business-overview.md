# EduCore

## University Digital Administration Platform

### Business Overview & Project Proposal

**Document type:** Business overview / project proposal (non-technical first)
**Project:** EduCore — University Digital Administration Platform
**Prepared for:** University management, administrators, lecturers, students, project supervisors, developers, and potential stakeholders
**Prepared by:** Rin Nairith & Yong Lyhor (Project Team)
**Target market:** Designed for Cambodian university environments
**Proposed development period:** Approximately 4–6 months
**Architecture:** Modular Monolith (web platform)
**Primary stack:** Laravel + Vue.js, PostgreSQL, Redis, MinIO

---

## Document Purpose

This document explains EduCore from a business, operational, academic, and product
perspective. It describes why the platform is needed, what it will do, who will use it,
what is included, what is deliberately left out, and what the project aims to deliver.

It is written so that a reader who has never seen EduCore — a university administrator,
a lecturer, a student, or a potential stakeholder — can understand the entire project
without reading technical documentation.

Throughout this document, functionality is clearly labelled:

- **[Implemented]** — exists and works in the current codebase.
- **[Planned]** — designed and intended for the initial release, not yet built.
- **[Future]** — possible in later versions, outside the initial release.
- **[Out of Scope]** — deliberately excluded from the project.

---

## 1. Executive Summary

EduCore is a centralized digital administration platform designed for universities, with
the initial focus on Cambodian university environments. It is intended to bring the main
academic and administrative workflows — students, lecturers, courses, enrollment,
attendance, examinations, grades, documents, invoices, payments, and announcements — into
one secure web platform.

Today, many university activities still rely on spreadsheets, paper documents,
disconnected systems, messaging applications, and manual administrative work. Important
information may exist in several places, be difficult to verify, and require students and
staff to visit offices in person for routine tasks. EduCore proposes to reduce this
fragmentation by giving each type of user a single place to find the information and
services relevant to their responsibilities.

The proposed platform will be used by five system roles:

- **Super Admin** — platform owner responsible for configuration, security, and oversight.
- **University Admin** — university-level administrators managing faculties, programs, people, and records.
- **Faculty/Department Admin** — administrative staff managing academic operations within their unit.
- **Lecturer** — teaching staff managing courses, attendance, assignments, exams, and grades.
- **Student** — students accessing their academic portal, records, and services.

EduCore is designed to support a complete university workflow: from the university down
through faculties, departments, programs, courses, sections, and lecturers, and through a
student's academic lifecycle of enrollment, attendance, assignments, examinations, grades,
GPA, and documents.

The project is proposed to be developed over approximately **4–6 months** by a two-person
team using a modular monolith architecture: a Laravel backend providing an API, a Vue.js
frontend, PostgreSQL as the database, Redis for cache and queues, MinIO for file storage,
all running under Docker, with GitHub Actions for automated checks and deployment designed
for Laravel Cloud.

The purpose of EduCore is not to replace every piece of university software. It is to
provide a reliable, centralized core for the most important academic and administrative
workflows first, and to be a foundation that can grow in later versions.

> Note on wording: this document describes a proposed platform. EduCore is **designed for**
> Cambodian university environments and is **intended to support** university operations; it
> is not yet claimed to be deployed in any specific university.

---

## 2. Project Background

University organizations manage large amounts of academic and administrative information.
In many environments this information still lives in a mixture of:

- **Spreadsheets** — student lists, grade tables, and enrollment rosters kept in individual files.
- **Paper documents** — certificates, transcripts, approvals, and administrative records.
- **Disconnected systems** — different departments or offices using separate tools that do not share data.
- **Manual communication** — requests handled by visiting offices, phone calls, or informal messages.

When processes rely on these approaches, common difficulties arise:

- **Duplicated data** — the same student information may be re-entered in several places, risking inconsistencies.
- **Fragmented academic records** — a student's grades, attendance, and documents may be spread across different systems and files.
- **Manual approval processes** — routine requests require physical visits and in-person signatures.
- **Limited visibility** — university management may not be able to see enrollment, attendance, or performance trends quickly.
- **Difficult document verification** — paper or emailed documents can be hard for third parties to verify as authentic.
- **Scattered notifications** — announcements reach different groups through different channels at different times.

These are common organizational challenges, not criticisms of any particular university.
Many institutions face the same situation. The pattern is familiar across higher education:
**valuable information exists, but it is hard to find, hard to keep consistent, and hard to act on
in one place.**

EduCore is a response to this pattern. Rather than assuming a university has no systems at all,
the platform is designed to consolidate the main academic and administrative workflows into a
single, structured environment where information is recorded once and can be accessed, updated,
and verified consistently.

---

## 3. Problem Statement

The problems EduCore aims to address can be grouped into areas of university operations. For
each area, the current challenge leads to an operational consequence that the proposed platform
intends to address.

### 3.1 Academic Administration

- **Current challenge:** Course information, prerequisites, programs, and academic years are managed in separate files and formats.
- **Operational consequence:** Planning semesters and ensuring students take courses in the right order is time-consuming and error-prone.
- **How EduCore intends to address it:** A structured academic model (faculty → department → program → course → section) keeps the academic structure consistent and reusable every semester.

### 3.2 Student Services

- **Current challenge:** Students must visit offices or use multiple channels to view timetable, grades, attendance, and documents.
- **Operational consequence:** Students spend time on routine administrative visits; staff handle repetitive enquiries.
- **How EduCore intends to address it:** A student portal gives students direct access to their own academic information and request services.

### 3.3 Lecturer Management

- **Current challenge:** Lecturer teaching assignments and course responsibilities are coordinated manually.
- **Operational consequence:** Overlaps and scheduling conflicts are discovered late.
- **How EduCore intends to address it:** Lecturers are linked to departments and course sections, with a clear teaching dashboard for the courses assigned to them.

### 3.4 Communication

- **Current challenge:** Announcements are distributed through informal or disconnected channels.
- **Operational consequence:** Some students and staff miss important academic information.
- **How EduCore intends to address it:** Centralized announcements with email and Telegram notifications so messages reach the right audience.

### 3.5 Documents

- **Current challenge:** Official documents are prepared manually and verified with difficulty.
- **Operational consequence:** Verification of documents by employers or other institutions is slow and unreliable.
- **How EduCore intends to address it:** A document request workflow with QR-based verification so documents can be checked as authentic.

### 3.6 Payments and Administrative Records

- **Current challenge:** Invoices and payment records are tracked in files and spreadsheets.
- **Operational consequence:** Following who owes what and whether a payment has been recorded is difficult.
- **How EduCore intends to address it:** A structured record of invoices and payments with clear statuses. (EduCore records payments; it does not process online payments in the initial scope.)

### 3.7 Reporting and Analytics

- **Current challenge:** Management questions such as "how many students are enrolled this year?" or "what is the average attendance?" require manual counting.
- **Operational consequence:** Decisions are made with delayed or incomplete information.
- **How EduCore intends to address it:** Dashboards and reports derived from the centralized data.

### 3.8 Data and Security

- **Current challenge:** Sensitive academic records in distributed files are hard to protect and control consistently.
- **Operational consequence:** Access to information may be uncontrolled, and changes may leave no trace.
- **How EduCore intends to address it:** Role-based access, secure authentication, and audit logs that record important administrative actions.

### 3.9 Operational Efficiency

- **Current challenge:** Staff time is consumed by re-typing, cross-checking, and following up manually.
- **Operational consequence:** Repetitive tasks limit the time available for higher-value work.
- **How EduCore intends to address it:** Data is entered once and reused, and routine workflows are guided by the system.

---

## 4. Project Vision

EduCore aims to provide a centralized digital environment where university academic and
administrative activities can be managed through one secure platform.

The vision is deliberately practical:

- A student can find their courses, timetable, attendance, grades, and documents in one place.
- A lecturer can manage the sections they teach without juggling files.
- An administrator can maintain the university structure and its people consistently.
- Management can see how the institution is operating through clear reports.

The vision does **not** promise to eliminate all university administration or to replace
every existing system. It asks a simpler question: *can the most important academic and
administrative workflows live in one well-organized, secure place?* EduCore proposes that
they can.

---

## 5. Project Mission

EduCore's mission is to support universities by:

- **Centralizing university information** in one structured, consistent environment.
- **Reducing repetitive manual administration** by recording data once and reusing it.
- **Improving academic workflow visibility** for students, lecturers, and administrators.
- **Improving student access to services** through a centralized academic portal.
- **Supporting lecturers** with organized tools for their courses, attendance, assignments, and grades.
- **Improving communication** through announcements and notifications.
- **Improving document traceability** through request statuses and QR-based verification.
- **Improving reporting** by basing reports on consistent, centralized data.
- **Providing role-based access** so each user sees only what their role requires.
- **Maintaining auditable records** of important administrative actions.

These are aims, not guarantees. Each one represents a design direction the platform follows,
to be validated through testing and use.

---

## 6. Objectives

### 6.1 General Objective

To develop a centralized digital university administration platform that supports academic
management, administrative workflows, communication, and student services within a single
secure web environment.

### 6.2 Specific Objectives

1. **Centralize academic information** — one consistent record for the university structure, courses, and academic periods. [Planned]
2. **Manage student records** — store and maintain student profiles and academic status. [Planned]
3. **Manage lecturer information** — store lecturer profiles, departments, and assignments. [Planned]
4. **Manage courses and programs** — maintain programs, courses, offerings, and prerequisites. [Planned]
5. **Support enrollment** — let students register for course sections with validation. [Planned]
6. **Manage attendance** — record and view attendance per section with calculated percentages. [Planned]
7. **Manage examinations and grades** — support exams, grade entry, and GPA calculation. [Planned]
8. **Support document requests** — digital request, approval, generation, and QR verification of documents. [Planned]
9. **Support invoices and payment records** — record invoices, items, and payments with statuses. [Planned]
10. **Provide notifications** — announcements delivered by email and Telegram. [Planned]
11. **Provide analytics** — dashboards and reports for the main academic and administrative areas. [Planned]
12. **Maintain audit trails** — record important administrative and academic actions. [Planned]

Each objective is scoped to the initial release. The objectives deliberately avoid
promising outcomes the initial project does not intend to deliver (such as predictive
analytics or online payments).

---

## 7. Target Users and Stakeholders

There is an important distinction in this project:

- **System roles** are accounts that exist *inside* EduCore and define what a user can do in the platform.
- **Project stakeholders** are people or groups who have an interest in the *project* succeeding, whether or not they personally log in.

The following table summarizes both alongside the main users of the platform.

| User / Stakeholder | Type | Role & Interest | Main Needs | EduCore Interaction |
|---|---|---|---|---|
| Super Admin | System role | Platform owner; manages users, roles, permissions, and system configuration | Full configuration, security oversight, audit visibility | Highest-level administrator account |
| University Admin | System role | University-level administrator; manages faculties, departments, programs, students, lecturers, courses, semesters, documents, and payment records | Maintain university structure and records; review requests; view reports | Daily administrative operations |
| Faculty/Department Admin | System role | Administrative staff managing academic operations within their faculty or department | Manage their unit's students, lecturers, courses, sections, and monitors | Unit-level academic operations |
| Lecturer | System role | Teaching staff assigned to course sections | Manage courses, attendance, assignments, exams, and grades | Teaching dashboard and workflows |
| Student | System role | Enrolled learner | View own academic portal, register courses, view timetable/attendance/grades, request documents, receive notifications | Primary beneficiary of student services |
| University Management | Stakeholder | Institutional leadership; may not operate the system directly | Visibility into enrollment, performance, and administrative activity | Receives reports and overview dashboards; may be represented by admin accounts |
| IT/Technical Staff | Stakeholder | Support and infrastructure | Deploy, maintain, and troubleshoot the platform | Operate Docker/CI, servers, and support accounts |
| Project Team | Stakeholder | Rin Nairith & Yong Lyhor — developers | Deliver a reliable platform within scope | Develop, test, document, and deploy |

**Notes:**
- Finance Officer appears here only as a *non-system observation*: in the initial scope there is
  **no dedicated Finance Officer role**. Invoices and payment records are managed by University
  Admins. See Section 23.
- University Management, IT/Technical Staff, and the Project Team are stakeholders; they are not
  necessarily separate login roles in the platform.

---

## 8. User Role Overview

EduCore uses **role-based access**: every account belongs to a role, and each role can see and
do only what that role is authorized to do.

### 8.1 Super Admin

**Responsibilities:** platform ownership — user and role management, permission assignment,
university configuration, system settings, and security oversight.

**Permissions:** the highest level of access, including user administration and audit logs.

**Typical workflows:**

- Create accounts and assign roles.
- Configure university details and permissions.
- Review audit logs for accountability.

**Information accessible:**

- Platform configuration, all users and roles, and audit records.
- Not operational data such as individual students' daily records by default (that belongs to administrators) unless configured.

### 8.2 University Admin

**Responsibilities:** day-to-day university-level administration across the whole institution.

**Permissions:** manage faculties, departments, programs, students, lecturers, courses,
academic years, semesters, announcements, document requests, and payment records; view reports.

**Typical workflows:**

- Set up the university structure and academic calendar.
- Maintain the people records (students and lecturers).
- Process document requests and record invoices and payments.
- Publish announcements.

**Information accessible:** institution-wide academic and administrative records.

### 8.3 Faculty/Department Admin

**Responsibilities:** academic operations within an assigned faculty or department.

**Permissions:** manage their unit's students, lecturers, courses, sections, schedules, and
monitor attendance and performance; handle unit-level requests.

**Typical workflows:**

- Create sections and schedules for their unit.
- Monitor attendance and academic progress.
- Review student requests within the unit.

**Information accessible:** records belonging to their assigned unit only. They do not see
other units' data.

### 8.4 Lecturer

**Responsibilities:** teaching management for the sections assigned to them.

**Permissions:** manage attendance, create assignments, manage exams, submit grades for their
own sections, and publish course-level announcements.

**Typical workflows:**

- Take attendance for a class session.
- Publish an assignment and view submissions.
- Enter and submit grades.

**Information accessible:** only the courses, sections, and students assigned to them. A
lecturer cannot see other lecturers' courses or grades.

### 8.5 Student

**Responsibilities:** manage their own academic life within the platform.

**Permissions:** view their own profile and academic information, register for course sections,
view their timetable, attendance, assignments, exams, grades, and GPA, request documents, and
view their invoices and payment records.

**Typical workflows:**

- Register for eligible course sections.
- Check the weekly timetable and attendance record.
- Submit assignments and view results.
- Request an official document and download it.
- Follow announcements and notifications.

**Information accessible:** **only their own records.** Students can never see another
student's data.

---

## 9. Core Business Modules

The following modules make up the proposed platform. Each is described by its purpose, primary
users, main business activities, and expected outputs.

### 9.1 Authentication & Authorization [Implemented]

- **Purpose:** secure login and role-based access for all users.
- **Primary users:** all roles.
- **Main business activities:** login/logout, password management, session control, role and permission assignment.
- **Expected outputs:** a secure environment where each user sees only what their role allows.
- **Delivered:** sign-in / sign-out (web session and API token), five fixed
  roles enforced server-side, inactive accounts refused and their sessions /
  tokens revoked, password change and emailed password reset
  ([29_Password-Change-and-Reset-Report.md](29_Password-Change-and-Reset-Report.md)),
  sign-in activity audited. Editable permission sets and two-factor sign-in
  are [Future].

### 9.2 Student Management [Implemented]

- **Purpose:** store and maintain student profiles and academic status.
- **Primary users:** University Admin, Faculty/Department Admin.
- **Main business activities:** create/update student records, assign programs, maintain status (active, inactive, suspended, graduated, withdrawn).
- **Expected outputs:** a single, consistent student register.
- **Delivered:** student profiles linked to Student-role accounts, status
  lifecycle (active/inactive/suspended/graduated/withdrawn), program history
  with transfers, guarded delete, `/api/students`, seeded students. See
  [13_Student-Management-Report.md](13_Student-Management-Report.md).
- **Not yet delivered `[Planned]`:** enrollment/grade/document views per student
  and lecturer access via sections.

### 9.3 Lecturer Management [Implemented]

- **Purpose:** store and maintain lecturer profiles and assignments.
- **Primary users:** University Admin, Faculty/Department Admin.
- **Main business activities:** maintain lecturer records, departments, and positions; assign sections.
- **Expected outputs:** a reliable staff register linked to teaching duties.
- **Delivered:** lecturer profiles linked to Lecturer-role login accounts
  (create a new account or link an existing one), home department, position,
  specialization, employment type, deactivate/reactivate mirrored onto the
  account, guarded delete, `/api/lecturers`, seeded staff. A lecturer can read
  their own profile. See [12_Lecturer-Management-Report.md](12_Lecturer-Management-Report.md).
- **Not yet delivered `[Planned]`:** section assignment (9.8) and lecturer
  self-service editing.

### 9.4 Faculty & Department Management [Implemented]

- **Purpose:** model the university's organizational structure.
- **Primary users:** University Admin (write), Super Admin (write), Faculty Admin (read-only).
- **Main business activities:** create/manage universities, faculties, departments, and their relationships.
- **Expected outputs:** a clear, reusable hierarchical structure.
- **Delivered:** university record with a single `is_current` row, faculties and
  departments with global/per-faculty name uniqueness, archive-vs-delete
  semantics, delete guards that count soft-deleted children, nested department
  management under the faculty screen, a `faculties-tree` read endpoint, and a
  deterministic 1 university / 3 faculty / 6 department seed. See
  [7_Faculty-and-Department-Report.md](7_Faculty-and-Department-Report.md).

### 9.5 Program Management [Implemented]

- **Purpose:** define programs of study under departments.
- **Primary users:** University Admin, Faculty/Department Admin.
- **Main business activities:** create/edit programs and program structures.
- **Expected outputs:** an accurate list of offered programs.
- **Delivered:** program CRUD under departments (code, name, degree level,
  duration, credits), unique code and per-department unique name, archive vs
  delete with guards, faculty → department cascade UI, `/api/programs`, seeded
  degree programs. Faculty Admin is read-only. See
  [10_Program-Management-Report.md](10_Program-Management-Report.md).
- **Curriculum editor:** delivered with 9.7 (program ↔ course membership).

### 9.6 Academic Year & Semester Management [Implemented]

- **Purpose:** define the academic calendar and periods.
- **Primary users:** University Admin.
- **Main business activities:** manage academic years, semesters, and enrollment/examination periods.
- **Expected outputs:** a structured academic calendar.
- **Delivered:** status lifecycles (year `planned → active → completed`, semester
  `planned → open → closed → completed`), one current academic year, semester
  dates constrained to their year, delete guards, web pages plus
  `/api/academic-years` with nested semesters, seeded three-year calendar.

### 9.7 Course Management [Implemented]

- **Purpose:** maintain the catalog of courses and their prerequisites.
- **Primary users:** University Admin, Faculty/Department Admin.
- **Main business activities:** create courses with codes, credits, descriptions, and prerequisites.
- **Expected outputs:** a consistent, reusable course catalog.
- **Delivered:** course CRUD with a draft → active → archived lifecycle, unique
  codes, acyclic prerequisites (self/duplicate/archived/cycle rejected), the
  program curriculum editor (course ↔ program with required flag and suggested
  semester), delete guards, `/api/courses`, seeded catalog. Faculty Admin is
  read-only. See [11_Course-Management-Report.md](11_Course-Management-Report.md).
- **Not yet delivered `[Planned]`:** course offerings and sections — they need
  lecturers, rooms and the timetable (9.3 / 9.8 / 9.10).

### 9.8 Class / Section Management [Implemented]

- **Purpose:** turn a course into concrete class instances for a semester (sections).
- **Primary users:** Faculty/Department Admin, University Admin.
- **Main business activities:** create sections (A, B, C…), assign lecturers/rooms/schedules/capacity.
- **Expected outputs:** concrete teachable classes per semester.
- **Delivered:** course offerings per semester, sections with capacity and
  status, lecturer assignment (primary/assistant/tutor), lecturer teaching
  load, guarded deletes, `/api/offerings` and `/api/sections`. See
  [14_Class-and-Section-Report.md](14_Class-and-Section-Report.md).
- **Rooms and weekly schedules:** delivered with 9.10.

### 9.9 Course Registration / Enrollment [Implemented]

- **Purpose:** let students register for eligible sections.
- **Primary users:** Student, Faculty/Department Admin.
- **Main business activities:** view available sections, register with validation (prerequisites, capacity, duplicates), confirm enrollment.
- **Expected outputs:** a validated enrollment record per student per section.
- **Delivered:** admin and student self-service registration with one rule
  engine (active student, open registration, strict prerequisites, credit
  limit, seats under a row lock, no duplicates), drop with history, completion,
  `/api/enrollments`. See [15_Enrollment-Report.md](15_Enrollment-Report.md).

### 9.10 Timetable Management [Implemented]

- **Purpose:** manage the weekly schedule of sections, lecturers, and rooms.
- **Primary users:** Faculty/Department Admin, University Admin.
- **Main business activities:** schedule lectures into days/time slots; detect lecturer, room, and student-group conflicts.
- **Expected outputs:** conflict-checked timetables.
- **Delivered:** rooms, weekly section schedules, per-semester conflict
  checks (room, section, lecturer, student, room capacity — incl. partial
  overlaps), clash checks at enrollment and lecturer assignment, personal
  timetables. See [16_Timetable-Report.md](16_Timetable-Report.md).

### 9.11 Attendance [Implemented]

- **Purpose:** record and view attendance per section.
- **Primary users:** Lecturer, Student (view), Faculty/Department Admin (monitor).
- **Main business activities:** mark attendance (present/absent/late/excused), calculate percentages.
- **Expected outputs:** attendance records and student attendance summaries.
- **Delivered:** dated sessions per section, bulk recording by the section's
  lecturers (managers may correct), date policy tied to the weekly schedule,
  derived attendance rates, cancel/restore class, lecturer register and
  student \"My attendance\". See [17_Attendance-Report.md](17_Attendance-Report.md).

### 9.12 Assignments [Implemented]

- **Purpose:** manage course work and submissions.
- **Primary users:** Lecturer, Student.
- **Main business activities:** create assignments with deadlines, upload materials, submit work, view results.
- **Expected outputs:** a structured record of assignments and submissions.
- **Delivered:** draft → published assignments per section with due dates
  inside the semester, private file submissions in MinIO (replaceable until
  graded, late work flagged), grading with feedback, authorized downloads,
  lecturer coursework page and student "My assignments". Lecturer-attached
  materials remain [Planned]. See [18_Assignments-Report.md](18_Assignments-Report.md).

### 9.13 Examinations [Implemented]

- **Purpose:** manage exams and results.
- **Primary users:** Lecturer, Faculty/Department Admin, Student (view).
- **Main business activities:** define exams, schedule, record results with configurable weighting.
- **Expected outputs:** structured exam schedules and results.
- **Delivered:** midterm/final/quiz/practical exams per section with weight
  (section total ≤ 100%), max score, date/time/location, clash detection for
  shared students, bulk results entry and correction, release of results,
  lecturer results grid and student "My exams". Weighting into final grades
  is delivered by 9.14. See [19_Examinations-Report.md](19_Examinations-Report.md).

### 9.14 Grades & GPA [Implemented]

- **Purpose:** turn scores into letter grades, grade points, and GPAs.
- **Primary users:** Lecturer (entry), Student (view), Administrators (oversight).
- **Main business activities:** enter scores, apply letter-grade scale, compute semester and cumulative GPA.
- **Expected outputs:** consistent grade records and GPA values.
- **Delivered:** configurable grading scale (default A=4.0 … F=0.0), per-course
  component weights (attendance, coursework, midterm, final, practical = 100%),
  a live section grade sheet computed from attendance, assignments and exam
  results, a draft → submitted → approved workflow (approval completes the
  enrollment), credit-weighted semester and cumulative GPA rebuilt on every
  grade or credit change (retakes replace earlier attempts), grade-based
  prerequisites, and lecturer / staff / student screens. The official
  transcript document is [Planned] with 9.16; grade-change auditing with 9.24.
  See [20_Grades-and-GPA-Report.md](20_Grades-and-GPA-Report.md).

### 9.15 Student Academic Dashboard [Implemented]

- **Purpose:** a single-page view of a student's academic life.
- **Primary users:** Student.
- **Main business activities:** view GPA, attendance, credits, today's classes, upcoming assignments, announcements, and recent grades.
- **Expected outputs:** an "at a glance" academic summary for each student.
- **Delivered:** the student `/dashboard` shows cumulative and semester GPA,
  credits this semester and earned, current-semester attendance (overall and
  per course), today's classes, assignments due, upcoming exams and recent
  approved grades, all read from the owning modules, plus
  `GET /api/students/{student}/dashboard`, and (since 9.19) the latest
  announcements. See [21_Student-Academic-Dashboard-Report.md](21_Student-Academic-Dashboard-Report.md).

### 9.16 Document Management [Implemented]

- **Purpose:** digital handling of official documents (enrollment certificates, transcripts, results, internship letters).
- **Primary users:** Student (request), University Admin / Faculty/Department Admin (approve/generate).
- **Main business activities:** request, review, approve, generate, download, and track document status.
- **Expected outputs:** a traceable document workflow.
- **Delivered:** student requests for an enrollment certificate, student
  certificate, academic transcript, one-semester academic result or
  internship letter; approve / reject (with reason) /
  generate / revoke by University Admin; PDFs rendered only from enrollments,
  approved grades and GPA, stored privately in MinIO and downloaded through an
  authorized route. Document fees are not billed yet. See
  [22_Documents-and-Verification-Report.md](22_Documents-and-Verification-Report.md) and
  [30_Grade-Finalization-and-Document-Templates-Report.md](30_Grade-Finalization-and-Document-Templates-Report.md).

### 9.17 Digital Document Verification [Implemented]

- **Purpose:** let third parties verify the authenticity of generated documents.
- **Primary users:** anyone with a document and internet access (employers, institutions, graduates).
- **Main business activities:** scan/enter a QR code to verify validity.
- **Expected outputs:** a clear valid/invalid verification result per document.
- **Delivered:** every PDF prints a verification URL and code; the public
  `/verify/{code}` page (and API) reports valid / revoked / not found with
  minimal facts and the file's SHA-256, logs each lookup and is rate limited.
  A scannable QR image is [Planned]. See [22_Documents-and-Verification-Report.md](22_Documents-and-Verification-Report.md).

### 9.18 Invoices & Payment Records [Implemented]

- **Purpose:** record invoices and payments.
- **Primary users:** University Admin, Student (view own).
- **Main business activities:** create invoices with items, record payments, track status (pending, partially paid, paid, overdue, cancelled).
- **Expected outputs:** accurate financial records. *(No online payment gateway — see Section 23.)*
- **Delivered:** itemised invoices (USD / KHR, discount, sequential numbers),
  payment records within the balance, append-only reversals, cancellation
  instead of deletion, derived statuses kept current (overdue refreshed on read
  and by a daily command), a per-currency student summary and "My invoices".
  Invoice PDFs and automatic tuition billing are [Future].
  See [23_Invoices-and-Payments-Report.md](23_Invoices-and-Payments-Report.md).

### 9.19 Announcements [Implemented]

- **Purpose:** publish and target announcements.
- **Primary users:** Administrators, authorized Lecturers, Student (receive).
- **Main business activities:** create announcements targeted to all/faculty/department/program/class/course; publish.
- **Expected outputs:** a structured announcement feed per audience.
- **Delivered:** drafts → published → archived announcements to everyone, a
  role group, or one faculty / department / program / section / course;
  lecturers write to the sections and courses they teach; every user has a
  live feed resolved from their memberships, and the student dashboard shows
  the latest three. Email / Telegram delivery ships with 9.20 / 9.21;
  attachments and read receipts are [Future].
  See [24_Announcements-Report.md](24_Announcements-Report.md).

### 9.20 Email Notifications [Implemented]

- **Purpose:** deliver important messages by email.
- **Primary users:** System (all roles receive).
- **Main business activities:** notifications for announcements, document status, registration confirmation, password-related messages, and administrative updates.
- **Expected outputs:** reliable asynchronous email delivery.
- **Delivered:** queued, retried emails for registration confirmation, grade
  approval, document request status, invoices and payments (always sent),
  announcements and assignment reminders, with a per-user opt-out for
  non-critical mail and a queue worker container. Password-related emails are
  [Future] (no password-reset flow yet).
  See [25_Notifications-Report.md](25_Notifications-Report.md).

### 9.21 Telegram Notifications [Implemented]

- **Purpose:** deliver time-sensitive messages via Telegram.
- **Primary users:** System (Students and Staff who opt in).
- **Main business activities:** class reminders, assignment reminders, announcement alerts.
- **Expected outputs:** supplementary near-real-time notification channel.
- **Delivered:** opt-in Telegram delivery of the same notifications (announcement
  alerts, daily assignment reminders, status updates) to a chat id the user
  links in their settings, through the Bot API with the server's bot token.
  Class reminders and automatic chat linking are [Future].
  See [25_Notifications-Report.md](25_Notifications-Report.md).

### 9.22 Internship Management [Implemented]

- **Purpose:** support the university internship workflow.
- **Primary users:** Student, University Admin / Faculty/Department Admin.
- **Main business activities:** manage company information, opportunities, applications, internship reports, evaluations, and status.
- **Expected outputs:** a structured internship lifecycle.
- **Delivered:** host companies, student applications (draft → submitted →
  under review → approved / rejected → in progress → completed, or cancelled),
  one open application per student, initial / progress / final reports with
  private files, supervisor and faculty evaluations, status notifications and
  a decision log, and an internship letter document (9.16). Published
  opportunity postings are [Future]. See [26_Internship-Management-Report.md](26_Internship-Management-Report.md).

### 9.23 Analytics & Reporting [Implemented]

- **Purpose:** provide institutional visibility.
- **Primary users:** Administrators and management.
- **Main business activities:** view student, enrollment, attendance, grade, GPA, course, faculty/department, internship, and administrative statistics.
- **Expected outputs:** dashboards and reports derived from centralized data. *(Not predictive analytics.)*
- **Delivered:** a management analytics page and API for a chosen semester —
  headline numbers, enrollment by program, attendance and results per course,
  grade and GPA distributions — plus the current workload (documents,
  internships, invoices, finance per currency), all computed live in SQL from
  the domain tables. Exports, faculty-scoped views and trends are [Future].
  See [27_Analytics-and-Reporting-Report.md](27_Analytics-and-Reporting-Report.md).

### 9.24 Audit Logs & Security [Implemented]

- **Purpose:** record important administrative and academic actions.
- **Primary users:** Super Admin.
- **Main business activities:** log who did what, to what record, and when; review for accountability.
- **Expected outputs:** an append-only audit trail of sensitive actions.
- **Delivered:** an audit trail of sign-ins and sensitive changes (users,
  student status, enrollment, exam corrections, grades and grading rules,
  documents, invoices and payments, announcements, internships) with
  before / after values and no secrets, append-only in the application and in
  the database; a Super Admin viewer; deactivated accounts lose their
  sessions and API tokens immediately. Retention / export is [Future].
  See [28_Audit-Logs-and-Security-Report.md](28_Audit-Logs-and-Security-Report.md).

### 9.25 System Error Logs [Implemented]

- **Purpose:** give Super Admin visibility into failing requests — pages that
  do not exist and server errors — without digging through server log files.
- **Primary users:** Super Admin only (a row can expose an exception class, a
  message and the originating path).
- **Main business activities:** every HTTP 404 and 5xx response is recorded
  automatically; Super Admin searches, filters by status code/group/method,
  and reviews a detail page per error.
- **Expected outputs:** an append-only, read-only error log distinct from the
  audit trail in 9.24 — this records failed *requests*, not business changes.
  No create/update/delete endpoint exists. Reached directly at `/error-logs`;
  not on the sidebar.

---

## 10. End-to-End Business Workflow

The platform is built around one central idea: a university is an organization with a
structure, and students move through that structure over time.

```
University
   → Faculty
   → Department
   → Program
   → Academic Year
   → Semester
   → Course
   → Course Offering (a course taught in a semester)
   → Section (a concrete class)
   → Lecturer (assigned to the section)
   → Student (enrolled in the section)
   → Timetable
   → Attendance
   → Assignment
   → Examination
   → Grade
   → GPA
   → Transcript / Document / Verification
```

In plain language: a university contains faculties, and each faculty has departments. Each
department offers programs (degrees). A program is made of courses. In a given semester, a
course is offered as one or more sections. Each section is taught by a lecturer and attended
by enrolled students. Over the semester, students attend classes, complete assignments, and
take examinations. Their results produce grades and GPAs. At the end, students can request
official documents that record this academic history, and third parties can verify those
documents digitally.

### 10.1 Student Enrollment Workflow

1. The university publishes programs, courses, and sections for the semester.
2. A student views sections they are eligible for.
3. The student registers for sections; validation checks prerequisites, duplicates, capacity, semester, and status.
4. On success, the enrollment is confirmed and appears in the student's record.

### 10.2 Course Registration Workflow

1. Administrators prepare the semester's course offerings and sections.
2. Students review available sections and their requirements.
3. Registration is validated (eligibility, capacity, prior completion).
4. Confirmed registrations become the section rosters.

### 10.3 Timetable Workflow

1. Administrators assign sections to days and time slots.
2. Rooms and lecturers are scheduled per section.
3. The system flags lecturer, room, and student-group conflicts before finalizing.
4. Students and lecturers see their own timetables.

### 10.4 Attendance Workflow

1. The lecturer opens the attendance record for a session.
2. Each student is marked present, absent, late, or excused.
3. Percentages are calculated automatically.
4. Students view their own attendance; administrators monitor trends.

### 10.5 Assignment Workflow

1. The lecturer creates an assignment with a description, materials, and deadline.
2. Students are notified and can submit work.
3. Submissions are collected and graded.
4. Students view results and feedback.

### 10.6 Examination Workflow

1. Exams are defined for sections (midterm, final, quizzes).
2. Schedules are published and visible to students.
3. Results are recorded per student.
4. Results feed into final grades according to configurable weighting.

### 10.7 Grade Workflow

1. The lecturer enters scores for their section.
2. The system applies the letter-grade scale.
3. Semester and cumulative GPAs are calculated.
4. Grades are finalized; students see results in their portal.

### 10.8 Document Request Workflow

1. A student requests an official document (certificate, transcript, result, internship letter).
2. An administrator reviews and approves/rejects the request.
3. On approval, the document is generated with a verification QR code.
4. The student downloads it; third parties can verify it later.

### 10.9 Payment Record Workflow

1. An administrator creates an invoice with items for a student.
2. The invoice gets a status (pending, partially paid, paid, overdue, cancelled).
3. Payments are recorded with amount, date, method, and reference.
4. Outstanding amounts remain visible until settled. *(Payment recording only — no online gateway.)*

### 10.10 Internship Workflow

1. Company and opportunity information is registered.
2. A student applies for an opportunity.
3. University staff review and approve the internship.
4. The intern submits reports; supervisors provide evaluations.
5. Status is tracked until completion.

### 10.11 Announcement / Notification Workflow

1. An authorized user creates an announcement targeted to an audience.
2. The system delivers it in-platform and via the configured channels (email, Telegram).
3. Recipients see the announcement in their feed and receive notifications.
4. Notification preferences control delivery where configured.

---

## 11. Functional Scope

Scope is defined in three clear categories so that readers understand exactly what the project
includes, what is deferred, and what is deliberately excluded.

| Category | Meaning |
|---|---|
| **In Scope** | Functionality planned for the initial release (Sections 12 and 16–25). |
| **Planned but Deferred** | Recognized needs for the future, intentionally not built in the initial release. |
| **Out of Scope** | Features consciously excluded from the project. |

These categories are not mixed. A feature appears in exactly one category.

---

## 12. In-Scope Features

The initial release will provide:

- **Authentication** — secure login for all roles. [Planned]
- **RBAC (role-based access control)** — five roles with defined permissions. [Planned]
- **Student management** — student records and status. [Planned]
- **Lecturer management** — lecturer records and assignments. [Planned]
- **Academic structure** — faculties, departments, programs, academic years, semesters. [Planned]
- **Courses** — course catalog with prerequisites and credits. [Planned]
- **Sections** — concrete class instances per semester. [Planned]
- **Enrollment** — validated student registration. [Planned]
- **Timetable** — schedules with conflict detection. [Planned]
- **Attendance** — recording and percentages. [Planned]
- **Assignments** — tasks and submissions. [Implemented]
- **Exams** — definitions, schedules, and results. [Planned]
- **Grades** — score-to-letter-grade conversion. [Planned]
- **GPA** — semester and cumulative calculation. [Planned]
- **Transcripts** — academic history documents. [Planned]
- **Documents** — request, approval, and generation workflow. [Planned]
- **QR verification** — digital authenticity check. [Planned]
- **Invoices** — invoice creation and status. [Planned]
- **Payment records** — recording of payments. [Planned]
- **Announcements** — targeted publishing. [Planned]
- **Email** — asynchronous email notifications. [Planned]
- **Telegram** — supplementary notifications. [Planned]
- **Internship** — applications, reports, and evaluations. [Planned]
- **Analytics** — dashboards and reports. [Planned]
- **Audit logs** — record of important actions. [Planned]

> **Implementation status note:** as of the date of this document, the codebase contains the
> **project foundation**: [Implemented] Laravel backend scaffold with Sanctum authentication,
> health endpoint, the Vue 3 frontend scaffold with an API client, the Docker development
> environment, and configured workflows. All module features listed above are **[Planned]** for
> the initial release; they have not yet been built.

---

## 13. Out-of-Scope Features

The following are **not** part of the initial project. They may be considered in future
versions but are explicitly excluded from the initial scope:

- **AI assistant** [Future]
- **Mobile application** [Future] *(the initial platform is a web application)*
- **Online payment gateway** [Out of Scope] *(see Section 23)*
- **Advanced timetable optimization** (full automatic scheduling algorithm) [Future]
- **Predictive analytics** (forecasting) [Future]
- **Microservice architecture** [Future] *(the project is a modular monolith — see Section 26)*
- **Advanced chat / messaging platform** [Future]
- **OCR** (automated text recognition from scanned documents) [Future]
- **Large-scale external university integrations** [Future]
- **Complex third-party ERP integrations** [Future]
- **Biometric attendance** [Future]
- **Advanced financial accounting** [Out of Scope] *(EduCore records invoices/payments; full accounting is outside scope)*
- **Full LMS replacement** [Out of Scope] *(EduCore covers core academic workflows; it is not a complete Learning Management System)*

These exclusions are deliberate scope boundaries, not failures. They keep the initial project
achievable, reliable, and maintainable.

---

## 14. Project Limitations

Limitations are **boundaries established to keep the project achievable and maintainable** —
not failures of the project.

### Technical Limitations

- EduCore is a web platform; it does not include native mobile apps in the initial scope.
- It is built as a modular monolith; it is not designed as microservices at the start.
- No OCR, biometrics, or AI features are included initially.

### Operational Limitations

- The platform supports workflow management; it cannot guarantee organizational adoption or
  that all processes change at once. Real benefits depend on consistent use.

### Data Limitations

- Data quality depends on the information provided by the university. The system can structure
  and validate data, but it cannot repair inaccurate historical records by itself.

### Integration Limitations

- Initial integrations are limited to email and Telegram notifications. There are no
  large-scale integrations with external university or ERP systems in the initial scope.

### Infrastructure Limitations

- The initial deployment is designed for Docker and Laravel Cloud. Institutions that require
  entirely custom on-premises infrastructure may need additional setup work.

### Human Resource Limitations

- The project is developed by a two-person team (Rin Nairith & Yong Lyhor). Development capacity
  establishes the pace of the roadmap.

### Project Time Limitations

- The project is planned for approximately **4–6 months**. This dictates which features are in
  the initial release and which are deferred.

### Deployment Limitations

- Initial deployment is designed for the Laravel Cloud / Docker model. Specific institutional
  hosting requirements may need configuration beyond the default setup.

### Scope Limitations

- EduCore covers core academic and administrative workflows. It is not a complete accounting
  system, an LMS, or a campus-wide ERP. Full financial accounting and LMS features are outside
  the initial scope.

---

## 15. Assumptions

The following **assumptions** are recorded because the platform design depends on them. They
are distinguished from confirmed facts.

| Assumption | Status |
|---|---|
| Universities have access to basic internet infrastructure (for a web platform). | Assumption |
| Users (students, lecturers, administrators) have appropriate accounts and devices. | Assumption |
| Academic data is available in a structured form (programs, courses, student lists). | Assumption |
| University administrators will define roles and permissions appropriate to their institution. | Assumption |
| Universities provide accurate, valid academic information for the platform. | Assumption |
| Email and Telegram integrations will be available where notifications are required. | Assumption |
| Initial deployment may require institution-specific configuration (calendar, grading scale, naming). | Assumption |

**Confirmed facts (as of this document):**

- The project foundation exists: Laravel backend scaffold, Vue frontend scaffold, Docker
  development environment, and CI workflows are [Implemented] in the repository.
- The target architecture is a modular monolith with the stated technology stack.

---

## 16. Business Benefits

Benefits are described with measured wording — "can help," "aims to," "expected to" — rather
than guaranteed outcomes.

### Administrative Benefits

EduCore can help reduce repeated data entry and manual record-keeping by storing each piece of
information once in a consistent structure. [Planned]

### Academic Benefits

It aims to make the academic structure (programs, courses, sections) clear and reusable every
semester, supporting consistent course planning. [Planned]

### Student Benefits

Students are expected to have one portal for their timetable, attendance, assignments, exams,
grades, documents, and announcements, reducing routine office visits. [Planned]

### Lecturer Benefits

Lecturers are expected to manage only the sections assigned to them, with organized workflows
for attendance, assignments, exams, and grades. [Planned]

### Management Benefits

Management is expected to gain visibility through dashboards and reports derived from
centralized data. [Planned]

### Communication Benefits

Centralized announcements with email and Telegram channels can help ensure important
information reaches the right audience. [Planned]

### Data Benefits

A single source of academic and administrative data can help reduce duplication and
inconsistency. [Planned]

### Security Benefits

Role-based access and audit logs are expected to improve control over who can see and change
what. [Planned]

### Reporting Benefits

Reports based on consistent data can help answer common management questions more quickly than
manual counting. [Planned]

---

## 17. Expected Outcomes

If the proposed platform is delivered as designed, the project expects:

- **Centralized academic information** — a single consistent record of the university structure and its people. [Planned]
- **Standardized workflows** — routine processes (enrollment, documents, attendance, grades) guided consistently. [Planned]
- **Improved information accessibility** — students, lecturers, and administrators find what they need in one place. [Planned]
- **Better record traceability** — document and workflow statuses are visible. [Planned]
- **Better communication** — announcements and notifications reach defined audiences. [Planned]
- **Digital document verification** — QR-based authenticity checks. [Planned]
- **Improved reporting** — dashboards and reports from centralized data. [Planned]
- **Role-based access** — each user sees only what their role requires. [Planned]
- **Auditable administrative actions** — audit logs cover important actions. [Planned]

Expected outcomes describe the intent of the design; they are realized over the course of
development, testing, and adoption.

---

## 18. Non-Functional Requirements

Beyond features, the platform will be held to quality standards understandable to
stakeholders.

- **Security** — secure authentication, role-based access, encrypted handling of passwords,
  validated inputs, and controlled file access. [Requirement]
- **Performance** — common operations (login, lists, dashboards) should respond promptly;
  heavy work such as notifications is handled asynchronously. [Requirement]
- **Availability** — the platform is designed to run reliably in the Docker/Laravel Cloud
  environment with health checks and predictable startup. [Requirement]
- **Scalability** — the modular monolith can grow within one deployable application; components
  (cache, queues, storage) are separable services. [Requirement]
- **Maintainability** — a clear code structure (Laravel + Vue), consistent conventions, and a
  documented architecture keep the codebase maintainable by a small team. [Requirement]
- **Usability** — interfaces are designed around the five roles' actual tasks, with consistent
  navigation and clear statuses. [Requirement]
- **Accessibility** — the interface will follow reasonable accessibility practices for web
  applications. [Requirement]
- **Data Integrity** — database constraints, validation, and consistent relationships protect
  record quality. [Requirement]
- **Auditability** — important administrative and academic actions are logged. [Requirement]

---

## 19. Security and Data Governance

Security is a core requirement because EduCore handles academic records. The platform will
apply the following controls:

- **Authentication** — users log in with their credentials; sessions and tokens are managed
  securely (Laravel Sanctum for API authentication). [Planned]
- **Role-based access** — five defined roles restrict what each account can do. [Planned]
- **Permission enforcement** — authorization checks are enforced on the server, not only hidden
  in the interface. [Planned]
- **Password protection** — passwords are stored hashed, never in plain text. [Planned]
- **Data validation** — inputs are validated before being stored. [Planned]
- **Audit logs** — important actions (grade changes, document approvals, payment records,
  authorization changes, login failures) are logged. [Planned]
- **File security** — uploaded files are validated and access-controlled, stored in MinIO.
  [Planned]
- **Secret management** — credentials live in environment configuration, never in the code or
  documentation. [Implemented convention]
- **Access control** — students see only their own records; lecturers only their own sections.
  [Planned]
- **Data integrity** — database constraints and consistent relationships protect record
  quality. [Planned]

> **Note on compliance:** this document does not claim legal compliance (e.g., data protection
> certification) unless and until it is actually established by the institution. EduCore
> applies the technical controls above, but formal compliance will need institutional review.

---

## 20. Data and Information Management

EduCore's value rests on a centralized data model. The main information categories are:

- **Users & Roles** — accounts, roles, and permissions.
- **Students & Lecturers** — profiles, status, and assignments.
- **Faculties & Departments** — the organizational structure.
- **Programs & Courses** — programs of study, the course catalog, and prerequisites.
- **Academic Years & Semesters** — the academic calendar.
- **Sections** — concrete classes per offering/semester.
- **Enrollments** — who is in which section.
- **Attendance — daily class records.**
- **Assessments (Assignments & Exams)** — tasks, submissions, exams, and results.
- **Grades & GPA** — scores, letter grades, and calculated GPAs.
- **Documents** — requests, generated documents, and verification records.
- **Invoices & Payments** — financial records and statuses.
- **Internships** — companies, opportunities, applications, reports, evaluations.
- **Notifications** — announcements and delivery records.
- **Audit Logs** — records of important actions.

This centralized structure means a fact is recorded once and reused many times: a student is
recorded once and linked to enrollments, attendance, assignments, exams, grades, invoices, and
documents. That consistency is what makes reporting and verification reliable.

---

## 21. Reporting and Analytics

The initial platform will support reporting in the following areas, derived from centralized
data:

- **Student statistics** — totals by status (active, inactive, suspended, graduated, withdrawn).
- **Enrollment statistics** — students per program, per department, per semester.
- **Attendance reports** — attendance percentages per course and per student.
- **Grade reports** — score and grade distributions.
- **GPA reports** — GPA distributions and academic performance summaries.
- **Course statistics** — enrollments, pass rates, and attendance per course.
- **Faculty/department statistics** — size and activity per unit.
- **Internship statistics** — applications, statuses, and completions.
- **Administrative reports** — pending requests, documents generated, outstanding invoices.

These reports describe what *has happened* in the institution. The initial scope does **not**
include predictive analytics or forecasting.

---

## 22. Document Management and Verification

### Document Requests

Students will be able to request official documents digitally — for example enrollment
certificates, student certificates, academic transcripts, academic results, and internship
letters. Each request follows a status path: requested → reviewed → approved/rejected →
generated → downloaded. [Planned]

### Document Generation & Storage

Approved documents are generated from the student's centralized academic data and stored
securely (in MinIO object storage). [Planned]

### Document Status & Traceability

Every request has a visible status, so students and administrators know where it stands.
[Planned]

### QR-Based Verification

Generated documents carry a **unique QR code**. Anyone with the document — an employer, a
seal-issuing body, another institution, or the graduate — can scan the code to reach a
verification page that confirms whether the document is **valid**, was generated by the
platform, and matches its recorded details.

In simple terms: **QR verification is a digital stamp of authenticity.** It lets a third party
confirm "yes, this document came from the university's system" without calling an office or
comparing paper records. [Planned]

---

## 23. Payment and Invoice Management

EduCore manages **financial records**, not financial processing.

**In the initial scope, EduCore provides:**

- **Invoices** — creation with items, amounts, and due dates.
- **Invoice items** — line items on an invoice.
- **Payment records** — amount, date, method, and reference.
- **Payment status** — pending, partially paid, paid, overdue, cancelled.

**What EduCore initially does NOT include:**

- An **online payment gateway** for paying through the platform. [Out of Scope]

The important distinction: **payment record management** (tracking invoices and recording that
a payment happened) is in scope. **Online payment processing** (collecting money electronically
through the platform) is out of scope for the initial release and may be considered in a future
version.

There is no dedicated Finance Officer role in the initial system. University Admins manage
invoice and payment records.

---

## 24. Communication

EduCore's communication layer combines in-platform announcements with outbound notifications.

### Announcements

Authorized users publish announcements targeted to specific audiences (all students, a faculty,
a department, a program, a class, or a course). [Planned]

### Email Notifications

Used for important or formal messages: announcements, document status changes, registration
confirmation, password-related messages, and administrative notifications. [Planned]

### Telegram Notifications

Used as a supplementary, near-real-time channel for announcements, class reminders, assignment
reminders, and important academic notifications. [Planned]

### Notification Preferences

Notification delivery respects the user's configured preferences where supported, and the
architecture is designed so additional channels can be added later. [Planned]

### Examples of notified events

- University/administrative announcements.
- Academic updates (registration windows, examination schedules).
- Assignment published/deadline reminders.
- Examination schedule changes.
- Document request status changes.

---

## 25. Internship Management

EduCore includes a university internship workflow to support students and administrators.

- **Internship companies** — registered company information. [Planned]
- **Internship opportunities** — published positions students can apply to. [Planned]
- **Applications** — student applications and status. [Planned]
- **Reports** — student internship reports. [Planned]
- **Evaluations** — supervisor and final evaluations. [Planned]
- **Internship status** — tracking from application through completion. [Planned]

This supports students by giving them a clear view of opportunities and their internship
progress, and supports administrators by keeping the internship process structured and
traceable in one place. [Planned]

---

## 26. Project Architecture Overview

A simple, non-technical view of how EduCore is built:

```
User's Browser
      │
      ▼
   Frontend (Vue.js)            → the screens users see and interact with
      │
      │  requests (API)
      ▼
   Backend (Laravel)            → the "brain": rules, logic, and security
      │
      ├──────────────┬──────────────┬───────────────┐
      ▼              ▼              ▼               ▼
  PostgreSQL    Redis         MinIO           Email / Telegram
  (main data)   (speed &      (files: docs,   (notifications)
                queues)        images)
```

- **Nginx** is the single entry point that directs web traffic.
- **Docker** packages all of these components so they run consistently.
- **GitHub** hosts the code; **GitHub Actions** runs automated checks and notifications.
- **Laravel Cloud** is the designed deployment target.

The application uses a **modular monolith** architecture: it is one application, organized
into clear modules (students, courses, enrollments, documents, payments…), deployed simply.
This is the opposite of microservices. Modules can be improved independently *inside* one
codebase, with the option to split out later if scale ever justifies it. [Architecture decision]

---

## 27. Technology Stack

| Layer | Technology | Purpose |
|---|---|---|
| Backend framework | **Laravel 12** | Application logic, API, validation, authorization, queues, notifications |
| Backend language | **PHP** | Server-side implementation language supported by Laravel |
| Frontend framework | **Vue 3** | Interactive user interface |
| Frontend language | **JavaScript** | Inertia.js + Vue 3; typed, safer backend contracts through the API |
| Styling | **Tailwind CSS** | Consistent, efficient styling |
| Frontend state | **Pinia** | Organized client-side state management |
| Frontend routing | **Vue Router** | Page navigation in the SPA |
| HTTP client | **Axios** | Reliable API requests from the browser |
| Charts | **Chart.js** | Dashboards and reports |
| Database | **PostgreSQL** | Primary relational data store |
| Cache & queues | **Redis** | Fast cache, session/queue support |
| Object storage | **MinIO** (S3-compatible) | File and document storage |
| Web server | **Nginx** | Single entry point / reverse proxy |
| Containers | **Docker** | Consistent local and deployable environment |
| API authentication | **Laravel Sanctum** | Secure token-based logins |
| Code hosting | **GitHub** | Version control and collaboration |
| CI/CD | **GitHub Actions** | Automated tests, builds, and notifications |
| Deployment | **Laravel Cloud** | Targeted production hosting |
| Email | Email provider (via Laravel notifications) | Formal email delivery |
| Telegram | Telegram Bot API | Supplementary notifications |

**Why these choices, at a high level:** Laravel + Vue provide a proven, maintainable
full-stack combination well suited to a small team; PostgreSQL is a reliable relational
database for structured academic data; Redis keeps the app fast and handles background work;
MinIO gives S3-compatible object storage for documents; Docker provides a consistent
environment from development to deployment; GitHub/GitHub Actions automate quality checks; and
Laravel Cloud is the designed production target.

---

## 28. Development Roadmap

The project is planned for approximately **4–6 months**, organized into six monthly phases.
Dependencies between features are respected: structure before people, people before courses,
courses before enrollment, and so on.

### Month 1 — Foundation & Architecture

- **Goals:** working application skeleton, development environment, and conventions.
- **Major activities:** requirements refinement, database schema design, project setup,
  authentication foundation, and Docker environment for all services.
- **Expected deliverables:** running web platform in Docker, authentication foundation, and
  documented architecture. [Implemented foundation]

### Month 2 — University Structure & People

- **Goals:** organizational structure and person records.
- **Major activities:** faculties, departments, programs, academic years, semesters, courses;
  student and lecturer management.
- **Expected deliverables:** the academic structure and the people linked to it.

### Month 3 — Academic Core

- **Goals:** the heart of the academic engine.
- **Major activities:** course offerings and sections, enrollment, timetable with conflict
  detection, and attendance.
- **Expected deliverables:** students registered in sections with schedules and attendance.

### Month 4 — Examination & Administration

- **Goals:** assessment and administrative records.
- **Major activities:** assignments, examinations, grades, GPA; document requests, generation,
  and QR verification; invoices and payment records.
- **Expected deliverables:** the assessment cycle plus document and financial records.

### Month 5 — Communication, Analytics & Internship

- **Goals:** communication and visibility.
- **Major activities:** announcements, email and Telegram notifications, dashboards and
  reports, dashboard analytics, internship management, audit logs.
- **Expected deliverables:** communication, reporting, and internship workflows.

### Month 6 — Testing, Security & Production Hardening

- **Goals:** verified, deployable platform.
- **Major activities:** comprehensive testing (unit, feature, API, permission, security),
  performance review, production configuration, deployment documentation, final audit.
- **Expected deliverables:** tested platform, deployment configuration, and complete
  documentation.

---

## 29. Project Deliverables

The project will produce:

- **Working web platform** — the full application.
- **Database schema & ERD** — the data model and its entity-relationship diagram.
- **API** — the documented backend interface.
- **Frontend** — the Vue application.
- **Authentication** — secure login for all roles.
- **RBAC** — role and permission enforcement.
- **Academic modules** — structure, people, courses, enrollment, schedule, attendance.
- **Administrative modules** — exams, grades, documents, invoices, payments, announcements.
- **Documentation** — project reports, API reference, architecture documentation.
- **Test suite** — automated tests at multiple levels.
- **Docker environment** — reproducible local environment. [Implemented]
- **CI/CD configuration** — automated checks and notifications. [Implemented]
- **Deployment configuration** — production setup for Laravel Cloud.
- **User documentation** — guidance for each user role.
- **Technical documentation** — developer guidance and conventions.

---

## 30. Project Team

- **Rin Nairith**
  - Full-stack development
  - Architecture
  - Backend/frontend implementation
  - DevOps/infrastructure
  - Documentation

- **Yong Lyhor**
  - Full-stack development
  - Frontend/backend implementation
  - Testing
  - UI/UX collaboration
  - Documentation

Both developers review each other's work rather than working in fully separate silos.

---

## 31. Project Risks

| Risk | Impact | Likelihood | Mitigation |
|---|---|---|---|
| Scope expansion (features creep) | High | Medium | Fixed in-scope definition; out-of-scope items moved to future roadmap |
| Changing requirements mid-project | Medium | Medium | Documented requirements and phased roadmap; changes reviewed against scope |
| Limited development resources (two-person team) | Medium | Medium | Prioritized roadmap; simpler stack; avoid over-engineering |
| Integration problems (email/Telegram/cloud) | Low | Medium | Early setup of integration accounts; fallback configurations documented |
| Data quality (existing records) | Medium | Medium | Structured imports; validation; clear data-entry guidance |
| Security issues | High | Low | Security review, validation, RBAC enforcement, audit logs, tests |
| Deployment issues | Medium | Low | Early Docker environment; documented deployment; CI checks |
| Timeline pressure | Medium | Medium | Realistic scope; features deferred rather than rushed |
| Insufficient testing | High | Low | Test suite across backend/frontend; CI gates |
| Infrastructure limitations (hosting/network) | Medium | Low | Compliance with documented hosting model; fallback configuration |

Risk levels are estimates, not guarantees. The primary mitigation strategy is **scope control:
deliver a reliable core first, defer enhancement.**

---

## 32. Success Criteria

The project is considered successful when:

- Core academic and administrative workflows function correctly. [Planned]
- Users can authenticate securely. [Planned]
- RBAC works correctly across all five roles. [Planned]
- Academic records are managed consistently. [Planned]
- The enrollment workflow works (with validation). [Planned]
- Attendance works and produces percentages. [Planned]
- Examination and grading workflows work, including GPA calculation. [Planned]
- Documents can be requested, generated, and verified with QR codes. [Planned]
- Notifications function via email and Telegram. [Planned]
- Reports and dashboards function. [Planned]
- Audit logs record important actions. [Planned]
- The system passes the defined test suite. [Planned]
- The Docker environment works reproducibly. [Implemented]
- The deployment process is documented. [Planned]

---

## 33. Future Expansion

The items below are **future possibilities**, not part of the current scope. They may be
considered in later versions depending on adoption and requirements.

- **Mobile application** — a companion native or PWA experience.
- **AI assistant** — assisted guidance and support within the platform.
- **Online payments** — a payment gateway so students can pay through the platform.
- **Advanced analytics** — deeper, self-service analytics.
- **Predictive analytics** — forecasting of academic outcomes.
- **Advanced timetable optimization** — automatic conflict-free scheduling algorithms.
- **OCR** — automated recognition of scanned documents.
- **Biometric attendance** — attendance identity verification.
- **LMS integrations** — richer learning-management connections.
- **University integrations** — data exchange with other institutions.
- **More communication features** — additional channels and richer messaging.
- **Advanced accounting** — fuller financial/accounting functionality.
- **Multi-university SaaS capabilities** — serving multiple institutions from one platform.

These are labeled clearly as **future possibilities**. They do not expand the current scope.

---

## 34. Conclusion

University academic and administrative work is often spread across spreadsheets, paper,
disconnected systems, and manual processes. This leads to duplicated data, limited visibility,
slow verification, and repetitive administration.

EduCore proposes a centralized, secure web platform designed for the academic and
administrative core of a university — from the university structure and its people, through
courses, enrollment, schedules, attendance, assignments, examinations, grades, and GPA, to
documents, financial records, communication, internships, reports, and audit trails.

The initial scope is deliberately controlled: core workflows are in scope; online payments,
AI, mobile apps, and large-scale integrations are explicitly deferred. The architecture is a
modular monolith built on Laravel and Vue.js, running under Docker, targeted at Laravel Cloud,
developed by a two-person team over approximately 4–6 months.

The project does not promise to replace every university system. It aims to provide a reliable,
consistent, and maintainable foundation for the most important academic and administrative
processes — and a platform that can grow into a broader ecosystem in future versions.

---

## 35. Glossary

| Term | Meaning |
|---|---|
| **EduCore** | The proposed university digital administration platform described in this document. |
| **RBAC (Role-Based Access Control)** | A way of controlling what each user can do based on their role (e.g., Lecturer vs. Student). |
| **Faculty** | A major academic division within a university (e.g., Faculty of Engineering). |
| **Department** | An academic unit within a faculty (e.g., Department of Computer Science). |
| **Program** | A program of study leading to a degree (e.g., Bachelor of Computer Science). |
| **Course** | A structured subject in the catalog (e.g., Software Engineering, 3 credits). |
| **Course Offering** | One course taught in a particular semester. |
| **Section** | A concrete class instance of an offering (Section A, B… with a lecturer, room, and schedule). |
| **Enrollment** | A student's registration in a section for a semester. |
| **Academic Year** | The institutional year (e.g., 2026–2027). |
| **Semester** | A teaching period within an academic year. |
| **GPA** | Grade Point Average — a standardized number summarizing academic performance. |
| **Transcript** | An official record of a student's academic history and results. |
| **Invoice** | A record of an amount owed (with items, due date, and status). |
| **Payment Record** | A record that a payment was made (amount, date, method, reference). |
| **QR Verification** | A digital authenticity check: scanning a QR code confirms a document was generated by the platform. |
| **Modular Monolith** | One application, built in clear modules, deployed together — the opposite of microservices. |
| **API** | The interface the frontend uses to talk to the backend; sends and receives data. |
| **MinIO** | S3-compatible object storage used to store files and documents. |
| **Redis** | An in-memory store used for cache and background queues. |
| **Laravel** | The PHP backend framework at the core of EduCore. |
| **Vue.js** | The JavaScript framework used to build the user interface. |
| **Docker** | Technology that packages the whole platform so it runs consistently. |
| **GitHub Actions** | Automated workflows for testing and notifications. |
| **Laravel Cloud** | The designed production hosting target for the platform. |

---

*End of document.*

*Report created: September 2026.*