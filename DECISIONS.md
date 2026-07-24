# Project Summary

## Design Decisions

1. **Monolithic Architecture**
   - Single Laravel application combining both frontend (Vue 3/Inertia) and backend
   - No separate REST API layer
   - Uses Wayfinder for clean route-to-TS function generation
   - Vite dev server proxies to the PHP app ("composition, not duplication")

2. **Authentication Flow**
   - Only Fortify register/login/logout (OTP/Google auth out of scope)
   - Email verification disabled by default
   - Unverified users redirected to verification on login via a custom Fortify pipeline step
   - `EmailVerificationController` handles both 'registration' and 'login' code types

3. **Data Architecture**
   - No API Resource or Response classes — Inertia props are built from plain arrays / `->toArray()` directly in controllers, keeping the data path simple since there's no external API consumer
   - Tag creation is inline only (no standalone tag-management CRUD)
   - Projects have global visibility (no per-user scoping)

4. **Service Layer**
   - Business logic extracted to service classes (`TaskService`, `TaskCommentService`, `TaskAttachmentService`, `ProjectService`, `TagService`, `TaskSummaryService`)
   - Controllers stay thin: validate → policy → service → response
   - `TaskSummaryService` centralizes the summary-card queries shared by Tasks and Dashboard

5. **UI Structure**
   - Fixed split shell: left sidebar + top bar + content area
   - Create/Edit Task as a reusable modal via a shared store
   - Mobile drawer for sidebar navigation
   - Flash toasts visible everywhere (AppLayout + AuthLayout)

## Assumptions Where Figma Was Unclear

1. **Dashboard vs Tasks**
   - Figma showed the Dashboard and Tasks screens with near-identical layouts (same table, same summary cards)
   - Rather than build a second page, `/dashboard` and `/tasks` both render the shared `Tasks/Index.vue` component
   - Chosen to avoid duplicating an effectively identical screen

2. **Activity Feed**
   - Figma's detail view implied an activity feed
   - Implemented as per-task comment threads (Task Details page) instead
   - No separate activity-log package was introduced

3. **Trend Calculation**
   - Summary cards show week-over-week deltas
   - Approximation: current task status is assumed to also have applied a week ago (no historical snapshot table)
   - Documented as an approximation, not exact history

4. **Attachments**
   - Figma showed per-task file upload/download
   - Stored on the `public` disk; requires `php artisan storage:link` for downloads to resolve
   - Users can only manage (delete) their own uploads

5. **Inline Tag Creation**
   - Figma's tag chips suggested inline creation rather than a separate management screen
   - Tags are created via the `tags.store` endpoint during task create/edit
   - Default color `#6b7280` for newly created tags

## Implementation Improvements

1. **Accessibility & UX**
   - Mobile sidebar drawer (hamburger menu)
   - Modal scrolls on mobile (`max-h-[90dvh] overflow-y-auto`)
   - Avatar color computed from name hash for consistent per-user coloring
   - Debounced (300ms) task search across title/description, combined with status/priority/project/tag filters

2. **Data Visualization**
   - Real week-over-week trends for summary cards (via `TaskSummaryService`)
   - Color-coded status/priority badges
   - Overdue tasks highlighted in red
   - Project color shown in both the table and mobile card views

3. **Performance & Quality**
   - Centralized summary queries in `TaskSummaryService` (zero-filled counts for every enum value, no N-per-status queries)
   - Eloquent relationships eager-loaded to avoid N+1 queries
   - TypeScript interfaces used throughout the frontend

4. **Security**
   - Ownership checks for task/comment/attachment deletions
   - Email verification pipeline enforced for unverified logins
   - Authentication enforced via middleware groups
   - Flash toasts confirm successful actions

5. **Development Experience**
   - Pest testing throughout (feature tests covering auth, task CRUD/filtering, comments, attachments, dashboard trends)
   - Shared composables (`useTaskModal`, `taskBadges`) to avoid duplicated logic across pages
   - Wayfinder for type-safe route handling
   - Laravel Pint, PHPStan/Larastan, ESLint, and Prettier all kept clean as part of the standard workflow

## Current State

- ✅ Core task management (CRUD, filtering, search, sorting, pagination)
- ✅ Projects & tags management
- ✅ Comments & attachments per task
- ✅ Authentication & email verification pipeline
- ✅ Responsive design (desktop/tablet/mobile)
- ⚠️ Bulk operations (row checkboxes present but currently no-op)

## Key Technologies

- PHP 8.4, Laravel 13
- Inertia v3 + Vue 3 + TypeScript
- Tailwind CSS v4
- Laravel Wayfinder, Fortify, Boost
- Pest (PestPHP v4)
- reka-ui for drawer/modal primitives
