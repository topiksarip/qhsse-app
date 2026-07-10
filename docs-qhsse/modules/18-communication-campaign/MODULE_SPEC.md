# Module Spec — Communication & Campaign

> **Module ID:** `18-communication-campaign`  
> **Module Code (numbering):** `communication`  
> **Number Prefix:** `COM`  
> **Phase:** Phase 18 — Communication & Campaign  
> **Status:** Ready for coding  
> **Dependencies:** Core Foundation, Notification, Document, Training  

---

## 1. Tujuan Modul

Modul Communication & Campaign menyediakan sistem manajemen komunikasi dan kampanye QHSSE secara terpusat. Modul ini mengelola safety alert, lesson learned, kampanye keselamatan, pengumuman, dan newsletter yang ditargetkan ke audiens spesifik dengan pelacakan acknowledgment.

Tujuan utama:

- Mengelola **kampanye komunikasi** dengan 5 tipe: `safety_alert`, `lesson_learned`, `campaign`, `announcement`, `newsletter`.
- Mencatat setiap kampanye dengan nomor unik `COM-YYYY-NNNN` yang di-generate otomatis pada saat create.
- Memfilter **target audience** berdasarkan: semua karyawan, site tertentu, departemen tertentu, atau role tertentu.
- Melakukan **publish workflow**: `draft` → `published` dengan blast notifikasi ke target audience.
- Melacak **acknowledgment** — pengguna konfirmasi telah membaca safety alert (wajib untuk `safety_alert`).
- Menghitung **view count** — jumlah pengguna yang melihat halaman kampanye.
- Mengirim **notifikasi blast** ke target audience saat kampanye di-publish.
- Menyediakan **audit trail** lengkap untuk semua perubahan kritikal (create, update, publish, acknowledge).
- Menyediakan **dashboard metrics** dan **export CSV** untuk analisis dan pelaporan.
- Menggunakan **simple status** (tanpa workflow engine) — `draft` → `published`.

---

## 2. Dependency

### Core Foundation (Phase 0 — COMPLETE)

| Dependency | Usage in This Module |
|---|---|
| **Auth** | User login, session, role identification |
| **PermissionService** (Spatie) | Permission keys `communication.campaigns.*` + `communication.acknowledgments.view` |
| **NumberingService** | Generate `COM-YYYY-NNNN` on campaign create |
| **ManagedFileService** | Upload/download attachment files via `managed_files` table |
| **NotificationService** | Blast notifikasi ke target audience saat publish |
| **AuditService** | Audit log via `audit_logs` table |
| **ActivityService** | Activity timeline via `activity_logs` table |
| **ListQuery** | Paginate, search, filter, sort on index pages |
| **CsvExporter** | CSV export for campaigns |

### Cross-Module Dependencies

| Module | Relationship |
|---|---|
| `Core Master Data` | `campaigns.site_id` → `sites.id`, `campaigns.department_id` → `departments.id` (FK, nullable — used for target audience filtering) |
| `Users` (Core) | `campaigns.author_id` → `users.id` (FK), `campaign_acknowledgments.user_id` → `users.id` (FK) |
| `Notification` (Core Service) | Blast notifications sent via `NotificationService::notifyMany()` on publish |
| `Document` (Module 07) | Campaign dapat me-reference dokumen terkait (e.g., safety alert merujuk pada dokumen prosedur) |
| `Training` (Module 08) | Lesson learned dapat memicu training record baru (integration point, not FK) |

### Tech Stack

- Laravel 12 (backend: Form Request, Policy, Service, Eloquent)
- Inertia.js React + TypeScript (frontend)
- Tailwind CSS (styling, UI in Indonesian)
- PostgreSQL (data layer)
- Spatie Laravel Permission (RBAC)

---

## 3. User Roles

10 roles terlibat dalam modul ini (sesuai `RolesAndPermissionsSeeder`):

