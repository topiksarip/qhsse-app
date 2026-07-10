# Module Spec — Investigation & RCA

> **Module ID:** `03-investigation-rca`  
> **Module Code (numbering):** `investigation`  
> **Number Prefix:** `INV`  
> **Workflow Code:** `INVESTIGATION_WORKFLOW`  
> **Phase:** Phase 2 (Investigation & Root Cause Analysis)  
> **Status:** Ready for coding

---

## 1. Tujuan Modul

Modul Investigation & RCA menyediakan sistem investigasi formal dan analisis root cause untuk kejadian QHSSE yang memerlukan pendalaman. Modul ini terhubung langsung ke modul Incident Reporting (Phase 1) melalui foreign key `incident_id`.

Tujuan utama:

- Memungkinkan **QHSSE Officer/Manager** memulai investigasi formal dari incident yang berstatus `under_review` atau `investigation`.
- Menyediakan **root cause analysis tools** yang terstruktur: **5-Why analysis** (tabel berjenjang) dan **Fishbone diagram** (Ishikawa, 6 kategori: Man, Method, Machine, Material, Environment, Management).
- Mengelola **contributing factors** (faktor kontribusi) yang berkontribusi terhadap terjadinya kejadian.
- Menyediakan **timeline of events** (kronologi kejadian) selama investigasi.
- Menghasilkan **recommendations** (rekomendasi tindakan korektif) yang dapat di-link ke modul CAPA (Phase 3).
- Mengelola **investigation team** (tim investigasi) melalui pivot table dengan role per anggota.
- Memastikan setiap investigasi memiliki **nomor unik** (INV-YYYY-NNNN) yang di-generate otomatis.
- Menyediakan **workflow status** yang jelas: Draft → In Progress → Completed (dengan jalur Cancel).
- Mengirim **notifikasi** ke stakeholder saat investigasi dimulai, selesai, atau dibatalkan.
- Menyediakan **audit trail** lengkap untuk semua perubahan kritikal.
- Menghubungkan ke modul **CAPA Action Tracking** (module `04`) via cross-module link dari recommendations.

---

## 2. Dependency

### Core Foundation (Phase 0 — complete)

| Dependency | Usage in This Module |
|---|---|
| **Auth** | User login, session, role identification |
| **PermissionService** (Spatie) | 7 permission keys `investigation.reports.*` |
| **NumberingService** | Generate `INV-YYYY-NNNN` on create |
| **WorkflowService** | Status transitions per `INVESTIGATION_WORKFLOW` definition |
| **FileService** | Upload/download investigation documents via `managed_files` table |
| **NotificationService** | In-app + email notifications |
| **AuditTrailService** | Audit log via `audit_logs` table |
| **CommentService** | Comments via `comments` table (`module_name='investigation'`) |
| **ActivityLogService** | Activity timeline via `activity_logs` table |
| **ListQuery** | Paginated, searchable, sortable list queries |
| **CsvExporter** | CSV export of investigation list |
| **MasterData** | Sites, Departments, Users (for team assignment) |

### Cross-Module Dependencies

| Module | Relationship | Direction |
|---|---|---|
| `02-incident-reporting` | Investigation links to Incident via `investigations.incident_id` FK → `incidents.id` (N:1) | Incoming (investigation requires incident) |
| `04-capa-action-tracking` | Investigation recommendations can spawn CAPA records via `source_module='investigation'` and `source_reference_id=investigations.id` | Outgoing (Phase 3) |

### Tech Stack

- Laravel 12 (backend: Form Request, Policy, Service, Eloquent)
- Inertia.js React + TypeScript (frontend)
- Tailwind CSS (styling, UI in Indonesian)
- PostgreSQL (data layer)
- Spatie Laravel Permission (RBAC)

---

## 3. User Roles

10 roles terlibat dalam modul ini:

