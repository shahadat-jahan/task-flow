# AGENTS.md

Instructions for AI coding agents (Claude Code, Cursor, Copilot, etc.)
working in this repository. Read this before making any change.

## Project

This codebase is graded on Laravel architecture, Vue implementation,
Figma accuracy, DB design, code quality, validation/error handling, UI
responsiveness, and git commit quality — treat all of these as real
constraints, not suggestions. Always write Clean, readable, and maintainable code.

## Stack & Architecture

- **Monolith**: single Laravel app serving Inertia.js + Vue 3 pages.
  There is no separate frontend build and no externally-exposed REST API.
  Do not introduce CORS config, a second `/frontend` app, or a
  token-based API auth flow — the starter kit's session-based web auth is
  correct here.
- **Laravel** (latest stable), **Inertia.js**, **Vue 3 + TypeScript**, **Tailwind CSS**, **MySQL**.
- Auth via Laravel's official Vue starter kit, which is built on
  **laravel/fortify** — NOT laravel/breeze. Don't hand-roll auth
  controllers and don't attempt to install Breeze; the starter kit's
  Fortify-based scaffolding already satisfies the Form Request /
  best-practice bar — extend it, don't replace it.
- **Service Layer Pattern**: Business logic and data manipulation must be encapsulated inside Service classes (e.g., `TaskService`, `CommentService`, `TaskSummaryService`).        Controllers must remain thin and delegate tasks to services.
- **Data Serialization (No API Resources)**: Avoid API Resources for Inertia props. Prefer plain arrays, `toArray()`, or flat objects (`->resolve()`) to pass simpler, flat data shapes to Vue components.
- **Testing Framework**: **Pest** (function-based `it()`/`test()` syntax in
  `tests/Feature/`), not PHPUnit's class-based `TestCase` style — even
  though Pest runs on top of PHPUnit, write new tests in Pest syntax to
  match the existing suite.
- **Static Analysis**: Larastan/PHPStan (`npm run types:check` on the JS
  side calls `vue-tsc`; `composer types:check` calls `phpstan analyse` on
  the PHP side) — both should stay clean.
- **Icons**: `@lucide/vue`, already a project dependency — use it consistently
  rather than mixing icon sets.
- Vue pages live in `resources/js/Pages/`, shared layouts in
  `resources/js/Layouts/`, reusable pieces in `resources/js/Components/`.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

### Never use Response or Resource classes
Only use actions for complex logic in Fortify. Do not create `App\Http\Responses\*` or `App\Http\Resources\*` classes. Use plain arrays, `->toArray()`, or `->resolve()` directly in controllers to keep the data path simple.

### Service Layer must be used
Always delegate business logic to service classes (e.g., `TaskService`, `CommentService`, `TaskSummaryService`). Controllers must remain thin and only handle request/response concerns. Never inline business logic in controllers or routes.

### Always queue emails, never send synchronously
Use `Mail::to(...)->queue(...)` instead of `Mail::to(...)->send(...)`. Sending email synchronously blocks the HTTP response and makes the UI feel slow. The mailables in this project already have the `Queueable` trait and `QUEUE_CONNECTION=database` is configured, so queueing is always available.

### Use DB::transaction() for atomic operations
Any code that performs multiple related database writes (e.g., creating a record + updating a related counter, deleting + logging, issuing a code + sending notification) must be wrapped in `DB::transaction(...)` to prevent partial writes on failure.

### Handle race conditions properly
Always consider concurrent requests that could operate on the same data simultaneously. Use database transactions, unique constraints, and `updateOrCreate`/`firstOrCreate` patterns to prevent duplicate records or inconsistent state. When deleting old records before creating new ones (e.g., OTP codes), wrap both operations in a transaction and add a database-level unique constraint on the relevant columns as a safety net — application-level checks alone are not sufficient under high concurrency.

### Use pessimistic row-level locking where needed
When a transaction reads a row and then updates/deletes it based on that read, use `lockForUpdate()` (pessimistic locking) inside the transaction to prevent concurrent requests from reading stale data and causing duplicate writes. This is essential for patterns like "delete old records then insert new ones" (e.g., OTP code issuance) where two concurrent requests would both see no existing row and both insert. Apply `lockForUpdate()` on the query that selects the rows to be modified before performing the write.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Coding Standard — PSR-12

All PHP code must comply with PSR-12. Non-negotiable, not a polish-pass
item:

- Laravel Pint is already in this project's `composer.json`
  (`require-dev`), configured for PSR-12 by default — don't install or
  configure a formatter, just use what's there.
- Run `composer lint` (which runs `pint --parallel`) before every commit
  that touches PHP. If Pint reports changes, apply them and include them
  in the same commit — never leave a commit with un-formatted PHP.
  `composer lint:check` (the `--test` / dry-run variant) is what CI-style
  checks use — safe to run that first if you just want to see what would
  change.
- This applies from the very first PHP file created, not as a cleanup step at the end.
- Vue/TS files: run `npm run lint:check` (ESLint) and `npm run format:check` (Prettier) — both are already configured in this starter kit's `package.json`; don't introduce a different linter/formatter.

## Testing — Continuous, Not a Final Step

Tests are written alongside the feature they cover, in the same commit or
the immediately following one — never batched into a single end-of-project
testing pass. Concretely:

- Every backend feature that adds/updates a controller, policy, service, or Form Request
  ships with a Pest Feature test in the same work session: auth (register/
  login/logout + protected-route rejection), Task CRUD (create/update/
  delete, ownership-based authorization via policy, filter/sort
  correctness), Comments (create, delete-own-only), Attachments (upload,
  delete-own-only), Projects/Tags (basic CRUD/services where implemented).
- Run `php artisan test` before every commit that touches backend logic.
  A commit that breaks an existing test is not acceptable — fix it in the
  same commit, don't defer. `composer test` runs the fuller check (config
  clear + lint:check + types:check + php artisan test) and is what to run
  before considering any prompt truly done.

## Scope & Core Modules

All major modules (**Task**, **Comments**, **Attachments**, **Projects**, **Tags**) are fully implemented via Service classes:

1. **Core Features**:
   - **Tasks**: Full CRUD with status, priority, due date, assignee, and tags filter/sort.
   - **Comments**: Per-task comment thread (create, delete own) backed by `CommentService`.
   - **Attachments**: File upload/download/delete (own only) on task details, stored on the `public` disk.
   - **Projects & Tags**: Global projects in sidebar with task counts; inline tag creation and filtering.
   - **Dashboard / Summary**: Aggregated metrics and week-over-week trend analysis via `TaskSummaryService`.
 2. **Auth & Unused Features**:
    - Fortify handles Register, Login, and Logout. Email Verification is partially active: registration issues `'registration'`-type OTPs (unchanged), and the login pipeline now intercepts unverified users, issues a `'login'`-type OTP, and redirects to verify-email. 2FA and Reset Password features remain disabled/out-of-scope.
