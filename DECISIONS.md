# Decisions

## Auth scaffolding review (Prompt: Fortify register/login/logout)

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

## Projects & Tags backend (Prompt: backend CRUD for projects + tags)

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

## Tasks backend (Prompt: CRUD + project/tag associations)

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

## Auth pages restyle (Prompt: Figma split-screen)

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

## App shell (Prompt: shared authenticated shell for Dashboard/Tasks/Task Details)

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
- **"+ New Task" button is a no-op.** There is no `tasks.create` route/page (the
  tasks resource is generated `->except(['create'])`), so the button carries
  `data-test="new-task-button"` but has no handler; the task-create modal is out
  of scope for this shell (wiring left for a later prompt).
- **Search input and notification Bell are decorative** (no handlers yet) —
  mirrors the starter kit's existing `Search` button pattern.
- **Profile and Settings nav rows both point at `profile.edit()`** (i.e.
  `/settings/profile`; only that route exists — `settings.profile` is a
  redirect). Two distinct rows kept for design fidelity.
- `app.ts` `layout` resolver left unchanged; `breadcrumbs` prop still accepted by
  `AppLayout` for backward compatibility but no longer rendered.


