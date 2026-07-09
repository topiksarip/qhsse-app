# Master Data Specification

## 1. Organization Master

- Site: code, name, address, status.
- Area: site_id, code, name, type, status.
- Department: code, name, site_id optional, status.
- Position: code, name, department_id optional, status.
- Company: code, name, type internal/contractor/vendor, status.
- Employee: employee_no, name, email, phone, company, department, position, site, status.

## 2. QHSSE General Master

- Severity: code, name, numeric_level, color, description.
- Priority: code, name, SLA days, color.
- Status: module, code, name, sequence, terminal flag.
- Category: module, code, name, parent_id optional.
- Risk matrix: likelihood, consequence, score, level, color.
- Body part, injury type, treatment type.
- Root cause category.
- Action category.
- Checklist category.
- Audit type/criteria.
- Document category/type.
- Training type/provider.
- Permit type.
- Waste type.
- Security incident type.
- Quality NCR type.
- Asset type.

## 3. Master Data Rules

- Semua master data memakai code unik.
- Data yang sudah dipakai tidak boleh hard delete; gunakan inactive.
- Perubahan master kritikal masuk audit trail.
- Import Excel boleh untuk employee, contractor, asset, document register, training record.
