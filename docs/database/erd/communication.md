# ERD — Communication

Announcements and the polymorphic notification inbox.

```mermaid
erDiagram
    USERS ||--o{ ANNOUNCEMENTS : "author"
    USERS ||--o{ NOTIFICATIONS : "receives"
    USERS ||--o| NOTIFICATION_PREFERENCES : "config"

    ANNOUNCEMENTS {
        bigint id PK
        bigint author_id FK
        varchar title
        text body
        varchar announcement_type
        varchar audience_type
        bigint audience_id
        varchar publish_state
        timestamptz published_at
    }
    NOTIFICATIONS {
        bigint id PK
        varchar notifiable_type
        bigint notifiable_id
        varchar type
        jsonb data
        timestamptz read_at
    }
    NOTIFICATION_PREFERENCES {
        bigint id PK
        bigint user_id FK
        boolean notify_by_email
        boolean notify_by_telegram
        varchar telegram_chat_id
    }
```

Notes:

- `notifications` mirrors Laravel's polymorphic notification contract
  (`notifiable_type` / `notifiable_id`) so queued notifications plug straight
  into Eloquent.
- `notifications.data` is a JSONB payload (see `database-conventions.md`).
- `announcements.audience_type` is a `varchar` + `CHECK`:
  `all|students|lecturers|staff|faculty|department|program|section|course`;
  `announcement_type` is `general|academic|administrative|event`;
  `publish_state` is `draft|published|archived`.
- Announcement attachments are `files` rows with `fileable` pointing at the
  announcement.