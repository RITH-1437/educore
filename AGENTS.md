# Agent Instructions — EduCore

## Mandatory rule: read skills first

Before processing any task, feature, fix, question, or documentation change in
this repository, you MUST first read the relevant skill file(s) under
`skills/`:

1. Select the skill(s) that match the task (see `skills/*/SKILL.md`
   descriptions).
2. Read the full `SKILL.md` content before doing anything else.
3. Follow the conventions, rules, and validation checklist in the skill.
4. If the task spans multiple areas, read all relevant skills before acting.

The `skills/` directory holds one project-specific `SKILL.md` per domain
(architecture, laravel, vue, database, api, authentication, authorization,
branding, docker, security, testing, frontend-ui, file-storage, notifications,
academic-domain, and each business module, plus git-workflow and
documentation).

`skills/branding/SKILL.md` routes to the brand/design system in
`docs/branding/`, which is the single source of truth for UI. Any UI change
must consult it.

## Mandatory rule: document every implementation

Whenever you implement or create anything, you MUST create or update the
matching documentation **in the same commit**. Code without documentation is an
incomplete change.

For each module or feature, ship these four deliverables:

1. A new numbered report `docs/N_<Module>-Report.md` covering scope, schema,
   models, endpoints, authorization matrix, UI, tests, and decisions, with
   Mermaid diagrams for anything structural or procedural.
2. `docs/6_Module-Status-and-Roadmap.md` — flip the module's label and close any
   open items it resolved.
3. `docs/3_business-overview.md` §9 — flip `[Planned]` to `[Implemented]` (or
   `[Future]`) truthfully.
4. `docs/api/api-audit.md` — add rows for new or changed endpoints.

Rules:

- Never mark `[Implemented]` without a report and a passing test suite.
- Update the root `README.md` when ports, env vars, commands, or routes change.
- Update `docs/database/` when the schema changes.
- Do not document features that do not exist, and do not commit secrets.

## Documentation convention

- `docs/` reports are sequentially numbered as `N_<name>.md`
  (e.g. `1_EduCore-Project-Report.md`, `2_EduCore-Docker-Setup-Report.md`,
  `3_business-overview.md`, `7_Faculty-and-Department-Report.md`).
- A single `README.md` lives at the repository root.
- Do not create duplicate README files inside subprojects.

## General rules

- Do not commit secrets or `.env`.
- Label functionality truthfully: `[Implemented]`, `[Planned]`, `[Future]`,
  `[Out of Scope]`.
- See `skills/documentation/SKILL.md` and `skills/git-workflow/SKILL.md` for
  details.