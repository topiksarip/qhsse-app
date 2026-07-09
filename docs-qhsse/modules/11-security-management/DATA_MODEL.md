# Data Model — Security Management

## Main Table

`11_security_management`

Common fields:

- id
- number/code
- title/name
- description
- site_id
- area_id
- department_id
- company_id nullable
- category_id/type_id nullable
- owner_id/reporter_id/requester_id
- assigned_to nullable
- reviewer_id nullable
- approver_id nullable
- verifier_id nullable
- event_date/date_from/date_to/due_date nullable
- severity_id nullable
- priority_id nullable
- risk_level_id nullable
- status
- created_by
- created_at
- updated_by
- updated_at
- deleted_at nullable

## Supporting Tables

- `11_security_management_items` for line/detail rows if needed.
- `11_security_management_approvals` for multi-approval if needed.
- `11_security_management_histories` optional if module-specific history is needed.

## Shared Relations

- files.module_name + files.reference_id
- comments.module_name + comments.reference_id
- activity_logs.module_name + activity_logs.reference_id
- audit_trails.module_name + audit_trails.reference_id
- workflow_histories.module_name + workflow_histories.reference_id

## Index

- number/code unique.
- status.
- site_id.
- department_id.
- date/due_date.
- assigned_to/PIC if applicable.
