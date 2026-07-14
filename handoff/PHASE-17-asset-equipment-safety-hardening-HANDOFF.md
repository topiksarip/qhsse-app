# Handoff — Phase 17 Asset & Equipment Safety Hardening

## 1. Status

- Phase: Asset & Equipment Safety recovery/hardening
- Status: Partial — operational vertical slice verified; advanced analytics deferred
- Date: 2026-07-14 05:50 UTC
- Executor: Hermes Agent
- Branch: `develop`
- Commit/push: not performed

## 2. Scope Dikerjakan

- Total audit backend, security, schema compatibility, Laravel–Inertia contracts, and production-like browser behavior for Asset, Certificate, and Inspection.
- Fail-closed organization scope through authenticated `user -> employee` context.
- Per-record authorization and nested Asset–Certificate/Inspection ownership.
- Private Certificate evidence through a nested authorized download endpoint.
- Non-destructive Asset lifecycle and permanent Certificate/Inspection compliance history.
- Comments, activity, audit trail, CAPA provenance/prefill, status automation, notifications, CSV export, Asset register warnings, and seven scope-aware Asset dashboard KPI cards.
- Corrective migrations and cross-engine upgrade/rollback/re-forward verification.
- Indonesian date-only display, responsive detail layouts, and browser UAT on a disposable SQLite database.

## 3. Scope Tidak Dikerjakan

- Five Asset-specific dashboard charts, three compliance table widgets, and dashboard category/safety-critical/status filters from `MODULE_SPEC.md` §11.2–11.4. Recorded in `docs-qhsse/25_BACKLOG.md`.
- Contractor company-scope visibility. Asset/Site currently has no deterministic company ownership; access intentionally fails closed. Recorded in backlog.
- No production deployment, commit, push, or history rewrite.

## 4. File/Folder Dibuat

- `app/Console/Commands/CheckAssetCertificates.php`
- `app/Console/Commands/CheckAssetInspections.php`
- `app/Modules/Asset/AssetAccess.php`
- `app/Modules/Asset/AssetNotificationRecipients.php`
- `database/migrations/2026_07_14_120000_add_certificate_file_id_to_asset_certificates_table.php`
- `database/migrations/2026_07_14_120100_move_asset_inspection_capa_link_to_source_columns.php`
- `database/migrations/2026_07_14_120200_remove_soft_deletes_from_asset_tables.php`
- `database/migrations/2026_07_14_120300_restrict_site_deletion_for_assets_table.php`
- `resources/js/Utils/date.ts`
- `tests/Feature/Modules/AssetEquipmentSafetyTest.php`
- `tests/Feature/Modules/AssetMigrationCompatibilityTest.php`
- `handoff/PHASE-17-asset-equipment-safety-hardening-HANDOFF.md`

## 5. File/Folder Diubah

Principal changed areas:

- `app/Core/Export/CsvExporter.php`
- `app/Core/Permissions/CorePermissions.php`
- `app/Http/Controllers/Core/{ManagedFileController,CommentActivityController}.php`
- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/Modules/Asset/*.php`
- `app/Http/Controllers/Modules/Capa/CapaActionController.php`
- `app/Http/Requests/Modules/Asset/*.php`
- `app/Models/Modules/Asset/*.php`
- `app/Policies/Modules/Asset/*.php`
- `database/seeders/{AssetSeeder,NotificationTemplateSeeder}.php`
- `resources/js/Pages/Modules/Asset/**/*.tsx`
- `resources/js/Pages/Modules/Capa/Form.tsx`
- `resources/js/Pages/Dashboard.tsx`
- `routes/{console.php,modules/asset.php}`
- `tests/Feature/DashboardTest.php`
- `docs-qhsse/{19_DECISION_LOG.md,20_CHANGELOG.md,25_BACKLOG.md}`
- `docs-qhsse/modules/17-asset-equipment-safety/TEST_CASES.md`

The worktree contained unrelated pre-existing modified/untracked files. They were not reset, reverted, deleted, committed, or pushed.

## 6. Database/Migration/Model

- Released migrations were not edited.
- `120000` deterministically maps legacy Certificate evidence; ambiguous evidence aborts before schema/data mutation.
- `120100` moves Inspection CAPA linkage to `source_module=asset_inspection` and `source_reference_id`.
- `120200` converts legacy soft-deleted rows to permanent history, storing `legacy_deleted_at` and previous Asset status.
- `120300` changes `sites → assets` from cascade to restrict.
- SQLite parent-table rebuild can invoke child cascades inside a test transaction because `PRAGMA foreign_keys` cannot be changed mid-transaction. The migration snapshots and idempotently restores Certificate/Inspection rows with their original IDs and timestamps; PostgreSQL uses native FK alteration.
- Asset parent `next_inspection_date` is recomputed from the latest Inspection only.

## 7. API/Backend

- Asset routes use `auth`, `verified`, and `active` middleware.
- Create always generates the Asset number through `NumberingService` and sets status `active` server-side.
- Asset status/decommission endpoints are explicit and audited/activity-logged.
- Generic Core file/comment endpoints cannot bypass Asset policy.
- Certificate private evidence download validates Asset, Certificate, and ManagedFile ownership.
- Inspector options and validation are site-scoped and authoritative.
- CAPA creation uses stable Inspection source provenance.
- Shared CSV export neutralizes `=`, `+`, `-`, and `@` spreadsheet formula prefixes.

## 8. UI/Frontend

- Asset register shows safety-critical, worst Certificate status, and failed Inspection without CAPA warnings.
- Asset form explains generated numbering and does not expose create lifecycle status.
- Site filters Area and Department options.
- Asset detail has responsive lifecycle controls, horizontally scrollable tabs, Certificate/Inspection deep links, comments/activity/audit history, and locale-safe dates.
- Certificate form/detail uses `issuing_body`, required issued date, one private evidence file, and Indonesian date labels.
- Inspection form/detail uses authoritative `inspector_id`, `next_inspection_date`, CAPA relation/action, and readable date/timestamp formatting.
- Global dashboard now exposes seven scope-aware Asset KPI cards and an Asset quick action.

## 9. Permission Ditambahkan/Diselaraskan

Existing Asset permission keys were retained. Role matrix alignment:

- Supervisor: Asset view/export, Certificate view, Inspection view; no Asset create/update.
- Contractor: Asset view permission only; data still fails closed until deterministic company ownership exists.
- QHSSE Manager/Officer and Admin roles retain module duties according to policy.
- Backend policy remains authoritative; UI abilities are per record.

## 10. Master Data/Seed

- `AssetSeeder` now associates Department with the seeded Site.
- Notification templates support Certificate expiry and Inspection due reminders.

## 11. Workflow/Status

- Asset: `active ↔ inactive → decommissioned`; decommissioned is terminal.
- Certificate: `valid`, `expiring_soon`, `expiring_critical`, `expired`, recalculated by command.
- Inspection: `pass`, `fail`, `maintenance_required`; failed Inspection may link to CAPA.

## 12. Notification

- Certificate expiry command scheduled daily at 06:00.
- Inspection due command scheduled daily at 06:30.
- Recipient resolution is scope/site aware.
- Notification idempotency uses the existing notification core keying pattern.

## 13. Report/Export

- Asset CSV export preserves active filters and organizational scope.
- 20-column contract retained.
- UTF-8 BOM and spreadsheet formula neutralization applied in shared exporter.

## 14. Test Dijalankan

- `php artisan test tests/Feature/Modules/AssetEquipmentSafetyTest.php tests/Feature/DashboardTest.php`
- `php artisan test tests/Feature/Modules/AssetMigrationCompatibilityTest.php`
- The migration suite above on a disposable PostgreSQL 15 container.
- Full `php artisan test`.
- `npx tsc --noEmit --pretty false && npm run build`.
- Targeted Pint on all touched Asset/CAPA/Core/dashboard/migration/test PHP files.
- `git diff --check`, route/scheduler checks, fresh disposable migration/seed, and browser UAT on `127.0.0.1:8123`.

## 15. Hasil Test

Final verified evidence:

- Focused Asset + Dashboard: **39 passed, 417 assertions**.
- Migration compatibility SQLite: **3 passed, 34 assertions**.
- Migration compatibility PostgreSQL 15 disposable: **3 passed, 34 assertions**.
- Full application suite through `make test`: **449 passed, 2,328 assertions** using 32 processes.
- TypeScript (`npx tsc --noEmit --pretty false`) and Vite production build: exit 0; Vite completed in 18.58 seconds.
- Scoped Pint: **37 touched PHP files**, exit 0.
- `git diff --check`: exit 0.
- Docker no-cache build through `make build`: exit 0; both `qhsse-app-v3-app:latest` and `qhsse-app-v3-queue:latest` built.
- Route verification: 24 Asset routes; verbose output confirmed `EnsureUserIsActive` and permission middleware, including the nested private evidence route.
- Scheduler verification with local array cache: Certificate check at 06:00 and Inspection check at 06:30.

## 16. Browser UAT

Verified using `/tmp/qhsse-asset-uat.sqlite`, not working data:

- Login and dashboard load.
- Asset register filters, compliance column, safety-critical badges, and status automation display.
- Asset create contract: generated number, no create status input, Site-dependent organization options.
- Asset detail tabs and locale-safe dates.
- Certificate list/create/detail render with matching backend contract and no checked resource errors.
- Inspection list/create/detail render; native `requestSubmit()` created an Inspection and redirected to `?tab=inspections`, increasing the count from one to two.
- Comment create persisted and generated activity history.
- Certificate and Inspection automation commands completed successfully.
- Dashboard showed `Total Aset=3`, `Safety-Critical=2`, `Sertifikat Expired=1`, and the remaining Asset KPI cards with fixture values.
- Checked pages had no HTTP resource response `>= 400` and no raw `T00:00:00` date display.

Browser-driver limitation:

- Ordinary button clicks did not always emit form requests, while native `requestSubmit()` did. This was isolated to automation; backend feature tests and the native submit path passed.
- Lifecycle buttons use native `confirm()`. The browser tool timed out while waiting for the dialog; the dialog was cancelled and no mutation occurred. Lifecycle behavior is covered by automated feature tests but not claimed as a successful browser mutation.

## 17. Known Issues

- Global Pint still includes unrelated baseline style deviations outside this scope; only touched PHP files were formatted/checked.
- Shared global dashboard still does not enforce organizational scope for every older non-Asset KPI. Asset KPI queries themselves use `AssetAccess` and fail closed.
- No deterministic Asset company relationship exists, so Contractor company visibility remains fail closed.

## 18. Deferred Items

See `docs-qhsse/25_BACKLOG.md`:

- Asset dashboard charts/table widgets/extra filters.
- Deterministic Asset company ownership and Contractor company scope.

## 19. Decision Log Update

Added decisions for:

- Permanent Asset compliance history and Site delete restriction.
- Resource-scoped authorization overriding generic Core endpoints.
- Forward-only deterministic corrective migrations with ambiguity fail-fast.

## 20. Breaking Changes

- Deleting a Site referenced by an Asset now fails instead of cascading deletion. Administrators must inactivate used organization masters, consistent with Phase 0 rules.
- Supervisor no longer receives Asset create/update permissions.
- Certificate evidence must be accessed through the nested Asset Certificate endpoint, not generic Core file routes.

## 21. Next Phase Readiness

- Operational Asset/Certificate/Inspection vertical slice: ready; final tests, builds, formatting, route, scheduler, cross-engine migration, and browser gates are green with the documented browser-driver limitations.
- Advanced Asset analytics: not implemented; tracked in backlog.
- Production deployment: not performed and requires explicit deployment approval, backup, migration review, and production UAT.

## 22. Rekomendasi Prompt Berikutnya

```text
Continue from handoff/PHASE-17-asset-equipment-safety-hardening-HANDOFF.md.
First verify the final gate evidence and the dirty worktree. Do not deploy or commit without explicit instruction.
If prioritizing analytics, implement only the Asset dashboard charts/table widgets/filter backlog as a separate tested vertical slice.
```
