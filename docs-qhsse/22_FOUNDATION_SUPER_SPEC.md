# Foundation Super Spec (Phase 0)

Phase 0 membangun seluruh platform core yang dipakai lintas modul:
- Auth (session) + inactive user block.
- RBAC (Spatie) + scope (own/department/site/company/all).
- Org master: company, employee, user admin.
- Organization: sites, areas, departments, positions.
- QHSSE master: severities, priorities, statuses, categories, risk matrix levels.
- Private file service (ManagedFileService) + ParentAuthorizationRegistry fail-closed.
- Numbering service.
- Workflow core.
- Audit trail (AuditService).
- Comments + activity log (shared).
- Notification core.
- Search/filter/pagination/export base (ListQuery, CsvExporter, ScopeService).
- Dashboard shell.

Verifikasi: `make test` + `npm run build` hijau; UAT `28_PHASE_0_UAT_CHECKLIST.md`.
