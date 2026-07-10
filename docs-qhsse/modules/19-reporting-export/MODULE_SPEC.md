# Module Spec — Reporting & Export

> **Module ID:** `19-reporting-export`
> **Module Code:** `reporting`
> **Phase:** Phase 5
> **Status:** Ready for coding

---

## 1. Tujuan Modul

Modul Reporting & Export adalah **sistem pembuat laporan terpusat** dalam platform QHSSE. Modul ini BUKAN modul CRUD biasa — melainkan **report builder** yang mengagregasi data dari SEMUA modul QHSSE untuk menghasilkan laporan terstruktur dalam berbagai format (CSV, PDF, Excel).

Tujuan utama:

- Menyediakan **template laporan** yang sudah pre-defined untuk tipe-tipe laporan standar QHSSE.
- Mengagregasi data dari **semua modul** — Incident, CAPA, Inspection, Audit, Training, Risk, Environment, Security, Quality, Legal, Emergency, Permit, Contractor, Asset, Communication.
- Menyediakan **konfigurasi parameter** laporan: rentang tanggal, site, departemen, dan format output (CSV/PDF/Excel).
- Menjalankan **generasi laporan secara asynchronous** (queued job) karena agregasi data lintas modul bisa berat.
- Menyimpan **hasil laporan** sebagai file yang dapat di-download kapan saja.
- Menyediakan **dashboard reporting** untuk melihat laporan yang sudah di-generate.
- Menjamin **data scope** — user hanya bisa generate dan view laporan sesuai scope data mereka.
- Mendukung **custom report template** yang dibuat oleh QHSSE Manager/Admin.
- Mengirim **notifikasi** saat laporan selesai di-generate atau gagal.
- Menyediakan **audit trail** untuk semua aktivitas pembuatan dan download laporan.

---

## 2. Dependency

### Core Foundation (Phase 0 — complete)

| Dependency | Usage in This Module |
|---|---|
| **Auth** | User login, session, role identification |
| **PermissionService** (Spatie) | 6 permission keys `reporting.templates.*` + `reporting.reports.*` |
| **FileService** | Store generated report files via `managed_files` table |
| **NotificationService** | In-app + email notifications saat report selesai/gagal |
| **AuditTrailService** | Audit log via `audit_logs` table |
| **ActivityLogService** | Activity timeline via `activity_logs` table |
| **ListQuery** | Paginated list dengan search, filter, sort |
| **Queue/Job** | Async report generation via Laravel Queue |
| **MasterData** | Sites, Departments, Users |

### Cross-Module Dependencies

| Module | Relationship |
|---|---|
| `01-dashboard-kpi` | Reporting menggunakan KPI data untuk laporan bulanan/tahunan |
| `02-incident-reporting` | Data insiden untuk `incident_summary`, `monthly_qhsse`, `annual_qhsse` |
| `03-investigation-rca` | Data investigasi untuk laporan insiden komprehensif |
| `04-capa-action-tracking` | Data CAPA untuk `capa_summary`, laporan bulanan/tahunan |
| `05-inspection-checklist` | Data inspection untuk `inspection_summary` |
| `06-audit-management` | Data audit untuk `audit_summary` |
| `07-document-control` | Status dokumen untuk laporan kepatuhan |
| `08-training-competency` | Data training untuk `training_compliance` |
| `09-permit-to-work` | Data permit untuk laporan bulanan/tahunan |
| `10-environmental-management` | Data environmental untuk laporan bulanan/tahunan |
| `11-security-management` | Data security untuk laporan bulanan/tahunan |
| `12-quality-management` | Data quality untuk laporan bulanan/tahunan |
| `13-risk-management` | Data risk untuk laporan bulanan/tahunan |
| `14-legal-compliance` | Data kepatuhan legal untuk laporan tahunan |
| `15-contractor-management` | Data contractor untuk laporan bulanan/tahunan |
| `16-asset-management` | Data asset untuk laporan bulanan/tahunan |
| `17-communication-management` | Data komunikasi untuk laporan bulanan |
| `18-emergency-management` | Data emergency untuk laporan bulanan/tahunan |

### Tech Stack

- Laravel 12 (backend: Form Request, Policy, Service, Eloquent, Queue Jobs)
- Inertia.js React + TypeScript (frontend)
- Tailwind CSS (styling, UI in Indonesian)
- PostgreSQL (data layer)
- Redis (queue driver untuk async report generation)
- Spatie Laravel Permission (RBAC)
- Laravel Excel / Dompdf / CsvExporter (format output)

