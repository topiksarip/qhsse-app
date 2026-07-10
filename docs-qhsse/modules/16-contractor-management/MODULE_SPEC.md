# Module Spec — Contractor Management (CSMS)

> **Module ID:** `16-contractor-management`  
> **Module Code (numbering):** `contractor`  
> **Number Prefix:** `CTR`  
> **Phase:** Phase 16 — Contractor Management  
> **Status:** Ready for coding

---

## 1. Tujuan Modul

Modul Contractor Management (Contractor Safety Management System / CSMS) menyediakan sistem pengelolaan kontraktor secara end-to-end, terintegrasi dengan Core Foundation dan modul QHSSE lainnya. Modul ini mengelola registrasi kontraktor, prequalification, evaluasi kinerja keselamatan, dan keterkaitan dengan izin kerja (PTW), insiden, serta audit.

Tujuan utama:

- Mendaftarkan kontraktor dengan nomor unik `CTR-YYYY-NNNN` yang di-generate otomatis pada saat create.
- Mengaitkan kontraktor ke data perusahaan (`companies`) yang sudah ada di Core.
- Mengelola status prequalification: tidak memenuhi syarat → prequalified (dengan masa berlaku `prequalified_until`).
- Menjalankan evaluasi kinerja kontraktor secara berkala dengan kriteria penilaian terstruktur (JSON criteria).
- Menghitung `safety_rating` otomatis berdasarkan rata-rata skor evaluasi terbaru.
- Menampilkan badge prequalification di halaman Index (hijau = prequalified, kuning = akan kedaluwarsa, merah = expired, abu-abu = not prequalified).
- Menampilkan riwayat evaluasi, skor keselamatan, izin kerja (PTW) terkait, dan insiden terkait di halaman Show.
- Menyediakan audit trail lengkap untuk semua perubahan kritikal (create, update, evaluate, prequalify).
- Mengirim notifikasi ke QHSSE Officer/Manager saat kontraktor baru didaftarkan, saat evaluasi selesai, dan saat prequalification akan kedaluwarsa.
- Menyediakan dashboard metrics dan export CSV untuk pelaporan manajemen.

---

## 2. Dependency

### Core Foundation (Phase 0 — COMPLETE)

| Dependency | Usage in This Module |
|---|---|
| **Auth** | User login, session, role identification |
| **PermissionService** (Spatie) | 5 permission keys `contractor.management.*` |
| **NumberingService** | Generate `CTR-YYYY-NNNN` on create |
| **FileService** | Upload/download dokumen prequalification via `managed_files` table |
| **NotificationService** | In-app + email notifications via `core_notifications` table |
| **AuditTrailService** | Audit log via `audit_logs` table |
| **CommentService** | Comments via `comments` table (`module_name='contractor'`) |
| **ActivityLogService** | Activity timeline via `activity_logs` table |
| **CsvExporter** | CSV export via `contractor.management.export` permission |
| **ListQuery** | Paginated, searchable, sortable list |
| **MasterData** | Companies, Users |

### Cross-Module Dependencies

| Module | Relationship |
|---|---|
| `09-permit-to-work` | Kontraktor terkait dengan izin kerja via `contractor_id` → `companies.id`. Halaman Show menampilkan daftar PTW yang dikaitkan dengan kontraktor. |
| `01-incident-reporting` | Kontraktor dapat menjadi pihak dalam insiden. Halaman Show menampilkan daftar insiden terkait berdasarkan `company_id`. |
| `06-audit-management` | Kontraktor dapat menjadi subjek audit supplier. Halaman Show menampilkan audit supplier terkait. |
| `08-training-competency` | Verifikasi training kompetensi pekerja kontraktor (future integration). |
| `10-environmental-management` | Kontraktor dapat terlibat dalam kejadian lingkungan (future integration). |

### Tech Stack

