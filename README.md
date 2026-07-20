# Task Flow

A task management application built as a monolithic **Laravel 13 + Inertia.js v3 +
Vue 3 + Tailwind CSS v4** app. There is no separate API tier — pages are rendered
server-side as Inertia responses and the client is a Vue SPA. (See
[DECISIONS.md](./DECISIONS.md) for the architecture rationale and the assumptions
made where the original brief was ambiguous.)

## Features

- **Task CRUD** via a single Create/Edit modal (status, priority, due date,
  assignee, project, tags, description).
- **Dashboard / Tasks list** — one shared page with summary cards (totals +
  week-over-week trends), client-driven filters (status, priority, project, tag,
  free-text search), responsive table → mobile cards, and a delete dialog.
- **Task Details** — inline status update, comments (list / add / delete your
  own), and attachments (upload / download / delete your own).
- **Projects & Tags** — tasks are grouped by project; tags are created inline
  while creating/editing a task (no separate tag-management screen).
- **Authentication** via Laravel Fortify (register, login, logout).

## Prerequisites

- PHP **8.4**
- [Composer](https://getcomposer.org/)
- Node.js + npm
- MySQL

## Setup

1. Clone the repository and enter the directory:

   ```bash
   git clone <repo-url> task-flow
   cd task-flow
   ```

2. Install PHP dependencies:

   ```bash
   composer install
   ```

3. Install Node.js dependencies:

   ```bash
   npm install
   ```

4. Create your environment file and configure the database. The starter kit
   defaults to **SQLite**, but this project is configured for **MySQL**:

   ```bash
   cp .env.example .env
   ```

   Then set the `DB_*` variables in `.env`:

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=task_flow
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. Generate the application key:

   ```bash
   php artisan key:generate
   ```

6. Run migrations and seed the database (the seeder creates demo users, projects,
   tags, tasks, and some comments/attachments):

   ```bash
   php artisan migrate --seed
   ```

## Running locally

### Recommended (one command)

`composer run dev` starts everything concurrently — the PHP server, the queue
listener, and the Vite dev server:

```bash
composer run dev
```

Then open the printed `APP_URL` (default `http://localhost:8000`).

### Manual alternative

Run the two processes in separate terminals:

```bash
php artisan serve   # Laravel backend (http://localhost:8000)
npm run dev         # Vite frontend (HMR on :5173)
```

### Attachments

Uploaded files are stored on Laravel's `public` disk, so they are only
downloadable after you create the storage symlink:

```bash
php artisan storage:link
```

Without it, attachment download links return 404.

## Verifying code quality (for reviewers)

```bash
composer lint           # Laravel Pint (code style)
composer test           # config:clear + Pint check + PHPStan (types:check) + php artisan test
npm run types:check    # vue-tsc frontend type check
```

## Testing

The suite is Pest (PHPUnit). Run it with:

```bash
php artisan test
```

> The starter kit's password-reset tests are skipped because Fortify's
> `resetPasswords()` feature is intentionally disabled (see DECISIONS.md).

## License

This project is open-sourced software licensed under the
[MIT license](https://opensource.org/licenses/MIT).
