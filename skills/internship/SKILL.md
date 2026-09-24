---
name: educore-internship
description: EduCore internship - application, approval, company info, reports, supervisor evaluation. Consult for any internship work.
---

# EduCore — Internship

## 1. Purpose

Manage the university internship workflow: student applies → university
review/approval → internship record → student reports → supervisor evaluation →
final evaluation.

## 2. Main entities

- `internship` (application/record): student, company, supervisor, status,
  dates.
- `internship_report` — student-submitted report(s).
- `internship_evaluation` (supervisor + faculty evaluation, submitted results,
  optional grade).

## 3. Relationships

- Internship → Student (N–1).
- Internship → Reports (1–N).
- Internship → Evaluations (1–N).
- (Optional) Internship → Document (agreement/letter — see `documents`).

## 4. Business rules

- A student may have one active internship application at a time (optional; be
  explicit).
- Status flow: `draft` → `submitted` → `under_review` → `approved` /
  `rejected` → `in_progress` → `completed`; transitions audited.
- Approval requires an authorized staff member; student cannot self-approve.
- Company info (name, contact) stored as part of the internship record
  (not a separate full CRM entity).
- Reports and evaluations are linked to the internship; evaluation may
  contribute to internship outcome.

## 5. API responsibilities

- `POST /api/internships` (student submit), `PATCH .../internships/{id}` (update
  while draft), `GET /api/internships` (own; staff all).
- `PATCH /api/internships/{id}/review` (approve/reject, staff).
- `POST /api/internships/{id}/reports`, `POST .../evaluations`.

## 6. Backend responsibilities

- `InternshipService`: state machine + validation; file storage for reports
  (MinIO — see `file-storage`); review transitions audited.
- Reports/evaluations stored in MinIO when files; structured evaluations with
  scores optional.

## 7. Frontend responsibilities

- Student internship portal: apply, upload reports, view status.
- Staff review queue with approve/reject.
- Status badges; evaluation forms.

## 8. Authorization rules

- Student: own internships only.
- Staff (Univ/Faculty Dept Admin): review/approve and evaluate.
- Supervisor evaluations may be entered by staff (no external login MVP).

## 9. Validation rules

- Valid student; required company/supervisor/date fields; valid status
  transitions; report file validated; no duplicate active application (per
  policy).

## 10. Important edge cases

- Rejected application resubmission (allow; new record or reopen).
- Student changes company after approval — requires staff update + audit.
- Early termination (status) — record and evaluate if needed.
- Document letter generation tied to approval (see `documents`).

## 11. Testing requirements

- State machine transitions; own-vs-staff authorization; file validation;
  evaluation entry; audit on approvals.

## 12. Must NOT

- Must NOT build a full company CRM (store a lean company snapshot on the
  record).
- Must NOT let students approve/reject.
- Must NOT delete internship history.
- Must NOT invent payment/stipend logic not requested.

## Cross-references

- `skills/academic-domain/SKILL.md`, `skills/file-storage/SKILL.md`,
  `skills/documents/SKILL.md`, `skills/audit-logging/SKILL.md`.

## Agent behavior (mandatory)

1. Inspect existing implementation first. 2. Follow established conventions.
3. Do not rewrite working code. 4. No tech outside the stack. 5. No unnecessary
abstractions. 6. No duplicate business logic. 7. No invented relationships.
8. No bypassing authorization. 9. No hardcoded secrets. 10. No unrelated
module changes. 11. Run tests. 12. Explain architectural decisions.