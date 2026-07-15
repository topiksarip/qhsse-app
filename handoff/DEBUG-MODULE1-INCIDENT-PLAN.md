# DEBUG-MODULE1-INCIDENT-PLAN.md — Debug Mendalam Modul 1 (Incident Reporting)

**Tanggal:** 2026-07-15
**Modul:** `02-incident-reporting` (Phase 1 — modul bisnis pertama)
**Metode:** systematic-debugging (Iron Law: TIDAK ada fix tanpa root-cause evidence)
**Pendahuluan:** Ini plan debug *total* untuk Modul 1. Bukan sekadar "tes lulus/tidak", tapi audit
mendalam tiap surface (CRUD, workflow, evidence, scope, notif, audit, frontend) + cross-check dengan
bug CAPA 403 yang ditemukan di suite terbagi.

---

## 0. Konteks & Status Saat Ini (dari bukti segar)

- Full suite terbagi per modul: **Incident = PASS (27 passed)**, Capa = FAIL (6× 403), Core = FAIL (6× artifact sqlite).
- Artinya: **workflow Incident TIDAK 403** (tes `submit`/`review`/`close` pakai `actingAs($admin)` lulus),
  tapi **CAPA 403**. Ini anomali yang WAJIB diinvestigasi (lihat §7).
- Modul Incident sudah relatif matang secara test, tapi membaca kode menemukan **fragmen rawan bug**
  yang tidak tertangkap test (karena test memakai data sintetis, bukan alur production nyata).

---

## 1. Inventori Target (file yang di-debug)

| Komponen | Path |
|----------|------|
| Controller CRUD | `app/Http/Controllers/Modules/Incident/IncidentReportController.php` |
| Controller Workflow | `app/Http/Controllers/Modules/Incident/IncidentWorkflowController.php` |
| Controller Evidence | `app/Http/Controllers/Modules/Incident/IncidentEvidenceController.php` |
| Controller Print | `app/Http/Controllers/Modules/Incident/IncidentReportPrintController.php` |
| Trait Access/Scope | `app/Modules/Incident/IncidentAccess.php` |
| Trait Lifecycle | `app/Modules/Incident/IncidentLifecycle.php` |
| Model | `app/Models/Modules/Incident/IncidentReport.php` |
| Routes | `routes/modules.php` (prefix `incident-reports`, middleware `permission:*`) |
| Form Requests | `app/Http/Requests/Modules/Incident/StoreIncidentReportRequest.php`, `UpdateIncidentReportRequest.php` |
| Frontend | `resources/js/Pages/Modules/Incident/{Index,Show,Form}.tsx` |
| Tests | `tests/Feature/Modules/Incident/IncidentReportTest.php`, `IncidentAcceptanceTest.php` |
| Spec | `docs-qhsse/modules/02-incident-reporting/{MODULE_SPEC,WORKFLOW,TEST_CASES,API_CONTRACT,DATA_MODEL,UI_PAGES}.md` |

---

## 2. Workstream & Bug yang SUDAH TERDETEKSI dari Baca Kode

### WS-1: CRUD — `category` disimpan sebagai STRING, bukan `category_id`  ⚠️ TERTUDUH
- **Bukti:** `IncidentReportController::store` line 79 `'category' => $validated['category']` (string code,
  mis. `'accident'`). TAPI `MODULE_SPEC.md §5/BR-03` menyatakan `category_id` (relasi ke tabel `categories`
  `where module='incident'`). `UpdateIncidentReportRequest` kemungkinan memvalidasi `category_id exists`,
  sehingga **update bisa gagal** jika store menyimpan string di kolom yang spec harapkan FK.
- **Risiko:** relasi `category()` rusak, filter by category di Index/Export salah, export kolom `Kategori`
  (`category.name`) kosong.
- **Verifikasi:** cek `IncidentReport` model ada relasi `category()`? cek kolom DB `category` vs `category_id`.
  Jalankan test store lalu cek `IncidentReport::first()->category` (string) vs apakah ada `category_id`.
- **DoD:** satu sumber kebenaran (string `category` CODE atau FK `category_id`); test cover keduanya; export
  menampilkan nama kategori benar.

### WS-2: `formOptions()` scope bug  ⚠️ TERTUDUH (minor)
- **Bukti:** `IncidentReportController::formOptions` line 255-261: user tanpa `core.scope.all` DAN tanpa
  `employee->site_id` → `$sites->whereKey($siteId ?? 0)` → `whereKey(0)` → **0 site tersedia** di form create.
  Juga `departments`/`areas`/`employees` difilter `site_id` tapi `departments` query tidak pakai `whereKey`.
