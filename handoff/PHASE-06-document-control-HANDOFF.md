# Handoff — Phase 6 Document Control

## 1. Status

- Phase: 6 — Document Control
- Status: Complete and verified
- Date: 2026-07-11
- Branch: `develop`

## 2. Scope yang Diimplementasikan

Vertical slice Document Control dari database hingga UI:

- Register dokumen terkontrol dengan search, filter, pagination, sorting base, dan CSV export.
- Jenis dokumen: SOP, WI, JSA, HIRADC, MSDS, policy, form, manual, dan other.
- Atomic document numbering melalui shared `NumberingService` (`DOC-YYYY-#####`).
- Private controlled-file upload melalui shared `ManagedFileService`.
- Endpoint download khusus dokumen yang memverifikasi module/reference/collection/deleted state.
- Confidential document download hanya untuk owner, approver, Admin/Super Admin, dan QHSSE Manager.
- Workflow: `draft → review → approved → effective → obsolete`, termasuk reject/revise loop.
- Review cycle history pada `document_reviews`.
- Shared workflow history, audit trail, activity log, comments, dan notification core.
- Reminder tanggal review/expiry pada H-30, H-7, H-1 melalui scheduled command idempotent.
- Role-aware navigation dan UI Bahasa Indonesia.

## 3. Database dan Model

Migration:

- `controlled_documents`
- `document_reviews`

Models/factories:

- `ControlledDocument`
- `DocumentReview`
- `ControlledDocumentFactory`
- `DocumentReviewFactory`

Tidak dibuat tabel file, workflow, audit, comment, activity, atau notification per module; semuanya memakai shared Phase 0 core.

## 4. Permission Matrix

Permission baru:

- `document.control.view`
- `document.control.create`
- `document.control.update`
- `document.control.submit_review`
- `document.control.approve`
- `document.control.make_effective`
- `document.control.obsolete`
- `document.control.export`

Backend route middleware dan action-level checks diterapkan. Crafted `action=submit_review` pada create tidak dapat melewati permission submit-review.

## 5. Audit dan Traceability

`ControlledDocument` mengimplementasikan `ProvidesAuditContext`, sehingga shared `Auditable` trait mencatat create/update/delete tepat satu kali dengan:

- `module_name = document`
- `reference_id = controlled_documents.id`

Keputusan reusable ini dicatat sebagai ADR-011. Workflow transition tetap menghasilkan workflow audit/history melalui core service.

## 6. UI

Pages:

- `Modules/DocumentControl/Index.tsx`
- `Modules/DocumentControl/Form.tsx`
- `Modules/DocumentControl/Show.tsx`

Fitur UI mencakup status badge, confidential indicator, controlled-file download, action buttons berbasis permission/status, review history, workflow timeline, comments, dan activity log.

## 7. Scheduler

Command:

```bash
php artisan documents:check-expiry
```

Schedule:

```text
Daily 07:00, without overlapping
```

Reminder dibuat idempotent per dokumen/recipient/hari.

## 8. Tests

Feature suite: `tests/Feature/Modules/DocumentControl/DocumentControlTest.php`

Cakupan:

- Permission allow/deny dan crafted permission bypass.
- Validation dan date rules.
- Atomic numbering.
- Private upload dan audit context.
- Full workflow happy path serta reject/revise cycle.
- Submit tanpa file.
- Edit status lock.
- Employee effective-only visibility.
- Confidential download authorization dan reference mismatch.
- Filters dan CSV export.
- Shared comments return path.
- Expiry reminder threshold dan idempotency.

## 9. Verification

Fresh verification setelah perubahan kode terakhir:

```bash
php artisan test --filter=DocumentControlTest --compact
# PASS — 21 tests, 98 assertions

php artisan migrate:fresh --seed --force
# PASS — seluruh migration dan seeder termasuk DocumentControlSeeder

npm run build
# PASS — 1.063 modules transformed, built in 4,74 detik

php artisan test
# PASS — 181 tests, 670 assertions
```

Browser smoke test pada `http://127.0.0.1:8080`:

- Seeded Super Admin dapat login.
- Document Register dan form Buat Dokumen dapat dirender.
- Menu Document Control tampil sesuai permission.
- Tidak ada runtime error pada browser console.
- Visual inspection tidak menemukan overlap atau elemen terpotong.

Setelah test, local SQLite dikembalikan ke state fresh-seeded dan akun UAT deterministik diverifikasi aktif dengan role Super Admin.

## 10. Next Phase

Lanjut sesuai roadmap aktif setelah user menentukan prioritas phase berikutnya. Jangan mengembangkan project lama `/home/ubuntu/qhsse-app-v2`.
