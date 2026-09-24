# ERD — Finance

Invoices, line items, and append-only payments.

```mermaid
erDiagram
    STUDENTS ||--o{ INVOICES : "billed"
    INVOICES ||--o{ INVOICE_ITEMS : "contains"
    INVOICES ||--o{ PAYMENTS : "paid by"

    INVOICES {
        bigint id PK
        bigint student_id FK
        varchar invoice_number UK
        varchar title
        text description
        varchar currency
        numeric subtotal
        numeric discount
        numeric total
        numeric amount_paid
        varchar status
        date issued_date
        date due_date
        text notes
        timestamptz deleted_at
    }
    INVOICE_ITEMS {
        bigint id PK
        bigint invoice_id FK
        varchar description
        numeric quantity
        numeric unit_price
        numeric amount
        varchar fee_category
    }
    PAYMENTS {
        bigint id PK
        bigint invoice_id FK
        numeric amount
        date paid_on
        varchar method
        varchar reference
        bigint received_by FK
        text notes
        boolean is_reversal
        bigint reversal_of FK
    }
```

Notes:

- Payments are append-only: reversals are new rows with `is_reversal = true`
  pointing at the original via `reversal_of`; nothing is updated or deleted.
- `invoice_number` is a business-friendly, human-readable unique identifier
  (the `reference` on a payment is informational, not unique).
- `invoices.amount_paid` is a derived running total (≤ `total`), maintained
  transactionally by the invoice service.