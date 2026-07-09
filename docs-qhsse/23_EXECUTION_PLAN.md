# Execution Plan — QHSSE Web Application

## 1. Mode Eksekusi

Mode kerja: YOLO terkontrol.

Artinya:

- Eksekusi dibuat agresif dan bertahap tanpa menunggu semua detail sempurna.
- Tetap memakai guardrail agar tidak keluar jalur.
- Setiap phase harus menghasilkan handoff sebelum lanjut phase berikutnya.
- Tidak boleh melompat ke modul berikutnya jika acceptance criteria phase aktif belum terpenuhi.
- Tidak boleh membangun fitur di luar spec tanpa masuk Decision Log.

## 2. Dokumen Kendali Utama

Semua generating/coding wajib mengacu ke dokumen berikut:

1. `docs-qhsse/21_BLUEPRINT.md`
2. `docs-qhsse/22_FOUNDATION_SUPER_SPEC.md`
3. `docs-qhsse/03_ROADMAP_AND_PHASES.md`
4. `docs-qhsse/04_MODULE_REGISTER.md`
5. `docs-qhsse/05_ROLE_PERMISSION_MATRIX.md`
6. `docs-qhsse/06_MASTER_DATA_SPEC.md`
7. `docs-qhsse/07_WORKFLOW_SPEC.md`
8. `docs-qhsse/08_DATA_MODEL_ERD.md`
9. `docs-qhsse/09_API_SPEC.md`
10. `docs-qhsse/17_TEST_PLAN_UAT.md`
11. Module folder sesuai phase aktif.

Rule: jika ada konflik antar dokumen, urutan prioritas adalah:

```text
Execution Plan > Foundation Super Spec > Blueprint > Module Spec > Global Spec > Old notes
```

## 3. Prinsip Anti Keluar Jalur

### 3.1 Scope Lock

Setiap phase hanya boleh mengerjakan item pada:

- Phase checklist.
- Module spec phase tersebut.
- Dependency langsung yang diperlukan.

Dilarang:

- Menambah modul baru tanpa update Module Register.
- Menambah workflow kompleks tanpa Decision Log.
- Membuat plugin system/microservice di awal.
- Membuat native mobile app sebelum web stabil.
- Membuat AI automation sebelum core data valid.

### 3.2 Definition of Ready

Sebuah phase boleh dimulai jika:

1. Module spec tersedia.
2. Workflow tersedia.
3. Data model tersedia.
4. UI pages tersedia.
5. Test cases tersedia.
6. Dependency phase sebelumnya selesai.
7. Open questions kritikal sudah diputuskan atau diberi default.

### 3.3 Definition of Done

Sebuah phase dianggap selesai jika:

1. Semua fitur checklist selesai.
2. Permission berjalan.
3. Workflow berjalan.
4. File/comment/audit/notification terintegrasi jika diperlukan.
5. List page memiliki search/filter/pagination.
6. Export dasar tersedia jika modul mensyaratkan.
7. Test manual/UAT checklist lulus.
8. Bug kritikal tertutup.
9. Handoff document dibuat.
10. Changelog dan Decision Log diperbarui.

### 3.4 Change Control

Jika saat generating muncul ide baru:

- Catat di backlog, jangan langsung dibangun.
- Jika wajib untuk phase aktif, masukkan Decision Log.
- Jika bukan dependency, pindahkan ke phase lanjutan.

## 4. Macro Roadmap

