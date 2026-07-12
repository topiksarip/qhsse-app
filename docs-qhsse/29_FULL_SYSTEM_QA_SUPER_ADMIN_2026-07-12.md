# Full-System QA — Super Admin

**Target:** QHSSE App v3 (`http://127.0.0.1:8080`)  
**Tanggal:** 2026-07-12  
**Commit:** `36159cd` pada branch `develop`  
**Role:** Super Admin (`[REDACTED]`)  
**Mode:** read-only browser dogfood, static route smoke test, CDP runtime sweep, Laravel test suite, build, log inspection, dan dependency audit.

## 1. Executive Summary

| Severity | Jumlah |
|---|---:|
| Critical | 8 |
| High | 6 |
| Medium | 3 |
| Low | 2 |
| **Total akar masalah historis** | **19** |
| **Resolved** | **2** |
| **Masih terbuka** | **17** |

**Status rilis: TIDAK SIAP.** Dashboard PostgreSQL dan Legal Register sudah diperbaiki, tetapi automated test, authorization, Inertia page contract, dan beberapa modul utama masih mengalami kegagalan blocking.

### Hasil gate utama

| Pemeriksaan | Hasil |
|---|---|
| `npm run build` | PASS, exit 0 |
| `php artisan test` | FAIL — 45 failed, 256 passed, 967 assertions, 508.91 s setelah remediasi Dashboard; failure count tidak bertambah |
| Composer validation/audit | PASS — manifest valid, 0 advisory |
| npm production dependency audit | PASS — 0 vulnerability |
| Migration PostgreSQL | Semua migration berstatus Ran |
| Route inventory | 305 registered routes |
| Static authenticated GET smoke | 90 route: 61 HTTP 200, 17 HTTP 500, 12 HTTP 403 |
| CDP runtime sweep atas Inertia HTTP 200 | 52 page: 45 render, 7 blank/crash |
| Super Admin | Aktif, verified, role benar, 148 permission efektif |

## 2. Coverage

### Dicakup

- Authentication dan redirect setelah login.
- Dashboard dan shared authenticated shell.
- Seluruh static GET route Core Foundation dan modul yang terdaftar.
- Index, create, export, permission middleware, Inertia component resolution, dan browser JavaScript runtime.
- Core: sites, areas, departments, positions, companies, employees, users, severities, priorities, statuses, categories, risk matrix, files, numbering, workflow, audit log, comments/activity, notifications.
- Modules: Incident, Investigation, CAPA, Inspection, Document Control, Audit, Training, Permit, Environmental, Security, Quality, Risk, Legal, dan Emergency.
- Laravel log, PostgreSQL-specific failures, Vite manifest, Ziggy route references, dan dependency vulnerability audit.

### Batas cakupan

- Seluruh 18 tabel/model bisnis live berisi 0 record. Detail/edit/workflow berbasis record tidak dapat diuji tanpa membuat data.
- Browser audit dijaga read-only; tidak ada POST/PUT/DELETE terhadap database development.
- Mutasi dan edge case diwakili feature tests, tetapi suite saat ini memiliki 45 kegagalan.
- Responsive/mobile dan file upload end-to-end ditunda sampai blocking runtime diperbaiki.

## 3. Module Status Matrix

| Area | HTTP/static result | Runtime browser | Status |
|---|---|---|---|
| Core Foundation | 36 route 200 | 31/33 page render; Sites dan Departments blank | Partial |
| Dashboard | 1 route 200 setelah remediasi | KPI dan 4 widget render | Resolved |
| Incident | 3 route 200 | Index/create render | Usable pada empty state |
| Investigation | 3 route 200 | Index/create render | Usable pada empty state |
| CAPA | 3 route 200 | Index/create render | Usable pada empty state |
| Inspection | 5 route 200 | 4/4 Inertia page render | Usable pada empty state |
| Document Control | 3 route 200 | Index/create render | Usable pada empty state |
| Audit | 3 route 200 | 0/2 Inertia page render | Blocked oleh frontend contract |
| Training | 6 route 500 | Tidak dapat masuk | Blocked |
| Permit to Work | 3 route 403 | Tidak dapat masuk sebagai Super Admin | Blocked |
| Environmental | 3 route 403 | Tidak dapat masuk sebagai Super Admin | Blocked |
| Security | 3 route 403 | Tidak dapat masuk sebagai Super Admin | Blocked |
| Quality | 3 route 403 | Tidak dapat masuk sebagai Super Admin | Blocked |
| Risk Management | Index 200; create/export 500 | Index render | Partial |
| Legal & Compliance | Index/create/export 200 setelah remediasi | Index dan create render; export CSV valid | Usable pada empty state |
| Emergency Preparedness | 3 route 200; 5 route 500 | Semua 3 index blank | Blocked |

