# DEBUG-MODULE19-REPORTING-PLAN.md — Debug Mendalam Modul 19 (Reporting & Export)

**Tanggal:** 2026-07-15
**Modul:** `19-reporting-export` (Phase 19 — ReportTemplate + SavedReport + GenerateReportJob)
**Controller:** `App\Http\Controllers\Modules\Reporting\{SavedReportController, ReportTemplateController}`
**Metode:** systematic-debugging (Iron Law: TIDAK ada fix tanpa root-cause evidence)
**Status:** 🟡 **Sangat matang (scope via ReportingScopeService, GenerateReportJob ADA + notif sukses/gagal, guards canDownload/canDelete, file download aman). Gap: tidak audit via AuditService (template + saved report), ZERO tests.**

---

## 0. Konteks & Bukti Segar

Modul 19 (Reporting) adalah **modul paling matang ke-2** (setelah Modul 17):
- `ReportingScopeService::scopeReports()` dipakai di `index` (L37) — scope benar.
- `GenerateReportJob` ADA (`app/Jobs/Modules/Reporting/GenerateReportJob.php`) + **SUDAH notif**
  `report.completed`/`report.failed` (sesuai WORKFLOW.md §6). Notif di JOB, bukan controller — ini
  DESIGN ASYNC yang benar (controller tdk block menunggu generate).
- `canDownload()` (L149) guard: hanya `completed` bisa download.
- `canDelete()` (L229) guard: `processing`/`pending` tdk bisa dihapus.
- `regenerate` buat **record baru** (L191) — sesuai WORKFLOW.md §8 (failed → regenerate = new record).
- File download aman: `Storage::path()` + `file_exists()` check (L153-157).
- Policies ADA: `ReportTemplatePolicy`, `SavedReportPolicy`.
- TIDAK ada hardcode role (grep empty).

TAPI ada **6 gap**:

| # | Gap | Bukti | WORKFLOW.md mensyaratkan |
|---|-----|------|--------------------------|
| G1 | 🟢 **Notif di job (bukan controller)** | `SavedReportController` TIDAK inject `NotificationService` (grep empty); notif ada di `GenerateReportJob` | §6: `report.completed`/`report.failed` — SUDAH di job ✅ (design benar, bukan gap) |
| G2 | 🟡 **Template TIDAK audit via AuditService** | `ReportTemplateController` L78/L128/L160/L192 hanya `activityService`; tdk `auditService->created/updated` | §7: `created`/`updated` = Audit + Activity |
| G3 | 🟡 **SavedReport TIDAK audit via AuditService** | `SavedReportController` L122/L203/L160/L240 hanya `activityService`; tdk `auditService->created/updated/log` | §7: `report.generated`/`downloaded`/`deleted` = Audit + Activity |
| G4 | 🔴 **ZERO tests** | `find tests -ipath "*eport*"` hanya `IncidentReportTest` (modul lain) + `TrainingAssetReportingRegressionTest`; TIDAK ada test Reporting sendiri | Smoke + permission + edge |
| G5 | 🟢 **`DB::beginTransaction()` vs `DB::transaction()`** | `ReportTemplateController` L67/L121/L155/L183 pakai facade `DB::beginTransaction()`; modul lain pakai `DB::transaction()` | Inkonsistensi minor (fungsional sama) |
| G6 | 🟡 **`destroy` saved report TIDAK hapus file kalau `canDelete()` false path** | L229-231 cek `canDelete()` → return error; tapi `canDownload()`/`canDelete()` definition perlu verifikasi di model (apakah `processing`/`pending` benar terblokir) | §8: `completed`/`failed` terminal; `pending`/`processing` tdk bisa dihapus |

---

## 1. Inventori Target

| Komponen | Path |
|----------|------|
| Controllers | `SavedReportController`, `ReportTemplateController` (`app/Http/Controllers/Modules/Reporting/`) |
| Models | `ReportTemplate`, `SavedReport` (`app/Models/Modules/Reporting/`) |
| Job | `app/Jobs/Modules/Reporting/GenerateReportJob.php` (✅ notif sukses/gagal) |
| Scope Service | `app/Services/Modules/Reporting/ReportingScopeService.php` (✅ scope) |
| Policies | `ReportTemplatePolicy`, `SavedReportPolicy` (`app/Policies/Modules/Reporting/`) |
| Routes | `routes/modules/reporting.php` (require L242) — resource + custom ✅ |
| Tests | **NONE** untuk Reporting sendiri (`tests/Feature/Modules/Reporting/` kosong) |
| Docs | `docs-qhsse/modules/19-reporting-export/` (✅ lengkap 276 lines) |

---

## 2. Workstream

### WS-1: Audit via AuditService (G2/G3)  🟡
- **Bug:** template + saved report hanya `activityService`; tidak `auditService`.
- **Fix:** inject `AuditService`; panggil `auditService->created/updated/log` di store/update/
  download/destroy/regenerate sesuai WORKFLOW.md §7.
