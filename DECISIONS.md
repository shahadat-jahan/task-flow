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

