# ERD — Audit

Append-only audit trail.

```mermaid
erDiagram
    USERS ||--o{ AUDIT_LOGS : "actor"

    AUDIT_LOGS {
        bigint id PK
        bigint actor_id FK
        varchar action
        text description
        varchar auditable_type
        bigint auditable_id
        jsonb before_values
        jsonb after_values
        varchar ip_address
        text user_agent
        timestamptz created_at
    }
```

Notes:

- `before_values` / `after_values` are JSONB snapshots of the rows before/after
  a change — the sanctioned JSONB use case (see `database-conventions.md`).
- `actor_id` is nullable (`?bigint`) because system/cron changes have no actor.
- Target record is optional and polymorphic via
  `auditable_type` / `auditable_id` (no FK on the pair).
- Never cleaned at runtime; archive via a nightly job if retention is needed.