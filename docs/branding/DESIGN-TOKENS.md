# EduCore Design Tokens

**Source of truth for all reusable visual values.** Every color, spacing,
typography, radius, shadow, and breakpoint used in EduCore comes from this file.

- Companion documents: [`BRAND-GUIDELINES.md`](./BRAND-GUIDELINES.md) (identity)
  and [`UI-COMPONENTS.md`](./UI-COMPONENTS.md) (components).
- Implementation target: Tailwind CSS 4 (see §15).

> Do not introduce values that are not in this document. If a new value is truly
> necessary, add it here first — never silently invent raw values in a component.

---

## 1. Color System

### 1.1 Primary / Brand

| Token | Name | Hex | RGB | HSL | Purpose | Where to use | Where NOT to use |
|---|---|---|---|---|---|---|---|
| `primary` | Academic Blue | `#2563EB` | `rgb(37, 99, 235)` | `hsl(217, 83%, 53%)` | Brand primary and primary actions | Primary buttons, links, active nav, selected states, primary actions, info alerts | Large decorative backgrounds, text on light surfaces at small sizes |
| `primary-dark` | Deep Navy | `#0F172A` | `rgb(15, 23, 42)` | `hsl(217, 47%, 11%)` | High-authority dark color | Headings, top navigation, dark surfaces, important text | Body copy on light backgrounds |
| `secondary` | Sky Blue | `#38BDF8` | `rgb(56, 189, 248)` | `hsl(199, 93%, 60%)` | Secondary accent | Secondary accents, information, charts, highlights | Buttons and text where contrast must be strong (too light on white) |
| `accent` | Teal | `#14B8A6` | `rgb(20, 184, 166)` | `hsl(172, 80%, 40%)` | Supporting accent | Supporting accent, selected data visualization, secondary positive states | Primary actions, generic decoration |

### 1.2 Neutral

| Token | Name | Hex | RGB | HSL | Purpose | Where to use | Where NOT to use |
|---|---|---|---|---|---|---|---|
| `background` | Background | `#F8FAFC` | `rgb(248, 250, 252)` | `hsl(210, 40%, 98%)` | App background | Page shell, main canvas | Text color |
| `surface` | White | `#FFFFFF` | `rgb(255, 255, 255)` | `hsl(0, 0%, 100%)` | Card/surface color | Cards, panels, inputs on background | As page background behind cards (use `background`) |
| `text` | Text (primary) | `#1E293B` | `rgb(30, 41, 59)` | `hsl(217, 33%, 17%)` | Primary text | Body copy, headings on light surfaces | — |
| `muted` | Muted | `#64748B` | `rgb(100, 116, 139)` | `hsl(215, 16%, 47%)` | Secondary text | Captions, placeholders, secondary labels | Primary body copy at small sizes |

### 1.3 Semantic

| Token | Name | Hex | RGB | HSL | Purpose | Where to use | Where NOT to use |
|---|---|---|---|---|---|---|---|
| `success` | Success | `#16A34A` | `rgb(22, 163, 74)` | `hsl(142, 76%, 36%)` | Success | Successful operations, active/success status | Non-semantic decoration |
| `warning` | Warning | `#F59E0B` | `rgb(245, 158, 11)` | `hsl(38, 92%, 50%)` | Warning | Pending, attention required | Error messages |
| `error` | Error | `#DC2626` | `rgb(220, 38, 38)` | `hsl(0, 72%, 51%)` | Error | Validation errors, destructive states | Success states |
| `info` | Info | `#2563EB` | `rgb(37, 99, 235)` | `hsl(217, 83%, 53%)` | Info | Informational alerts, info icons | (same as primary) |

---

## 2. Color Hierarchy

```
Primary     Academic Blue  #2563EB
PrimaryDark Deep Navy      #0F172A
Secondary   Sky Blue       #38BDF8
Accent      Teal           #14B8A6
Neutral     Background / Surface / Text / Muted
Semantic    Success / Warning / Error / Info
```

**Color proportion (calm enterprise interface):**
`60% neutral/background · 30% surface/content · 10% brand/accent`

**Usage rules:**

- Do not use all brand colors in one component. One dominant + at most one accent.
- Semantic colors carry meaning — never use them as decoration.
- Prefer text/icons + color together; never color alone for meaning.