- Laravel 12 (backend: Form Request, Policy, Service, Eloquent)
- Inertia.js React + TypeScript (frontend)
- Tailwind CSS (styling, UI in Indonesian)
- PostgreSQL (data layer)
- Spatie Laravel Permission (RBAC)

---

## 3. User Roles

10 roles terlibat dalam modul ini:

| # | Role | Deskripsi Peran dalam Contractor Management |
|---|---|---|
| 1 | **Super Admin** | Akses penuh ke semua data dan fungsi. Bypass scope. |
| 2 | **Admin** | Akses sistem penuh termasuk konfigurasi, numbering, master data. |
| 3 | **QHSSE Manager** | Create, update, evaluate, prequalify, export contractor. Scope: all sites. |
| 4 | **QHSSE Officer** | Create, update, evaluate contractor. Scope: assigned site(s). |
| 5 | **Supervisor** | View contractor di department-nya. Scope: department. |
| 6 | **Department Head** | View contractor di department-nya. Scope: department. |
| 7 | **Employee/Reporter** | View contractor. Scope: own department. |
| 8 | **Contractor** | View data kontraktornya sendiri. Scope: company. |
| 9 | **Auditor** | View-only semua data dalam scope audit. Export. Tidak create/edit. |
| 10 | **Top Management** | View dashboard & report, export. Scope: all. Tidak create/edit. |

---

## 4. Fitur Lengkap

### 4.1 Contractor CRUD

- **Create** — Form pendaftaran kontraktor. Nomor `CTR-YYYY-NNNN` di-generate otomatis oleh `NumberingService` pada saat record dibuat. Status awal: `active`. `is_prequalified` default `false`.
- **List** — Halaman list dengan search (nomor, nama perusahaan, contact person), filter (status, prequalification status, service type), pagination (default 15 per page), dan tombol Export CSV. Menampilkan prequalification badge.
- **Detail** — Halaman detail menampilkan: nomor, info perusahaan, contact person, service type, safety rating, prequalification status, evaluasi history, linked PTW, linked incidents, linked audits, attachments, comments, activity log, audit trail.
- **Update** — Edit record. Bisa edit semua field kecuali `contractor_number` (immutable setelah generate).
- **Delete** — Soft delete. Hanya Super Admin / Admin. Tidak bisa delete contractor yang sedang prequalified atau memiliki PTW active.

### 4.2 Prequalification Management

- Kontraktor memiliki `is_prequalified` (boolean) dan `prequalified_until` (date nullable).
- Prequalification di-set manual oleh QHSSE Manager/Officer setelah verifikasi dokumen dan evaluasi.
- Prequalification status di halaman Index:
  - **Prequalified** — `is_prequalified = true` dan `prequalified_until` > 30 hari dari sekarang. Badge hijau.
  - **Expiring Soon** — `is_prequalified = true` dan `prequalified_until` ≤ 30 hari dari sekarang. Badge kuning.
  - **Expired** — `is_prequalified = true` dan `prequalified_until` < sekarang. Badge merah.
  - **Not Prequalified** — `is_prequalified = false`. Badge abu-abu.
- Scheduled job mengecek prequalification expiry dan mengirim notifikasi 30 hari sebelum `prequalified_until`.

### 4.3 Evaluation Management

- Evaluasi kinerja kontraktor dilakukan secara berkala.
- Setiap evaluasi memiliki: tanggal evaluasi, evaluator (FK→users), criteria (JSON), total_score (decimal), result (pass/conditional/fail), notes.
- `criteria` JSON menyimpan skor per kriteria, contoh:
  ```json
  {
    "compliance_dokumen": 20,
    "rekam_jejak_keselamatan": 25,
    "kompetensi_personel": 20,
    "ketersediaan_apd": 15,
    "program_k3": 20
  }
  ```
- `total_score` = jumlah dari semua skor criteria (0-100).
- `result` di-derive dari `total_score`:
  - `pass` — total_score ≥ 80
  - `conditional` — total_score 60-79
  - `fail` — total_score < 60
