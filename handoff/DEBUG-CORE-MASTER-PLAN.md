# DEBUG-CORE-MASTER-PLAN.md — Debug Mendalam Core Services & Master Data

**Tanggal:** 2026-07-15
**Ruang lingkup:** `app/Core/*` (19 service-group) + `app/Models/Core/MasterData/*` (11 model) + seeder terkait
**Metode:** systematic-debugging (Iron Law: TIDAK ada fix tanpa root-cause evidence)
**Pendahuluan:** Ini plan debug *total* untuk fondasi (Core) dan data master. Core adalah tulang punggung
semua modul bisnis (Incident, Capa, dst). Bug di sini berdampak meluas. Plan ini berkaitan erat dengan
`DEBUG-MODULE1-INCIDENT-PLAN.md` (WS-1 `category` ↔ MasterData `Category`; §3 CAPA 403 ↔ `WorkflowService`/`Permissions`).

---

## 0. Konteks & Bukti Segar

- Full suite terbagi: **Core = FAIL (6 failed, 65 passed)**. Ke-6 failure di **2 file**:
  `CommentsActivityTest` (3) + `ManagedFileServiceTest` (3). Semua error = `ModelNotFoundException`
  tepat setelah `Model::create()` / `firstOrFail()` di assertion (test line 32, 52, 88).
- **Hipotesis awal:** artifact **sqlite `:memory:`** (phpunit.xml) — data create tidak terbaca di koneksi
  assertion berbeda. BUKAN bug logika (test lain: WorkflowCore, RbacCore, NumberingService, AuditTrail,
  NotificationCore, dll **LULUS**). Harus dikonfirmasi vs PostgreSQL (WS-0).
- Master Data: 11 model; sebagian sudah di-seed (`QhsseMasterDataSeeder`), sebagian butuh penambahan
  (MODULE_SPEC §5: kategori `INCIDENT/ENVIRONMENTAL_SPILL/SECURITY_BREACH` belum di-seed).

---

## 1. Inventori Target

### Core service-groups (19)
`Activity, Audit, AuditTrail, Auth, Authorization, Comments, Dashboard, Export, Exports, Files,
Import, MasterData, Notifications, Numbering, Permissions, Query, Services, Users, Workflow`

### Master Data models (11)
`Area, Category, Company, Department, Position, Priority, RiskMatrixLevel, Severity, Site, Status`
+ seeder: `QhsseMasterDataSeeder`, `RolesAndPermissionsSeeder`, `NumberingFormatSeeder`, `WorkflowSeeder`

### Test Core (15 file) — status diketahui
- **FAIL (artifact diduga):** `CommentsActivityTest`, `ManagedFileServiceTest`
- **PASS:** `AdminToolingTest, AuditTrailTest, DashboardShellTest, IdentityCoreTest,
  ListQueryCompatibilityTest, NotificationCoreTest, NumberingServiceTest, OrganizationMasterTest,
  QhsseMasterDataTest, RbacCoreTest, RolePermissionMatrixTest, SearchFilterExportBaseTest, WorkflowCoreTest`

---

## 2. Workstream

### WS-0: Konfirmasi & Selesaikan Artifact Core  🔴 WAJIB DULU
- **Bukti:** 6 failure = `ModelNotFoundException` pasca-create di `:memory:`.
- **Verifikasi:** jalankan `php artisan test tests/Feature/Core --env=testing` vs **PostgreSQL**
  (buat `phpunit.production.xml` dengan `DB_CONNECTION=pgsql`, `SESSION_DRIVER=database`,
  `CACHE_STORE=redis|file`). Jika LULUS vs PG → konfirmasi artifact (bukan bug).
- **Resolusi (jika artifact):** dokumentasikan di DEBUGGING-HANDOFF §4 pitfall; tambahkan
  `phpunit.production.xml` agar CI pakai PG. JANGAN "memperbaiki" kode aplikasi yang sudah benar.
- **Resolusi (jika GAGAL vs PG):** berarti bug nyata di `CommentService`/`ManagedFileService` → lanjut WS-3/WS-7.
- **DoD:** status 6 failure tertulis (artifact vs bug); CI profile PG ada.

### WS-1: Permissions & RBAC (krusial — terkait CAPA 403)  🔴
- `CorePermissions::all()` / `roleMap()` mengisi 7 key `incident.reports.*` + `capa.actions.*` +
  `core.scope.*` + `core.workflow.transition`.
- **Debug:** bandingkan `routes/modules.php` seksi incident vs capa; cek `capa.actions.*` benar-benar
  di-seed ke role Admin/QHSSE. Ini root-cause **CAPA 403** (lihat DEBUG-MODULE1 §3).
- **Verifikasi:** `php artisan route:list | grep -E "capa|incident"`; `grep -rn "capa.actions" app/Core/Permissions`;
  standalone script: `User::find(1)->can('capa.actions.start')` vs `incident.reports.submit`.
