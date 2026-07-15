# DEBUG-MODULE17-ASSET-PLAN.md — Debug Mendalam Modul 17 (Asset & Equipment Safety)

**Tanggal:** 2026-07-15
**Modul:** `17-asset-equipment-safety` (Phase 17 — Asset + Certificate + Inspection)
**Controller:** `App\Http\Controllers\Modules\Asset\{AssetController, AssetCertificateController, AssetInspectionController}`
**Metode:** systematic-debugging (Iron Law: TIDAK ada fix tanpa root-cause evidence)
**Status:** 🟡 **Sangat matang (scope via AssetAccess, jobs notif certificate ADA, nested routes aman). Gap level "polish": schedule job HILANG, transition asset tidak notif, hardcode role, tidak audit via AuditService.**

---

## 0. Konteks & Bukti Segar

Modul 17 (Asset) adalah **modul paling matang** yang ditemukan sejauh ini:
- `AssetAccess::scope()` dipakai di `index`/`export` (L41/L308) — scope benar via `core.scope.*`.
- 3 controllers, 3 models, nested routes certificate/inspection dengan middleware permission.
- Jobs `CheckAssetCertificates`/`CheckAssetInspections` ADA + SUDAH notif (L63/L49 `$notifications->notify`
  dengan idempotency key) — **berbeda dari Modul 14/16 yang command-nya HILANG**.
- File download aman (L233-246: abort_unless cek file ownership).
- `decommission`/`status` guard benar (L209 authorize decommission, L236 abort_if sama status).
- Tests ADA: `AssetEquipmentSafetyTest`, `AssetMigrationCompatibilityTest`, `TrainingAssetReportingRegressionTest`.

TAPI ada **5 gap** (level polish, bukan non-functional):

| # | Gap | Bukti | WORKFLOW.md mensyaratkan |
|---|-----|------|--------------------------|
| G1 | 🔴 **Jobs TIDAK di-schedule** | `routes/console.php` tidak ada schedule asset (grep empty); command `assets:check-certificates`/`assets:check-inspections` ada tapi tidak jalan otomatis | §5/§6: schedule daily 06:00 / 06:30 |
| G2 | 🟡 **Transition asset TIDAK notif** | `status` L231-250 / `decommission` L252-268 controller TIDAK inject `NotificationService`; tdk notif | §4.3: `asset.decommissioned` notif; §4.1/§4.2: set_inactive/active notif (opsional) |
| G3 | 🔴 **Hardcode role di AssetAccess/AssetNotificationRecipients** | `AssetAccess` L111/113 `'Super Admin','Admin','QHSSE Manager'`; `AssetNotificationRecipients` L18/20/32/47/59 `'QHSSE Manager'/'QHSSE Officer'/'Top Management'` | Antipattern (sama Modul 7) |
| G4 | 🟡 **`update`/`status`/`decommission` TIDAK audit via AuditService** | L218/L240/L258 hanya `activityService->log`; tdk `auditService->updated` | §9: `asset.updated`/`set_*`/`decommissioned` = Audit + Activity |
| G5 | 🟡 **Certificate/Inspection TIDAK audit via AuditService** | `AssetCertificateController` L84/L192 hanya `activityService`; inspection controller sama | §9: `certificate.*`/`inspection.*` = Audit + Activity |

---

## 1. Inventori Target

| Komponen | Path |
|----------|------|
| Controllers | `AssetController`, `AssetCertificateController`, `AssetInspectionController` (`app/Http/Controllers/Modules/Asset/`) |
| Models | `Asset`, `AssetCertificate`, `AssetInspection` (`app/Models/Modules/Asset/`) |
| Access/Recipients | `AssetAccess`, `AssetNotificationRecipients` (`app/Modules/Asset/`) |
| Jobs (Commands) | `app/Console/Commands/CheckAssetCertificates.php` (✅ notif), `CheckAssetInspections.php` (✅ notif) |
| Policies | `AssetPolicy`, `AssetCertificatePolicy`, `AssetInspectionPolicy` (`app/Policies/Modules/Asset/`) |
| Routes | `routes/modules/asset.php` (require L236) — nested, middleware permission ✅ |
| Tests | `tests/Feature/Modules/AssetEquipmentSafetyTest.php`, `AssetMigrationCompatibilityTest.php`, `TrainingAssetReportingRegressionTest.php` |
| Docs | `docs-qhsse/modules/17-asset-equipment-safety/` (✅ lengkap 753 lines) |
| Schedule | `routes/console.php` → **schedule asset HILANG** |

---

## 2. Workstream

### WS-1: Schedule jobs (G1)  🔴🔴 (compliance)
- **Bug:** command ada + notif benar, tapi TIDAK di-schedule → alert certificate/inspection tidak jalan.
- **Fix:** tambah ke `routes/console.php`:
  ```php
  Schedule::command('assets:check-certificates')->dailyAt('06:00')->withoutOverlapping();
  Schedule::command('assets:check-inspections')->dailyAt('06:30')->withoutOverlapping();
  ```
- **Verifikasi:** `php artisan schedule:list` menampilkan 2 command; jalankan manual → notif terkirim.
- **DoD:** schedule terdaftar; test cover (schedule list / manual run).

### WS-2: Notif transition asset (G2)  🟡
- **Fix:** inject `NotificationService` di `AssetController`; notif `asset.decommissioned`
  (decommission) + `asset.set_inactive`/`asset.set_active` (status, opsional). Recipient via
  `AssetNotificationRecipients` (site scope), bukan hardcode.
- **DoD:** notif jalan; test cover.

