---
name: educore-admin-navigation
description: EduCore dashboard sidebar and breadcrumb navigation - which items each role sees, how items are grouped, when to add or remove one, and how planned modules are shown. Consult before adding, moving, or hiding any sidebar item or breadcrumb label.
---

# EduCore Admin Navigation (Sidebar)

The sidebar is the primary map of the platform. It must stay short, role-aware,
and truthful: it shows only what exists and what the signed-in role may open.

## When to use

- Adding, renaming, moving, or removing a sidebar item or group.
- Shipping a module (its sidebar item ships in the same change).
- Changing breadcrumb labels or the page title derived from the URL.

## Source of truth

| Concern | File |
| --- | --- |
| Items per role, breadcrumbs, page title | `frontend/src/composables/useNavigation.js` |
| Rendering, collapse, tooltips, drawer | `frontend/src/components/layout/AppSidebar.vue` |
| Shell, theme, mobile drawer | `frontend/src/layouts/DefaultLayout.vue` |
| Who may open a route | `backend/routes/web.php` (`role:` middleware) and the Policies |

`useNavigation.js` only decides what is **shown**. The route middleware and
policies decide what is **allowed**. Never rely on hiding for security
(`skills/authorization/SKILL.md`).

## Rules

1. **Mirror the routes.** An item is visible to a role only if that role passes
   the route's `role:` middleware. When middleware changes, update navigation in
   the same commit.
2. **Only real destinations.** Every enabled item links to an existing page.
   No placeholder links, no `#anchor` hrefs for live items.
3. **Planned modules** may appear as disabled "Soon" items **only for Super
   Admin**, at most 3 at a time, so the roadmap is visible without cluttering
   other roles. Other roles never see planned items.
4. **Group size.** A group has at most 7 items; split or promote to a new group
   before exceeding it. Group labels are nouns, sentence case.
5. **Item labels** use the canonical domain terms from
   `skills/academic-domain/SKILL.md` (Faculties & departments, Programs,
   Courses, Students, Lecturers). No synonyms.
6. **Active state:** `bg-primary/5` + primary text + the 2px left indicator;
   `aria-current="page"`. Do not invent other active styles.
7. **Icons:** Lucide only, 20px, one icon per item, no duplicates within a
   sidebar.
8. **Badges/counts** (e.g. pending requests) only from real data supplied by
   the backend. No fake or hard-coded numbers; no notification dot without a
   notifications module (`skills/notifications/SKILL.md`).
9. **Breadcrumbs** are `Dashboard → Section → (New | Edit | Details)`. Add the
   section label to `SECTION_LABELS` when adding a top-level route.
10. **Collapsed mode** shows icon + tooltip; mobile uses the drawer. Do not add
    hover-only information.
11. A new sidebar item is part of the module's deliverables
    (`AGENTS.md` documentation rule): update the module report and the
    `docs/9_Dashboard-UI-Refinement-Report.md` navigation table if the grouping
    changes.

## Target navigation by role

`[Implemented]` = shown now. `[Planned]` = added in the same change as its
module (Super Admin sees at most 3 as "Soon").

### Super Admin

| Group | Items |
| --- | --- |
| Overview | Dashboard `[Implemented]` |
| Academic structure | University `[Implemented]` · Faculties & departments `[Implemented]` · Programs `[Implemented]` · Academic years `[Implemented]` |
| People | Users & roles `[Implemented]` · Students `[Implemented]` · Lecturers `[Implemented]` |
| Academics | Courses `[Implemented]` · Offerings & sections `[Implemented]` · Rooms `[Implemented]` · My timetable (student/lecturer) `[Implemented]` · Attendance (lecturer) / My attendance (student) `[Implemented]` · My assignments (student; lecturers reach coursework from their section cards) `[Implemented]` · Enrollments `[Implemented]` |
| System | Error logs `[Implemented]` · Audit logs `[Future: 9.24]` · Settings & security `[Future]` |

### University Admin

Dashboard · Academic structure (University, Faculties & departments, Programs,
Academic years) · People (Students, Lecturers) · Academics (Courses, Sections,
Enrollment) · Operations (Documents, Invoices, Announcements) — each item only
once its module is `[Implemented]`.

### Faculty Admin

Dashboard · Academic structure (read-only: University, Faculties & departments,
Programs). Academic items follow once unit scoping exists on the user record.

### Lecturer

My courses · Timetable · Attendance · Assignments · Exams & grades ·
Announcements — each arrives with its module; until then only Dashboard.

### Student

Dashboard · My courses · Timetable · Attendance · Grades & GPA · Documents ·
Invoices · Announcements — each arrives with its module; until then only
Dashboard.

## Prohibitions

- DO NOT show an item a role cannot open (even disabled), except the Super
  Admin "Soon" items.
- DO NOT add an item for a module that has no page yet.
- DO NOT change RBAC or route middleware from the navigation file.
- DO NOT nest more than one level; use groups, not sub-menus.
- DO NOT use colors, icons, or states outside the design system
  (`skills/branding/SKILL.md`).

## Validation checklist

1. Each role's list matches the route middleware for its destinations.
2. No enabled item points at a missing page; no duplicate hrefs or icons.
3. Group sizes ≤ 7; "Soon" items ≤ 3 and Super Admin only.
4. Breadcrumb/section label added for any new top-level route.
5. Collapsed tooltips, mobile drawer, and keyboard focus still work.
6. Docs updated in the same commit.

## Related skills

`skills/branding/SKILL.md`, `skills/frontend-ui/SKILL.md`,
`skills/vue/SKILL.md`, `skills/authorization/SKILL.md`,
`skills/academic-domain/SKILL.md`.

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
