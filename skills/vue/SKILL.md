---
name: educore-vue
description: EduCore Vue conventions - Inertia.js + Vue 3 (JavaScript) + Pinia + Vue Router (none; Inertia handles routing) + Axios services, components, composables, loading/error states, forms. Consult for any frontend implementation.
---

# EduCore Vue Conventions

Frontend: **Vue 3 + JavaScript (no TypeScript) + Inertia.js + Pinia + Axios +
Tailwind CSS 4 + Chart.js**, running in the frontend Docker container (Node 22,
Vite dev server). Naming for build: `npm run build` → `vite build` (plain
JavaScript; no `vue-tsc`). Laravel renders Inertia page components from
`routes/web.php`; the JSON REST API (`/api`) is still used for data
mutations/queries via Axios.

## When to use

- Writing or changing any frontend code: pages, components, composables,
  stores, services.

## Before coding

- Inspect existing frontend code (`frontend/src`) and match its patterns.
- Do not rewrite working UI unnecessarily.
- Keep the Vue 3 `<script setup>` SFC style already used in the project.
- Pages are plain `.vue` with `<script setup>` (no `lang="ts"`).

## Structure

```
frontend/src/
├── pages/        # Inertia page components (HomePage.vue, ...) named to match
│                 # Inertia::render('Home') → pages/Home.vue
├── layouts/      # DefaultLayout.vue (persistent layout), auth/admin layouts
├── components/   # reusable UI components
├── composables/  # shared logic (useAuth, useTable, useForm)
├── stores/       # Pinia stores
├── services/     # Axios API modules (api.js entry point)
└── app.js        # Inertia bootstrap (createInertiaApp)
```

Routing is handled by Inertia on the server (`routes/web.php` +
`Inertia::render()`); there is **no client-side `vue-router`**.

## Page navigation (Inertia)

- Routes are defined in `backend/routes/web.php`; controllers (or inline
  closures) return `Inertia::render('PageName', $props)`.
- Page component path mirrors the name: `Inertia::render('Home')` →
  `frontend/src/pages/Home.vue`; nested with dots, e.g. `Admin.Users` →
  `pages/Admin/Users.vue`.
- Use `<Link href="...">` (from `@inertiajs/vue3`) for in-app navigation,
  never `<a>`.
- Server-side authorization is the source of truth; Inertia page props plus
  `routes/web.php` middleware (`auth`, role policies) guard pages.
- Shared props (e.g. `auth.user`, flash messages) come from
  `App\Http\Middleware\HandleInertiaRequests`.

## API access

- All HTTP for data goes through `services/api.js` (the shared Axios instance) +
  one service module per API domain. This is used for REST mutations/queries;
  page loads themselves are Inertia requests, not Axios.
- `api.js` already: base URL from `VITE_API_URL` (default `/api`), attaches
  `Authorization: Bearer <auth_token>` from localStorage, and clears the token
  on 401. DO NOT duplicate this logic.
- Pages MUST NOT call `axios` directly — always go through a service module.

## Components

- **Reusable** components for anything repeated: buttons, inputs, tables,
  modals, cards, dropdowns, badges, empty states.
- Avoid giant components: break pages into focused components; split at 200-300
  lines.
- Prefer composition over heredity; props down, events up; define `emits`
  explicitly.
- Modal pattern: a single reusable `<BaseModal>` with slots, controlled by a
  `v-model` open state from the caller.

## Composables

- Extract reusable logic into `composables/` (`useAuth`, `useDataTable`,
  `usePagination`, `useFormSubmit`).
- Encapsulate loading/error/data-lifetime concerns in composables so components
  stay presentational.

## Pinia stores

- Use stores for shared/global state: auth session, current user, permissions,
  global notifications, filters shared across pages.
- NOT for per-page local data that only one component needs (keep that in a
  composable/`ref`).
- Actions call services; state is read-only from components (use getters).

## Access control on pages

- Server-side: `routes/web.php` middleware (`auth`, role checks) is the
  enforcement. Inertia still renders the SPA shell; guards happen on the server.
- Page props shared via `HandleInertiaRequests` (e.g. `auth.user`, `roles`) let
  the UI hide what the user cannot do.

## Loading & error states

- Every async view must handle: loading, success, empty, error.
- Reusable `<LoadingSpinner>`, `<EmptyState>`, `<ErrorAlert>` components.
- On mutation: disable submit while pending; show success/error feedback
  (toast or inline alert). See `skills/frontend-ui/SKILL.md`.

## Form handling & validation

- Forms use `v-model` + composables; server-side validation errors from the API
  (Laravel `422`) are mapped back to inputs (services return the `errors`
  object).
- Show field-level errors; do client-side pre-checks for UX but NEVER rely on
  them as security.
- Reusable form field wrappers map label + v-model + error slot.

## Pagination / filtering / sorting

- Use a shared `useDataTable` composable that talks to the API query contract
  (`?page=&per_page=&search=&sort_by=&sort_dir=&filters[...]`) defined in
  `skills/api/SKILL.md`.
- Tables: `BaseTable` with slot columns, pagination footer, and empty state.

## JavaScript (no TypeScript)

- Plain JavaScript only — no `lang="ts"`, no `.ts` files, no `vue-tsc`.
- Build/type-enforcement happens server-side (Laravel validation, API
  contracts). Keep client code simple, small, readable.
- Prefer `defineModel`/`defineProps` from Vue where idiomatic.

## Frontend prohibitions

- DO NOT query the DB/Redis/MinIO directly from the frontend.
- DO NOT call Laravel internal code or env().
- DO NOT scatter raw `axios` calls in components — use services.
- DO NOT build side features outside EduCore scope (no AI chat, no payments
  gateway, etc.).
- DO NOT store sensitive data (passwords) in localStorage; token only.

## Validation checklist

1. `npm run build` (vite) passes on `frontend`; Laravel `@vite` resolves assets.
2. Pages render via `Inertia::render()` from `routes/web.php`.
3. REST calls go through service modules (`services/api.js`).
3. No giant components introduced.
4. Loading/error/empty states present for new views.
5. Related skills respected: `frontend-ui`, `api`, `authorization` (UI guards).

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