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


