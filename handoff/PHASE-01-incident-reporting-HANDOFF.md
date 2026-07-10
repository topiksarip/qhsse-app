# Handoff — Phase 1 Incident Reporting

## 1. Status

- Phase: 1 — Incident Reporting
- Status: Completed
- Date: 2026-07-11
- Executor: AI Agent
- Project path: `/home/qhsse/qhsse-app-v3`

## 2. Scope Dikerjakan

- Migration: `incidents` table + `incident_involved_persons` pivot
- Model: `IncidentReport` with 6 relationships (site, area, department, reporter, severity, priority, involvedPersons)
- Factory: `IncidentReportFactory` with valid test data
- Permissions: 7 `incident.reports.*` keys added to CorePermissions + role-permission matrix (10 roles)
- Form Requests: `StoreIncidentReportRequest` + `UpdateIncidentReportRequest` with full validation
- Controller: `IncidentReportController` with index, create, store, show, edit, update, submit, review, close, export
- Routes: 10 routes registered in `routes/modules.php`
- Seeder: `IncidentReportingSeeder` (workflow transition under_review→closed + 4 notification templates)
- React Pages: Index (list+filter+search+pagination), Form (sectioned create/edit), Show (detail with evidence+comments+activity+workflow timeline+action buttons+close modal)
- Navigation: "Modul QHSSE" group with "Laporan Insiden" menu item
- Feature Tests: 19 Pest tests (functional, permission, integration, negative)

## 3. Scope Tidak Dikerjakan

- File evidence upload via UI (backend ManagedFileService exists, UI upload not wired in Form page)
- Involved persons UI repeater (backend supports, UI not implemented)
- Reject workflow action (backend workflow supports, controller endpoint not exposed)
- Data scope filtering (own/department/site/company) — all viewers see all incidents
- Dashboard KPI widgets for incidents
- PDF report export
- Mobile-specific UI optimizations

## 4. File/Folder Dibuat

- `database/migrations/2026_07_11_000001_create_incidents_table.php`
- `app/Models/Modules/Incident/IncidentReport.php`
- `database/factories/Modules/Incident/IncidentReportFactory.php`
- `app/Http/Requests/Modules/Incident/StoreIncidentReportRequest.php`
- `app/Http/Requests/Modules/Incident/UpdateIncidentReportRequest.php`
- `app/Http/Controllers/Modules/Incident/IncidentReportController.php`
- `database/seeders/IncidentReportingSeeder.php`
- `resources/js/Pages/Modules/Incident/Index.tsx`
- `resources/js/Pages/Modules/Incident/Form.tsx`
- `resources/js/Pages/Modules/Incident/Show.tsx`
- `tests/Feature/Modules/Incident/IncidentReportTest.php`
- `handoff/PHASE-01-incident-reporting-HANDOFF.md`

## 5. File/Folder Diubah

- `app/Core/Permissions/CorePermissions.php` — added 7 incident permissions + role matrix
- `routes/modules.php` — added 10 incident routes
- `database/seeders/DatabaseSeeder.php` — added IncidentReportingSeeder call
- `resources/js/Layouts/AuthenticatedLayout.tsx` — added "Modul QHSSE" nav group

## 6. Database/Migration/Model

- `incidents` table: 15 columns (id, incident_number unique, title, category, occurred_at, site_id FK, area_id FK nullable, department_id FK nullable, reporter_id FK, severity_id FK, priority_id FK, description, immediate_action nullable, status default 'draft', timestamps)
- `incident_involved_persons` pivot: 5 columns (id, incident_id FK cascade, employee_id FK, note nullable, timestamps)
- Migration status: all ran on PostgreSQL

## 7. API/Backend

