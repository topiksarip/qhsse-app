# DEBUG-MODULE2-WS1-EXECUTION.md — Eksekusi WS-1 (CAPA 403 root cause)

**Tanggal:** 2026-07-15
**Modul:** 2 (CAPA / Action Tracking)
**Workstream:** WS-1 (CapaAccess root cause 403)
**Metode:** systematic-debugging

---

## Root-Cause Evidence
- `CapaAccess::canAccess()` (L58-62 old): `$employee = $user->employee()->where('is_active',true)->first()` → null → `return false` (403). Admin test TANPA employee → 403.
- `getSiteIds`/`getDepartmentIds` (L78/L99 old): hardcode `'System Admin'` → seeder pakai `'Super Admin'`/`'Admin'` → bypass gagal.
- Kontras `IncidentAccess` (permission-based `core.scope.*`, tidak wajib employee).

## Perubahan
- **REWRITE** `app/Modules/Capa/CapaAccess.php`:
  - `scope()`: `core.scope.all` → return all; else OR-where `assigned_to`/`assigned_by` (own), `department_id`, `site_id` via `employee`.
  - `canAccess()`: `core.scope.all` → true (no employee required); else check site/department/own match.
  - HAPUS hardcode role name; pakai `core.scope.*` seperti `IncidentAccess`.

## Verifikasi (fresh, real execution)
```
php artisan test tests/Feature/Modules/Capa/CapaActionTest.php
  → 21 passed / 49 assertions (6 failure lama PASS + 2 test regresi baru)
php -l app/Modules/Capa/CapaAccess.php → No syntax errors
```

## Tests Baru (CapaActionTest)
- `admin WITHOUT employee record can start CAPA (WS-1 root cause 403 fixed)`
- `QHSSE Officer in different site cannot start cross-site CAPA (WS-1 scope)`

## Status
✅ **WS-1 SELESAI & TERVERIFIKASI.** (Catatan: WS-2/3/4/5/6/7/8 Modul 2 belum dikerjakan — hanya WS-1 sesuai instruksi.)