---

## 3. Dark Mode

Intentional dark palette — not inverted light mode.

| Token (dark) | Name | Hex | RGB | HSL |
|---|---|---|---|---|
| `dark-bg` | Dark background | `#0B1120` | `rgb(11, 17, 32)` | `hsl(222, 49%, 8%)` |
| `dark-surface` | Dark surface | `#111827` | `rgb(17, 24, 39)` | `hsl(221, 39%, 11%)` |
| `dark-surface-2` | Dark secondary surface | `#1E293B` | `rgb(30, 41, 59)` | `hsl(217, 33%, 17%)` |
| `dark-text` | Dark text (primary) | `#F8FAFC` | `rgb(248, 250, 252)` | `hsl(210, 40%, 98%)` |
| `dark-muted` | Dark muted text | `#94A3B8` | `rgb(148, 163, 184)` | `hsl(215, 20%, 65%)` |
| `dark-primary` | Dark primary (brightened) | `#60A5FA` | `rgb(96, 165, 250)` | `hsl(213, 94%, 68%)` |
| `dark-border` | Dark border | `#334155` | `rgb(51, 65, 85)` | `hsl(217, 25%, 27%)` |

**Dark-mode behavioral states:**

| State | Behavior |
|---|---|
| Background | `#0B1120` app shell |
| Surface | `#111827` cards, inputs, modals |
| Secondary surface | `#1E293B` hover surfaces, nested panels, sidebar |
| Border | `#334155` default |
| Text | `#F8FAFC` primary |
| Muted text | `#94A3B8` secondary |
| Hover | raise surface one step (`#111827` → `#1E293B`); do not darken text |
| Active | `#1E293B` surface + `dark-primary` accent |
| Focus | `dark-primary` (`#60A5FA`) focus ring |
| Disabled | opacity `.5` + `dark-muted` |
| Success/warning/error | keep hue, brighten for dark (`#4ADE80`/`#FBBF24`/`#F87171` are acceptable bright variants) |
| Primary button (dark) | `#60A5FA` background, `#0B1120` text |

Dark mode must be reachable through a `dark` class/variant — apply at the
application root, not per-component.

---

## 4. Typography

| Token | Font | Size | Line height | Weight | Letter spacing | Usage |
|---|---|---|---|---|---|---|
| `display` | Plus Jakarta Sans (opt) | 48px | 56px | 700 | `-0.025em` | Hero/landing display only |
| `h1` | Inter | 36px | 44px | 700 | `-0.02em` | Page titles |
| `h2` | Inter | 30px | 38px | 700 | `-0.015em` | Section titles |
| `h3` | Inter | 24px | 32px | 600 | `-0.01em` | Block titles |
| `h4` | Inter | 20px | 28px | 600 | `0` | Card titles |
| `body` | Inter | 16px | 24px | 400 | `0` | Default copy |
| `small` | Inter | 14px | 20px | 400 | `0` | Secondary text, table cells |
| `caption` | Inter | 12px | 16px | 400 | `0.01em` | Captions, footnotes |
| `label` | Inter | 14px | 20px | 500 | `0` | Form labels, nav labels |
| `button` | Inter | 14px | 20px | 500 | `0` | Button text |
| `table` | Inter | 14px | 20px | 400/600 | `0` | Table cells/headers |
| `code` | Monospace stack | 13px | 18px | 400 | `0` | Code, IDs, technical values |

Fallback stack: `ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif`.
Code stack: `ui-monospace, SFMono-Regular, Menlo, Consolas, monospace`.

---

## 5. Spacing Scale (base 4px)

This is the **only** spacing vocabulary. Never use arbitrary values like `7px`,
`13px`, `19px`, `27px`.

| Token | Value |
|---|---|
| `space-1` | 4px |
| `space-2` | 8px |
| `space-3` | 12px |
| `space-4` | 16px |
| `space-5` | 20px |
| `space-6` | 24px |
| `space-8` | 32px |
| `space-10` | 40px |
| `space-12` | 48px |
| `space-16` | 64px |
| `space-20` | 80px |
| `space-24` | 96px |
| `space-32` | 128px |

**When to use which:**

