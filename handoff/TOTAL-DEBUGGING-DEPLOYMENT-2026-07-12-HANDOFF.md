# Total Debugging & Production Deployment Handoff

**Date:** 2026-07-12  
**Target:** `18.192.98.211:8000`  
**Branch:** `develop`

## Scope

End-to-end debugging and corrective deployment of the QHSSE Laravel/Inertia application on the production VPS.

## Critical Findings and Fixes

1. **Wrong branch deployed**
   - VPS initially ran `main` at `982bad6`, while the current application lived on `develop`.
   - Production was moved to `develop` after database and source backups.

2. **PostgreSQL migration ordering**
   - Equal migration timestamps caused foreign keys to execute before parent tables.
   - Migration ordering in `develop` was already corrected.
   - Existing production migration metadata was mapped transactionally to the corrected names before non-destructive migration.

3. **Incomplete production schema**
   - Only core tables existed on the initial deployment.
   - All pending business migrations were applied with `php artisan migrate --force`.
   - `migrate:fresh` was not used during corrective deployment.

4. **Duplicate named routes**
   - PUT and PATCH routes had identical names as separate route objects, blocking `route:cache`.
   - They now use one `Route::match(['put', 'patch'], ...)` route.

5. **Production seeder depended on dev Faker**
   - `DatabaseSeeder` used `User::factory()` although production Composer install excluded dev packages.
   - Baseline user creation is now factory-independent.

6. **Seeder ordering/schema mismatch**
   - The Super Admin user is now created before business demo seeders.
   - `CampaignSeeder` no longer writes non-existent audit columns to master organization tables.

7. **Public registration**
   - Public self-registration is disabled by default through `ALLOW_PUBLIC_REGISTRATION=false`.
   - GET and POST `/register` return 404 when disabled.
   - Landing-page registration visibility follows the same config.

8. **Missing queue and scheduler runtime**
   - Added `qhsse-queue.service` with automatic restart, 192 MB worker memory limit, and bounded job lifecycle.
   - Added Laravel scheduler cron and enabled the OS cron service.

9. **Production upload/runtime limits**
   - PHP-FPM memory limit: 256 MB.
   - PHP-FPM execution time: 120 seconds.
   - PHP upload limit: 25 MB.
   - PHP post limit: 30 MB.
   - Nginx `client_max_body_size`: 25 MB.

## Backups

- Database: `/var/backups/qhsse/qhsse_production-20260712-165539.dump`
- Source/config snapshot directory recorded in:
  `/var/backups/qhsse/latest-deploy-backup.txt`
- PHP-FPM config backup:
  `/var/backups/qhsse/php-fpm.ini.before-tuning`
- Nginx config backup:
  `/var/backups/qhsse/nginx-qhsse.before-tuning`

## Verification

- Production build passed.
- Route cache passed.
- Full backend test suite passed before final security hardening: 342 tests / 1,217 assertions.
- Authentication smoke test passed: login returned 302 to `/dashboard`.
- 29 authenticated dashboard/core/business pages returned HTTP 200.
- No pending migrations.
- No failed queue jobs.
- Nginx, PHP-FPM, PostgreSQL, Redis, Cloudflare Tunnel, and queue worker active.
- Cron service active and scheduler registered.

## Access

- URL: `http://18.192.98.211:8000`
- Baseline account: `test@example.com`
- Role: `Super Admin`

> Change the baseline password immediately after operational acceptance.

## Operational Notes

- Production source should always deploy from `develop` until the release workflow promotes it to `main`.
- Do not run `migrate:fresh` on this production database.
- Do not run non-idempotent demo seeders repeatedly on production.
- Rotate the Cloudflare tunnel token because it was provided in chat and command history.
- Rotate repository credentials/PAT if previously embedded in a remote URL.