| # | Role | Deskripsi Peran dalam Communication & Campaign |
|---|---|---|
| 1 | **Super Admin** | Akses penuh ke semua data dan fungsi. Bypass scope. |
| 2 | **Admin** | Akses sistem penuh termasuk konfigurasi, numbering, master data. |
| 3 | **QHSSE Manager** | Kelola kampanye. Scope: all sites. Create, update, publish, export. |
| 4 | **QHSSE Officer** | Kelola kampanye. Scope: assigned site(s). Create, update, publish. |
| 5 | **Supervisor** | View kampanye untuk department-nya. Acknowledge safety alert. |
| 6 | **Department Head** | View kampanye untuk department-nya. Acknowledge safety alert. |
| 7 | **Employee/Reporter** | View kampanye yang ditargetkan kepadanya. Acknowledge safety alert. |
| 8 | **Contractor** | View kampanye yang ditargetkan kepadanya. Acknowledge safety alert. |
| 9 | **Auditor** | View-only semua data dalam scope audit. Export. Tidak create/edit. |
| 10 | **Top Management** | View semua kampanye & report, export. Scope: all. Tidak create/edit. |

---

## 4. Fitur Lengkap

### 4.1 Campaign CRUD

- **Create** — Form pembuatan kampanye: judul, tipe, konten (rich text), target audience selector, tanggal kedaluwarsa. Nomor `COM-YYYY-NNNN` di-generate otomatis oleh `NumberingService` pada saat kampanye dibuat. Status awal: `draft`.
- **List** — Halaman list dengan search (nomor, judul), filter (tipe, status published), pagination (default 15 per page), dan tombol Export CSV.
- **Detail** — Halaman detail menampilkan: nomor, judul, tipe, konten, target audience, status, view count, acknowledgment list, activity log, audit trail.
- **Update** — Edit kampanye. Hanya bisa di-edit jika status = `draft`. Kampanye yang sudah `published` tidak dapat di-edit (kecuali oleh Super Admin untuk update expiry date).
- **Publish** — Action khusus: mengubah status dari `draft` → `published`, mengisi `published_at`, dan mengirim notifikasi blast ke target audience.

### 4.2 Target Audience Filtering

Target audience menentukan siapa yang menerima kampanye dan notifikasi:

| Target Audience | Value | Description |
|---|---|---|
| `all` | Semua Karyawan | Kampanye terlihat oleh semua user aktif |
| `specific_site` | Site Tertentu | Kampanye terlihat oleh user di site tertentu (`site_id` FK) |
| `specific_department` | Departemen Tertentu | Kampanye terlihat oleh user di departemen tertentu (`department_id` FK) |
| `specific_role` | Role Tertentu | Kampanye terlihat oleh user dengan role tertentu (`target_role` string) |

- Saat `target_audience = 'specific_site'`, field `site_id` wajib diisi.
- Saat `target_audience = 'specific_department'`, field `department_id` wajib diisi.
- Saat `target_audience = 'specific_role'`, field `target_role` wajib diisi (pilih dari daftar role: Super Admin, Admin, QHSSE Manager, QHSSE Officer, Supervisor, Department Head, Employee / Reporter, Contractor, Auditor, Top Management).
- Saat `target_audience = 'all'`, semua field filter (`site_id`, `department_id`, `target_role`) harus NULL.

### 4.3 Campaign Types

5 tipe kampanye (disimpan sebagai string di field `type`):

| # | Code | Nama | Deskripsi | Acknowledgment Required |
|---|---|---|---|---|
| 1 | `safety_alert` | **Safety Alert** | Peringatan keselamatan kritis yang wajib dibaca dan di-acknowledge oleh semua target audience. | ✅ Ya (wajib) |
| 2 | `lesson_learned` | **Lesson Learned** | Pembelajaran dari insiden atau near-miss untuk disebarluaskan. | Opsional |
| 3 | `campaign` | **Kampanye** | Kampanye keselamatan/lingkungan dengan durasi tertentu (e.g., "Zero Accident Month"). | Opsional |
| 4 | `announcement` | **Pengumuman** | Pengumuman resmi QHSSE (e.g., perubahan prosedur, jadwal audit). | Opsional |
| 5 | `newsletter` | **Newsletter** | Buletin berkala QHSSE berisi update, statistik, berita. | Tidak |