- **DoD:** matrix permission cocok spec; Admin/QHSSE bisa transition CAPA; tidak ada key hilang.

### WS-2: WorkflowService (engine transisi)  🔴
- `WorkflowService::transition/start/availableTransitions/isTerminalStatus`. Dipakai Incident & Capa.
- **Debug:** kenapa `incident` transition lolos tapi `capa` 403? Cek apakah `transition()` melakukan
  pengecekan permission internal (Gate `core.workflow.transition`) yang gagal untuk capa tapi tidak incident,
  atau `required_permission` di `workflow_transitions` berbeda.
- **Verifikasi:** cek `WorkflowSeeder` transition `capa` vs `incident`; `grep -n "required_permission"`;
  jalankan `WorkflowCoreTest` (sudah PASS → engine ok; masalah di level route/permission, bukan engine).
- **DoD:** engine netral; permission dicek konsisten; CAPA 403 terjelaskan.

### WS-3: Comments & Activity (lokus artifact)  🟡
- `CommentService::add`, `ActivityService::log`. Test gagal di `firstOrFail` pasca-create.
- **Debug:** jika WS-0 konfirmasi artifact → tutup. Jika bug nyata: cek `comments`/`activity_logs`
  relasi `module_name`/`reference_id`, soft-delete, `author()` relation.
- **DoD:** add/list comments & activity bekerja di PG; test hijau di CI PG.

### WS-4: Files / ManagedFileService (private storage security)  🔴
- Upload ke `local` disk (private), download via authorized stream, delete soft + audit.
- **Debug:** walau test gagal (artifact), logika KEAMANAN harus diaudit:
  - Download endpoint cek permission + scope (bukan hanya `core.files.download`)?
  - Path traversal? `stored_name` UUID → aman, tapi `reference_id` bisa di-guess? (UUID incident number).
  - Max 25MB / ext allow — sudah di `MODULE_SPEC §10` untuk Incident; apakah `ManagedFileService`
    enforce global atau per-module? Cek validasi di controller `core.files.store`.
- **Verifikasi:** upload `.exe` → reject (test sudah ada); download user luar scope → 403 (test ada).
- **DoD:** file private tidak bisa diakses tanpa auth; scope ter-enforce; audit `file.*` lengkap.

### WS-5: NumberingService (race / duplicate)  🟡
- `generate('incident', ...)` → `INC-YYYY-NNNN`. Test `duplicate incident_number cannot occur` PASS.
- **Debug:** concurrency (2 request bersamaan) → unique constraint + retry. Cek apakah `generate` pakai
  DB transaction / lock. Di PostgreSQL production, race nyata (bukan sqlite).
- **Verifikasi:** baca `NumberingService::generate`; cek `numbering_formats` seed; tes 2 generate paralel.
- **DoD:** tidak ada duplikat di bawah concurrency PG.

### WS-6: Notifications (NotificationService)  🟡
- `notify`/`notifyMany`; template resolve; recipient by role/scope.
- **Debug:** `IncidentLifecycle` hardcode role `QHSSE Officer/Manager` — cross-check ke
  `RolesAndPermissionsSeeder` nama role persis. Notif `incident.submitted` tes lolos; event lain belum.
- **Verifikasi:** `NotificationCoreTest` (PASS) + manual probe recipient resolution.
- **DoD:** ke-4 event Incident + notif lintas modul ke recipient benar.

### WS-7: AuditTrail & Activity (integrity)  🟢
- `AuditService::created/updated`, `ActivityService::log`. Test `AuditTrailTest` PASS.
- **Debug:** pastikan `old_values`/`new_values` tercatat untuk field berubah (UpdateIncidentReportRequest).
- **DoD:** audit lengkap per BR-06 (Incident) & setara tiap modul.

### WS-8: Query / ListQuery (search/filter/pagination base)  🟡
- `ListQuery::paginate/apply/filters`. Dipakai Index semua modul. Test `ListQueryCompatibilityTest`,
  `SearchFilterExportBaseTest` PASS.
- **Debug:** pastikan filter `occurred_at`/`created_at` + scope tidak bentrok; pagination 15.
- **DoD:** list/filter konsisten di PG.

### WS-9: ScopeService / Authorization (data visibility)  🔴
- `ScopeService` + `core.scope.{own,department,site,company,all}`. Dipakai `IncidentAccess`.
- **Debug:** pastikan `company` scope (Contractor) benar filter via `reporter.company_id`;
  `site` scope QHSSE Officer benar.
- **Verifikasi:** tes positif tiap scope (lihat DEBUG-MODULE1 WS-5).
- **DoD:** tidak ada kebocoran antar scope di PG.

