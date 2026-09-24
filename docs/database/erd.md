# EduCore Database ERD

Entity-relationship diagrams for the complete schema (49 domain tables). The
terminology follows `skills/academic-domain/SKILL.md` exactly.

## Contents

| Diagram | Where |
|---|---|
| High-level architecture | below (this file) |
| Complete database ERD | below (this file) |
| Identity & Access | [`erd/identity-access.md`](./erd/identity-access.md) |
| University Structure | [`erd/university-structure.md`](./erd/university-structure.md) |
| Academic Management | [`erd/academic.md`](./erd/academic.md) |
| Enrollment & Attendance | [`erd/enrollment-attendance.md`](./erd/enrollment-attendance.md) |
| Examination & Grading | [`erd/examination-grading.md`](./erd/examination-grading.md) |
| Documents | [`erd/documents.md`](./erd/documents.md) |
| Finance | [`erd/finance.md`](./erd/finance.md) |
| Communication | [`erd/communication.md`](./erd/communication.md) |
| Internship | [`erd/internship.md`](./erd/internship.md) |
| Audit | [`erd/audit.md`](./erd/audit.md) |

---

## 1. High-level architecture ERD

```mermaid
erDiagram
    USERS ||--o| STUDENTS : "is a"
    USERS ||--o| LECTURERS : "is a"
    USERS }o--|| ROLES : "has role"
    ROLES }o--o{ PERMISSIONS : "via permission_role"

    UNIVERSITIES ||--o{ FACULTIES : "contains"
    FACULTIES ||--o{ DEPARTMENTS : "contains"
    DEPARTMENTS ||--o{ PROGRAMS : "offers"
    PROGRAMS }o--o{ COURSES : "includes (course_programs)"
    DEPARTMENTS ||--o{ COURSES : "owns"

    ACADEMIC_YEARS ||--o{ SEMESTERS : "contains"
    SEMESTERS ||--o{ COURSE_OFFERINGS : "offers"
    COURSES ||--o{ COURSE_OFFERINGS : "taught in"
    COURSE_OFFERINGS ||--o{ SECTIONS : "has"
    SECTIONS }o--o{ LECTURERS : "taught by (section_lecturers)"
    SECTIONS ||--o{ SCHEDULE_ENTRIES : "meets"
    ROOMS ||--o{ SCHEDULE_ENTRIES : "hosts"

    STUDENTS ||--o{ ENROLLMENTS : "registers"
    SECTIONS ||--o{ ENROLLMENTS : "contains"
    ENROLLMENTS ||--o{ ATTENDANCE_RECORDS : "has"
    ENROLLMENTS ||--o{ ASSIGNMENT_SUBMISSIONS : "has"
    ENROLLMENTS ||--o{ EXAM_RESULTS : "has"
    ENROLLMENTS ||--o| GRADES : "has final"

    STUDENTS ||--o{ DOCUMENT_REQUESTS : "requests"
    DOCUMENT_REQUESTS ||--o| DOCUMENTS : "generates"
    DOCUMENTS ||--o{ DOCUMENT_VERIFICATIONS : "verified by"

    STUDENTS ||--o{ INVOICES : "billed"
    INVOICES ||--o{ INVOICE_ITEMS : "contains"
    INVOICES ||--o{ PAYMENTS : "paid by"

    STUDENTS ||--o{ INTERNSHIPS : "applies to"
    INTERNSHIP_COMPANIES ||--o{ INTERNSHIPS : "hosts"
    INTERNSHIPS ||--o{ INTERNSHIP_REPORTS : "has"
    INTERNSHIPS ||--o{ INTERNSHIP_EVALUATIONS : "has"

    USERS ||--o{ AUDIT_LOGS : "actor"
```

## 2. Complete database ERD

```mermaid
erDiagram
    USERS ||--o| STUDENTS : "is a"
    USERS ||--o| LECTURERS : "is a"
    USERS }o--|| ROLES : "has role"
    USERS ||--o{ AUDIT_LOGS : "actor"
    USERS ||--o{ ANNOUNCEMENTS : "author"
    USERS ||--o| NOTIFICATION_PREFERENCES : "config"
    USERS ||--o{ NOTIFICATIONS : "receives"
    ROLES }o--o{ PERMISSIONS : "via permission_role"

    UNIVERSITIES ||--o{ FACULTIES : "contains"
    FACULTIES ||--o{ DEPARTMENTS : "contains"
    DEPARTMENTS ||--o{ PROGRAMS : "offers"
    PROGRAMS }o--o{ COURSES : "via course_programs"
    PROGRAMS ||--o{ STUDENT_PROGRAMS : "has"
    DEPARTMENTS ||--o{ COURSES : "owns"
    DEPARTMENTS ||--o{ LECTURERS : "employs"

    ACADEMIC_YEARS ||--o{ SEMESTERS : "contains"
    SEMESTERS ||--o{ COURSE_OFFERINGS : "offers"
    COURSES ||--o{ COURSE_OFFERINGS : "taught in"
    COURSES }o--o{ COURSES : "prereq (course_prerequisites)"
    COURSES ||--o| COURSE_GRADING_CONFIGS : "weights"
    COURSE_OFFERINGS ||--o{ SECTIONS : "has"
    SECTIONS }o--o{ LECTURERS : "via section_lecturers"
    SECTIONS ||--o{ SCHEDULE_ENTRIES : "meets"
    ROOMS ||--o{ SCHEDULE_ENTRIES : "hosts"

    STUDENTS ||--o{ STUDENT_PROGRAMS : "history"
    STUDENTS ||--o{ ENROLLMENTS : "registers"
    SECTIONS ||--o{ ENROLLMENTS : "contains"
    ENROLLMENTS ||--o{ ATTENDANCE_RECORDS : "has"
    ENROLLMENTS ||--o{ ASSIGNMENT_SUBMISSIONS : "submits"
    ENROLLMENTS ||--o{ EXAM_RESULTS : "has"
    ENROLLMENTS ||--o| GRADES : "final grade"
    ATTENDANCE_SESSIONS ||--o{ ATTENDANCE_RECORDS : "records"
    ASSIGNMENTS ||--o{ ASSIGNMENT_SUBMISSIONS : "collects"
    EXAMS ||--o{ EXAM_RESULTS : "scores"

    STUDENTS ||--o{ DOCUMENT_REQUESTS : "requests"
    DOCUMENT_TYPES ||--o{ DOCUMENT_REQUESTS : "typed by"
    DOCUMENT_REQUESTS ||--o| DOCUMENTS : "generates"
    DOCUMENTS ||--o{ DOCUMENT_VERIFICATIONS : "verified by"

    STUDENTS ||--o{ INVOICES : "billed"
    INVOICES ||--o{ INVOICE_ITEMS : "contains"
    INVOICES ||--o{ PAYMENTS : "paid by"

    STUDENTS ||--o{ INTERNSHIPS : "applies"
    INTERNSHIP_COMPANIES ||--o{ INTERNSHIPS : "hosts"
    INTERNSHIPS ||--o{ INTERNSHIP_REPORTS : "has"
    INTERNSHIPS ||--o{ INTERNSHIP_EVALUATIONS : "has"

    GRADING_SCALES {
        bigint id PK
        varchar grade
        numeric grade_point
    }
    FILES {
        bigint id PK
        varchar fileable_type
        bigint fileable_id
    }
    SETTINGS {
        bigint id PK
        varchar key UK
    }
```

Per-domain diagrams with columns: see the [`erd/`](./erd/) folder.