### 4.4 Acknowledgment Tracking

- Pengguna dapat mengkonfirmasi (acknowledge) bahwa mereka telah membaca kampanye, terutama untuk `safety_alert`.
- Setiap acknowledgment dicatat di tabel `campaign_acknowledgments` dengan `campaign_id`, `user_id`, `acknowledged_at`, `ip_address`.
- Satu user hanya bisa acknowledge satu kampanye sekali (unique constraint: `campaign_id + user_id`).
- Untuk `safety_alert`, acknowledgment bersifat **wajib** — sistem dapat menampilkan reminder berulang hingga user meng-acknowledge.
- Untuk tipe lain, acknowledgment bersifat **opsional** — tombol acknowledge tetap tersedia tetapi tidak ada reminder.
- Halaman detail kampanye menampilkan **daftar acknowledgment** (siapa, kapan, IP) — memerlukan permission `communication.acknowledgments.view`.

### 4.5 View Tracking

- Setiap kali halaman detail kampanye di-load oleh user yang termasuk target audience, `view_count` di-increment.
- View tracking dilakukan sekali per user (deduplication via session/cache).
- `view_count` menampilkan jumlah total views di halaman detail dan list.

### 4.6 Publish Workflow

- Kampanye dibuat dengan status `draft`.
- Action **Publish** mengubah status menjadi `published`:
  1. Set `status = 'published'`
  2. Set `published_at = now()`
  3. Resolve target audience (query users berdasarkan target_audience filter)
  4. Send notification blast via `NotificationService::notifyMany()`
  5. Log activity: `campaign.published`
  6. Log audit trail
- Kampanye yang sudah `published` tidak dapat di-unpublish.
- Kampanye yang sudah `published` dapat di-expire otomatis ketika `expires_at < now()`.

### 4.7 Notification Events

- 2 event notifikasi: `communication.campaign_published`, `communication.acknowledgment_reminder`.
- In-app notification via `core_notifications` table.
- Email notification jika configured.

### 4.8 Dashboard & Reporting

- Dashboard widget: total kampanye, breakdown by type, breakdown by status, kampanye published bulan ini, acknowledgment rate untuk safety alert.
- Export CSV kampanye dengan kolom yang dispesifikasikan di Section 12.

---

## 5. Tipe Kampanye

5 tipe kampanye (disimpan sebagai string di field `type`):

| # | Code | Nama | Deskripsi | Acknowledgment |
|---|---|---|---|---|
| 1 | `safety_alert` | **Safety Alert** | Peringatan keselamatan kritis (e.g., kebakaran, kebocoran kimia, near-miss serius). Wajib dibaca dan di-acknowledge. | ✅ Wajib |
| 2 | `lesson_learned` | **Lesson Learned** | Pembelajaran dari insiden atau near-miss untuk mencegah pengulangan. | Opsional |
| 3 | `campaign` | **Kampanye** | Kampanye keselamatan/lingkungan dengan durasi tertentu (e.g., "Zero Accident Month", "Safety Week"). | Opsional |
| 4 | `announcement` | **Pengumuman** | Pengumuman resmi QHSSE (e.g., perubahan prosedur, jadwal audit, pelatihan wajib). | Opsional |
| 5 | `newsletter` | **Newsletter** | Buletin berkala QHSSE berisi update, statistik, berita QHSSE. | Tidak |

> **Catatan:** Tipe disimpan sebagai string bebas di field `type` (bukan FK ke tabel master). Daftar tipe divalidasi di Form Request.

