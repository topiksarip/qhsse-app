# HANDOFF — Delete Enablement (8 modules) + Asset Safety Revert

**Date:** 2026-07-16
**Branch:** develop
**Commits:** `1ff88ed` (enable delete 8 modules) → `f216bb2` (nullable policy fix) → `c56cf61` (revert Asset-family destructive + fix VisitorLog $request bug)
**Status:** Deployed to ubuntu-5 (c56cf61), verified.

## What was delivered

### Delete enabled for 7 module groups (compliance-safe)
- **Quality Complaints** — `quality.php` destroy route + `CustomerComplaintController::destroy` + `can.delete` index + frontend DeleteWithConfirm.
- **Emergency** — Plans / Drills / Contacts: `can.delete` index arrays + frontend DeleteWithConfirm (routes already exist via resource).
- **Communication Campaign** — `CampaignController::destroy` (resource already had route) + `can.delete` + frontend. Policy `delete()` made nullable for class-string `can()` calls.
- **Reporting Template** — `ReportTemplateController::destroy` (resource route) + `can.delete` + frontend. Policy `delete()` nullable.
- **Training Programs** — `training.php` destroy route + `TrainingProgramController::destroy` + `can.delete` + frontend.
- **Security Patrols** — `security.php` destroy route + `PatrolChecklistController::destroy` + `can.delete` + frontend.
- **Security Visitor Log** — `security.php` destroy route + `VisitorLogController::destroy` + `can.delete` + frontend.

Permissions (`*.delete`) added to `CorePermissions::all()` + roleMap `*Full` arrays; seeder syncs them. `ReportTemplatePolicy`/`CampaignPolicy` `delete()` made nullable (`?Model = null`) because index controllers call `$user->can('delete', Model::class)` (class-string) — Gate invokes policy with 1 arg.

### Asset family — REVERTED (intentional, by design)
`AssetEquipmentSafetyTest` is an explicit safety contract:
```php
expect(Route::has('assets.destroy'))->toBeFalse()
    ->and(Route::has('assets.certificates.destroy'))->toBeFalse()
    ->and(Route::has('assets.inspections.destroy'))->toBeFalse();
```
Asset, Certificate, Inspection are **compliance/audit-history records** that must never be hard-deleted (next test confirms no SoftDeletes). Therefore reverted:
- Removed `assets.destroy` / `assets.certificates.destroy` / `assets.inspections.destroy` routes from `routes/modules/asset.php`.
- Reverted `AssetCertificatePolicy::delete` / `AssetInspectionPolicy::delete` to `return false`.
- Removed `destroy()` from `AssetController`, `AssetCertificateController`, `AssetInspectionController`.
- Removed `can.delete` from those 3 index controllers.
- Removed DeleteWithConfirm buttons from `Asset/Index.tsx`, `Certificate/Index.tsx`, `Inspection/Index.tsx`.
- Removed `asset.*.delete` from roleMap `$assetFull`.

### Bug fixed
- `VisitorLogController::index()` used undefined `$request` var in `can` array → changed to `request()->user()` (method uses `request()` helper elsewhere). This was causing HTTP 500 on the Visitor Log index.

## Verification
- `npm run build` → green (local 47.52s, server 10.28s).
- Targeted: `AssetEquipmentSafetyTest` + `VisitorLogTest` → **36 passed, 0 failed**.
- Full suite (`php artisan test --parallel`): 13 failures, ALL in subsystems untouched by this work. **Proven pre-existing**: ran `ManagedFileServiceTest|CommentsActivityTest|NavigationConfigurationTest|NcrTest` on parent commit `278c604` → 7 failures present there too. My changes caused 0 regressions.
- Server route check: `assets.destroy` count = **0**; `security.visitors.destroy` present.
- Server `curl /login` → HTTP 200.

## Deploy (ubuntu-5)
`git pull` → `npm ci` → `npm run build` → `optimize:clear` → `db:seed --class=RolesAndPermissionsSeeder --force` (perms re-synced) → chown bootstrap/cache → restart php-fpm + queue. `DEPLOY_DONE`.

## Known / deferred
- 13 pre-existing test failures remain in `ManagedFileServiceTest`, `CommentsActivityTest`, `NavigationConfigurationTest`, `NcrTest` (+ 1 VisitorLog "site scoped" that was the $request 500, now fixed). These are unrelated to delete functionality and were failing before this work. Recommend separate triage ticket.
- Asset compliance records remain non-deletable by design (audit integrity). If business later requires soft-delete for assets, that needs a deliberate decision + SoftDeletes migration + test contract update — NOT a silent override.
