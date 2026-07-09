#!/usr/bin/env bash
# =============================================================================
# QHSSE App — Docker Entrypoint
# Dijalankan setiap container start. Handles: key generate, migrate, seed.
# =============================================================================
set -euo pipefail

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  QHSSE App — Container Startup"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

cd /var/www/html

# ── 1. APP_KEY ──────────────────────────────────────────────────────────────
if [ -z "${APP_KEY:-}" ] || [ "${APP_KEY}" = "base64:REPLACEME" ]; then
    echo "[entrypoint] Generating APP_KEY..."
    php artisan key:generate --force
fi

# ── 2. Storage link ─────────────────────────────────────────────────────────
if [ ! -L public/storage ]; then
    echo "[entrypoint] Creating storage symlink..."
    php artisan storage:link --force 2>/dev/null || true
fi

# ── 3. Storage permissions ───────────────────────────────────────────────────
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# ── 4. Migrate ───────────────────────────────────────────────────────────────
echo "[entrypoint] Running migrations..."
php artisan migrate --force

# ── 5. Seed (hanya jika DB kosong — cek tabel users) ────────────────────────
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | tail -1 | tr -d '[:space:]' || echo "0")
if [ "${USER_COUNT}" = "0" ]; then
    echo "[entrypoint] Seeding database (first run)..."
    php artisan db:seed --force
else
    echo "[entrypoint] Database already seeded (${USER_COUNT} users), skipping."
fi

# ── 6. Cache production configs ─────────────────────────────────────────────
if [ "${APP_ENV:-local}" = "production" ]; then
    echo "[entrypoint] Caching config/routes/views for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

echo "[entrypoint] Startup complete. Starting: $*"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

exec "$@"
