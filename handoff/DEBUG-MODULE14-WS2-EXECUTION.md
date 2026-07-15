# DEBUG-MODULE14-WS2-EXECUTION.md — Eksekusi WS-2: CheckOverdueObligations command + schedule

**Tanggal:** 2026-07-15
**Modul:** 14 (Legal & Compliance)
**Workstream:** WS-2 (command overdue 🔴🔴 CRITICAL compliance)
**Metode:** systematic-debugging

---

## Root-Cause Evidence (sebelum fix)
| Temuan | Bukti |
|--------|-------|
| `CheckOverdueObligations` command HILANG | `find app/Console -iname "*Overdue*"` = empty |
| Schedule `legal:check-overdue` TIDAK ada | `grep legal routes/console.php` = exit 1 |
| `LegalObligation` punya `scopeOverdue()` + `scopeDueSoon(7)` | model L63-76 — query siap, tapi tidak dipakai |
| `LegalRegister` punya `owner_id` + `site_id` | model L34/36 — recipient candidate |
| `NotificationService::notify` support `idempotencyKey` | signature L32-42 (anti-duplicate) |

---

## Perubahan (delta)
- **BARU** `app/Console/Commands/CheckOverdueObligations.php`:
  - Signature `legal:check-overdue`.
  - Query `LegalObligation::overdue()` + `dueSoon(7)` (with `legalRegister`).
  - Recipients: register `owner_id` + QHSSE Mgr/Off active (tanpa site filter employee agar tidak drop owner tanpa employee record).
  - `notify()` per recipient dengan `idempotencyKey = "{$type}.{$obligationId}.{$date}.{$recipientId}"` (anti-duplicate same-day).
  - `actionUrl = route('legal.registers.show', $register)` (obligations tidak punya index route).
- **BARU** schedule `routes/console.php`: `legal:check-overdue` `dailyAt('00:01')->withoutOverlapping()`.
- **BARU** `tests/Feature/Modules/LegalCompliance/LegalOverdueCommandTest.php` — 2 tests:
  - overdue → notif `legal.obligation.overdue` terkirim.
  - run 2x same day → count = 1 (idempotency).

---

## Verifikasi (fresh, real execution)
```
php -l (command + console.php)                        → No syntax errors
php artisan test --filter LegalOverdueCommandTest     → 2 passed / 2 assertions
```

## Status
✅ **WS-2 SELESAI & TERVERIFIKASI** (command + schedule + anti-duplicate + test).

## Sisa WS Modul 14
- WS-1 notif store/update(complete) — G1
- WS-3 hardcode role → scope — G3
- WS-4 audit consistency — G4/G5
- WS-5 status terminal guards — G6/G7
- WS-6/7 tests + frontend