| Method | Endpoint | Permission |
|---|---|---|
| GET | /incident-reports | incident.reports.view |
| GET | /incident-reports/create | incident.reports.create |
| POST | /incident-reports | incident.reports.create |
| GET | /incident-reports/{id} | incident.reports.view |
| GET | /incident-reports/{id}/edit | incident.reports.update |
| PUT | /incident-reports/{id} | incident.reports.update |
| POST | /incident-reports/{id}/submit | incident.reports.submit |
| POST | /incident-reports/{id}/review | incident.reports.review |
| POST | /incident-reports/{id}/close | incident.reports.close |
| GET | /incident-reports/export | incident.reports.export |

## 8. UI/Frontend

| Page | Purpose |
|---|---|
| Modules/Incident/Index.tsx | List with search, filter (status, category), pagination, export |
| Modules/Incident/Form.tsx | Sectioned create/edit (Informasi Umum, Lokasi, Klasifikasi, Deskripsi) |
| Modules/Incident/Show.tsx | Detail with summary, workflow timeline, comments, activity log, action buttons, close modal |

## 9. Permission Ditambahkan

- `incident.reports.view` — all roles except bare users
- `incident.reports.create` — Admin, QHSSE, Supervisor, Dept Head, Employee, Contractor
- `incident.reports.update` — Admin, QHSSE, Supervisor, Dept Head
- `incident.reports.submit` — Admin, QHSSE, Supervisor, Dept Head, Employee, Contractor
- `incident.reports.review` — Admin, QHSSE Manager, QHSSE Officer
- `incident.reports.close` — Admin, QHSSE Manager, QHSSE Officer
- `incident.reports.export` — Admin, QHSSE, Auditor, Top Management

## 10. Master Data/Seed Ditambahkan

- Workflow transition: `under_review → closed` (action_key: close, requires_reason: true)
- Notification templates: incident.submitted, incident.reviewing, incident.closed, incident.rejected

## 11. Workflow/Status Ditambahkan

Uses existing `incident` workflow definition (seeded in Phase 0):
- draft → submitted (submit)
- submitted → under_review (review)
- under_review → closed (close, requires_reason) ← **NEW transition added by IncidentReportingSeeder**
- submitted → rejected (reject, requires_reason)
- under_review → rejected (reject, requires_reason)

## 12. Notification Ditambahkan

| Event | Template Type | Recipients |
|---|---|---|
| Submit | incident.submitted | QHSSE Officer + Manager |
| Review | incident.reviewing | Reporter |
| Close | incident.closed | Reporter |

## 13. Report/Export Ditambahkan

- CSV export via CsvExporter
- Columns: Nomor, Judul, Kategori, Severity, Priority, Status, Tanggal Kejadian, Reporter, Site
- Permission: incident.reports.export

## 14. Test Dijalankan dan Hasilnya

```bash
php artisan test
npm run build
```

Results:
- Tests: **98 passed** (338 assertions) — 79 Phase 0 + 19 Phase 1
- Build: **pass** (4.54s)
- Migration: all ran

## 15. Known Issues

- File evidence upload UI not yet wired (backend ManagedFileService ready, Form page needs upload component)
- Involved persons repeater UI not implemented (backend relationship + sync ready)
- Reject action endpoint not exposed (workflow supports it)
- No data scope filtering yet (all viewers see all incidents)

## 16. Deferred Items (to Backlog)

- File evidence upload UI → Phase 1 enhancement or Phase 2
- Involved persons UI repeater → Phase 1 enhancement
- Reject action + reason modal → Phase 1 enhancement
- Data scope filtering (own/department/site/company) → Phase 2+
- Dashboard KPI widgets for incidents → Phase 1 Dashboard module
- PDF report export → Phase 19

## 17. Next Prompt Recommendation

```text
Lanjutkan Phase 2 — Investigation & RCA.
Project path: /home/qhsse/qhsse-app-v3.
Baca SOUL.md, IDEA.md, AGENTS.md, docs-qhsse, handoff terakhir.
Kerjakan hanya scope phase ini.
Gunakan core foundation yang sudah ada.
Tambahkan migration/model/request/controller/route/UI/tests.
Jalankan php artisan test dan npm run build.
Update changelog/decision log bila perlu.
Buat handoff setelah selesai.
```
