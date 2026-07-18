# Tech Stack Decision

- **Framework**: Laravel 12 (PHP 8.3)
- **Frontend**: Inertia.js + React + TypeScript
- **Styling**: Tailwind CSS (custom theme: primary #2563eb, navbar #fdb913)
- **Auth**: Laravel session auth; Spatie laravel-permission
- **Database**: PostgreSQL (prod) / SQLite (local tests, in-memory)
- **Queue**: systemd qhsse-queue.service (no supervisor)
- **Web server**: php8.3-fpm.service
- **Build**: Vite (npm run build -> public/build)
- **Architecture**: Modular monolith: app/Core (platform) + app/Modules/{Module} + app/Models/Modules/{Module}

## Catatan
- Lokal dev pakai SQLite in-memory untuk test (DB-agnostic queries wajib: `LOWER(col) LIKE LOWER(?)` bukan `ILIKE`).
