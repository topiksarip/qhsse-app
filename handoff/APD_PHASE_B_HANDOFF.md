# APD Phase B — Issuance + Workflow Core — HANDOFF

> Date: 2026-07-17
> Module: 21-apd-ppe (APD / PPE)
> Build on top of: Phase A (katalog + stok inventori), commit 41b7e96
> Status: CODE COMPLETE — local build + 15 APD tests green. Deploy pending.

## What was built

Phase B makes APD actually distributable: PPE can be issued to a person (employee/contractor) or a location,
tracked through a request→approve→issue→return/dispose workflow, with stock auto-updated and full audit trail.

### New files
- `database/migrations/2026_07_16_150000_create_apd_issuances_table.php`
- `app/Models/Modules/Apd/ApdIssuance.php` — Auditable + SoftDeletes, polymorphic holder, relations to item/actor, status/holder helpers, `getStatuses()`/`getConditions()`/`$holderTypes`.
- `app/Policies/Modules/Apd/ApdIssuancePolicy.php` — per-transition gates (request/approve/issue/receive/export/view/viewAny).
- `app/Http/Requests/Modules/Apd/StoreApdIssuanceRequest.php` — validates item/holder/quantity/condition/dates.
- `app/Http/Requests/Modules/Apd/ProcessApdIssuanceRequest.php` — validates return/dispose/reject action + reason.
- `app/Modules/Apd/ApdLifecycle.php` — orchestrates create/request/approve/issue/return/dispose/reject + stock effects via WorkflowService + ActivityService.
- `app/Http/Controllers/Modules/Apd/ApdIssuanceController.php` — index/create/store/show/request/approve/issue/process/export.
- `resources/js/Pages/Modules/Apd/Issuances/{Index,Form,Show}.tsx` — follow existing APD/Catalog conventions (FilterPanel, Pagination, plain inputs, local formatDate).
- `tests/Feature/Modules/Apd/ApdIssuanceWorkflowTest.php` — 7 tests.

### Modified files
- `app/Core/Permissions/CorePermissions.php` — added `apd.request`, `apd.receive` to perms + roleMap (QHSSE full, Supervisor/Dept Head/Auditor/TopMgmt get request+approve, Employee/Contractor get request).
- `database/seeders/NumberingFormatSeeder.php` — added `apd_issue` (PPE-ISSUE-YYYY-NNNN, padding 4, yearly).
- `database/seeders/WorkflowSeeder.php` — added `APD_ISSUANCE` workflow (draft→requested→approved→issued→returned/disposed; requested→rejected).
- `app/Modules/Apd/ApdAccess.php` — added `scopeIssuance`, `canViewIssuance`, `employees`, `contractors` (fixed Employee namespace to `Core\Users`).
- `routes/modules/apd.php` — added `issuances` route group.
- `app/Http/Controllers/SearchController.php` — added `apd_issuances` global search entry.
- `resources/js/Pages/Modules/Apd/Items/Show.tsx` — fixed "Issue" CTA → now links to issuance create with `?apd_item_id=` for in_stock items.
- `database/seeders/ApdSeeder.php` — demo issuances (serial→employee, lot→location) created via ApdLifecycle so workflow state + stock are consistent.

### Key design decisions
- Issuance is its own entity; the source item's stock status flips (`in_stock` ↔ `issued`) and holder is assigned on issue, cleared on return/dispose.
- Morph map registered on `ApdIssuance` boot: `employee`→`Core\Users\Employee`, `contractor`→`Modules\Contractor\Contractor`, `location`→`Core\MasterData\Area`.
- `store` route middleware = `permission:apd.request`; an employee (no `apd.issue`) creates a **draft** issuance, not a direct issue.
- `process` action `return` requires `apd.receive` OR `apd.issue`; `dispose`/`reject` require `apd.issue`/`apd.approve` respectively.
- Workflow history + ActivityLog both recorded; `apd.issuances.show` passes `workflow.available_transitions` to render action buttons.

## Verification
- `php artisan test tests/Feature/Modules/Apd/` → 15 passed (Phase A 8 + Phase B 7).
- `npm run build` → green.
- `php artisan route:list` → 9 `apd.issuances.*` routes registered.
- SearchTest + NavigationConfigurationTest unaffected.

## Deploy (Ubuntu-5) — pending (b14)
```
ssh ubuntu@18.192.98.211
cd /var/www/qhsse
git pull
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --class=NumberingFormatSeeder --force   # if not already auto
php artisan db:seed --class=WorkflowSeeder --force
php artisan db:seed --class=ApdSeeder --force               # refresh demo data
php artisan optimize:clear
sudo systemctl restart php8.x-fpm
sudo systemctl restart qhsse-queue.service
```
Smoke: login → APD menu → Penugasan APD → Issue/Create → pick in-stock item → issue → verify stock item flips to `issued` and workflow history shows `issued`.

## Known gaps / next
- Phase C: inspections (apd_inspections, foto evidence, `tidak_layak`→`damaged`).
- Phase D: dashboard widgets + integration links (Risk/Incident/Training fit-test).
- Demo seeder uses `AppdLifecycle` so stock state is valid; production migration of older manual issuance rows (if any) should re-init workflow_state.
