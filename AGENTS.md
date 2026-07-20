# AGENTS.md

Instructions for AI coding agents (Claude Code, Cursor, Copilot, etc.)
working in this repository. Read this before making any change.

## Project

This codebase is graded on Laravel architecture, Vue implementation,
Figma accuracy, DB design, code quality, validation/error handling, UI
responsiveness, and git commit quality — treat all of these as real
constraints, not suggestions.

## Stack & Architecture

- **Monolith**: single Laravel app serving Inertia.js + Vue 3 pages.
  There is no separate frontend build and no externally-exposed REST API.
  Do not introduce CORS config, a second `/frontend` app, or a
  token-based API auth flow — the starter kit's session-based web auth is
  correct here.
- Laravel (latest stable), Inertia.js, Vue 3 + TypeScript, Tailwind CSS,
  MySQL.
- Auth via Laravel's official Vue starter kit, which is built on
  **laravel/fortify** — NOT laravel/breeze. Don't hand-roll auth
  controllers and don't attempt to install Breeze; the starter kit's
  Fortify-based scaffolding already satisfies the Form Request /
  best-practice bar — extend it, don't replace it.
- Testing framework is **Pest** (function-based `it()`/`test()` syntax in
  `tests/Feature/`), not PHPUnit's class-based `TestCase` style — even
  though Pest runs on top of PHPUnit, write new tests in Pest syntax to
  match the existing suite.
- Static analysis via Larastan/PHPStan (`npm run types:check` on the JS
  side calls `vue-tsc`; `composer types:check` calls `phpstan analyse` on
  the PHP side) — both should stay clean.
- Icons: `@lucide/vue`, already a project dependency — use it consistently
  rather than mixing icon sets.
- Vue pages live in `resources/js/Pages/`, shared layouts in
  `resources/js/Layouts/`, reusable pieces in `resources/js/Components/`.

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
- This applies from the very first PHP file created (Prompt 1 onward), not
  as a cleanup step at the end.
- Vue/TS files: run `npm run lint:check` (ESLint) and `npm run
  format:check` (Prettier) — both are already configured in this starter
  kit's `package.json`; don't introduce a different linter/formatter.

## Testing — Continuous, Not a Final Step

Tests are written alongside the feature they cover, in the same commit or
the immediately following one — never batched into a single end-of-project
testing pass. Concretely:

- Every backend prompt that adds a controller, policy, or Form Request
  ships with a Pest Feature test in the same work session: auth (register/
  login/logout + protected-route rejection), Task CRUD (create/update/
  delete, ownership-based authorization via the policy, filter/sort
  correctness), Comments (create, delete-own-only), Attachments (upload,
  delete-own-only), Projects/Tags (basic CRUD where implemented).
- Run `php artisan test` before every commit that touches backend logic.
  A commit that breaks an existing test is not acceptable — fix it in the
  same commit, don't defer. `composer test` runs the fuller check (config
  clear + lint:check + types:check + php artisan test) and is what to run
  before considering any prompt truly done.
- `/agent-prompts.md`'s dedicated "Polish Pass" prompt is for closing
  coverage gaps and adding edge-case tests, not for writing tests for the
  first time.

## Scope

Two tiers — never blur them:

1. **Core (non-negotiable, PDF-graded)**: Task CRUD (title, description,
   status, priority, due date, assignee), list/filter/sort, dashboard
   summary, Register/Login/Logout. This must always work correctly. Never
   sacrifice this tier for tier 2 features.
2. **Extended (Figma-driven, matches the design but not in the PDF)**:
   Projects, Tags, Task Comments, Task Attachments, an OTP-style email
   verification screen, a Google OAuth button (UI only), a pre-auth
   splash/welcome page. Build these fully if time allows. If time runs
   short, cut in this order: splash page → Attachments → Comments →
   Tags/Projects. Log every cut in `/DECISIONS.md`.

## Data Model Conventions

- `status` — backed PHP enum `App\Enums\TaskStatus`: `todo`,
  `in_progress`, `in_review`, `done`, `cancelled`. Five values, not three
  — don't collapse `in_review`/`cancelled` into the others.
- `priority` — backed PHP enum `App\Enums\TaskPriority`: `low`, `medium`,
  `high`.