| Phase | Modul | Fokus | Output |
|---:|---|---|---|
| 0 | Core Foundation | Pondasi super lengkap | Platform dasar siap dipakai semua modul |
| 1 | Incident Reporting | Laporan insiden end-to-end | Incident lifecycle berjalan |
| 2 | Investigation & RCA | Investigasi dan RCA | Investigation report + RCA |
| 3 | CAPA Action Tracking | Follow-up action | Action lifecycle lintas modul |
| 4 | Inspection Checklist | Inspeksi template dan eksekusi | Finding menjadi action |
| 5 | Dashboard & KPI | Visualisasi KPI | Dashboard data awal |
| 6 | Document Control | Dokumen dan approval | Repository, versioning, expiry |
| 7 | Audit Management | Audit dan finding | Audit finding ke CAPA |
| 8 | Training & Competency | Training matrix | Expiry reminder, compliance |
| 9 | Permit to Work | Permit lifecycle | Approval dan closure permit |
| 10 | Risk Management | HIRADC/JSA | Risk register reusable |
| 11 | Environmental Management | Lingkungan | Monitoring, waste, exceedance |
| 12 | Security Management | Security ops | Visitor, patrol, incident |
| 13 | Quality Management | Quality/NCR | NCR, complaint, calibration |
| 14 | Legal Compliance | Legal register | Obligation, due date, evidence |
| 15 | Emergency Preparedness | Drill dan ERP | Drill, equipment, contacts |
| 16 | Contractor Management | CSMS | Contractor eligibility |
| 17 | Asset Equipment Safety | Asset safety | Certificate, inspection, defect |
| 18 | Communication Campaign | Broadcast | Alert, lesson learned, campaign |
| 19 | Reporting Export Advanced | Report builder | Monthly QHSSE report |
| 20 | Admin Master Data Hardening | Admin polish | Full admin completeness |

## 5. Phase 0 — Core Foundation Super Complete

### 5.1 Tujuan

Membangun platform dasar yang mampu menopang seluruh modul QHSSE tanpa rework besar.

### 5.2 Deliverables

- Authentication.
- User management.
- Role and permission.
- Organization master.
- General master data.
- File upload core.
- Notification core.
- Numbering system.
- Workflow status core.
- Audit trail.
- Comment and activity log.
- Search/filter/pagination/export base.
- Dashboard shell.
- System settings.
- Seed data baseline.
- Core test suite/UAT checklist.

### 5.3 Execution Steps

#### Step 0.1 — Project Bootstrap

- Pilih tech stack final.
- Buat repository/project structure.
- Setup environment dev.
- Setup database.
- Setup lint/format/test baseline.
- Setup `.env.example`.
- Setup README developer.

Handoff output:

- Project bisa dijalankan lokal.
- Database terkoneksi.
- Developer setup terdokumentasi.

#### Step 0.2 — Base Architecture

- Buat struktur core dan modules.
- Buat layout folder modular.
- Buat convention naming.
- Buat shared response/error format.
- Buat base model/controller/service pattern.

Guardrail:

- Jangan membuat microservice.
- Jangan membuat plugin engine kompleks.

#### Step 0.3 — Authentication

- Login.
- Logout.
- Forgot/reset password.
- Change password.
- Session/token handling.
- Inactive user blocking.
- Login rate limit jika tersedia.

Acceptance:

- User valid bisa login.
- User inactive tidak bisa login.
- Password reset berjalan.

#### Step 0.4 — User, Employee, Company

- User CRUD.
- Employee CRUD.
- Company/contractor CRUD.
- Link user dengan employee/company.
- Active/inactive state.
- Import baseline optional.

Acceptance:

- Admin dapat membuat user internal dan contractor.
- Data inactive tidak hilang dari histori.

#### Step 0.5 — Role & Permission Engine

- Role CRUD.
- Permission seed.
- Role-permission assignment.
- User-role assignment.
- Permission middleware/server-side check.
- Scope: own, department, site, company, all.

Acceptance:

- Permission benar-benar membatasi endpoint/UI.
- Contractor hanya melihat data company-nya.

#### Step 0.6 — Organization & Master Data

- Site CRUD.
- Area CRUD.
- Department CRUD.
- Position CRUD.
- Severity.
- Priority.
- Status.
- Category.
- Risk matrix.
- Module master categories.

Acceptance:

