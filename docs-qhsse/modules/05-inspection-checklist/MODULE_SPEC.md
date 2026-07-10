# Module Spec — Inspection Checklist

> **Module ID:** `05-inspection-checklist`
> **Module Code (numbering):** `inspection`
> **Number Prefix:** `INS`
> **Workflow Code:** `INSPECTION_WORKFLOW`
> **Phase:** Phase 4 (Inspection Checklist — depends on Core + CAPA)
> **Status:** Ready for coding

---

## 1. Tujuan Modul

Modul Inspection Checklist menyediakan sistem manajemen checklist inspeksi QHSSE secara end-to-end. Modul ini terdiri dari dua kelompok resource: **Template** (definisi checklist) dan **Inspection** (eksekusi inspeksi berdasarkan template).

Tujuan utama:

- Memungkinkan **QHSSE Officer/Manager** membuat **template inspeksi** yang berisi item-item pertanyaan dengan berbagai tipe jawaban (Yes/No, Safe/Unsafe, NA, Scale, Text).
- Memungkinkan **inspector** menjalankan inspeksi berdasarkan template yang sudah didefinisikan, mencatat hasil per item, dan menandai item yang *unsafe*.
- Menghasilkan **nomor unik** inspeksi (`INS-YYYY-NNNN`) yang di-generate otomatis pada saat create.
- Menyediakan **workflow status** yang jelas: `pending` → `in_progress` (start) → `completed` (complete).
- Menampilkan **ringkasan hasil inspeksi** di halaman Show, dengan item *unsafe* di-*highlight* dan link untuk membuat CAPA otomatis.
- Mengirim **notifikasi** ke QHSSE Manager saat inspeksi selesai dan saat ada item *unsafe*.
- Menyediakan **audit trail** lengkap untuk semua perubahan kritikal (create template, create inspection, start, complete, result changes).
- Menghubungkan ke modul **CAPA** (module `04`) ketika ditemukan item *unsafe* pada hasil inspeksi.

---

## 2. Dependency

### Core Foundation (Phase 0 — COMPLETE)

| Dependency | Usage in This Module |
|---|---|
| **Auth** | User login, session, role identification |
| **PermissionService** (Spatie) | Permission keys `inspection.checklists.*` + `inspection.results.*` |
| **NumberingService** | Generate `INS-YYYY-NNNN` on inspection create |
| **WorkflowService** | Status transitions per `INSPECTION_WORKFLOW` definition |
| **FileService** | Upload/download evidence photos via `managed_files` table |
| **NotificationService** | In-app + email notifications |
| **AuditTrailService** | Audit log via `audit_logs` table |
| **CommentService** | Comments via `comments` table (`module_name='inspection'`) |
| **ActivityLogService** | Activity timeline via `activity_logs` table |
| **ExportService** | CSV export via `inspection.checklists.export` permission |
| **MasterData** | Sites, Areas, Departments, Users |

### Cross-Module

| Module | Relationship |
|---|---|
| `04-capa-action-tracking` | Inspection result with `is_unsafe=true` → suggest creating CAPA record (`source_module='inspection'`, `source_reference_id=inspection.id`) |

### Tech Stack

- Laravel 12 (backend: Form Request, Policy, Service, Eloquent)
- Inertia.js React + TypeScript (frontend)
- Tailwind CSS (styling, UI in Indonesian)
- PostgreSQL (data layer)
- Spatie Laravel Permission (RBAC)

---

## 3. User Roles

| # | Role | Deskripsi Peran dalam Inspection Checklist |
|---|---|---|
| 1 | **Super Admin** | Akses penuh ke semua data dan fungsi. Bypass scope. |
| 2 | **Admin** | Akses sistem penuh termasuk konfigurasi template dan master data. |
| 3 | **QHSSE Manager** | Create/update/delete template, view all inspections, export. Scope: all sites. |
| 4 | **QHSSE Officer** | Create/update template, create/execute inspection, view results. Scope: assigned site(s). |
| 5 | **Supervisor** | View inspections di department-nya. Tidak bisa create template. Scope: department. |
| 6 | **Department Head** | View inspections di department-nya. Scope: department. |
| 7 | **Employee/Reporter** | Tidak memiliki akses ke modul ini (inspeksi adalah ranah QHSSE Officer/Manager). |
| 8 | **Contractor** | Tidak memiliki akses ke modul ini. |
| 9 | **Auditor** | View-only semua data dalam scope audit. Export. Tidak create/edit. |
| 10 | **Top Management** | View dashboard & report, export. Scope: all. Tidak create/edit. |