- **DoD:** audit_logs konsisten; test cover.

### WS-2: Tests (G4)  🔴
- **Bug:** ZERO tests untuk Reporting.
- **Fix:** buat `ReportTemplateTest` (CRUD + toggleActive + scope + audit), `SavedReportTest`
  (generate dispatch + scope + download guard + delete guard + regenerate), `GenerateReportJobTest`
  (success → completed + notif; exception → failed + notif).
- **DoD:** suite PASS; coverage G2/G3/G4.

### WS-3: Verify canDelete/canDownload guards (G6)  🟡
- **Fix:** baca `SavedReport` model; pastikan `canDelete()` blokir `pending`/`processing`,
  `canDownload()` hanya `completed`. Jika ada celah, perbaiki.
- **DoD:** guard benar; test cover.

### WS-4: Consistency DB transaction (G5)  🟢
- **Fix:** ubah `DB::beginTransaction()` → `DB::transaction()` di `ReportTemplateController`
  (inkonsistensi minor; opsional).
- **DoD:** konsisten; test tetap PASS.

### WS-5: Frontend  🟡
- **Debug:** Show render status badge (pending/processing/completed/failed) + download button
  (disabled kalau not completed); `npm run build` green.
- **DoD:** UI benar; `npm run build` green.

---

## 3. Cross-Link ke Plan Lain

- **DEBUG-MODULE17-ASSET-PLAN.md G4/G5 + DEBUG-MODULE14-LEGAL-PLAN.md G4/G5 + DEBUG-MODULE15-EMERGENCY-PLAN.md G3/G4:** tidak audit via `AuditService` — Reporting G2/G3 SAMA.
- **DEBUG-MODULE16-CONTRACTOR-PLAN.md G7 (ZERO tests) + DEBUG-MODULE7-TRAINING-PLAN.md + DEBUG-MODULE8-PERMIT-PLAN.md:** ZERO tests — Reporting G4 SAMA.
- **DEBUG-MODULE17-ASSET-PLAN.md G1:** notif di job bukan controller — Reporting G1 SAMA (DESIGN BENAR, bukan gap).
- **Decision Log:** "Audit via `AuditService` wajib untuk semua critical op; notif async di job adalah design benar; ZERO tests = risiko eksekusi."

---

## 4. Urutan Eksekusi

1. **WS-1** (audit) — consistency.
2. **WS-2** (tests) — ZERO coverage risiko.
3. **WS-3** (guards verify) — integrity.
4. **WS-4** (transaction consistency) — minor.
5. **WS-5** (frontend).

---

## 5. Commands Verifikasi

```bash
# Cek notif (harus di job, bukan controller)
grep -rn "NotificationService\|notify" app/Http/Controllers/Modules/Reporting/   # empty = di job (OK)
grep -rn "NotificationService\|notify" app/Jobs/Modules/Reporting/GenerateReportJob.php   # ADA = OK

# Cek audit di controller
grep -n "AuditService\|auditService" app/Http/Controllers/Modules/Reporting/*.php   # empty = GAP

# Cek tests
find tests/Feature/Modules/Reporting -type f   # empty = ZERO tests

# Frontend
npm run build
```

---

## 6. Definition of Done (Modul 19 Total)

- [ ] WS-1: template + saved report audit via `AuditService`; test cover.
- [ ] WS-2: `ReportTemplateTest` + `SavedReportTest` + `GenerateReportJobTest`; suite PASS.
- [ ] WS-3: `canDelete()`/`canDownload()` guard benar; test cover.
- [ ] WS-4: `DB::transaction()` konsisten (opsional).
- [ ] WS-5: `npm run build` green; UI status badge + download guard benar.
- [ ] Cross-link Modul 17/14/15/16/7/8, Core/Master WS-6 tertutup; Decision Log + Handoff.

---

## 7. Catatan Jujur

- Modul 19 adalah **modul paling sehat ke-2** setelah Modul 17:
  - Scope benar via `ReportingScopeService` (bukan hardcode di controller).
  - `GenerateReportJob` ADA + SUDAH notif `report.completed`/`report.failed` (design async benar).
  - Guards `canDownload()`/`canDelete()` ADA (beda dari Modul 9/16 yang tidak ada guards).
  - File download aman (path + exists check).
  - `regenerate` buat record baru (sesuai WORKFLOW §8).
  - TIDAK ada hardcode role.
- **Satu gap riil**: tidak audit via `AuditService` (G2/G3) — konsisten dgn antipattern global
  (activity doang).
- **ZERO tests** (G4) — risiko saat eksekusi WS-1 (bisa break existing CRUD).
- Notif di job (G1) BUKAN gap — ini design async yang benar (controller tdk block).
- Plan ini BUKAN eksekusi; tidak ada kode diubah sampai per WS dilakukan dengan root-cause evidence.
