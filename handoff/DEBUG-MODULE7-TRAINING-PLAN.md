# DEBUG-MODULE7-TRAINING-PLAN.md — Debug Mendalam Modul 7 (Training & Competency)

**Tanggal:** 2026-07-15
**Modul:** `08-training-competency` (Phase 8 — modul paling bermasalah secara struktural)
**Metode:** systematic-debugging (Iron Law: TIDAK ada fix tanpa root-cause evidence)
**Status:** 🔴 **TIDAK ADA feature test + ANTIPATTERN HARDCODE ROLE MASSAL + LOGIC GAP status transition**.

---

## 0. Konteks & Bukti Segar

- **Modul 7 TIDAK punya feature test** untuk controller/authorization. Hanya:
  - `tests/Feature/Modules/TrainingAssetReportingRegressionTest.php` (test reporting, BUKAN Training CRUD)
  - `tests/Feature/Factories/TrainingRecordFactoryTest.php` (factory only)
  - `tests/Feature/Modules/Training/` → **KOSONG**.
- **Modul 7 pakai antipattern hardcode role SECARA MASIF** — lebih buruk dari CAPA:
  `TrainingRecordController` (L54, L107, L203, L302) & `TrainingMatrixController` (L32) langsung
  `$user->hasRole(['Super Admin','Admin','QHSSE Manager','Auditor','QHSSE Officer','Supervisor','Department Head'])`.
  TIDAK pakai `core.scope.*` (beda dgn Modul 5/6 yang BENAR).
- **Modul 7 TIDAK pakai WorkflowService** (WORKFLOW.md §1: sengaja simple status) — tapi transisi
  status TIDAK divalidasi di controller (lihat G3).

| # | Gap | Bukti kode | WORKFLOW.md mensyaratkan |
|---|-----|-----------|--------------------------|
| G1 | Hardcode role massal | `hasRole([...7 role...])` di `index` L54, `create` L107, `edit` L203, `export` L302, `matrix` L32 | Inkonsisten dgn `core.scope.*`; fragile ke nama seeder |
| G2 | Tidak ada feature test | `tests/Feature/Modules/Training/` kosong | Tidak ada verifikasi authz/scope/logic |
| G3 | `update()` tak validasi ALLOWED_TRANSITIONS | `update()` L226-286 LANGSUNG `$record->update($data)` TANPA cek transisi | §3/§4: `scheduled→completed` dilarang skip `in_progress`; `cancelled` terminal; `completed→expired` auto |
| G4 | `show()` tak on-access expiry check | `show()` L173-188 TIDAK update `expired` | §5.3: on-access detect `expiry_date<now()` → `expired` |
| G5 | `update()` tak log `record.status_changed` | `update()` tidak panggil `ActivityService::log('record.status_changed')` | §6: audit status change |
| G6 | `NotificationService->notify` signature | `store` L158 `notify($user_id,'Training Scheduled',$msg,$route)` — 4 arg | Perlu cek signature `NotificationService::notify`; kemungkinan mismatch (silent fail/error) |
| G7 | `CheckTrainingExpiry` command | WORKFLOW.md §5.1 sebut `app/Console/Commands/CheckTrainingExpiry.php` | Perlu cek file ada & scheduled di `routes/console.php` |

---

## 1. Inventori Target

| Komponen | Path |
|----------|------|
| Controllers | `TrainingProgramController`, `TrainingRecordController`, `TrainingMatrixController` (`app/Http/Controllers/Modules/Training/`) |
| Models | `TrainingProgram`, `TrainingRecord` (`app/Models/Modules/Training/`) |
| Policy | — (pakai `authorize('view'/'update'/'delete', $record)` di Record; `authorize('training.programs.*')` di Program) |
| Requests | `StoreTrainingProgramRequest`, `UpdateTrainingProgramRequest`, `StoreTrainingRecordRequest`, `UpdateTrainingRecordRequest` |
| Routes | `routes/modules/training.php` (require di `routes/modules.php` L211) |
| Frontend | `resources/js/Pages/Modules/Training/{Programs,Records,Matrix}/*.tsx` |
| Tests | `tests/Feature/Modules/Training/` → **KOSONG**; `TrainingAssetReportingRegressionTest.php` (regression) |
| Spec | `docs-qhsse/modules/08-training-competency/{MODULE_SPEC,WORKFLOW,DATA_MODEL,TEST_CASES,API_CONTRACT,UI_PAGES}.md` |
| Commands | `app/Console/Commands/CheckTrainingExpiry.php` (per §5.1 — perlu cek ada) |

