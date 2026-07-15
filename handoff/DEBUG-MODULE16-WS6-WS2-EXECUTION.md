# DEBUG-MODULE16-WS6-WS2-EXECUTION.md — Eksekusi WS-6 (destroy authorize) + WS-2 (notif registered)

**Tanggal:** 2026-07-15
**Modul:** 16 (Contractor Management)
**Workstream:** WS-6 (destroy authorize + audit 🟡) + WS-2 (notif registered 🔴 residual)
**Metode:** systematic-debugging (Iron Law: root-cause evidence before fix)

---

## Root-Cause Evidence (sebelum fix)

| Temuan | Bukti |
|--------|-------|
| **WS-6:** `destroy()` TIDAK `authorize('delete')` | L456 langsung `activityService->log` + `delete()` — bypass policy |
| **WS-6:** `destroy()` TIDAK audit delete | tidak panggil `auditService->deleted` |
| **WS-2:** `store()` TIDAK notif registered | hanya audit + activity; `NotificationService` sudah di-inject tapi tidak dipakai untuk registrasi |
| Policy `delete` SUDAH ada | `ContractorPolicy::delete()` L35 cek `can('contractor.management.delete')` |

---

## Perubahan (delta)

### WS-6 — Destroy authorize + audit
- `destroy()`: + `$this->authorize('delete', $contractor)` + `auditService->deleted($contractor, request()->user(), 'contractor', $id)`.
  - Catatan: `destroy(Contractor $contractor)` tidak punya `$request` param → pakai `request()->user()` (bukan `$request->user()`).

### WS-2 — Notif registered
- `store()`: setelah audit, loop `User::role(['QHSSE Manager','QHSSE Officer'])->where('is_active', true)` → `notificationService->notify(type:'contractor.registered', ...)` ke masing-masing (actionUrl ke show).

### Tests
- **BARU** `tests/Feature/Modules/ContractorDestroyNotifyTest.php` — 3 tests / 7 assertions:
  - WS-6: delete diblokir user tanpa `contractor.management.delete` (403); delete berhasil + audit `deleted` untuk authorized.
  - WS-2: store → `CoreNotification` type `contractor.registered` ada.

---

## Verifikasi (fresh, real execution)
```
php -l (3 files)                                      → No syntax errors
php artisan test --filter Contractor                  → 23 passed / 86 assertions (WS-1+3+4+5+6+2)
npm run build                                         → ✓ built in 6.05s
```

## Status
✅ **WS-6 SELESAI & TERVERIFIKASI** (destroy authorize + audit).
✅ **WS-2 SELESAI & TERVERIFIKASI** (notif registered; evaluated/prequalified di WS-1, expiring_soon di WS-3).

## Sisa WS Modul 16
- WS-7/WS-8 tests CRUD + frontend (sudah sebagian covered oleh WS-1/3/4/5/6 tests).