---

## 3. User Roles

| # | Role | Deskripsi Peran dalam Reporting |
|---|---|---|
| 1 | **Super Admin** | Akses penuh ke semua template dan report. Bypass scope. |
| 2 | **Admin** | Akses sistem penuh termasuk konfigurasi template. |
| 3 | **QHSSE Manager** | Create/update template, generate & download semua report. Scope: all sites. |
| 4 | **QHSSE Officer** | Generate & download report. View templates. Scope: assigned site(s). |
| 5 | **Supervisor** | Generate & download report untuk departemen. Scope: department. |
| 6 | **Department Head** | View & download report untuk departemen. Scope: department. |
| 7 | **Employee/Reporter** | Tidak ada akses reporting. |
| 8 | **Contractor** | Tidak ada akses reporting. |
| 9 | **Auditor** | View & download semua report dalam scope audit. Tidak generate. |
| 10 | **Top Management** | View & download semua report. Scope: all. Tidak generate. |

---

## 4. Fitur Lengkap

### 4.1 Report Template Management

- **Template Index** — Halaman daftar template laporan yang tersedia. Pre-defined templates untuk 8 tipe laporan standar, plus custom templates yang dibuat oleh QHSSE Manager/Admin.
- **Create Template** — Hanya untuk tipe `custom`. Pre-defined templates (incident_summary, capa_summary, dll.) sudah di-seed dan tidak bisa dibuat baru.
- **Update Template** — Edit konfigurasi template custom. Pre-defined templates hanya bisa di-update `is_active` dan `description`.
- **Activate/Deactivate** — Toggle `is_active` untuk menampilkan/menyembunyikan template dari daftar yang bisa dipilih user.

### 4.2 Report Generation

- **Configure Page** — User memilih template, mengisi parameter (rentang tanggal, site, departemen, format output), lalu submit untuk generate.
- **Async Generation** — Report di-generate via queued job (`GenerateReportJob`). Status saved_report berubah dari `pending` → `processing` → `completed` (atau `failed`).
- **Progress Tracking** — UI menampilkan status real-time: pending, processing, completed, failed.
- **File Output** — Hasil generate disimpan sebagai file di disk private. Path disimpan di `saved_reports.file_path`.
- **Download** — User dengan permission `reporting.reports.download` dapat download file laporan.

### 4.3 Saved Reports

- **Saved Reports List** — Halaman daftar semua laporan yang sudah di-generate. Filter by template type, status, date range, generated_by.
- **Re-generate** — User dapat re-generate laporan dengan parameter yang sama atau yang dimodifikasi.
- **Delete** — Soft delete saved report. Hanya Super Admin / Admin / QHSSE Manager.
- **Download** — Download file laporan yang sudah di-generate.

### 4.4 Report Types (8 Types)

| # | Type | Nama | Deskripsi | Data Sources |
|---|---|---|---|---|
| 1 | `incident_summary` | **Ringkasan Insiden** | Laporan ringkasan insiden per periode, site, departemen | incidents, investigations |
| 2 | `capa_summary` | **Ringkasan CAPA** | Status tindakan korektif/preventif, overdue, closure rate | capa_actions |
| 3 | `inspection_summary` | **Ringkasan Inspection** | Hasil inspection, compliance rate, findings | inspections, inspection_items |
| 4 | `audit_summary` | **Ringkasan Audit** | Audit findings, status, closure | audit_findings, audit_schedules |
| 5 | `training_compliance` | **Kepatuhan Training** | Status kelengkapan training per karyawan/departemen | training_records, training_enrollments |
| 6 | `monthly_qhsse` | **Laporan Bulanan QHSSE** | Laporan komprehensif bulanan: insiden + CAPA + inspection + audit + training + permit + environment + security | ALL modules |
| 7 | `annual_qhsse` | **Laporan Tahunan QHSSE** | Laporan komprehensif tahunan dengan tren dan analisis | ALL modules |
| 8 | `custom` | **Laporan Custom** | Template custom yang dapat dikonfigurasi oleh QHSSE Manager/Admin | Configurable |

### 4.5 Report Parameters

Parameter yang dapat dikonfigurasi saat generate laporan:

| Parameter | Type | Required | Description |
|---|---|---|---|
| `date_from` | date | ✅ | Tanggal mulai periode laporan |
| `date_to` | date | ✅ | Tanggal akhir periode laporan |
| `site_id` | bigint | ❌ | Filter by site (null = semua site in scope) |
| `department_id` | bigint | ❌ | Filter by departemen (null = semua departemen) |
| `format` | enum | ✅ | `csv`, `pdf`, `excel` |
| `include_charts` | boolean | ❌ | Sertakan grafik (hanya untuk PDF/Excel) |

### 4.6 Notification

- 2 event notifikasi: `report.completed`, `report.failed`.
- In-app notification via `core_notifications` table.
- Email notification jika configured.

### 4.7 Dashboard & Metrics

- Dashboard widget: total reports generated, pending count, completed count, failed count, breakdown by type.
- Recent reports table.

---

## 5. Report Template Categories

8 tipe laporan, stored di kolom `type` pada tabel `report_templates`:

### Pre-defined Templates (Seeded)

| # | Code | Nama | Deskripsi |
|---|---|---|---|
| 1 | `incident_summary` | **Ringkasan Insiden** | Laporan ringkasan insiden per periode. Menampilkan: jumlah insiden by severity, by type, by site, trend bulanan, status distribution. |
| 2 | `capa_summary` | **Ringkasan CAPA** | Laporan status CAPA. Menampilkan: total open, in_progress, waiting_verification, closed, rejected, overdue count, closure rate, by source module, by priority. |
| 3 | `inspection_summary` | **Ringkasan Inspection** | Laporan hasil inspection. Menampilkan: total inspection, pass/fail rate, findings by category, compliance rate per site. |
| 4 | `audit_summary` | **Ringkasan Audit** | Laporan audit findings. Menampilkan: total audit, findings by severity, status distribution, closure rate. |
| 5 | `training_compliance` | **Kepatuhan Training** | Laporan status training. Menampilkan: enrollment count, completion rate, overdue training, compliance per departemen. |
| 6 | `monthly_qhsse` | **Laporan Bulanan QHSSE** | Laporan komprehensif bulanan. Menampilkan: insiden, CAPA, inspection, audit, training, permit, environment, security, quality — semua dalam satu laporan. |
| 7 | `annual_qhsse` | **Laporan Tahunan QHSSE** | Laporan komprehensif tahunan dengan tren 12 bulan, benchmarking, dan analisis. |

### Custom Templates

| # | Code | Nama | Deskripsi |
|---|---|---|---|
| 8 | `custom` | **Laporan Custom** | Template custom yang dibuat oleh QHSSE Manager/Admin. Konfigurasi disimpan di `config` JSON. |

---

## 6. Business Rules

### BR-01: No Numbering Needed

- Modul reporting **tidak menggunakan NumberingService**. Tidak ada nomor unik yang perlu di-generate.
- `saved_reports.name` diisi oleh user saat generate atau auto-generated dari template name + date.

### BR-02: Pre-defined Templates Cannot Be Created

- Template dengan tipe `incident_summary`, `capa_summary`, `inspection_summary`, `audit_summary`, `training_compliance`, `monthly_qhsse`, `annual_qhsse` sudah di-seed.
- User hanya bisa **membuat template baru dengan tipe `custom`**.
- Endpoint `store` akan menolak jika `type` != `custom`.

### BR-03: Pre-defined Templates Limited Update

- Pre-defined templates (non-custom) hanya bisa di-update `description` dan `is_active`.
- Field `config` dan `name` pada pre-defined templates tidak dapat diubah.
- Custom templates dapat di-update sepenuhnya.

### BR-04: Async Report Generation

- Saat user submit generate, `saved_reports` record dibuat dengan `status = 'pending'`.
- `GenerateReportJob` di-dispatch ke queue.
- Job mengubah status: `pending` → `processing` → `completed` (atau `failed`).
- Jika gagal, `file_path` = NULL, `status` = `failed`, dan error message disimpan di `parameters.error`.
- Notifikasi dikirim saat completed atau failed.

### BR-05: Report Parameters Validation

- `date_from` wajib, harus tanggal valid.
- `date_to` wajib, harus tanggal valid dan >= `date_from`.
- Rentang maksimum: 2 tahun (730 hari). Jika lebih, ditolak dengan error.
- `site_id` opsional, jika diisi harus exists di `sites` table.
- `department_id` opsional, jika diisi harus exists di `departments` table dan `site_id` harus match.
- `format` wajib, harus salah satu: `csv`, `pdf`, `excel`.

### BR-06: Data Scope Enforcement

