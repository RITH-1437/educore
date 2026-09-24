---
name: educore-authentication
description: EduCore authentication - Student ID / password login, Laravel Sanctum tokens, session handling, password reset, rate limiting. Consult for any auth-related work.
---

# EduCore Authentication

Authentication proves a user is who they say they are. Authorization (what they
can do) is separate — see `skills/authorization/SKILL.md`.

## When to use

- Implementing or changing login, logout, session/token handling, password
  reset, password rules, or auth middleware.

## Credentials

- Primary identifier: **Student ID + password** (students).
- Admins/lecturers may log in with their institutional ID/email + password
  (consistent with `User.email` / unique username — see
  `skills/student-management`, `skills/lecturer-management`).
- Passwords stored **hashed** only (`bcrypt`/`argon2` via Eloquent `hashed`
  cast). **Never plaintext, never reversible.**

## Laravel Sanctum (token-based auth)

- Use `laravel/sanctum` (pinned in `composer.json`).
- Protect API routes with `Route::middleware('auth:sanctum')`.
- On login, issue a personal access token; the frontend stores it in
  `localStorage` as `auth_token` (already wired in `frontend/src/services/api.ts`).
- Tokens are revocable: `revoke` on logout and `delete` old tokens when a
  password is changed.

## Login / logout flow

1. POST credentials to `/api/login` (rate-limited — see below).
2. Verify; on failure return `401` with a **generic** message (do not reveal
   whether the ID exists).
3. On success return the token + minimal user payload (id, name, role).
4. Logout: revoke the current token, return `200`. Frontend clears
   `localStorage`.

## Password security

- Enforce a sensible minimum length on reset/change (≥ 8, recommend more).
- Use Laravel's `Hash::make` / `hashed` cast — never manual hashing.
- Password reset: generate a signed, single-use, expiring link; invalidate
  previous tokens on password change.
- Never log or return the password or reset token.

## Session / token handling (frontend)

- Store only the token (already done in `api.ts`).
- On any `401`, `api.ts` clears the token automatically; the router guard then
  redirects to `/login`. Do not store passwords, do not store PII beyond the
  minimal user object needed for display/permissions.

## Rate limiting

- `throttle` middleware on `/api/login` and password-reset endpoints
  (e.g. 5-10/min per IP/user).
- Return `429` with `Retry-After`.
- Frontend shows a friendly "too many attempts" message.

## Account security

- Optional: lockout / exponential backoff after repeated failed logins.
- Protect against account enumeration (generic error messages).
- Session/token expiry (Sanctum `expires_at`) and revoke-on-logout.
- Audit login events (see `skills/audit-logging/SKILL.md`).

## NEVER

- NEVER store plaintext passwords.
- NEVER hardcode credentials or default passwords in production code paths.
- NEVER expose the password field in API responses (`$hidden` on the model).
- NEVER skip `auth:sanctum` on protected routes.

## Validation checklist

1. Passwords hashed; never returned/logged.
2. `auth:sanctum` on all protected routes.
3. Login + reset rate-limited.
4. Tokens revoked on logout/password change.
5. Generic error messages (no account enumeration).
6. Covered by tests (see `skills/testing/SKILL.md` — Login flow).

## Cross-references

- `skills/authorization/SKILL.md` — RBAC on top of identity.
- `skills/security/SKILL.md` — OWASP, secrets, rate limiting.
- `skills/api/SKILL.md` — `401`/`429` response shapes.

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