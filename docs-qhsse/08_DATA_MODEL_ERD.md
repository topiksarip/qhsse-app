# Data Model / ERD Specification

## 1. Core Tables

- users
- roles
- permissions
- role_permissions
- user_roles
- sites
- areas
- departments
- positions
- companies
- employees
- master_categories
- statuses
- severities
- priorities
- risk_matrix_levels
- files
- notifications
- numbering_sequences
- audit_trails
- comments
- activity_logs
- workflow_histories

## 2. Shared Columns

Semua tabel transaksional:

- id
- created_at
- created_by
- updated_at
- updated_by
- deleted_at nullable
- status

## 3. Attachment Pattern

`files` memakai polymorphic reference sederhana:

- module_name
- reference_id
- file_category

## 4. Comment Pattern

`comments` memakai:

- module_name
- reference_id
- body
- mentioned_user_ids optional

## 5. Module Tables Ringkas

- incidents, incident_people, incident_witnesses
- investigations, investigation_causes, investigation_evidence
- actions
- checklist_templates, checklist_questions, inspections, inspection_answers
- audits, audit_findings
- documents, document_versions, document_acknowledgments
- trainings, training_attendances, training_certificates
- permits, permit_approvals, permit_checklist_answers
- risks, risk_controls
- environmental_records, waste_manifests, lab_results
- security_visitors, security_patrols, security_incidents
- quality_ncrs, calibrations, supplier_evaluations
- legal_registers, compliance_tasks
- emergency_drills, emergency_equipment
- contractor_requirements, contractor_documents
- assets, asset_certificates, asset_defects
- communications, communication_acknowledgments

## 6. Index Rules

- Index foreign keys.
- Index status, site_id, department_id, date fields.
- Index module_name + reference_id pada files/comments/activity.
- Unique untuk generated number per module.
