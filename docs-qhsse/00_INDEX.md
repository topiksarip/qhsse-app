# QHSSE App — Documentation Index

Dokumentasi lengkap aplikasi QHSSE (Quality, Health, Safety, Security, Environment).
Dibangkitkan dari kode aktual repo (routes, `app/Core/Permissions/CorePermissions.php`, migrations, models).

## Daftar Dokumen

- `00_INDEX.md` — indeks ini
- `01_PROJECT_CHARTER.md` — maksud, ruang lingkup, stakeholder
- `02_PRD_QHSSE_APP.md` — product requirements
- `03_ROADMAP_AND_PHASES.md` — fase pengembangan
- `04_MODULE_REGISTER.md` — daftar modul & status
- `05_ROLE_PERMISSION_MATRIX.md` — RBAC lengkap
- `06_MASTER_DATA_SPEC.md` — master data
- `07_WORKFLOW_SPEC.md` — workflow engine
- `08_DATA_MODEL_ERD.md` — skema & relasi
- `09_API_SPEC.md` — spesifikasi route/endpoint
- `10_UI_UX_WIREFRAME.md` — struktur UI
- `11_DESIGN_SYSTEM.md` — token desain
- `12_NOTIFICATION_SPEC.md` — notifikasi
- `13_NUMBERING_SPEC.md` — penomoran
- `14_REPORTING_EXPORT_SPEC.md` — ekspor & laporan
- `15_SECURITY_AUDIT_SPEC.md` — keamanan & audit
- `16_NFR.md` — non-functional requirements
- `17_TEST_PLAN_UAT.md` — rencana test/UAT
- `18_DEPLOYMENT_OPERATION_GUIDE.md` — deploy & operasi
- `19_DECISION_LOG.md` — log keputusan
- `20_CHANGELOG.md` — changelog
- `21_BLUEPRINT.md` — arsitektur blueprint
- `22_FOUNDATION_SUPER_SPEC.md` — core foundation spec
- `23_EXECUTION_PLAN.md` — rencana eksekusi
- `24_HANDOFF_PROTOCOL.md` — protokol handoff
- `25_BACKLOG.md` — backlog
- `26_TECH_STACK_DECISION.md` — keputusan tech stack
- `27_PHASE_0_BUILD_PLAN.md` — Phase 0 build plan
- `28_PHASE_0_UAT_CHECKLIST.md` — Phase 0 UAT
- `29_SYSTEM_QA_REPORT.md` — QA super admin
- `30_MODULE_COMPLETENESS_AUDIT.md` — audit kelengkapan modul
- `31_PROJECT_COMPLETION_REPORT.md` — laporan penyelesaian
- `32_SPEC_VS_IMPLEMENTATION_AUDIT.md` — audit spec vs implementasi
- `33_PHASE_COMPLETION_STATUS.md` — status penyelesaian fase

## Dokumentasi Per-Modul (`docs-qhsse/modules/`)

Setiap modul memiliki file mandiri berisi ringkasan, fields (skema DB aktual), workflow state, permission, endpoint, UI, dan catatan implementasi:

- `incident-reporting.md` — Incident Reporting
- `investigation-rca.md` — Investigation & RCA
- `capa-action-tracking.md` — CAPA / Action Tracking
- `inspection-checklist.md` — Inspection Checklist
- `document-control.md` — Document Control
- `audit-management.md` — Audit Management
- `training-competency.md` — Training & Competency
- `risk-management.md` — Risk Management (HIRADC/JSA)
- `legal-compliance.md` — Legal & Compliance Register
- `contractor-management.md` — Contractor Management
- `asset-equipment-safety.md` — Asset & Equipment Safety
- `apd-ppe.md` — APD / PPE Management
- `communication-campaign.md` — Communication & Campaign
- `reporting-export.md` — Reporting & Export
- `emergency-preparedness.md` — Emergency Preparedness
- `permit-to-work.md` — Permit to Work
- `environmental-monitoring.md` — Environmental Monitoring
- `security-management.md` — Security Management
- `quality-ncr-complaints.md` — Quality NCR & Complaints

## Dokumentasi Frontend Flutter (`docs-qhsse/flutter/`)

Kontrak JSON API & panduan Flutter (backend saat ini Inertia — lihat `12_BACKEND_API_ENABLEMENT.md`):

- `01_API_ARCHITECTURE.md` — arsitektur API (REST JSON + Sanctum, envelope)
- `02_API_AUTH.md` — login token, secure storage, 401 handling
- `03_API_ENDPOINTS.md` — kontrak endpoint per modul (diturunkan dari `route:list`)
- `04_API_ERRORS.md` — HTTP status & envelope error validasi
- `05_API_PAGINATION_FILTER.md` — page/sort/filter (ListQuery)
- `06_API_FILES.md` — upload/download privat (ManagedFileService)
- `07_API_NOTIFICATIONS.md` — notifikasi + FCM
- `08_FLUTTER_SETUP.md` — struktur project Flutter & env
- `09_FLUTTER_OFFLINE_SYNC.md` — offline-first & sync lapangan
- `10_FLUTTER_LOCALIZATION.md` — lokalisasi id/en
- `11_FLUTTER_SECURITY.md` — token storage, cert pinning, data lokal
- `12_BACKEND_API_ENABLEMENT.md` — prasyarat backend (Sanctum, route /api, resources)
