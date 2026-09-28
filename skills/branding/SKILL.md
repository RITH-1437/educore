---
name: educore-branding
description: EduCore brand and design system - colors, typography, spacing, radii, shadows, status badges, component rules, accessibility. Consult BEFORE creating or modifying any UI, page, component, or layout. Mandatory for all frontend work.
---

# EduCore Branding & Design System

EduCore's visual identity, design tokens, and component rules are defined in
`docs/branding/`. That folder is the **single source of truth**. This skill
routes to it, states the rules that are most often broken, and does not
duplicate the documents.

**Tagline:** "One Platform. Smarter Education."

## When to use

- MANDATORY before creating or modifying any UI: pages, components, layouts,
  forms, tables, badges, status colors, dashboards, landing sections.
- Mandatory when choosing a color, font size, spacing value, radius, or shadow.
- Mandatory when reviewing UI work for consistency.

## Source of truth (read the relevant one)

| Document | Read it for |
| --- | --- |
| `docs/branding/README.md` | Entry point, quick-reference token table, mandatory agent rule |
| `docs/branding/BRAND-GUIDELINES.md` | Identity, personality, principles, color hierarchy/proportion, dark mode, typography, iconography, accessibility, non-goals |
| `docs/branding/DESIGN-TOKENS.md` | Exact values: colors, spacing scale, padding, layout, breakpoints, radii, borders, shadows, state scale, Tailwind `@theme` mapping |
| `docs/branding/UI-COMPONENTS.md` | Button/form/table/card/modal/status/navigation/dashboard/chart rules, UX states, anti-patterns |

Never invent a value that already exists in these files. If a genuinely new
pattern is required, **update the design system first**, then use it.

## Core tokens (most used)

| Token | Value | Tailwind utility today |
| --- | --- | --- |
| Primary (Academic Blue) | `#2563EB` | `blue-600` (hover `blue-700`, focus ring `blue-600`) |
| Navy | `#0F172A` | `slate-900` |
| Sky | `#38BDF8` | `sky-400` |
| Teal | `#14B8A6` | `teal-500` |
| Background | `#F8FAFC` | `slate-50` |
| Surface | `#FFFFFF` | `white` / `slate-900` in dark |
| Text | `#1E293B` | `slate-800` |
| Muted | `#64748B` | `slate-500` |
| Success | `#16A34A` | `emerald-600` |
| Warning | `#F59E0B` | `amber-500` |
| Error | `#DC2626` | `red-600` |
| Info | `#2563EB` | `blue-600` |

### Important implementation note

`frontend/src/style.css` currently contains only `@import "tailwindcss";` — the
`@theme` mapping in `DESIGN-TOKENS.md` §15 is **not implemented yet**. Until it
is:

- Use the Tailwind stock utilities in the table above; they are the current
  de-facto implementation of the tokens (see `BaseButton`/`BaseInput`).
- Do **not** hardcode hex (`bg-[#2563EB]`) and do **not** invent substitutes from
  other palettes (e.g. `indigo` for primary, `gray` for neutrals). Mixing
  `indigo`/`gray` into a `blue`/`slate` design is the most common drift.
- Implementing `@theme` is a separate, approved task — not a side effect of a
  feature.

## Typography

- Inter for all UI text; Plus Jakarta Sans (`font-display` utility, already in
  `style.css`) for landing/hero display only.
- Fallback: `ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif`.
- Scale (`DESIGN-TOKENS.md` §4): h1 36/44 700 · h2 30/38 700 · h3 24/32 600 ·
  h4 20/28 600 · body 16/24 400 · small 14/20 · caption 12/16 · label/button
  14/20 500 · code monospace 13/18.
- Page title = h1, section title = h2, block/card title = h3/h4. Do not invent
  sizes between these.

## Spacing, radius, shadows

- Spacing: **4px base scale only** — 4, 8, 12, 16, 20, 24, 32, 40, 48, 64, 80,
  96, 128px. No arbitrary values (`p-[13px]`, `mt-[7px]`).
  Card padding default 20px · section gap 32px · page gap 48/64px.
