---
name: educore-file-storage
description: EduCore file storage - MinIO / S3-compatible storage, upload validation, private files, generated documents, avatars, assignments. Consult for any file handling.
---

# EduCore File Storage

EduCore stores files in **MinIO** (S3-compatible) via Laravel's filesystem
(config `FILESYSTEM_DISK=s3`, `AWS_ENDPOINT=http://minio:9000`,
`AWS_USE_PATH_STYLE_ENDPOINT=true`, bucket `educore`). Production is intended
to use real S3-compatible object storage.

## When to use

- Uploading, storing, retrieving, or generating files: profile images, student
  documents, assignment submissions, reports, generated PDFs.

## Storage rules

- Always use the `storage` facade (`Storage::disk('s3')`).
- **Never rely on ephemeral container/php-fpm local disk for important files.**
  Store user files in MinIO; `/var/www/html/storage` holds only transient
  cache/temp work.

## Upload validation (backend)

- Validate before storing: MIME type, extension, and size.
- Allow only expected types per feature (e.g. images: `jpg png webp`;
  documents: `pdf`; assignments: `pdf docx zip`); cap size per feature.
- Reject executable/script types; do not store based on client filename alone.
- Generate server-side object keys (random/UUID) — never trust client names for
  the stored key.

## Storage keys / folders

- Structured keys, e.g.:
  - `students/{studentId}/avatar.{ext}`
  - `documents/{documentId}/proof.{ext}` or `documents/{userId}/{uuid}.pdf`
  - `assignments/{courseId}/{assignmentId}/submissions/{studentId}/{uuid}.{ext}`
  - `reports/{month}/report-{uuid}.pdf`
- Store the key (+ size + mime) in the DB record, not the full URL (URLs can
  change between MinIO/S3).

## Public vs private

- **Public**: profile photos, announcement attachments the university publishes.
- **Private**: student documents, transcripts, assignment submissions, invoices.
- Private files must NOT be directly reachable via a guessable public URL.
  Serve through an authenticated controller route that:
  1. Checks the Policy (owner / staff).
  2. Streams the file via S3 signed URL or server-side response with correct
     attachment/inline disposition.
- Do not expose the S3/MinIO console credentials via client URLs.

## Generated documents

- Document generation (PDF) — see `skills/documents/SKILL.md`. Generated files
  are stored in MinIO, then made downloadable only through the authorized route.

## Frontend

- Upload via `multipart/form-data` through service modules (see `skills/vue`).
- Show progress/pending state and server validation errors (type/size).
- Download links point to the authorized API route, not MinIO directly.

## Backend responsibilities

- Form Request validates upload; service stores to disk('s3') with structured
  key; model stores key/mime/size; delete old file on replace (and on model
  delete) to avoid orphans.

## Prohibitions

- DO NOT store business files on the container's ephemeral filesystem.
- DO NOT accept files without type/size validation.
- DO NOT expose private files without authorization.
- DO NOT rely on `env()` in app code — use config (see `skills/laravel`).

## Validation checklist

1. Upload validated (mime/ext/size) server-side.
2. Stored under a structured, server-generated key.
3. Private files served only through an authorized route.
4. Metadata (key/size/mime) persisted in DB.
5. Tests cover upload success + rejection (see `skills/testing`).

## Cross-references

- `skills/security/SKILL.md` — file upload security.
- `skills/documents/SKILL.md` — generated documents.
- `skills/assignments/SKILL.md` — submission files.

## Agent behavior (mandatory everywhere)

1. Inspect the existing implementation before modifying it.
2. Follow existing project conventions already established.
3. Do not rewrite working code unnecessarily.
4. Do not introduce technologies outside the EduCore stack.
5. Do not create unnecessary abstractions.
6. Do not create duplicate business logic.
7. Do not invent database relationships.
8. Do not bypass authorization.
9. Do not hardcode secrets.
10. Do not modify unrelated modules.
11. Run appropriate tests after changes.
12. Explain important architectural decisions.