# Task Flow

A task management application built with Laravel, Inertia.js, Vue, and Tailwind CSS.

## Features

- **Task Management**: Create, read, update, and delete tasks
- **Project Organization**: Group tasks within projects
- **Tagging System**: Categorize tasks with colored tags
- **Task Details**: Status, priority, due dates, assignees
- **User Management**: Authentication via Laravel Fortify
- **Real-time Updates**: Powered by Inertia.js

## Database Schema

### Users Table
Standard Laravel users table (from authentication scaffolding) with additional email verification column (unused).

### Projects Table
- `id` – primary key
- `name` – project name
- `color` – hex code (default: `#7C3AED` – purple)
- `owner_id` – foreign key to users (cascade on delete)
- `timestamps`

### Tasks Table
- `id` – primary key
- `title` – required string
- `description` – nullable text
- `status` – enum (`todo`, `in_progress`, `in_review`, `done`, `cancelled`) – default `todo`, indexed
- `priority` – enum (`low`, `medium`, `high`) – default `medium`, indexed
- `due_date` – nullable date
- `assignee_id` – foreign key to users (null on delete, nullable)
- `created_by` – foreign key to users (cascade on delete)
- `project_id` – foreign key to projects (null on delete, nullable)
- `timestamps`
- Indexes on `status`, `priority`, `assignee_id`, `project_id`

### Tags Table
- `id` – primary key
- `name` – unique string
- `color` – hex code
- `timestamps`

### Tag Task Pivot Table
- `task_id` – foreign key to tasks (cascade on delete)
- `tag_id` – foreign key to tags (cascade on delete)
- Composite primary key (`task_id`, `tag_id`)

## Enums
- `App\Enums\TaskStatus`
- `App\Enums\TaskPriority`

## Relationships
- **User**:
  - `assignedTasks()` – tasks where user is assignee
  - `createdTasks()` – tasks created by user
  - `projects()` – projects owned by user
- **Project**:
  - `owner()` – belongs to user
  - `tasks()` – has many tasks
- **Task**:
  - `assignee()` – belongs to user
  - `creator()` – belongs to user
  - `project()` – belongs to project
  - `tags()` – belongs to many tags

## Installation

1. Clone the repository
2. Install PHP dependencies:
   ```bash
   composer install
   ```
3. Install Node.js dependencies:
   ```bash
   npm install
   ```
4. Copy `.env.example` to `.env` and configure your database connection
5. Generate application key:
   ```bash
   php artisan key:generate
   ```
6. Run migrations and seed the database:
   ```bash
   php artisan migrate --seed
   ```
7. Start the development servers:
   ```bash
   php artisan serve   # Laravel backend
   npm run dev         # Vite frontend
   ```

## Usage

- Register a new account or log in
- Create projects from the sidebar
- Add tasks to projects, set status, priority, due dates, assignees, and tags
- Filter tasks by status, priority, assignee, project, or tags
- Update tasks inline or via edit form
- Delete tasks or projects as needed

## Testing

Run the test suite:
```bash
php artisan test
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).