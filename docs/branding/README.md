# EduCore Brand & UI Design System

**Single source of truth** for EduCore's visual identity, design tokens, and UI
component rules.

> **Tagline:** "One Platform. Smarter Education."

## Quick Reference

| Token | Value |
|---|---|
| Primary (Academic Blue) | `#2563EB` |
| Navy (Deep Navy) | `#0F172A` |
| Sky (Sky Blue) | `#38BDF8` |
| Teal | `#14B8A6` |
| Background | `#F8FAFC` |
| Surface | `#FFFFFF` |
| Text | `#1E293B` |
| Muted | `#64748B` |
| Success | `#16A34A` |
| Warning | `#F59E0B` |
| Error | `#DC2626` |
| Info | `#2563EB` |
| Font | Inter (display: Plus Jakarta Sans) |
| Base spacing | 4px |
| Card radius | 12px |
| Input radius | 8px |
| Max content width | 1280px |
| Dark bg | `#0B1120` |
| Dark surface | `#111827` |
| Dark text | `#F8FAFC` |
| Dark primary | `#60A5FA` |

## Documents

| Document | Contents |
|---|---|
| [`BRAND-GUIDELINES.md`](./BRAND-GUIDELINES.md) | Brand identity: name, purpose, vision, personality, principles, visual direction, logo, color, typography, iconography, imagery, accessibility, anti-goals |
| [`DESIGN-TOKENS.md`](./DESIGN-TOKENS.md) | Exact reusable values: colors, dark mode, typography, spacing, padding/margins, layout, breakpoints, radii, borders, shadows, icons, Tailwind mapping |
| [`UI-COMPONENTS.md`](./UI-COMPONENTS.md) | Component rules: buttons, forms, tables, cards, modals, statuses, navigation, dashboards, charts, states, animation, anti-patterns |

## Related Skills

- `skills/frontend-ui/SKILL.md` — UI implementation conventions.
- `skills/vue/SKILL.md` — Vue 3 + Inertia (JavaScript) structure and rules.
- `skills/authorization/SKILL.md` — permission-aware UI.

## AI Agent Usage Rule (mandatory)

> **Before creating or modifying UI, the coding agent MUST consult this
> branding/design system.**

The agent must:

1. **Reuse existing tokens** — never hardcode hex values or arbitrary values.
2. **Reuse existing components** — use the `Base*` primitives; do not restyle per page.
3. **Follow spacing rules** — the 4px base scale only.
4. **Follow typography rules** — the documented type scale.
5. **Follow color rules** — the documented palette and hierarchy.
6. **Follow responsive rules** — breakpoints and mobile behavior.
7. **Follow accessibility rules** — contrast, focus, labels, semantics.
8. **Avoid introducing new visual patterns without justification.**

If a **new design pattern is necessary**, update the design system (this README,
`BRAND-GUIDELINES.md`, `DESIGN-TOKENS.md`, or `UI-COMPONENTS.md`) instead of
silently creating an inconsistent pattern.

## Project Notes / Inventory (as of this design system)

- **Frontend:** Vue 3 + Inertia (JavaScript) + Vite, Tailwind CSS 4
  (`@tailwindcss/vite`), Pinia, Vue Router, Axios, Chart.js.
- **Current styling:** `frontend/src/style.css` currently contains only
  `@import "tailwindcss";` — no custom tokens defined yet. The Tailwind `@theme`
  mapping in `DESIGN-TOKENS.md` §15 is the intended implementation.
- **Existing layout scaffold:** `frontend/src/layouts/DefaultLayout.vue` uses
  `bg-gray-50` + `max-w-7xl` + gray text tokens (Tailwind defaults). These map to
  this system (`background`, `1280px`, `text`/`muted`) and should be migrated to
  semantic tokens when the theme is implemented.
- **Brand asset:** `frontend/public/assets/logo/edu-core.jpg`.
  `frontend/public/favicon.svg` is still a generic scaffold asset (to be replaced
  with the final brand mark when approved).
- Existing naming/asset conventions from `skills/documentation/SKILL.md` apply to
  these documents (single root README, `docs/` numbering).

## Validation Checklist

1. All four documents exist and reference each other.
2. Values are identical across documents (no contradictions).
3. Each color has one consistent meaning.
4. Spacing/typography/radius/shadow values match `DESIGN-TOKENS.md`.
5. Tailwind-compatible token naming (`@theme`).
6. Accessibility + dark mode documented.
7. No random tokens introduced.
8. AI agent usage rule is stated here (required for agents).

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