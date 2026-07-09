# Handoff — Phase 0 Task 0.9 Numbering Service

## 1. Status

- Phase: 0 — Core Foundation
- Task: 0.9 — Numbering Service
- Status: Completed
- Date: 2026-07-09
- Executor: AI Agent

## 2. Scope Dikerjakan

- Implemented configurable numbering format table.
- Implemented atomic numbering counter table with unique constraint per module/site/year.
- Implemented generated number ledger with unique generated number constraint.
- Implemented reusable `NumberingService` for sequence generation.
- Added yearly reset support.
- Added optional site code support.
- Added baseline numbering formats for QHSSE modules.
- Added permissions, routes, controller, requests, and minimal Inertia UI.
- Added tests for seed, sequential generation, duplicate prevention baseline, site-code generation, missing site-code rejection, UI route generation, and permission blocking.

## 3. Scope Tidak Dikerjakan

- No direct integration into business modules yet.
- No dedicated API endpoint for external systems.
- No concurrency stress test with parallel workers; database transaction + row locks + unique constraints are implemented as the foundation.
- No audit trail event creation; audit trail is Task 0.11.

## 4. File/Folder Dibuat

- `database/migrations/2026_07_09_101000_create_numbering_formats_table.php`
- `database/migrations/2026_07_09_101001_create_numbering_counters_table.php`
- `database/migrations/2026_07_09_101002_create_generated_numbers_table.php`
- `app/Models/Core/Numbering/NumberingFormat.php`
- `app/Models/Core/Numbering/NumberingCounter.php`
- `app/Models/Core/Numbering/GeneratedNumber.php`
- `database/factories/Core/Numbering/NumberingFormatFactory.php`
- `app/Core/Numbering/NumberingService.php`
- `database/seeders/NumberingFormatSeeder.php`
- `app/Http/Controllers/Core/NumberingFormatController.php`
- `app/Http/Requests/Core/NumberingFormatRequest.php`
- `app/Http/Requests/Core/GenerateNumberRequest.php`
- `resources/js/Pages/Core/Numbering/Index.tsx`
- `resources/js/Pages/Core/Numbering/Form.tsx`
- `tests/Feature/Core/NumberingServiceTest.php`
- `handoff/PHASE-00-task-0.9-numbering-service-HANDOFF.md`

## 5. File/Folder Diubah

- `database/seeders/DatabaseSeeder.php`
- `app/Core/Permissions/CorePermissions.php`
- `routes/core.php`
- `resources/js/Layouts/AuthenticatedLayout.tsx`
- `docs-qhsse/19_DECISION_LOG.md`
- `docs-qhsse/20_CHANGELOG.md`

## 6. Database/Migration/Model

- Table: `numbering_formats`
  - `module_name`, `prefix`, `padding`, `separator`, `reset_frequency`, `include_year`, `include_site_code`, `sample`, `is_active`
- Table: `numbering_counters`
  - `module_name`, `site_code`, `year`, `current_number`
  - unique: `module_name, site_code, year`
- Table: `generated_numbers`
  - `module_name`, `number`, `site_code`, `year`, `sequence`, `reference_type`, `reference_id`, `generated_by`, `metadata`
  - unique: `number`
- Models:
  - `App\Models\Core\Numbering\NumberingFormat`
  - `App\Models\Core\Numbering\NumberingCounter`
  - `App\Models\Core\Numbering\GeneratedNumber`

## 7. API/Backend

- `GET /core/numbering` — format list + recent generated numbers, permission `core.numbering.view`
- `GET /core/numbering/create` — format create form, permission `core.numbering.create`
- `POST /core/numbering` — create format, permission `core.numbering.create`
- `GET /core/numbering/{numbering_format}/edit` — edit form, permission `core.numbering.update`
- `PUT /core/numbering/{numbering_format}` — update format, permission `core.numbering.update`
- `POST /core/numbering/generate` — generate test/utility number, permission `core.numbering.generate`

## 8. UI/Frontend

- `resources/js/Pages/Core/Numbering/Index.tsx`
  - Search numbering formats.
  - Generate test number.
  - Show recent generated numbers.
- `resources/js/Pages/Core/Numbering/Form.tsx`
  - Create/edit format.
  - Configure module, prefix, padding, separator, reset frequency, year/site options, active status.
- `resources/js/Layouts/AuthenticatedLayout.tsx`
  - Added Numbering navigation link.

## 9. Permission Ditambahkan

- `core.numbering.view`
- `core.numbering.create`
- `core.numbering.update`
- `core.numbering.generate`

## 10. Master Data/Seed Ditambahkan

- `NumberingFormatSeeder` seeds baseline formats for:
  - incident — `INC`
  - investigation — `INV`
  - capa — `ACT`
  - inspection — `INS`
  - audit — `AUD`
  - document — `DOC`
  - training — `TRN`
  - permit — `PTW` with site code
  - risk — `RSK`
  - environment — `ENV`
  - security — `SEC`
  - quality — `NCR`
  - legal — `LEG`
  - emergency — `EMG`
  - contractor — `CTR`
  - asset — `AST`
  - communication — `COM`

## 11. Workflow/Status Ditambahkan

- No workflow/status added.

## 12. Notification Ditambahkan

- No notifications added.

## 13. Report/Export Ditambahkan

- No report/export added.

## 14. Test Dijalankan

- `php artisan test tests/Feature/Core/NumberingServiceTest.php`
- `php artisan test`
- `npm run build`

## 15. Hasil Test

- Passed: `php artisan test tests/Feature/Core/NumberingServiceTest.php` — 6 passed, 15 assertions.
- Passed: `php artisan test` — 51 passed, 158 assertions.
- Passed: `npm run build`.
- Failed: none.
- Not tested: browser manual generation.

## 16. Known Issues

- Parallel process stress testing was not performed; database transaction, row locking, and unique constraints are in place.
- Generic UI route can generate utility numbers; business module integration must call `NumberingService` directly.
- Existing generated numbers are not rolled back/reused if future module transactions fail after number generation. This is acceptable for audit-safe document numbering, but modules should decide whether to generate at draft creation or submission.

## 17. Deferred Items

- Per-module automatic number assignment.
- Manual prefix per site/company beyond optional site code.
- Sequence preview without consuming a number.
- Number cancellation/void status if module transaction fails.
- Audit trail integration for format updates and number generation.

## 18. Decision Log Update

- Added decision: generated numbers are immutable ledger records and not reused.

## 19. Breaking Changes

- None.

## 20. Next Phase Readiness

- Ready for Phase 0 Task 0.10 — Workflow Core.
- Reason: Numbering Service is implemented, verified, and available for future modules.

## 21. Rekomendasi Prompt Berikutnya

```text
Lanjutkan Phase 0 Task 0.10 — Workflow Core.
Project path: /home/ubuntu/qhsse-app-v3.
Baca AGENTS.md, docs-qhsse/27_PHASE_0_BUILD_PLAN.md, dan handoff/PHASE-00-task-0.9-numbering-service-HANDOFF.md.
Kerjakan hanya workflow core: reusable workflow definitions/transitions/history using module_name + reference_id, transition validation, actor recording, reason support, tests, changelog, decision log if needed, and handoff.
Jangan kerjakan audit trail, comments, notification, export, dashboard, atau modul bisnis.
```
