# EduCore Database Module Map

Which tables belong to which business module. 14 modules, 49 business tables,
8 framework tables. Reporting is derived (no base tables).

## 1. Identity & Access

```
Identity & Access
├── users
├── roles
├── permissions
├── permission_role            (pivot)
└── settings
```

## 2. University Structure

```
University Structure
├── universities
├── faculties
├── departments
└── programs
```

## 3. Academic Management

```
Academic Management
├── academic_years
├── semesters
├── courses
├── course_programs            (pivot course ↔ program)
├── course_prerequisites       (pivot course ↔ course)
├── course_offerings
├── sections
├── rooms
├── schedule_entries
└── section_lecturers          (pivot section ↔ lecturer)
```

## 4. People

```
People
├── students
├── student_programs           (history)
└── lecturers
```

## 5. Enrollment

```
Enrollment
└── enrollments
```

## 6. Attendance

```
Attendance
├── attendance_sessions
└── attendance_records         (raw records → percentages derived)
```

## 7. Assessment

```
Assessment
├── assignments
└── assignment_submissions
```

## 8. Examination & Grading

```
Examination & Grading
├── exams
├── exam_results
├── grades
├── grading_scales
├── course_grading_configs
└── gpa_records                (computed snapshot)
```

## 9. Documents & File Storage

```
Documents & File Storage
├── document_types
├── document_requests
├── documents
├── document_verifications
└── files                      (polymorphic uploads → MinIO metadata)
```

## 10. Finance

```
Finance
├── invoices
├── invoice_items
└── payments
```

## 11. Communication

```
Communication
├── announcements
├── notifications              (in-app, polymorphic notifiable)
└── notification_preferences
```

## 12. Internship

```
Internship
├── internship_companies
├── internships
├── internship_reports
└── internship_evaluations
```

## 13. Reporting (derived — no base tables)

Reporting reads from other modules and is **derived on demand**:

- Student statistics ← `students`, `student_programs`
- Enrollment statistics ← `enrollments`
- Attendance reports ← `attendance_records`
- Grade reports ← `grades`, `exam_results`
- GPA reports ← `gpa_records`
- Course statistics ← `enrollments`, `grades`, `attendance_records`
- Faculty/department statistics ← `departments`, `students`, `courses`
- Internship statistics ← `internships`
- Administrative reports ← `document_requests`, `documents`, `invoices`

No summary tables are added because the aggregates are cheap at the initial
scale and stay correct by construction (see `skills/analytics-reporting`).

## 14. Audit & Security

```
Audit & Security
└── audit_logs
```

Sanctum tokens (`personal_access_tokens`) support this module indirectly.

## Framework module (Laravel/Sanctum scaffolding)

```
Framework
├── password_reset_tokens
├── sessions
├── cache
├── cache_locks
├── jobs
├── job_batches
├── failed_jobs
└── personal_access_tokens
```

## Module ↔ table count

| Module | Tables |
|---|---|
| Identity & Access | 5 |
| University Structure | 4 |
| Academic Management | 10 |
| People | 3 |
| Enrollment | 1 |
| Attendance | 2 |
| Assessment | 2 |
| Examination & Grading | 6 |
| Documents & File Storage | 5 |
| Finance | 3 |
| Communication | 3 |
| Internship | 4 |
| Reporting | 0 (derived) |
| Audit & Security | 1 |
| **Domain total** | **49** |
| Framework | 8 |
| **Grand total** | **57** |