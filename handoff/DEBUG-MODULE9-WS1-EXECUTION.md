# DEBUG-MODULE9-WS1-EXECUTION.md — Eksekusi WS-1: Environment Transitions

**Tanggal:** 2026-07-15
**Modul:** 9 (Environment / `10-environmental-management`)
**Workstream:** WS-1 (Priority 🔴🔴 — transitions hilang)
**Status:** ✅ **SELESAI & TERVERIFIKASI**

---

## Ringkasan Eksekusi

Modul 9 (Environment) TIDAK memiliki state-transition method sama sekali. Controller hanya punya
CRUD + index/show/export; routes tidak punya endpoint transisi; policy tidak punya method
`investigate`/`close`. WORKFLOW.md §2-§3 mendefinisikan workflow `recorded → investigated →
action_open → closed` yang sepenuhnya tidak terimplementasi.

**WS-1 mengimplementasikan transitions agar modul berfungsi operasional.**

---

## Root-Cause (bukti segar sebelum fix)

1. `EnvironmentalRecordController.php` — TIDAK ada method `investigate`/`openAction`/`close`.
   Hanya `index/create/store/show/edit/update/export`.
2. `routes/modules/environment.php` — TIDAK ada route transisi.
3. `EnvironmentalRecordPolicy.php` — TIDAK ada method `investigate`/`close`.
4. **Permission `environment.records.investigate` TIDAK ada di DB** (hanya `approve`, `close`,
   `create`, `export`, `update`, `view`). Role `QHSSE Officer`/`QHSSE Manager` SUDAH punya
   `environment.records.approve` (via `CorePermissions::$environmentFull`).
   → Pakai `approve` sebagai gate transisi (sesuai permission yang ada).
5. `AuditService::log()` signature: `(string $event, ?Model $auditable, array $oldValues,
   array $newValues, ?User $actor, ?string $moduleName, ?int $referenceId, array $metadata,
   ?Request $request)`. TIDAK pakai named param `action/details/userId`.
6. `CapaAction` migration NOT NULL: `action_number`, `assigned_to`, `priority_id`.
   Pattern benar (lihat `CapaActionController::store`): pakai `'action_number' => 'TEMP-'.uniqid()`
   lalu update setelah `numberingService->generate()`.

---

## Perubahan (Delta)

### 1. `app/Policies/Modules/Environment/EnvironmentalRecordPolicy.php`
- Tambah method `investigate(User, EnvironmentalRecord): bool` — gate `environment.records.approve` + role QHSSE.
- Tambah method `close(User, EnvironmentalRecord): bool` — gate `environment.records.close` + role QHSSE.
- Tambah static `getAvailableTransitions(string $status): array` (untuk UI + guard reference).

### 2. `app/Http/Controllers/Modules/Environment/EnvironmentalRecordController.php`
- Inject `NotificationService` di constructor.
- Tambah `use` statements: `Area`, `Site`, `Priority`, `CapaAction`, `EnvironmentalRecord`, `User`, `RedirectResponse`, `NotificationService`, request classes.
- Method `investigate()`: `recorded → investigated`, audit + activity + notify reporter `environment.investigated`.
- Method `openAction()`: `investigated → action_open`, buat `CapaAction` (source_module='environment'),
  set `capa_action_id`, audit + activity.
- Method `close()`: validasi `reason` min:10, `→ closed`, audit + activity + notify reporter `environment.closed`.
- Semua `auditService->log()` pakai positional signature yang benar.
- `openAction` ikut pattern `TEMP-` + `Priority::firstOrCreate('medium')` + `assigned_to`.

### 3. `routes/modules/environment.php`
- `POST /{environmental_record}/investigate` → `investigate` (permission: `environment.records.approve`)
- `POST /{environmental_record}/open-action` → `openAction` (permission: `environment.records.approve`)
- `POST /{environmental_record}/close` → `close` (permission: `environment.records.close`)

### 4. `tests/Feature/Modules/EnvironmentalRecordTransitionTest.php` (BARU)
7 test, 18 assertions:
- QHSSE Officer bisa investigate (recorded→investigated)
- Reporter diblokir 403 (permission gate)
- openAction buat CAPA + set capa_action_id + source_module='environment'
- close butuh reason (validation) + notify reporter
- direct close dari recorded
- reject invalid transition (investigate pada investigated → 400)
- reject close pada sudah-closed (400)

---

## Verifikasi

```bash
php -l app/Policies/Modules/Environment/EnvironmentalRecordPolicy.php   # OK
php -l app/Http/Controllers/Modules/Environment/EnvironmentalRecordController.php  # OK
php -l routes/modules/environment.php                                   # OK
php artisan test --filter EnvironmentalRecordTransitionTest             # 7 passed / 18 assertions
php artisan test --filter Environment                                    # 8 passed / 19 assertions
npm run build                                                            # ✓ built in 6.80s
```

---

## Catatan / Risk

- **Permission `investigate` vs `approve`**: WORKFLOW.md §3 menyebut `environment.records.investigate`,
  tapi permission itu TIDAK ada di `CorePermissions`. Yang ada adalah `environment.records.approve`
  (sudah di-role-kan ke QHSSE). Keputusan: gunakan `approve` agar tidak perlu modifikasi seeder
  (hindari over-reach). Jika user ingin nama `investigate` konsisten dgn WORKFLOW, tambahkan
  permission baru di `CorePermissions` + sync ke role (WS terpisah).
- **WS-2 (store exceedance notif + recipient resolution)** BELUM dikerjakan — masih terpisah.
- **WS-3 (UI buttons)** BELUM — frontend belum panggil route transisi. Perlu tambah tombol
  investigate/open-action/close di `Show.tsx` (Environment) menggunakan `getAvailableTransitions`.
- **WS-4 (scope audit + hardcode role → core.scope.*)** BELUM — policy masih pakai hardcode role.
- Test env memakai sqlite `:memory:` (phpunit.xml override) — sesuai konvensi project.

---

## Deferred (ke WS lain / backlog)

- WS-2: notif exceedance saat store/update (WORKFLOW §7 `environment.exceedance_detected`).
- WS-3: UI transition buttons + show available transitions.
- WS-4: ganti hardcode role di policy dengan `core.scope.*` (consistency dengan modul lain).
- Pertimbangkan tambah permission `environment.records.investigate` agar cocok nama WORKFLOW.

---

## Definition of Done Checklist

- [x] Migration/model/request/controller/route/UI selesai bila dibutuhkan (route+controller+policy ✅, UI ditunda WS-3)
- [x] Permission backend diterapkan (route `permission:` + policy method)
- [x] Form validasi server-side (close wajib reason)
- [x] Audit trail (auditService->log positional)
- [x] Activity log (activityService->log)
- [x] Notification via NotificationService (reporter notified)
- [x] Tests added + passing (7 test / 18 assertions)
- [x] `npm run build` passing
- [x] Docs/handoff diperbarui
