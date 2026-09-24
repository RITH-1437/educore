# ERD — Examination & Grading

Exams, results, final grades, grading scales, GPA records, and per-course
grading configuration. Includes assessment tables (assignments).

```mermaid
erDiagram
    COURSES ||--o| COURSE_GRADING_CONFIGS : "weights"
    EXAMS ||--o{ EXAM_RESULTS : "scores"
    ENROLLMENTS ||--o{ EXAM_RESULTS : "has"
    ENROLLMENTS ||--o{ ASSIGNMENT_SUBMISSIONS : "submits"
    ASSIGNMENTS ||--o{ ASSIGNMENT_SUBMISSIONS : "collects"
    ENROLLMENTS ||--o| GRADES : "final grade"

    ASSIGNMENTS {
        bigint id PK
        bigint section_id FK
        varchar title
        text description
        text instructions
        numeric max_score
        timestamptz due_at
        varchar assignment_type
        numeric weight_override
        boolean is_published
        timestamptz published_at
    }
    ASSIGNMENT_SUBMISSIONS {
        bigint id PK
        bigint assignment_id FK
        bigint enrollment_id FK
        timestamptz submitted_at
        varchar status
        numeric score
        text feedback
        bigint graded_by FK
        timestamptz graded_at
    }
    EXAMS {
        bigint id PK
        bigint section_id FK
        varchar exam_type
        varchar title
        numeric weight
        numeric max_score
        date scheduled_date
        time start_time
        time end_time
        varchar location
        boolean is_published
    }
    EXAM_RESULTS {
        bigint id PK
        bigint exam_id FK
        bigint enrollment_id FK
        numeric score
        text remarks
        bigint recorded_by FK
    }
    GRADES {
        bigint id PK
        bigint enrollment_id FK
        varchar letter_grade
        numeric grade_point
        numeric total_score
        varchar status
        bigint graded_by FK
        timestamptz submitted_at
        timestamptz approved_at
        text remarks
        timestamptz deleted_at
    }
    GRADING_SCALES {
        bigint id PK
        varchar name
        varchar grade
        numeric min_percentage
        numeric max_percentage
        numeric grade_point
        boolean is_pass
        boolean is_active
    }
    COURSE_GRADING_CONFIGS {
        bigint id PK
        bigint course_id FK
        numeric attendance_weight
        numeric assignment_weight
        numeric midterm_weight
        numeric final_weight
        numeric practical_weight
    }
    GPA_RECORDS {
        bigint id PK
        bigint student_id FK
        bigint academic_year_id FK
        bigint semester_id FK
        numeric gpa_value
        numeric attempted_credits
        numeric earned_credits
        numeric grade_points
        boolean cumulative
        timestamptz computed_at
    }
```

Notes:

- `grading_scales` holds one row per letter band of the configurable scale
  (default A/B+/B/C+/C/D/F → 4.0 / 3.5 / 3.0 / 2.5 / 2.0 / 1.0 / 0.0) with
  `is_pass` flagging passing bands.
- GPA is stored only as recomputed snapshots in `gpa_records` — it is never
  kept in a pre-aggregated live column.
- `grades` is soft-deletable and carries an approval workflow
  (`draft|submitted|approved|finalized`) with `graded_by`.
- `course_grading_configs` weights must sum to 100 (DB CHECK).
- Assessment tables (`assignments`, `assignment_submissions`) belong to the
  Assessment module but are shown here because they feed the same grading
  pipeline.