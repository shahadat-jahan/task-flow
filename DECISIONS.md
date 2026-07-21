# Decisions

## Design Decisions

### Monolith Laravel + Inertia + Vue (no separate API tier)
The app is a server-rendered SPA: every route returns an Inertia response and the
client is a single Vue 3 application. There is deliberately **no REST API layer**.
Rationale: one deployable unit, shared route/type definitions via Wayfinder
(`@/routes`, `@/actions`) so the client never hard-codes URLs, and no CORS to
manage. Vite dev server proxies to the PHP app (`composer run dev` runs both).

### API Resources used internally for data shaping
`TaskResource` / `UserResource` / `ProjectResource` / `TagResource` shape the
data sent to Inertia props — and a single task uses `(new TaskResource($task))
->resolve($request)` to produce a **flat** `task` object instead of the default
`data`-wrapped shape. This was chosen for consistent, testable serialization even
though there is no external API consumer.

### Schema / enum approach
`App\Enums\TaskStatus` (`todo`, `in_progress`, `in_review`, `done`, `cancelled`)
and `App\Enums\TaskPriority` (`low`, `medium`, `high`) are backed enums (DB
strings), with indexed columns on `status` / `priority` / `assignee_id` /
`project_id`. The `tag_task` pivot uses a composite primary key, and `created_by`
(task) / `uploaded_by` (attachment) foreign keys back the authorship and
ownership checks used by the policy/abort guards.

## Assumptions (where the brief / Figma was unclear)

- **OTP / Google auth** — Out of scope. Only Fortify register/login/logout are
  wired; email verification and two-factor auth are disabled. The "Continue with
  Google" button is a visual stub with no handler.
- **Dashboard vs Tasks/Index** — Merged into one shared page. `GET /dashboard`
  renders the same `Tasks/Index` component as `GET /tasks` (a deliberate decision,
  not a duplicate page). The `verified` gate was removed from `/dashboard` so the
  two identical routes behave the same.
- **Activity feed fidelity** — No activity feed was built. There is no
  activity-log package in the project; task **comments** are the visible activity
  record on the Task Details page.
- **Tag creation constraints** — Tags are created **inline only**, during task
  create/edit via `tags.store`, with a default gray `#6b7280` (the `StoreTagRequest`
  colour is required). No standalone tag-management CRUD (update/destroy omitted).
- **Trend delta computation** — Summary-card trends are computed by
  week-over-week **comparison queries** (current totals vs. records created before
  one week ago), not a time-series snapshot. Documented approximation: each task's
  *current* status is assumed to also apply a week ago. No migration was needed.
- **Inertia file upload** — Attachments use `useForm({ file })` with
  `forceFormData: true` and are stored on the `public` disk
  (`storeAs(..., 'public')`), so `php artisan storage:link` is required for the
  download links to resolve.

## Improvements

These were added to match the **full Figma design**, not as scope creep:

- **Projects** — a `projects` table + model, listed in the sidebar with live task
  counts. The Figma task list shows a Project filter and colour dot, so projects
  make that meaningful rather than decorative.
- **Tags** — colour-coded tags with inline creation while editing a task, matching
  the Figma tag chips (the PDF only hinted at tags via the filter).
- **Comments** — a per-task comment thread (add / delete your own) on the Task
  Details page, providing the visible activity record the Figma detail view shows.
- **Attachments** — per-task file upload / download / delete (own only) on the
  Task Details page, stored on the `public` disk; matches the Figma attachment
  area.

---

## Implementation history (per-prompt log)

## Auth scaffolding review

### Routes — confirmed present (Fortify default, wired through Inertia)
- `GET|HEAD  /register`  → `register`            (RegisteredUserController@create)
- `POST      /register`  → `register.store`      (RegisteredUserController@store)
- `GET|HEAD  /login`     → `login`               (AuthenticatedSessionController@create)
- `POST      /login`     → `login.store`         (AuthenticatedSessionController@store)
- `POST      /logout`    → `logout`              (AuthenticatedSessionController@destroy)

Underlying logic left as-is (Fortify action classes / Form Requests) per spec.

### Inertia auth page paths (for later Figma restyle)
- `resources/js/Pages/auth/Login.vue`
- `resources/js/Pages/auth/Register.vue`
- `resources/js/Pages/auth/ForgotPassword.vue`  (orphaned — reset-password feature disabled, see below)
- `resources/js/Pages/auth/ResetPassword.vue`   (orphaned — reset-password feature disabled, see below)