- Master data dipakai sebagai referensi modul.
- Data yang sudah dipakai tidak hard delete.

#### Step 0.7 — File Service

- Upload.
- Download authorized.
- Preview metadata.
- Delete sesuai permission.
- File reference by module_name/reference_id.
- MIME/extension/size validation.

Acceptance:

- File tidak bisa diunduh tanpa akses record.
- File metadata tersimpan.

#### Step 0.8 — Numbering Service

- Sequence table.
- Prefix per module.
- Year reset.
- Optional site code.
- Atomic increment.

Acceptance:

- Tidak ada nomor duplikat.
- Format sesuai `13_NUMBERING_SPEC.md`.

#### Step 0.9 — Workflow Core

- Status transition helper.
- Workflow history.
- Reject reason required.
- Actor permission check.

Acceptance:

- Invalid transition ditolak.
- Workflow history tercatat.

#### Step 0.10 — Audit Trail

- Audit create/update/delete.
- Audit workflow action.
- Audit permission/master changes.
- Audit file access untuk data sensitif optional.

Acceptance:

- Perubahan kritikal dapat dilacak user, waktu, old/new value.

#### Step 0.11 — Comments & Activity Log

- Comment by module/reference.
- Mention basic optional.
- Activity system log.
- Timeline UI/API.

Acceptance:

- Detail record dapat menampilkan comment dan activity.

#### Step 0.12 — Notification Core

- In-app notification.
- Email notification baseline.
- Notification center.
- Mark as read.
- Event trigger interface.

Acceptance:

- Event test dapat membuat notifikasi ke user tepat.

#### Step 0.13 — List, Filter, Export Base

- Standard list pattern.
- Search.
- Filter.
- Sort.
- Pagination.
- CSV/Excel export baseline.

Acceptance:

- Core pages mengikuti pattern yang sama.

#### Step 0.14 — Dashboard Shell

- Base layout.
- KPI card component.
- Chart placeholder.
- Filter date/site/department.
- Role-aware shell.

Acceptance:

- Dashboard kosong/placeholder siap menerima metric modul.

#### Step 0.15 — Core UAT & Handoff

- Jalankan test checklist.
- Catat known issues.
- Update changelog.
- Update decision log.
- Buat handoff Phase 0.

### 5.4 Phase 0 Stop Condition

Berhenti sebelum Phase 1 jika:

- Permission belum stabil.
- File access belum aman.
- Numbering belum atomic.
- Audit trail belum berjalan.
- Master data belum cukup untuk Incident.

## 6. Phase 1 — Incident / Accident / Near Miss Reporting

### 6.1 Tujuan

Membangun pelaporan incident end-to-end sebagai modul transaksional pertama.

### 6.2 Scope

- Accident.
- Incident.
- Near miss.
- Unsafe act.
- Unsafe condition.
- Environmental incident trigger.
- Security incident trigger.
- Quality NCR trigger.
- Evidence upload.
- Severity and risk classification.
- Review, reject, close.
- PDF/export dasar.

### 6.3 Execution Steps

1. Review `modules/02-incident-reporting/*`.
2. Finalisasi mandatory fields.
3. Buat database incident.
4. Buat API CRUD draft/submit/review/reject/close.
5. Integrasi numbering `INC`.
6. Integrasi file attachment.
7. Integrasi comments/activity.
8. Integrasi audit trail.
9. Integrasi notification incident submitted/rejected/closed.
10. Buat UI list/form/detail/review.
11. Buat export list dan PDF detail minimal.
12. Buat dashboard metric incident dasar.
13. UAT incident.
14. Handoff Phase 1.

### 6.4 Acceptance

- Reporter bisa submit laporan.
- QHSSE bisa review.
- Reject wajib alasan.
- Status lifecycle berjalan.
- Evidence bisa diupload.
- Audit trail lengkap.
- Report bisa diexport.

## 7. Phase 2 — Investigation & RCA