| # | Role | Deskripsi Peran dalam Investigation & RCA |
|---|---|---|
| 1 | **Super Admin** | Akses penuh ke semua data dan fungsi. Bypass scope. |
| 2 | **Admin** | Akses sistem penuh termasuk konfigurasi, numbering, master data. |
| 3 | **QHSSE Manager** | Create, conduct, submit, review, close, export investigation. Scope: all sites. Approve laporan investigasi. |
| 4 | **QHSSE Officer** | Create, conduct, submit, update, export investigation. Scope: assigned site(s). Investigator utama. |
| 5 | **Supervisor** | View investigation di department-nya. Scope: department. Tidak dapat create/edit. |
| 6 | **Department Head** | View investigation di department-nya. Scope: department. |
| 7 | **Employee/Reporter** | View investigation milik incident yang ia laporkan. Scope: own. |
| 8 | **Contractor** | View investigation milik company/contractor-nya. Scope: company. |
| 9 | **Auditor** | View-only semua data dalam scope audit. Export. Tidak create/edit. |
| 10 | **Top Management** | View dashboard & report, export. Scope: all. Tidak create/edit. |

---

## 4. Fitur Lengkap

### 4.1 Investigation CRUD

- **Create** — Form pembuatan investigasi dari incident yang sudah `under_review` atau `investigation`. Nomor `INV-YYYY-NNNN` di-generate otomatis oleh `NumberingService` pada saat record dibuat. Status awal: `draft`. Investigator default: user yang membuat.
- **List** — Halaman list dengan search (nomor investigasi, judul, nomor incident terkait), filter (status, site, investigator, date range), pagination (default 15 per page), dan tombol Export CSV.
- **Detail** — Halaman detail menampilkan: nomor investigasi, judul, incident terkait, status, investigator, root cause analysis (5-why table, fishbone diagram), contributing factors, timeline events, recommendations, team members, comments, activity log, audit trail.
- **Update** — Edit record. Hanya bisa edit jika status `draft` atau `in_progress`. Setelah `completed`, record tidak bisa di-edit.
- **Delete** — Soft delete. Hanya Super Admin / Admin. Tidak bisa delete record yang sudah `completed`.

### 4.2 Root Cause Analysis Tools

#### 4.2.1 5-Why Analysis

Tabel 5-Why yang memungkinkan pendalaman bertingkat dari masalah awal hingga root cause:

| Level | Question | Answer |
|---|---|---|
| Why 1 | Mengapa kejadian terjadi? | [Jawaban] |
| Why 2 | Mengapa hal di atas terjadi? | [Jawaban] |
| Why 3 | Mengapa hal di atas terjadi? | [Jawaban] |
| Why 4 | Mengapa hal di atas terjadi? | [Jawaban] |
| Why 5 | Mengapa hal di atas terjadi? | [Jawaban → Root Cause] |

- Disimpan sebagai JSON array di kolom `five_whys` pada tabel `investigations`.
- Minimum 1 level, maksimum 7 level (5 standar, 2 tambahan untuk kompleksitas).
- Field: `{ level: number, question: string, answer: string, is_root_cause: boolean }`.

#### 4.2.2 Fishbone Diagram (Ishikawa)

Diagram fishbone dengan 6 kategori standar:

| # | Kategori | Nama (Indonesian) | Deskripsi |
|---|---|---|---|
| 1 | `Man` | Manusia | Faktor manusia: kompetensi, kelelahan, pelatihan, sikap |
| 2 | `Method` | Metode | Faktor metode: prosedur, SOP, instruksi kerja |
| 3 | `Machine` | Mesin | Faktor mesin: peralatan, maintenance, kalibrasi |
| 4 | `Material` | Material | Faktor material: bahan baku, spesifikasi, kualitas |
| 5 | `Environment` | Lingkungan | Faktor lingkungan: suhu, kebisingan, pencahayaan, layout |
| 6 | `Management` | Manajemen | Faktor manajemen: kebijakan, supervisi, resource allocation |

- Disimpan sebagai JSON di kolom `fishbone` pada tabel `investigations`.
- Struktur: `{ category: string, causes: string[] }[]`.
- Setiap kategori dapat memiliki multiple causes (text).
- Tampilan visual sebagai diagram fishbone atau list terstruktur.

#### 4.2.3 Contributing Factors

