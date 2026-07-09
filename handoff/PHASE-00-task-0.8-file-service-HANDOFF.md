# Handoff — Phase 0 Task 0.8 File Service

## 1. Status

- Phase: 0 — Core Foundation
- Task: 0.8 — File Service
- Status: Completed
- Date: 2026-07-09
- Executor: AI Agent

## 2. Scope Dikerjakan

- Implemented secure managed file metadata table using `module_name + reference_id + collection` reference pattern.
- Implemented private-storage upload service.
- Implemented authorized file list, upload, download, and delete/soft-delete routes.
- Implemented MIME/extension/size validation for uploads.
- Added file service permissions and included them in seeded roles.
- Added minimal Inertia UI for managed file list and upload.
- Added feature tests for upload, authorized download, unauthorized blocking, invalid file rejection, oversize rejection, and deleted download blocking.

## 3. Scope Tidak Dikerjakan

- No module-specific attachment widgets yet; business modules will integrate with File Service later.
- No antivirus/malware scanning.
- No image thumbnail generation.
- No S3/cloud disk setup.
- No audit trail event creation; audit trail is Task 0.11.
- No per-record row-level access policy yet; full scope enforcement comes after workflow/scope integration per module.

## 4. File/Folder Dibuat

- `app/Core/Files/FileReference.php`
- `app/Core/Files/ManagedFileService.php`
- `app/Http/Controllers/Core/ManagedFileController.php`
- `app/Http/Requests/Core/ManagedFileUploadRequest.php`
- `resources/js/Pages/Core/Files/Index.tsx`
- `resources/js/Pages/Core/Files/Form.tsx`
- `tests/Feature/Core/ManagedFileServiceTest.php`
- `handoff/PHASE-00-task-0.8-file-service-HANDOFF.md`

## 5. File/Folder Diubah

- `database/migrations/2026_07_09_095858_create_managed_files_table.php`
- `app/Models/Core/Files/ManagedFile.php`
- `database/factories/Core/Files/ManagedFileFactory.php`
- `app/Core/Permissions/CorePermissions.php`
- `routes/core.php`
- `resources/js/Layouts/AuthenticatedLayout.tsx`
- `docs-qhsse/19_DECISION_LOG.md`
- `docs-qhsse/20_CHANGELOG.md`

## 6. Database/Migration/Model

- Table: `managed_files`
- Key columns:
  - `module_name`
  - `reference_id`
  - `collection`
  - `disk`
  - `path`
  - `original_name`
  - `stored_name`
  - `mime_type`
  - `extension`
  - `size`
  - `checksum`
  - `metadata`
  - `uploaded_by`
  - `deleted_at`
  - `deleted_by`
- Indexes:
  - `module_name, reference_id`
  - `module_name, reference_id, collection`
  - `uploaded_by`
  - `deleted_at`
- Model: `App\Models\Core\Files\ManagedFile`
- Factory: `Database\Factories\Core\Files\ManagedFileFactory`

## 7. API/Backend

- `GET /core/files` — managed file list, permission `core.files.view`
- `GET /core/files/create` — upload form, permission `core.files.upload`
- `POST /core/files` — upload file, permission `core.files.upload`
- `GET /core/files/{file}/download` — private download, permission `core.files.download`
- `DELETE /core/files/{file}` — mark file deleted, permission `core.files.delete`

## 8. UI/Frontend

- `resources/js/Pages/Core/Files/Index.tsx`
  - Search by file/module/collection.
  - Filter by module and reference id.
  - Download and delete actions.
- `resources/js/Pages/Core/Files/Form.tsx`
  - Upload form with module name, reference id, collection, and file.
- `resources/js/Layouts/AuthenticatedLayout.tsx`
  - Added Files navigation link.

## 9. Permission Ditambahkan

- `core.files.view`
- `core.files.upload`
- `core.files.download`
- `core.files.delete`

## 10. Master Data/Seed Ditambahkan

- No master data added.
- Role/permission seeder now includes file service permissions via `CorePermissions`.

## 11. Workflow/Status Ditambahkan

- No workflow/status added.

## 12. Notification Ditambahkan

- No notifications added.

## 13. Report/Export Ditambahkan

- No report/export added.

## 14. Test Dijalankan

- `php artisan test tests/Feature/Core/ManagedFileServiceTest.php`
- `php artisan test`
- `npm run build`

## 15. Hasil Test

- Passed: `php artisan test tests/Feature/Core/ManagedFileServiceTest.php` — 4 passed, 20 assertions.
- Passed: `php artisan test` — 45 passed, 143 assertions.
- Passed: `npm run build`.
- Failed: none.
- Not tested: manual browser upload/download.

## 16. Known Issues

- File Service currently uses route permission checks only; row-level authorization is deferred until module-specific record ownership/scope exists.
- Delete is metadata soft-delete only; stored binary remains in private storage for retention/audit needs.
- Upload UI is intentionally generic and basic.

## 17. Deferred Items

- Module-specific reusable attachment component.
- File access policies per module/reference.
- S3/private object storage configuration.
- Antivirus scanning.
- File preview and thumbnail support.
- Audit trail integration for upload/delete/download events.

## 18. Decision Log Update

- Added decision: keep managed files private and download only through authorized controller endpoints.

## 19. Breaking Changes

- None.

## 20. Next Phase Readiness

- Ready for Phase 0 Task 0.9 — Numbering Service.
- Reason: File Service foundation is implemented, verified, and available for future modules.

## 21. Rekomendasi Prompt Berikutnya

```text
Lanjutkan Phase 0 Task 0.9 — Numbering Service.
Project path: /home/ubuntu/qhsse-app-v3.
Baca AGENTS.md, docs-qhsse/27_PHASE_0_BUILD_PLAN.md, dan handoff/PHASE-00-task-0.8-file-service-HANDOFF.md.
Kerjakan hanya numbering service: configurable prefix, yearly reset, optional site code, atomic sequence generation, tests, changelog, decision log if needed, and handoff.
Jangan kerjakan workflow, audit trail, comments, notification, export, dashboard, atau modul bisnis.
```
