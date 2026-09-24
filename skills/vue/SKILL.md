---
name: educore-vue
description: Vue 3 + TypeScript conventions for EduCore - Composition API, Pinia, Vue Router, Axios services, components, composables, loading/error states, forms. Consult for any frontend implementation.
---

# EduCore Vue Conventions

Frontend: **Vue 3 + TypeScript + Pinia + Vue Router + Axios + Tailwind CSS 4 + Chart.js**,
running in the frontend Docker container (Node 22, Vite dev server). Naming for
build: `vue-tsc -b && vite build` (strict TypeScript).

## When to use

- Writing or changing any frontend code: pages, components, composables,
  stores, services, router.

## Before coding

- Inspect existing frontend code (`frontend/src`) and match its patterns.
- Do not rewrite working UI unnecessarily.
- Keep the Vue 3 `<script setup>` SFC style already used in the project.

## Structure

```
frontend/src/
├── pages/        # route-level views (HomePage.vue, ...)
├── layouts/      # DefaultLayout.vue, auth layout, admin layout
├── components/   # reusable UI components
├── composables/  # shared logic (useAuth, useTable, useForm)
├── stores/       # Pinia stores
├── services/     # Axios API modules (api.ts entry point)
├── router/       # routes + guards
└── types/        # TS types mirroring API resources
```

## API access

- All HTTP goes through `services/api.ts` (the shared Axios instance) + one
  service module per API domain.
- `api.ts` already: base URL from `VITE_API_URL` (default `/api`), attaches
  `Authorization: Bearer <auth_token>` from localStorage, and clears the token
  on 401. DO NOT duplicate this logic.
- Pages MUST NOT call `axios` directly — always go through a service module.
- Type the response data with TS interfaces from `src/types` (mirror API
  resources).

## Components

- **Reusable** components for anything repeated: buttons, inputs, tables,
  modals, cards, dropdowns, badges, empty states.
- Avoid giant components: break pages into focused components; split at 200-300
  lines.
- Prefer composition over heredity; props down, events up; define `emits`
  explicitly in TypeScript.
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

## Router

- Route definitions in `src/router/index.ts`.
- Route guards: `meta: { requiresAuth: true, roles: [...] }`; block
  unauthorized navigation client-side (defense in depth — backend still
  enforces everything).
- 404 fallback route.

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

## TypeScript

- Strict mode enforced by `vue-tsc` build.
- Type all props, emits, store state, API payloads, and API responses.
- No `any` unless truly unavoidable and documented.
- Use `generics`/`defineModel` where idiomatic.

## Frontend prohibitions

- DO NOT query the DB/Redis/MinIO directly from the frontend.
- DO NOT call Laravel internal code or env().
- DO NOT scatter raw `axios` calls in components — use services.
- DO NOT build side features outside EduCore scope (no AI chat, no payments
  gateway, etc.).
- DO NOT store sensitive data (passwords) in localStorage; token only.

## Validation checklist

1. `npm run build` (vue-tsc) passes on `frontend`.
2. API calls go through service modules; typed responses.
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