### Fortify features — verification & correction
`config/fortify.php` `features` array reviewed against the Prompt 1 decision
(email verification disabled; only register/login/logout in scope).

Removed (unwanted extra that had slipped in):
- `Features::resetPasswords()` — not part of the agreed scope. Disabling it
  gracefully skips the starter kit's `PasswordResetTest` (guarded by
  `skipUnlessFortifyHas(Features::resetPasswords())`), so the suite stays green.
  The `ForgotPassword.vue` / `ResetPassword.vue` pages are now unrouted.

Confirmed NOT enabled (absent from `features`, as required):
- `emailVerification()` — off per Prompt 1 (the `users.email_verified_at`
  column is intentionally left unused).
- `twoFactorAuthentication()` — off.
- `passkeys()` — off.

Note on `password.confirm` / `password.confirmation` routes: in this Fortify
version these routes are **always** registered and are NOT gated by a feature
flag (there is no `Features::passwordConfirmation()` method). They cannot be
disabled via config. This is default Fortify behavior, not an enabled feature.

## Projects & Tags backend

### Projects — global visibility, no per-user scoping
- `ProjectController` index/store/update/destroy are open to any authenticated
  user. The `project_policy` / ownership checks were intentionally **not** added
  because the design does not show per-user project scoping — all projects are
  visible/editable by any authenticated user. `store()` sets `owner_id` to the
  current user (required by the `projects.owner_id` column) but this is just a
  creation marker, not an authorization boundary.

### Tags — lightweight, no full CRUD
- Only `TagController::index` + `TagController::store` were built. `update` /
  `destroy` routes/controllers were **deliberately omitted** — full tag
  management UI is out of scope for now. Tag management happens inline during
  task create/edit (the `tags.store` endpoint backs inline tag creation there).

### Build/asset fix (side effect of disabling resetPasswords earlier)
Disabling `Features::resetPasswords()` regenerated `@/routes/password` without
the `email` / `update` / `request` exports, which broke `npm run build`:
- Removed the now-orphaned `resources/js/pages/auth/ForgotPassword.vue` and
  `ResetPassword.vue` (already flagged as orphaned above).
- Removed the dead "Forgot your password?" `TextLink` (and its `request()`
  import) from `Login.vue`; `canResetPassword` is always `false` now, so the
  link never rendered anyway.

### Placeholder pages added
Minimal `Projects/Index.vue` and `Tags/Index.vue` were added (single-root,
`AppLayout` default, breadcrumb) so the Inertia renders resolve in the Vite
manifest. They are placeholders — the real Figma UI is built in a later prompt.

## Tasks backend

### Summary service (shared by Tasks index and Dashboard)
- `App\Services\TaskSummaryService::summarize()` is the single source of the
  summary cards. It returns `total_tasks`, `by_status` and `by_priority`
  (both **zero-filled** for every `TaskStatus` / `TaskPriority` enum value) and
  `overdue_count` (tasks whose `due_date < today()` and whose status is NOT
  `done` / `cancelled`). Built from two grouped `count` queries plus one
  overdue `count` — no N-per-status queries.
- Both `TaskController@index` and `DashboardController@index` inject the service
  and expose the result as the `summary` prop.

### Dashboard == Tasks view (deliberate, not a duplicate page)
- The design screenshots show the Dashboard and Tasks/Index as near-identical
  layouts (same task table with the same summary cards above it). Rather than
  build a second page, `GET /dashboard` (`DashboardController@index`) **renders
  the shared `Tasks/Index` component** and reuses `TaskSummaryService`. So
  `/dashboard` and `/tasks` are functionally the same screen.
- `/dashboard` was moved out of the `['auth','verified']` group into the plain
  `['auth']` group so it matches `/tasks` exactly (email verification is
  disabled per the auth decision, so the `verified` gate would otherwise
  diverge the two identical routes).
- `resources/js/pages/Dashboard.vue` is now **unrouted / orphaned** — it is no
  longer the target of any route. Left in place rather than deleted; if a
  distinct dashboard UI is ever wanted it can be routed again.

