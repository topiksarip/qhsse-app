# Handoff — Phase 6 Document Control

## 1. Status

- Phase: 6 — Document Control
- Status: Complete, reviewer findings remediated, and verified
- Date: 2026-07-11
- Branch: `develop`
- Base implementation commit: `4206421`
- Reviewer verdict before remediation: `REQUEST_CHANGES`

## 2. Scope yang Diimplementasikan

Vertical slice Document Control dari database hingga UI:

- Register dokumen terkontrol dengan search, filter, pagination, sorting base, dan CSV export.
- Jenis dokumen: SOP, WI, JSA, HIRADC, MSDS, policy, form, manual, dan other.
- Atomic document numbering melalui shared `NumberingService` (`DOC-YYYY-#####`).
- Private controlled-file upload melalui shared `ManagedFileService`.
- Upload contract khusus Document Control: PDF, Word, Excel, PowerPoint (`ppt`/`pptx`), maksimal 50 MB.
- Endpoint download khusus dokumen memverifikasi module/reference/collection/deleted state dan policy confidential.
- Generic core file CRUD/download mengecualikan `module_name=document`, sehingga tidak dapat dipakai sebagai alternate authorization path.
- Confidential document download hanya untuk owner, approver, Admin/Super Admin, dan QHSSE Manager.
- Workflow: `draft → review → approved → effective → obsolete`, termasuk reject/revise loop.
- Draft boleh incomplete; seluruh metadata wajib, tanggal efektif, owner, dan controlled file divalidasi saat submit review.
- Review cycle history pada `document_reviews`.
- Shared workflow history, audit trail, activity log, comments, dan notification core.
- Reminder tanggal review/expiry pada H-30, H-7, H-1 melalui scheduled command idempotent.
- Role-aware navigation dan UI Bahasa Indonesia.

## 3. Security Remediation

Independent review setelah commit awal menemukan dua blocker security. Keduanya telah ditutup:

1. **Alternate confidential file download path**
   - `ManagedFileController` tidak lagi menampilkan, mengunduh, atau menghapus file Document Control.
   - File Document Control hanya dapat diakses melalui endpoint modul yang menjalankan visibility dan confidential authorization.

2. **Organization-scope backend authorization**
   - Semua read dan mutation Document Control memakai scope yang sama.
   - `core.scope.all`: semua dokumen.
   - `core.scope.site`: dokumen dalam site employee, diturunkan lewat `controlled_documents.department_id → departments.site_id`.
   - `core.scope.department`: dokumen dalam department employee.
   - `core.scope.own`: dokumen yang dimiliki user.
   - Dokumen effective tetap dapat dibaca role view-only sesuai BR-10.
   - Assignment department/owner pada create/update juga divalidasi terhadap scope actor.

Tidak ditambahkan `site_id` duplikat pada `controlled_documents`.

## 4. Database dan Model

Migration:

- `controlled_documents`
- `document_reviews`
- Penambahan nullable unique `idempotency_key` pada `core_notifications`

Database invariants:

- Unique document number.
- Enum/check untuk document type, document status, dan review decision.
- Index untuk status/type/department/owner/review/expiry access paths.
- Metadata draft nullable sesuai BR-02, tetapi wajib lengkap pada submit sesuai BR-03.
- Unique notification idempotency key mencegah duplicate reminder antar-worker/node.

Models/factories:

- `ControlledDocument`
- `DocumentReview`
- `ControlledDocumentFactory`
- `DocumentReviewFactory`

Tidak dibuat tabel file, workflow, audit, comment, activity, atau notification per module; semuanya memakai shared Phase 0 core.

## 5. Permission Matrix

Permission modul:

- `document.control.view`
- `document.control.create`
- `document.control.update`
- `document.control.submit_review`
- `document.control.approve`
- `document.control.make_effective`
- `document.control.obsolete`
- `document.control.export`

Perbaikan role matrix:

- Department Head: view + submit review dalam department scope.
- Contractor: view-only effective documents.
- QHSSE Officer: create/update/submit dalam site scope.
- QHSSE Manager: full Document Control dalam all scope.
- Supervisor: create/update/submit dalam department scope.

Backend route middleware, action-level permissions, document visibility, mutation scope, dan assignment scope diterapkan. Crafted `action=submit_review` tidak dapat melewati permission submit-review.

## 6. Audit dan Traceability

`ControlledDocument` mengimplementasikan `ProvidesAuditContext` untuk generic field-change history dengan:

- `module_name = document`
- `reference_id = controlled_documents.id`

Business audit events eksplisit:

- `document.created`
- `document.updated`
- `document.file.uploaded`
- `document.submitted`
- `document.approved`
- `document.effective`
- `document.rejected`
- `document.revised`
- `document.obsolete`
- `document.file.downloaded`
- `document.exported`