## 4. Findings

### QA-001 — Dashboard gagal di PostgreSQL karena `strftime()` — RESOLVED

- **Severity:** Critical
- **Category:** Functional / Database portability
- **URL:** `/dashboard`
- **Temuan awal:** HTTP 500 `SQLSTATE[42883]`; PostgreSQL tidak memiliki fungsi `strftime()`.
- **Source awal:** `app/Http/Controllers/DashboardController.php:146,149`.
- **Remediasi 2026-07-12:** menambahkan `DatePeriodExpression` untuk memilih `to_char(..., 'YYYY-MM')` pada PostgreSQL dan mempertahankan `strftime(...)` pada SQLite.
- **Verification:** 3 unit tests/4 assertions dan 13 Dashboard tests/151 assertions lulus; production build lulus; browser PostgreSQL live merender Dashboard, KPI, dan empat widget tanpa exception.
- **Evidence awal:** `/home/qhsse/.hermes/cache/screenshots/browser_screenshot_881dec0912b64da091dfae0428f54188.png`
- **Evidence sesudah perbaikan:** `/home/qhsse/.hermes/cache/screenshots/browser_screenshot_44890d7bcd2a48c2adaddde3b39c6969.png`

### QA-002 — Empat modul tidak dapat diakses Super Admin

- **Severity:** Critical
- **Category:** Authorization
- **URLs:** `/permit/work`, `/environment/records`, `/security/incidents`, `/quality/ncrs` beserta create/export.
- **Actual:** Semua 12 static route menghasilkan HTTP 403.
- **Root cause:** Route meminta permission `permit.work.*`, `environment.records.*`, `security.incidents.*`, dan `quality.ncrs.*`, tetapi permission tersebut tidak didefinisikan dalam `CorePermissions::all()`. Catalog DB sinkron dengan code (148/148), jadi bukan stale seed.
- **Expected:** Permission module terdefinisi, di-seed, dan diberikan ke Super Admin.

### QA-003 — Training module gagal total karena kontrak backend yang tidak konsisten

- **Severity:** Critical
- **Category:** Functional / Dependency injection
- **URLs:** enam static Training route menghasilkan HTTP 500.
- **Root causes terkonfirmasi:**
  - `ListQuery::paginate()` membutuhkan array searchable, tetapi controller mengirim nilai search string/null pada `TrainingProgramController.php:36-40`.
  - `App\Core\Services\Files\PrivateFileService` tidak ada.
  - Base controller tidak menyediakan `$this->authorize()`.
  - Controller merender `Programs/Form` dan `Programs/Show`, sementara page aktual tidak lengkap/berbeda nama.
- **Expected:** Seluruh Training index/create/matrix/export dapat digunakan Super Admin.

### QA-004 — Legal module gagal dependency resolution — RESOLVED

- **Severity:** Critical
- **Category:** Functional / Dependency injection
- **URLs:** `/legal/registers`, `/legal/registers/create`, `/legal/registers/export`.
- **Temuan awal:** HTTP 500 karena namespace Numbering/Scope service salah; create juga memakai model/field Document Control lama dan Blade meminta page Inertia sebagai Vite entry kedua.
- **Remediasi:** selaraskan ke shared Core services, `ControlledDocument.document_number`, entrypoint tunggal `app.tsx`, nomor register string, shared Activity Service, dan relasi file/comment/activity Legal.
- **Verifikasi:** index dan create render pada PostgreSQL live; export CSV selesai; feature tests membuktikan index/create/export/store/show, format `LEG-YYYY-NNNN`, activity log, relasi dokumen, dan permission block.

### QA-005 — Core Sites dan Departments blank walaupun HTTP 200

- **Severity:** Critical
- **Category:** Console / Inertia contract
- **URLs:** `/core/sites`, `/core/departments`.
- **Actual:** halaman putih; CDP menangkap `TypeError: Cannot convert undefined or null to object`.
- **Root cause:** empty PHP array `filters` diserialisasi sebagai JSON `[]`. Di React, `filters.sort` kemudian mengacu ke native `Array.prototype.sort`; `useState(filters.sort ?? 'name')` menjalankannya sebagai lazy initializer tanpa receiver dan crash.
- **Expected:** `filters` selalu object atau frontend melakukan normalisasi sebelum akses.
- **Evidence:** `/home/qhsse/.hermes/cache/screenshots/browser_screenshot_d859a700e4fa401f9c4b7e12799d7988.png`

### QA-006 — Audit pages blank akibat Ziggy dan prop mismatch

