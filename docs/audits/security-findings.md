# Security Findings (2026-10-14)

Part of the [post-login audit](post-login-audit.md). No secret, token or real record appears in this
document. Controls are described as implemented; EduCore has not been independently audited or
certified against a security standard.

## 1. Findings

| ID | Severity | Finding | Status |
|---|---|---|---|
| SEC-01 | P2 | API tokens never expired (`sanctum.expiration = null`) | Fixed |
| SEC-02 | P3 | API sign-out was not audited | Fixed |
| SEC-03 | P3 | Uploaded avatars are public by sequential user id (report 45 decision) | Documented → BD-06 |
| SEC-04 | P3 | Validation answers before the policy on some write endpoints (422 instead of 403 for a malformed request from a user without access; no record data is returned) | Accepted |
| SCOPE-01 | P2 | A former department keeps access to a transferred student (report 32 definition) | Documented → BD-05 |

### SEC-01 — API token lifetime

- **Before:** a token from `POST /api/login` stayed valid until sign-out, a password change or
  deactivation; a leaked token kept working indefinitely.
- **After:** `config/sanctum.php` reads `SANCTUM_EXPIRATION` (minutes, default **10 080 = 7 days**,
  `0` disables expiry). Expired tokens answer 401; `sanctum:prune-expired --hours=24` runs daily at
  01:20. Web sign-in is unaffected (session, `SESSION_LIFETIME`).
- **Test:** `LoginTest::test_api_tokens_expire_after_the_configured_lifetime` — valid one minute
  before the limit, 401 one minute after.
- **Decision:** 7 days balances integrations against exposure; the value is an environment setting
  (README §2).

### SEC-02 — API sign-out audit

- **Before:** web sign-out fires Laravel's `Logout` event, which `AppServiceProvider` records as
  `auth.logout`; deleting an API token fires nothing, so API sign-outs left no trace while API sign-ins
  did (`AuthService::login` dispatches `Login`).
- **After:** `AuthService::logout()` dispatches `Logout('sanctum', $user)` after deleting the token.
- **Test:** `LoginTest::test_api_logout_revokes_the_token_and_is_audited`.

## 2. Verified controls

| Area | Control | Evidence |
|---|---|---|
| Authentication | Hashed passwords; generic failure message; web and API sign-in rate limited (5 attempts, `LoginRequest`, `throttle:login`); inactive accounts refused; optional TOTP two-factor with single-use recovery codes, challenged before any session exists | `LoginTest`, `TwoFactorTest`, report 52 |
| Session lifecycle | Web sign-out invalidates the session and regenerates the CSRF token; a deactivated account loses its session / token on the next request (`EnsureAccountIsActive`, audited `auth.access_revoked`); password change or reset ends other sessions and tokens | `LoginTest`, `PasswordTest`, report 29 |
| Role changes | Role and department are read from the database on every request, so a change applies at once; a Super Admin cannot remove their own access or the last active Super Admin | `UserManagementTest` (report 50) |
| Function-level access | Every non-public route needs `auth` / `auth:sanctum`; `EnsureUserHasRole` per route; every protected action calls a policy or gate | [route matrix](route-api-permission-matrix.md) |
| Object-level access (BOLA / IDOR) | Policies check department (`DepartmentScope`), teaching assignment (`section_lecturers`, active lecturer), enrollment or ownership with the record; lists are scoped server-side (`visibleTo`) | `RoleIsolationMatrixTest`: 75 own-vs-other probes, 27 function-level refusals, guests 401 |
| Self-service | Profile, inbox, notification preferences and two-factor act on the request user only, with no id in the path; the profile update persists an explicit field list (no role / department / status) | `ProfileTest`, `InboxTest` |
| Mass assignment | No model with `$guarded = []`; no `$request->all()` into a model (the Telegram webhook passes the update to a service that reads named fields) | code scan |
| Files | Private storage (MinIO); downloads through authorized routes as attachments with the stored, content-derived type and `nosniff`; storage keys never leave the server | report 50 F-06 |
| Public endpoints | Verification shows document type, holder name and number, issue date, issuer, checksum — rate limited and logged; Telegram webhook needs the configured secret (`hash_equals`), 404 without one; catalog public data only, rate limited | `DocumentTest`, `TelegramLinkingTest`, `CatalogTest` |
| Injection / XSS | Eloquent bindings; search uses bound `ilike` parameters; no `v-html` / `innerHTML` in the frontend (report 50) | code scan |
| Audit | Append-only `audit_logs` (database trigger); sign-in, sign-out (web and now API), failures, lockouts, two-factor changes, sensitive record changes | `AuditTest` |
| Notifications | Queued after commit; grade notices name the course, not the grade; reset links are never stored in the inbox | report 42 |

## 3. Not covered by this audit

- Penetration testing, dependency CVE review beyond report 50's `composer audit` / `npm audit`,
  TLS / infrastructure hardening of a production deployment, and backup / restore.
- Real email and Telegram delivery (no credentials in this environment; the queued jobs and their
  payloads are covered by `NotificationTest`, `ClassRemindersTest`, `TelegramLinkingTest`).
