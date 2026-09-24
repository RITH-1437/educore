# ERD — Documents

Document types, requests, generated documents, verifications, and the
polymorphic file storage table.

```mermaid
erDiagram
    DOCUMENT_TYPES ||--o{ DOCUMENT_REQUESTS : "typed by"
    STUDENTS ||--o{ DOCUMENT_REQUESTS : "requests"
    DOCUMENT_REQUESTS ||--o| DOCUMENTS : "generates"
    DOCUMENTS ||--o{ DOCUMENT_VERIFICATIONS : "verified by"

    DOCUMENT_TYPES {
        bigint id PK
        varchar code UK
        varchar name
        text description
        boolean requires_fee
        boolean is_active
        smallint sort_order
    }
    DOCUMENT_REQUESTS {
        bigint id PK
        bigint student_id FK
        bigint document_type_id FK
        bigint academic_year_id FK
        bigint semester_id FK
        text reason
        varchar status
        timestamptz submitted_at
        bigint processed_by FK
        timestamptz processed_at
        text rejection_reason
    }
    DOCUMENTS {
        bigint id PK
        bigint document_request_id FK
        varchar file_key
        varchar file_name
        varchar mime_type
        bigint file_size
        varchar checksum
        varchar verification_token UK
        bigint generated_by FK
        timestamptz generated_at
        varchar status
        timestamptz deleted_at
    }
    DOCUMENT_VERIFICATIONS {
        bigint id PK
        bigint document_id FK
        varchar verification_token
        varchar result
        timestamptz verified_at
        varchar ip_address
        text user_agent
    }
    FILES {
        bigint id PK
        varchar fileable_type
        bigint fileable_id
        bigint uploader_id FK
        varchar file_name
        varchar original_name
        varchar storage_key
        varchar bucket
        varchar mime_type
        bigint size
        varchar visibility
        varchar checksum
    }
```

Notes:

- `files` is polymorphic (`fileable_type` / `fileable_id`) — attachments to
  assignments, submissions, internship reports, and announcements. No FK on
  the polymorphic pair.
- `documents` stores its own `file_key`/`file_name`/`checksum` because a
  generated document is a discrete artifact, not a generic upload
  (see `data-dictionary.md`).
- `verification_token` is the unique human-visible key used on the public
  verification page; verification attempts append to
  `document_verifications`.
- Documents are soft-deleted, never hard-purged, to preserve verification
  history.