### Inertia resource shape — single vs collection
- List/Index uses `TaskResource::collection($tasks)` (paginated), so the
  prop is `tasks.data` (standard pagination envelope).
- `show()` / `edit()` return a **single** task. A bare `new TaskResource($task)`
  is a `Responsable`, which Inertia resolves via `toResponse()` and wraps in a
  `data` key — giving a `task.data.*` shape. To keep the details/edit pages
  ergonomic (`task.title`, not `task.data.title`), the controller passes
  `(new TaskResource($task))->resolve($request)` instead, producing a flat
  `task` object. Nested `UserResource`/`ProjectResource`/`TagResource` and the
  `comments.*.user` / `attachments.*.uploader` maps resolve recursively.

## Auth pages restyle

### Visual-only change, logic preserved
- `resources/js/layouts/AuthLayout.vue` was rewritten from a centered-card shell
  into the confirmed split-screen: purple→indigo gradient left panel (TaskFlow
  logo/wordmark, italic testimonial from "Maya Rodriguez / MR", three stat
  counters) + white right panel with the form. Left panel is `hidden` below the
  `md` breakpoint. Exact Figma copy used (testimonial, name/title, stats
  `10K+ Teams` / `99.9% Uptime` / `4.9/5 Rating`).
- `Login.vue` / `Register.vue` were restyled (Tailwind only). All form wiring is
  **untouched**: `store.form()`, `v-slot="{ errors, processing }"`,
  `:reset-on-success`, `:passwordrules="passwordRules"`, `data-test` hooks,
  `TextLink`/`InputError`/`Spinner` usage, and the `status` flash.
- Auth page headings/subtext render via the layout's `title`/`description` props
  (passed from each page's `defineOptions({ layout: { title, description } })`),
  unchanged mechanism.

### Deviations
- **"Forgot password?" link is gated by `canResetPassword`** and points to the
  literal `/forgot-password` path. Fortify `resetPasswords()` is intentionally
  disabled (see auth decision), so `@/routes/password` is NOT generated and
  `canResetPassword` is always `false` — the link is therefore hidden in
  practice. This mirrors the original starter-kit pattern ("keep it wired")
  without importing a non-existent module (which previously broke `npm run build`).
- **"Continue with Google" button** is visually present (outline, full-width)
  with no click handler and an HTML comment marking OAuth as out of scope.
- `resources/js/layouts/auth/AuthSplitLayout.vue` (a pre-existing unused split
  variant) was left as-is; `app.ts` resolves `auth/*` → the top-level
  `AuthLayout.vue` that was rewritten.

## App shell

### Rebuilt bespoke shell, did not extend the reka-ui starter shell
- `resources/js/layouts/AppLayout.vue` was **rewritten** from a thin wrapper
  (which delegated to `AppSidebarLayout` → reka-ui `SidebarProvider` collapsible
  icon rail) into a bespoke fixed split shell: a static left **sidebar** (TaskFlow
  logo, nav with lucide icons, a PROJECTS section, a user dropdown) + a **top bar**
  (page title + subtitle left; search input, "+ New Task" button, notification Bell
  right) + `<slot />` content area. Single root `<div>`.
- The reka-ui collapsible `Sidebar` primitives were the wrong model for this
  fixed two-pane design ("adapt rather than duplicate"), so we reused the
  project's own UI primitives instead of re-inventing them:
  `@/components/ui/{avatar,dropdown-menu,button,input}`, `UserMenuContent.vue`
  (drop-in Settings/Logout menu — already wires `logout()` / `profile.edit()` /
  `data-test="logout-button"`), `AppLogoIcon.vue`, the `useInitials` /
  `useCurrentUrl` composables, and `@/routes` wayfinder helpers.
- The starter-kit shell components (`AppSidebar.vue`, `NavMain.vue`,
  `NavFooter.vue`, `NavUser.vue`, `AppSidebarHeader.vue`, `AppContent.vue`,
  `AppShell.vue`, `AppHeader.vue`, `AppHeaderLayout.vue`, `AppSidebarLayout.vue`)
  are now **orphaned** (left in place, not deleted — consistent with prior
  "leave orphaned" pattern). `app.ts` already imports `@/layouts/AppLayout.vue`,
  so the resolver is unchanged; settings pages still wrap `[AppLayout,
  SettingsLayout]` and render fine inside the new `<slot />`.

### Sidebar data — shared `sidebarProjects` prop
- `HandleInertiaRequests::share()` now adds `sidebarProjects`:
  `Project::orderBy('name')->withCount('tasks')->get(['id','name','color'])` →
  mapped to `{ id, name, color, tasks_count }`. Guarded so guests (the login page
  also shares props) get `[]`. Typed in `resources/js/types/global.d.ts`.
- Named `sidebarProjects` (not `projects`) deliberately: the Dashboard and Task
  controllers already pass a page prop called `projects` (`ProjectResource`
  collection, used for task-filter dropdowns), and a page prop **shadows** a
  shared prop of the same name. A distinct key lets the sidebar's fresh
  `tasks_count` data coexist with the page's filter list. (Confirmed via test:
  the shared prop surfaces as `page.props.sidebarProjects`, not nested under
  `data`.)

