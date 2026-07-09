# Security & Audit Trail Specification

## 1. Security Requirement

- Password hashing.
- Server-side authorization.
- CSRF protection jika web session.
- Rate limit login.
- File upload MIME/extension/size validation.
- Private file storage.
- Signed/authorized file download.
- Role/site/department/company data scoping.

## 2. Sensitive Data

- Injury/medical details.
- Witness statements.
- Disciplinary/security cases.
- Contractor compliance documents.
- Legal evidence.

## 3. Audit Trail Events

- Create/update/delete.
- Submit/approve/reject/close/reopen.
- File upload/delete/download for sensitive docs.
- Permission changes.
- User activation/deactivation.
- Master data changes.

## 4. Audit Trail Fields

- user_id
- module_name
- reference_id
- action
- old_value
- new_value
- ip_address
- user_agent
- created_at

## 5. Retention

Default retention minimal 5 tahun, final mengikuti regulasi/perusahaan.
