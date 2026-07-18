# Data Model / ERD

## Tabel Utama (80 migrations)

- users, cache, jobs, permission_tables (Spatie)
- companies, employees, sites, departments, areas, positions
- categories, priorities, severities, statuses, risk_matrix_levels
- managed_files, numbering_formats, numbering_counters, generated_numbers
- workflow_definitions, workflow_transitions, workflow_instances, workflow_histories
- audit_logs, comments, activity_logs, core_notifications, notification_templates
- incidents, investigations, capa_actions, inspection_tables (incl. inspection_units, inspection_results)
- document_control_tables, audit_management_tables, training_tables, permit_tables
- environmental_records, security_incidents_and_visitor_logs, patrol_checklists_and_results
- ncrs_and_customer_complaints, risk_registers, legal_register, legal_obligations
- emergency_plans, emergency_drills, emergency_contacts, contractors, contractor_evaluations
- assets, asset_certificates, asset_inspections, campaigns, campaign_acknowledgments
- report_templates, saved_reports, apd_catalogs, apd_items, apd_issuances, apd_inspections, apd_requirements

## Relasi Kunci
- `incidents` 1—* `investigations` 1—* `capa_actions`
- `inspections` 1—* `inspection_units` 1—* `inspection_results` (FK `inspection_unit_id`)
- `inspection_units.asset_id` → `assets` (nullable; Daftar Unit dari master Asset)
- `assets` 1—* `asset_certificates`, `asset_inspections`
- `audits` 1—* `audit_findings`
- `managed_files` polymorphic via `module_name` + `reference_id`
- `comments` / `activity_logs` shared via `module_name` + `reference_id`
- `workflow_instances` terikat `module_name` + `reference_id`
- Org: `companies` 1—* `employees` → `users`; `sites` 1—* `areas` 1—* `departments`

## Konvensi Penamaan
- Modul bisnis: `app/Models/Modules/{Module}/{Entity}.php`.
- SoftDeletes pada entity utama (asset family di-restore setelah eksperimen). AuditLog/Comment pakai `deleted_at` manual.