- **Risiko:** user dengan scope `company`/`own` tapi tanpa site tetap (mis. Contractor) tidak bisa pilih site
  saat create → `ensureSiteAllowed` abort 403.
- **Verifikasi:** buat user Contractor (scope company, no site) → GET create → cek `sites` props kosong.
- **DoD:** form options mengembalikan site yang valid untuk scope user; tidak `whereKey(0)`.

### WS-3: Workflow Transitions (submit/review/reject/close)
- **Status tes:** PASS di suite (admin bisa transition). Tapi harus verifikasi **role matrix nyata**:
  - Supervisor bisa `review` tapi TIDAK `close` (spec §8). Tes `IncidentReportTest` hanya cek Supervisor
    tidak bisa `close` (lulus). Perlu tes Supervisor BISA `review` (belum ada tes eksplisit).
  - `reject` butuh reason min:5 (controller line 32). Spec §BR-04 bilang min:10. **Ketidakcocokan validasi**
    (controller min:5 vs spec min:10) — harus diselaraskan.
- **Verifikasi:** jalankan alur `draft→submitted→under_review→closed` sebagai QHSSE Officer; cek reason min.
- **DoD:** tiap transition sesuai role matrix; reason min konsisten (spec 10 vs code 5 → pilih 1, dokumentasikan).

### WS-4: Evidence File (IncidentEvidenceController) — BELUM DIBACA  🔲
- Controller evidence belum di-inspeksi. Harus debug: upload (permission `update`/reporter draft),
  download (private stream, scope check), delete (dicekal kalau `closed`/SuperAdmin bypass),
  audit `incident.file.*`.
- **Verifikasi:** upload file → cek `managed_files` row; download sebagai user luar scope → 403;
  delete saat `closed` → 403; delete sebagai SuperAdmin → boleh.
- **DoD:** semua aturan `MODULE_SPEC §10` terpenuhi + ter-test.

### WS-5: Scope / Visibility (IncidentAccess) — server-side
- `visibleQuery` pakai `core.scope.{own,department,site,company,all}`. Sudah benar secara struktur.
- **Yang belum ter-test:** Employee hanya lihat miliknya; Department Head lihat dept; QHSSE Officer lihat
  site; Contractor lihat company. `IncidentReportTest` hanya tes "no permission → 403" dan auditor view.
  Perlu tes positif tiap scope.
- **Verifikasi:** seeding user tiap role + incident di site/dept beda → assert list hanya menampilkan scope-nya.
- **DoD:** query scope terbukti benar untuk ke-5 scope; tidak ada data bocor antar scope.

### WS-6: Notifications (IncidentLifecycle::sendNotification)
- Hardcode role `QHSSE Officer`/`QHSSE Manager` (line 80). Notif `incident.submitted` → notifyMany;
  `review/reject/close` → ke `reporter`.
- **Bug potensial:** `qhsseUsers()` query `roles.name IN (...)` — kalau nama role berbeda di seeder
  (mis. `QHSSE Officer` vs `QHSSE-Officer`), notif tidak terkirim. Tes `notification created on submit`
  lolos karena test assign role persis `QHSSE Officer`.
- **Verifikasi:** cek `RolesAndPermissionsSeeder` nama role persis match. Tes notif `reviewing`/`closed`/
  `rejected` ke reporter (belum ada tes eksplisit selain submitted).
- **DoD:** ke-4 event notif terkirim ke recipient benar; template variable resolve.

### WS-7: Audit Trail & Activity Log
- `auditService->created/updated`, `activityService->log` dipanggil di store/update/lifecycle.
- Tes `audit trail records creation/status change` + `activity log records creation` LULUS.
- **Yang kurang:** audit `incident.updated` hanya mencatat field berubah (cek `UpdateIncidentReportRequest`
  mengizinkan field apa saat draft). Audit `file.uploaded/deleted/downloaded` (WS-4).
- **DoD:** event audit sesuai `BR-06`; field old/new tercatat.

### WS-8: Frontend (Index/Show/Form .tsx)
- **Sudah diperbaiki:** `priorities` type `sla_days` (TS fix sebelumnya, build green).
- **Perlu cek:** prop types `IncidentReport` (category string vs category_id — ikut WS-1),
  `availableTransitions` rendering tombol (submit/review/reject/close) sesuai status,
  error handling `workflow` (controller `withErrors(['workflow'=>...])`), file upload UI.
