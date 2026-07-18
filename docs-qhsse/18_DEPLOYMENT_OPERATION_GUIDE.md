# Deployment & Operations Guide

## Stack & Layanan
- **Framework**: Laravel 12 (PHP 8.3)
- **Frontend**: Inertia.js + React + TypeScript
- **Styling**: Tailwind CSS (custom theme: primary #2563eb, navbar #fdb913)
- **Auth**: Laravel session auth; Spatie laravel-permission
- **Database**: PostgreSQL (prod) / SQLite (local tests, in-memory)
- **Queue**: systemd qhsse-queue.service (no supervisor)
- **Web server**: php8.3-fpm.service
- **Build**: Vite (npm run build -> public/build)
- **Architecture**: Modular monolith: app/Core (platform) + app/Modules/{Module} + app/Models/Modules/{Module}

## Server (ubuntu-5)
- Host: `18.192.98.211`, deploy dir: `/var/www/qhsse-app`, user `ubuntu`.
- php-fpm: `php8.3-fpm.service`; queue: `qhsse-queue.service` (systemd, BUKAN supervisor).
- DB: PostgreSQL. Local test: SQLite in-memory.

## Deploy Steps (wajib urutan)
1. `git push` (lokal) SEBELUM server pull.
2. SSH server: `git pull origin develop`
3. `composer install --no-dev` (jika ada perubahan PHP).
4. `npm ci && npm run build`.
5. `php artisan migrate --force` (SELALU, ada migrasi baru).
6. `php artisan optimize:clear` (+ config/route/cache clear).
7. Restart: `sudo systemctl restart php8.3-fpm` & `sudo systemctl restart qhsse-queue`.
8. Verifikasi: `curl -s -o /dev/null -w '%{http_code}' http://localhost/dashboard` (302 = sehat).

## Rollback
- `git revert` + redeploy. Migrasi reversible bila memungkinkan.