Faktor-faktor tambahan yang berkontribusi terhadap kejadian namun bukan root cause utama:

- Disimpan sebagai JSON array di kolom `contributing_factors`.
- Struktur: `{ factor: string, category: string, impact: 'direct'|'indirect' }[]`.
- Category mengacu pada 6 kategori fishbone.
- Tampilan sebagai list dengan badge kategori.

### 4.3 Timeline of Events

Kronologi kejadian selama investigasi:

- Disimpan sebagai JSON array di kolom `timeline_events`.
- Struktur: `{ timestamp: string, event: string, description: string, source: string }[]`.
- Timestamp dalam format ISO 8601.
- Source: `incident_report`, `witness_statement`, `cctv_footage`, `document_review`, `site_inspection`, `other`.

### 4.4 Recommendations

Rekomendasi tindakan korektif hasil investigasi:

- Disimpan sebagai text di kolom `recommendations`.
- Dapat di-link ke modul CAPA (Phase 3) saat CAPA record dibuat dari investigation.
- Setiap rekomendasi dapat spawn 1 CAPA record.

### 4.5 Investigation Team

- Pivot table `investigation_team` (investigation_id, user_id, role).
- Role: `lead_investigator`, `investigator`, `subject_matter_expert`, `recorder`.
- Satu investigation memiliki 1 lead investigator (investigator_id di tabel utama), multiple investigators/SME/recorders.

### 4.6 Workflow Actions

- **Save Draft** — Simpan tanpa validasi mandatory. Status tetap `draft`.
- **Start** — Mulai investigasi. Status: `draft` → `in_progress`. Set `started_at`.
- **Complete** — Selesaikan investigasi. Status: `in_progress` → `completed`. Wajib reason. Set `completed_at`.
- **Cancel** — Batalkan investigasi. Status: `draft`/`in_progress` → `cancelled`. Wajib reason.

### 4.7 Comments & Activity Timeline

- Comment dapat ditambahkan oleh user yang punya akses ke record.
- Activity log otomatis mencatat: create, start, complete, cancel, field changes, team member add/remove.
- Timeline ditampilkan di halaman detail.

### 4.8 Notification

- 3 event notifikasi: `investigation.started`, `investigation.completed`, `investigation.cancelled`.
- In-app notification via `core_notifications` table.
- Email notification jika configured.

### 4.9 Dashboard & Reporting

- Dashboard widget: total investigations, breakdown by status, avg time to complete, investigations per site.
- Export CSV dengan kolom yang dispesifikasikan di Section 12.

### 4.10 Cross-Module Link

- Investigation → Incident: link via `investigations.incident_id` FK → `incidents.id` (N:1).
- Investigation → CAPA: link via CAPA record's `source_module='investigation'` dan `source_reference_id=investigations.id` (1:N, Phase 3).

---

## 5. Kategori Analisis

### 5-Why Analysis Levels

| Level | Nama | Deskripsi |
|---|---|---|
| 1 | Why 1 | Pertanyaan pertama: mengapa kejadian terjadi? |
| 2 | Why 2 | Pendalaman dari jawaban Why 1 |
| 3 | Why 3 | Pendalaman dari jawaban Why 2 |
| 4 | Why 4 | Pendalaman dari jawaban Why 3 |
| 5 | Why 5 | Root cause level (answer ditandai sebagai root cause) |
| 6 | Why 6 | Optional: pendalaman tambahan untuk kompleksitas |
| 7 | Why 7 | Optional: pendalaman maksimum |

### Fishbone Categories (6M)

| Code | Indonesian | Deskripsi |
|---|---|---|
| `Man` | Manusia | Kompetensi, kelelahan, pelatihan, sikap, motivasi |
| `Method` | Metode | Prosedur, SOP, instruksi kerja, planning |
| `Machine` | Mesin | Peralatan, maintenance, kalibrasi, kondisi |
| `Material` | Material | Bahan baku, spesifikasi, kualitas, ketersediaan |
| `Environment` | Lingkungan | Suhu, kebisingan, pencahayaan, layout, cuaca |
| `Management` | Manajemen | Kebijakan, supervisi, resource, komunikasi |