---

## 6. Business Rules

### BR-01: Numbering on Create

- Nomor kampanye di-generate **saat kampanye dibuat** (POST create).
- Format: `COM-YYYY-NNNN` (contoh: `COM-2026-0001`).
- Sumber: `NumberingService::generate('communication', ...)`.
- Konfigurasi numbering (sudah di-seed di `numbering_formats`):
  - `module_name`: `communication`
  - `prefix`: `COM`
  - `padding`: `4`
  - `separator`: `-`
  - `reset_frequency`: `yearly`
  - `include_year`: `true`
  - `include_site_code`: `false`
  - `sample`: `COM-2026-0001`
- Nomor bersifat **unique**. Database unique constraint mencegah duplikat.
- Nomor tidak dapat diubah setelah di-generate.

### BR-02: Status Transitions (Manual — No Workflow Engine)

- Kampanye menggunakan **simple status field** tanpa WorkflowService.
- Status diubah manual melalui publish endpoint.
- Status yang tersedia: `draft`, `published`.
- Transisi yang diizinkan:
  - `draft` → `published` (via publish action)
  - `published` → (terminal, tidak dapat diubah kembali ke draft)
- Setiap transisi dicatat di `activity_logs` dan `audit_logs`.

### BR-03: Target Audience Validation

- Saat `target_audience = 'all'`: `site_id`, `department_id`, `target_role` harus NULL.
- Saat `target_audience = 'specific_site'`: `site_id` wajib, `department_id` dan `target_role` harus NULL.
- Saat `target_audience = 'specific_department'`: `department_id` wajib, `site_id` dan `target_role` harus NULL.
- Saat `target_audience = 'specific_role'`: `target_role` wajib, `site_id` dan `department_id` harus NULL.
- Validasi dilakukan di Form Request (conditional rules).

### BR-04: Publish Action

- Publish hanya dapat dilakukan jika `status = 'draft'`.
- Publish memerlukan permission `communication.campaigns.publish`.
- Saat publish:
  1. Set `status = 'published'`, `published_at = now()`
  2. Resolve target users berdasarkan `target_audience` filter
  3. Kirim notifikasi blast via `NotificationService::notifyMany()`
  4. Log activity + audit trail
- Kampanye yang sudah `published` tidak dapat di-edit (kecuali `expires_at` oleh Super Admin).
- Kampanye yang sudah `published` tidak dapat di-publish ulang.

### BR-05: Acknowledgment Rules

- Setiap user dapat acknowledge maksimal sekali per kampanye (unique constraint: `campaign_id + user_id`).
- Acknowledgment terbuka untuk semua user yang termasuk target audience.
- Untuk `safety_alert`: acknowledgment wajib. Sistem mengirim reminder berulang hingga user acknowledge.
- Untuk `lesson_learned`, `campaign`, `announcement`: acknowledgment opsional.
- Untuk `newsletter`: tidak ada acknowledgment.
- Acknowledgment mencatat: `user_id`, `acknowledged_at`, `ip_address` (untuk audit trail).

### BR-06: View Count Tracking

- `view_count` di-increment ketika user mengakses halaman detail kampanye.
- Deduplication: satu user hanya increment sekali (via session flag atau cache key `campaign:{id}:viewed:{user_id}`).
- View count tidak di-increment saat author atau admin melihat kampanye yang mereka buat.
- View count ditampilkan di halaman detail dan list.

### BR-07: Expiry Handling

- `expires_at` bersifat opsional (nullable).
- Jika `expires_at < now()` dan `status = 'published'`, kampanye tetap `published` tetapi ditandai sebagai "kedaluwarsa" di UI.
- Kampanye yang kedaluwarsa tetap dapat dilihat dan di-acknowledge (data historis).
- Scheduled command dapat mengirim reminder untuk safety alert yang belum di-acknowledge mendekati expiry.

### BR-08: Edit Restrictions

