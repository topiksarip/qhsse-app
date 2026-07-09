# AGENTS.md — QHSSE Web Application

## Project

QHSSE Web Application: modular web platform for Quality, Health, Safety, Security, and Environment management.

## Operating Mode

Use YOLO terkontrol:

- Move fast within the active phase.
- Do not implement outside the active phase.
- If scope changes, update Decision Log or Backlog first.
- Always create handoff after each phase/generation.

## Required Docs Before Work

Always check relevant docs:

- `docs-qhsse/23_EXECUTION_PLAN.md`
- `docs-qhsse/24_HANDOFF_PROTOCOL.md`
- `docs-qhsse/26_TECH_STACK_DECISION.md`
- `docs-qhsse/21_BLUEPRINT.md`
- `docs-qhsse/22_FOUNDATION_SUPER_SPEC.md`
- Current module folder under `docs-qhsse/modules/`
- `tasks/plan.md`
- `tasks/todo.md`

## Tech Stack

Default:

- Laravel 12
- Inertia React + TypeScript
- PostgreSQL
- Redis
- Tailwind CSS
- spatie/laravel-permission
- Laravel session auth, Sanctum later if needed
- Modular monolith

## Commands

Commands will be finalized after bootstrap. Target commands:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan test
npm run build
```

## Code Boundaries

Always:

- Follow active phase only.
- Use server-side authorization.
- Validate all user input.
- Keep file storage private.
- Add audit trail for critical changes.
- Update handoff after phase completion.
- Run available tests/build before claiming completion.

Ask/update Decision Log first:

- Adding major dependency.
- Changing database architecture.
- Changing tech stack.
- Introducing workflow builder.
- Adding module outside current phase.
- Changing permission model.

Never:

- Commit secrets or `.env`.
- Implement microservices in early phases.
- Make UI-only permission security.
- Expose private files directly by public path.
- Skip handoff.
- Delete user work or docs without explicit approval.

## Architecture Rules

- Modular monolith, not microservices.
- Core services are reused by all modules.
- Business modules live under `app/Modules`.
- Shared platform concerns live under `app/Core`.
- Use `module_name + reference_id` pattern for files, comments, workflow history, activity logs, and audit trails where appropriate.
- Build vertical slices, not massive horizontal layers.

## Phase Gate

Before starting a phase:

- Read module docs.
- Confirm Definition of Ready.
- Check previous handoff.
- Check backlog for relevant deferred items.

Before ending a phase:

- Run tests/build if available.
- Update changelog.
- Update decision log if needed.
- Create handoff file.
- Record known issues and deferred items.

## Skill Path

Use this skill path when applicable:

```text
interview-me
-> spec-driven-development
-> planning-and-task-breakdown
-> context-engineering
-> source-driven-development
-> incremental-implementation
-> test-driven-development
-> code-review-and-quality
-> documentation-and-adrs
-> shipping-and-launch
```
