# DEBUG-MODULE16-WS3-EXECUTION.md — Eksekusi WS-3: Expiry Command + Schedule

**Tanggal:** 2026-07-15
**Modul:** 16 (Contractor Management)
**Workstream:** WS-3 (Priority 🔴 — command `CheckExpiringPrequalification` + schedule HILANG)
**Metode:** systematic-debugging (Iron Law: root-cause evidence before fix)

---

## Root-Cause Evidence (sebelum fix)

| Temuan | Bukti |
|--------|-------|
| **Command `CheckExpiringPrequalification` TIDAK ADA** | `find app/Console -iname "*ontractor*"` / `"*prequalif*"` → empty |
| **Schedule contractor TIDAK ada di `routes/console.php`** | `grep contractor / routes/console.php` → exit 1 (hanya documents + assets scheduled) |
| Pattern serupa ada | `app/Console/Commands/CheckDocumentExpiry.php` (notif `document.expiry_reminder` pakai `CoreNotification::create`) |
| Model `Contractor` TIDAK punya scope expiring (cuma `contract_end_date`) | grep `expiringSoon`/`prequalif` → hanya fillable + casts |

**Kesimpulan:** WS-3 butuh command baru + schedule. Logic per WORKFLOW.md §3: notify `contractor.expiring_soon` ke QHSSE Mgr/Off + creator untuk `is_prequalified=true` AND `prequalified_until` dalam 30 hari ke depan (belum expired).

---

## Perubahan (delta)

### 1. Command (WS-3a)
- **BARU** `app/Console/Commands/CheckExpiringPrequalification.php`
  - signature: `contractor:check-prequalification-expiry`
  - logic: query `Contractor::where('is_prequalified', true)->whereNotNull('prequalified_until')->where(prequalified_until > today)->where(prequalified_until <= today+30)`
  - recipients: `User::role(['QHSSE Manager','QHSSE Officer'])->where('is_active', true)` + creator (`created_by`) dedup
  - notif `contractor.expiring_soon` via `NotificationService::notify` (named params, idempotencyKey = hash contractor+until)
  - `moduleName: 'contractor'`, `referenceId: contractor->id`, `actionUrl: route('contractors.show', ...)`

### 2. Schedule (WS-3b)
- `routes/console.php`: +`Schedule::command('contractor:check-prequalification-expiry')->dailyAt('08:00')->withoutOverlapping();`
  (sama jam dengan `documents:check-expiry` per WORKFLOW.md §3)

### 3. Tests (WS-3c)
- **BARU** `tests/Feature/Modules/ContractorPrequalificationExpiryTest.php` — 3 tests / 11 assertions:
  - no notify kalau tidak ada expiring (far future / not prequalified) ✓
  - notify creator + QHSSE team dalam 30 hari ✓
  - already-expired (subDays 5) → NOT treated as expiring-soon ✓
- **Pattern penting:** NotificationService pakai `CoreNotification::create` (DB), BUKAN Laravel Notification facade. Jadi assert via `CoreNotification::where('type','contractor.expiring_soon')` — BUKAN `Notification::fake()`/`assertSentTo`. (Lesson dari DocumentControlTest.)

---

## Verifikasi (fresh, real execution)

```
php -l CheckExpiringPrequalification.php + routes/console.php → No syntax errors
php artisan test --filter ContractorPrequalificationExpiryTest → 3 passed / 11 assertions
php artisan test --filter ContractorPrequalification           → 10 passed / 31 assertions (WS-1 + WS-3)
npm run build                                                → ✓ built in 6.38s
```

---

## Status
✅ **WS-3 SELESAI & TERVERIFIKASI.** Command expiry + schedule functional. Compliance reminder (prequalification < 30 hari) akan terkirim tiap 08:00 ke QHSSE team + creator.

## Sisa WS Modul 16
- WS-2 notif registered (sebagian: evaluated/prequalified sudah di WS-1; expiring_soon di WS-3)
- WS-4 scope `core.scope.*` (index/export bocor)
- WS-5 audit store/update + transition guard (blacklisted→active Admin only)
- WS-6 destroy authorize
- WS-7/WS-8 tests CRUD + frontend