| Scope | Who | What They See |
|---|---|---|
| `own` | Employee/Reporter, Contractor | No access to reporting |
| `department` | Supervisor, Department Head | Reports for their department only |
| `site` | QHSSE Officer | Reports for their assigned site(s) |
| `all` | QHSSE Manager, Top Management, Auditor, Super Admin, Admin | All reports |

- Scope check dilakukan **server-side** di Controller/Policy.
- Saat generate, parameter `site_id` dan `department_id` difilter sesuai scope user.
- Saat view saved reports, hanya report yang di-generate oleh user dengan scope yang overlap yang terlihat.

### BR-07: File Storage & Access

- Generated report files disimpan di disk `local` (private, not publicly accessible).
- Path pattern: `reports/{saved_report_id}/{filename}.{ext}`.
- Download melalui authorized endpoint (permission `reporting.reports.download` + scope check).
- File dihapus dari disk saat saved_report di-delete (hard delete setelah soft delete grace period).

### BR-08: Re-generate Report

- User dapat re-generate laporan yang sudah ada.
- Re-generate membuat record `saved_reports` baru dengan parameter yang sama (atau yang dimodifikasi).
- Record lama tetap ada (tidak di-overwrite).

### BR-09: Audit Trail

Audit trail dicatat untuk event berikut (via `audit_logs` table, `module_name='reporting'`):

| Event | Auditable | Old/New Values |
|---|---|---|
| `reporting.template.created` | ReportTemplate | new_values: all fields |
| `reporting.template.updated` | ReportTemplate | changed fields only |
| `reporting.template.activated` | ReportTemplate | is_active change |
| `reporting.template.deactivated` | ReportTemplate | is_active change |
| `reporting.report.generated` | SavedReport | new_values: all fields |
| `reporting.report.completed` | SavedReport | status + file_path |
| `reporting.report.failed` | SavedReport | status + error |
| `reporting.report.downloaded` | SavedReport | metadata: user, ip |
| `reporting.report.deleted` | SavedReport | soft delete |

### BR-10: Template Config JSON Structure

```json
{
  "sections": [
    {
      "key": "summary",
      "label": "Ringkasan Eksekutif",
      "enabled": true
    },
    {
      "key": "incident_stats",
      "label": "Statistik Insiden",
      "enabled": true,
      "data_source": "incident",
      "group_by": ["severity", "site", "month"]
    },
    {
      "key": "capa_stats",
      "label": "Statistik CAPA",
      "enabled": true,
      "data_source": "capa",
      "group_by": ["status", "priority", "source_module"]
    }
  ],
  "default_parameters": {
    "date_range": "last_month",
    "site_id": null,
    "department_id": null,
    "format": "pdf"
  }
}
```

---

## 7. Permission Keys

6 permission keys untuk modul Reporting, dibagi menjadi 2 resource groups:

### Resource Group 1: `reporting.templates.*`

| # | Permission Key | Description |
|---|---|---|
| 1 | `reporting.templates.view` | View template list dan detail. |
| 2 | `reporting.templates.create` | Create custom template (hanya tipe `custom`). |
| 3 | `reporting.templates.update` | Update template. Pre-defined: hanya description + is_active. Custom: semua field. |

### Resource Group 2: `reporting.reports.*`

| # | Permission Key | Description |
|---|---|---|
| 4 | `reporting.reports.view` | View saved reports list dan detail. Scope-filtered. |
| 5 | `reporting.reports.generate` | Generate new report from template. Creates saved_report + dispatches job. |
| 6 | `reporting.reports.download` | Download generated report file. Scope-filtered. |

### Implementation Notes

- Permission keys mengikuti format `{module}.{entity}.{action}` → `reporting.templates.*` + `reporting.reports.*`.
- Keys harus di-register di `CorePermissions::all()`.
- Keys di-assign ke roles via `CorePermissions::roleMap()`.
- File download menggunakan permission `reporting.reports.download`.
- Tidak ada workflow transition (reports are generated on demand).
- Tidak ada NumberingService (no numbering needed).

---

## 8. Role-Permission Matrix

### `reporting.templates.*`

| Role | `view` | `create` | `update` |
|---|:---:|:---:|:---:|
| Super Admin | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ |
| QHSSE Manager | ✅ | ✅ | ✅ |
| QHSSE Officer | ✅ | ❌ | ❌ |
| Supervisor | ✅ | ❌ | ❌ |
| Department Head | ✅ | ❌ | ❌ |
| Employee/Reporter | ❌ | ❌ | ❌ |
| Contractor | ❌ | ❌ | ❌ |
| Auditor | ✅ | ❌ | ❌ |
| Top Management | ✅ | ❌ | ❌ |