- **Severity:** Critical
- **Category:** Console / Frontend-backend contract
- **URLs:** `/audits`, `/audits/create`.
- **Actual:** HTTP 200 tetapi React root kosong.
- **Exceptions:**
  - Index: `route 'audit.management.export' is not in the route list`; backend route bernama `audits.export`.
  - Create: `Cannot read properties of undefined (reading 'audit_number')`.
- **Expected:** Route name dan initial form props cocok dengan controller.

### QA-007 — Emergency index pages blank

- **Severity:** Critical
- **Category:** Console / Frontend-backend contract
- **URLs:** `/plans`, `/drills`, `/contacts`.
- **Actual:** Ketiga index HTTP 200, tetapi blank; exception `Cannot read properties of undefined (reading 'create')`.
- **Expected:** permission/action props selalu dikirim atau frontend memiliki default aman.

### QA-008 — Risk numbering menyimpan objek/JSON, bukan nomor string

- **Severity:** Critical
- **Category:** Data integrity
- **Evidence test:** nilai `register_number` menjadi JSON record numbering, bukan `RSK-YYYY-NNNN`.
- **Root cause:** hasil Numbering Service dipakai sebagai string tanpa mengambil field nomor yang benar.
- **Impact:** nomor register salah format dan berpotensi merusak uniqueness, pencarian, export, serta auditability.

### QA-009 — Inertia page resolution/Vite contract menghasilkan 500

- **Severity:** High
- **Category:** Functional / Build integration
- **URLs:** Emergency create pages dan `/risk-registers/create`.
- **Actual:** `Unable to locate file in Vite manifest` untuk page `Create.tsx` meskipun source menggunakan file lain seperti `CreateOrEdit.tsx`, atau page entry tidak masuk manifest.
- **Contributing design:** `resources/views/app.blade.php` memuat page Inertia sebagai Vite entry tambahan; mismatch nama controller-page langsung menjadi server 500.
- **Expected:** app hanya memuat entry utama atau seluruh render target divalidasi otomatis terhadap page filesystem/manifest.

### QA-010 — Base Controller tidak memakai `AuthorizesRequests`

- **Severity:** High
- **Category:** Authorization / Architecture
- **Actual:** `$this->authorize()` menghasilkan undefined method pada Training dan berpotensi di controller lain.
- **Source:** `app/Http/Controllers/Controller.php` kosong.
- **Expected:** Base controller memakai trait Laravel authorization, atau semua controller mengimpor trait secara konsisten.

### QA-011 — Export API tidak konsisten dan beberapa endpoint rusak

- **Severity:** High
- **Category:** Functional
- **Examples:**
  - Risk memanggil `$this->csvExporter->export()`, sementara shared service hanya punya `stream()`.
  - Training juga memanggil `export()` yang tidak ada.
  - Permit/Environmental/Security/Quality memakai `CsvExporter::export()` statis yang tidak tersedia.
  - Emergency `Route::resource()` didaftarkan sebelum `/export`, sehingga `export` tertangkap sebagai model ID dan menghasilkan PostgreSQL invalid bigint/404.
- **Expected:** Satu shared export contract dan route export didefinisikan sebelum wildcard route.

### QA-012 — Emergency mutation memakai helper `activity()` yang tidak terpasang

- **Severity:** High
- **Category:** Functional / Auditability
- **Actual:** create/update/delete Plan dan Drill gagal dengan `Call to undefined function ... activity()`.
- **Source:** Emergency Plan lines 104/151/167 dan Emergency Drill lines 113/160/184/210.
- **Expected:** Gunakan shared `ActivityService` Core, bukan helper package yang tidak tersedia.

### QA-013 — Query `ILIKE` membuat test SQLite gagal

- **Severity:** High
- **Category:** Test portability
- **Actual:** Emergency search tests gagal `near "ilike": syntax error` pada SQLite `:memory:`.
- **Impact:** test suite tidak dapat menjadi release gate andal, sementara runtime PostgreSQL memakai perilaku berbeda.
- **Expected:** gunakan query portable/case-insensitive abstraction atau jalankan integration suite PostgreSQL.

### QA-014 — Risk factory tidak cocok dengan schema migration

- **Severity:** High
- **Category:** Test data/schema drift
- **Actual:** enam kegagalan karena factory mengisi `severity_level`, `probability_level`, dan `risk_level` yang tidak ada pada `risk_matrix_levels`.
- **Expected:** factory mengikuti schema released; perubahan schema dilakukan additive migration, bukan asumsi kolom lama.

### QA-015 — Build lulus tetapi tidak mendeteksi kontrak runtime