### WS-3: Hardcode role → `core.scope.*` / permission (G3)  🔴
- **Bug:** `AssetAccess`/`AssetNotificationRecipients` hardcode role name.
- **Fix:** ganti ke permission/role-name via config atau `core.scope.*`; minimal pakai constant
  dari `CorePermissions` agar tidak hardcode string. (Scope sudah benar; ini fragility fix.)
- **DoD:** no hardcode role string; test cross-site scope tetap benar.

### WS-4: Audit via AuditService (G4/G5)  🟡
- **Fix:** `update`/`status`/`decommission` asset + certificate/inspection store/update panggil
  `auditService->created/updated` (selain `activityService`). Sesuai WORKFLOW.md §9.
- **DoD:** audit_logs konsisten; test cover.

### WS-5: Tests (G1-G5)  🟡
- **Bug:** tests ada tapi mungkin belum cover schedule + notif transition + audit.
- **Fix:** tambah test schedule (WS-1), notif decommission (WS-2), audit (WS-4).
- **DoD:** coverage lengkap; suite PASS.

### WS-6: Frontend  🟡
- **Debug:** Show render certificate status badge + inspection result; `npm run build` green.
- **DoD:** UI benar; `npm run build` green.

---

## 3. Cross-Link ke Plan Lain

- **DEBUG-MODULE14-LEGAL-PLAN.md G2 (command HILANG):** Asset G1 BEDA — command ADA tapi TIDAK
  di-schedule (masih compliance gap, tapi lebih ringan: tinggal tambah schedule).
- **DEBUG-MODULE4-INSPECTION-PLAN.md G2/G3 + Modul 9/11/13/14/15 G1:** notif silent — Asset G2
  (hanya transition asset, bukan certificate: certificate SUDAH notif via job).
- **DEBUG-MODULE7-TRAINING-PLAN.md G1:** hardcode role — Asset G3 sama (di `AssetAccess`/`AssetNotificationRecipients`).
- **DEBUG-MODULE14-LEGAL-PLAN.md G4/G5 + DEBUG-MODULE15-EMERGENCY-PLAN.md G3/G4:** tidak audit — Asset G4/G5 sama.
- **DEBUG-CORE-MASTER-PLAN.md WS-6:** notif via permission/site, bukan hardcode role.
- **Decision Log:** "Schedule wajib ada kalau WORKFLOW sebutkan (command ada ≠ jalan);
  BANNED hardcode role di Access/Recipients; semua critical op audit via `AuditService`."

---

## 4. Urutan Eksekusi

1. **WS-1** (schedule — CRITICAL compliance) — job tidak jalan otomatis.
2. **WS-3** (hardcode role → config) — fragility.
3. **WS-2** (notif transition) — silent gap minor.
4. **WS-4** (audit) — consistency.
5. **WS-5** (tests) — coverage.
6. **WS-6** (frontend).

---

## 5. Commands Verifikasi

```bash
# Cek schedule asset (harus empty)
grep -n "assets:check" routes/console.php   # empty = TIDAK di-schedule

# Cek notif di controller asset (harus empty)
grep -n "NotificationService\|notify" app/Http/Controllers/Modules/Asset/AssetController.php   # empty = silent

# Cek hardcode role
grep -rn "whereIn('name', \['Super Admin'" app/Modules/Asset/   # L111/113

# Manual jalankan job (notif harus jalan)
php artisan assets:check-certificates
php artisan assets:check-inspections

# Frontend
npm run build
```

---

## 6. Definition of Done (Modul 17 Total)

- [x] WS-1: schedule `assets:check-certificates` (06:00) + `assets:check-inspections` (06:30) di `routes/console.php`; `schedule:list` show; test cover. (✅ 2026-07-15 — schedule SUDAH ada sejak awal, diverifikasi `schedule:list` + hanya update plan/handoff)
- [ ] WS-3: `AssetAccess`/`AssetNotificationRecipients` no hardcode role string; test cross-site scope benar.
- [ ] WS-2: `AssetController` notif `asset.decommissioned` (+ set_inactive/active); test cover.
- [ ] WS-4: asset + certificate + inspection audit via `AuditService`; test cover.
- [ ] WS-5: tests schedule + notif + audit; suite PASS.
- [ ] WS-6: `npm run build` green; UI badge benar.
- [ ] Cross-link Modul 7/9/11/13/14/15, Core/Master WS-6 tertutup; Decision Log + Handoff.

---

## 7. Catatan Jujur

- Modul 17 adalah **modul paling sehat** yang di-debug sejauh ini:
  - Scope benar via `AssetAccess` (bukan hardcode di controller seperti Modul 7/13/14/15).
  - Jobs certificate/inspection ADA + SUDAH notif (berbeda drastis dari Modul 14/16 yang command
    HILANG).
  - File download aman (ownership check).
  - Nested routes dengan middleware permission.
  - Tests ADA (3 files).
- **Satu gap CRITICAL**: jobs TIDAK di-schedule → alert tidak jalan otomatis. Tapi ini **lebih
  ringan dari Modul 14/16** (command sudah ada & benar; tinggal tambah 2 baris schedule).
- Notif transition asset (G2) minor: certificate/inspection SUDAH notif via job, hanya
  `decommission`/`status` controller yang tidak.
- Tidak audit via `AuditService` (G4/G5) — konsisten dengan antipattern global (activity doang).
- Hardcode role (G3) di `AssetAccess`/`AssetNotificationRecipients` — fragility, bukan celah
  (scope sudah benar).
- Plan ini BUKAN eksekusi; tidak ada kode diubah sampai per WS dilakukan dengan root-cause evidence.
