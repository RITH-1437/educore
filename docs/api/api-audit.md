# EduCore API Audit

- **Date:** 2026-09-28
- **Scope:** `routes/api.php`, API controllers, Form Requests, API Resources,
  models, policies/middleware, Swagger/OpenAPI, and frontend API service usage.
- **Runtime:** Local Docker Compose development stack. No database schema or
  application business logic was changed during this audit.
- **Swagger UI:** `http://localhost/api/documentation/ui`
- **OpenAPI JSON:** `http://localhost/api/documentation`
- **Generation:** `docker compose --project-directory . -f docker/docker-compose.yml exec -T backend php artisan l5-swagger:generate`

## Executive Summary

| Measure | Result |
|---|---:|
| Application API route definitions (`routes/api.php`) | 19 |
| HTTP method/path operations, expanding combined `PUT|PATCH` routes | 21 |
| Swagger operations after documentation corrections | 21 |
| Undocumented application operations | 0 |
| Extra Swagger operations not in the application API routes | 0 |
| Swagger generation errors after corrections | 0 |
| Current documentation mismatches fixed in this audit | 8 |
| Remaining API contract / security concerns reported (not changed) | 4 |
| Frontend API-service calls found | 0 |

`route:list --path=api` also displays L5-Swagger's JSON endpoint
`/api/documentation`, UI endpoint `/api/documentation/ui`, and
`/api/oauth2-callback`; these package routes are not application API endpoints.
Laravel registers `HEAD` alongside each `GET`; OpenAPI represents the GET
operation, rather than counting each automatic HEAD alias as a separate
operation.

## Method

Compared registered routes and middleware with controller attributes, Form
Request rules, Resources, model serialization, policies, Swagger schemas, and
frontend references to `frontend/src/services/api.js`. Checked the generated
JSON for valid referenced schemas and compared documented HTTP method/path pairs
against application routes. The frontend's API service has no consumers in
`frontend/src`; the current page flows use Inertia/web routes.

## Findings and Corrections

### A-01 — OpenAPI generation previously failed on undefined schema references

- **Endpoint:** `GET /api/academic-years` (generation stopped at this operation;
  the same issue applied to the other academic-year and semester annotations).
- **Current backend behavior:** The route/controller runs and returns Laravel
  API Resource JSON when called.
- **Current Swagger behavior:** Before this audit, generation failed because
  `#/components/schemas/AcademicYearCollection` was not defined. The remaining
  Academic Year and Semester request/response schema references were also
  missing.
- **Frontend behavior:** There are no consumers of the shared REST `api.js`
  service; academic-year pages currently use Inertia web routes.
- **Problem:** Swagger generation could not produce a fresh complete API
  contract; the stored JSON was stale and omitted all academic-year and semester
  operations.
- **Recommended correction:** Added documentation-only schema definitions for
  academic-year and semester resources/collections, request bodies, and single
  Resource response envelopes. Generation now succeeds.

### A-02 — Resource responses documented without Laravel's `data` envelope

- **Endpoint:** `POST /api/users`, `GET /api/users/{user}`, `PUT|PATCH
  /api/users/{user}`, and single academic-year/semester resource responses.
- **Current backend behavior:** `JsonResource` responses are wrapped as
  `{"data": ...}`. Create endpoints return the same envelope with HTTP 201.
- **Current Swagger behavior:** The user and academic-year annotations referred
  directly to `User`/`AcademicYear`; Semester responses referred directly to
  `Semester`, omitting the runtime `data` wrapper.
- **Frontend behavior:** No REST API consumers found; Inertia pages load through
  web controllers.
- **Problem:** Generated client types and interactive examples would not match
  actual response JSON.
- **Recommended correction:** Added `UserResourceResponse`,
  `AcademicYearResourceResponse`, and `SemesterResourceResponse` envelope
  schemas and used them for single-resource responses. No runtime response
  behavior changed.

### A-03 — Semester list response documented as a raw array

