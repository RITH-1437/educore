---
name: educore-frontend-ui
description: EduCore frontend UI - Tailwind CSS, responsive design, reusable components, typography, forms, tables, modals, alerts, empty/loading/error states, dashboard cards, charts. Consult for any UI implementation.
---

# EduCore Frontend UI

EduCore should look and feel like a **professional university administration
platform** — clean, consistent, information-dense where needed, and never
cluttered.

## When to use

- Building or changing any Vue UI: pages, components, layouts, forms, tables,
  charts.

## Stack

- Tailwind CSS 4 (via `@tailwindcss/vite`), Vue 3, Inertia.js, Chart.js.
- Avoid adding another CSS framework or component library unless explicitly
  approved.

## Design principles

- Consistency over cleverness: reuse components instead of restyling per page.
- Information with hierarchy: dashboards show summary cards above detail tables.
- Restraint: avoid unnecessary visual complexity, gradients, animations, or
  decorative features that add no value.
- Responsive: layouts degrade gracefully on tablets/mobiles (admin-heavy
  desktop-first, but must not break).

## Typography & spacing

- Use the Tailwind default font stack and spacing scale consistently.
- Consistent heading scale (page title, section title, card title).
- Consistent padding/gap rhythm (`p-4/p-6`, `gap-4`) across pages.
- Define reusable spacing in components, not arbitrary values per page.

## Reusable components

Keep in `frontend/src/components` and reuse everywhere:

| Component | Purpose |
| --- | --- |
| `BaseButton` / `BaseInput` / `BaseSelect` / `BaseTextarea` | Form primitives with label + error slot |
| `BaseTable` | Data tables with slots, sorting, pagination footer |
| `BaseModal` | Modal scaffold with `v-model` open state |
| `BaseCard` | Content card container |
| `BaseDropdown` / `BaseTooltip` / `BaseBadge` | Small interaction primitives |
| `EmptyState` / `LoadingSpinner` / `ErrorAlert` | Status visuals |
| `StatusBadge` | Status → color mapping (e.g. Paid/Pending) |
| `Pagination` | Page navigation |

- Add components when a pattern repeats; do not create a component for a single
  usage.
- Props down / events up; explicit `emits` + props in `<script setup>`.

## Forms

- Consistent field layout: label above input, error text below in red.
- Disable submit while pending; show inline field errors from API `422`
  responses (see `skills/api/SKILL.md`).
- Group related fields with standard section headers.

## Tables

- Standard table with header, row hover, and consistent column alignment.
- Keep actions (edit/delete) in a rightmost actions column.
- Empty state message when no rows.
- Pagination control consistent across all tables.

## Modals

- Use `BaseModal`; open/close controlled by caller via `v-model`.
- Confirm-destructive actions (delete) with a clear confirm modal.
- Trap focus / close on Escape ideally; keep behavior consistent everywhere.

## Alerts & feedback

- Inline alerts for validation/errors near the relevant form.
- Success feedback after mutations (toast or inline) — pick one pattern and
  reuse it.
- Distinguish info / success / warning / error consistently.

## Empty / loading / error states

- Every data view has 4 states: **loading** (spinner/skeleton), **success**,
  **empty** (meaningful message + optional CTA), **error** (message + retry).
- Never silently show a blank page or a raw `[object Object]`.

## Dashboard cards

- Summary cards: label + value + optional delta, consistent layout
  (`GPA`, `Attendance %`, `Credits`, `Total students`, ...).
- Charts via Chart.js, small and readable; label axes.

## Permission-aware UI

- Hide actions the user cannot perform (see `skills/authorization/SKILL.md`).
- Remember: hiding is UX, backend still enforces.

## Prohibitions

- DO NOT scatter ad hoc purple/teal accent colors — stick to the Tailwind
  palette consistently.
- DO NOT build one huge 800-line component; split into focused components
  (see `skills/vue/SKILL.md`).
- DO NOT add CSS libraries/frameworks outside the stack.
- DO NOT make forms submit before validation/accounting for pending state.

## Validation checklist

1. Reuses existing primitives where possible; no new library added.
2. Loading/empty/error states present on new data views.
3. Consistent spacing/typography/status colors.
4. Responsive — page usable on smaller screens.
5. Related skills respected: `vue`, `api`, `authorization`.

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