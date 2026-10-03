# EduCore — Project & Design Report

| | |
|---|---|
| **Project** | EduCore — University Digital Administration Platform |
| **Target market** | Designed for Cambodian university environments |
| **Architecture** | Modular Monolith (MVC backend) |
| **Development team** | Rin Nairith & Yong Lyhor |
| **Status** | All business-overview §9 modules [Implemented] (2026-10-02); remaining gaps listed per module and in `docs/6_Module-Status-and-Roadmap.md` §6 |
| **Report version** | 2.1 (status refreshed 2026-10-02 after modules 9.1–9.25) |

> **Reading notes**
> - **Status labels** are used throughout: `[Implemented]`, `[Planned]`,
>   `[Future]`, `[Out of Scope]`. Planned functionality is never described as if
>   it already exists.
> - This report is the technical/product companion to the business proposal
>   `docs/3_business-overview.md`.

---

## 1. Executive Summary

EduCore is a centralized digital administration platform for universities, designed
initially for Cambodian university environments. It consolidates academic and
administrative workflows — student and lecturer management, academic structure,
course enrollment, the assessment cycle, documents, financial records,
communication, internships, and reporting — into one secure web platform.

Many institutions operate with spreadsheets, paper documents, disconnected systems,
and manual approval processes. EduCore proposes to reduce fragmentation by giving each
role a single, secure place to access the information and services they need.

The system is built around five roles and one central academic workflow:

```mermaid
flowchart LR
    A[University] --> B[Faculty]
    B --> C[Department]
    C --> D[Program]
    A --> E[Academic Year]
    E --> F[Semester]
    D --> G[Course]
    G --> H[Course Offering]
    H --> I[Section]
    I --> J[Lecturer]
    I --> K[Student]
    K --> L[Enrollment]
    I --> M[Attendance]
    I --> N[Assignment]
    I --> O[Exam]
    O --> P[Grade]
    P --> Q[GPA]
    Q --> R[Transcript / Document]
```

**Major capabilities (initial release):** authentication & RBAC, student/lecturer
management, academic structure, courses & sections, enrollment, timetable, attendance,
assignments, examinations, grades & GPA, documents with QR verification, invoice &
payment records, announcements, email & Telegram notifications, internship management,
analytics, and audit logs.

**Implementation status:** the **foundation is implemented** — Laravel 12 backend
scaffold with Sanctum authentication and health endpoint, Vue 3 + Inertia
(JavaScript) frontend scaffold with an API client, a complete Docker development
environment, and CI/CD workflows. **All business modules are planned** for the
initial release.

---

## 2. Project Vision

EduCore aims to provide a centralized digital environment where a university's academic
and administrative activities can be managed through one secure platform.

```mermaid
flowchart TB
    subgraph Users
        S[Student]
        L[Lecturer]
        A[Administrators]
        M[Management]
    end
    subgraph EduCore[EduCore Platform]
        Academic[Academic Services]
        Docs[Document Services]
        Comm[Communication]
        Report[Analytics & Reports]
    end
    subgraph Data
        P[(PostgreSQL)]
        R[(Redis)]
        MO[(MinIO)]
    end
    S --> Academic
    L --> Academic
    A --> Docs
    A --> Comm
    M --> Report
    Academic --> P
    Docs --> MO
    Comm --> R
    Report --> P
```

### Current pattern vs. proposed pattern

```mermaid
flowchart LR
    subgraph Today[Today]
        T1[Paper documents] 
        T2[Spreadsheets] 
        T3[Messaging apps] 
        T4[Separate systems]
    end
    subgraph EduCore[EduCore]
        E1[One secure platform] 
        E2[Students / Lecturers / Admins]
    end
    T1 -. fragmented .-> X[manual work, limited visibility]
    T2 -. fragmented .-> X
    T3 -. fragmented .-> X
    T4 -. fragmented .-> X
    X -.->|replaces| E1
    E1 --> E2
```

The vision is deliberately practical: it does not promise to replace every university
system; it consolidates the most important workflows first.

---

## 3. Problem Statement

Universities manage large amounts of academic and administrative information. Common
problems include:

