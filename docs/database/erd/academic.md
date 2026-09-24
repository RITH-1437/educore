# ERD — Academic Management

Calendar, courses, prerequisites, offerings, sections, schedule, rooms,
lecturer assignments.

```mermaid
erDiagram
    ACADEMIC_YEARS ||--o{ SEMESTERS : "contains"
    DEPARTMENTS ||--o{ COURSES : "owns"
    PROGRAMS }o--o{ COURSES : "via course_programs"
    COURSES }o--o{ COURSES : "prereq via course_prerequisites"
    COURSES ||--o{ COURSE_OFFERINGS : "taught in"
    SEMESTERS ||--o{ COURSE_OFFERINGS : "offers"
    COURSE_OFFERINGS ||--o{ SECTIONS : "has"
    SECTIONS }o--o{ LECTURERS : "via section_lecturers"
    SECTIONS ||--o{ SCHEDULE_ENTRIES : "meets"
    ROOMS ||--o{ SCHEDULE_ENTRIES : "hosts"

    ACADEMIC_YEARS {
        bigint id PK
        varchar code UK
        varchar name
        date start_date
        date end_date
        varchar status
        boolean is_current
    }
    SEMESTERS {
        bigint id PK
        bigint academic_year_id FK
        varchar name
        varchar code
        smallint sequence
        date start_date
        date end_date
        date enrollment_start
        date enrollment_end
        date exam_start
        date exam_end
        varchar status
    }
    COURSES {
        bigint id PK
        bigint department_id FK
        varchar code UK
        varchar name
        numeric credits
        smallint lecture_hours
        varchar status
    }
    COURSE_PROGRAMS {
        bigint id PK
        bigint course_id FK
        bigint program_id FK
        boolean is_required
        smallint suggested_semester
    }
    COURSE_PREREQUISITES {
        bigint id PK
        bigint course_id FK
        bigint prerequisite_course_id FK
        boolean is_strict
    }
    COURSE_OFFERINGS {
        bigint id PK
        bigint course_id FK
        bigint semester_id FK
        varchar status
        int max_enrollments
    }
    SECTIONS {
        bigint id PK
        bigint course_offering_id FK
        varchar code
        smallint capacity
        varchar status
    }
    ROOMS {
        bigint id PK
        varchar code UK
        varchar name
        varchar building
        smallint capacity
        varchar room_type
    }
    SCHEDULE_ENTRIES {
        bigint id PK
        bigint section_id FK
        bigint room_id FK
        smallint day_of_week
        time start_time
        time end_time
    }
    SECTION_LECTURERS {
        bigint id PK
        bigint section_id FK
        bigint lecturer_id FK
        varchar role
    }
```

Notes:

- Offering = course × semester (UQ `(course_id, semester_id)`).
- Schedule: recurring weekly slots; conflict backstops in DB (section/room),
  partial-overlap & lecturer checks in `TimetableService`.
- One primary lecturer per section (partial unique index on
  `section_lecturers`).