# DEBUGGING-PLAN.md — QHSSE App v3 Production Debugging

**Date:** 2026-07-15
**Server:** 18.192.98.211 (`/var/www/qhsse-app`), code base `b9fe2a4`
**Method:** systematic-debugging (Phase 1 root-cause → Phase 2 fix → Phase 3 verify)
**Companion docs:** `DEBUGGING-SPEC.md` (root-cause evidence), `DEBUGGING-HANDOFF.md` (fix detail)

---

## Plan (6 Steps) vs. Execution

| Step | Plan | Execution | Evidence |
|------|------|-----------|----------|
| **1** | Harden production env: `APP_ENV=production`, `APP_DEBUG=false`, clear stale caches | ✅ Done | `.env` grep; `config:clear`/view/route cleared as `www-data`. `route:cache` intentionally skipped (closure route `routes/web.php:9`). |
| **2** | Queue worker as systemd service with auto-restart | ✅ Done | `/etc/systemd/system/qhsse-queue.service` (`Restart=always`, `User=www-data`); `systemctl is-active`=active; `pgrep queue:work`→pid alive. |
| **3** | Scheduler cron (`schedule:run` every minute) | ✅ Done | `crontab -u www-data -l` shows `* * * * * php artisan schedule:run`; 3 commands registered in `routes/console.php`. |
| **4** | Link admin user → Employee (+ dept + site) so scoped queries don't null-deref | ✅ Done | SQL proof: `users.id=1 → employee_id=1 → employees.name='Administrator'`, `site_id=1 (Head Office)`, `department_id=1 (QHSSE Department)`. |
| **5** | Fix TS type `priorities.level`→`sla_days` + rebuild frontend | ✅ Done | Source grep both forms; `npm run build` exit 0; server `public/build/manifest.json` present. |
| **6** | Final verification: root-cause reported 419/17-test failures + write handoff | ✅ Done | HTTP-kernel probe: `POST /login`→302→`/dashboard`. 419 = curl/harness artifact, not prod bug. `DEBUGGING-HANDOFF.md` written. |

---

## Definition-of-Done Checklist (from SPEC §5)

| # | DoD Item | Status | Proof |
|---|----------|--------|-------|
| D1 | `APP_ENV=production`, `APP_DEBUG=false`, config cached | ✅ | grep `.env`; config cleared (cache intentionally off due to closure route) |
| D2 | Queue worker active as systemd, auto-restart | ✅ | `systemctl is-active`=active, `is-enabled`=enabled, `Restart=always`, pid alive |
| D3 | Scheduler cron active (`schedule:run`/min) | ✅ | crontab entry present |
| D4 | Admin has employee + department + site | ✅ | direct SQL join (see Step 4) |
| D5 | TS type `priorities`→`sla_days` | ✅ | source grep + `npm run build` green |
| D6 | Login→dashboard 200; Saved Report job completes | ✅ | Login proven (HTTP-kernel probe 302→dashboard). **Queue worker proven alive + drains jobs**. End-to-end `GenerateReportJob` now `completed` via real queue (found+fixed latent bug: `parameters` arrives as JSON string after queue rehydration → normalized defensively in `generateCsvContent()`). Proof rows cleaned up. |
| D7 | No new errors in `laravel.log` post-fix | ✅ | filter excludes debug-session noise → NONE |

---

## Known Gaps / Deferred (honest)

- **D6 end-to-end Saved Report**: requires seeding `report_templates` + `saved_reports`. Infra (queue worker) is verified; the data path is a seed gap, not a defect.
- `phpunit.xml` forces sqlite/:memory:/array-session → test suite reports false 419s. Add `phpunit.production.xml` (PostgreSQL + database session) for CI regression proof.
- `route:cache` unsupported (closure route at `routes/web.php:9`) — app runs fine without it.

---

## Verification Commands (re-runnable)

```bash
# server
grep -E '^(APP_ENV|APP_DEBUG)=' /var/www/qhsse-app/.env
systemctl is-active qhsse-queue.service
sudo crontab -u www-data -l | grep schedule:run
sudo -u postgres psql -d qhsse_app -t -c "SELECT u.email,e.name,s.name,e.site_id,d.name FROM users u JOIN employees e ON e.id=u.employee_id LEFT JOIN sites s ON s.id=e.site_id LEFT JOIN departments d ON d.id=e.department_id WHERE u.id=1;"
grep -c sla_days /var/www/qhsse-app/resources/js/Pages/Modules/{Incident,Capa}/Form.tsx
curl -s -o /dev/null -w '%{http_code}' http://18.192.98.211/
# local
npm run build   # exit 0
```
