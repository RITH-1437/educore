---
name: educore-git-commit-style
description: EduCore commit message style - the exact '[Tag]: description.' format, approved tags, rules, and examples. Consult before writing any commit message.
---

# EduCore — Git Commit Style

Every EduCore commit message follows the format:

```
[Tag]: description.
```

- Tag = a short, capitalized action category from the approved list below.
- Colon + space separates tag from the description.
- Description is concise, lowercase start, and ends with a period.

## Approved tags

| Tag | Use for |
| --- | --- |
| `[Build]` | Build tooling, Docker, CI/CD, infrastructure, dependency setup |
| `[Doc]` | Documentation changes (README, `docs/`, comments) |
| `[Feature]` | New in-scope functionality |
| `[Fix]` | Bug fixes |
| `[Refactor]` | Code restructuring without behavior change |
| `[Test]` | Test additions/changes |
| `[Style]` | Formatting, code style (e.g. Pint) |
| `[Chore]` | Maintenance tasks that are none of the above |

If a change spans multiple categories, pick the **dominant** one — do not stack
tags.

## Examples (real commits from this repo)

- `[Build]: rename CI workflow to CI CD`
- `[Build]: read Telegram credentials from environment variables`
- `[Doc]: project report.`

## Rules

- One tag only, at the start of the message.
- Description: imperative, concise, no trailing whitespace.
- No jargon just for the sake of it — describe what the commit actually does.
- A commit may have a body (blank line + bullet points) when the change is
  large, but the first line must always be `[Tag]: description.`
- Stage only intended files; never commit `.env` or secrets.

## What is required before every commit

1. Run `git status` and review the change set.
2. Confirm no `.env` or credential files are staged.
3. Choose the correct tag from the table.
4. Write `[Tag]: short description.`
5. Commit; push only when asked or when the workflow requires it.

## Validation checklist

1. First line matches `[Tag]: description.` exactly.
2. Tag is from the approved table.
3. Description is accurate and concise.
4. No secrets staged.
5. If references exist in `skills/git-workflow/SKILL.md`, keep them consistent.

## Related skills

- `skills/git-workflow/SKILL.md` — branches, PRs, CI/CD, merge strategy.
- `skills/documentation/SKILL.md` — docs naming and conventions.

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