### Title/subtitle mechanism
- `AppLayout` accepts `title?` / `subtitle?` props (passed via
  `defineOptions({ layout: { title, subtitle } })`). Pages that don't pass a
  title fall back to `page.props.name` (app name). `Tasks/Index.vue` →
  "Tasks" / "Manage and track your team's tasks"; `Tasks/Show.vue` → "Task
  Details" / "View and manage this task". (Dashboard renders the shared
  `Tasks/Index` component, so it inherits that title.)

### Deviations
- **User "role" not shown.** The `User` model has no role column, so the sidebar
  user block shows `name` + `email` (existing `UserInfo`/avatar pattern) instead
  of a role line.
- **"+ New Task" button opens the create modal.** It carries `data-test="new-task-button"`
  and now calls `useTaskModal().openCreate()` — a module-scoped shared store
  (see the Create/Edit Task modal decision). The modal itself is mounted by
  `Tasks/Index.vue`, so the button is effectively a no-op on pages that don't
  render that component (Task Details, Settings). `<Toaster />` is mounted here
  (in `AppLayout`) so the flash toasts emitted by `store`/`update`/`destroy`
  render on every authenticated page.
- **Search input and notification Bell are decorative** (no handlers yet) —
  mirrors the starter kit's existing `Search` button pattern.
- **Profile and Settings nav rows both point at `profile.edit()`** (i.e.
  `/settings/profile`; only that route exists — `settings.profile` is a
  redirect). Two distinct rows kept for design fidelity.
- `app.ts` `layout` resolver left unchanged; `breadcrumbs` prop still accepted by
  `AppLayout` for backward compatibility but no longer rendered.

## Tasks list page

### Shared by `/dashboard` and `/tasks`
- Both routes render `Tasks/Index.vue`. `GET /dashboard` (`DashboardController@index`)
  already reused the `Tasks/Index` component (see the app-shell decision), so this
  single page is the list for both URLs. No second page was built.

### Summary cards — real week-over-week trends (NOT a neutral placeholder)
- `App\Services\TaskSummaryService::summarize()` now also returns a `trends` key
  with `total_tasks` / `completed` / `in_progress` / `overdue_count`, each a
  `{ value, direction }` pair. `SummaryCard.vue` renders the icon badge, the
  count, the label, and the trend delta ("+200% vs last week" etc.).
- Trends are computed by comparison queries, not a snapshots table: the "previous"
  value is every task whose `created_at < now()->subWeek()`. `direction` is
  `up`/`down`/`neutral`; `value` is `'New'` (previous 0 & current > 0),
  `'0%'` (no change / no baseline), or `'%+d%%'` (signed percentage).
- **Approximation (documented):** task status is not snapshotted over time, so the
  completed / in-progress / overdue "week ago" figures assume each record's
  *current* status also applied a week ago. Good enough for a trend card; not a
  precise historical diff. No migration added — it works against existing columns.