| Problem area | Current challenge | Operational consequence |
|---|---|---|
| **Information fragmentation** | Student, course, attendance, grade, and document data live in different systems/files | Inconsistent records and repeated re-entry |
| **Manual processes** | Students visit offices for documents, registration, approvals | Slow turnaround, heavy staff workload |
| **Limited visibility** | Management lacks timely enrolment/attendance/performance views | Decisions rely on delayed information |
| **Communication** | Announcements spread across informal channels | Some students/staff miss important information |
| **Document management** | Documents prepared and verified manually | Slow, hard-to-verify documents |
| **Scheduling conflicts** | Manual timetable creation | Lecturer/room/section conflicts |
| **Fragmented student services** | Students use several channels for courses, schedules, grades, documents | Poor student experience |

EduCore addresses these through centralized data, structured workflows, role-based
access, and integrated communication.

---

## 4. Project Objectives

### 4.1 General Objective

Develop a centralized digital university administration platform that improves academic
management, administrative workflows, communication, and student services.

### 4.2 Specific Objectives

1. Centralize university academic information. [Implemented]
2. Digitize student and lecturer management. [Implemented]
3. Manage faculties, departments, programs, courses, and sections. [Implemented]
4. Provide students with a centralized academic portal. [Implemented]
5. Provide lecturers with tools for attendance, assignments, exams, and grades. [Implemented]
6. Provide administrators with centralized management tools. [Implemented]
7. Digitize document request workflows with QR verification. [Implemented] (verification by printed code / URL; a scannable QR image is [Future])
8. Provide invoice and payment-record management. [Implemented]
9. Improve communication through email and Telegram notifications. [Implemented]
10. Provide academic analytics and reporting. [Implemented] (CSV exports; PDF exports [Future])
11. Support the internship workflow. [Implemented]
12. Build a modular-monolith architecture that supports future expansion. [Implemented]

---

## 5. Target Users and Roles

EduCore uses **role-based access control** with five system roles.

```mermaid
flowchart TB
    R[System Roles — RBAC]
    R --> SA[Super Admin]
    R --> UA[University Admin]
    R --> FA[Faculty / Department Admin]
    R --> LE[Lecturer]
    R --> ST[Student]
```

| Role | Responsibility | Typical capabilities |
|---|---|---|
| **Super Admin** | Platform owner | User/role/permission management, university configuration, audit logs, monitoring |
| **University Admin** | University-level administration | Faculties, departments, programs, students, lecturers, courses, semesters, announcements, documents, payment records, reports |
| **Faculty / Department Admin** | Unit-level academic operations | Their unit's students, lecturers, courses, sections, schedules; monitor attendance/performance; review requests (implemented: read-only, limited to the assigned faculty) |
| **Lecturer** | Teaching management | View assigned sections, take attendance, create assignments, manage exams, submit grades, publish course announcements, upload materials |
| **Student** | Own academic life | View profile/academic info, register courses, view timetable/attendance/assignments/exams/grades/GPA, request documents, view invoices/payments, receive announcements |

**Isolation rules:** students see only their own records; lecturers only their assigned
sections; unit admins only their unit. Authorization is enforced server-side.

> There is **no Finance Officer role** in the initial system. Invoices and payment
> records are managed by University Admins.

---

## 6. Academic Structure

The platform models the university hierarchy precisely, with canonical terms used
consistently across code, docs, and skills.

```mermaid
flowchart TB
    U[University]
    U --> FA[Faculty]
    FA --> DE[Department]
    DE --> PR[Program]
    U --> AY[Academic Year]
    AY --> SE[Semester]
    PR --> CO[Course]
    CO --> OF[Course Offering]
    OF --> SEC[Section]
    DE --> CO
    SE --> OF
```

**Example instance:**

```mermaid
flowchart TB
    U[University]
    U --> FE[Faculty of Engineering]
    FE --> CS[Department of Computer Science]
    CS --> BCS[Bachelor of Computer Science]
    AY[Academic Year 2026-2027] --> S1[Semester 1]
    BCS --> DB[Database Systems]
    BCS --> WD[Web Development]
    BCS --> SE[Software Engineering]
    DB --> DBA[Section A]
    DB --> DBB[Section B]
    S1 --> DBA
```

---

## 7. Core Database Entities

The database is organized around the academic model. A simplified ERD:

