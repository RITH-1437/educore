# ERD — University Structure

The institutional hierarchy root: university → faculty → department → program.

```mermaid
erDiagram
    UNIVERSITIES ||--o{ FACULTIES : "contains"
    FACULTIES ||--o{ DEPARTMENTS : "contains"
    DEPARTMENTS ||--o{ PROGRAMS : "offers"

    UNIVERSITIES {
        bigint id PK
        varchar code UK
        varchar name
        varchar short_name
        text address
        varchar phone
        varchar email
        varchar logo_key
        boolean is_current
    }
    FACULTIES {
        bigint id PK
        bigint university_id FK
        varchar code UK
        varchar name
        varchar dean_name
        boolean is_active
    }
    DEPARTMENTS {
        bigint id PK
        bigint faculty_id FK
        varchar code UK
        varchar name
        varchar head_name
        boolean is_active
    }
    PROGRAMS {
        bigint id PK
        bigint department_id FK
        varchar code UK
        varchar name
        varchar degree_level
        smallint duration_years
        numeric credits_required
    }
```

Notes:

- `is_current` marks the active institution row.
- Deleting a faculty/department/program is `RESTRICT` (must be empty first).