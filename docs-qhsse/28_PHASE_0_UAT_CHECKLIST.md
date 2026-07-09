# Phase 0 Core UAT Checklist

Date: 2026-07-09
Status: Passed with documented deferred items
Scope: Core Foundation only, no Phase 1 business modules

## 1. Environment Readiness

| Check | Result | Evidence |
|---|---|---|
| Laravel routes registered | Passed | `php artisan route:list --path=core` shows 112 core routes |
| Dashboard route registered | Passed | `php artisan route:list --name=dashboard` shows `/dashboard` handled by `DashboardController` |
| Migrations applied on local DB | Passed | `php artisan migrate:status` shows all Phase 0 migrations as `Ran` |
| Seeders runnable on local DB | Passed | `php artisan db:seed` completed after seeder cache refresh fix |
| Test suite | Passed | `php artisan test` — 79 passed, 295 assertions |
| Frontend build | Passed | `npm run build` — Vite build completed |

## 2. Phase 0 Acceptance Checklist

| # | Acceptance | Result | Coverage |
|---|---|---|---|
| 1 | Admin can login and manage users | Passed | Auth tests, `IdentityCoreTest`, `RbacCoreTest` |
| 2 | Role/permission protects backend and UI actions | Passed | `RbacCoreTest`, permission middleware, role-aware navigation props |
| 3 | Site/area/department/company/employee master data works | Passed | `OrganizationMasterTest`, `IdentityCoreTest` |
| 4 | General master data required for Incident exists | Passed | `QhsseMasterDataTest` |
| 5 | File upload/download is secure | Passed | `ManagedFileServiceTest` |
| 6 | Numbering service generates unique numbers | Passed | `NumberingServiceTest` |
| 7 | Workflow transition history works | Passed | `WorkflowCoreTest` |
| 8 | Audit trail captures critical changes | Passed | `AuditTrailTest` |
| 9 | Comments/activity can attach to module/reference | Passed | `CommentsActivityTest` |
| 10 | Notification center works | Passed | `NotificationCoreTest` |
| 11 | Export base works | Passed | `SearchFilterExportBaseTest` |
| 12 | Dashboard shell works | Passed | `DashboardShellTest` |
| 13 | Tests/build pass or known failures documented | Passed | `php artisan test`, `npm run build` |
| 14 | Handoff exists | Passed | `handoff/PHASE-00-core-foundation-HANDOFF.md` |

## 3. Smoke Test Coverage

| Scenario | Result | Evidence |
|---|---|---|
| Login redirects to dashboard | Passed | `DashboardShellTest`, auth feature tests |
| Admin CRUD baseline | Passed | Company, employee, user, site, area, department, position tests |
| Permission block | Passed | RBAC, organization, master data, files, workflow, export tests |
| File upload/download | Passed | Private upload/download and unauthorized download tests |
| Notification | Passed | Create, view own notifications, read/unread, test notification route |
| Export filtered list | Passed | Filtered Department CSV export and unauthorized export tests |
| Dashboard filters render | Passed | Dashboard shell test asserts filters/options/widgets |

Note: browser-click manual smoke test was not run in this CLI session. The checklist above is covered by automated feature tests and command-level verification.

## 4. Known Issues

- Dashboard metrics are placeholders based on core tables only; real business metrics start in module phases.
- CSV export is synchronous and covers baseline Sites/Departments only.
- Export permission is global (`core.export.csv`), not per resource.
- Email/queue/realtime notification delivery is deferred; notification core is in-app and metadata-ready.
- Mention notification integration from comments is deferred.
- Browser manual smoke test remains recommended before production-style demo.

## 5. Deferred Items

- Real Incident/CAPA/Inspection dashboard widgets.
- Excel/PDF exports if business reporting requires them.
- Queue-backed exports for large datasets.
- Email/WhatsApp/Telegram notification channels.
- User notification preferences and realtime notification badge.
- Dashboard personalization.

## 6. Final Verification Commands

```bash
php artisan migrate:status
php artisan db:seed
php artisan test
npm run build
```