- Kampanye dengan `status = 'draft'` dapat di-edit (title, content, type, target_audience, expires_at).
- Kampanye dengan `status = 'published'` TIDAK dapat di-edit (kecuali `expires_at` oleh Super Admin).
- Jika perlu mengoreksi konten kampanye yang sudah published, buat kampanye baru dengan referensi ke kampanye lama.

### BR-09: Audit Trail on Critical Changes

Audit trail dicatat untuk event berikut (via `audit_logs` table, `module_name='communication'`):

| Event | Auditable | Old/New Values |
|---|---|---|
| `communication.campaign.created` | Campaign | new_values: all fields |
| `communication.campaign.updated` | Campaign | changed fields only |
| `communication.campaign.published` | Campaign | status change: draft → published |
| `communication.campaign.acknowledged` | CampaignAcknowledgment | new_values: user_id, acknowledged_at |
| `communication.file.uploaded` | ManagedFile | new_values |
| `communication.file.downloaded` | ManagedFile | metadata: user, ip |

### BR-10: Data Visibility by Scope

Data visibility mengikuti role scope (sesuai `CorePermissions::roleMap()`):

| Scope | Who | What They See |
|---|---|---|
| `target_audience` | Employee/Reporter, Contractor, Supervisor, Department Head | Kampanye yang ditargetkan kepada mereka (berdasarkan target_audience filter) |
| `site` | QHSSE Officer | Kampanye yang dibuat untuk site-nya + kampanye dengan target_audience='all' |
| `all` | QHSSE Manager, Top Management, Auditor, Super Admin, Admin | Semua kampanye |

- Scope check dilakukan **server-side** di Controller/Policy.
- Author selalu dapat melihat kampanye yang mereka buat, terlepas dari scope.

---

## 7. Permission Keys

Communication & Campaign menggunakan **2 resource groups**:

### 7.1 Campaigns (`communication.campaigns.*`)

| # | Permission Key | Description |
|---|---|---|
| 1 | `communication.campaigns.view` | View campaign list and detail. Scope-filtered. |
| 2 | `communication.campaigns.create` | Create new campaign. Generates COM number. |
| 3 | `communication.campaigns.update` | Update campaign (only in draft status). |
| 4 | `communication.campaigns.publish` | Publish campaign (draft → published). Triggers notification blast. |
| 5 | `communication.campaigns.export` | Export campaigns to CSV. Scope-filtered. |

### 7.2 Acknowledgments (`communication.acknowledgments.*`)

| # | Permission Key | Description |
|---|---|---|
| 6 | `communication.acknowledgments.view` | View acknowledgment list on campaign detail page. |

### Implementation Notes

- Permission keys mengikuti format `{module}.{entity}.{action}`.
- Keys harus di-register di seeder (tambahkan ke `CorePermissions::all()`).
- Keys di-assign ke roles via `CorePermissions::roleMap()`.
- File upload/download menggunakan permission core: `core.files.upload`, `core.files.download`.
- Semua user dapat acknowledge kampanye yang ditargetkan kepada mereka (tidak memerlukan permission khusus — di-enforce via target_audience check).

---

## 8. Role-Permission Matrix

### 8.1 Campaigns (`communication.campaigns.*`)

| Role | `view` | `create` | `update` | `publish` | `export` |
|---|:---:|:---:|:---:|:---:|:---:|
| Super Admin | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Manager | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Officer | ✅ | ✅ | ✅ | ✅ | ✅ |
| Supervisor | ✅ | ❌ | ❌ | ❌ | ❌ |
| Department Head | ✅ | ❌ | ❌ | ❌ | ❌ |
| Employee/Reporter | ✅ | ❌ | ❌ | ❌ | ❌ |
| Contractor | ✅ | ❌ | ❌ | ❌ | ❌ |
| Auditor | ✅ | ❌ | ❌ | ❌ | ✅ |
| Top Management | ✅ | ❌ | ❌ | ❌ | ✅ |

