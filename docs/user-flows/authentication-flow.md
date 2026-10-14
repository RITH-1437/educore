# Authentication Flow

- **Validated against the code:** 2026-10-14 · **Status:** `[Implemented]`
- **Code:** `AuthenticatedSessionController` (web), `Api\AuthController` + `AuthService` (API),
  `LoginRequest`, `TwoFactorService`, `PasswordService`, `EnsureAccountIsActive`
- **Reports:** 28 (audit), 29 (password change / reset), 52 (two-factor), 55 (this audit: token expiry,
  API sign-out audit)

## 1. Web sign-in → dashboard

```mermaid
flowchart TD
    A([GET /login]) --> B[Email + password]
    B --> C[POST /login]
    C --> RL{More than 5 failures for<br/>this email and IP?}
    RL -- yes --> LO[Refused: try again in N seconds<br/>audit auth.lockout]
    RL -- no --> V{Password correct and<br/>account active?}
    V -- no --> F[Generic message — never says which<br/>audit auth.failed · attempt counted]
    F --> B
    V -- yes --> TF{Two-factor on?}
    TF -- yes --> P[Account parked in the session for 5 minutes<br/>no sign-in yet]
    P --> CH[/two-factor-challenge<br/>6-digit code or a recovery code/]
    CH --> CV{Valid and unused?}
    CV -- no --> CF[Refused · audit auth.two_factor_failed<br/>throttled]
    CF --> CH
    CV -- yes --> OK
    TF -- no --> OK[Signed in · session regenerated<br/>last_login_at · audit auth.login]
    OK --> R{Role}
    R -- Super Admin --> AD[/admin/dashboard/]
    R -- other roles --> RD[/dashboard → the role's page/]
```

`/` sends a signed-in user to the same destination; `/login` sends them away (guest middleware).

## 2. API sign-in

```mermaid
sequenceDiagram
    participant C as API client
    participant API as POST /api/login (throttle:login)
    participant S as AuthService
    C->>API: email, password [, two_factor_code | recovery_code]
    API->>S: validateCredentials (generic 422 on failure, counted)
    alt two-factor on and no code
        S-->>C: 422 two_factor_code required (not counted)
    else code wrong
        S-->>C: 422 (counted toward the limit)
    else ok
        S->>S: Login event → audit auth.login
        S-->>C: 200 { data: { token, user } }
    end
    C->>API: Authorization: Bearer token
    Note over C,API: token valid for SANCTUM_EXPIRATION minutes (default 7 days), then 401
    C->>API: POST /api/logout
    API->>S: delete the token · Logout event → audit auth.logout
```

## 3. While signed in

| Event | Effect |
|---|---|
| Account deactivated by a Super Admin | Next request ends the web session / deletes the token (`EnsureAccountIsActive`), audit `auth.access_revoked` |
| Role or department changed | Applies on the next request (read from the database each time) |
| Password changed (`/account/password`, current password required) | Other sessions and all API tokens end; audit `user.password_changed`; email notice |
| Web session idle longer than `SESSION_LIFETIME` (120 min) | Next request redirects to `/login` |
| API token older than `SANCTUM_EXPIRATION` | 401; the client signs in again; expired rows pruned daily |

## 4. Password reset

```mermaid
flowchart TD
    A([/forgot-password]) --> B[Email]
    B --> C[POST · throttle:password-reset]
    C --> D[Same answer whether the account exists or not]
    D --> E{Active account?}
    E -- yes --> F[Email: single-use link, 60 minutes<br/>audit auth.password_reset_requested]
    E -- no --> X[Nothing sent]
    F --> G[/reset-password/token/]
    G --> H[New password ≥ 8 characters with letters and numbers]
    H --> I{Token valid?}
    I -- no --> J[Error, request a new link]
    I -- yes --> K[Password saved · other sessions and tokens end<br/>audit auth.password_reset · email notice]
    K --> L([/login])
```

## 5. Two-factor management (`/account/two-factor`)

| Action | Rule | Audit |
|---|---|---|
| Turn on | New secret + QR code, confirmed with a valid code; eight recovery codes shown once (stored hashed) | `auth.two_factor_enabled` |
| New recovery codes | Current password required | `auth.two_factor_recovery_codes_regenerated` |
| Turn off | Current password required | `auth.two_factor_disabled` |
| Reset another account | Super Admin only, never their own (`DELETE /users/{user}/two-factor`) | `auth.two_factor_reset` |
| Use a recovery code | Spent on use | `auth.recovery_code_used` |

Every change is also emailed to the account owner.

## 6. Sign-out

`POST /logout` (web): session invalidated, CSRF token regenerated, audit `auth.logout`, redirect to `/`.
`POST /api/logout`: the presenting token is deleted and audited the same way.