```mermaid
erDiagram
    USERS ||--o{ STUDENTS : "is a"
    USERS ||--o{ LECTURERS : "is a"
    USERS ||--o{ AUDIT_LOGS : "performs"
    ROLES ||--o{ USERS : "assigns"

    FACULTIES ||--o{ DEPARTMENTS : "has"
    DEPARTMENTS ||--o{ PROGRAMS : "offers"
    PROGRAMS ||--o{ COURSES : "includes"
    COURSES ||--o{ COURSE_PREREQUISITES : "has requirements"
    COURSES ||--o{ COURSE_OFFERINGS : "is offered as"
    COURSE_OFFERINGS ||--o{ SECTIONS : "contains"
    LECTURERS ||--o{ SECTION_LECTURERS : "teaches"
    SECTIONS ||--o{ SECTION_LECTURERS : "staffed by"
    SECTIONS ||--o{ ENROLLMENTS : "enrolls"
    STUDENTS ||--o{ ENROLLMENTS : "enroll into"
    SECTIONS ||--o{ SCHEDULES : "scheduled on"
    ROOMS ||--o{ SCHEDULES : "hosts"
    SECTIONS ||--o{ ATTENDANCE : "records"
    SECTIONS ||--o{ ASSIGNMENTS : "has"
    ASSIGNMENTS ||--o{ ASSIGNMENT_SUBMISSIONS : "receives"
    SECTIONS ||--o{ EXAMS : "schedules"
    EXAMS ||--o{ EXAM_RESULTS : "produces"
    SECTIONS ||--o{ GRADES : "issues"
    STUDENTS ||--o{ GRADES : "receives"

    STUDENTS ||--o{ DOCUMENT_REQUESTS : "requests"
    DOCUMENT_REQUESTS ||--o{ DOCUMENTS : "generates"
    DOCUMENTS ||--o{ DOCUMENT_VERIFICATIONS : "verified via"
    STUDENTS ||--o{ INVOICES : "owes"
    INVOICES ||--o{ PAYMENTS : "paid by"

    STUDENTS ||--o{ INTERNSHIPS : "applies to"
    INTERNSHIPS ||--o{ INTERNSHIP_REPORTS : "produces"
    INTERNSHIPS ||--o{ INTERNSHIP_EVALUATIONS : "evaluated by"

    USERS ||--o{ ANNOUNCEMENTS : "publishes"
    USERS ||--o{ NOTIFICATIONS : "receives"
```

The canonical relationship rules (see `skills/database/SKILL.md`):

- A course offering = a course × a semester.
- A section = a concrete class instance of an offering (lecturer, room, capacity, schedule).
- A student can enroll in a section only if prerequisites are satisfied and capacity allows.
- No duplicate enrollment in the same offering/semester.

---

## 8. System Modules (Overview)

```mermaid
flowchart TB
    subgraph Access[Access Layer]
        Auth[Authentication & RBAC]
    end
    subgraph Academic[Academic Core]
        SM[Student Management]
        LM[Lecturer Management]
        FD[Faculty & Department]
        PM[Program Management]
        AYS[Academic Year & Semester]
        CM[Course Management]
        SEC[Section Management]
        ENR[Enrollment]
        TT[Timetable]
        AT[Attendance]
        ASN[Assignments]
        EX[Examinations]
        GR[Grades & GPA]
        DASH[Student Academic Dashboard]
    end
    subgraph AdminAdmin[Administration]
        DOC[Document Management]
        VER[QR Document Verification]
        INV[Invoices & Payments]
        AMP[Announcements]
        NTF[Email & Telegram Notifications]
        INT[Internship Management]
        ANL[Analytics & Reporting]
        AUD[Audit Logs]
    end
    Auth --> Academic
    Auth --> AdminAdmin
```

All modules are `[Implemented]` as of 2026-10-02 (plus 9.25 System Error Logs,
an addition). Each has a numbered report in `docs/` (7–28); §9 below notes, per
module, what was deliberately left out.

---

## 9. Module Details

### 9.1 Authentication & Authorization [Implemented]

> **Status:** sign-in by email + password (web session and Sanctum API token), five roles enforced by route middleware and policies, inactive accounts refused and their sessions / tokens revoked (9.24). Password change and emailed reset — `docs/29_Password-Change-and-Reset-Report.md`. **Not built:** sign-in by student / staff ID [Future]; editable permission sets (roles are fixed) [Future]; two-factor sign-in [Future].

- **Login** with student ID / staff ID + password.
- **Logout, password change, password reset, session/account management.**
- **RBAC** with role and permission management.
- **Implementation:** Laravel Sanctum for API token authentication; permissions
  enforced server-side (`skills/authentication`, `skills/authorization`).

