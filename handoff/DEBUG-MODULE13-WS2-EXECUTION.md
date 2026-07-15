# DEBUG-MODULE13-WS2-EXECUTION.md — Eksekusi WS-2 (RiskRegister Policy + index/export scope)

**Tanggal:** 2026-07-15
**Modul:** 13 (Risk Management)
**Workstream:** WS-2 (cross-site authz — Policy TODO + index/export scope)
**Metode:** systematic-debugging

---

## Root-Cause Evidence
- `RiskRegisterPolicy::view`/`update` (L34-37 / L76-79 old): `QHSSE Officer` branch `// TODO: implement site scope check` → `return true` → celah cross-site (user site A lihat/edit risk site B).
- `RiskRegisterController::index`/`export` (L43 / L490 old): `RiskRegister::query()` TANPA scope → bocor.

## Perubahan
- `app/Policies/Modules/RiskManagement/RiskRegisterPolicy.php`: ganti 2 TODO dengan cek `core.scope.all` → allow; else `employee.site_id === riskRegister.site_id`.
- `app/Http/Controllers/Modules/RiskManagement/RiskRegisterController.php`: `index` + `export` tambah scope block `core.scope.*` (own/department/site) sebelum search/filter.

## Verifikasi (fresh, real execution)
```
php artisan test tests/Feature/Modules/RiskManagement/RiskRegisterTest.php
  → 24 passed / 108 assertions (+3 test WS-2 baru)
php -l (2 files) → No syntax errors
```

## Tests Baru (RiskRegisterTest)
- `QHSSE Officer in site A cannot view risk register of site B (WS-2 policy)`
- `QHSSE Officer can view risk register of own site (WS-2 policy sanity)`
- `Super Admin with scope.all sees all risk registers (WS-2 scope.all)`

## Catatan
Inertia prop index = `items` (bukan `riskRegisters`). Test disesuaikan.

## Status
✅ **WS-2 SELESAI & TERVERIFIKASI.** (WS-1/3/4/5 Modul 13 belum dikerjakan — hanya WS-2 sesuai instruksi.)
