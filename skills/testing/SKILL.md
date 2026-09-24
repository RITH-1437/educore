---
name: educore-testing
description: EduCore testing strategy - unit/feature/API/authorization/database/frontend/integration/E2E tests, plus required coverage for core business flows. Consult before writing or changing tests.
---

# EduCore Testing

Testing protects EduCore's core academic workflows (enrollment, attendance,
grades, GPA, documents, payments) from regressions.

## When to use

- Writing new tests, changing existing tests, or verifying a change did not
  break behavior.

## Test types

| Type | Scope | Tooling |
| --- | --- | --- |
| Unit | Services, domain logic in isolation | PHPUnit |
| Feature / API | Endpoints (request → response) | PHPUnit |
| Authorization | Policy allow/deny per role | PHPUnit |
| Database | Migrations, constraints, relationships | PHPUnit + RefreshDatabase |
| Frontend | Component/composable/store logic | Vue Test Utils (Vitest) if added |
| Integration | Multi-module flows (login → enroll → grade) | PHPUnit |
| E2E | Full browser flows | Playwright/Cypress (optional, where valuable) |

- Backend tests live in `backend/tests` (PSR-4 `Tests\`); run with
  `php artisan test` (`make test`).
- Frontend tests, if introduced, run via an `npm` script in the frontend
  container; keep them focused on logic, not snapshot noise.

## What must be tested (business-critical)

At minimum, cover these flows with tests:

1. **Login** — success, failure, rate limit.
2. **RBAC** — each role allowed/denied the right actions.
3. **Enrollment** — prerequisites enforced, capacity, duplicates rejected,
   atomicity.
4. **Attendance** — recording + percentage calculation.
5. **Grades** — grade submission, letter/point mapping, invalid input.
6. **GPA** — calculation correctness (weighted by credits) + recompute on
   change.
7. **Documents** — request → approval → generation → download authorization.
8. **Payments** — invoice/payment records, status transitions, totals.
9. **Notifications** — queued email/Telegram dispatched with correct payload.

## Conventions

- Use `RefreshDatabase` for feature tests touching the DB.
- Use factories/seeders to build realistic academic fixtures
  (`skills/database/SKILL.md`).
- Assert both happy-path **and** error-path (403/404/422) responses with the
  standard JSON shapes from `skills/api/SKILL.md`.
- Authorization tests must assert `403` for the wrong role, not just `200` for
  the right one.
- Keep tests deterministic; no reliance on wall-clock "now" or random data
  beyond factories.

## What NOT to test

- Framework internals.
- Trivial getters/setters.
- Pure snapshots of large HTML.

## Running tests

```sh
docker compose exec backend php artisan test    # or: make test
docker compose exec frontend npm run test       # if frontend tests added
```

- Run tests after every change (see Agent Behavior #11).
- Pint (`vendor/bin/pint`) must also pass (CI enforces lint + tests).

## Prohibitions

- DO NOT write tests that assert nothing meaningful.
- DO NOT mock away the very DB/policy behavior you are trying to verify for
  integration flows.
- DO NOT leave a failing/disabled test "for later".

## Validation checklist

1. New/changed behavior has a covering test (happy + error path).
2. `php artisan test` passes locally in Docker.
3. `vendor/bin/pint` passes.
4. Authorization denials asserted explicitly.
5. Related skills respected: `laravel`, `api`, `authorization`.

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