```mermaid
sequenceDiagram
    participant U as User
    participant A as Frontend (Vue)
    participant B as API (Laravel)
    participant P as PostgreSQL
    U->>A: Enter ID + password
    A->>B: POST /api/login
    B->>P: Verify credentials
    P-->>B: User + roles
    B-->>A: Sanctum token
    A->>U: Signed in (token stored)
    A->>B: GET /api/user (Bearer token)
```

### 9.2 Student Management [Implemented]

> **Status:** profiles, status lifecycle, program history — `docs/13_Student-Management-Report.md`. **Not built:** student photo [Future].

Manages: student ID, name, gender, DOB, contact, address, photo, program, department,
faculty, academic year, enrollment status.

```mermaid
flowchart LR
    ST[Student status] --> A[Active]
    ST --> I[Inactive]
    ST --> S[Suspended]
    ST --> G[Graduated]
    ST --> W[Withdrawn]
```

### 9.3 Lecturer Management [Implemented]

> **Status:** `docs/12_Lecturer-Management-Report.md`; section assignment with 9.8.

Manages: profile, employee info, department, academic position, assigned courses,
teaching schedules, contact, status.

### 9.4 Faculty & Department Management [Implemented]

> **Status:** `docs/7_Faculty-and-Department-Report.md`; programs `docs/10_Program-Management-Report.md`.

Manages faculties, departments, programs, and program structures (see §6).

### 9.5 Course Management [Implemented]

> **Status:** `docs/11_Course-Management-Report.md`.

Per course: code, name, description, credits, department, program, semester,
prerequisites, sections, schedule.

Example: `CS301 Software Engineering — 3 credits — Prerequisites: CS201, CS202`.

### 9.6 Academic Year & Semester Management [Implemented]

> **Status:** see `docs/5_Build-Steps-Report.md` and the roadmap.

Supports academic years, semesters, and enrollment/registration/examination periods.

```mermaid
flowchart TB
    AY[2026-2027] --> S1[Semester 1]
    AY --> S2[Semester 2]
    S1 --> E1[Enrollment period]
    S1 --> R1[Registration period]
    S1 --> X1[Examination period]
```

### 9.7 Section Management [Implemented]

> **Status:** `docs/14_Class-and-Section-Report.md`.

A course offering can have multiple sections (A, B, C…) each with lecturer, students,
room, schedule, capacity, and semester. Capacity is enforced at enrollment.

### 9.8 Course Registration / Enrollment [Implemented]

> **Status:** `docs/15_Enrollment-Report.md`.

```mermaid
flowchart TB
    Student[Student] --> View[View available sections]
    View --> Select[Select sections]
    Select --> Submit[Submit registration]
    Submit --> Val[Validation]
    Val -->|passes| Ok[Registration confirmed]
    Val -->|fails| Reject[Reason returned: prerequisite / duplicate / capacity / status]
```

Validation checks: course availability, prerequisites, duplicate registration,
semester, maximum credits, student status.

### 9.9 Timetable Management [Implemented]

> **Status:** `docs/16_Timetable-Report.md`.

```mermaid
flowchart LR
    subgraph Conflicts[Conflict detection]
        C1[Lecturer conflict]
        C2[Room conflict]
        C3[Student-group / section conflict]
    end
    TT[Timetable builder] --> Conflicts
```

### 9.10 Attendance Management [Implemented]

> **Status:** `docs/17_Attendance-Report.md`.

```mermaid
flowchart LR
    L[Lecturer] --> Mark[Mark session]
    Mark --> P2[Present / Absent / Late / Excused]
    P2 --> Calc[Percentages calculated]
    Calc --> Stu[Student views: e.g. 92%]
```

### 9.11 Assignment Management [Implemented]

> **Status:** `docs/18_Assignments-Report.md`. **Not built:** lecturer-attached materials [Planned].

- Lecturer: create assignments, set deadlines, upload files, view and grade submissions.
- Student: view assignments, download materials, submit work, view results.

### 9.12 Examination Management [Implemented]

> **Status:** `docs/19_Examinations-Report.md`; course weighting with 9.13.

Supports midterm, final, quizzes; exam schedules and results. Grading weights are
configurable by the university.

```mermaid
pie
    title Example weighting — Database Systems
    "Midterm" : 30
    "Final" : 40
    "Assignments" : 20
    "Attendance" : 10
```

### 9.13 Grades & GPA [Implemented]

> **Status:** `docs/20_Grades-and-GPA-Report.md`; finalize lock (Super Admin reopen with reason) in `docs/30_Grade-Finalization-and-Document-Templates-Report.md`.

