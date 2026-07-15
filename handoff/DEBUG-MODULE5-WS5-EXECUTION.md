# DEBUG-MODULE5-WS5-EXECUTION.md — Eksekusi WS-5 (scope transition audit)

**Tanggal:** 2026-07-15
**Modul:** 5 (Audit Management)
**Workstream:** WS-5 (transition scope — cross-site authz)
**Metode:** systematic-debugging

---

## Root-Cause Evidence
- `startAudit`/`generateReport`/`closeAudit` (L257/L278/L301) pakai `canExecute`/`canClose` (permission+status) TAPI TIDAK `ensureVisible` → user lintas site/dept dengan permission bisa eksekusi transition audit orang lain. `ensureVisible` (L492) SUDAH ada, dipakai di `comment`/`uploadEvidence` tapi TIDAK di 3 transition.

## Perubahan
- `app/Http/Controllers/Modules/Audit/AuditController.php`: tambah `$this->ensureVisible($actor, $audit)` di `startAudit`, `generateReport`, `closeAudit` (setelah `canExecute`/`canClose`).

## Verifikasi (fresh, real execution)
```
php artisan test tests/Feature/Modules/Audit/AuditTest.php
  → 30 passed / 158 assertions (+3 test WS-5 baru)
php -l app/Http/Controllers/Modules/Audit/AuditController.php → No syntax errors
```

## Tests Baru (AuditTest)
- `QHSSE Officer cannot start audit of other department (WS-5 scope)`
- `QHSSE Officer can start audit of own department (WS-5 scope sanity)`
- `QHSSE Officer cannot close audit of other department (WS-5 scope)`

## Catatan
`audits` table TIDAK punya `site_id` (hanya `department_id`) — scope via `department.site_id` (sudah benar di `visibleQuery`). Test disesuaikan pakai department cross-site.

## Status
✅ **WS-5 SELESAI & TERVERIFIKASI.** (WS-1/2/3/4/6/7/8 Modul 5 belum dikerjakan — hanya WS-5 sesuai instruksi.)