- `safety_rating` di contractor di-update otomatis setelah evaluasi:
  - `excellent` — rata-rata skor ≥ 85
  - `good` — rata-rata skor 70-84
  - `fair` — rata-rata skor 55-69
  - `poor` — rata-rata skor < 55
- `safety_rating` dihitung dari rata-rata `total_score` 3 evaluasi terbaru.

### 4.4 Linked Records Display

- **Linked PTW** — Menampilkan semua permit yang memiliki `contractor_id` = `contractor.company_id`. Dapat filter by status.
- **Linked Incidents** — Menampilkan semua incident yang memiliki `contractor_id` = `contractor.company_id`. Dapat filter by severity.
- **Linked Audits** — Menampilkan audit dengan type `supplier` yang terkait dengan company. (Future: jika audit memiliki `supplier_id` FK).

### 4.5 Evidence Management

- Upload dokumen prequalification (SPPKP, sertifikat SMK3, sertifikat ISO, daftar tenaga kerja, daftar APD, dsb.) melalui File Service core.
- Collection: `prequalification`, `evaluation`, `supporting_docs`.
- Multiple files per contractor.
- Download melalui authorized endpoint (permission check).

### 4.6 Comments & Activity Timeline

- Comment dapat ditambahkan oleh user yang punya akses ke record.
- Activity log otomatis mencatat: create, update, evaluate, prequalify, revoke prequalification.
- Timeline ditampilkan di halaman detail.

### 4.7 Notification

- 4 event notifikasi: `contractor.registered`, `contractor.evaluated`, `contractor.prequalified`, `contractor.expiring_soon`.
- In-app notification via `core_notifications` table.
- Email notification jika configured.

### 4.8 Dashboard & Reporting

- Dashboard widget: total contractor, prequalified count, expiring soon, expired, not prequalified, average safety rating.
- Export CSV dengan kolom yang dispesifikasikan di Section 14.

---

## 5. Business Rules

### BR-01: Numbering on Create

- Nomor contractor di-generate **saat record dibuat** (POST create).
- Format: `CTR-YYYY-NNNN` (contoh: `CTR-2026-0001`).
- Sumber: `NumberingService::generate('contractor', $actor)`.
- Konfigurasi numbering (sudah di-seed di `numbering_formats`):
  - `module_name`: `contractor`
  - `prefix`: `CTR`
  - `padding`: `4`
  - `separator`: `-`
  - `reset_frequency`: `yearly`
  - `include_year`: `true`
  - `include_site_code`: `false`
  - `sample`: `CTR-2026-0001`
- Nomor bersifat **unique**. Jika terjadi race condition, database unique constraint mencegah duplikat; service melakukan retry dengan increment.
- Nomor tidak dapat diubah setelah di-generate.

### BR-02: Company Linkage

- Setiap contractor **wajib** dikaitkan ke satu `company_id` (FK→companies).
- Satu perusahaan hanya bisa menjadi satu contractor aktif (unique constraint: `company_id` + `status = 'active'`).
- Jika perusahaan sudah terdaftar sebagai contractor aktif, create ditolak dengan error: "Perusahaan ini sudah terdaftar sebagai kontraktor aktif."
- Company harus memiliki `type = 'contractor'` atau `type = 'vendor'` di master data.

### BR-03: Prequalification Rules

- `is_prequalified` hanya bisa di-set ke `true` oleh QHSSE Manager atau QHSSE Officer.
- Saat mengaktifkan prequalification, `prequalified_until` wajib diisi (date di masa depan).
- Saat menonaktifkan prequalification (revoke), `is_prequalified` di-set ke `false` dan `prequalified_until` di-set ke `NULL`.
- Contractor yang tidak prequalified tidak dapat diajukan PTW (validasi di modul PTW, bukan di modul ini).

### BR-04: Evaluation Rules