```mermaid
flowchart LR
    Score[Score] --> LG[Letter Grade]
    LG --> GP[Grade Point]
    GP --> SGPA[Semester GPA]
    SGPA --> CGPA[Cumulative GPA]
```

Example scale (configurable): `A=4.0, B+=3.5, B=3.0, C+=2.5, C=2.0, D=1.0, F=0.0`.

### 9.14 Student Academic Dashboard [Implemented]

> **Status:** `docs/21_Student-Academic-Dashboard-Report.md`.

A single-page academic summary:

```mermaid
flowchart TB
    subgraph Dashboard[Student Dashboard]
        Cards[GPA · Attendance · Credits]
        Today[Today's classes]
        Up[R][Upcoming assignments]
        Ann[Announcements]
        Recent[Recent grades]
    end
```

### 9.15 Document Management [Implemented]

> **Status:** enrollment certificate, student certificate, transcript, semester result and internship letter as PDFs — `docs/22_Documents-and-Verification-Report.md`, `docs/30_Grade-Finalization-and-Document-Templates-Report.md`. **Not built:** document fees [Future].

Students request documents digitally (enrollment certificate, student certificate,
academic transcript, academic result, internship letter, others).

```mermaid
flowchart TB
    S[Student] --> Req[Document request]
    Req --> Rev[Administrator review]
    Rev -->|rejected| Back[Request returned with reason]
    Rev -->|approved| Gen[Document generated with QR]
    Gen --> DL[Student downloads]
```

### 9.16 Digital Document Verification [Implemented]

> **Status:** printed verification URL + code, public page with SHA-256 checksum — `docs/22_Documents-and-Verification-Report.md`. **Not built:** scannable QR image [Future].

Generated documents carry a unique QR code linking to a verification page.

```mermaid
flowchart LR
    Doc[Document] --> QR[QR Code]
    QR --> URL[Verification URL]
    URL --> Check[EduCore verification]
    Check --> V[Valid / Invalid]
```

### 9.17 Invoice & Payment Records [Implemented]

> **Status:** `docs/23_Invoices-and-Payments-Report.md`. **Not built:** invoice PDFs / receipts, automatic tuition billing [Future].

```mermaid
flowchart LR
    Stu[Student] --> Inv[Invoice]
    Inv --> Amt[Amount]
    Inv --> Due[Due date]
    Inv --> Stats[Status: Pending / Partially Paid / Paid / Overdue / Cancelled]
    Pay[Payment record: amount, date, method, reference] --> Inv
```

> **Boundary:** this records payments; it does **not** include an online payment
> gateway in the initial scope. (`skills/invoices-payments/SKILL.md`)

### 9.18 Announcement Management [Implemented]

> **Status:** `docs/24_Announcements-Report.md`. **Not built:** attachments, read receipts [Future].

Targets: all students, faculty, department, program, class, course.

```mermaid
flowchart LR
    Auth[Authorized user] --> Pub[Publish announcement]
    Pub --> Target[Target audience]
    Target --> NTF[Email / Telegram]
```

### 9.19 Notification System [Implemented]

> **Status:** email + Telegram, queued, with user preferences — `docs/25_Notifications-Report.md`. Password-reset and password-changed emails added with report 29. **Not built:** class-start reminders, in-app inbox [Future].

- **Email:** announcements, document status, registration confirmation, password-related,
  administrative notifications.
- **Telegram:** announcements, class reminders, assignment reminders, important
  academic notifications.
- Designed so additional channels can be added later.

### 9.20 Internship Management [Implemented]

> **Status:** `docs/26_Internship-Management-Report.md`. **Not built:** published opportunity postings [Future].

```mermaid
flowchart TB
    Stu[Student] --> App[Internship application]
    App --> Cmp[Company information]
    App --> Rev[University review]
    Rev --> Appr[Approval]
    Appr --> Int[Internship]
    Int --> Rep[Reports]
    Rep --> Eval[Supervisor evaluation]
    Eval --> Fin[Final evaluation]
```

### 9.21 Analytics & Reporting [Implemented]

> **Status:** `docs/27_Analytics-and-Reporting-Report.md`; CSV exports in `docs/31_CSV-Exports-Report.md`. **Not built:** PDF exports, faculty-scoped views, multi-semester trends [Future].

- Student analytics: total/active/graduated/withdrawn.
- Academic analytics: GPA distribution, course pass rate, attendance, course performance.
- Enrollment analytics: per program/department/semester.
- Administrative analytics: pending requests, documents generated, outstanding invoices,
  announcements.