- **Severity:** Medium
- **Category:** QA process
- **Actual:** TypeScript/Vite build exit 0 meskipun terdapat 17 HTTP 500, 12 HTTP 403, dan 7 blank React pages.
- **Expected:** CI menambah route smoke, Inertia render-target validation, Ziggy named-route validation, dan minimal browser E2E login/navigation.

### QA-016 — Debug exception mengekspos detail internal

- **Severity:** Medium
- **Category:** Security hardening / Information disclosure
- **URL:** `/dashboard`.
- **Actual:** Halaman exception menampilkan SQL lengkap, nama tabel, alamat dan nama database, versi framework/PHP, absolute source path, baris kode, query log, dan stack trace.
- **Impact:** Risiko meningkat bila `APP_DEBUG=true` terbawa ke staging/production.
- **Expected:** Production menggunakan debug off, generic error page, correlation ID, dan detail hanya tersedia pada log terproteksi.

### QA-017 — Validasi submit kosong tidak terlihat pada beberapa form

- **Severity:** Medium
- **Category:** Functional / UX
- **URLs:** Company, User, Incident, Investigation, CAPA, dan Inspection Template create.
- **Actual:** Submit kosong tetap berada pada form tanpa pesan field atau ringkasan validasi; Employee dan Inspection create menjadi pembanding yang menampilkan error.
- **Expected:** Semua field wajib menampilkan pesan yang jelas dan fokus berpindah ke field invalid pertama.

### QA-018 — Pesan validasi Inspection belum dilokalkan

- **Severity:** Low
- **Category:** Content / Localization
- **URL:** `/inspections/create`.
- **Actual:** Pesan masih berbahasa Inggris dan memakai nama internal seperti `inspection template id`.
- **Expected:** Pesan Bahasa Indonesia memakai label yang dipahami user operasional.

### QA-019 — Inkonsistensi UX pada filter, empty state, dan aksi profil

- **Severity:** Low
- **Category:** UX
- **Actual:** Inspection index tidak memiliki Reset seperti modul sejenis; Risk empty state menampilkan `0–0 dari 0`; pilihan Risk Level memiliki label skor berulang tanpa dimensi pembeda; dua aksi berbeda di Profile sama-sama berlabel generik `SAVE`.
- **Expected:** Pattern filter/empty state konsisten, pilihan risiko tidak ambigu, dan label aksi menjelaskan tindakan.

## 5. Automated Test Failure Groups

45 failure terkonsentrasi pada dua suite yang tampil di summary:

- Emergency Preparedness: helper `activity()` tidak ada, `ILIKE` tidak portable, export route order salah, dan expectation authorization/export gagal.
- Risk Management: stale factory columns, numbering result salah tipe, CsvExporter method mismatch, dan satu expectation authorization mismatch.

Jumlah summary Pest yang tampil: 18 baris failure group; total final tetap 45 failed karena beberapa setup/root cause memicu banyak test.

## 6. Prioritas Remediasi

1. **P0:** perbaiki permission empat modul, base authorization trait, dan Training DI/ListQuery.
2. **P0:** perbaiki blank React pages (Core, Audit, Emergency) dan tambahkan default prop/contract tests.
3. **P0:** betulkan Risk numbering agar selalu string unik dan audit-safe.
4. **P1:** seragamkan CsvExporter API dan urutan route `/export` sebelum wildcard binding.
5. **P1:** ganti helper `activity()` dengan shared Core ActivityService.
6. **P1:** sinkronkan Inertia render target dengan file aktual; hilangkan dynamic page Vite entry dari Blade bila tidak diperlukan.
7. **P1:** sinkronkan Risk factory dan buat query search portable.
8. **P2:** tambahkan PostgreSQL integration test serta Playwright login/navigation/index/create smoke.
9. **P2:** matikan debug pada environment non-local, seragamkan validasi frontend, dan lokalkan pesan error.
10. Seed dataset UAT terkontrol, kemudian ulangi CRUD, show/edit, workflow transition, upload/download, comments/activity, dan responsive/mobile.

## 7. Release Exit Criteria Setelah Perbaikan

- `php artisan test`: 0 failure.
- `npm run build`: exit 0.
- Authenticated static GET sweep: tidak ada 500/403 untuk Super Admin pada fitur yang memang diizinkan.
- CDP/browser sweep: seluruh target memiliki DOM render dan zero uncaught exception.
- Dashboard berjalan pada PostgreSQL.
- Permit, Environmental, Security, dan Quality dapat diakses Super Admin.
- Export seluruh modul mengembalikan CSV valid.
- Dataset UAT memungkinkan validasi detail/edit/workflow dan evidence file.