- Evaluasi hanya dapat dilakukan oleh user dengan permission `contractor.management.evaluate`.
- `criteria` wajib diisi (JSON object dengan minimal 1 key).
- `total_score` dihitung otomatis dari jumlah nilai criteria di controller (bukan di DB).
- `result` di-derive otomatis dari `total_score`:
  - `pass` — total_score ≥ 80
  - `conditional` — total_score 60-79
  - `fail` — total_score < 60
- Setelah evaluasi disimpan, `safety_rating` di contractor di-update otomatis berdasarkan rata-rata 3 evaluasi terbaru.
- Jika jumlah evaluasi < 3, `safety_rating` dihitung dari rata-rata evaluasi yang ada.
- Jika belum ada evaluasi, `safety_rating` = `NULL`.

### BR-05: Safety Rating Calculation

```php
$evaluations = $contractor->evaluations()
    ->orderBy('evaluation_date', 'desc')
    ->limit(3)
    ->get();

if ($evaluations->isEmpty()) {
    $safetyRating = null;
} else {
    $avgScore = $evaluations->avg('total_score');
    $safetyRating = match (true) {
        $avgScore >= 85 => 'excellent',
        $avgScore >= 70 => 'good',
        $avgScore >= 55 => 'fair',
        default         => 'poor',
    };
}

$contractor->update(['safety_rating' => $safetyRating]);
```

### BR-06: Audit Trail on Critical Changes

Audit trail dicatat untuk event berikut (via `audit_logs` table, `module_name='contractor'`):

| Event | Auditable | Old/New Values |
|---|---|---|
| `contractor.created` | Contractor record | new_values: all fields |
| `contractor.updated` | Contractor record | changed fields only |
| `contractor.evaluated` | ContractorEvaluation record | new_values: all fields |
| `contractor.prequalified` | Contractor record | is_prequalified, prequalified_until |
| `contractor.prequalification_revoked` | Contractor record | is_prequalified, prequalified_until |
| `contractor.safety_rating_updated` | Contractor record | safety_rating old → new |
| `contractor.file.uploaded` | ManagedFile | new_values |
| `contractor.file.downloaded` | ManagedFile | metadata: user, ip |

### BR-07: Data Visibility by Scope

Data visibility mengikuti role scope (sesuai `CorePermissions::roleMap()`):

| Scope | Who | What They See |
|---|---|---|
| `own` | Employee/Reporter | All contractors (view only) |
| `department` | Supervisor, Department Head | All contractors (view only) |
| `site` | QHSSE Officer | All contractors in assigned site(s) |
| `company` | Contractor | Only their own contractor record |
| `all` | QHSSE Manager, Top Management, Auditor, Super Admin, Admin | All contractors |

- Scope check dilakukan **server-side** di Controller/Policy.
- Contractor hanya melihat record milik company-nya sendiri (via `users.company_id`).

### BR-08: Service Type

- `service_type` adalah free-text string (max 255) yang mendeskripsikan jenis layanan kontraktor.
- Contoh: "Konstruksi Sipil", "Mechanical & Piping", "Electrical", "Scaffolding", "Cleaning Service", "Security", "Transportasi", "Maintenance", "General Contractor".
- Tidak ada enum constraint — fleksibel untuk berbagai jenis kontraktor.
- Dapat di-filter di halaman Index (dropdown berisi unique service_type values).

---

## 6. Permission Keys

5 permission keys untuk modul Contractor Management:

| # | Permission Key | Description |
|---|---|---|
| 1 | `contractor.management.view` | View contractor list and detail. Scope-filtered. |
| 2 | `contractor.management.create` | Create new contractor record. Generates CTR number. |
| 3 | `contractor.management.update` | Update contractor record. Set/unset prequalification. |
| 4 | `contractor.management.evaluate` | Create evaluation for contractor. Triggers safety_rating recalculation. |
| 5 | `contractor.management.export` | Export contractor list to CSV. Scope-filtered. |