| Use case | Token |
|---|---|
| Tight inline gaps (icon–text) | `space-1` / `space-2` |
| Button–input groups, label–control gaps | `space-2` |
| Small element gaps (cards in a row) | `space-3` / `space-4` |
| Card padding (default) | `space-5` (20px) |
| Section spacing on a page | `space-8` (32px) |
| Page and large-section spacing | `space-12` / `space-16` (48/64px) |
| Empty states / large hero areas | `space-20` and up |

> Match Tailwind default: Tailwind `p-1`=4px … `p-6`=24px, `p-8`=32px, etc. Prefer
> `space-*` aliases or the native scale — never a number not in this set.

---

## 6. Padding Rules

### Buttons

| Size | Padding | Height (approx) |
|---|---|---|
| Small | `8px 12px` | 32px |
| Medium (default) | `10px 16px` | 40px |
| Large | `12px 20px` | 48px |

### Cards

| Type | Padding |
|---|---|
| Small | 16px |
| Default | 20px |
| Large | 24px |

### Modal

- Padding: **24px** on all sides.

### Page

| Viewport | Horizontal padding |
|---|---|
| Mobile | 16px |
| Tablet | 24px |
| Desktop | 32px |
| Large desktop | 40px |

Vertical rhythm uses the spacing scale (§5), primarily 32px between sections.

---

## 7. Margin / Gap Rules

Prefer **`gap`** in flex/grid layouts over scattering manual margins.

| Relationship | Gap |
|---|---|
| Heading → paragraph | 8px |
| Label → input | 6px (allowed exception near scale) — or `space-2` (8px) for consistency |
| Input → input (vertical stack) | 16px |
| Section → section | 32px |
| Card → card (grid) | 16px / 24px |
| Page sections | 48px / 64px |

**Rule:** if the value is not in the spacing scale, do not use it. (The 6px
label–input gap is a documented micro-exception for visual alignment.)

---

## 8. Layout System

### Page container

- Max content width: **1280px** (`max-w-[1280px]`).
- Data-heavy administration screens may exceed this when necessary, but the
  default page shell is centered at 1280px.
- Page horizontal padding: see §6 (mobile 16 → desktop 32/40px).

### Application frame

| Region | Behavior |
|---|---|
| Sidebar | Fixed-width navigation (collapsible), light/dark aware |
| Header | Top bar: breadcrumbs/context + user menu + notifications |
| Content area | Scrollable main region, `background` background |
| Dashboard grid | 4-col desktop, 2-col tablet, 1-col mobile |
| Form layout | Single column ≤ 640px; two columns only when fields pair |
| Table layout | Full-width on a card surface with pagination footer |
| Modal layout | Centered overlay, width per §Modals (UI-COMPONENTS) |

---

## 9. Breakpoints (Tailwind-compatible)

| Token | Min-width | Device / behavior |
|---|---|---|
| (base) | `< 640px` | Mobile: single column, hamburger nav |
| `sm` | 640px | Phone landscape / small tablet |
| `md` | 768px | Tablet: 2-col grids appear |
| `lg` | 1024px | Desktop: sidebar + multi-col |
| `xl` | 1280px | Desktop max content width |
| `2xl` | 1536px | Large desktop: allow wider data tables |

The application is desktop-first for administration, but **must remain usable on
mobile**: navigation collapses, tables scroll horizontally, forms stack.

---

## 10. Border Radius

| Token | Value |
|---|---|
| `radius-sm` | 6px |
| `radius-md` | 8px |
| `radius-lg` | 12px |
| `radius-xl` | 16px |
| `radius-2xl` | 20px |
| `radius-pill` | 9999px |

**Usage:**

| Element | Radius |
|---|---|
| Inputs, selects, textareas | 8px |
| Buttons | 8px |
| Cards | 12px |
| Large panels, modals | 16px |
| Badges, tags, pills, avatars | pill (9999px) |

Avoid excessive rounding; never mix radii arbitrarily within one component.

---

## 11. Borders

| Token | Value | Usage |
|---|---|---|
| `border-default` | `1px solid #E2E8F0` | Cards, inputs, dividers |
| `border-muted` | `1px solid #CBD5E1` | Stronger dividers, hover boundaries |
| `border-focus` | `2px solid #2563EB` | Focus rings and selected states |
| `border-dark` | `1px solid #334155` | Dark-mode default border |
| `border-dark-focus` | `2px solid #60A5FA` | Dark-mode focus ring |

