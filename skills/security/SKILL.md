---
name: educore-security
description: EduCore security - OWASP principles, auth/authz, CSRF/XSS/SQLi, mass assignment, file uploads, secrets, rate limiting, logging, dependency security. Consult for any security-relevant change.
---

# EduCore Security

EduCore handles academic records (students, grades, documents) — security is a
first-class requirement, not an afterthought.

## When to use

- Any change touching authentication, authorization, input handling, file
  uploads, secrets, logging, or dependencies.

## OWASP alignment

Cover the OWASP Top 10, especially:

- **A01 Broken Access Control** — enforce Policies server-side (see
  `skills/authorization/SKILL.md`).
- **A02 Cryptographic Failures** — hashed passwords, no plaintext secrets.
- **A03 Injection** — parameterized queries (Eloquent/Query Builder), input
  validation.
- **A04 Insecure Design** — correct business rules (prerequisites, grades).
- **A05 Security Misconfiguration** — `.env` for config, not code; DEBUG off
  in production.
- **A07 Auth Failures** — rate-limited login, token revocation (see
  `skills/authentication/SKILL.md`).
- **A08 Data Integrity Failures** — validate/serialize all input.
- **A09 Logging & Monitoring** — audit logging (see `skills/audit-logging`).
- **A10 SSRF** — do not fetch user-supplied URLs server-side.

## Authentication security

- Hashed passwords only; generic login errors (no account enumeration);
  rate-limited auth endpoints; revoke tokens on password change/logout.
  See `skills/authentication/SKILL.md`.

## Authorization

- Backend enforces every action; frontend hiding is UX only.
- Scope queries to the authenticated user. See `skills/authorization/SKILL.md`.

## CSRF / XSS

- API uses Sanctum token auth (Bearer header) — stateless API, so traditional
  cookie-CSRF risk is minimized; if cookies are ever used for auth, enable
  Laravel CSRF protection appropriately.
- Prevent XSS by treating all user content as data: return JSON, escape in the
  frontend (Vue escapes by default); sanitize any `v-html`.
- Never echo raw user input into HTML without escaping.

## SQL injection

- Use Eloquent / Query Builder (parameter binding). Never concatenate user input
  into raw SQL; if raw is unavoidable use bindings (`DB::select('... ?', [$x])`).
- Whitelist sort/filter columns (see `skills/api/SKILL.md`).

## Mass assignment

- Define `$fillable` / `$guarded` correctly on every model.
- Never `$guarded = []` on models with sensitive fields.
- Never pass `request()->all()` straight into `create()` — validate first.

## Validation

- Validate all input (Form Requests) — required, types, ranges, formats.
- Validate business rules (prerequisites, capacity) in services, not just field
  shapes.

## File upload security

- Validate MIME type, extension, and size; generate server-side filenames; store
  outside the webroot (MinIO). See `skills/file-storage/SKILL.md`.
- Do not allow executable types; restrict extensions explicitly.

## Secrets

**NEVER commit:**
- `.env` (git-ignored)
- passwords
- API keys
- Telegram bot tokens
- database credentials
- AWS/S3 credentials

- Read secrets only via `config(...)` ← `.env`.
- Use GitHub Secrets for CI/workflows (Telegram, etc.).
- `.env.docker.example` may contain **dev placeholder** values only — clearly
  non-real, documented as such.

## API security

- `auth:sanctum` on protected routes; Policies for actions; rate limiting on
  auth and expensive endpoints; consistent `401/403/422/429` responses (see
  `skills/api/SKILL.md`).
- Paginate; never `SELECT *` unbounded.

## Rate limiting

- `throttle` login/password reset (5-10/min); consider throttles on costly
  report/analytics endpoints.

## Logging & sensitive data

- Log security-relevant events (login, password change, admin actions) — see
  `skills/audit-logging/SKILL.md`.
- Never log passwords, tokens, or full request bodies with secrets.
- Hide sensitive attributes in responses (`$hidden` on models).

## Dependency security

- Keep `composer.json`/`package.json` and lock files current.
- Review advisories (`composer audit`, `npm audit`) when upgrading.
- Do not add unvetted third-party packages.

## Prohibitions

- DO NOT hardcode or commit any secret listed above.
- DO NOT trust client-supplied IDs/roles for authorization.
- DO NOT use `DB::raw` with user input.
- DO NOT disable security middleware (CSRF, throttle) to "make tests pass".

## Validation checklist

1. No secrets in the diff; `.env` still git-ignored.
2. Authorization enforced server-side (Policy) for the changed path.
3. Input validated; mass assignment safe.
4. File uploads validated (type/size) if touched.
5. Secrets read from config/env only.
6. Related skills respected: `authentication`, `authorization`, `file-storage`,
   `audit-logging`.

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