# ERD — Identity & Access

Users, roles, permissions, RBAC pivot, and system settings.

```mermaid
erDiagram
    USERS ||--o| STUDENTS : "is a"
    USERS ||--o| LECTURERS : "is a"
    USERS }o--|| ROLES : "has role"
    ROLES }o--o{ PERMISSIONS : "via permission_role"

    USERS {
        bigint id PK
        varchar name
        varchar email UK
        varchar password
        bigint role_id FK
        varchar phone
        boolean is_active
        timestamptz last_login_at
        timestamptz deleted_at
    }
    ROLES {
        bigint id PK
        varchar name UK
        varchar slug UK
        text description
        boolean is_system
    }
    PERMISSIONS {
        bigint id PK
        varchar name UK
        varchar slug UK
        varchar module
        text description
    }
    PERMISSION_ROLE {
        bigint id PK
        bigint role_id FK
        bigint permission_id FK
    }
    SETTINGS {
        bigint id PK
        varchar key UK
        text value
        varchar group
        varchar type
        boolean is_public
    }
```

Notes:

- One role per user in the MVP (see `decisions.md` §6).
- A user may also be a student OR a lecturer (never both).
- `settings` is a key-value store for institution configuration.