### 7.1 Scope

- Trigger investigation dari incident severity tertentu.
- Assign investigation team.
- Timeline.
- Evidence.
- Witness statement.
- 5 Why.
- Fishbone.
- ICAM-lite.
- Root cause.
- Recommendation.
- Approval.
- Investigation report PDF.

### 7.2 Execution Steps

1. Review `modules/03-investigation-rca/*`.
2. Tambahkan incident relation.
3. Buat investigation record.
4. Buat team assignment.
5. Buat RCA forms.
6. Buat evidence/witness modules.
7. Integrasi action recommendation ke CAPA placeholder atau backlog jika CAPA belum aktif.
8. UI investigation detail.
9. PDF report.
10. UAT.
11. Handoff.

### 7.3 Guardrail

- Jangan membuat RCA terlalu kompleks sebelum 5 Why dan Fishbone berjalan.
- ICAM-lite cukup structured fields, bukan engine kompleks.

## 8. Phase 3 — CAPA / Action Tracking

### 8.1 Scope

- Action dari incident/investigation/manual.
- Corrective/preventive action.
- PIC.
- Verifier.
- Due date.
- Priority.
- Evidence.
- Verification.
- Reject/reopen.
- Extension request.
- Reminder and escalation.
- Aging report.

### 8.2 Execution Steps

1. Review `modules/04-capa-action-tracking/*`.
2. Buat action table generic source module/reference.
3. CRUD action.
4. Assign PIC/verifier.
5. Workflow open -> in progress -> waiting verification -> closed.
6. Evidence upload.
7. Reminder due soon.
8. Overdue job/check.
9. Escalation notification.
10. My actions/team actions pages.
11. Export action report.
12. Dashboard action metric.
13. UAT.
14. Handoff.

### 8.3 Guardrail

- CAPA harus generic agar semua modul bisa membuat action.
- Jangan membuat action khusus per modul.

## 9. Phase 4 — Inspection Checklist

### 9.1 Scope

- Checklist template.
- Dynamic questions.
- Schedule inspection.
- Assign inspector.
- Execute inspection.
- Photo evidence.
- Failed item auto-create CAPA.
- Approval.
- PDF report.

### 9.2 Execution Steps

1. Review `modules/05-inspection-checklist/*`.
2. Buat template/question model.
3. Buat schedule model.
4. Buat inspection execution model.
5. Buat answer types.
6. Buat failed item -> CAPA integration.
7. UI template builder sederhana.
8. UI execute inspection mobile-friendly.
9. PDF/export.
10. Dashboard inspection metric.
11. UAT.
12. Handoff.

## 10. Phase 5 — Dashboard & KPI

### 10.1 Scope

- Executive dashboard.
- QHSSE dashboard.
- Supervisor dashboard.
- Contractor dashboard.
- Incident KPI.
- CAPA KPI.
- Inspection KPI.
- Filters.
- Export dashboard.

### 10.2 Execution Steps

1. Review `modules/01-dashboard-kpi/*`.
2. Buat dashboard query/service per modul.
3. Buat KPI cards.
4. Buat charts.
5. Buat role-specific dashboard.
6. Tambahkan filters.
7. Export dashboard snapshot/report minimal.
8. UAT.
9. Handoff.

### 10.3 Guardrail

- Jangan membuat KPI yang belum ada datanya.
- Man-hours/LTIFR/TRIR masuk jika data man-hours tersedia.

## 11. Phase 6 — Document Control

### Scope

- Repository.
- Document numbering.
- Versioning.
- Draft/review/approval.
- Effective date.
- Review/expiry date.
- Obsolete archive.
- Read acknowledgment.
- Access control.
- Document register.

### Steps

1. Review `modules/07-document-control/*`.
2. Buat document and version model.
3. Buat approval workflow.
4. Buat file relation khusus document version.
5. Buat expiry reminder.
6. Buat acknowledgment.
7. UI repository/list/detail/version.
8. Export document register.
9. UAT.
10. Handoff.