- Test: `tests/Feature/DashboardTest.php` ("summary trends compare current totals
  against last week") seeds 1 task >2 weeks old + 2 tasks 3 days old and asserts
  `summary.trends.total_tasks` = `+200%` / `up`. `TaskTest` asserts the four
  `summary.trends.*` keys reach the client on `/tasks`.

### Filters (client-driven, `router.get` with `preserveState`)
- `status`, `priority`, `project_id`, `tag_id` are reka-ui `Select`s bound with
  `v-model` to local refs seeded from the `filters` prop (sentinal `'all'` = no
  filter). A `watch` on the four selects fires `applyFilters()` immediately.
- `search` is a debounced (300ms) `Input`; only the search box is debounced so
  typing doesn't spam the server while Select changes stay instant. `applyFilters()`
  drops any `'all'`/empty value and `router.get(index.url(), params, { preserveState,
  preserveScroll, replace })`. `withQueryString()` on the controller keeps the
  other params through pagination links.

### Table / mobile / pagination / delete
- Desktop table (`hidden md:block`): checkbox, `#id`, Task (title + project color
  dot + tags), Status badge, Priority badge, Assignee avatar+name, Due Date
  (red when `isOverdue` — past due, not done/cancelled), Actions (Edit Link,
  Delete button). Status/Priority colors are local `Record<enum, class>` maps.
  Row click → `router.visit(show.url(task.id))`; the checkbox and Actions cells
  use `@click.stop` so they don't navigate.
- Mobile (`md:hidden`): stacked cards with the same fields.
- Pagination: renders the Laravel `tasks.links` array; active link styled, disabled
  (`url: null`) rendered as plain text, labels via `v-html` (trusted backend
  output for the `«`/`»` markers, wrapped in a native `<span>` to satisfy
  `vue/no-v-html-on-component`).
- Delete uses a **controlled** `DeleteTaskDialog.vue` (reka-ui `Dialog` with
  `:open` + `@update:open` → cancel). Parent holds `pendingDelete`; confirm does
  `router.delete(destroy.url(id), { preserveScroll, onSuccess: clear })`. This is a
  deliberate deviation from the starter-kit's uncontrolled `DialogTrigger` pattern
  (used in `DeleteUser.vue`) so the dialog state lives in the list component.

### Deviations
- **Row checkboxes are a no-op.** Present and individually toggleable
  (`selectedIds` `Set`), but no bulk action (select-all, bulk delete/status) is
  wired yet — out of scope for this list page; left for a later prompt.
- **"Create your first task" (empty state) is a no-op.** There is no
  `tasks.create` route/page; the empty-state CTA carries `data-test="new-task-button"`
  but no handler. AppLayout's "+ New Task" button, by contrast, is now wired to
  open the create modal (see the Create/Edit Task modal decision).
- **`+ Filters` is not implemented** — the four inline Selects (plus search) cover
  the agreed filter set; the visual "+ Filters" affordance from the design is
  omitted rather than built as a non-functional button.

## Create/Edit Task modal

### One modal, two modes, driven by a shared store
- `TaskFormModal.vue` is a single reka-ui `Dialog` (controlled: `:open` +
  `@update:open` → `emit('close')`) that handles both Create and Edit. The header
  title/subtext and the footer primary button ("Create Task" / "Save Changes")
  switch on whether the `task` prop is present; in Edit mode the footer also
  shows a red "Delete" that flips to an inline two-step "Really delete?" confirm
  (no separate dialog). Form submission uses Inertia `useForm` (`form.post` for
  create, `form.put` for edit, `router.delete` for delete); 422 errors surface
  via `form.errors.*` under each field. Success → `emit('close')`; the backend
  flashes a `toast` that `AppLayout`'s `<Toaster />` surfaces.
- Opening is coordinated through a **module-scoped** `useTaskModal` composable
  (`isOpen` / `editingTask` refs + `openCreate` / `openEdit` / `close`). This lets
  the global AppLayout top-bar button and the page-level modal share state without
  an event bus. `openEdit(task)` takes the row `Task` object directly — its
  `TaskFormTask` shape only adds `description` / `assignee_id` / `project_id` to
  the page's `Task` interface (all present in `TaskResource` at runtime; the page
  type just omitted them).

### Inline tag creation + Assignee select
- The Tags `<Select multiple>` is backed by a local `availableTags` ref seeded from
  the `tags` prop, so a tag created inline appears immediately without a reload.
  `addTag()` does a raw `fetch` POST to `tags.store` — Inertia v3 removed axios, so
  we read the non-httpOnly `XSRF-TOKEN` cookie and send it as `X-XSRF-TOKEN` (the
  standard Laravel SPA pattern). New tags are created with a default gray
  `#6b7280` because `StoreTagRequest` requires a `color` hex.