Derived from centralized data. No predictive analytics in the initial scope.

### 9.22 Audit Logs [Implemented]

> **Status:** append-only in the application and the database, Super Admin viewer — `docs/28_Audit-Logs-and-Security-Report.md`. CSV export in `docs/31_CSV-Exports-Report.md`. **Not built:** retention [Future].

Append-only record of important actions (grade changes, document approvals, payment
records, authorization changes, login failures). Never logs secrets.
(`skills/audit-logging/SKILL.md`)

---

## 10. System Architecture

EduCore is a **modular monolith**: one application organized into clear modules,
deployed simply.

```mermaid
flowchart TB
    Client[Browser] --> Nginx[Nginx — single entry point]
    Nginx --> BE[Laravel backend — Inertia pages + REST API]
    BE -->|Inertia render| FE[Vue 3 + JavaScript frontend via Vite]
    BE --> PG[(PostgreSQL)]
    BE --> RD[(Redis — cache/queue/session)]
    BE --> MO[(MinIO — document/image storage)]
    BE --> NTF[Email / Telegram notifications]
    DevOps[GitHub · GitHub Actions · Docker · Laravel Cloud] -.-> Nginx
```

### 10.1 Backend layers

```mermaid
flowchart TB
    C[Controller] --> S[Service]
    S --> Repo[Repository]
    S --> Req[Form Request validation]
    Repo --> M[Model]
    M --> PG[(PostgreSQL)]
    Auth[Policies / RBAC] -. enforces .-> C
```

**Laravel MVC responsibilities:**

- **Model:** relationships, business entities, data representation.
- **Controller:** receive requests, call services, return responses — For
  page views, `Inertia::render()` (Vue components); for data, JSON Resources.
- **View:** Vue components rendered via Inertia (Laravel controls routing).

### 10.2 Frontend organization

```mermaid
flowchart TB
    SRC["frontend/src/"] --> FE2["components/ · layouts/ · pages/ · views/"]
    SRC --> ST["stores/ (Pinia)"]
    SRC --> SV["services/ (Axios api.js)"]
    SRC --> BOOT["app.js (Inertia createInertiaApp)"]
    SRC --> UX["composables/ · utils/"]
    SRC --> UI["styling (Tailwind CSS)"]
```

Organized by feature; never a single huge component collection.

---

## 11. Technology Stack

| Layer | Technology | Purpose |
|---|---|---|
| Backend | Laravel 12 (PHP 8.4) | API, services, validation, authorization |
| API auth | Laravel Sanctum | Token-based authentication |
| Frontend | Vue 3 + Inertia (JavaScript) + Vite | User interface |
| Styling | Tailwind CSS | Styling system |
| State | Pinia | Client state |
| Routing | Vue Router | Navigation |
| HTTP | Axios | API requests |
| Charts | Chart.js | Dashboards |
| Database | PostgreSQL | Primary data store |
| Cache/queue/session | Redis | Performance, background work |
| Object storage | MinIO (S3) | Files and documents |
| Web server | Nginx | Single entry point |
| Containers | Docker / Docker Compose | Consistent environment |
| CI/CD | GitHub Actions | Lint, tests, build, notifications |
| Deployment | Laravel Cloud | Production target |
| Notifications | Email provider + Telegram Bot API | Communication |

[Implemented] base: Laravel 12 scaffold, Sanctum, Vue scaffold, Docker stack (7
services healthy), CI/CD workflows.

---

## 12. Development Methodology

EduCore follows an **Agile** approach with short iterations.

```mermaid
flowchart LR
    Req[Requirement] --> US[User story]
    US --> DB[Database]
    DB --> API[API]
    API --> FE[Frontend]
    FE --> T[Tesing / review]
    T --> M[Merge]
```

---

## 13. Project Roadmap

The initial target is a **production-capable MVP in approximately 12 weeks**, continuing
into V1/V2 rather than delaying the first usable release. The overall program is planned
for approximately 4–6 months.

```mermaid
gantt
    title EduCore initial roadmap (MVP ~12 weeks)
    dateFormat  YYYY-MM-DD
    section Foundation
    Research, requirements, architecture   :a1, 2026-01-01, 7d
    Database, UI, project foundation       :a2, after a1, 7d
    section Structure
    Authentication + RBAC                 :b1, after a2, 7d
    University structure                   :b2, after b1, 7d
    Students + lecturers                   :b3, after b2, 7d
    section Academic core
    Courses + sections                     :c1, after b3, 7d
    Enrollment + timetable                  :c2, after c1, 7d
    Attendance + assignments                :c3, after c2, 7d
    Exams + grades + GPA                    :c4, after c3, 7d
    section Administration
    Documents + invoices + payments         :d1, after c4, 7d
    Notifications + dashboards             :d2, after d1, 7d
    Testing + deployment                    :d3, after d2, 7d
```