## 12. Phase 7 — Audit Management

### Scope

- Annual audit plan.
- Audit schedule.
- Checklist.
- Finding.
- Major/minor/observation/OFI.
- Evidence.
- Auto CAPA.
- Close-out.
- Effectiveness verification.
- Audit report.

### Steps

1. Review `modules/06-audit-management/*`.
2. Buat audit plan/schedule.
3. Buat audit checklist/finding.
4. Integrasi finding -> CAPA.
5. Buat close-out and effectiveness verification.
6. UI audit pages.
7. PDF report.
8. UAT.
9. Handoff.

## 13. Phase 8 — Training & Competency

### Scope

- Training matrix.
- Mandatory training by position.
- Schedule.
- Attendance.
- Certificate upload.
- Expiry reminder.
- Contractor induction.
- Toolbox meeting.
- Compliance dashboard.

### Steps

1. Review `modules/08-training-competency/*`.
2. Buat training type/requirement matrix.
3. Buat schedule/session.
4. Buat attendance.
5. Buat certificate and expiry.
6. Buat reminder.
7. UI training matrix.
8. Export matrix.
9. UAT.
10. Handoff.

## 14. Phase 9 — Permit to Work

### Scope

- Permit request.
- Hot work.
- Height.
- Confined space.
- Electrical.
- Excavation.
- Lifting.
- LOTO.
- Checklist.
- Approval.
- Validity.
- Extension.
- Suspension.
- Closure.
- QR verification.

### Steps

1. Review `modules/09-permit-to-work/*`.
2. Buat permit type and checklist.
3. Buat permit request.
4. Integrasi JSA/risk attachment.
5. Integrasi training/contractor eligibility if available.
6. Buat approval path.
7. Buat validity and extension.
8. Buat closure.
9. UI permit active board.
10. Print/PDF/QR.
11. UAT.
12. Handoff.

### Guardrail

- PTW kompleks; mulai dengan general approval path, jangan workflow builder.

## 15. Phase 10 — Risk Management / HIRADC / JSA

### Scope

- HIRADC/HIRA register.
- JSA/JHA register.
- Risk matrix.
- Hazard identification.
- Existing controls.
- Additional controls.
- Initial/residual risk.
- Risk owner.
- Review date.
- Approval.
- Critical control verification.

### Steps

1. Review `modules/13-risk-management/*`.
2. Buat risk register.
3. Buat hazard/control structure.
4. Integrasi risk matrix core.
5. Buat JSA records reusable by PTW.
6. Approval workflow.
7. Review reminder.
8. Export risk register.
9. UAT.
10. Handoff.

## 16. Phase 11 — Environmental Management

### Scope

- Spill report.
- Waste tracking.
- B3 manifest.
- Water/energy/fuel consumption.
- Emission monitoring.
- Wastewater monitoring.
- Lab result.
- Legal limit alert.
- Environmental permit.

### Steps

1. Review `modules/10-environmental-management/*`.
2. Buat environmental record types.
3. Buat monitoring input.
4. Buat waste/manifest tracking.
5. Buat lab result and limit comparison.
6. Alert exceedance.
7. Integrasi CAPA untuk exceedance.
8. Export environmental report.
9. UAT.
10. Handoff.

## 17. Phase 12 — Security Management

### Scope

- Security incident.
- Theft/loss.
- Visitor management.
- Vehicle access.
- Gate pass.
- Patrol schedule.
- QR/NFC checkpoint.
- Emergency contact.
- Security dashboard.

### Steps

1. Review `modules/11-security-management/*`.
2. Buat visitor and access log.
3. Buat security incident.
4. Buat patrol checklist/schedule.
5. Buat checkpoint scan baseline.
6. Integrasi CAPA for findings.
7. Dashboard security metric.
8. Export report.
9. UAT.
10. Handoff.

