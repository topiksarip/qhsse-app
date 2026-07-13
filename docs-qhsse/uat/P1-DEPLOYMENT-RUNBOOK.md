# P1 Production Deployment Runbook

**Target:** VPS Ubuntu-5 (18.192.98.211)
**Source Commit:** `f0c1a3680b60a0f6b66d1022902e7dfdeb6e75f6`
**Date:** 2026-07-13
**Executor:** Requires authorized SSH access to production server

## Prerequisites Verified

- [x] Local regression: 403 tests / 1,737 assertions passing
- [x] Frontend build passes
- [x] Docker build passes (app + queue images)
- [x] Fresh migration + seed passes on SQLite
- [x] All changes committed and pushed to `origin/develop`
- [x] Handoff documentation complete
- [ ] **BLOCKER:** Production SSH access available
- [ ] Production database backup completed
- [ ] Production source/config backup completed

## Pre-Deployment Checklist

Execute these checks **before** any modification:

```bash
# 1. Verify SSH access
ssh USER@18.192.98.211 'hostname && id -un'

# Expected: ubuntu-5 or similar hostname, authorized username

# 2. Verify current production state
ssh USER@18.192.98.211 'cd /var/www/qhsse && \
  git branch --show-current && \
  git rev-parse HEAD && \
  git status --short && \
  df -h /var/www/qhsse | tail -1'

# Expected: clean worktree, sufficient disk space (>2GB free recommended)

# 3. Verify services are healthy
ssh USER@18.192.98.211 'systemctl is-active nginx php8.4-fpm postgresql redis-server'

# Expected: all services "active"

# 4. Test database connectivity
ssh USER@18.192.98.211 'cd /var/www/qhsse && php artisan db:show'

# Expected: connection successful, database name displayed
```

## Backup Phase (MANDATORY)

**DO NOT SKIP.** Backups enable rollback if deployment fails.

```bash
# 1. Backup production database
ssh USER@18.192.98.211 'cd /var/www/qhsse && \
  php artisan db:backup --path=/var/backups/qhsse/db-pre-p1-$(date +%Y%m%d-%H%M%S).sql'

# 2. Backup source and config
ssh USER@18.192.98.211 'sudo tar czf /var/backups/qhsse/source-pre-p1-$(date +%Y%m%d-%H%M%S).tar.gz \
  -C /var/www qhsse --exclude=qhsse/storage/logs --exclude=qhsse/node_modules'

# 3. Verify backups exist and are non-zero
ssh USER@18.192.98.211 'ls -lh /var/backups/qhsse/*pre-p1* | tail -2'

# Expected: two files with reasonable sizes (database ~MB, source ~MB)
```

## Deployment Phase

### 1. Pull Latest Code

```bash
ssh USER@18.192.98.211 'cd /var/www/qhsse && \
  git fetch origin && \
  git diff --stat HEAD origin/develop && \
  git log --oneline HEAD..origin/develop'

# Review: confirm commits match expectation (f0c1a36, 0bcceb1, c61830d)

ssh USER@18.192.98.211 'cd /var/www/qhsse && \
  git pull --ff-only origin develop'

# Expected: Fast-forward to f0c1a36
```

### 2. Install Dependencies

```bash
ssh USER@18.192.98.211 'cd /var/www/qhsse && \
  composer install --no-dev --optimize-autoloader --no-interaction'

# Expected: no errors, dependencies up to date
```

### 3. Run Migrations (Non-Destructive)

P1 has **no new migrations**. This step verifies schema is current.

```bash
ssh USER@18.192.98.211 'cd /var/www/qhsse && \
  php artisan migrate:status'

# Expected: all migrations "Ran", no "Pending"
```

### 4. Seed New Permissions

P1 adds these permissions:
- `incident.reports.evidence`
- `quality.complaints.create`
- `quality.complaints.update`
- `quality.complaints.close`
- `quality.complaints.export`

```bash
ssh USER@18.192.98.211 'cd /var/www/qhsse && \
  php artisan db:seed --class=CorePermissionsSeeder'

# Expected: permissions created or already exist, no errors
```

### 5. Build Frontend Assets

```bash
ssh USER@18.192.98.211 'cd /var/www/qhsse && \
  npm ci && \
  npm run build'

# Expected: build completes, manifest.json updated
# Duration: ~2-5 minutes depending on server
```

