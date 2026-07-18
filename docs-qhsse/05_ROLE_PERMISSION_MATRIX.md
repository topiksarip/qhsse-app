# Role & Permission Matrix

Sumber otoritatif: `app/Core/Permissions/CorePermissions.php` (Spatie laravel-permission).

## Roles

| Role | Deskripsi |
|------|-----------|
| Super Admin | Semua izin (core.scope.all). Akses penuh termasuk RBAC. |
| Admin | Semua izin (core.scope.all). |
| QHSSE Manager | Semua modul QHSSE penuh + scope all. Pimpinan QHSSE. |
| QHSSE Officer | Modul QHSSE penuh (create/execute) + scope site. |
| Security Officer | Security full + scope site. |
| Supervisor | Create/update terbatas per department; review insiden, capa assign, inspection view. |
| Department Head | View + submit per department; approval dokumen. |
| Foreman | Inspeksi create+execute, insiden supervisor, per department. |
| Operator | Report insiden (basic), inspeksi create+execute, view. |
| Employee / Reporter | Report insiden basic + view modul terkait (scope own). |
| Contractor | Report insiden + view dokumen/asset/apd terbatas (scope company). |
| Auditor | View + export semua modul (scope all), read-only audit. |
| Top Management | View + export semua modul (scope all). |

## Permission Groups

### Core Organization & Master Data
- `core.sites.{view,create,update,deactivate,delete}`
- `core.areas.{view,create,update,deactivate,delete}`
- `core.departments.{view,create,update,deactivate,delete}`
- `core.positions.{view,create,update,deactivate,delete}`
- `core.companies.{view,create,update,deactivate,delete}`
- `core.employees.{view,create,update,deactivate,delete}`
- `core.users.{view,create,update,deactivate,delete}`
- `core.severities / priorities / statuses / categories / risk-matrix : {view,create,update,deactivate,delete}`

### Core Services
- `core.files.{view,upload,download,delete}`
- `core.numbering.{view,create,update,generate,delete}`
- `core.workflow.{view,manage,transition}`
- `core.audit.view`
- `core.comments.{view,create,delete}`
- `core.activity.view`
- `core.notifications.{view / manage}`
- `core.export.csv`
- `core.roles.manage`
- `core.scope.{own,department,site,company,all}`

### Business Modules
- `incident.reports.{view,create,update,submit,review,close,export,evidence,delete}`
- `investigation.reports.{view,create,update,submit,review,close,export,delete}`
- `capa.actions.{view,create,update,submit,verify,close,reject,export,delete}`
- `inspection.checklists.{view,create,update,execute,export,delete}`
- `document.control.{view,create,update,submit_review,approve,make_effective,obsolete,export,delete}`
- `audit.management.{view,create,update,execute,close,export,delete} + audit.findings.*`
- `training.programs.* / training.records.*`
- `risk.registers.*`
- `legal.register.* / legal.obligations.*`
- `contractor.management.*`
- `asset.management.* / asset.certificates.* / asset.inspections.*`
- `apd.* (view,create,update,delete,export,issue,approve,request,receive,inspect,requirements.manage)`
- `communication.campaigns.*`
- `reporting.templates.* / reporting.reports.*`
- `emergency.plans.* / emergency.drills.* / emergency.contacts.*`
- `permit.work.* / permit.checklist.sign`
- `environment.records.*`
- `security.incidents.* / security.visitors.* / security.patrols.*`
- `quality.ncrs.* / quality.complaints.*`

## Scope Levels
`core.scope.own` (hanya milik sendiri) · `core.scope.department` · `core.scope.site` · `core.scope.company` · `core.scope.all`

## Catatan Keamanan
- Generic endpoints file/comment gunakan `ParentAuthorizationRegistry` (fail-closed: modul tak terdaftar → 403/404).
- Modul terdaftar: `incident`, `capa`. Modul lain (asset, document) punya endpoint dedikasi.