### Contributing Factor Impact Levels

| Impact | Nama | Deskripsi |
|---|---|---|
| `direct` | Langsung | Faktor yang langsung berkontribusi pada kejadian |
| `indirect` | Tidak Langsung | Faktor yang memperburuk atau memfasilitasi kejadian |

### Timeline Event Sources

| Source | Nama | Deskripsi |
|---|---|---|
| `incident_report` | Laporan Insiden | Berdasarkan laporan insiden |
| `witness_statement` | Keterangan Saksi | Berdasarkan keterangan saksi |
| `cctv_footage` | Rekaman CCTV | Berdasarkan rekaman CCTV |
| `document_review` | Tinjauan Dokumen | Berdasarkan tinjauan dokumen |
| `site_inspection` | Inspeksi Lokasi | Berdasarkan inspeksi lokasi |
| `other` | Lainnya | Sumber lain |

---

## 6. Business Rules

### BR-01: Numbering on Create

- Nomor investigasi di-generate **saat record dibuat** (POST create), bukan saat submit/start.
- Format: `INV-YYYY-NNNN` (contoh: `INV-2026-0001`).
- Sumber: `NumberingService::generate('investigation')`.
- Konfigurasi numbering (sudah di-seed di `numbering_formats`):
  - `module_name`: `investigation`
  - `prefix`: `INV`
  - `padding`: `4`
  - `separator`: `-`
  - `reset_frequency`: `yearly`
  - `include_year`: `true`
  - `include_site_code`: `false`
  - `sample`: `INV-2026-0001`
- Nomor bersifat **unique**. Database unique constraint mencegah duplikat; service melakukan retry dengan increment.
- Nomor tidak dapat diubah setelah di-generate.

### BR-02: Draft Save Without Mandatory Fields

- Saat status `draft`, record dapat disimpan tanpa mengisi mandatory fields (5-why, fishbone, recommendations).
- Field wajib hanya divalidasi saat **start** (transition `draft` → `in_progress`).
- Investigator dapat menyimpan draft berkali-kali sebelum start.

### BR-03: Start Validates Mandatory Fields

Saat user melakukan **start** (transition `draft` → `in_progress`), sistem memvalidasi field mandatory berikut:

| Field | Validation Rule |
|---|---|
| `title` | required, string, max:255 |
| `incident_id` | required, exists in incidents where status IN ('under_review', 'investigation') |
| `investigator_id` | required, exists in users |
| `five_whys` | required, array, min:1 item |
| `fishbone` | required, array, min:1 category with min:1 cause |

Jika validasi gagal, start ditolak dan record tetap berstatus `draft`.

### BR-04: Complete Requires Reason

- Transition `in_progress` → `completed` memerlukan field `reason` (wajib, text, min:10 karakter).
- Reason disimpan di `workflow_histories.reason`.
- Field `root_cause` wajib diisi sebelum complete.
- `recommendations` wajib diisi sebelum complete.
- Notifikasi dikirim ke reporter incident dan stakeholder.
- Setelah complete, record menjadi read-only. Tidak bisa edit, tidak bisa hapus file.

### BR-05: Cancel Requires Reason

- Transition `draft`/`in_progress` → `cancelled` memerlukan field `reason` (wajib, text, min:10 karakter).
- Reason disimpan di `workflow_histories.reason`.
- Notifikasi dikirim ke stakeholder.
- Record cancelled tetap tersimpan untuk referensi namun tidak dapat dilanjutkan (terminal status).

### BR-06: Audit Trail on Critical Changes

Audit trail dicatat untuk event berikut (via `audit_logs` table, `module_name='investigation'`):

