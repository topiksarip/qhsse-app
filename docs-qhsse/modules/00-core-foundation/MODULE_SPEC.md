# Module Spec — Core Foundation

## 1. Tujuan Modul

Modul Core Foundation mengelola proses QHSSE terkait `Core Foundation` secara end-to-end, terintegrasi dengan Core Foundation: user, role, permission, master data, file upload, notification, numbering, workflow, audit trail, comments, dashboard, dan reporting.

## 2. Dependency

-

## 3. User Role yang Terlibat

- Employee/Reporter
- Supervisor
- QHSSE Officer
- QHSSE Manager
- Department Head
- Contractor jika relevan
- Auditor jika relevan
- Admin
- Top Management untuk dashboard/report

## 4. Fitur

- Authentication
- User management
- Role & permission
- Organization master
- General master data
- File upload core
- Notification core
- Numbering system
- Workflow status core
- Audit trail
- Comment & activity log
- Search/filter/pagination/export
- Dashboard shell
- System setting
- Backup baseline

## 5. Workflow Umum

```text
Draft -> Submitted -> Under Review -> In Progress/Approved -> Waiting Verification -> Closed
                         |-> Rejected
                         |-> Cancelled
```

Workflow final dapat disesuaikan per modul, tetapi tetap memakai Workflow Core.

## 6. Data Field Umum

- generated_number
- title/name
- description
- site_id
- area_id
- department_id
- company_id/contractor_id optional
- owner/reporter/requester
- assigned_to/PIC optional
- reviewer/approver/verifier optional
- category/type
- severity/priority/risk optional
- event/date/due_date optional
- status
- attachments
- comments
- activity logs
- audit trail

## 7. Business Rules

- Record resmi memakai numbering otomatis.
- Draft belum wajib punya nomor kecuali modul menentukan lain.
- Submit wajib validasi field mandatory.
- Reject wajib alasan.
- Close wajib memenuhi syarat evidence/action jika relevan.
- Semua perubahan status dicatat di workflow history.
- Semua perubahan penting masuk audit trail.
- Data visibility mengikuti role scope: own, department, site, company, all.

## 8. Notification Rules

- Submit: notify reviewer/approver.
- Assignment: notify PIC.
- Due soon: notify PIC sebelum due date.
- Overdue: notify PIC dan atasan/escalation role.
- Rejected: notify requester/PIC.
- Closed: notify relevant owner/stakeholder.

## 9. File Attachment Rules

- Attachment memakai File Service core.
- File sensitif mengikuti permission record.
- Evidence tidak boleh dihapus setelah record closed kecuali admin berwenang.

## 10. Permission Keys

- `core-foundation.view`
- `core-foundation.create`
- `core-foundation.update`
- `core-foundation.submit`
- `core-foundation.review`
- `core-foundation.approve`
- `core-foundation.reject`
- `core-foundation.verify`
- `core-foundation.close`
- `core-foundation.reopen`
- `core-foundation.export`

## 11. UI Pages

- List page dengan search/filter/pagination/export.
- Create/edit form.
- Detail page.
- Review/approval panel.
- Attachment panel.
- Comment/activity timeline.
- Report/export page jika relevan.
- Dashboard widget jika relevan.

## 12. API Requirement

- GET list
- POST create
- GET detail
- PUT update
- DELETE/cancel sesuai permission
- POST submit
- POST approve/reject
- POST verify/close/reopen jika relevan
- GET/POST comments
- GET/POST files
- GET export/report

## 13. Dashboard Metrics

- Total records.
- Open/in progress count.
- Closed count.
- Overdue count jika ada due date.
- Trend by month.
- Breakdown by site/department/category/status.

## 14. Report / Export

- Export list Excel/CSV.
- PDF detail/report untuk record resmi bila dibutuhkan.
- Filter export mengikuti filter list.

## 15. Acceptance Criteria

1. User dengan permission benar dapat membuat record.
2. User tanpa permission ditolak.
3. Submit memvalidasi field mandatory.
4. Workflow status berjalan sesuai rule.
5. Attachment bisa upload/download sesuai permission.
6. Comment dan activity log tampil.
7. Audit trail tercatat.
8. Notification terkirim ke penerima tepat.
9. List dapat search/filter/pagination.
10. Export menghasilkan data sesuai filter dan permission.

## 16. Open Questions

- Field mandatory final per perusahaan/site.
- Approval path final.
- Template report final.
- SLA/due date default.
- Data sensitif yang perlu pembatasan tambahan.