---

## 2. Workstream

### WS-1: Replace hardcode role dgn `core.scope.*` (G1)  🔴
- **Bug:** 5 tempat pakai `hasRole([7 role])`. Fragile & inkonsisten. Kalau seeder ganti nama role
  → scope rusak (user lihat terlalu banyak/terlalu sedikit).
- **Fix:** ganti ke pola `visibleQuery`/`ensureVisible`/`ensureMutable` berbasis `core.scope.*`
  (seperti Modul 5/6). Buat helper di controller:
  - `core.scope.all` → lihat semua
  - `core.scope.site` → site employee
  - `core.scope.department` → department employee
  - `core.scope.own` → `employee_id = user.employee_id`
- **Verifikasi:** QHSSE Officer site A tidak lihat record site B; Supervisor dept X lihat dept X saja.
- **DoD:** scope pakai permission, bukan hardcode role; test cover (lihat WS-2).

### WS-2: Feature tests (G2)  🔴
- **Bug:** zero coverage Training controller. Tidak ada bukti authz/scope/logic jalan.
- **Fix:** buat `tests/Feature/Modules/Training/`:
  - `TrainingProgramTest` (CRUD + permission + audit)
  - `TrainingRecordTest` (CRUD + scope + status transition + notif)
  - `TrainingMatrixTest` (scope matrix)
- **DoD:** suite cover G1/G3/G4/G5; minimal 10 test.

### WS-3: Status transition validation (G3)  🔴
- **Bug:** `update()` tidak cek `ALLOWED_TRANSITIONS`. Record bisa `scheduled→completed` (skip
  `in_progress`), `cancelled→scheduled` (padahal terminal), `scheduled→expired` (tidak logis).
- **Fix:** tambah `private const ALLOWED_TRANSITIONS` (§4) + validasi di `update()`:
  `if ($oldStatus !== $newStatus && !in_array($newStatus, ALLOWED[$oldStatus]??[])) return back()->withErrors(['status'=>...])`
- **Verifikasi:** `scheduled→completed` → `assertSessionHasErrors(['status'])`; `cancelled→*` → error.
- **DoD:** transisi diblokir sesuai §3; test cover.

### WS-4: On-access expiry check (G4)  🟡
- **Bug:** `show()` tidak update `expired` saat `expiry_date<now()`.
- **Fix:** di `show()` (L173) tambah on-access check (§5.3) sebelum render.
- **Verifikasi:** record `completed` dgn `expiry_date` lampau → GET `show` → status jadi `expired`.
- **DoD:** on-access expiry jalan; test cover.

### WS-5: Status change audit (G5)  🟡
- **Bug:** `update()` tidak log `record.status_changed` activity (§6).
- **Fix:** di `update()` setelah validasi transisi, panggil `activityService->log('record.status_changed', ...)`.
- **DoD:** audit status change ada; test cover.

### WS-6: NotificationService signature (G6)  🟡
- **Bug:** `store` L158 `notify($user_id, 'Training Scheduled', $msg, $route)` — 4 arg. Perlu cek
  `NotificationService::notify` signature. Jika mismatch → silent fail atau TypeError.
- **Fix:** sesuaikan call dgn signature `notify($recipients, $type, $context, $actor, $moduleName, $referenceId, $actionUrl)`.
- **Verifikasi:** `core_notifications` terisi saat store/update completed.
- **DoD:** notif jalan; test cover.

### WS-7: Expiry command (G7)  🟡
- **Bug:** WORKFLOW.md §5.1 sebut `CheckTrainingExpiry` command. Perlu cek file ada & scheduled.
- **Fix:** jika tidak ada → buat command + schedule di `routes/console.php`. Jika ada → verify.
- **DoD:** command ada & jalan (atau tercatat future).

### WS-8: Program controller scope (verify)  🟡
- `TrainingProgramController` `index()` (L29) **TIDAK scope** (`TrainingProgram::query()` mentah).
  Program memang global (bisa dilihat semua), tapi `create`/`update`/`store`/`edit` pakai
  `authorize('training.programs.*')` — OK. Program mungkin sengaja global. **Verifikasi** di spec.
- **DoD:** program scope sesuai spec (global atau di-scope).