## 18. Phase 13 — Quality Management

### Scope

- NCR.
- Customer complaint.
- Supplier complaint.
- Quality issue.
- RCA.
- CAPA.
- Calibration register.
- Supplier evaluation.
- Quality KPI.

### Steps

1. Review `modules/12-quality-management/*`.
2. Buat NCR and complaint.
3. Integrasi RCA/CAPA.
4. Buat calibration register and expiry.
5. Buat supplier evaluation.
6. Dashboard quality metric.
7. Export quality report.
8. UAT.
9. Handoff.

## 19. Phase 14 — Legal & Compliance Register

### Scope

- Legal register.
- Compliance obligation.
- Evidence.
- PIC.
- Due date.
- Review date.
- Compliance calendar.
- Permit/license register.
- Expiry reminder.
- Gap assessment.

### Steps

1. Review `modules/14-legal-compliance/*`.
2. Buat legal register.
3. Buat obligation/compliance task.
4. Evidence upload.
5. Reminder due/expiry.
6. Gap assessment.
7. Integrasi CAPA.
8. Export legal register.
9. UAT.
10. Handoff.

## 20. Phase 15 — Emergency Preparedness

### Scope

- ERP repository.
- Emergency contact.
- Muster point.
- Emergency equipment.
- Drill schedule.
- Attendance.
- Evaluation.
- Finding.
- Broadcast.

### Steps

1. Review `modules/15-emergency-preparedness/*`.
2. Buat emergency master.
3. Buat drill schedule/session.
4. Buat attendance/evaluation.
5. Finding -> CAPA.
6. Integrasi communication broadcast.
7. Export drill report.
8. UAT.
9. Handoff.

## 21. Phase 16 — Contractor Management

### Scope

- Contractor register.
- Worker register.
- Document requirement.
- Induction status.
- Training certificate.
- PTW eligibility.
- Performance KPI.
- Audit.
- CSMS prequalification.

### Steps

1. Review `modules/16-contractor-management/*`.
2. Expand contractor company/worker model.
3. Buat requirement checklist.
4. Buat contractor document compliance.
5. Integrasi training/induction.
6. Integrasi PTW eligibility.
7. Contractor KPI dashboard.
8. Export contractor report.
9. UAT.
10. Handoff.

## 22. Phase 17 — Asset & Equipment Safety

### Scope

- Equipment register.
- Safety critical equipment.
- Inspection schedule.
- Certificate register.
- Lifting equipment.
- Pressure vessel.
- Fire equipment.
- QR code.
- Defect report.

### Steps

1. Review `modules/17-asset-equipment-safety/*`.
2. Buat asset/equipment register.
3. Buat certificate and expiry.
4. Buat inspection schedule relation.
5. Buat defect report -> CAPA.
6. QR code asset.
7. Dashboard asset safety.
8. Export register.
9. UAT.
10. Handoff.

## 23. Phase 18 — Communication & Campaign

### Scope

- Safety alert.
- Lesson learned.
- Campaign.
- Toolbox material.
- Bulletin.
- Announcement.
- Read acknowledgment.
- Broadcast by site/department.

### Steps

1. Review `modules/18-communication-campaign/*`.
2. Buat communication content.
3. Buat target audience.
4. Buat broadcast.
5. Read acknowledgment.
6. Link to incident lesson learned.
7. Dashboard read compliance.
8. UAT.
9. Handoff.

## 24. Phase 19 — Reporting & Export Advanced

### Scope

- Incident report.
- Investigation report.
- Inspection report.
- Audit report.
- CAPA report.
- Training matrix.
- Monthly QHSSE report.
- Custom report builder.
- Scheduled email.

### Steps

1. Review `modules/19-reporting-export/*`.
2. Inventory all existing reports.
3. Standardize report template.
4. Build monthly QHSSE report generator.
5. Build scheduled report email.
6. Build custom report builder if still needed.
7. UAT report accuracy.
8. Handoff.

