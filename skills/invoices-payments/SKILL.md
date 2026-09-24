---
name: educore-invoices-payments
description: EduCore invoices and payment records - invoice generation, statuses, payment records, no online gateway in MVP. Consult for any finance-record work.
---

# EduCore — Invoices & Payment Records

## 1. Purpose

Record institutional charges (invoice) and received payments (payment records)
per student. There is **no dedicated Finance Officer role** and **no online
payment gateway in the MVP**; administrators record financial data.

## 2. Main entities

- `invoice` — student, amount, due date, status, references.
- `payment` — invoice ref, amount, date, method, reference.

## 3. Relationships

- Invoice → Student (N–1).
- Invoice → Payments (1–N).
- (Optional) Invoice → enrollment-related charge or tuition config; keep
  flexible per institution.

## 4. Business rules

- Invoice statuses: `pending`, `partial`, `paid`, `overdue`, `cancelled`.
  Derive `partial`/`overdue` from payments/dates rather than storing
  contradictory state (compute, persist a status field consistent with
  reality on each payment).
- Amounts stored in decimal; currency configurable (e.g. USD/riel display).
- No online processing; payments are records of received funds (cash,
  bank transfer) entered by admins.
- A payment can't exceed remaining balance (or allow overpayment? decide and
  document; prefer reject).
- Cancelling an invoice with payments is blocked unless payments reversed
  (audited).

## 5. API responsibilities

- `GET/POST/PATCH/DELETE /api/invoices` (staff).
- `GET /api/students/{student}/invoices` (own or staff).
- `POST /api/invoices/{invoice}/payments`, `DELETE .../payments/{id}`.
- `GET /api/invoices/{invoice}` incl. payments + balance.

## 6. Backend responsibilities

- `InvoiceService` / `PaymentService`: create/validate invoices; record
  payments in a transaction (update invoice status + totals atomically — see
  `skills/laravel`).
- Compute balance via aggregation; enforce payment ≤ balance.
- Audit every financial change (see `audit-logging`).

## 7. Frontend responsibilities

- Invoice list/filters (status, overdue) + invoice detail with payments.
- Payment entry form (amount, method, date, reference).
- Student finance view (own invoices, balances, status badges).

## 8. Authorization rules

- Univ Admin (admins): all invoice/payment management.
- Student: view own invoices/payments only.

## 9. Validation rules

- Valid student; amount > 0; due date sane; payment ≤ remaining balance; valid
  status transitions; methods from a fixed list.

## 10. Important edge cases

- Overdue derived vs stored status drift — recompute on payment date change.
- Reverse/correction of a payment — requires admin + audit trail.
- Invoices created after the semester starts / before due dates.
- Display currency formatting consistently.

## 11. Testing requirements

- Invoice CRUD + status derivation (partial/paid/overdue).
- Payment within balance; reject overpayment; transaction atomicity.
- Cancel-blocked-with-payments rule.
- Student sees only own finances.

## 12. Must NOT

- Must NOT implement or assume an online payment gateway (MVP scope).
- Must NOT store money as floats; use decimal.
- Must NOT let students enter/record payments.
- Must NOT delete historical invoices/payments (soft or restrict).

## Cross-references

- `skills/academic-domain/SKILL.md`, `skills/security/SKILL.md`,
  `skills/audit-logging/SKILL.md`, `skills/analytics-reporting/SKILL.md`.

## Agent behavior (mandatory)

1. Inspect existing implementation first. 2. Follow established conventions.
3. Do not rewrite working code. 4. No tech outside the stack. 5. No unnecessary
abstractions. 6. No duplicate business logic. 7. No invented relationships.
8. No bypassing authorization. 9. No hardcoded secrets. 10. No unrelated
module changes. 11. Run tests. 12. Explain architectural decisions.