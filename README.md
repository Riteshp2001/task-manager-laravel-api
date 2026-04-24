# Task Manager API (Laravel)

Laravel is the primary backend for the task manager. It handles authentication, project management, task ownership, and role-based access control. The Django service is used only for overdue-task rule evaluation.

## What is implemented

- Sanctum token authentication
- Role-based access for `admin` and `user`
- Admin-only project creation
- Admin-only task creation and user allocation
- Member-only visibility for assigned work
- Task status updates with Django-driven overdue rules
- Consistent JSON responses
- Form Request validation
- Seeded credentials and starter data

## Tech choices

- Laravel 13 skeleton
- Sanctum personal access tokens
- MySQL-first `.env.example`
- Small service layer for auth, projects, tasks, and overdue-rule integration

## API routes

- `POST /auth/register`
- `POST /auth/login`
- `GET /auth/me`
- `POST /auth/logout`
- `GET /projects`
- `POST /projects`
- `GET /projects/{project}`
- `GET /projects/{project}/tasks`
- `POST /projects/{project}/tasks`
- `PATCH /tasks/{task}/status`
- `GET /users/assignees`

## Django integration

Laravel calls the Django service for two rule checks:

- `POST /api/rules/evaluate-overdue/`
- `POST /api/rules/validate-transition/`

The Laravel service class is [`app/Services/OverdueRuleService.php`](app/Services/OverdueRuleService.php).

## Seeded credentials

- Admin: `admin@example.com` / `password123`
- Member: `member@example.com` / `password123`

## Local setup

1. Install PHP, Composer, and a MySQL database.
2. Copy `.env.example` to `.env`.
3. Set the database values and Django service URL.
4. Install dependencies:

```bash
composer install
```

5. Generate the app key and run migrations:

```bash
php artisan key:generate
php artisan migrate --seed
```

6. Start the API:

```bash
php artisan serve
```

## Scheduled overdue sync

Laravel defines a console command for syncing overdue task statuses:

```bash
php artisan tasks:sync-overdue
```

It is also registered in [`routes/console.php`](routes/console.php) for scheduler use.

## Tests

Feature tests are included for:

- login and registration
- project visibility by role
- overdue-task transition rules

Run them with:

```bash
php artisan test
```
