---
name: educore-api
description: EduCore REST API conventions - URL naming, methods, status codes, pagination, filtering, sorting, JSON structure, error handling. Consult for every endpoint.
---

# EduCore REST API Conventions

The Vue frontend and Laravel backend communicate through a JSON REST API
(prefixed `/api`) for data mutations/queries. Page navigation itself flows
through **Inertia.js** (`Inertia::render()` in `routes/web.php`); the REST API
is the contract for everything data-related. Consistency across modules is
required.

## When to use

- Designing or changing any endpoint, response shape, error shape, or query
  parameter contract.

## URL naming

- Group by resource under `/api`, e.g. `api/students`, `api/courses`,
  `api/enrollments`, `api/announcements`.
- Nested only for genuinely child collections, e.g.
  `api/courses/{course}/sections`, `api/students/{student}/enrollments`.
- Prefer flat top-level resources with query filters over deep nesting.
- Singular resources (e.g. `api/me`) for the authenticated user profile.

## Methods & semantics

| Method | Meaning | Typical codes |
| --- | --- | --- |
| GET | list (paginated) or read one | 200 |
| POST | create | 201 |
| PUT/PATCH | update | 200 |
| DELETE | delete (or soft delete) | 200/204 |
| POST | custom action under a resource | 200 |

- Return `201` with the created resource for successful creates.
- Return `204` for deletes (or `200` with `{"deleted": true}` when a body is
  expected).
- Do not invent verbs in paths (`/delete`); use DELETE.

## Standard status codes

- `200` success
- `201` created
- `204` no content
- `400` malformed request / business rule violation with message
- `401` unauthenticated
- `403` authenticated but not authorized
- `404` not found
- `409` conflict (duplicate business key) where useful
- `422` validation errors (Laravel standard shape)
- `429` rate limited
- `500` unexpected error (generic message, details logged only)

## Consistent JSON success shape

All successful resource responses are **API Resources**. Wrap paginated lists:

```json
{
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 42,
    "last_page": 3
  }
}
```

Single resources return `{ "data": { ... } }`, consistent with Laravel
Resources. Keep the same key names across modules for the same concepts
(`id`, `name`, `created_at`, ...).

## Consistent JSON error shape

```json
{
  "message": "Human-readable summary",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

- `message` is always present.
- `errors` is present for `422` validation failures (Laravel default shape).
- Authorization failure: `403` + `{"message": "This action is unauthorized."}`.
- Authentication failure: `401` + `{"message": "Unauthenticated."}`.
- Configure the exception handler to produce this shape globally; controllers
  should never build ad-hoc error arrays.

## Pagination

- Query param `?page=1`.
- Optional `?per_page=` capped (e.g. max 100, default 15).
- Response includes the `meta` block above using `LengthAwarePaginator`.

## Filtering

- `?search=<term>` — loose `LIKE`/`ILIKE` search on name/code, scoped per
  endpoint.
- `?filters[field]=value` — exact matches (e.g. `filters[faculty_id]=3`,
  `filters[status]=active`).
- Validate filter keys; ignore unknown keys or return `422`.
- For range filters use `?from=` / `?to=` (dates) rather than inventing syntax.

## Sorting

- `?sort_by=<column>&sort_dir=asc|desc`.
- Whitelist allowed sort columns per endpoint — never accept arbitrary
  DB column names.

## Authentication & authorization errors

- Endpoints requiring a session/token → `auth:sanctum`.
- Unauthenticated: `401`.
- Authorized but not permitted: `403` via Policy.
- The frontend's `services/api.js` already clears the token on `401` — keep that
  behavior.

## Headers & conventions

- Request body: `application/json`; responses: `application/json`.
- The frontend sends `Accept: application/json`.
- File uploads use `multipart/form-data` only where files are uploaded — see
  `skills/file-storage/SKILL.md`.
- No server-rendered redirects in API paths; `302` not expected for `/api`.

## Exception handling

- Centralize in `app/Exceptions/Handler`:
  - `ValidationException` → `422` standard shape.
  - `Authentication/Authorization` → `401`/`403`.
  - Domain/business rule exceptions → `400`/`409` with a clear `message`.
  - `ModelNotFoundException` → `404`.
  - Unknown → `500` `{"message": "Server Error"}`; log the real exception.
- Throw domain exceptions from services; controllers return the mapped response.

## Rate limiting

- `throttle` on auth endpoints (login/password reset) — e.g. 5-10 per minute.
- Consider a global or module-level throttle for list endpoints.
- Return `429` with `Retry-After`; frontend shows a friendly message.

## Versioning

- V1 implied on the current codebase; add an explicit `/api/v1` prefix only if
  backward-incompatible changes are needed later. Do not invent it now.

## Prohibitions

- DO NOT return raw Eloquent models from controllers.
- DO NOT write per-controller custom error shapes — use the global ones.
- DO NOT expose internal columns via `select('*')` — use Resources +
  pagination.
- DO NOT accept arbitrary sort/filter columns.
- DO NOT put auth/business logic in routes.

## Validation checklist

1. URL follows `/api/<resource>` conventions; methods map to CRUD.
2. Success and error shapes match the templates above.
3. Lists are paginated with `meta`.
4. Filters/sort whitelisted and validated.
5. `401/403/404/422/429` handled consistently.
6. Backend authorization enforced (Policies), not just frontend hiding.
7. Tests cover the endpoint's happy + error paths (see `skills/testing`).

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