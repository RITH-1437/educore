---
name: educore-architecture
description: EduCore system architecture - modular monolith, MVC, dependency direction, and structural conventions. Consult before any architectural decision or structural refactor.
---

# EduCore Architecture

EduCore is a **University Digital Administration Platform** for Cambodian
universities. This skill defines how the system is architected and how new code
must fit into that structure.

## When to use

- Adding a new module, feature area, or subsystem.
- Deciding where code belongs (controller, service, model, etc.).
- Refactoring or restructuring existing code.
- Reviewing whether an abstraction/pattern is justified.

## Architecture model

- **Modular Monolith** — one Laravel application, one Vue application, one
  deployment. Modules are separated by conventions (directories, namespaces,
  route prefixes) inside the monolith, NOT as separate deployable services.
- **MVC + Inertia** — Laravel provides Model, Controller, View. Vue components
  are the View layer, rendered through Inertia.js: controllers return
  `Inertia::render('Page', props)` and the SPA shell updates without full
  reloads.
- **Hybrid API-first** — the Vue frontend renders pages via Inertia; the JSON
  REST API (`/api`) remains the contract for data mutations/queries (Axios
  service modules).
- **Client → Nginx → (Laravel PHP-FPM | Vite dev server) → PostgreSQL/Redis/MinIO**

## Stack (fixed — do not introduce others)

- Backend: Laravel 12, PHP 8.4, Sanctum, PostgreSQL, Redis, MinIO (S3-compatible)
- Frontend: Vue 3 (JavaScript, no TypeScript), Inertia.js, Pinia, Axios,
  Tailwind CSS, Chart.js
- Infra: Docker Compose, Nginx, pgAdmin
- External: GitHub Actions, Telegram, Email, Laravel Cloud (production)

## Layering / dependency direction

Dependencies flow downward only:

```
HTTP routes
   ↓
Controllers (thin)  ← Form Requests, Policies
   ↓
Services (business logic)
   ↓
Models / Repositories (data access) → PostgreSQL
```

- **Controllers**: thin. Parse the request, call a service, return a JSON
  response (Resource). No business rules.
- **Services**: hold business logic. Named after the operation or domain
  (`RegistrationService`, `GradingService`). Plain PHP classes in
  `app/Services`.
- **Repositories**: only when a query is complex/reused and must be testable in
  isolation. Do NOT wrap every model with one "for architectural appearance".
- **Models**: Eloquent; define relationships, scopes, casts, and attribute
  logic only.
- **Form Requests**: complex/required validation.
- **Policies**: all authorization beyond "authenticated".
- **API Resources**: shape all JSON responses.
- **Events/Listeners**: only for genuinely asynchronous, decoupled reactions
  (e.g. "grade submitted" → recompute GPA, notify student).
- **Jobs/Queues**: heavy or slow work (email, Telegram, file processing,
  analytics aggregation). Redis queue.
- **Notifications**: Laravel Notifications for email/Telegram.

## Explicit prohibitions

- DO **not** introduce microservices, grep-sidecar services, or split the
  monolith without project-level approval.
- DO **not** create repositories/interfaces/factories/services/abstract base
  classes just for architectural appearance. Add them only when they solve a
  real problem (complex queries, shared behavior, testability).
- DO **not** duplicate business logic between modules — reuse services.
- DO **not** write business logic inside controllers, routes, blade, or Vue
  pages.
- DO **not** add technologies outside the stack above.

## Module organization

Modules are grouped by domain in `app/`:

```
app/
├── Models/
├── Http/Controllers/<Module>/
├── Http/Requests/<Module>/
├── Http/Resources/<Module>/
├── Services/<Module>/
├── Policies/
├── Notifications/
└── Enums/
```

Routes are grouped by module in `routes/api.php` with a module prefix and
`AuthController`-style naming. See `skills/api/SKILL.md` for the route shape.

## Backend / frontend boundary

- The frontend never queries the database or Redis directly and never calls
  Laravel internal classes.
- The backend renders the Vue UI through Inertia (page components live in
  `frontend/src/pages`, served by `Inertia::render()`).
- Contract between them: page components via Inertia props, plus the JSON REST
  API + JSON structure defined in `skills/api/SKILL.md`.

## Validation checklist

1. New code follows the layering and dependency direction above.
2. No new technology was introduced outside the fixed stack.
3. No unnecessary abstraction was added.
4. No business logic lives in a controller or frontend component.
5. Controllers remain thin; logic is in services.
6. Authorization uses Policies; validation uses Form Requests where required.
7. Atomic multi-step DB work is wrapped in transactions.
8. Related skills respected: `laravel`, `vue`, `database`, `api`.

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