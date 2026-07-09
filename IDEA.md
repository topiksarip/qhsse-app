# IDEA.md — QHSSE App Generating Guide

## 1. Fungsi Dokumen Ini

Dokumen ini adalah panduan ide, arah generating, dan standar output untuk semua pengembangan berikutnya pada project QHSSE App.

Gunakan dokumen ini bersama:

- `SOUL.md`
- `AGENTS.md`
- `docs-qhsse/21_BLUEPRINT.md`
- `docs-qhsse/22_FOUNDATION_SUPER_SPEC.md`
- `docs-qhsse/23_EXECUTION_PLAN.md`
- `docs-qhsse/27_PHASE_0_BUILD_PLAN.md`
- `docs-qhsse/28_PHASE_0_UAT_CHECKLIST.md`
- `handoff/PHASE-00-core-foundation-HANDOFF.md`

Project aktif:

```text
/home/ubuntu/qhsse-app-v3
```

## 2. Ide Produk Utama

Aplikasi ini adalah QHSSE Control Center berbasis web.

Bukan sekadar CRUD, tetapi sistem yang menghubungkan:

- laporan,
- evidence,
- severity,
- workflow,
- investigasi,
- action,
- komentar,
- audit trail,
- notification,
- dashboard,
- export/report.

Setiap modul harus menghasilkan data yang bisa dipakai untuk monitoring, follow-up, dan pengambilan keputusan.

## 3. Modul Target

### Phase 1 — Incident Reporting

Tujuan: pengguna bisa melaporkan kejadian QHSSE dan QHSSE team bisa melakukan review awal.

Fitur ideal:

- Incident report list.
- Create incident report.
- Detail incident report.
- Edit draft/submitted report sesuai permission.
- Kategori laporan:
  - Accident
  - Incident
  - Near Miss
  - Unsafe Act
  - Unsafe Condition
  - Environmental Spill
  - Security Breach
- Lokasi/site/area/department.
- Date/time kejadian.
- Reporter dan involved person.
- Severity, priority, initial risk.
- Description dan immediate action.
- File evidence melalui private file service.
- Numbering melalui numbering service.
- Workflow status minimal: Draft, Submitted, Under Review, Closed/Rejected.
- Audit trail untuk perubahan penting.
- Comment/activity timeline di detail page.
- Notification saat submitted/reviewed jika relevan.
- Search/filter/pagination/export baseline.

### Phase 2 — CAPA / Action Tracking

Tujuan: semua finding/incident/action punya PIC, due date, evidence, dan close-out.

Fitur ideal:

- Action list.
- Assign PIC.
- Due date dan overdue status.
- Action source: incident, audit finding, inspection finding, manual.
- Status: Open, In Progress, Waiting Verification, Closed, Overdue.
- Evidence upload.
- Verification by QHSSE.
- Notification reminder/escalation.
- Dashboard overdue action.

### Phase 3 — Inspection Checklist

Tujuan: inspeksi rutin dapat dilakukan dengan template dan menghasilkan finding/action.

Fitur ideal:

- Checklist template.
- Checklist question/items.
- Inspection schedule.
- Inspection execution/result.
- Yes/No/NA/Safe/Unsafe answer.
- Photo evidence.
- Auto-create action if unsafe.
- Completion tracking.

### Phase 4 — Audit Management

Tujuan: audit internal/eksternal dan finding dapat dikelola sampai close-out.

Fitur ideal:

- Audit plan.
- Audit scope/site/department.
- Audit checklist.
- Finding: Major, Minor, Observation, OFI.
- Corrective action link.
- Close-out evidence.
- Audit report export.

### Phase 5 — Document Control

Tujuan: SOP, WI, JSA, HIRADC, MSDS, policy terdokumentasi dan terkendali.

Fitur ideal:

- Document register.
- Document number/revision/effective date/review date.
- File upload private.
- Approval workflow.
- Expiry/review reminder.
- Search and filter.

### Phase 6 — Training & Competency

Tujuan: training matrix dan sertifikasi dapat dipantau.

Fitur ideal:

- Training master.
- Employee training matrix.
- Certificate upload.
- Expiry date.
- Compliance dashboard.
- Reminder notification.

### Phase 7 — Permit to Work

Tujuan: high-risk work dikontrol dengan permit digital.

Fitur ideal:

- Hot work.
- Working at height.
- Confined space.
- Electrical work.
- Excavation.
- Lifting operation.
- Validity period.
- Approval workflow.
- Closure permit.

### Phase 8 — Environmental Monitoring

Tujuan: aspek lingkungan bisa dicatat dan dilaporkan.

Fitur ideal:

- Waste tracking.
- Spill report.
- Emission/noise/water monitoring.
- Environmental incident.
- Compliance evidence.

### Phase 9 — Security Management

Tujuan: aspek security masuk dalam QHSSE platform.

Fitur ideal:

- Security incident.
- Visitor log.
- Patrol checklist.
- Access issue/loss report.

## 4. Pattern Wajib untuk Modul Baru

Setiap modul baru harus mengikuti struktur minimal ini:

```text
app/Modules/{ModuleName}/
app/Http/Controllers/Modules/{ModuleName}/
app/Http/Requests/Modules/{ModuleName}/
app/Models/Modules/{ModuleName}/
database/migrations/
database/factories/Modules/{ModuleName}/
database/seeders/
resources/js/Pages/Modules/{ModuleName}/
tests/Feature/Modules/{ModuleName}/
handoff/PHASE-XX-{module-slug}-HANDOFF.md
```

Jika struktur final project sudah punya konvensi lain, ikuti konvensi yang ada dan jangan membuat pola baru tanpa alasan.

## 5. Template Vertical Slice Modul