- **Verifikasi:** `npm run build` green; cek Inertia props di `Show.tsx` match controller.
- **DoD:** build green; tidak ada runtime TS error; tombol transition muncul sesuai status.

---

## 3. Cross-Check CAPA 403 (KRUSIAL — §7 di plan utama)

- **Fakta:** CAPA workflow endpoint → **403 untuk admin** (6 test fail). Incident workflow → **PASS**.
  Keduanya pakai pola `Route::post(...)->middleware('permission:*.action')` + controller tanpa Policy.
- **Pertanyaan root-cause:** kenapa Incident lolos, CAPA gagal? Kemungkinan:
  (a) CAPA route/middleware pakai permission key berbeda yang tidak di-seed ke role Admin, atau
  (b) CAPA controller melakukan pengecekan tambahan (Policy/Gate) yang deny, atau
  (c) `core.workflow.transition` permission tidak ada di role Admin untuk modul capa.
- **Tindakan:** bandingkan `routes/modules.php` seksi incident vs capa; bandingkan
  `CorePermissions::roleMap()` untuk `incident.reports.*` vs `capa.actions.*`; jalankan
  `php artisan route:list | grep -E "incident|capa"` dan cek middleware; cek seeder permission capa.
- **DoD:** penjelasan root-cause CAPA 403 tertulis; jika Incident juga punya celah serupa, diperbaiki di sini.

---

## 4. Urutan Eksekusi (Vertikal, per workstream)

1. **WS-3 + §3 (cross-check CAPA)** dulu — paling berdampak operasional (PIC tidak bisa proses CAPA).
2. **WS-1 (category)** — integrity data, sentiment tinggi.
3. **WS-2 (formOptions)** — UX create untuk scoped user.
4. **WS-4 (evidence)** — keamanan file private.
5. **WS-5 (scope positif)** — kebocoran data antar scope.
6. **WS-6 (notif)** — reliability notif.
7. **WS-7 (audit)** — completeness.
8. **WS-8 (frontend)** — build + prop sync.

Setiap WS: tulis repro → dapat root cause → fix → test baru → jalankan `php artisan test tests/Feature/Modules/Incident`
→ update plan status → (jika fix melintasi modul) catat di Decision Log.

---

## 5. Commands Verifikasi (re-runnable)

```bash
# Incident suite (sudah hijau — jangan rusak)
php artisan test tests/Feature/Modules/Incident

# Cross-check CAPA vs Incident routes + permissions
php artisan route:list | grep -E "incident-reports|capa"
grep -n "capa.actions\|incident.reports" app/Core/Permissions/CorePermissions.php

# Frontend
npm run build

# Scope/rolematrix manual probe (standalone script, bypas curl 419)
# lihat DEBUGGING-HANDOFF.md §4 pitfall #1
```

---

## 6. Definition of Done (Modul 1 Total)

- [ ] WS-1: `category` konsisten (string code ATAU FK `category_id`); export nama kategori benar; test cover.
- [ ] WS-2: `formOptions` tidak `whereKey(0)`; scoped user dapat site valid.
- [ ] WS-3: semua transition sesuai role matrix; reason-min selaras spec/code.
- [ ] WS-4: upload/download/delete evidence patuh `MODULE_SPEC §10`; audit file tercatat.
- [ ] WS-5: ke-5 scope terbukti benar (tes positif tiap role); tidak ada kebocoran.
- [ ] WS-6: ke-4 notif event ke recipient benar; role name match seeder.
- [ ] WS-7: audit trail lengkap per `BR-06`.
- [ ] WS-8: `npm run build` green; prop types sync; tombol transition benar.
- [ ] §3: root-cause CAPA 403 tertulis; celah serupa di Incident (jika ada) diperbaiki.
- [ ] Handoff `DEBUG-MODULE1-INCIDENT-HANDOFF.md` dibuat; changelog + decision log diupdate.

---

## 7. Catatan Jujur / Deferred

- Suite sqlite `:memory:` (phpunit.xml) masih bisa beri false-positive/negative untuk relasi berat;
  untuk WS-1/WS-5 disarankan jalankan vs PostgreSQL (`phpunit.production.xml`) agar mirror production.
- 5 modul tanpa test (Communication, Contractor, Documents, Environment, Permit) di-debug di plan terpisah.
- Ini plan, BUKAN eksekusi. Tidak ada kode diubah sampai user setuju / per WS dilakukan.