### 8.2 Acknowledgments (`communication.acknowledgments.view`)

| Role | `view` |
|---|:---:|
| Super Admin | ✅ |
| Admin | ✅ |
| QHSSE Manager | ✅ |
| QHSSE Officer | ✅ |
| Supervisor | ❌ |
| Department Head | ❌ |
| Employee/Reporter | ❌ |
| Contractor | ❌ |
| Auditor | ✅ |
| Top Management | ✅ |

### Notes

- **Employee/Reporter** dan **Contractor** dapat view kampanye yang ditargetkan kepada mereka (scope: target_audience).
- **Supervisor** dan **Department Head** dapat view kampanye untuk department-nya.
- **Auditor** dan **Top Management** hanya view + export (read-only).
- **QHSSE Officer/Manager** dapat create/update/publish kampanye.
- Semua permission di-enforce **server-side** via Laravel Policy atau Gate.

---

## 9. Notification Events

2 event notifikasi untuk modul Communication & Campaign:

### 9.1 `communication.campaign_published`

| Property | Value |
|---|---|
| **Trigger** | Kampanye di-publish (status: draft → published) |
| **Recipients** | Semua user yang termasuk target audience (resolved saat publish) |
| **Type** | `communication.campaign_published` |
| **Title (template)** | `{type_label}: {campaign.title}` |
| **Message (template)** | `Kampanye "{campaign.title}" ({campaign.campaign_number}) telah dipublikasi. {acknowledgment_message}` |
| **Action URL** | `/campaigns/{campaign.id}` |
| **Module/Reference** | `module_name='communication'`, `reference_id={campaign.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

**Acknowledgment message by type:**

- `safety_alert`: `Mohon segera baca dan konfirmasi (acknowledge) safety alert ini.`
- `lesson_learned`: `Silakan baca pelajaran yang dapat dipetik dari kejadian ini.`
- `campaign`: `Ikuti kampanye ini untuk meningkatkan budaya keselamatan.`
- `announcement`: `Mohon perhatikan pengumuman ini.`
- `newsletter`: `Buletin QHSSE terbaru telah terbit.`

### 9.2 `communication.acknowledgment_reminder`

| Property | Value |
|---|---|
| **Trigger** | Scheduled command mendeteksi safety alert yang belum di-acknowledge oleh user |
| **Recipients** | User yang belum acknowledge safety alert dalam target audience |
| **Type** | `communication.acknowledgment_reminder` |
| **Title (template)** | `Pengingat: Safety Alert Belum Dikonfirmasi — {campaign.title}` |
| **Message (template)** | `Anda belum mengkonfirmasi (acknowledge) safety alert "{campaign.title}" ({campaign.campaign_number}). Mohon segera baca dan konfirmasi.` |
| **Action URL** | `/campaigns/{campaign.id}` |
| **Module/Reference** | `module_name='communication'`, `reference_id={campaign.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### Implementation Notes

- Notification dikirim setelah DB transaction commit (Event/Listener or Observer pattern).
- Acknowledgment reminder dijalankan via scheduled command: `php artisan communication:send-acknowledgment-reminders` (daily).
- In-app notification: sync write to `core_notifications`.
- Email notification: dispatch to queue if available, fallback sync.
- Recipient resolution: query users with matching scope (site/department/role) based on `target_audience`.

---

## 10. File Attachment Rules

### 10.1 Storage Configuration

| Property | Value |
|---|---|
| **Service** | Core ManagedFileService (`App\Core\File\ManagedFileService`) |
| **Table** | `managed_files` |
| **module_name** | `communication` |
| **reference_id** | `campaigns.id` |
| **collection** | `attachment` |
| **Disk** | `local` (private, not publicly accessible) |
| **Path pattern** | `communication/{campaign_id}/attachment/{stored_name}` |

### 10.2 Validation Rules

| Rule | Value |
|---|---|
| **Allowed extensions** | `pdf`, `jpg`, `jpeg`, `png`, `doc`, `docx`, `xls`, `xlsx`, `ppt`, `pptx` |
| **Allowed MIME types** | `application/pdf`, `image/jpeg`, `image/png`, `application/msword`, `application/vnd.openxmlformats-officedocument.wordprocessingml.document`, `application/vnd.ms-excel`, `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`, `application/vnd.ms-powerpoint`, `application/vnd.openxmlformats-officedocument.presentationml.presentation` |
| **Max file size** | 10 MB per file |
| **Max files per campaign** | 5 (multiple attachments allowed) |
| **Filename** | Original filename stored in `original_name`; generated UUID-based name in `stored_name` |

### 10.3 Access Rules

- **Upload**: User must have `communication.campaigns.update` (campaign must be in draft status).
- **Download**: User must have `communication.campaigns.view` and be within target audience scope of the campaign.
- **Delete/Replace**: User must have `communication.campaigns.update`. Only allowed in draft status.
- Download endpoint streams file from private storage; no direct public URL.
- File access logged in audit trail (`communication.file.downloaded`).

### 10.4 File Metadata

Each file record in `managed_files` includes:

- `module_name`: `communication`
- `reference_id`: `campaigns.id`
- `collection`: `attachment`
- `disk`: `local`
- `path`: storage path
- `original_name`: user's original filename
- `stored_name`: generated filename
- `mime_type`: detected MIME
- `extension`: file extension
- `size`: file size in bytes
- `checksum`: SHA-256 hash (optional)
- `metadata`: JSON (e.g., `{"uploaded_at": "2026-07-11T14:30:00"}`)
- `uploaded_by`: user ID
- `deleted_at`: soft delete timestamp
- `deleted_by`: user ID who deleted

---

## 11. Dashboard Metrics

### 11.1 KPI Cards

| Metric | Query | Display |
|---|---|---|
| **Total Kampanye** | Count all campaigns in scope | Number + icon |
| **Draft** | Count where status = `draft` | Number, yellow |
| **Published** | Count where status = `published` | Number, green |
| **Safety Alert Aktif** | Count where type = `safety_alert` AND status = `published` AND (expires_at IS NULL OR expires_at > now()) | Number, **red badge** |
| **Total Views** | Sum of view_count for published campaigns | Number |
| **Avg. Acknowledgment Rate** | (acknowledgments / target_audience_count) × 100 for safety alerts | Percentage |
| **Bulan Ini** | Count published in current month | Number + trend arrow |

### 11.2 Charts

| Chart | Type | Data |
|---|---|---|
| **Monthly Trend** | Line chart | Campaign count by month (last 12 months) |
| **By Type** | Donut | Count by type (safety_alert, lesson_learned, campaign, announcement, newsletter) |
| **By Status** | Donut | Count by status (draft, published) |
| **Acknowledgment Rate** | Bar chart | Acknowledgment rate per safety alert (top 10) |

### 11.3 Table Widgets

| Widget | Columns | Filter |
|---|---|---|
| **Safety Alert Aktif** | Nomor, Judul, Published At, Acknowledged/Total, Rate | type=safety_alert, status=published, not expired |
| **Kampanye Terbaru** | Nomor, Judul, Tipe, Status, Created At | Last 10, scoped |
| **Pending Acknowledgment** | Judul, Target Audience, Published At, Unacknowledged Count | type=safety_alert, status=published, has unacknowledged users |

### 11.4 Filters

Dashboard metrics support:
- Date range filter (default: current year)
- Type filter
- Status filter
- Site filter (for QHSSE Manager+)

---

## 12. Export Spec

### 12.1 CSV Export — Campaigns

| Property | Value |
|---|---|
| **Format** | CSV (UTF-8 with BOM for Excel compatibility) |
| **Filename** | `campaigns_export_{YYYYMMDD_HHmmss}.csv` |
| **Permission** | `communication.campaigns.export` |
| **Scope** | Follows user's data scope |
| **Filter** | Follows current list page filter parameters |

### 12.2 CSV Columns (in order)

| # | Column Header | Field Source | Notes |
|---|---|---|---|
| 1 | `Nomor` | `campaign_number` | COM-YYYY-NNNN |
| 2 | `Judul` | `title` | |
| 3 | `Tipe` | `type` | safety_alert/lesson_learned/campaign/announcement/newsletter |
| 4 | `Target Audiens` | `target_audience` | all/specific_site/specific_department/specific_role |
| 5 | `Site` | `site.name` | Via `site_id`, nullable |
| 6 | `Departemen` | `department.name` | Via `department_id`, nullable |
| 7 | `Role Target` | `target_role` | Nullable |
| 8 | `Status` | `status` | draft/published |
| 9 | `Published At` | `published_at` | Format: YYYY-MM-DD HH:mm |
| 10 | `Expires At` | `expires_at` | Format: YYYY-MM-DD, nullable |
| 11 | `Views` | `view_count` | Integer |
| 12 | `Acknowledgments` | `acknowledgments_count` | Count via relation |
| 13 | `Author` | `author.name` | Via `author_id` |
| 14 | `Dibuat` | `created_at` | Format: YYYY-MM-DD |

---

## 13. Acceptance Criteria

1. User dengan permission `communication.campaigns.create` dapat membuat kampanye baru dengan nomor COM otomatis.
2. User dengan permission `communication.campaigns.update` dapat mengedit kampanye yang masih berstatus draft.
3. User dengan permission `communication.campaigns.publish` dapat mempublish kampanye yang memicu notifikasi blast ke target audience.
4. Sistem mengirim notifikasi in-app kepada semua user yang termasuk target audience saat kampanye di-publish.
5. User yang termasuk target audience dapat meng-acknowledge kampanye (terutama safety alert).
6. Sistem mencegah double acknowledgment (unique constraint: campaign_id + user_id).
7. `view_count` di-increment ketika user mengakses halaman detail kampanye (dengan deduplication per user).
8. Kampanye yang sudah published tidak dapat di-edit (kecuali expires_at oleh Super Admin).
9. Target audience filtering bekerja: user hanya melihat kampanye yang ditargetkan kepada mereka.
10. User dengan permission `communication.acknowledgments.view` dapat melihat daftar acknowledgment di halaman detail.
11. User dengan permission `communication.campaigns.export` dapat export daftar kampanye ke CSV.
12. Scheduled command `communication:send-acknowledgment-reminders` mengirim reminder untuk safety alert yang belum di-acknowledge.
13. Semua perubahan kritikal (create, update, publish, acknowledge) tercatat di audit trail dan activity log.
14. UI menampilkan filter tipe dan status di halaman index.
15. UI menampilkan target audience selector di form (dengan conditional fields).
16. UI menampilkan daftar acknowledgment dan view count di halaman detail.

---

## 14. Open Questions

1. **Rich text editor**: Apakah menggunakan TipTap, Quill, atau Markdown? Default: TipTap (sesuai stack Inertia React).
2. **Scheduled publishing**: Apakah kampanye dapat di-schedule untuk publish otomatis di tanggal tertentu? (Future enhancement, Phase 1: manual publish only).
3. **Multi-language content**: Apakah konten kampanye perlu mendukung multi-bahasa? (Phase 1: Bahasa Indonesia only).
4. **Email template**: Apakah email blast menggunakan template HTML atau plain text? (Default: HTML dengan template sederhana).
5. **Read receipt vs acknowledgment**: Apakah perlu membedakan "telah dibaca" (view) dari "acknowledgment" (konfirmasi)? (Saat ini: view = dibaca, acknowledgment = konfirmasi eksplisit).