Do not outline every element. Borders separate real groups; use spacing first,
borders second.

---

## 12. Shadows

Small, restrained scale:

| Token | Value | Usage |
|---|---|---|
| `shadow-sm` | `0 1px 2px rgb(15 23 42 / 0.06)` | Inputs, small cards |
| `shadow-md` | `0 4px 6px -1px rgb(15 23 42 / 0.10), 0 2px 4px -2px rgb(15 23 42 / 0.10)` | Dropdowns, popovers, modals |
| `shadow-lg` | `0 10px 15px -3px rgb(15 23 42 / 0.12), 0 4px 6px -4px rgb(15 23 42 / 0.10)` | Large overlays |

Use **shadows to lift surfaces, not to decorate.** Default cards rely on border +
light `shadow-sm`. Avoid excessive shadowing.

---

## 13. Iconography

| Property | Value |
|---|---|
| Family | **Lucide Icons** |
| Sizes | `16 / 18 / 20 / 24` px |
| Default size | `20px` (inline), `24px` (large/empty states) |
| Stroke width | `1.5` (default), `2` for emphasis at 16px |
| Color | inherit `currentColor` — never hardcode fill |
| Alignment | inline-flex, icon + text baseline-aligned |

---

## 14. State Scale

| State | Definition |
|---|---|
| Default | Resting style |
| Hover | `hover:` one-step emphasis (surface lighten / border darken) |
| Focus | `focus-visible:` 2px focus ring (`border-focus`) |
| Active | Pressed state (slightly darker surface) |
| Disabled | opacity `.5`, `not-allowed` cursor, no interaction styles |
| Loading | spinner + disabled interaction |
| Success | success color feedback |
| Error | error color + message |
| Empty | EmptyState component with message |

---

## 15. Tailwind Implementation

Because the project uses **Tailwind CSS 4** (via `@tailwindcss/vite`), map tokens
into the theme rather than hardcoding hex values:

```css
/* frontend/src/style.css — @theme mapping (conceptual) */
@theme {
  --color-primary: #2563EB;
  --color-primary-dark: #0F172A;
  --color-secondary: #38BDF8;
  --color-accent: #14B8A6;
  --color-background: #F8FAFC;
  --color-surface: #FFFFFF;
  --color-ink: #1E293B;
  --color-muted: #64748B;
  --color-success: #16A34A;
  --color-warning: #F59E0B;
  --color-error: #DC2626;
  --color-info: #2563EB;

  --color-dark-bg: #0B1120;
  --color-dark-surface: #111827;
  --color-dark-surface-2: #1E293B;
  --color-dark-ink: #F8FAFC;
  --color-dark-muted: #94A3B8;
  --color-dark-primary: #60A5FA;
  --color-dark-border: #334155;

  --font-sans: "Inter", ui-sans-serif, system-ui, sans-serif;
  --font-display: "Plus Jakarta Sans", var(--font-sans);

  --radius-sm: 6px;
  --radius-md: 8px;
  --radius-lg: 12px;
  --radius-xl: 16px;
  --radius-2xl: 20px;

  --shadow-sm: 0 1px 2px rgb(15 23 42 / 0.06);
  --shadow-md: 0 4px 6px -1px rgb(15 23 42 / 0.10), 0 2px 4px -2px rgb(15 23 42 / 0.10);
  --shadow-lg: 0 10px 15px -3px rgb(15 23 42 / 0.12), 0 4px 6px -4px rgb(15 23 42 / 0.10);
}
```

Then components use semantic classes (`bg-primary`, `text-muted`,
`border-default`, `status-success`) instead of raw values.

**Rule:** never write `bg-[#2563EB]` or similar arbitrary values in components —
always reference the token.

---

## Validation Checklist

1. Every value used in the app maps to a token in this file.
2. No hex/RGB outside this document appears in components.
3. Spacing only from the base-4px scale (§5).
4. Radii only from the radius scale (§10).
5. Typography only from the scale (§4).
6. Colors carry one consistent meaning (success is always success).
7. Dark mode uses the dark palette, not inverted light.
8. Tailwind `@theme` mirrors these tokens (when implemented).

---

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