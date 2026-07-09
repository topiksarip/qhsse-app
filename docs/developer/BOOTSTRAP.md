# Bootstrap Notes

## Project Path

`/home/ubuntu/qhsse-app-v3`

## Stack Detected

- Laravel application pinned with `laravel/laravel:^12.0` skeleton.
- Inertia React + TypeScript installed through Laravel Breeze.
- Tailwind CSS and Vite are installed by Breeze.
- PostgreSQL/Redis are the target services for Phase 0, but local `.env` still needs final database values before migrations.

## Important Bootstrap Decision

The first attempt without a version constraint installed Laravel 13 and failed on a Vite/plugin peer dependency mismatch. This project was recreated with Laravel 12 as required by the QHSSE readiness docs.

Breeze currently pins `@types/node` too low for Vite 7 on this environment, so `@types/node` was raised to `^22.12.0` to satisfy Vite's documented peer requirement.

## Commands Verified

```bash
php artisan --version
npm run build
```

## Next Phase 0 Work

Continue with Task 0.3 Authentication only after reviewing:

- `docs-qhsse/27_PHASE_0_BUILD_PLAN.md`
- `tasks/todo.md`
- `handoff/PHASE-00-bootstrap-task-0.1-0.2-HANDOFF.md`
