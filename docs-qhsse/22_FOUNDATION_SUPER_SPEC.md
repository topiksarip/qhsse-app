# Core Foundation Super Spec

## 1. Tujuan Pondasi

Pondasi harus mencakup kebutuhan semua modul QHSSE tanpa membuat sistem terlalu kompleks. Semua modul harus memakai layanan core yang sama agar data konsisten, permission seragam, audit trail lengkap, dan integrasi antar modul mudah.

## 2. Core Capability Map

| Capability | Dipakai Oleh |
|---|---|
| Authentication | Semua modul |
| User/Role/Permission | Semua modul |
| Organization master | Semua modul |
| Master data | Semua modul |
| File upload | Incident, CAPA, Inspection, Audit, Document, Training, PTW, Legal, Asset |
| Notification | CAPA, Document, Training, PTW, Legal, Asset, Audit |
| Numbering | Semua record resmi |
| Workflow | Incident, CAPA, Document, Audit, PTW, Legal |
| Audit trail | Semua modul |
| Comments/activity | Incident, CAPA, Investigation, Audit, PTW, NCR |
| Export | Semua modul |
| Dashboard shell | Semua modul |

## 3. Authentication Detail

Fitur:

- Login email/password.
- Logout.
- Forgot password.
- Reset password token.
- Change password.
- Session timeout.
- Optional remember me.

Security:

- Hash password.
- Rate limit login.
- Disable inactive user.
- Log failed login optional.

## 4. User, Employee, Company

User adalah akun login. Employee adalah data personil. Company menampung internal company, contractor, vendor.

Relasi:

```text
company 1..n employee
employee 0..1 user
user n..n role
user n..n site access optional
```

Rule:

- Contractor user wajib terhubung ke company contractor.
- User inactive tidak bisa login.
- Employee inactive tetap ada untuk histori.

## 5. Permission Engine

Permission key format:

```text
{module}.{action}
```

Contoh:

```text
incident.view_all
incident.create
incident.review
incident.close
action.verify
document.approve
permit.approve_hse
```

Scope:

- own
- department
- site
- company
- all

Server wajib mengecek permission pada semua endpoint.

## 6. Master Data Engine

Master data dapat berupa tabel khusus atau generic category. Untuk data yang banyak dipakai dan butuh relasi kuat, gunakan tabel khusus. Untuk kategori sederhana, gunakan generic master.

Tabel khusus:

- sites
- areas
- departments
- positions
- companies
- employees
- severities
- priorities
- statuses
- risk_matrix

Generic:

- incident category
- action category
- document type
- training type
- permit subtype

## 7. File Service

Validation:

- Allowlist extension.
- Allowlist MIME.
- Max file size.
- Virus scan optional.

Storage:

- Private folder/bucket.
- Generated filename.
- Original filename stored.
- Download through authorized endpoint.

Reference:

- module_name
- reference_id
- file_category

## 8. Notification Service

Notification object:

- recipient_user_id
- channel
- subject
- body
- link_url
- read_at
- sent_at
- status

Trigger pattern:

- Domain event dibuat setelah transaksi berhasil.
- Notification diproses sync untuk in-app, async untuk email jika queue tersedia.

ponytail: email queue optional di awal; wajib jika volume notifikasi mulai memperlambat submit form.

## 9. Numbering Service

Input:

- module_code
- site_code optional
- year

Output:

- generated number unique

Concurrency:

- Sequence increment harus atomic/transaction-safe.

## 10. Workflow Service

Minimal:

- Validate current status.
- Validate target status.
- Validate actor permission.
- Require reason for reject/cancel.
- Write workflow history.

Workflow transition dapat hardcoded per modul di awal.

## 11. Audit Trail Service

Audit trail ditulis otomatis untuk:

- Model create/update/delete.
- Workflow action.
- Permission/master changes.
- Sensitive file access.

Old/new values boleh JSON. Untuk data besar, simpan changed fields saja.

## 12. Comment & Activity

Comment:

- Body required.
- Mention optional.
- Soft delete by author/admin.

Activity:

- System generated.
- Tidak diedit user.
- Menampilkan timeline record.

## 13. Export Service

Export minimal:

- CSV/Excel list berdasarkan filter.
- PDF per record untuk report resmi.

Rule:

- Export mengikuti permission dan data scope.
- Export event masuk audit trail untuk data sensitif.

## 14. Dashboard Base

Dashboard shell menyediakan:

- Date range filter.
- Site filter.
- Department filter.
- KPI card component.
- Chart component.
- Table widget.

Setiap modul menyediakan metric query sendiri.

## 15. Core UI Pages

- Login
- Forgot/reset password
- Main dashboard shell
- User list/form/detail
- Role list/form/detail
- Permission matrix
- Site/area/department master
- Company/contractor master
- Employee master
- General master data
- Notification center
- Audit trail viewer
- System settings

## 16. Core Acceptance Criteria

- Semua role bisa diuji permission-nya.
- Data scoping bekerja.
- File tidak bisa diakses tanpa permission.
- Numbering tidak duplikat.
- Audit trail tercatat untuk event kritikal.
- Notification muncul pada user tepat.
- Semua list core punya search/filter/pagination.
