---
name: educore-documentation
description: EduCore documentation conventions - README, API docs, architecture docs, ERD, ADRs, setup/deployment/user docs. Consult when creating or updating project documentation.
---

# EduCore — Documentation

EduCore keeps documentation in a single root `README.md` plus numbered reports
under `docs/`. Documentation must stay in sync with architecture and major
behavior.

## When to use

- Creating or updating any project documentation: README, API docs, dev setup,
  architecture notes, ERD, ADRs, deployment notes.

## Documentation locations

| Document | Location | Update when |
| --- | --- | --- |
| Project/README (setup, ports, commands, layout) | `README.md` (root, single) | dev workflow/config changes |
| Progress/design reports | `docs/N_<name>.md` (numbered, e.g. `1_EduCore-Project-Report.md`, `2_...-...md`) | milestones reached, major decisions |
| API contract / endpoint reference | doc section or `docs/` report (follow existing convention) | endpoint shape changes |
| ERD / database model | `docs/` report or migration comments consistent with `skills/database` | schema changes |
| Architecture decisions (ADR) | `docs/` as a numbered report when a significant decision is made | a consequential decision |
| Setup / deployment | `README.md` section | container/config changes |

## Rules

- Keep **one root README** — do not scatter duplicate README files inside
  `backend/` or `frontend/` (removed in favor of the single root README).
- Numbered `docs/*.md` uses the `N_` prefix convention already established.
- Update README when ports, services, env vars, or commands change (e.g. the
  Docker section reflects the real port mapping and `make` targets).
- API docs must match the actual routes/response shapes described in
  `skills/api/SKILL.md` (consistent JSON, status codes, pagination).
- Architecture/ERD docs reflect `skills/academic-domain/SKILL.md` terminology.
- ADRs capture: context, decision, and consequences — concise, dated.
- Never document credentials or secrets — use placeholders referencing `.env`.

## What to document on major changes

1. New module/entity → update ERD/architecture docs + the relevant numbered
   report.
2. New/public endpoint → API reference.
3. Changed ports/env/compose → README Docker section + troubleshooting.
4. Significant architecture direction → ADR-style report.

## Conventions

- Markdown, tables for reference data (ports, env vars), fenced code for
  commands.
- Keep docs concise and actionable; avoid duplicating content between files.
- When you change architecture or major behavior, update the docs **in the same
  change** (don't leave docs stale).

## Prohibitions

- DO NOT commit secrets into docs (even in examples — use placeholders).
- DO NOT create a second README inside subprojects.
- DO NOT document features that don't exist.
- DO NOT let docs drift — update alongside the code.

## Validation checklist

1. README reflects current ports/services/commands.
2. `docs/` numbers are sequential (`1_`, `2_`, …).
3. Terminology matches `skills/academic-domain` and `skills/api`.
4. No secrets/credentials in any doc.
5. Related skills respected: `architecture`, `api`, `git-workflow`.

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