- **Endpoint:** `GET /api/academic-years/{academicYear}/semesters`.
- **Current backend behavior:** `SemesterResource::collection()` returns an
  unpaginated Resource collection with a top-level `data` array.
- **Current Swagger behavior:** It previously declared a raw JSON array.
- **Frontend behavior:** No REST service consumer; the page uses Inertia props.
- **Problem:** The documented body did not match Laravel's Resource collection
  response.
- **Recommended correction:** Added `SemesterCollection` with a `data` array and
  updated the annotation. No pagination metadata is claimed because this route
  currently returns all semesters for the year.

### A-04 — Academic-year path parameter casing did not match route binding

- **Endpoint:** Academic-year item and status routes, plus all nested semester
  routes.
- **Current backend behavior:** Route placeholders are `{academicYear}` and
  `{semester}`.
- **Current Swagger behavior:** Academic-year annotations used
  `{academic_year}` / `academic_year` before correction.
- **Frontend behavior:** No REST service calls; Inertia routes use web URL
  patterns.
- **Problem:** Swagger's generated operation paths and parameters diverged from
  registered paths.
- **Recommended correction:** Changed annotation placeholders and parameter
  names to match camelCase route binding. Generated paths now match registered
  route paths.

### A-05 — User creation schema omitted a required confirmation field

- **Endpoint:** `POST /api/users`.
- **Current backend behavior:** `StoreUserRequest` requires
  `password_confirmation` through Laravel's `confirmed` rule, as well as a
  password of at least 8 characters.
- **Current Swagger behavior:** `StoreUserRequest` described the confirmation
  property but did not mark it required.
- **Frontend behavior:** No REST API service consumer found.
- **Problem:** Swagger clients could send a body that Swagger described as
  complete but the server rejects with 422.
- **Recommended correction:** Marked `password_confirmation` required in the
  request schema. Runtime validation was not changed.

### A-06 — User paginated collection schema was incomplete

- **Endpoint:** `GET /api/users`.
- **Current backend behavior:** Laravel paginated Resource collection returns
  `data`, `links`, and `meta` (including pagination link metadata).
- **Current Swagger behavior:** The schema described only `data` and `meta`, and
  `PaginationMeta` omitted Laravel's `links` and `path` fields.
- **Frontend behavior:** No API service calls found.
- **Problem:** Consumers relying on pagination navigation metadata could not
  generate accurate client models.
- **Recommended correction:** Added collection `links` and the pagination
  `links`/`path` properties to schemas.

### A-07 — Login examples included a working-looking default credential pair

- **Endpoint:** `POST /api/login`.
- **Current backend behavior:** Accepts validated email and password; limiter is
  configured for five attempts per minute per email/IP key. Invalid credentials
  are returned as Laravel validation errors.
- **Current Swagger behavior:** The previous examples used a project admin email
  and a concrete default password.
- **Frontend behavior:** The current login page posts to the web session route
  `/login`; no REST API service uses `/api/login`.
- **Problem:** Documentation examples looked like usable credentials and could
  encourage reliance on seeded defaults.
- **Recommended correction:** Replaced them with non-secret placeholders and
  documented the optional `remember` field read by the shared login request.

### A-08 — OpenAPI overview described only user-role authorization

- **Endpoint:** API overview applies to `/api/users` and
  `/api/academic-years` plus nested semesters.
- **Current backend behavior:** User routes require Sanctum and `super-admin`;
  calendar routes require Sanctum and either `super-admin` or
  `university-admin`.
- **Current Swagger behavior:** The document-level description described only
  user management role authorization.
- **Frontend behavior:** Existing Inertia pages hide navigation according to
  role; this is not the REST client path.
- **Problem:** API overview was incomplete for calendar endpoints.
- **Recommended correction:** Updated the overview to mention both role scopes
  while retaining per-operation security declarations.

## Remaining Contract and Security Concerns

These concerns involve runtime behavior or an explicit API contract choice and
were **not changed** during this documentation-only audit.

### R-01 — Disabled users can authenticate through the API

