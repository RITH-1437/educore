# ERD — Internship

Companies, internship placements, reports, and evaluations.

```mermaid
erDiagram
    INTERNSHIP_COMPANIES ||--o{ INTERNSHIPS : "hosts"
    STUDENTS ||--o{ INTERNSHIPS : "applies"
    INTERNSHIPS ||--o{ INTERNSHIP_REPORTS : "has"
    INTERNSHIPS ||--o{ INTERNSHIP_EVALUATIONS : "has"

    INTERNSHIP_COMPANIES {
        bigint id PK
        varchar name UK
        varchar industry
        varchar contact_name
        varchar contact_email
        varchar contact_phone
        text address
        varchar website
        boolean is_active
    }
    INTERNSHIPS {
        bigint id PK
        bigint student_id FK
        bigint company_id FK
        varchar position_title
        text description
        date start_date
        date end_date
        varchar supervisor_name
        varchar supervisor_email
        varchar supervisor_phone
        varchar status
        timestamptz submitted_at
        bigint reviewed_by FK
        timestamptz reviewed_at
        text notes
    }
    INTERNSHIP_REPORTS {
        bigint id PK
        bigint internship_id FK
        varchar report_type
        varchar title
        text summary
        timestamptz submitted_at
        varchar status
        text reviewer_comment
    }
    INTERNSHIP_EVALUATIONS {
        bigint id PK
        bigint internship_id FK
        varchar evaluator_type
        varchar evaluator_name
        numeric score
        varchar rating
        text comments
        timestamptz evaluated_at
        bigint submitted_by FK
    }
```

Notes:

- Company records are lean (no opportunity catalog) — see `decisions.md` §10.
- `internships` enforces one open application per student (partial unique
  index) across `submitted|under_review|approved|in_progress`.
- Evaluations are typed `supervisor|faculty` with a free-text `evaluator_name`;
  `score` is 0–100 (unsigned CHECK) — no polymorphic FK in the MVP
  (see `decisions.md` §4).
- Reports attach their rendered file via the polymorphic `files` table.