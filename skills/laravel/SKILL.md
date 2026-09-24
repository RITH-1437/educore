---
name: educore-laravel
description: Laravel 12 conventions for EduCore - controllers, models, services, requests, resources, policies, validation, Eloquent, transactions, queues, config. Consult for any backend implementation.
---

# EduCore Laravel Conventions

Backend: **Laravel 12**, PHP 8.4, Sanctum, running inside Docker Compose
(PHP 8.4-FPM, Composer installed in-container). All backend work follows this
skill.

## When to use

- Writing or changing Laravel code: controllers, models, migrations, services,
  requests, resources, policies, jobs, notifications, commands, tests.

## Before coding

- Inspect the existing implementation first; match its conventions.
- Do not rewrite working code just to restyle it.
- Run `vendor/bin/pint` before finishing (Pint is pinned in composer.json and
  enforced by CI).

## Controllers

- **Thin.** Parse input, authorize, call a service, return a resource
  (`Api\Resources\...`).
- Business logic MUST NOT live in controllers.
- Place in `app/Http/Controllers/<Module>/`, suffixed `Controller`.
- Conventions: `index`, `store`, `show`, `update`, `destroy`.
- Return `Response::json`/Resource; do not return raw models.

## Models / Eloquent

- Place in `app/Models`, singular PascalCase (`Faculty`, `StudentEnrollment`).
- Table names: snake_case plurals (`faculties`, `course_enrollments`).
- Use `protected $casts`, `$hidden` for secrets, relationships with explicit
  foreign keys.
- Use Eloquent relationships — never hand-write joins unless unavoidable.
- Add a `HasFactory` trait so factories/seeders work.
- **Avoid N+1**: eager-load (`with(...)`) or use `withCount` for summaries.
- Soft deletes ONLY where keeping records matters (documents, enrollments,
  grade history). Not on reference/lookup tables.

## Form Requests

- Complex or required validation goes in `app/Http/Requests/<Module>`.
- Use rules arrays with `Rule` classes (`Rule::exists`, `Rule::unique`).
- Do `authorize()` via Policy in the Request when convenient, else authorize in
  the controller.
- Keep simple one-two field validation inline when a full class is overkill.

## API Resources

- Shape responses with `app/Http/Resources/*` (use `JsonResource`).
- Return consistent keys; hide sensitive fields via `$hidden` on the model, NOT
  by stripping in the resource.
- Use `->whenLoaded(...)` / `->whenCounted(...)` for conditional includes.

## Services

- Business logic lives in `app/Services` plain classes (e.g.
  `Services/Registration/RegistrationService`).
- Static methods only when stateless; prefer explicit instances otherwise.
- Services may call other services (module reuse) — no duplicate logic.

## Policies

- All authorization goes through Policies (see `skills/authorization/SKILL.md`).
- Register in `AuthServiceProvider` (Laravel 12 auto-discovers by convention;
  keep the model/policy pairs adjacent).
- Use `Gate::authorize` / `$this->authorize` in controllers; never trust the
  frontend.

## Middleware

- `auth:sanctum` for authenticated API routes.
- `throttle` for rate limiting (see `skills/api/SKILL.md` for limits).
- Module-scoped middleware only when shared across the module; prefer Policies
  for per-resource rules.

## Jobs / Queues

- Slow or side-effect work → queued Jobs (Redis queue; `QUEUE_CONNECTION=redis`).
- Examples: sending emails/Telegram notifications, file processing, expensive
  analytics.
- Always dispatch via `dispatch()` or `->onQueue()` with a sensible queue name.
- Handle failures: `failed()` hook + retries (configure `--tries` / `attempts`).

## Events / Listeners

- Use only for genuine decoupled reactions ("grade submitted" → GPA recalc +
  notify). If a listener could just be a synchronous service call, prefer the
  service call.
- Name events in past tense: `GradeSubmitted`, `EnrollmentApproved`.

## Notifications

- Use Laravel Notifications (`app/Notifications`).
- Channels available: email + Telegram (see
  `skills/notifications/SKILL.md`).
- Never hardcode credentials; read from `config/services.php` ← `.env`.

## Commands

- Repetitive/system work (recompute GPA, send reminders) → `app/Console/Commands`
  scheduled via route in `routes/console.php`.
- Keep commands thin: call services.

## Validation

- Backend is the single source of truth for validation.
- Always validate file uploads (type + size + mime).
- Business-rule validation beyond simple field rules → Form Request or service
  validation (e.g. prerequisites must be met before enrollment).

## Transactions

- Multi-step operations MUST be atomic: wrap in `DB::transaction(...)`.
- Roll back on exception; rethrow so queued jobs know the failure.
- Examples: enrollment (insert enrollment + assignment + seats), grade posting
  (grade + GPA recalc).

## Exceptions

- API errors are JSON, consistent shape (see `skills/api/SKILL.md`).
- Use `app/Exceptions/Handler` to map exceptions; throw domain exceptions from
  services (`DomainException`, `BusinessRuleException`) rather than returning
  error booleans from services.

## Logging

- Use `Log::channel('daily')->info(...)` / contextual `->withContext()`.
- Never log passwords or secrets.
- Log important events (enrollment, grade changes, admin actions) — see
  `skills/audit-logging/SKILL.md`.

## Configuration & environment

- Config lives in `config/*.php`; read via `config(...)`, never `env(...)` in
  app code.
- `.env` values already defined in `docker/.env.docker.example` map into config
  (database, redis, aws, mail, telegram). Add new vars to BOTH
  `docker/.env.docker.example` (committed template) and `backend/.env` (local,
  git-ignored).
- Never hardcode credentials anywhere.

## Conventions summary

| Item | Convention |
| --- | --- |
| Controllers | thin, in `Http/Controllers/<Module>/` |
| Business logic | `app/Services` classes |
| Validation | `Http/Requests` for complex cases |
| Auth | Sanctum middleware + Policies |
| Responses | API Resources, consistent JSON |
| Relationships | Eloquent, eager-load to avoid N+1 |
| Atomicity | `DB::transaction` for multi-step writes |
| Slow work | queued Jobs (Redis) |
| Notifications | Laravel Notifications (email/Telegram) |
| Formatting | `vendor/bin/pint` before commit |

## Validation checklist

1. `vendor/bin/pint` passes.
2. Controllers thin; logic in services.
3. No N+1 queries added.
4. Multi-step writes are transactional.
5. No secrets in code/config/commits.
6. Related API and Database conventions respected
   (`skills/api/SKILL.md`, `skills/database/SKILL.md`).
7. Tests written/updated for changed behavior.

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