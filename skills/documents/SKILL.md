---
name: educore-documents
description: EduCore documents - student document requests, approval workflow, generated documents (PDF), QR verification, download authorization. Consult for any document work.
---

# EduCore — Documents

## 1. Purpose

Students request official documents (enrollment certificate, transcript,
result, internship letter); staff approve; the system generates the document
and (optionally) a QR verification token; the student downloads only that
version.

## 2. Main entities

- `document_request` — type, status (`pending`, `approved`, `rejected`,
  `generated`), submitted_at, processed_at.
- `document` — generated file (MinIO key, doc type, reference request,
  verification token/hash).
- `document_verification` — public read-only verification lookup by token.

## 3. Relationships

- Document Request → Student (N–1).
- Document → Request (1–1) once generated.
- Document → QR/verification token (1–1 optional).

## 4. Business rules

- Document request types are a fixed, configurable list (enrollment
  certificate, student certificate, transcript, academic result, internship
  letter, other).
- Only the student can request their own documents; staff approve.
- A rejected request stores a reason (optional but useful); student can resubmit.
- Generated document content comes from authoritative data (enrollments,
  grades, GPA — see `grading-gpa`), NOT from a manually entered form.
- Documents are **private**; downloads require login + ownership/staff.
- QR verification (optional feature) allows verifying authenticity WITHOUT
  showing full private data (doc id, status, generated date).

## 5. API responsibilities

- `POST /api/document-requests` (student).
- `GET /api/document-requests` (own; staff see all, filter by status).
- `PATCH /api/document-requests/{id}` (approve/reject with reason).
- `POST /api/document-requests/{id}/generate` (staff triggers generation).
- `GET /api/documents/{document}/download` (authorized stream).
- `GET /api/verifications/{token}` (public minimal info).

## 6. Backend responsibilities

- `DocumentRequestService`: state machine (pending → approved → generated /
  rejected), all transitions validated + audited.
- Generation service: render a PDF from template + live data, store in MinIO
  under `documents/{...}`, save key + verification token (see `file-storage`).
- Download endpoint checks Policy (owner or staff) then streams the private
  file (signed/disposition).

## 7. Frontend responsibilities

- Student: request form (type + note), request list with status badge,
  download button when available.
- Admin: request queue with approve/reject/generate actions.
- Download links via authorized API route only.

## 8. Authorization rules

- Student: own requests/downloads.
- Univ Admin / Faculty-Dept Admin: process requests for their scope.
- Public: verification endpoint only (token-scoped minimal data).

## 9. Validation rules

- Valid request type; student must be the authenticated owner; status
  transitions valid; file generated & stored or error surfaced.

## 10. Important edge cases

- Request approved but generation fails (file error) — status stays
  processing/retryable with audit log.
- Student re-requests the same doc — allow (new request) vs dedupe (policy).
- Transcript after grades change — a previously generated PDF becomes stale;
  decide: re-generate vs immutable snapshot (document decision; keep audit).
- Deleting a student — documents remain (request history).

## 11. Testing requirements

- Request flow state machine; authorization (own vs staff); generation success
  + failure; download for owner/staff only; public verification minimal data;
  audit recorded.

## 12. Must NOT

- Must NOT generate documents from hand-entered data (use authoritative data).
- Must NOT expose document URLs publicly (MinIO console URLs).
- Must NOT allow a student to approve their own request.
- Must NOT hard-delete document history.

## Cross-references

- `skills/file-storage/SKILL.md`, `skills/notifications/SKILL.md`,
  `skills/grading-gpa/SKILL.md` (transcript data),
  `skills/audit-logging/SKILL.md`.

## Agent behavior (mandatory)

1. Inspect existing implementation first. 2. Follow established conventions.
3. Do not rewrite working code. 4. No tech outside the stack. 5. No unnecessary
abstractions. 6. No duplicate business logic. 7. No invented relationships.
8. No bypassing authorization. 9. No hardcoded secrets. 10. No unrelated
module changes. 11. Run tests. 12. Explain architectural decisions.