---
name: educore-git-workflow
description: EduCore git and GitHub workflow - branch naming, commit conventions, PRs, code review, GitHub Actions (CI CD + Telegram), merge strategy. Consult for any git/CI work.
---

# EduCore — Git & GitHub Workflow

EduCore is version-controlled on GitHub. Two developers (Rin + Lyhor) work off
the same repository — a disciplined workflow prevents conflicts.

## When to use

- Creating branches, committing, opening PRs, or changing workflows under
  `.github/workflows/`.

## Branches

- `main` — stable, always deployable.
- `develop` — integration branch for feature work (recommended; if it does not
  exist yet and the team works directly off `main`, follow the established
  convention until `main`+`develop` is introduced deliberately).
- Feature/fix branches off the integration branch:
  - `feature/<module>-<short>`
  - `fix/<module>-<short>`
  - `refactor/<module>-<short>`
  - `docs/<short>`
  - `chore/<short>`

## Commits

- Commit messages MUST follow the format `[Tag]: description.` defined in
  `skills/git-commit-style/SKILL.md` (approved tags: `[Build]`, `[Doc]`,
  `[Feature]`, `[Fix]`, `[Refactor]`, `[Test]`, `[Style]`, `[Chore]`).
- Read `skills/git-commit-style/SKILL.md` before writing any commit message.
- One logical change per commit; stage only intended files.
- NEVER commit: `.env`, passwords, tokens, API keys, credentials. `.env` is
  git-ignored — verify with `git status` before commit.

## Pull requests

- Short-lived branches → open a PR into the integration branch.
- Title summarizes the change; description explains what/why (reference
  related skills where relevant).
- Self-review the diff before requesting review.
- CI must be green on the PR (GitHub Actions run on push/PR).

## Code review

- Both developers review each other's work (not strictly siloed per area).
- Check for: architecture conformity (`skills/architecture`), authorization
  enforced server-side (`skills/authorization`), no secrets, no unnecessary
  abstractions, tests updated (`skills/testing`).
- Address review comments before merge.

## GitHub Actions (CI/CD)

- `.github/workflows/ci.yml` "CI CD":
  - Runs on push + pull_request + workflow_dispatch.
  - `backend`: PHP 8.4 + PostgreSQL 16 service → `composer install`,
    `key:generate`, `vendor/bin/pint --test`, `php artisan test`.
  - `frontend`: Node 22 → `npm ci`, `npm run build` (`vue-tsc` typecheck +
    Vite build).
  - Nothing runs `migrate` toward your local Docker stack — it tests against
    the ephemeral GitHub Postgres service.
- `.github/workflows/telegram-notification.yml`:
  - On push (and manual), sends a Telegram message to the team.
  - Reads `TELEGRAM_BOT_TOKEN` / `TELEGRAM_CHAT_ID` from GitHub
    **Environment variables** (`vars.`), not secrets—do not hardcode them.
  - Missing-variable failure is by design (fails loudly with setup hint).
- When changing workflows: keep secrets/vars via GitHub settings, never inline.

## Merge strategy

- Squash or regular merge per team preference — prefer keeping a clean history
  with one logical commit per PR.
- Delete the feature branch after merge.
- Keep `main` deployable at all times (CI green before merge).

## Prohibitions

- DO NOT commit secrets or `.env`.
- DO NOT push directly to `main`/`develop` without PR + review (unless the
  team's existing convention explicitly allows direct pushes for trivial docs).
- DO NOT bypass CI (`[skip ci]` only for pure-doc/not-yet-configured cases and
  never to hide failures).
- DO NOT leave half-done work on `main`.

## Validation checklist

1. Branch named per convention.
2. Commit message meaningful and consistent with `git log` style.
3. No secrets in the diff; `.env` unchanged/ignored.
4. CI green (backend lint+tests, frontend build).
5. PR reviewed before merge; related skills respected.

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