| Event | Auditable | Old/New Values |
|---|---|---|
| `investigation.created` | Investigation record | new_values: all fields |
| `investigation.updated` | Investigation record | changed fields only |
| `investigation.started` | Investigation record | status change + started_at |
| `investigation.completed` | Investigation record | status change + completed_at + reason |
| `investigation.cancelled` | Investigation record | status change + reason |
| `investigation.deleted` | Investigation record | soft delete |
| `investigation.file.uploaded` | ManagedFile | new_values |
| `investigation.file.deleted` | ManagedFile | soft delete |
| `investigation.file.downloaded` | ManagedFile | metadata: user, ip |
| `investigation.team.added` | Investigation record | team member details |
| `investigation.team.removed` | Investigation record | team member details |
| `investigation.exported` | Investigation record | export metadata |

### BR-07: Data Visibility by Scope

Data visibility mengikuti role scope (sesuai `CorePermissions::roleMap()`):

| Scope | Who | What They See |
|---|---|---|
| `own` | Employee/Reporter, Contractor | Only investigations linked to incidents they created |
| `department` | Supervisor, Department Head | Investigations linked to incidents in their department |
| `site` | QHSSE Officer | Investigations linked to incidents in their assigned site(s) |
| `all` | QHSSE Manager, Top Management, Auditor, Super Admin, Admin | All investigations |

- Scope check dilakukan **server-side** di Controller/Policy.
- Query scope: filter berdasarkan incident's `reporter_id` (own), incident's `department_id` (department), incident's `site_id` (site), atau no filter (all).

### BR-08: One Investigation per Incident (Recommended)

- Satu incident disarankan memiliki **satu active investigation** pada satu waktu.
- Saat membuat investigation baru dari incident yang sudah memiliki investigation dengan status `draft` atau `in_progress`, sistem menampilkan warning.
- Tidak ada hard constraint di database level; kontrol dilakukan di application layer.

### BR-09: Root Cause Required Before Complete

- Field `root_cause` wajib diisi sebelum transition `in_progress` → `completed`.
- Root cause dapat berisi ringkasan dari hasil 5-Why analysis atau fishbone analysis.
- Tidak ada panjang minimum, namun disarankan minimal 20 karakter.

### BR-10: Recommendations Required Before Complete

- Field `recommendations` wajib diisi sebelum transition `in_progress` → `completed`.
- Berisi rekomendasi tindakan korektif/preventif hasil investigasi.

---

## 7. Permission Keys

7 permission keys untuk modul Investigation & RCA:

| # | Permission Key | Description |
|---|---|---|
| 1 | `investigation.reports.view` | View investigation list and detail. Scope-filtered. |
| 2 | `investigation.reports.create` | Create new investigation record. Generates INV number. |
| 3 | `investigation.reports.update` | Update investigation record. Only draft/in_progress status. |
| 4 | `investigation.reports.submit` | Start investigation (draft → in_progress). Validates mandatory fields. |
| 5 | `investigation.reports.review` | Review investigation data. QHSSE Manager reviews before close. |
| 6 | `investigation.reports.close` | Complete investigation (in_progress → completed). Requires reason. |
| 7 | `investigation.reports.export` | Export investigation list to CSV. Scope-filtered. |

### Implementation Notes

- Permission keys mengikuti format `{module}.{entity}.{action}` → `investigation.reports.*`.
- Keys harus di-register di seeder (tambahkan ke `CorePermissions::all()` atau buat `InvestigationPermissions` class terpisah).
- Keys di-assign ke roles via `CorePermissions::roleMap()` atau seeder modul.
- File upload/download menggunakan permission core: `core.files.upload`, `core.files.download`.
- Comment menggunakan permission core: `core.comments.view`, `core.comments.create`.
- Workflow transition menggunakan permission `investigation.reports.submit` (start), `investigation.reports.close` (complete).

---

## 8. Role-Permission Matrix

| Role | `view` | `create` | `update` | `submit` | `review` | `close` | `export` |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Super Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Manager | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Officer | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Supervisor | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Department Head | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Employee/Reporter | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Contractor | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Auditor | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Top Management | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |

### Notes

- **QHSSE Officer** dan **QHSSE Manager** adalah roles utama yang dapat create + conduct investigation.
- **Supervisor** hanya dapat view investigations di department-nya.
- **Admin** memiliki semua permission.
- **Employee/Reporter** dan **Contractor** hanya dapat view investigations linked to incidents mereka sendiri (scope: own/company).
- **Auditor** dan **Top Management** hanya view + export (read-only).
- Semua permission di-enforce **server-side** via Laravel Policy atau Gate.

