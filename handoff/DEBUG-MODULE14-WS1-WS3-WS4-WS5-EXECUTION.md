# DEBUG-MODULE14-WS1-WS3-WS4-WS5-EXECUTION.md — Eksekusi WS-1 (notif) + WS-3 (scope) + WS-4 (audit) + WS-5 (guards)

**Tanggal:** 2026-07-15
**Modul:** 14 (Legal & Compliance)
**Workstream:** WS-1 (notif store/update/complete) + WS-3 (hardcode role → scope) + WS-4 (audit) + WS-5 (status terminal guards)
**Metode:** systematic-debugging (Iron Law: root-cause evidence before fix)

---

## Root-Cause Evidence (sebelum fix)
| WS | Temuan | Bukti |
|----|--------|-------|
| WS-1 | `store` register TIDAK notif | L127 `activityService->log` saja |
| WS-1 | `update` non_compliant TIDAK notif | L200 `// TODO: Send notification` |
| WS-1 | `LegalObligationController::complete` TIDAK notif owner | L106 `activity()` saja |
| WS-3 | `index`/`export` hardcode `hasAnyRole([...5 roles])` | L73, L264 |
| WS-4 | `store`/`obligation.*` pakai `activity()` bukan `AuditService` | L36-44, 67-75, 106-116, 130-138 |
| WS-5 | `destroy` TIDAK guard `status==='inactive'` | L217 |
| WS-5 | `update` TIDAK guard `status!=='active'` | L177 |

---

## Perubahan (delta)
### WS-3 — Scope via LegalAccess
- **BARU** `app/Modules/LegalCompliance/LegalAccess.php` (mirror AssetAccess): `scope()` + `canView()` pakai `core.scope.*` (site→`employee.site_id`, department→`employee.department_id`, own/fallback→`owner_id`).
- `LegalRegisterController::index` + `export`: hardcode role → `$this->legalAccess->scope($query, $user)`.

### WS-1 — Notifications
- `LegalRegisterController`: inject `NotificationService`; `store` → notif `legal.register.created` ke QHSSE Mgr/Off; `update` non_compliant → notif `legal.compliance.changed`.
- `LegalObligationController`: inject `NotificationService`; `store` → notif `legal.obligation.created` ke owner; `complete` → notif `legal.obligation.completed` ke owner.

### WS-4 — Audit
- `LegalRegisterController`: `store`→`auditService->created`; `update`→`auditService->updated`; `destroy`→`auditService->deleted`.
- `LegalObligationController`: `store`/`update`/`complete`/`destroy` → `auditService->created/updated/deleted` (ganti `activity()` helper).

### WS-5 — Status guards
- `destroy` → `abort_if($register->status === 'inactive', 403)`.
- `update` → `abort_if($register->status !== 'active', 403)`.

### Tests
- **BARU** `tests/Feature/Modules/LegalCompliance/LegalComplianceActionTest.php` — 5 tests / 20 assertions (store audit+notif, non_compliant notif, destroy guard, update guard, scope index).

---

## Verifikasi (fresh, real execution)
```
php -l (3 files)                                  → No syntax errors
php artisan test --filter Legal                   → 17 passed / 79 assertions (WS-1/2/3/4/5)
npm run build                                     → ✓ built in 6.20s
```

## Status
✅ **WS-1/WS-3/WS-4/WS-5 SELESAI & TERVERIFIKASI.**
Sisa WS-6/WS-7 (tests + frontend) — sebagian covered oleh LegalComplianceActionTest.