### WS-9: Frontend  🟡
- **Sudah:** TS `sla_days` fix (build green).
- **Debug:** Records/Index/Show/Matrix render `can`, matrix keyed shape, error `status` handling.
- **DoD:** `npm run build` green; UI sesuai spec.

---

## 3. Cross-Link ke Plan Lain

- **DEBUG-MODULE1-INCIDENT-PLAN.md WS-6 + DEBUG-CORE-MASTER-PLAN.md WS-6 + Modul 6 WS-1:** hardcode
  role (G1) SAMA antipattern. Modul 7 PALING PARAH (5 tempat). Gunakan satu helper `core.scope.*`.
- **DEBUG-MODULE5-AUDIT-PLAN.md G5 + DEBUG-MODULE6-DOCUMENT-PLAN.md:** Modul 5/6 SUDAH scope benar
  (`visibleQuery`/`ensureVisible`/`ensureMutable`). Modul 7 harus MENGIKUTI pola itu, bukan hardcode.
- **DEBUG-MODULE4-INSPECTION-PLAN.md G1/G3:** Modul 4 lupa validasi + audit; Modul 7 SAMA (G3/G5).
- **Decision Log:** "Semua scope pakai `core.scope.*`, BANNED hardcode role name di controller".

---

## 4. Urutan Eksekusi

1. **WS-1** (hardcode role → scope) — keamanan/consistency.
2. **WS-3** (status transition) — logic bug.
3. **WS-2** (feature tests) — coverage (bukti fix jalan).
4. **WS-4/5/6/7/8/9** — expiry, audit, notif, command, program scope, frontend.

---

## 5. Commands Verifikasi

```bash
# Cek feature test ada?
ls tests/Feature/Modules/Training/   # saat ini KOSONG

# Repro G1: hardcode role
grep -rn "hasRole(\['Super Admin'" app/Http/Controllers/Modules/Training/

# Repro G3: scheduled->completed tanpa validasi (saat ini lolos = bug)
php artisan tinker --execute="
\$u=App\Models\User::factory()->create(); \$u->assignRole('Admin');
\$emp=App\Models\Core\Users\Employee::factory()->create();
\$prog=App\Models\Modules\Training\TrainingProgram::factory()->create();
\$r=App\Models\Modules\Training\TrainingRecord::factory()->create(['status'=>'scheduled','employee_id'=>\$emp->id,'training_program_id'=>\$prog->id]);
\$c=app(App\Http\Controllers\Modules\Training\TrainingRecordController::class);
\$c->update(app(App\Http\Requests\Modules\Training\UpdateTrainingRecordRequest::class)->merge(['status'=>'completed']), \$r);
echo \$r->fresh()->status; // saat ini 'completed' (BUG), seharusnya 'scheduled' (diblokir)
"

# Frontend
npm run build
```

---

## 6. Definition of Done (Modul 7 Total)

- [ ] WS-1: scope pakai `core.scope.*` di semua controller; no hardcode role; test cover.
- [ ] WS-3: `update()` validasi `ALLOWED_TRANSITIONS`; test cover.
- [ ] WS-2: feature tests (Program/Record/Matrix) minimal 10; suite PASS.
- [ ] WS-4: on-access expiry di `show()`; test cover.
- [ ] WS-5: `record.status_changed` audit; test cover.
- [ ] WS-6: `NotificationService` call benar; notif jalan; test cover.
- [ ] WS-7: expiry command ada + scheduled (atau tercatat).
- [ ] WS-8: program scope sesuai spec.
- [ ] WS-9: `npm run build` green; UI sesuai.
- [ ] Cross-link Core/Master WS-6, Incident WS-6, Modul 5/6 tertutup; Decision Log + Handoff.

---

## 7. Catatan Jujur

- Modul 7 adalah **modul paling bermasalah** yang di-review: TIDAK ada test + hardcode role massal +
  status transition tidak divalidasi. Ini regression risk tinggi.
- G1 (hardcode role) muncul di 5 tempat — paling parah dari semua modul.
- G3 (status transition) = logic bug: `cancelled` bisa di-reactivate, `scheduled` bisa `completed`
  tanpa `in_progress` — melanggar lifecycle.
- Berbeda dgn Modul 5/6 yang production-ready, Modul 7 perlu **refactor scope** supaya konsisten.
- Plan ini BUKAN eksekusi; tidak ada kode diubah sampai per WS dilakukan dengan root-cause evidence.