Initial temporary numbering insert tidak menghasilkan audit snapshot `TEMP-*`; `document.created` menyimpan nomor final.

Keputusan reusable dicatat di Decision Log: module authorization mengalahkan generic file access, scope organisasi diturunkan dari relasi employee, dan scheduler idempotency dijamin database.

## 7. Notifications dan Scheduler

Command:

```bash
php artisan documents:check-expiry
```

Schedule:

```text
Daily 08:00, without overlapping
```

Behavior:

- Threshold H-30, H-7, H-1 untuk `review_date` dan `expiry_date`.
- Recipient reminder: owner + seluruh QHSSE Manager aktif.
- Effective notification: owner + user department terkait.
- Obsolete notification: owner + user department terkait + QHSSE Officer.
- Business notifications dikirim setelah transaction closure berhasil.
- Reminder key mencakup recipient, document, due field, due date, dan threshold.
- Unique DB key + `createOrFirst` membuat idempotency aman dari race antar-worker.

## 8. UI

Pages:

- `Modules/DocumentControl/Index.tsx`
- `Modules/DocumentControl/Form.tsx`
- `Modules/DocumentControl/Show.tsx`

Fitur UI mencakup status badge, confidential indicator, controlled-file download, action buttons berbasis permission/status, review history, workflow timeline, comments, dan activity log. Draft incomplete dirender aman dengan fallback label; form upload menjelaskan contract 50 MB dan PPT/PPTX.

## 9. Tests

Feature suite: `tests/Feature/Modules/DocumentControl/DocumentControlTest.php`

Cakupan 32 skenario:

- Permission allow/deny dan crafted permission bypass.
- Generic managed-file endpoint bypass regression.
- Department/site/own visibility dan mutation scope.
- Cross-organization assignment scope.
- Draft incomplete dan mandatory submit validation, termasuk revalidation tanggal pada submit draft existing.
- Review cycle reject/revise memastikan historical decision berubah menjadi `revise` sebelum re-submit.
- Upgrade-safe corrective migration dari released schema baseline pada PostgreSQL dan SQLite.
- Upload contract PPT/PPTX maksimal 50 MB.
- Database lifecycle invariants.
- Atomic numbering dan no temporary-number audit snapshot.
- Full workflow happy path serta reject/revise cycle.
- Role matrix Department Head, Contractor, Officer, Manager, Supervisor.
- Confidential download authorization dan reference mismatch.
- Filters dan CSV export.
- Shared comments authorization.
- Multi-recipient H-30/H-7/H-1 reminders dan atomic idempotency.
- Effective/obsolete stakeholder notifications.
- Explicit business audit events.

## 10. Verification

Fresh verification setelah perubahan source terakhir:

```bash
vendor/bin/pint --test <touched PHP files>
# PASS — 11 touched PHP files

php artisan migrate:fresh --seed --force
# PASS — PostgreSQL fresh schema + seluruh seeder, termasuk corrective migration 000007

php artisan migrate:rollback --step=1 --force && php artisan migrate --force
# PASS — migration 000007 rollback/forward pada PostgreSQL aktif dan SQLite sementara

# PostgreSQL upgrade simulation dari released migration 000005
# PASS — baseline row preserved; title/type/version nullable; 3 CHECK constraints dan 5 indexes terpasang

php artisan test --filter=DocumentControlTest --compact
# PASS — 32 tests, 149 assertions

npm run build
# PASS — 1.063 modules transformed, built in 5,01 detik

php artisan test
# PASS — 192 tests, 721 assertions, 161,91 detik

php artisan route:list --name=document.control
# PASS — 15 routes

php artisan schedule:list
# PASS — documents:check-expiry terdaftar pada 0 8 * * *
```

Static diff security scan: `CLEAN`.

Browser smoke test sebelumnya pada commit Phase 6 awal:

- Seeded Super Admin dapat login.
- Document Register dan form Buat Dokumen dapat dirender.
- Menu Document Control tampil sesuai permission.
- Tidak ada runtime error pada browser console.
- Visual inspection tidak menemukan overlap atau elemen terpotong.

## 11. Known Constraints

- Full-repo Pint masih memiliki baseline style debt pada file lama; gate format sengaja dibatasi ke touched PHP files agar tidak membuat legacy churn.
- Schema Document Control tetap dua tabel sesuai spec; tidak ada tabel version-history tambahan.
- Email/WhatsApp delivery tetap di luar scope; notification core saat ini in-app.

## 12. Next Phase

Phase default berikutnya adalah Audit Management sesuai roadmap aktif, setelah user memberi instruksi lanjut. Jangan mengembangkan project lama `/home/ubuntu/qhsse-app-v2`.
