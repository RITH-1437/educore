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
docker, security, testing, frontend-ui, file-storage, notifications,
academic-domain, and each business module, plus git-workflow and
documentation).

## Documentation convention

- `docs/` reports are sequentially numbered as `N_<name>.md`
  (e.g. `1_EduCore-Project-Report.md`, `2_EduCore-Docker-Setup-Report.md`,
  `3_business-overview.md`).
- A single `README.md` lives at the repository root.
- Do not create duplicate README files inside subprojects.

## General rules

- Do not commit secrets or `.env`.
- Label functionality truthfully: `[Implemented]`, `[Planned]`, `[Future]`,
  `[Out of Scope]`.
- See `skills/documentation/SKILL.md` and `skills/git-workflow/SKILL.md` for
  details.