- Assignee is a `<Select>` over the new `users` prop (value = `String(id)`); an
  Unassigned sentinel `'none'` is normalized back to `''` on submit. Due Date is a
  native `<input type="date">`. Description uses a native `<textarea>` (there is no
  `textarea` UI component), styled with the `Input.vue` Tailwind classes.

### Backend changes (small)
- `TagController::store()` now branches on `$request->inertia()`: non-Inertia
  requests (the modal's `fetch`) get `JSON:201 { tag: TagResource }`; Inertia
  requests still redirect to `tags.index`. This keeps the starter-kit Tags page
  working while enabling in-modal inline creation.
- `TaskController@index` and `DashboardController@index` now also pass `users`
  (`UserResource` collection) for the Assignee select, plus `projects` / `tags` /
  `users` via `->resolve($request)` so they reach the client as **plain arrays**
  (not the `{ data: [...] }` envelope that `JsonResource::collection` would
  otherwise wrap them in). This also fixes a latent bug: the list page's existing
  filter `<Select>`s were iterating a wrapped `data` key.

### Tests
- `TagTest` create assertion changed from `assertRedirect(tags.index)` to
  `assertStatus(201)` + `assertJsonStructure(['tag' => ['id','name','color']])`.
- `TaskTest` list assertion gained `->has('users')` / `users.0.{id,name,email}`
  to guard the new prop. Pint clean; full suite green.

### Deviations
- **`<Toaster />` mounted in `AppLayout`** (was only in the orphaned starter
  layouts), so backend flash toasts are now visible everywhere.
- **Inline delete confirm** (not `DeleteTaskDialog`) per the chosen UX.
- **"+ New Task" is a no-op on non-list pages** — no modal is rendered there.
- **Collections now resolve to plain arrays** (documented above) — a behavior
  change from the default `JsonResource::collection` wrapping, made to keep the
  client-side selects simple.

## Polish pass

### `Tasks/Show.vue` was a placeholder (core-CRUD gap, now built)
- The `tasks.show` route + `TaskController@show` shipped earlier, but the Vue
  page was only a **stub** that rendered none of its data — comments and
  attachments were passed by the controller but never displayed. That is why the
  Task-Details empty states and responsive requirements of this pass couldn't be
  met until the page was actually built.
- It is now a **full Task Details page**: header card (title, status/priority
  badges, inline Status `<Select>` that `router.patch`es `tasks.status.update`
  on change, due date / assignee / project / created-by meta grid, tags badges,
  description), a Comments section (list with avatar+relative-time+body, delete
  button for the author, add form via `useForm`), and an Attachments section
  (list with size/uploader/download link, author-only delete, file-upload form).
  All three lists have centered empty states with a lucide icon.

### Tasks list empty state now distinguishes filter vs no-task
- `Tasks/Index.vue` empty block splits on the existing `hasActiveFilters`
  computed: filters active → "No tasks match your filters" + "Try adjusting or
  clearing your filters." + a **Clear filters** button (`resetFilters()`); no
  filters → "No tasks found" + a **Create your first task** CTA that now opens
  the create modal (`openCreate()`), replacing the prior no-op CTA. Padding
  softened to `p-8 sm:p-12` so it isn't cramped at 375px.

### Flash toasts already working; `<Toaster />` added to AuthLayout
- Every authenticated page already surfaces success toasts (controllers flash
  `toast: { type, message }`; `flashToast.ts` listens on `router.on('flash')`
  and calls `toast[type](message)`; `<Toaster />` was already in `AppLayout`).
  Only addition: `<Toaster />` is now also mounted in `AuthLayout.vue` so auth
  flashes (e.g. password-mismatch / throttling errors) surface too.

### Mobile sidebar drawer + shared `SidebarContent`
- `AppLayout.vue` sidebar was `hidden … md:flex` — below `md` it vanished with
  no nav access. Extracted the sidebar body (logo + nav + projects + user
  dropdown) into a new `components/SidebarContent.vue` that emits `navigate`
  on `<Link>` click. The desktop `<aside>` and a new `Sheet` drawer (reka-ui
  `Sheet`, `side="left"`) both render `<SidebarContent>`; the drawer closes on
  `@navigate`. A hamburger `<Button>` (`Menu` icon, `md:hidden`) in the top bar
  opens it.

### Shared badge maps extracted
- `statusBadgeClass` / `priorityBadgeClass` (`Record<string,string>`) were
  duplicated in `Tasks/Index.vue` and `Tasks/Show.vue`; both now import them
  from a new `lib/taskBadges.ts` so the two views stay consistent.

### Modal scrolls on mobile
- `TaskFormModal.vue` `DialogContent` gained `max-h-[90dvh] overflow-y-auto`
  (keep `sm:max-w-2xl`) so long forms scroll rather than overflow on mobile.

### Tests added
- `tests/Feature/TaskTest.php`: a combined-filter test creates tasks across
  distinct status/priority/project/tag combos and asserts that setting **all
  four** filters returns exactly the one matching task, and that dropping the tag
  filter broadens it to two. (Existing tests filter one dimension at a time.)
  Note: tag is created *after* the tasks, because the Task factory's
  `afterCreating` hook auto-attaches 1–3 random tags when any Tag exists —
  creating the tag first would cause a `tag_task` unique-constraint collision.
- `tests/Feature/AuthTest.php` (new): guest redirects off `/dashboard` &
  `/tasks.index`; valid/invalid login; authenticated logout; valid/invalid
  registration. Uses `User::factory()` with a known bcrypt password and Fortify
  routes (`login.store`, `logout`, `register.store`).
- Policy edge cases (forbidden task/comment/attachment deletes) were already
  covered by existing `TaskTest`/`CommentTest`/`AttachmentTest`, so no
  duplication was added.

## Unverified-user login interception

### Problem
Previously, an unverified user (`email_verified_at === null`) could log in and
reach the authenticated dashboard — the `email_verified_at` column existed but
was never enforced by any gate or middleware because Fortify's
`emailVerification()` feature was disabled. The OTP flow was intentionally out
of scope at project start; now we are adding it.

### Solution — pipeline step, not a response-class override
Rather than overriding Fortify's `LoginResponse` contract (which would replace
the entire response object), we inject the verification check as the **last step
in the authentication pipeline** via `Fortify::authenticateThrough()` in
`FortifyServiceProvider::configureAuthenticationPipeline()`.