**Month-by-month view (4–6 month program):**

| Month | Focus |
|---|---|
| Month 1 | Foundation & architecture |
| Month 2 | University structure & people |
| Month 3 | Academic core (sections, enrollment, timetable, attendance) |
| Month 4 | Examinations, grades, documents, financial records |
| Month 5 | Communication, analytics, internship, audit |
| Month 6 | Testing, security, production hardening |

**Progress (2026-10-03):** every module of business-overview §9 is
implemented and tested (404 feature tests), documented in reports 7–34, with
all 216 API operations in the OpenAPI document. Faculty / Department Admins
read their assigned faculty (`docs/32_Faculty-Admin-Scoping-Report.md`),
process its students' document requests and internships
(`docs/33_Faculty-Admin-Request-Handling-Report.md`) and see what waits for
them on their dashboard (`docs/34_Faculty-Admin-Dashboard-Report.md`); sections
and schedules for their unit remain open — see `docs/6_Module-Status-and-Roadmap.md` §6.

---

## 14. Git & GitHub Workflow

- Branches: `main`, `develop`, `feature/<module>-<name>`, `fix/*`, `refactor/*`, `docs/*`, `chore/*`.
- **Commit format: `[Tag]: description.`** — see `skills/git-commit-style/SKILL.md`
  (tags: `[Build]`, `[Doc]`, `[Feature]`, `[Fix]`, `[Refactor]`, `[Test]`, `[Style]`, `[Chore]`).
- Workflow:

```mermaid
flowchart LR
    FB[Feature branch] --> Dev[Development]
    Dev --> PR[Pull request]
    PR --> CR[Code review]
    CR --> Merge[Merge → develop]
    Merge --> Test[Testing]
    Test --> Rel[Release → main]
```

Two developers (Rin + Yong Lyhor) review each other's work.

**CI/CD:**

```mermaid
flowchart LR
    Push[Push / PR] --> CI[GitHub Actions]
    CI --> Pipeline[Backend: pint + phpunit · Frontend: vite build]
    Pipeline --> Notify[Telegram notification]
```

---

## 15. Security Requirements

Security is a major requirement because EduCore handles academic records.

- Authentication (Sanctum), RBAC, permission-based authorization.
- Password hashing; secrets only in environment config (never committed).
- Input validation, file validation, rate limiting.
- Audit logs; secure document access.
- Database constraints and access policies.
- Isolation: students see only own data; lecturers only own sections.

```mermaid
flowchart TB
    Access[User request] --> Auth2[Authenticate]
    Auth2 --> Authz[Authorize by role]
    Authz --> Validate[Validate input]
    Validate --> Execute[Execute — audit logged]
    Audit[(audit_logs)]
    Execute -. important action .-> Audit
```

---

## 16. Scope Definitions

### 16.1 In scope (initial release)

Authentication, RBAC, student/lecturer management, academic structure, courses,
sections, enrollment, timetable, attendance, assignments, exams, grades, GPA,
student dashboard, documents + QR verification, invoices + payment records,
announcements, email + Telegram, internship, analytics, audit logs.

### 16.2 Planned but deferred

Advanced timetable optimization, course prerequisite engine, digital signatures,
advanced document workflows, scheduled announcements, advanced dashboards.

### 16.3 Out of scope

AI assistant, mobile application, online payment gateway, predictive analytics,
microservices, advanced chat system, OCR, large-scale external university
integrations, complex ERP integrations, biometric attendance, advanced financial
accounting, full LMS replacement.

The goal is a reliable core system first.

---

## 17. Deliverables

- Working web platform (Laravel API + Vue frontend).
- Database schema and ERD.
- Authentication and RBAC.
- Academic and administrative modules.
- Docker environment and CI/CD configuration.
- Deployment configuration (Laravel Cloud).
- Test suite (backend PHPUnit, Pint; frontend typecheck/build).
- User documentation and technical documentation.

---

## 18. Testing Strategy

