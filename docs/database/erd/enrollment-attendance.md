# ERD — Enrollment & Attendance

Student enrollment into sections, program history, attendance sessions and
per-student records.

```mermaid
erDiagram
    STUDENTS ||--o{ STUDENT_PROGRAMS : "history"
    STUDENTS ||--o{ ENROLLMENTS : "registers"
    SECTIONS ||--o{ ENROLLMENTS : "contains"
    ENROLLMENTS ||--o{ ATTENDANCE_RECORDS : "has"
    ATTENDANCE_SESSIONS ||--o{ ATTENDANCE_RECORDS : "records"

    STUDENT_PROGRAMS {
        bigint id PK
        bigint student_id FK
        bigint program_id FK
        date started_on
        date ended_on
        varchar status
        text notes
    }
    ENROLLMENTS {
        bigint id PK
        bigint student_id FK
        bigint section_id FK
        bigint academic_year_id FK
        bigint semester_id FK
        varchar status
        timestamptz enrolled_at
        timestamptz dropped_at
        timestamptz deleted_at
    }
    ATTENDANCE_SESSIONS {
        bigint id PK
        bigint section_id FK
        date session_date
        time start_time
        time end_time
        varchar topic
        varchar status
        bigint recorded_by FK
    }
    ATTENDANCE_RECORDS {
        bigint id PK
        bigint attendance_session_id FK
        bigint enrollment_id FK
        varchar status
        text remarks
        bigint marked_by FK
    }
```

Notes:

- `enrollments` has a unique `(student_id, section_id)` pair plus snapshot
  FKs to `academic_year_id` / `semester_id` so historical reports survive
  calendar edits.
- Soft delete is enabled on `enrollments`.
- Statuses: enrollment `pending|confirmed|completed|dropped|withdrawn`;
  attendance session `scheduled|held|cancelled`; attendance record
  `present|absent|late|excused`.
- `student_programs` enforces exactly one active program per student (partial
  unique index).
- `attendance_sessions` is unique per `(section_id, session_date)`.