---

## 4. Fitur Lengkap

### 4.1 Inspection Template CRUD (Resource Group: Templates)

- **Create Template** — Form pembuatan template inspeksi. Fields: code, name, description, category, is_active. Bisa menambahkan multiple inspection items.
- **List Templates** — Halaman list dengan search (code, name), filter (category, is_active), pagination (default 15), tombol Export CSV.
- **Detail Template** — Halaman detail menampilkan: code, name, description, category, items list, status aktif/non-aktif, created/updated info.
- **Update Template** — Edit template dan items-nya. Bisa add/remove/reorder items.
- **Delete Template** — Soft delete. Hanya Super Admin / Admin / QHSSE Manager. Tidak bisa delete template yang sudah dipakai inspection (ada relasi).
- **Toggle Active** — Activate/deactivate template tanpa delete.

### 4.2 Inspection Item Management (dalam Template Form)

Setiap item dalam template memiliki:
- **Question** — Pertanyaan inspeksi (wajib).
- **Type** — Tipe jawaban: `yes_no`, `safe_unsafe`, `na`, `scale`, `text`.
- **Category** — Kategori item (opsional, untuk grouping di report).
- **Is Required** — Apakah item wajib dijawab.
- **Order** — Urutan item dalam template.

### 4.3 Inspection Execution (Resource Group: Inspections)

- **Create Inspection** — Pilih template, site, area (opsional), inspector, scheduled date. Nomor `INS-YYYY-NNNN` di-generate otomatis. Status awal: `pending`.
- **List Inspections** — Halaman list dengan search (nomor, template name), filter (site, status, template, date range), pagination, tombol Export CSV.
- **Execute Inspection** — Form eksekusi inspeksi: tampilkan semua items dari template, inspector menjawab setiap item (Yes/No/NA/Unsafe + remark). Bisa upload photo evidence per item.
- **Start Inspection** — Transition `pending` → `in_progress`. Set `executed_at`.
- **Complete Inspection** — Transition `in_progress` → `completed`. Auto-calculate `overall_result` (fail jika ada item `unsafe`, pass jika semua aman, pending jika belum semua dijawab).
- **Show Inspection** — Halaman detail menampilkan: nomor, template, site/area, inspector, scheduled/executed date, status, overall result, results per item (dengan item *unsafe* di-highlight), link ke CAPA jika ada item unsafe.

### 4.4 Inspection Result

- Setiap item dalam inspection memiliki `answer` (string), `remark` (text opsional), dan `is_unsafe` (boolean).
- Jika tipe item `safe_unsafe` dan jawaban `unsafe`, maka `is_unsafe` = true.
- Jika tipe item `yes_no` dan jawaban `no`, maka `is_unsafe` = true (opsional, tergantung konfigurasi).
- Item dengan `is_unsafe=true` ditandai dengan badge merah dan link "Buat CAPA".

### 4.5 CAPA Integration

- Jika ada item `is_unsafe=true` pada inspection yang `completed`, sistem menampilkan tombol **"Buat CAPA"** di halaman Show.
- Klik tombol → redirect ke form CAPA dengan pre-fill: `source_module='inspection'`, `source_reference_id={inspection.id}`, description auto-filled dengan detail item unsafe.
- Satu inspection bisa menghasilkan multiple CAPA records (satu per item unsafe, atau satu gabungan).

### 4.6 Evidence Management

- Upload photo bukti per item atau per inspection melalui File Service core.
- Collection: `evidence`.
- Multiple files per inspection.
- Download melalui authorized endpoint.

### 4.7 Notification

- 3 event notifikasi: `inspection.completed`, `inspection.unsafe_found`, `inspection.overdue`.
- In-app notification via `core_notifications` table.
- Email notification jika configured.

### 4.8 Dashboard & Reporting

- Dashboard widget: total inspections, breakdown by status/template/site, trend bulanan, unsafe count.
- Export CSV dengan kolom yang dispesifikasikan di Section 12.

---

## 5. Kategori Template

Kategori template inspeksi (di-seed di tabel `categories` dengan `module='inspection'`):