### 6. Clear and Rebuild Caches

```bash
ssh USER@18.192.98.211 'cd /var/www/qhsse && \
  php artisan optimize:clear && \
  php artisan config:cache && \
  php artisan route:cache && \
  php artisan view:cache && \
  php artisan optimize'

# Expected: all caches rebuilt successfully
```

### 7. Restart Services

```bash
ssh USER@18.192.98.211 'sudo systemctl restart php8.4-fpm && \
  sudo systemctl restart qhsse-queue'

# Expected: services restart without errors
```

### 8. Verify Manifest Updated

```bash
ssh USER@18.192.98.211 'stat -c "%s:%Y" /var/www/qhsse/public/build/manifest.json'

# Compare timestamp with pre-deployment check
# Expected: newer timestamp, possibly different size
```

## Post-Deployment Verification

### 1. Anonymous Smoke Tests

```bash
# Test public routes return expected status
curl -fsS -o /dev/null -w 'LOGIN=%{http_code}\n' http://18.192.98.211:8000/login
curl -fsS -o /dev/null -w 'REGISTER=%{http_code}\n' http://18.192.98.211:8000/register

# Expected: LOGIN=200, REGISTER=404
```

### 2. New Route Accessibility

```bash
# These should now be accessible (redirect to login for unauthenticated)
curl -fsS -w 'ADMIN=%{http_code} URL=%{url_effective}\n' -L http://18.192.98.211:8000/admin
curl -fsS -w 'ROLES=%{http_code} URL=%{url_effective}\n' -L http://18.192.98.211:8000/core/roles

# Expected: both redirect to login (302 or 200 on login page)
```

### 3. Asset Manifest Check

```bash
curl -fsS http://18.192.98.211:8000/login | grep -o '/build/assets/[^"]*' | head -5

# Expected: new asset hashes different from pre-deployment
```

### 4. Service Health

```bash
ssh USER@18.192.98.211 'systemctl is-active nginx php8.4-fpm postgresql redis-server qhsse-queue'

# Expected: all "active"
```

### 5. Application Logs

```bash
ssh USER@18.192.98.211 'tail -50 /var/www/qhsse/storage/logs/laravel.log'

# Review for errors after deployment
# Expected: no critical errors, typical access logs
```

## Authenticated UAT

Execute `docs-qhsse/uat/P1-UAT-CHECKLIST.md` with production credentials.

**Critical paths:**
1. Incident evidence upload/download
2. Incident reject with reason
3. Incident involved persons
4. Incident print report
5. Visitor Log check-in/checkout
6. Customer Complaint creation/close
7. Role-Permission Matrix update
8. Bulk Import (employees CSV)
9. Admin Dashboard KPIs
10. Inactive user session termination

## Rollback Plan

If deployment fails or UAT uncovers critical issues:

### 1. Restore Database

```bash
ssh USER@18.192.98.211 'cd /var/www/qhsse && \
  php artisan db:restore --path=/var/backups/qhsse/db-pre-p1-TIMESTAMP.sql'
```

### 2. Restore Source

```bash
ssh USER@18.192.98.211 'sudo tar xzf /var/backups/qhsse/source-pre-p1-TIMESTAMP.tar.gz -C /var/www'
```

### 3. Rebuild Caches and Restart

```bash
ssh USER@18.192.98.211 'cd /var/www/qhsse && \
  php artisan optimize:clear && \
  php artisan config:cache && \
  php artisan route:cache && \
  php artisan view:cache && \
  sudo systemctl restart php8.4-fpm qhsse-queue'
```

### 4. Verify Rollback

```bash
ssh USER@18.192.98.211 'cd /var/www/qhsse && git rev-parse HEAD'

# Should match pre-deployment commit
```

## Success Criteria

Deployment is complete when:

- [x] All deployment steps executed without errors
- [x] Services restarted successfully
- [x] Manifest timestamp updated
- [x] Anonymous smoke tests pass
- [x] New routes accessible (redirect to login)
- [x] No critical errors in application logs
- [x] All 10 UAT paths verified and documented
- [x] Module Register updated to "Released" status
- [x] Handoff marked as deployment-complete

## Current Status

**BLOCKED:** Awaiting authorized SSH access to `USER@18.192.98.211`.

Once access is available, execute this runbook in sequence. Do not skip backup phase.