The pipeline runs:
1. `EnsureLoginIsNotThrottled` (rate limiter)
2. `RedirectIfTwoFactorAuthenticatable` (if two-factor feature enabled — it isn't)
3. `AttemptToAuthenticate` (Fortify's credentials check)
4. `PrepareAuthenticatedSession` (regenerates session)
5. **Custom closure** — checks `auth()->user()->email_verified_at === null`:
   - Logs the user out
   - Issues a `'login'`-type OTP via `IssueVerificationCode::handle($email, 'login')`
   - Redirects to `verification.notice?email=...` with status message

### Why this approach
- **No separate response class.** The user explicitly asked to avoid using a
  response class.
- **Surgeries the minimum pipeline.** Only the final closure is custom; the
  standard Fortify actions handle credentials and session setup.
- **Registration and password-reset are untouched.** `RegisteredUserController`
  still issues `'registration'` codes; `PasswordResetController` still issues
  `'reset'` codes. Only the login pipeline is modified.

### EmailVerificationController changes
- `verify()` now accepts **both** `'registration'` and `'login'` code types
  (`whereIn('type', ['registration', 'login'])`). A user redirected from the
  new login flow can enter the exact same OTP screen to verify.
- `resend()` preserves the original code type by querying the latest code
  record's `type` field, so a user who was redirected from login receives a
  fresh `'login'`-type code rather than `'registration'`.

### Test coverage
| Test | What it guards |
|---|---|
| `a verified user can log in with valid credentials` | Normal login still works (user created with `email_verified_at: now()`) |
| `an unverified user is redirected to verify-email and given a login-type OTP on login attempt` | Redirect to `verification.notice`, session status, `'login'`-type code created, `assertGuest()` |
| All existing `AuthTest`, `AuthenticationTest`, `PasswordResetTest`, `RegistrationTest` | Registration and password-reset flows unchanged; full suite 75 passed |

### Files changed
- `app/Providers/FortifyServiceProvider.php` — added `configureAuthenticationPipeline()`
- `app/Http/Controllers/Auth/EmailVerificationController.php` — `verify()` uses `whereIn`,
  `resend()` preserves code type
- `tests/Feature/AuthTest.php` — new test for unverified login, existing test renamed
- `DECISIONS.md` — this entry