| # | Code | Nama | Deskripsi |
|---|---|---|---|
| 1 | `SAFETY` | **Safety Inspection** | Inspeksi keselamatan kerja: APD, kondisi kerja, prosedur safety. |
| 2 | `ENVIRONMENT` | **Environmental Inspection** | Inspeksi lingkungan: waste management, emisi, spill prevention. |
| 3 | `EQUIPMENT` | **Equipment Inspection** | Inspeksi peralatan: kondisi, kalibrasi, maintenance. |
| 4 | `FIRE` | **Fire Safety Inspection** | Inspeksi keselamatan kebakaran: APAR, sprinkler, emergency exit. |
| 5 | `HOUSEKEEPING` | **Housekeeping Inspection** | Inspeksi kerapian dan kebersihan area kerja (5S). |
| 6 | `SECURITY` | **Security Inspection** | Inspeksi keamanan: access control, CCTV, perimeter. |
| 7 | `QUALITY` | **Quality Inspection** | Inspeksi kualitas: produk, proses, dokumentasi. |
| 8 | `COMPLIANCE` | **Compliance Inspection** | Inspeksi kepatuhan terhadap regulasi dan standar. |

---

## 6. Business Rules

### BR-01: Numbering on Create

- Nomor inspeksi di-generate **saat record dibuat** (POST create).
- Format: `INS-YYYY-NNNN` (contoh: `INS-2026-0001`).
- Sumber: `NumberingService::generate('inspection')`.
- Konfigurasi numbering (sudah di-seed di `numbering_formats`):
  - `module_name`: `inspection`
  - `prefix`: `INS`
  - `padding`: `4`
  - `separator`: `-`
  - `reset_frequency`: `yearly`
  - `include_year`: `true`
  - `include_site_code`: `false`
  - `sample`: `INS-2026-0001`
- Nomor bersifat **unique**. Jika terjadi race condition, database unique constraint mencegah duplikat; service melakukan retry dengan increment.
- Nomor tidak dapat diubah setelah di-generate.

### BR-02: Template Active Status

- Template dengan `is_active=false` tidak muncul di dropdown saat membuat inspection baru.
- Template yang sudah dipakai oleh inspection (ada relasi) tidak bisa di-delete, hanya bisa di-deactivate.
- Mengubah template (menambah/mengubah/menghapus item) tidak mempengaruhi inspection yang sudah dibuat (snapshot items saat create inspection).

### BR-03: Inspection Item Types

Tipe jawaban yang didukung:

| Type | Answer Options | is_unsafe logic |
|---|---|---|
| `yes_no` | `yes`, `no` | `no` → `is_unsafe=true` |
| `safe_unsafe` | `safe`, `unsafe` | `unsafe` → `is_unsafe=true` |
| `na` | `na` | Tidak pernah unsafe |
| `scale` | `1`–`5` (string) | `1` atau `2` → `is_unsafe=true` (opsional) |
| `text` | Free text | Tidak pernah unsafe |

### BR-04: Overall Result Calculation

Saat inspection di-complete, sistem menghitung `overall_result`:

| Condition | overall_result |
|---|---|
| Ada satu atau lebih item dengan `is_unsafe=true` | `fail` |
| Semua item required sudah dijawab, tidak ada unsafe | `pass` |
| Ada item required yang belum dijawab | `pending` (tidak bisa complete) |

- Jika ada item `is_unsafe=true`, `overall_result` selalu `fail` terlepas dari jawaban item lain.
- `overall_result` hanya dihitung saat transition `in_progress → completed`.

### BR-05: Start Requires Pending Status

- Transition `pending` → `in_progress` hanya bisa dilakukan jika `status === 'pending'`.
- Saat start, `executed_at` diisi dengan timestamp saat ini.
- Inspector yang melakukan start harus sama dengan `inspector_id` atau QHSSE Officer/Manager.

### BR-06: Complete Requires All Required Items Answered

- Transition `in_progress` → `completed` memerlukan semua item dengan `is_required=true` sudah dijawab (answer tidak null/kosong).
- Jika ada item required yang belum dijawab, complete ditolak dengan error message.
- Saat complete, `overall_result` dihitung otomatis (BR-04).

### BR-07: Audit Trail on Critical Changes

Audit trail dicatat untuk event berikut (via `audit_logs` table, `module_name='inspection'`):