Untuk setiap modul, mulai dari vertical slice kecil yang selesai total:

1. Migration.
2. Model + factory.
3. Permission constants/seeder update.
4. Request validation.
5. Controller index/create/store/show/edit/update.
6. Routes dengan middleware auth/verified/permission.
7. React pages: Index, Form, Show.
8. Integrasi core services yang relevan.
9. Tests:
   - can create record,
   - can view list/detail,
   - permission block,
   - validation error,
   - workflow/file/numbering/audit bila digunakan.
10. Docs/handoff.

## 6. Core Services yang Harus Dipakai

### RBAC

Gunakan `CorePermissions` pattern. Tambahkan permission baru secara eksplisit.

Selalu cek permission di route/controller backend.

### Numbering

Gunakan numbering service untuk nomor laporan, dokumen, permit, audit, atau action yang butuh identitas formal.

Jangan membuat nomor manual dengan random string jika nomor harus traceable.

### Workflow

Gunakan workflow core untuk status lifecycle.

Jangan hardcode status transition kompleks di controller jika sudah bisa lewat workflow service.

### Audit Trail

Catat perubahan penting, terutama:

- status,
- assignee/PIC,
- severity/priority,
- due date,
- closure/reopen,
- permission/master data,
- verification.

### File Service

Semua evidence/file sensitif harus memakai private file service.

Jangan link langsung ke `public/storage` untuk evidence QHSSE.

### Comments & Activity

Detail page modul harus punya timeline aktivitas bila modul memiliki workflow atau collaboration.

Gunakan pattern `module_name + reference_id`.

### Notification

Gunakan notification core untuk event penting:

- report submitted,
- action assigned,
- status changed,
- overdue,
- mentioned in comment,
- verification requested.

### Search/Export

Gunakan pattern `ListQuery` dan `CsvExporter` untuk list/export baseline.

## 7. Standard Permission Naming

Gunakan format:

```text
module.resource.action
```

Contoh:

```text
incident.reports.view
incident.reports.create
incident.reports.update
incident.reports.submit
incident.reports.review
incident.reports.close
incident.reports.export
```

Untuk core tetap gunakan format yang sudah ada:

```text
core.sites.view
core.files.upload
core.workflow.transition
```

## 8. Standard Status Naming

Gunakan status yang manusiawi dan dapat diaudit.

Contoh Incident:

```text
Draft
Submitted
Under Review
Need More Info
Closed
Rejected
```

Contoh Action:

```text
Open
In Progress
Waiting Verification
Closed
Overdue
Cancelled
```

## 9. Test Philosophy

Tests wajib membuktikan aplikasi berfungsi, bukan hanya class bisa dipanggil.

Minimal tests per modul:

- Authorized user can access list.
- Unauthorized user is forbidden.
- Authorized user can create valid record.
- Invalid payload is rejected.
- Record appears in list/detail.
- File evidence upload works if module uses files.
- Number generated if module uses numbering.
- Workflow transition works if module has status.
- Audit/activity generated for critical change.
- Notification generated if relevant.
- `npm run build` passes after frontend changes.

## 10. UI/UX Direction

Gunakan UI yang jelas, profesional, dan kuat untuk konteks QHSSE:

- Dashboard seperti control center.
- Status badge jelas.
- Severity/priority punya warna konsisten.
- Table punya filter yang berguna.
- Form dibagi section yang mudah dipahami.
- Detail page harus menampilkan summary, status, evidence, comments, activity, dan actions.
- Empty state harus menjelaskan tindakan berikutnya.
- Mobile responsive tetap diperhatikan.

Jangan membuat UI generik asal jadi. Setiap halaman harus terasa punya fungsi operasional.

## 11. Data Quality Rules

Data penting tidak boleh ambigu.

Untuk laporan/record utama, pertimbangkan field berikut:

- unique number,
- title/summary,
- date/time,
- site/area/department,
- reporter/owner/PIC,
- type/category,
- severity/priority/risk,
- status,
- description,
- immediate action,
- due date jika actionable,
- file evidence,
- audit/activity history.

## 12. Handoff Rules

Setiap phase/module harus punya handoff.

Handoff minimal menjawab:

- Status selesai/partial/blocked.
- Scope yang dikerjakan.
- Scope yang tidak dikerjakan.
- File dibuat/diubah.
- Database/migration/model.
- API/backend.
- UI/frontend.
- Permission.
- Workflow/status.
- Notification.
- Report/export.
- Test dijalankan dan hasilnya.
- Known issues.
- Deferred items.
- Next prompt recommendation.

## 13. Verification Commands

Default verification:

```bash
php artisan test
npm run build
```

Tambahan bila mengubah DB/seed:

```bash
php artisan migrate:status
php artisan db:seed
```

Tambahan bila mengubah route:

```bash
php artisan route:list
```

## 14. Prompt Ideal untuk Lanjut Phase

Gunakan format instruksi seperti ini:

```text
Lanjutkan Phase X — Nama Modul.
Project path: /home/ubuntu/qhsse-app-v3.
Baca SOUL.md, IDEA.md, AGENTS.md, docs-qhsse, dan handoff terakhir.
Kerjakan hanya scope phase ini.
Gunakan core foundation yang sudah ada.
Tambahkan migration/model/request/controller/route/UI/tests sesuai kebutuhan.
Jalankan php artisan test dan npm run build.
Update changelog/decision log jika perlu.
Buat handoff setelah selesai.
```

## 15. North Star

Hasil generating harus membuat aplikasi semakin dekat ke sistem QHSSE yang benar-benar bisa dipakai harian oleh perusahaan.

Jika output hanya terlihat bagus tapi tidak punya backend, permission, test, workflow, atau auditability, maka output belum memenuhi standar project ini.
