---
name: educore-announcements
description: EduCore announcements - creating targeted announcements, audiences, notifications, read states. Consult for any announcement work.
---

# EduCore — Announcements

## 1. Purpose

Publish announcements to targeted audiences (all students, faculty,
department, program, class, course) and notify users (email/Telegram) while
tracking visibility/read state.

## 2. Main entities

- `announcement` — title, body, audience scope (faculty/department/program/
  section or "all"), published_at, optional attachment.
- `announcement_recipient` — computed membership (if read-tracking per user is
  needed) or rely on audience query.

## 3. Relationships

- Announcement → target scope (reference to faculty/department/program/section
  optionally).
- Announcement → Notifications (dispatched on publish — see
  `skills/notifications/SKILL.md`).
- Announcement → (optionally) read receipts.

## 4. Business rules

- Audience scopes: `all`, `faculty`, `department`, `program`, `section`.
- A published announcement triggers queued notifications to the audience
  members (email; Telegram for subscribers).
- Draft → publish workflow; scheduled publish optional.
- Only authorized roles publish (admins; lecturers within their course/section).
- Past/published announcements are append-only (edits create history or add an
  update, do not silently rewrite).

## 5. API responsibilities

- `GET /api/announcements` (own audience feed, paginated).
- `GET/POST/PATCH/DELETE /api/announcements` (staff publish; drafts draft-only).
- `POST /api/announcements/{id}/publish`.
- `POST /api/announcements/{id}/read` (optional read receipt).

## 6. Backend responsibilities

- `AnnouncementService`: build audience member set at publish time, dispatch
  notification jobs (queued), store published state. Avoid N+1 over members.
- Attachment stored in MinIO (see `file-storage`).
- Feed query scoped to the requesting user's role/membership.

## 7. Frontend responsibilities

- Announcement feed with pagination + category badges.
- Admin compose/editor form with audience picker + publish.
- Read-state indicator (if used); attachments downloadable.

## 8. Authorization rules

- Univ Admin: all announcements.
- Faculty/Dept Admin + Lecturers: publish within their scope (department/
  section).
- Students: view their audience feed only.

## 9. Validation rules

- Title/body required; valid audience scope + target; publish time sane.

## 10. Important edge cases

- Publishing to a large audience — queue the notifications (don't loop inline).
- Audience changes after publish (new student joins department) — existing
  announcement doesn't retroactively notify (documented behavior).
- Publishing with attachment — validate/store the file first.

## 11. Testing requirements

- Audience resolution correctness; feed scoping (student sees own only);
  publish triggers queued notifications; authorization (student cannot publish);
  attachment validation.

## 12. Must NOT

- Must NOT spam synchronous emails in a request loop.
- Must NOT let students publish announcements.
- Must NOT silently edit a published announcement without history.
- Must NOT invent push notifications (not in stack).

## Cross-references

- `skills/notifications/SKILL.md`, `skills/file-storage/SKILL.md`,
  `skills/authorization/SKILL.md`, `skills/vue/SKILL.md`.

## Agent behavior (mandatory)

1. Inspect existing implementation first. 2. Follow established conventions.
3. Do not rewrite working code. 4. No tech outside the stack. 5. No unnecessary
abstractions. 6. No duplicate business logic. 7. No invented relationships.
8. No bypassing authorization. 9. No hardcoded secrets. 10. No unrelated
module changes. 11. Run tests. 12. Explain architectural decisions.