| Event | Auditable | Old/New Values |
|---|---|---|
| `inspection.template.created` | InspectionTemplate | new_values: all fields |
| `inspection.template.updated` | InspectionTemplate | changed fields only |
| `inspection.template.deleted` | InspectionTemplate | soft delete |
| `inspection.created` | Inspection | new_values: all fields |
| `inspection.started` | Inspection | status change + executed_at |
| `inspection.completed` | Inspection | status change + overall_result |
| `inspection.result.saved` | InspectionResult | new_values: answer, remark, is_unsafe |
| `inspection.file.uploaded` | ManagedFile | new_values |
| `inspection.file.deleted` | ManagedFile | soft delete |

### BR-08: Data Visibility by Scope

| Scope | Who | What They See |
|---|---|---|
| `own` | N/A (inspectors are QHSSE roles) | Only inspections where `inspector_id = auth user` |
| `department` | Supervisor, Department Head | Inspections in their department (via site/area) |
| `site` | QHSSE Officer | Inspections in their assigned site(s) |
| `all` | QHSSE Manager, Top Management, Auditor, Super Admin, Admin | All inspections |

- Templates: semua QHSSE roles bisa melihat semua templates (tidak scope-filtered).
- Scope check dilakukan **server-side** di Controller/Policy.

---

## 7. Permission Keys

### Resource Group: Templates (`inspection.checklists.*`)

| # | Permission Key | Description |
|---|---|---|
| 1 | `inspection.checklists.view` | View template list and detail. |
| 2 | `inspection.checklists.create` | Create new template + items. |
| 3 | `inspection.checklists.update` | Update template + items. |
| 4 | `inspection.checklists.delete` | Delete (soft-delete) template. Admin/QHSSE Manager only. |
| 5 | `inspection.checklists.execute` | Create and execute inspection from template. |
| 6 | `inspection.checklists.export` | Export template/inspection list to CSV. |

### Resource Group: Results (`inspection.results.*`)

| # | Permission Key | Description |
|---|---|---|
| 7 | `inspection.results.view` | View inspection results and detail. Scope-filtered. |

### Implementation Notes

- Permission keys mengikuti format `{module}.{entity}.{action}`.
- Keys harus di-register di seeder (tambahkan ke `CorePermissions::all()`).
- Keys di-assign ke roles via `CorePermissions::roleMap()`.
- File upload/download menggunakan permission core: `core.files.upload`, `core.files.download`.
- Comment menggunakan permission core: `core.comments.view`, `core.comments.create`.
- Workflow transition menggunakan permission core: `core.workflow.transition`.

---

## 8. Role-Permission Matrix

### Template Permissions (`inspection.checklists.*`)

| Role | `view` | `create` | `update` | `delete` | `execute` | `export` |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| Super Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Manager | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Officer | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ |
| Supervisor | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Department Head | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Employee/Reporter | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Contractor | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Auditor | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Top Management | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |

### Result Permissions (`inspection.results.*`)

| Role | `view` |
|---|:---:|
| Super Admin | ✅ |
| Admin | ✅ |
| QHSSE Manager | ✅ |
| QHSSE Officer | ✅ |
| Supervisor | ✅ |
| Department Head | ✅ |
| Employee/Reporter | ❌ |
| Contractor | ❌ |
| Auditor | ✅ |
| Top Management | ✅ |

### Notes

- **QHSSE Officer** dapat membuat dan mengeksekusi inspeksi tetapi tidak dapat menghapus template.
- **Supervisor** dan **Department Head** hanya dapat melihat hasil inspeksi di scope mereka.
- **Employee/Reporter** dan **Contractor** tidak memiliki akses ke modul ini.
- **Auditor** dan **Top Management** hanya view + export (read-only).
- Semua permission di-enforce **server-side** via Laravel Policy atau Gate.

---

## 9. Notification Events

3 event notifikasi untuk modul Inspection Checklist:

### 9.1 `inspection.completed`