---

## 9. Notification Events

3 event notifikasi untuk modul Investigation & RCA:

### 9.1 `investigation.started`

| Property | Value |
|---|---|
| **Trigger** | QHSSE Officer/Manager melakukan start (transition `draft` → `in_progress`) |
| **Recipients** | Reporter of the linked incident, Supervisor of the incident's department, Department Head |
| **Type** | `investigation.started` |
| **Title (template)** | `Investigasi Dimulai: {investigation.number}` |
| **Message (template)** | `Investigasi {investigation.number} - {investigation.title} untuk incident {incident.number} telah dimulai oleh {investigator.name}.` |
| **Action URL** | `/investigations/{investigation.id}` |
| **Module/Reference** | `module_name='investigation'`, `reference_id={investigation.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.2 `investigation.completed`

| Property | Value |
|---|---|
| **Trigger** | QHSSE Officer/Manager melakukan complete (transition `in_progress` → `completed`) |
| **Recipients** | Reporter of the linked incident, Supervisor of the incident's department, Department Head, QHSSE Manager (if completed by Officer) |
| **Type** | `investigation.completed` |
| **Title (template)** | `Investigasi Selesai: {investigation.number}` |
| **Message (template)** | `Investigasi {investigation.number} - {investigation.title} telah diselesaikan oleh {completer.name}. Root cause: {root_cause_summary}.` |
| **Action URL** | `/investigations/{investigation.id}` |
| **Module/Reference** | `module_name='investigation'`, `reference_id={investigation.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.3 `investigation.cancelled`

| Property | Value |
|---|---|
| **Trigger** | QHSSE Officer/Manager melakukan cancel (transition `draft`/`in_progress` → `cancelled`) |
| **Recipients** | Reporter of the linked incident, QHSSE Manager |
| **Type** | `investigation.cancelled` |
| **Title (template)** | `Investigasi Dibatalkan: {investigation.number}` |
| **Message (template)** | `Investigasi {investigation.number} - {investigation.title} telah dibatalkan oleh {canceller.name}. Alasan: {cancel_reason}.` |
| **Action URL** | `/investigations/{investigation.id}` |
| **Module/Reference** | `module_name='investigation'`, `reference_id={investigation.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### Implementation Notes

- Notification dikirim setelah DB transaction commit (use Laravel Event/Listener or Observer pattern).
- In-app notification: sync write to `core_notifications`.
- Email notification: dispatch to queue if available, fallback sync.
- Template variables di-resolve di NotificationService atau listener.
- Recipient resolution: query users with target role + matching scope (site/department).

---

## 10. File Attachment Rules

### 10.1 Storage Configuration

| Property | Value |
|---|---|
| **Service** | Core FileService (`App\Core\File\FileService`) |
| **Table** | `managed_files` |
| **module_name** | `investigation` |
| **reference_id** | `investigations.id` |
| **collection** | `evidence`, `report`, `attachment` |
| **Disk** | `local` (private, not publicly accessible) |
| **Path pattern** | `investigation/{investigation_id}/{collection}/{stored_name}` |

### 10.2 Validation Rules

| Rule | Value |
|---|---|
| **Allowed extensions** | `jpg`, `jpeg`, `png`, `gif`, `webp`, `pdf`, `doc`, `docx`, `xls`, `xlsx`, `ppt`, `pptx` |
| **Allowed MIME types** | Corresponding to extensions above |
| **Max file size** | 25 MB per file |
| **Max files per investigation** | 30 |
| **Filename** | Original filename stored in `original_name`; generated UUID-based name in `stored_name` |

### 10.3 Access Rules

- **Upload**: User must have `investigation.reports.update` (or be the investigator of a draft).
- **Download**: User must have `investigation.reports.view` and be within data scope.
- **Delete**: User must have `investigation.reports.update` AND investigation status must NOT be `completed` or `cancelled`. Once completed, evidence files **cannot be deleted** except by Super Admin / Admin.
- Download endpoint streams file from private storage; no direct public URL.
- File access logged in audit trail (`investigation.file.downloaded`).

---

## 11. Dashboard Metrics

### 11.1 KPI Cards

| Metric | Query | Display |
|---|---|---|
| **Total Investigations** | Count all investigations in scope | Number + icon |
| **In Progress** | Count where status = `in_progress` | Number, blue |
| **Completed** | Count where status = `completed` | Number, green |
| **Cancelled** | Count where status = `cancelled` | Number, gray |
| **Avg Duration (days)** | Avg days between `started_at` and `completed_at` for completed | Number |
| **This Month** | Count created in current month | Number + trend arrow |

### 11.2 Charts

| Chart | Type | Data |
|---|---|---|
| **Monthly Trend** | Line chart | Investigation count by month (last 12), split by status |
| **By Status** | Donut | Count by workflow status |
| **By Site** | Horizontal bar | Count by site (top 10) |
| **Root Cause Categories** | Bar chart | Count of fishbone categories used as root cause |
| **Avg Time to Complete** | Bar chart | Avg completion time by month |

### 11.3 Table Widgets

| Widget | Columns | Filter |
|---|---|---|
| **Recent Investigations** | Number, Title, Incident Number, Status, Investigator, Created At | Last 10, scoped |
| **Overdue (In Progress > 30 days)** | Number, Title, Incident Number, Investigator, Days In Progress | status=in_progress, started_at < 30 days ago |
| **Completed This Month** | Number, Title, Root Cause, Completed At | status=completed, completed_at this month |

---

## 12. Export Spec

### 12.1 CSV Export

| Property | Value |
|---|---|
| **Format** | CSV (UTF-8 with BOM for Excel compatibility) |
| **Filename** | `investigations_export_{YYYYMMDD_HHmmss}.csv` |
| **Permission** | `investigation.reports.export` |
| **Scope** | Follows user's data scope |
| **Filter** | Follows current list page filter parameters |

### 12.2 CSV Columns (in order)

| # | Column Header | Field Source | Notes |
|---|---|---|---|
| 1 | `Nomor` | `investigation.investigation_number` | INV-YYYY-NNNN |
| 2 | `Judul` | `investigation.title` | |
| 3 | `Nomor Incident` | `incident.incident_number` | Via `incident_id` |
| 4 | `Judul Incident` | `incident.title` | Via `incident_id` |
| 5 | `Status` | `investigation.status` | draft, in_progress, completed, cancelled |
| 6 | `Investigator` | `investigator.name` | Via `investigator_id` |
| 7 | `Root Cause` | `investigation.root_cause` | Truncated to 500 chars |
| 8 | `Started At` | `investigation.started_at` | YYYY-MM-DD HH:MM |
| 9 | `Completed At` | `investigation.completed_at` | Nullable |
| 10 | `Duration (days)` | Calculated: `completed_at - started_at` | Nullable |
| 11 | `Created At` | `investigation.created_at` | YYYY-MM-DD HH:MM:SS |

### 12.3 Export Rules

- Export event dicatat di audit trail (`investigation.exported`).
- Export mengikuti permission dan scope user.
- Maksimal 10.000 record per export.
- Date format mengikuti ISO 8601 di CSV.

---

## 13. Acceptance Criteria

1. **AC-01: Create with auto-numbering** — User dengan permission `investigation.reports.create` dapat membuat investigation record dari incident yang berstatus `under_review` atau `investigation`. Nomor `INV-YYYY-NNNN` di-generate otomatis pada saat create. Nomor bersifat unique.

2. **AC-02: Permission enforcement** — User tanpa permission `investigation.reports.view` tidak dapat mengakses halaman list/detail. User tanpa `investigation.reports.create` tidak dapat membuat investigation. Server-side check memblokir akses.

3. **AC-03: Draft save without mandatory fields** — User dapat menyimpan draft investigation tanpa mengisi 5-why, fishbone, recommendations. Draft tetap tersimpan.

4. **AC-04: Start validates mandatory fields** — Saat start (draft → in_progress), sistem memvalidasi title, incident_id, investigator_id, five_whys (min 1), fishbone (min 1 category with 1 cause). Jika gagal, start ditolak.

5. **AC-05: Workflow transitions correct** — Transisi: draft→in_progress(start), in_progress→completed(complete, requires_reason), draft/in_progress→cancelled(cancel, requires_reason). Transition tidak valid ditolak.

6. **AC-06: Complete requires reason and root_cause** — Transition complete wajib reason (min 10 karakter), root_cause tidak kosong, recommendations tidak kosong.

7. **AC-07: 5-Why analysis** — Form menyediakan tabel 5-Why dengan minimum 1 level, maksimum 7 level. Setiap level memiliki question dan answer. Level terakhir dapat ditandai sebagai root cause.

8. **AC-08: Fishbone diagram** — Form menyediakan 6 kategori fishbone (Man, Method, Machine, Material, Environment, Management). Setiap kategori dapat memiliki multiple causes (text). Data disimpan sebagai JSON.

9. **AC-09: Contributing factors** — Form menyediakan input contributing factors dengan kategori (6M) dan impact level (direct/indirect). Disimpan sebagai JSON array.

10. **AC-10: Investigation team** — Pivot table investigation_team memungkinkan multiple users dengan role (lead_investigator, investigator, subject_matter_expert, recorder). Team members dapat add/remove sebelum status completed.

11. **AC-11: Cross-module link** — Investigation terhubung ke incident via incident_id FK. Investigation recommendations dapat spawn CAPA records (Phase 3) via `source_module='investigation'`.

12. **AC-12: Notifications sent correctly** — Notifikasi terkirim untuk 3 event: `investigation.started`, `investigation.completed`, `investigation.cancelled`.

13. **AC-13: Audit trail complete** — Audit trail tercatat untuk: create, update, start, complete, cancel, delete, file upload/download/delete, team add/remove, export.

14. **AC-14: List with search/filter/pagination/export** — Halaman list mendukung search (nomor, judul, nomor incident), filter (status, investigator, date range), pagination, dan export CSV.

---

## 14. Open Questions

| # | Question | Default Answer (if not decided) |
|---|---|---|
| 1 | Apakah satu incident bisa memiliki multiple investigations? | **Yes, tapi hanya satu active** (draft/in_progress). Completed/cancelled investigations tetap tersimpan untuk historical reference. |
| 2 | Apakah investigation bisa di-reopen setelah completed? | **No** — completed adalah terminal status. Reopen dapat ditambahkan di Phase 3 jika diperlukan. |
| 3 | Apakah 5-Why harus selalu 5 level? | **No** — minimum 1 level, maksimum 7. Standar 5, tetapi fleksibel sesuai kompleksitas. |
| 4 | Apakah fishbone diagram ditampilkan secara visual atau sebagai list? | **Both** — list terstruktur di form, visual diagram di show page (opsional, render sebagai SVG/canvas). |
| 5 | Apakah investigation bisa dibuat tanpa incident terkait? | **No** — setiap investigation harus terhubung ke incident via `incident_id`. |
| 6 | Apakah ada SLA/time limit untuk investigasi? | **No untuk Phase 2** — tidak ada auto-escalation. Overdue notification dapat ditambahkan dengan cron job. |
| 7 | Apakah recommendations otomatis membuat CAPA records? | **No** — CAPA dibuat manual dari show page dengan tombol "Buat CAPA" (Phase 3). |
| 8 | Apakah notifikasi email aktif di Phase 2? | **Optional** — In-app notification wajib. Email jika SMTP configured. |
| 9 | Apakah investigation bisa di-export sebagai PDF report? | **Phase 3** — CSV export list untuk Phase 2. PDF per investigation dapat ditambahkan di Phase 3. |
| 10 | Apakah timeline events otomatis di-generate dari workflow history? | **No** — timeline events diisi manual oleh investigator sebagai kronologi kejadian (bukan workflow history). |