- **Endpoint:** `POST /api/login`, then all token-protected endpoints.
- **Current backend behavior:** `AuthService::login()` calls the shared
  `LoginRequest::authenticate()`, which calls `Auth::attempt()` with email and
  password. Neither it nor the API middleware checks `users.is_active`.
  Therefore, a user whose `is_active` is false can still receive a Sanctum token
  if the credentials are valid.
- **Current Swagger behavior:** The login description says credentials are
  verified but does not describe inactive-account eligibility.
- **Frontend behavior:** Login UI submits to web-session `/login`; the same
  shared authentication request also does not check active status.
- **Problem:** The account has an explicit active flag, but it currently does
  not disable authentication. This is an authorization/security policy decision
  requiring owner confirmation.
- **Recommended correction:** Confirm whether inactive accounts must be denied
  login. If yes, update shared authentication behavior, document the exact
  response, and add API + web tests. No business logic changed in this audit.

### R-02 — Invalid API credentials return 422, not the documented convention's 401

- **Endpoint:** `POST /api/login`.
- **Current backend behavior:** Invalid credentials throw
  `ValidationException::withMessages(...)`, which produces HTTP 422 with a
  field-keyed `errors.email` payload. Missing/invalid fields also return 422.
- **Current Swagger behavior:** Documents 422 for validation failures or invalid
  credentials.
- **Frontend behavior:** The API service's 401 interceptor clears a stored
  token, but the current login form uses web Inertia auth and maps validation
  errors.
- **Problem:** Swagger matches runtime, but `skills/authentication/SKILL.md`
  says failed API login returns 401. This is an internal contract/convention
  mismatch, not a generation error.
- **Recommended correction:** Choose and test a single login failure status
  contract (401 for invalid credentials, 422 for malformed/missing fields is a
  common distinction). Runtime currently produces 422 for both invalid
  credentials and validation errors; the Swagger 422 description is consistent
  with that. Do not change it without choosing the desired contract. Not
  changed.

### R-03 — PATCH routes use full-update validation

- **Endpoint:** `PATCH /api/users/{user}` and
  `PATCH /api/academic-years/{academicYear}`.
- **Current backend behavior:** Laravel routes PATCH to the same update actions
  and Form Requests as PUT. Required fields remain required (`name`, `email`,
  `role_id` for users; `code`, `name`, `start_date`, `end_date` for years).
- **Current Swagger behavior:** PATCH operations reuse the corresponding update
  request schemas. Descriptions now state that required fields remain required.
- **Frontend behavior:** No REST API service calls found.
- **Problem:** PATCH normally implies partial modification; the current behavior
  accepts the method but still requires the complete validated update fields.
- **Recommended correction:** Keep/document PUT semantics on both verbs or
  approve a business-contract change to make PATCH validation optional per
  field. No validator/business change was made.

### R-04 — REST API service has no frontend callers; current UI uses Inertia

- **Endpoint:** Relevant to all endpoints; especially `/api/login`, `/api/logout`,
  `/api/users`, `/api/academic-years`, and nested semesters.
- **Current backend behavior:** REST routes work independently and use Sanctum
  bearer tokens on protected operations.
- **Current Swagger behavior:** Describes these REST endpoints and bearer
  authentication.
- **Frontend behavior:** `frontend/src/services/api.js` configures an Axios
  bearer-token client, but searches found no imports/calls outside that file.
  Login uses `/login`; CRUD pages use Inertia web routes.
- **Problem:** The documented REST API is not the data path currently used by
  the Vue pages. API changes would not necessarily affect those pages, and the
  token client is currently unused.
- **Recommended correction:** Decide whether the frontend should continue using
  Inertia/web sessions or consume the REST API. Keep the API documentation
  authoritative either way; if switching consumers later, migrate intentionally
  and test both auth modes.

## Endpoint Inventory

Application endpoint counts below exclude the two L5-Swagger package routes.
Each combined update route contributes one PUT and one PATCH operation.