### WS-10: Master Data Models & Seeder  🔴 (terkait Incident WS-1)
- **Bug terdeteksi (dari Incident plan):** `IncidentReportController::store` simpan `category` STRING,
  tapi spec minta `category_id` (FK `categories`). `Category` model punya `module` + `code`.
  **Harus putuskan:** Incident pakai `category` (string code) atau `category_id` (FK)?
- **Seed consistency:** `MODULE_SPEC §5` → kategori `INCIDENT/ENVIRONMENTAL_SPILL/SECURITY_BREACH`
  BELUM di-seed di `QhsseMasterDataSeeder`. `RiskMatrixLevel` sudah di-seed? `Status` sudah?
- **Verifikasi:** `QhsseMasterDataTest` (PASS) → cek isinya cover semua 7 kategori + severity + priority.
  Cek `Category::where('module','incident')->count() == 7`.
- **DoD:** 7 kategori incident ter-seed; relasi `category()` konsisten (Incident pakai FK ATAU string
  disepakati & didokumentasikan); seeder idempoten (`updateOrCreate`).

### WS-11: Users / Employee link (foundation)  🟢
- Sudah diverifikasi di debugging pass (admin↔employee↔dept↔site). `IdentityCoreTest`,
  `OrganizationMasterTest` PASS.
- **Debug:** pastikan `user->employee->department_id`/`site_id` terisi untuk semua role (Contractor
  punya employee?).
- **DoD:** scope resolution tidak null-deref untuk tiap role.

---

## 3. Urutan Eksekusi

1. **WS-0** (konfirmasi artifact) — tentukan apakah 6 failure = noise atau bug.
2. **WS-1 + WS-2** (Permissions + Workflow) — root-cause CAPA 403; dampak lintas modul.
3. **WS-10** (MasterData Category) — selaraskan dengan Incident WS-1.
4. **WS-4** (Files security) — keamanan private storage.
5. **WS-9** (Scope) — kebocoran data.
6. **WS-3/5/6/7/8/11** — service lain (sebagian besar sudah hijau, verifikasi ringan).

---

## 4. Commands Verifikasi (re-runnable)

```bash
# WS-0: artifact check vs PostgreSQL
cp phpunit.xml phpunit.production.xml   # edit: DB_CONNECTION=pgsql, SESSION_DRIVER=database
php artisan test tests/Feature/Core --configuration=phpunit.production.xml

# WS-1: permission matrix
grep -rn "capa.actions\|incident.reports" app/Core/Permissions database/seeders
php artisan tinker --execute="echo User::find(1)->can('capa.actions.start') ? 'YES':'NO'; echo PHP_EOL;"

# WS-2: workflow transitions seeded
php artisan tinker --execute="App\Models\Core\Workflow\WorkflowInstance::where('module_name','capa')->count();"

# WS-10: master data seed
php artisan tinker --execute="echo App\Models\Core\MasterData\Category::where('module','incident')->count();"

# Full Core (sqlite, known artifact)
php artisan test tests/Feature/Core
```

---

## 5. Definition of Done (Core & Master Total)

- [ ] WS-0: 6 Core failure tertulis artifact vs bug; CI PG profile ada.
- [ ] WS-1: permission `capa.actions.*`/`incident.reports.*` cocok role matrix; Admin/QHSSE bisa transition.
- [ ] WS-2: WorkflowService netral; CAPA 403 terjelaskan & diperbaiki.
- [ ] WS-3: Comments/Activity hijau di PG.
- [ ] WS-4: file private aman (auth+scope+audit); ext/size enforce.
- [ ] WS-5: numbering tanpa duplikat di concurrency PG.
- [ ] WS-6: notif ke recipient benar (role name match seeder).
- [ ] WS-7: audit trail lengkap.
- [ ] WS-8: ListQuery filter/pagination konsisten.
- [ ] WS-9: scope own/dept/site/company/all tidak bocor.
- [ ] WS-10: 7 kategori incident ter-seed; `category`/`category_id` konsisten dgn Incident.
- [ ] WS-11: user↔employee link utuh tiap role.
- [ ] Handoff `DEBUG-CORE-MASTER-HANDOFF.md` + changelog + decision log.

---

## 6. Catatan Jujur / Deferred

- 6 Core failure **kemungkinan besar artifact** (bukan bug) — WS-0 akan memastikan. Jangan asal "fix"
  kode yang sudah benar hanya untuk membuat test sqlite lulus.
- `phpunit.xml` sqlite `:memory:` adalah sumber banyak false signal di seluruh suite; WS-0 + `phpunit.production.xml`
  adalah perbaikan sistemik terpenting.
- Beberapa Core service (Import, Exports, Dashboard) belum punya test dedicated — masuk deferred jika
  digunakan modul bisnis nanti.
- Plan ini BUKAN eksekusi; tidak ada kode diubah sampai per WS dilakukan dengan root-cause evidence.