| Property | Value |
|---|---|
| **Trigger** | Inspector melakukan complete (transition `in_progress` → `completed`) |
| **Recipients** | QHSSE Manager in the same site scope. If no site-scoped QHSSE Manager exists, notify all QHSSE Managers. |
| **Type** | `inspection.completed` |
| **Title (template)** | `Inspeksi Selesai: {inspection.inspection_number}` |
| **Message (template)** | `{inspector.name} telah menyelesaikan inspeksi {inspection.inspection_number} - {template.name} di {site.name}. Hasil: {overall_result}.` |
| **Action URL** | `/inspections/{inspection.id}` |
| **Module/Reference** | `module_name='inspection'`, `reference_id={inspection.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.2 `inspection.unsafe_found`

| Property | Value |
|---|---|
| **Trigger** | Saat inspection di-complete dan ada satu atau lebih item dengan `is_unsafe=true` |
| **Recipients** | QHSSE Manager and QHSSE Officer in the same site scope. |
| **Type** | `inspection.unsafe_found` |
| **Title (template)** | `Item Tidak Aman Ditemukan: {inspection.inspection_number}` |
| **Message (template)** | `Inspeksi {inspection.inspection_number} menemukan {count} item tidak aman. Mohon tindak lanjut dengan pembuatan CAPA.` |
| **Action URL** | `/inspections/{inspection.id}` |
| **Module/Reference** | `module_name='inspection'`, `reference_id={inspection.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.3 `inspection.overdue`

| Property | Value |
|---|---|
| **Trigger** | Scheduled date lewat dan inspection masih `pending` atau `in_progress` (checked by scheduled job) |
| **Recipients** | Inspector (`inspector_id`) and QHSSE Officer in the same site scope. |
| **Type** | `inspection.overdue` |
| **Title (template)** | `Inspeksi Terlambat: {inspection.inspection_number}` |
| **Message (template)** | `Inspeksi {inspection.inspection_number} - {template.name} sudah melewati jadwal ({scheduled_at}). Mohon segera laksanakan.` |
| **Action URL** | `/inspections/{inspection.id}` |
| **Module/Reference** | `module_name='inspection'`, `reference_id={inspection.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### Implementation Notes

- Notification dikirim setelah DB transaction commit.
- In-app notification: sync write to `core_notifications`.
- Email notification: dispatch to queue if available, fallback sync.
- Recipient resolution: query users with target role + matching scope (site).

---

## 10. File Attachment Rules

### 10.1 Storage Configuration

| Property | Value |
|---|---|
| **Service** | Core FileService (`App\Core\File\FileService`) |
| **Table** | `managed_files` |
| **module_name** | `inspection` |
| **reference_id** | `inspections.id` |
| **collection** | `evidence` |
| **Disk** | `local` (private, not publicly accessible) |
| **Path pattern** | `inspection/{inspection_id}/evidence/{stored_name}` |

### 10.2 Validation Rules

| Rule | Value |
|---|---|
| **Allowed extensions** | `jpg`, `jpeg`, `png`, `gif`, `webp`, `pdf` |
| **Allowed MIME types** | Corresponding to extensions above |
| **Max file size** | 10 MB per file |
| **Max files per inspection** | 20 |

### 10.3 Access Rules

- **Upload**: User must have `inspection.checklists.execute` (or be the inspector of a pending/in_progress inspection).
- **Download**: User must have `inspection.results.view` and be within data scope of the inspection.
- **Delete**: User must have `inspection.checklists.execute` AND inspection status must NOT be `completed`. Once completed, evidence files cannot be deleted except by Super Admin / Admin.
- File access logged in audit trail.

---

## 11. Dashboard Metrics

### 11.1 KPI Cards

| Metric | Query | Display |
|---|---|---|
| **Total Inspections** | Count all inspections in scope | Number + icon |
| **Pending/In Progress** | Count where status IN (`pending`, `in_progress`) | Number, yellow |
| **Completed** | Count where status = `completed` | Number, green |
| **Unsafe Found** | Count where overall_result = `fail` | Number, red badge |
| **Overdue** | Count where scheduled_at < today AND status IN (`pending`, `in_progress`) | Number, red |
| **This Month** | Count created in current month | Number + trend arrow |

### 11.2 Charts

| Chart | Type | Data |
|---|---|---|
| **Monthly Trend** | Line chart | Inspection count by month (last 12 months), split by status |
| **By Template** | Bar chart | Count by template (top 10) |
| **By Site** | Horizontal bar | Count by site (top 10) |
| **By Status** | Donut | Count by workflow status |
| **Unsafe Trend** | Line chart | Unsafe item count by month |

### 11.3 Table Widgets

| Widget | Columns | Filter |
|---|---|---|
| **Recent Inspections** | Number, Template, Site, Inspector, Status, Result, Date | Last 10, scoped |
| **Unsafe Open** | Number, Template, Site, Unsafe Count, Date | overall_result=fail, status=completed |
| **Overdue** | Number, Template, Site, Inspector, Scheduled Date | Overdue, not completed |

### 11.4 Filters

Dashboard metrics support:
- Date range filter (default: current year)
- Site filter
- Template filter

---

## 12. Export Spec

### 12.1 CSV Export — Templates

| Property | Value |
|---|---|
| **Format** | CSV (UTF-8 with BOM for Excel compatibility) |
| **Filename** | `inspection_templates_export_{YYYYMMDD_HHmmss}.csv` |
| **Permission** | `inspection.checklists.export` |

### CSV Columns (Templates):

| # | Column Header | Field Source |
|---|---|---|
| 1 | `Kode` | `code` |
| 2 | `Nama` | `name` |
| 3 | `Kategori` | `category` |
| 4 | `Jumlah Item` | count of items |
| 5 | `Status` | `is_active` (Aktif/Nonaktif) |
| 6 | `Dibuat Pada` | `created_at` |

### 12.2 CSV Export — Inspections

| Property | Value |
|---|---|
| **Format** | CSV (UTF-8 with BOM for Excel compatibility) |
| **Filename** | `inspections_export_{YYYYMMDD_HHmmss}.csv` |
| **Permission** | `inspection.checklists.export` |
| **Scope** | Follows user's data scope |

### CSV Columns (Inspections):

| # | Column Header | Field Source | Notes |
|---|---|---|---|
| 1 | `Nomor` | `inspection_number` | INS-YYYY-NNNN |
| 2 | `Template` | `template.name` | Via `inspection_template_id` |
| 3 | `Site` | `site.name` | Via `site_id` |
| 4 | `Area` | `area.name` | Via `area_id`, nullable |
| 5 | `Inspector` | `inspector.name` | Via `inspector_id` |
| 6 | `Jadwal` | `scheduled_at` | |
| 7 | `Dieksekusi` | `executed_at` | Nullable |
| 8 | `Status` | `status` | pending/in_progress/completed |
| 9 | `Hasil` | `overall_result` | pass/fail/pending |
| 10 | `Jumlah Unsafe` | count of results where is_unsafe=true | |
| 11 | `Catatan` | `notes` | Nullable |

---

## 13. Acceptance Criteria

1. User dengan permission `inspection.checklists.create` dapat membuat template inspeksi dengan items.
2. User dengan permission `inspection.checklists.execute` dapat membuat inspeksi dari template aktif.
3. Nomor inspeksi `INS-YYYY-NNNN` di-generate otomatis dan unik.
4. Inspector dapat menjawab setiap item inspeksi (Yes/No/NA/Unsafe/Scale/Text).
5. Transition `pending → in_progress` berjalan sesuai rule (BR-05).
6. Transition `in_progress → completed` memvalidasi semua item required terisi (BR-06).
7. `overall_result` dihitung otomatis saat complete (BR-04).
8. Item `is_unsafe=true` ditampilkan dengan highlight di halaman Show.
9. Tombol "Buat CAPA" muncul di halaman Show jika ada item unsafe.
10. Notifikasi `inspection.completed` dan `inspection.unsafe_found` terkirim.
11. Audit trail tercatat untuk create, start, complete, dan result changes.
12. Export CSV templates dan inspections menghasilkan data sesuai filter dan permission.
13. User tanpa permission ditolak (403).
14. Data visibility mengikuti role scope (BR-08).

---

## 14. Open Questions

1. Apakah item `yes_no` dengan jawaban `no` selalu dianggap `unsafe`, atau perlu konfigurasi per item?
2. Apakah satu CAPA per item unsafe atau satu CAPA gabungan per inspection?
3. Apakah perlu fitur "re-inspection" (membuat inspection baru dari inspection yang sudah completed)?
4. Apakah perlu schedule recurring inspection (otomatis membuat inspection berdasarkan jadwal)?
5. Apakah inspector bisa di-assign ke multiple inspections atau hanya satu aktif pada satu waktu?
6. Apakah perlu approval dari QHSSE Manager sebelum inspection dianggap final?
7. Field mandatory final per perusahaan/site.
8. SLA/due date default per kategori template.