- Store enums as plain string columns with PHP enum casts, not MySQL
  native enum types (portability, easier migrations).
- Task relationships: `assignee_id` and `created_by` are both FKs to
  `users` but are semantically different — name the Eloquent relations
  `assignedTasks()`/`createdTasks()` on `User`, never generic `tasks()`.
- Tags are many-to-many via a `tag_task` pivot. Projects are a simple
  `belongsTo` on Task (one project per task, not many-to-many).
- Task IDs are displayed as `TF-001` style in the UI (`sprintf('TF-%03d',
  $id)`) — this is display formatting only, not a schema column.

## Backend Conventions

- Every mutating endpoint uses a Form Request class for validation — no
  inline `$request->validate()` in controllers.
- Use Laravel API Resources (`TaskResource`, `UserResource`,
  `ProjectResource`, `TagResource`) to shape data passed into
  `Inertia::render()`, even though nothing is exposed as a public REST
  API. This is a deliberate choice to keep the "API Resources" best
  practice signal despite the monolith architecture — explain this in
  `/DECISIONS.md`, don't drop it.
- Authorization via Policies (`TaskPolicy`), not ad-hoc `if` checks in
  controllers. Only a task's creator can update/delete it; any
  authenticated user can view, create, and comment.
- Avoid N+1 queries — eager-load `assignee`, `creator`, `project`, `tags`
  wherever tasks are listed or shown.
- Dashboard/summary counts go through a shared `TaskSummaryService`, not
  duplicated inline queries — the Dashboard and Tasks list are the *same
  page* (see below) and must not compute the same numbers twice.

## Frontend Conventions

Directly satisfies responsive layout, clean UI implementation, and matching Figma as closely as possible
are graded criteria, not aesthetic preferences.
Figma link: "https://www.figma.com/design/uuG7IjiNz8jDyo17yD4aAA/Design-TaskFlow-SaaS-App?node-id=0-1&p=f&t=P4g2biS1Fy2wCPTd-0".

- **Dashboard and Tasks list are one page, not two.** The Figma design
  confirms this: greeting text, 4 summary cards, then the
  filterable/searchable task table, all in a single `/dashboard` view.
  Don't build separate `DashboardView` and `TasksView` pages with
  duplicated logic.
- Create/Edit Task is a **modal**, not a separate route.
- Match the confirmed design system: purple/indigo gradient accents
  (auth screens' left panel, primary buttons), white content areas,
  colored status/priority badges, avatar-initial chips for assignees.
- Tailwind utility classes only — no separate CSS files unless a specific
  visual effect (e.g. the auth gradient) genuinely needs it.
- Mobile: sidebar collapses to a drawer/hamburger, the task table
  collapses to stacked cards, modals go near-fullscreen. Verify at
  375px/768px/1280px on every page before considering it done — not just
  at the end.

## Git & Commits

- Alwayse write, run Test and run Pint
- Commit messages explain **why**, not just what
  (`feat(tasks): CRUD backend with project/tag associations, search, and
  resource-shaped data`, not `add task controller`).
- Never bundle unrelated changes into one commit.

## Handling Ambiguity

If a requirement is genuinely unclear (Figma doesn't cover a case, the
PDF and Figma disagree, a field's exact validation rule isn't specified):
make the most reasonable Laravel/Inertia/Vue best-practice choice, proceed,
and add one line to `/DECISIONS.md` explaining the assumption. Do not stop
to ask — this is a take-home assignment with a hard deadline, and
`/DECISIONS.md` is where assumptions are meant to be surfaced for the
reviewer.

## Do Not

- Do not add a second `/frontend` app, CORS config, or Sanctum SPA
  token/cookie setup — this is a monolith.
- Do not implement real Google OAuth — render the button, leave it
  non-functional, note it in `/DECISIONS.md`.
- Do not skip Form Requests or API Resources to save time — these are
  named, explicit grading criteria.
- Do not silently drop Extended-tier features without logging the cut in
  `/DECISIONS.md`.
- Do not commit unformatted PHP — run `composer lint` first.
- Do not defer test-writing to a single end-of-project pass — write tests
  alongside the feature, every backend-touching prompt.
- Do not install Breeze or PHPUnit-style class-based tests — this project
  uses Fortify and Pest; adding Breeze on top would conflict with the
  already-scaffolded auth.