- Radii: inputs 8px · cards 12px · buttons 8px · pills fully rounded.
- Shadows: subtle, low count — one card shadow and one raised state; no stacks
  of shadows or decorative gradients.
- Content max width 1280px (`max-w-7xl`).
- Breakpoints: Tailwind defaults (`sm` 640 · `md` 768 · `lg` 1024 · `xl` 1280).

## Color hierarchy and status colors

- Statuses map to **semantic** colors; never invent a per-status color.
  Active/Approved/Published/Completed → success · Pending → warning ·
  Rejected/Failed → error · Inactive/Draft/Cancelled → muted.
- Status badge = pill, ~10% tinted background, colored text, 11–12px,
  optional icon. **Always pair color with text or an icon — never color alone.**
- Action semantics: info = informational · warning = attention ·
  success = completion · error = failure/destructive.
- Keep the documented color proportion (mostly neutral surface, primary as
  accent); do not flood a page with brand color.

## Icons

- Lucide only. Do not mix icon libraries. Size and stroke-width from
  `DESIGN-TOKENS.md` §13.

## Rules that are most often broken

1. Reuse `Base*` primitives; never restyle a component per page.
2. Reuse existing tokens; never hardcode hex or arbitrary values.
3. Respect the 4px spacing scale and the documented type scale.
4. Every data view has loading, empty, error, and success states.
5. Keep actions in a rightmost table column; confirm destructive actions.
6. Hide actions the user cannot perform (backend still enforces).
7. Accessibility: visible focus rings, labels tied to controls, semantic
   elements, AA contrast, `prefers-reduced-motion` respected.
8. Dark mode and responsive behavior are not optional.
9. Split components at roughly 200–300 lines.
10. Success/error feedback uses one consistent pattern across the app.

## Prohibitions

- DO NOT hardcode hex colors in Vue components (`bg-[#...]`).
- DO NOT introduce colors outside the palette, or substitutes from other
  Tailwind palettes (`indigo`, `purple`, `teal` as an accent) without updating
  the design system first.
- DO NOT use spacing/radius/shadow values outside the documented scales.
- DO NOT style every page differently; new pages inherit the module template.
- DO NOT add a CSS framework, component library, or icon set outside the
  approved stack (Tailwind 4, `Base*` primitives, Lucide).
- DO NOT build giant components or duplicate an existing one.
- DO NOT ignore dark mode, responsive behavior, or accessibility.
- DO NOT use rainbow or multi-colored charts.

## Related skills

- `skills/frontend-ui/SKILL.md` — UI implementation conventions and component
  inventory. `skills/vue/SKILL.md` — Vue 3 + Inertia structure.
  `skills/authorization/SKILL.md` — permission-aware UI.

## Validation checklist

1. Every color, space, radius, font size, and shadow traces to a documented
   token; no hex, no arbitrary values.
2. No off-palette palette substitutions.
3. Existing `Base*`/shared components reused; no duplicated component.
4. Loading, empty, error, and success states present on new data views.
5. Status colors semantic and paired with text/icon.
6. Type scale and 4px spacing scale respected.
7. Responsive, dark-mode-ready, and accessible (focus, labels, contrast).
8. Any genuinely new pattern is documented in `docs/branding/` first.

## Agent behavior (mandatory everywhere)

1. Read `docs/branding/README.md` and the relevant document before writing UI.
2. Inspect the existing implementation before modifying it.
3. Follow existing project conventions already established.
4. Do not rewrite working code unnecessarily.
5. Do not introduce technologies outside the EduCore stack.
6. Do not create unnecessary abstractions.
7. Do not create duplicate business logic.
8. Do not invent database relationships.
9. Do not bypass authorization.
10. Do not hardcode secrets.
11. Do not modify unrelated modules.
12. Run appropriate tests and the frontend build after changes.
13. Explain important architectural decisions.