### `reporting.reports.*`

| Role | `view` | `generate` | `download` |
|---|:---:|:---:|:---:|
| Super Admin | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ |
| QHSSE Manager | ✅ | ✅ | ✅ |
| QHSSE Officer | ✅ | ✅ | ✅ |
| Supervisor | ✅ | ✅ | ✅ |
| Department Head | ✅ | ❌ | ✅ |
| Employee/Reporter | ❌ | ❌ | ❌ |
| Contractor | ❌ | ❌ | ❌ |
| Auditor | ✅ | ❌ | ✅ |
| Top Management | ✅ | ❌ | ✅ |

### Notes

- **QHSSE Manager & Admin** dapat create/update template custom dan generate/download semua report.
- **QHSSE Officer** dapat generate dan download report untuk site yang di-assign.
- **Supervisor** dapat generate dan download report untuk departemen mereka.
- **Department Head** dapat view dan download report, tetapi tidak dapat generate (hanya melihat yang sudah ada).
- **Auditor & Top Management** hanya view + download (read-only), tidak dapat generate.
- **Employee/Reporter & Contractor** tidak memiliki akses ke modul reporting.
- Semua permission di-enforce **server-side** via Laravel Policy atau Gate.

---

## 9. Notification Events

2 event notifikasi untuk modul Reporting:

### 9.1 `report.completed`

| Property | Value |
|---|---|
| **Trigger** | `GenerateReportJob` selesai dengan sukses |
| **Recipients** | User yang men-generate report (`generated_by`) |
| **Type** | `report.completed` |
| **Title (template)** | `Laporan Selesai: {report.name}` |
| **Message (template)** | `Laporan {report.name} telah selesai di-generate. Format: {report.format}. Anda dapat mengunduh laporan sekarang.` |
| **Action URL** | `/reports/saved/{report.id}` |
| **Module/Reference** | `module_name='reporting'`, `reference_id={report.id}` |
| **Channel** | In-app + Email (if configured) |

### 9.2 `report.failed`

| Property | Value |
|---|---|
| **Trigger** | `GenerateReportJob` gagal (exception) |
| **Recipients** | User yang men-generate report (`generated_by`) |
| **Type** | `report.failed` |
| **Title (template)** | `Laporan Gagal: {report.name}` |
| **Message (template)** | `Laporan {report.name} gagal di-generate. Error: {error_message}. Silakan coba lagi atau hubungi administrator.` |
| **Action URL** | `/reports/saved/{report.id}` |
| **Module/Reference** | `module_name='reporting'`, `reference_id={report.id}` |
| **Channel** | In-app + Email (if configured) |

---

## 10. File Attachment Rules

### 10.1 Storage Configuration

| Property | Value |
|---|---|
| **Service** | Core FileService (`App\Core\File\FileService`) |
| **Table** | `managed_files` |
| **module_name** | `reporting` |
| **reference_id** | `saved_reports.id` |
| **collection** | `generated_report` |
| **Disk** | `local` (private, not publicly accessible) |
| **Path pattern** | `reports/{saved_report_id}/{filename}.{ext}` |

### 10.2 File Formats

| Format | Extension | MIME Type | Library |
|---|---|---|---|
| CSV | `.csv` | `text/csv` | `CsvExporter` (core) |
| PDF | `.pdf` | `application/pdf` | `Dompdf` atau `barryvdh/laravel-dompdf` |
| Excel | `.xlsx` | `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` | `Maatwebsite/Laravel-Excel` |

### 10.3 Access Rules

- **Download**: User must have `reporting.reports.download` AND be within data scope.
- **Delete file**: Only Super Admin / Admin / QHSSE Manager. File dihapus dari disk saat record dihapus.
- **Retention**: Generated report files disimpan selama record `saved_reports` ada. Tidak ada auto-expiry.

---

## 11. Dashboard Metrics

### 11.1 KPI Cards

| Metric | Query | Display |
|---|---|---|
| **Total Laporan** | Count all saved_reports in scope | Number + icon |
| **Pending** | Count where status = `pending` | Number, blue |
| **Processing** | Count where status = `processing` | Number, yellow |
| **Completed** | Count where status = `completed` | Number, green |
| **Failed** | Count where status = `failed` | Number, **red badge** |
| **Bulan Ini** | Count generated in current month | Number + trend arrow |