### Implementation Notes

- Permission keys mengikuti format `{module}.{entity}.{action}` → `contractor.management.*`.
- Keys harus di-register di `CorePermissions::all()`.
- Keys di-assign ke roles via `CorePermissions::roleMap()`.
- File upload/download menggunakan permission core: `core.files.upload`, `core.files.download`.
- Comment menggunakan permission core: `core.comments.view`, `core.comments.create`.

---

## 7. Role-Permission Matrix

| Role | `view` | `create` | `update` | `evaluate` | `export` |
|---|:---:|:---:|:---:|:---:|:---:|
| Super Admin | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Manager | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Officer | ✅ | ✅ | ✅ | ✅ | ✅ |
| Supervisor | ✅ | ❌ | ❌ | ❌ | ✅ |
| Department Head | ✅ | ❌ | ❌ | ❌ | ✅ |
| Employee/Reporter | ✅ | ❌ | ❌ | ❌ | ❌ |
| Contractor | ✅ | ❌ | ❌ | ❌ | ❌ |
| Auditor | ✅ | ❌ | ❌ | ❌ | ✅ |
| Top Management | ✅ | ❌ | ❌ | ❌ | ✅ |

### Notes

- **QHSSE Officer** dan **QHSSE Manager** dapat melakukan full lifecycle: create, update, evaluate, set prequalification.
- **Supervisor** dan **Department Head** dapat view + export (read-only).
- **Employee/Reporter** dan **Contractor** hanya view (read-only, scope-limited).
- **Auditor** dan **Top Management** view + export (read-only).
- Semua permission di-enforce **server-side** via Laravel Policy atau Gate.

---

## 8. Notification Events

4 event notifikasi untuk modul Contractor Management:

### 8.1 `contractor.registered`