| Method | Path | Authentication / authorization | Documentation |
|---|---|---|---|
| POST | `/api/login` | Public; `throttle:login` | Documented |
| POST | `/api/logout` | `auth:sanctum` | Documented |
| GET | `/api/user` | `auth:sanctum` | Documented |
| GET | `/api/health` | Public | Documented |
| GET | `/api/users` | Sanctum + super-admin | Documented |
| POST | `/api/users` | Sanctum + super-admin | Documented |
| GET | `/api/users/{user}` | Sanctum + super-admin | Documented |
| PUT, PATCH | `/api/users/{user}` | Sanctum + super-admin | Both documented |
| DELETE | `/api/users/{user}` | Sanctum + super-admin | Documented |
| GET | `/api/academic-years` | Sanctum + super-admin or university-admin | Documented |
| POST | `/api/academic-years` | Sanctum + super-admin or university-admin | Documented |
| GET | `/api/academic-years/{academicYear}` | Sanctum + super-admin or university-admin | Documented |
| PUT, PATCH | `/api/academic-years/{academicYear}` | Sanctum + super-admin or university-admin | Both documented |
| POST | `/api/academic-years/{academicYear}/status` | Sanctum + super-admin or university-admin | Documented |
| DELETE | `/api/academic-years/{academicYear}` | Sanctum + super-admin or university-admin | Documented |
| GET | `/api/academic-years/{academicYear}/semesters` | Sanctum + super-admin or university-admin | Documented |
| POST | `/api/academic-years/{academicYear}/semesters` | Sanctum + super-admin or university-admin | Documented |
| POST | `/api/academic-years/{academicYear}/semesters/{semester}/status` | Sanctum + super-admin or university-admin | Documented |
| DELETE | `/api/academic-years/{academicYear}/semesters/{semester}` | Sanctum + super-admin or university-admin | Documented |

## Authentication and Authorization Check

- OpenAPI declares an HTTP bearer security scheme named `sanctum` with bearer
  format “Sanctum personal access token”.
- `POST /api/login` and `GET /api/health` are explicitly anonymous in OpenAPI.
- `POST /api/logout`, `GET /api/user`, user-management routes, and academic
  calendar routes declare the `sanctum` security requirement.
- Runtime middleware matches those declarations: user management additionally
  requires `super-admin`; academic calendar routes require `super-admin` or
  `university-admin`.
- Policies additionally gate User, AcademicYear, and Semester operations.
- The remaining inactive-user issue (R-01) is not represented as API behavior
  because runtime currently permits inactive users to authenticate.

## Validation Results

- `docker compose --project-directory . -f docker/docker-compose.yml ps`: all
  long-running services were healthy.
- Swagger generation command above: **passed** with no output warnings/errors.
- `php artisan route:list --path=api`: **21 listed route entries**, including
  two L5-Swagger package routes. `routes/api.php` has **19 application route
  definitions**.
- Expanded application operations: **21 method/path operations**.
- Generated OpenAPI: **12 paths, 21 operations**. Method/path comparison: **no
  undocumented or extra application operations**.
- Swagger UI returned HTTP 200 at `http://localhost/api/documentation/ui`.
- OpenAPI JSON returned HTTP 200 at `http://localhost/api/documentation`.
- Sanctum bearer scheme and per-operation security declarations verified.
- Full API backend test suite was not run as part of this audit; no runtime code
  or business logic was changed.

## Recommended Next Steps

1. Confirm the policy for inactive accounts and add shared web/API auth tests if
   they must be blocked.
2. Decide whether API login failures should follow the authentication skill's
   401 contract or the current 422 validation contract.
3. Decide whether PATCH should remain full-update validation or become genuinely
   partial; keep docs aligned with the decision.
4. Decide whether Vue pages should consume the REST API or continue using
   Inertia/web-session endpoints; the current Axios API service has no callers.
5. Add an automated Swagger-generation and route-vs-spec comparison check to CI
   to prevent missing schemas or endpoint drift from returning.