### 11.2 Charts

| Chart | Type | Data |
|---|---|---|
| **Monthly Trend** | Line chart | Report count by month (last 12 months), split by type |
| **By Type** | Donut | Count by report type (incident_summary, capa_summary, dll.) |
| **By Status** | Donut | Count by status (pending, processing, completed, failed) |
| **By Format** | Bar chart | Count by format (csv, pdf, excel) |
| **By Site** | Horizontal bar | Count by site (top 10) |

### 11.3 Table Widgets

| Widget | Columns | Filter |
|---|---|---|
| **Recent Reports** | Name, Template, Status, Format, Generated By, Generated At | Last 10, scoped |
| **Pending/Processing** | Name, Template, Status, Started At | Status in pending/processing |

---

## 12. Export Spec

### 12.1 Report Output Formats

| Format | Use Case | Notes |
|---|---|---|
| **CSV** | Data export untuk analisis lebih lanjut di Excel/Power BI | UTF-8 with BOM, semicolon separator untuk locale ID |
| **PDF** | Laporan formal untuk distribusi | Include header/footer, page numbers, charts (jika `include_charts=true`) |
| **Excel** | Laporan multi-sheet dengan formatting | Sheet per section, conditional formatting, charts |

### 12.2 Monthly QHSSE Report Structure (Contoh)

```
LAPORAN BULANAN QHSSE
Periode: Januari 2026
Site: Jakarta Plant
Departemen: Semua

1. RINGKASAN EKSEKUTIF
   - Total Insiden: 12 (↑ 2 dari bulan lalu)
   - CAPA Open: 8, Closed: 15
   - Inspection Compliance: 94%
   - Audit Findings: 5 open, 3 closed
   - Training Compliance: 87%

2. STATISTIK INSIDEN
   [Tabel: by severity, by type, by area]

3. STATUS CAPA
   [Tabel: by status, by priority, overdue list]

4. HASIL INSPECTION
   [Tabel: total, pass/fail, findings]

5. AUDIT FINDINGS
   [Tabel: by severity, status, closure rate]

6. TRAINING COMPLIANCE
   [Tabel: enrollment, completion, overdue]

7. PERMIT TO WORK
   [Tabel: active, expired, by type]

8. ENVIRONMENTAL
   [Tabel: incidents, compliance]

9. SECURITY
   [Tabel: incidents, patrols]
```

---

## 13. Acceptance Criteria

- [ ] User dengan permission `reporting.templates.view` dapat melihat daftar template laporan.
- [ ] Pre-defined templates (7 tipe) sudah ter-seed dan muncul di template index.
- [ ] User dengan permission `reporting.templates.create` dapat membuat custom template.
- [ ] User dengan permission `reporting.reports.generate` dapat mengkonfigurasi dan generate laporan.
- [ ] Generate laporan berjalan async (queued job) dan status terupdate: pending → processing → completed.
- [ ] Jika generate gagal, status = `failed` dan notifikasi error dikirim.
- [ ] User dengan permission `reporting.reports.download` dapat download file laporan.
- [ ] Data scope di-enforce: user hanya melihat report sesuai scope mereka.
- [ ] Notifikasi `report.completed` dan `report.failed` terkirim ke user yang generate.
- [ ] Audit trail tercatat untuk semua aktivitas (create template, generate, download, delete).
- [ ] Format CSV, PDF, dan Excel didukung.
- [ ] Monthly QHSSE report mengagregasi data dari semua modul.
- [ ] Annual QHSSE report menampilkan tren 12 bulan.

---

## 14. Open Questions

1. **Scheduled Reports** — Apakah perlu fitur auto-generate laporan terjadwal (misalnya: monthly report auto-generate setiap tanggal 1)? (Defer to Phase 6)
2. **Email Distribution** — Apakah report perlu dikirim via email ke daftar recipient otomatis setelah generate? (Defer to Phase 6)
3. **Custom Report Builder UI** — Apakah perlu drag-and-drop builder untuk custom report? (Defer ke fase mendatang)
4. **Report Versioning** — Apakah perlu menyimpan history versi dari re-generate? Saat ini setiap re-generate membuat record baru.
5. **Watermark** — Apakah PDF report perlu watermark (misalnya "CONFIDENTIAL")? (Defer)