| Property | Value |
|---|---|
| **Trigger** | User membuat contractor baru (POST create) |
| **Recipients** | All users with role `QHSSE Officer` and `QHSSE Manager` |
| **Type** | `contractor.registered` |
| **Title (template)** | `Kontraktor Baru Terdaftar: {contractor.contractor_number}` |
| **Message (template)** | `{actor.name} telah mendaftarkan kontraktor {contractor.contractor_number} — {company.name} ({contractor.service_type}). Mohon lakukan verifikasi dan prequalification.` |
| **Action URL** | `/contractors/{contractor.id}` |
| **Module/Reference** | `module_name='contractor'`, `reference_id={contractor.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 8.2 `contractor.evaluated`

| Property | Value |
|---|---|
| **Trigger** | User menambahkan evaluasi baru untuk contractor (POST evaluate) |
| **Recipients** | QHSSE Manager(s), contractor creator |
| **Type** | `contractor.evaluated` |
| **Title (template)** | `Evaluasi Kontraktor Selesai: {contractor.contractor_number}` |
| **Message (template)** | `Evaluasi kinerja kontraktor {contractor.contractor_number} — {company.name} telah dilakukan oleh {evaluator.name}. Skor: {evaluation.total_score}/100 ({evaluation.result}). Safety rating: {contractor.safety_rating}.` |
| **Action URL** | `/contractors/{contractor.id}` |
| **Module/Reference** | `module_name='contractor'`, `reference_id={contractor.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 8.3 `contractor.prequalified`

| Property | Value |
|---|---|
| **Trigger** | QHSSE Manager/Officer mengaktifkan prequalification |
| **Recipients** | Contractor creator, related supervisors |
| **Type** | `contractor.prequalified` |
| **Title (template)** | `Kontraktor Memenuhi Syarat: {contractor.contractor_number}` |
| **Message (template)** | `Kontraktor {contractor.contractor_number} — {company.name} telah memenuhi syarat prequalification hingga {contractor.prequalified_until}.` |
| **Action URL** | `/contractors/{contractor.id}` |
| **Module/Reference** | `module_name='contractor'`, `reference_id={contractor.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 8.4 `contractor.expiring_soon`

| Property | Value |
|---|---|
| **Trigger** | Scheduled job: 30 hari sebelum `prequalified_until` jika masih prequalified |
| **Recipients** | QHSSE Officer di site terkait, QHSSE Manager, contractor creator |
| **Type** | `contractor.expiring_soon` |
| **Title (template)** | `Prequalification Kontraktor Akan Kedaluwarsa: {contractor.contractor_number}` |
| **Message (template)** | `Prequalification kontraktor {contractor.contractor_number} — {company.name} akan kedaluwarsa dalam 30 hari pada {contractor.prequalified_until}. Mohon lakukan evaluasi ulang.` |
| **Action URL** | `/contractors/{contractor.id}` |
| **Module/Reference** | `module_name='contractor'`, `reference_id={contractor.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### Implementation Notes

- Notification dikirim setelah DB transaction commit (use Laravel Event/Listener or Observer pattern).
- In-app notification: sync write to `core_notifications`.
- Email notification: dispatch to queue if available, fallback sync.
- `contractor.expiring_soon` dijalankan via scheduled command (Laravel Scheduler, setiap hari jam 08:00).

---

## 9. File Attachment Rules

- Attachment memakai File Service core (`ManagedFileService`).
- `module_name = 'contractor'`, `reference_id = contractor.id`.
- Collections:
  - `prequalification` — dokumen prequalification (SPPKP, sertifikat SMK3, sertifikat ISO, dll.)
  - `evaluation` — dokumen pendukung evaluasi
  - `supporting_docs` — dokumen pendukung lainnya
- File sensitif mengikuti permission record.
- Evidence tidak boleh dihapus setelah contractor prequalified kecuali admin berwenang.
- Maksimum ukuran file: 10MB per file.
- Format yang diizinkan: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG.

---

## 10. File Structure

```
app/Models/Modules/ContractorManagement/
    Contractor.php
    ContractorEvaluation.php
app/Http/Controllers/Modules/ContractorManagement/
    ContractorController.php
    ContractorEvaluationController.php
app/Http/Requests/Modules/ContractorManagement/
    StoreContractorRequest.php
    UpdateContractorRequest.php
    StoreContractorEvaluationRequest.php
    UpdateContractorPrequalificationRequest.php
app/Policies/Modules/ContractorManagement/
    ContractorPolicy.php
database/migrations/
    2026_07_11_000001_create_contractors_table.php
    2026_07_11_000002_create_contractor_evaluations_table.php
database/factories/Modules/ContractorManagement/
    ContractorFactory.php
    ContractorEvaluationFactory.php
database/seeders/
    ContractorManagementSeeder.php
resources/js/Pages/Modules/ContractorManagement/
    Index.tsx
    Form.tsx
    Show.tsx
tests/Feature/Modules/ContractorManagement/
    ContractorManagementTest.php
docs-qhsse/modules/16-contractor-management/
    MODULE_SPEC.md
    DATA_MODEL.md
    UI_PAGES.md
    API_CONTRACT.md
    TEST_CASES.md
    WORKFLOW.md
```

---

## 11. Categori & Service Types

Kategori kontraktor berdasarkan `service_type` (free-text, contoh nilai):

| Kategori | Deskripsi |
|---|---|
| Konstruksi Sipil | Pekerjaan konstruksi, beton, struktur |
| Mechanical & Piping | Pekerjaan mekanikal, piping, fabricasi |
| Electrical | Pekerjaan kelistrikan, instalasi panel |
| Scaffolding | Penyediaan scaffolding dan akses ketinggian |
| Cleaning Service | Kebersihan area, housekeeping |
| Security | Jasa keamanan |
| Transportasi | Angkutan, logistik |
| Maintenance | Pemeliharaan fasilitas, equipment |
| General Contractor | Kontraktor umum, multi-disiplin |
| Lainnya | Jenis layanan lain |

---

## 12. Dashboard Metrics

| Metric | Query | Display |
|---|---|---|
| Total Contractor | `Contractor::count()` | Number widget |
| Prequalified | `Contractor::where('is_prequalified', true)->where('prequalified_until', '>', now())->count()` | Number widget (hijau) |
| Expiring Soon (≤30 hari) | `Contractor::where('is_prequalified', true)->where('prequalified_until', '>', now())->where('prequalified_until', '<=', now()->addDays(30))->count()` | Number widget (kuning) |
| Expired | `Contractor::where('is_prequalified', true)->where('prequalified_until', '<', now())->count()` | Number widget (merah) |
| Not Prequalified | `Contractor::where('is_prequalified', false)->count()` | Number widget (abu-abu) |
| Average Safety Rating | Avg `total_score` dari evaluasi terbaru per contractor | Gauge chart |
| Trend Evaluasi per Bulan | Group by month | Line chart |
| Breakdown by Service Type | Group by `service_type` | Pie chart |
| Breakdown by Safety Rating | Group by `safety_rating` | Bar chart |

---

## 13. Report / Export

- Export list Excel/CSV dengan filter yang sama dengan halaman Index.
- PDF detail/report untuk record kontraktor bila dibutuhkan.
- Filter export mengikuti filter list.

---

## 14. Export Specification

Endpoint: `GET /contractors/export`

Applies same ListQuery filters, then streams CSV via `CsvExporter`.

### CSV Columns:

| Column Header | Source |
|---|---|
| `Nomor` | `contractor_number` |
| `Perusahaan` | `company.name` |
| `Contact Person` | `contact_person` |
| `Telepon` | `contact_phone` |
| `Email` | `contact_email` |
| `Jenis Layanan` | `service_type` |
| `Safety Rating` | `safety_rating` |
| `Prequalified` | `is_prequalified` (Ya/Tidak) |
| `Berlaku Sampai` | `prequalified_until` (formatted: Y-m-d) |
| `Status` | `status` |
| `Tanggal Dibuat` | `created_at` (formatted: Y-m-d H:i) |

---

## 15. Acceptance Criteria

1. User dengan permission `contractor.management.create` dapat membuat record kontraktor dengan nomor auto-generated.
2. User tanpa permission ditolak (403).
3. Nomor `CTR-YYYY-NNNN` di-generate otomatis dan unique.
4. Contractor wajib dikaitkan ke `companies` yang sudah ada (FK).
5. Satu perusahaan tidak bisa menjadi dua contractor aktif.
6. Prequalification dapat di-set/unset oleh QHSSE Manager/Officer.
7. Evaluasi menyimpan criteria JSON, menghitung total_score, menentukan result (pass/conditional/fail).
8. Safety rating di-update otomatis setelah evaluasi (excellent/good/fair/poor).
9. Halaman Show menampilkan evaluasi history, linked PTW, linked incidents.
10. List dapat search/filter/pagination dengan badge prequalification.
11. Export CSV menghasilkan data sesuai filter dan permission.
12. Audit trail tercatat untuk semua perubahan kritikal.
13. Notification terkirim ke penerima tepat.
14. Scheduled job mengecek prequalification expiry dan mengirim notifikasi.

---

## 16. Open Questions

- Field mandatory final per perusahaan/site (misal: wajib NPWP, alamat lengkap perusahaan).
- Approval path untuk prequalification (siapa yang approve final — QHSSE Manager saja atau melibatkan Procurement?).
- Template report final (format PDF untuk laporan kontraktor).
- SLA default untuk evaluasi ulang (setiap 6 bulan? 12 bulan?).
- Integrasi training competency: apakah pekerja kontraktor wajib terdaftar di modul training?
- Data sensitif yang perlu pembatasan tambahan (misal: data finansial kontraktor).
- Apakah perlu menyimpan history perubahan prequalification (log kapan di-set, di-revoke, di-extend)?
