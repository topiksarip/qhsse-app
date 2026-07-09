# Handoff — Phase 0 Task 0.14 Search, Filter, Pagination, Export Base

## 1. Status

- Phase: 0 — Core Foundation
- Task: 0.14 — Search, Filter, Pagination, Export Base
- Status: Completed
- Date: 2026-07-09
- Executor: AI Agent

## 2. Scope Dikerjakan

- Implemented shared `ListQuery` service for search, active-state filter, sort, direction, and per-page pagination.
- Implemented streamed `CsvExporter` service without adding a new package/dependency.
- Applied shared list query pattern to Sites and Departments core admin lists.
- Added CSV export endpoints for Sites and Departments.
- Added `core.export.csv` permission and seeded it through existing RBAC seeder.
- Updated Sites and Departments index pages with search/filter/sort/per-page controls and CSV export links.
- Added focused tests for shared list filtering, filtered CSV export, and permission blocking.
- Updated changelog and decision log.

## 3. Scope Tidak Dikerjakan

- No Excel/XLSX export package added.
- No PDF export added.
- No background export jobs added.
- No dashboard shell implementation; this remains Task 0.15.
- Existing non-site/department list pages were not visually redesigned in this task.

## 4. File/Folder Dibuat

- `app/Core/Query/ListQuery.php`
- `app/Core/Export/CsvExporter.php`
- `tests/Feature/Core/SearchFilterExportBaseTest.php`
- `handoff/PHASE-00-task-0.14-search-filter-export-HANDOFF.md`

## 5. File/Folder Diubah

- `app/Http/Controllers/Core/SiteController.php`
- `app/Http/Controllers/Core/DepartmentController.php`
- `app/Core/Permissions/CorePermissions.php`
- `routes/core.php`
- `resources/js/Pages/Core/Sites/Index.tsx`
- `resources/js/Pages/Core/Departments/Index.tsx`
- `docs-qhsse/19_DECISION_LOG.md`
- `docs-qhsse/20_CHANGELOG.md`

## 6. Database/Migration/Model

- No database schema changes.
- No model changes.
- Existing `RolesAndPermissionsSeeder` now seeds `core.export.csv` via `CorePermissions::all()`.

## 7. API/Backend

- `GET /core/sites` — now uses shared search/filter/sort/pagination pattern.
- `GET /core/departments` — now uses shared search/filter/sort/pagination pattern.
- `GET /core/sites/export` — streams filtered Sites CSV, permission `core.export.csv`.
- `GET /core/departments/export` — streams filtered Departments CSV, permission `core.export.csv`.

Supported query params through `ListQuery`:

- `search`
- `is_active`
- `sort`
- `direction`
- `per_page`

## 8. UI/Frontend

- `resources/js/Pages/Core/Sites/Index.tsx`
  - Search input.
  - Active/inactive filter.
  - Sort field selector.
  - Direction selector.
  - Per-page selector.
  - Reset button.
  - Export CSV link preserving current filters.
- `resources/js/Pages/Core/Departments/Index.tsx`
  - Same list controls as Sites.
  - Displays related Site name in the list.
  - Export CSV link preserving current filters.

## 9. Permission Ditambahkan

- `core.export.csv`

## 10. Master Data/Seed Ditambahkan

- No master data records added.
- Permission seed updated through `CorePermissions`.

## 11. Workflow/Status Ditambahkan

- No workflow/status definitions added.

## 12. Notification Ditambahkan

- No notifications added.

## 13. Report/Export Ditambahkan

- Sites CSV export.
- Departments CSV export.
- Export respects list filters and `core.export.csv` permission.
- CSV is streamed directly; no public export files are created.

## 14. Test Dijalankan

- `php artisan test tests/Feature/Core/SearchFilterExportBaseTest.php`
- `php artisan test`
- `npm run build`

## 15. Hasil Test

- Passed: `php artisan test tests/Feature/Core/SearchFilterExportBaseTest.php` — 3 passed, 23 assertions.
- Passed: `php artisan test` — 76 passed, 245 assertions.
- Passed: `npm run build`.
- Failed: none.
- Not tested: browser manual export click/download.

## 16. Known Issues

- Export permission is a coarse global permission (`core.export.csv`), not per-resource export permission.
- CSV export only covers Sites and Departments for Phase 0 baseline verification.
- CSV output is synchronous; large exports may need queued jobs later.

## 17. Deferred Items

- Excel/XLSX export if required.
- PDF exports for formal reports.
- Queued/background export jobs.
- Applying the same UI control redesign to every existing core list page.

## 18. Decision Log Update

- Added decision: use lightweight shared `ListQuery` and streamed CSV exporter rather than adding an export package in Phase 0.

## 19. Breaking Changes

- None.

## 20. Next Phase Readiness

- Ready for Phase 0 Task 0.15 — Dashboard Shell.
- Reason: baseline search/filter/pagination and permission-protected CSV export are implemented and verified.

## 21. Rekomendasi Prompt Berikutnya

```text
Lanjutkan Phase 0 Task 0.15 — Dashboard Shell.
Project path: /home/ubuntu/qhsse-app-v3.
Baca AGENTS.md, docs-qhsse/27_PHASE_0_BUILD_PLAN.md, dan handoff/PHASE-00-task-0.14-search-filter-export-HANDOFF.md.
Kerjakan hanya dashboard shell: main dashboard route, reusable KPI card, chart placeholder, date/site/department filter UI, role-aware menu shell, tests, changelog, decision log jika perlu, dan handoff.
Jangan kerjakan modul bisnis Phase 1.
```