### Guardrail

- Jangan buat custom report builder sebelum report standar matang.

## 25. Phase 20 — Admin & Master Data Hardening

### Scope

- User admin.
- Role admin.
- Permission admin.
- Site/area/department.
- Employee.
- Contractor.
- Severity.
- Risk matrix.
- Category.
- Template.
- System logs.

### Steps

1. Review `modules/20-admin-master-data/*`.
2. Lengkapi master data yang muncul dari seluruh modul.
3. Buat import/export master.
4. Buat system log viewer.
5. Buat configuration validation.
6. UAT admin scenario.
7. Final handoff.

## 26. Handoff Setelah Setiap Generating

Setiap selesai generating phase/modul, wajib membuat file:

```text
handoff/PHASE-{number}-{slug}-HANDOFF.md
```

Isi wajib:

1. Phase name.
2. Scope yang dikerjakan.
3. File/folder yang dibuat/diubah.
4. Database migration/model yang dibuat.
5. API yang dibuat.
6. UI pages yang dibuat.
7. Permission yang ditambahkan.
8. Master data/seed yang ditambahkan.
9. Workflow/status yang ditambahkan.
10. Notification yang ditambahkan.
11. Report/export yang ditambahkan.
12. Test yang dijalankan.
13. Hasil test.
14. Known issues.
15. Deferred items.
16. Breaking changes.
17. Next phase readiness.
18. Prompt rekomendasi untuk generating berikutnya.

## 27. Prompt Template Untuk Generating Phase

Gunakan template ini saat mulai phase:

```text
Kita akan mengerjakan Phase [N] — [Nama Modul].

Wajib baca dan ikuti:
- docs-qhsse/23_EXECUTION_PLAN.md
- docs-qhsse/22_FOUNDATION_SUPER_SPEC.md
- docs-qhsse/21_BLUEPRINT.md
- docs-qhsse/modules/[module-folder]/MODULE_SPEC.md
- docs-qhsse/modules/[module-folder]/WORKFLOW.md
- docs-qhsse/modules/[module-folder]/DATA_MODEL.md
- docs-qhsse/modules/[module-folder]/UI_PAGES.md
- docs-qhsse/modules/[module-folder]/TEST_CASES.md

Kerjakan hanya scope phase ini. Jangan menambah fitur di luar spec.
Jika ada kebutuhan baru, catat di Decision Log atau backlog, jangan langsung dibangun.
Setelah selesai, buat handoff di folder handoff/ sesuai template.
```

## 28. Quality Gate Checklist

Sebelum menyatakan phase selesai:

- [ ] Scope sesuai execution plan.
- [ ] Tidak ada fitur liar di luar spec.
- [ ] Permission dicek server-side.
- [ ] UI menyembunyikan action tanpa permission.
- [ ] Data scoping own/department/site/company/all berjalan.
- [ ] Audit trail event kritikal tercatat.
- [ ] Workflow transition valid.
- [ ] Reject/cancel wajib reason.
- [ ] File upload/download aman.
- [ ] Notification trigger diuji.
- [ ] Export mengikuti filter dan permission.
- [ ] Test cases modul dijalankan.
- [ ] Handoff dibuat.
- [ ] Changelog diperbarui.
- [ ] Decision Log diperbarui jika ada keputusan baru.

## 29. Backlog Handling

Item yang muncul saat generating tapi bukan scope phase aktif dimasukkan ke:

```text
docs-qhsse/25_BACKLOG.md
```

Format:

| Date | Source Phase | Item | Reason | Priority | Target Phase | Status |
|---|---|---|---|---|---|---|

## 30. Final Rule

Jika ragu antara cepat dan rapi, pilih rapi pada pondasi. Untuk modul, pilih iteratif: minimal usable end-to-end dulu, lalu hardening.