| Level | Coverage |
|---|---|
| Backend | Model, service, API feature, authorization tests |
| Frontend | Component, form validation, navigation, permission-based UI |
| Integration | Full academic workflow end-to-end |

```mermaid
flowchart LR
    Login[Student login] --> Reg[Course registration]
    Reg --> Enr[Enrollment]
    Enr --> Att[Attendance]
    Att --> Grade[Grade]
    Grade --> GPA[GPA]
```

---

## 19. Deployment

```mermaid
flowchart LR
    Dev[Development] --> Docker[Docker stack: Laravel · Vue · PostgreSQL · Redis · MinIO · Nginx · pgAdmin]
    Prod[Production] --> GH[GitHub]
    GH --> CI[CI/CD]
    CI --> LC[Laravel Cloud]
    LC --> App[Production application]
```

Docker ensures consistent development environments between both developers.

---

## 20. Expected Outcomes

1. Centralized student and lecturer management.
2. University academic structure management.
3. Course and section management with enrollment.
4. Timetable and attendance management.
5. Assignment and examination management.
6. Grade and GPA management.
7. Digital document requests and QR verification.
8. Invoice and payment records.
9. Announcements, email, and Telegram notifications.
10. Academic dashboards.
11. Role-based access control.
12. Secure API architecture.
13. Production deployment.

---

## 21. Success Criteria

The MVP is successful when a complete academic workflow can be performed digitally:

```mermaid
flowchart TB
    Admin[Admin creates] --> RCA[Faculty → Department → Program]
    RCA --> AY2[Academic Year → Semester]
    AY2 --> CO2[Course → Section → Lecturer → Student]
    CO2 --> E2[Enrollment → Timetable → Attendance]
    E2 --> A2[Assignment → Exam → Grade → GPA]
    A2 --> Portal[Student accesses results via their portal]
```

- Core workflows function correctly.
- Users authenticate securely; RBAC works.
- Records managed consistently; enrollment validated.
- Documents requested, generated, and QR-verified.
- Notifications, reports, and audit logs function.
- System passes the defined test suite; Docker works reproducibly.

---

## 22. Future Expansion [Future]

```mermaid
flowchart LR
    MVP[MVP: modular monolith] --> V1[V1: advanced docs, deeper analytics]
    V1 --> V2[V2: mobile app, online payments, integrations]
    V2 --> ADV[Advanced: services split only if scale justifies]
```

Beyond MVP:

- **V1:** advanced timetable management, prerequisite engine, graduation eligibility,
  more document types, digital signatures, richer reports, Telegram automation,
  scheduled announcements.
- **V2:** mobile application/PWA, online payment integration, library & student ID
  integration, QR attendance, external verification API, advanced reporting.
- **Advanced architecture:** if scale justifies, modules could be extracted into
  services (identity, academic, student, document, notification, payment, analytics).
  The MVP remains a modular monolith.

---

## 23. Recommended First Milestone Artifacts

Before the first feature, produce:

1. **PRD** — exactly what EduCore must do.
2. **User stories** — e.g. "As a student, I want to view my timetable so I know when and
   where my classes occur."
3. **Use-case diagram** — Student, Lecturer, Administrator, Super Admin interactions.
4. **ERD** — the complete database structure.
5. **System architecture** — Vue → Laravel API → Services → Models → PostgreSQL.
6. **UI design system** — colors, typography, components, tables, forms, dashboards,
   navigation, responsive behavior.

Only after these are approved should implementation proceed.

---

## 24. Project Summary

| Item | Decision |
|---|---|
| Project | EduCore |
| Type | University Digital Administration Platform |
| Target | Designed for Cambodian universities |
| Team | Rin Nairith + Yong Lyhor |
| Architecture | Modular Monolith (MVC backend) |
| Backend | Laravel 12 (PHP 8.4) |
| Frontend | Vue 3 + Inertia (JavaScript) |
| Styling | Tailwind CSS |
| State | Pinia |
| Database | PostgreSQL |
| Cache/queue | Redis |
| Storage | MinIO (S3) |
| API | REST (Sanctum auth) |
| Authorization | RBAC (5 roles) |
| Notifications | Email + Telegram |
| Payments | Invoice + payment records (no online gateway in MVP) |
| Deployment | Docker + Laravel Cloud |
| CI/CD | GitHub Actions |
| Development | Agile |
| MVP target | ~12 weeks (program 4–6 months) |
| Status | Foundation and all §9 modules [Implemented] (2026-10-02); gaps listed in §9 and the roadmap |

---

*End of report.*