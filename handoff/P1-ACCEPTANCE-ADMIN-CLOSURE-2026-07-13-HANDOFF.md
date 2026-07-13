# P1 Acceptance and Admin Tooling Closure Handoff

**Date:** 2026-07-13
**Project:** QHSSE App v3
**Branch:** `develop`
**Baseline commit:** `c61830d`
**Status:** Source verified; production deployment and UAT pending

## 1. Scope Closed

This closure completes the remaining acceptance and operational gaps for:

- Phase 1 Incident Reporting.
- Visitor Log hardening and dedicated coverage.
- Customer Complaint hardening and dedicated coverage.
- Role–Permission Matrix.
- Atomic CSV Bulk Import for employees, sites, and departments.
- Dedicated Admin Dashboard.
- Inactive authenticated-session enforcement.

Security Patrol was completed separately in commit `c61830d` and remains part of the P1 enhancement package.

## 2. Incident Reporting

- Centralized backend access scope for list, detail, update, export, workflow, evidence, and print.
- Supports all/site/department/own scope and company scope through the reporter's company relationship.
- Relational validation prevents cross-site area, department, and involved-employee tampering.
- Corrected the involved-person pivot key to the schema's `incident_id`.
- Extracted transactional submit/review/reject/close lifecycle handling with audit, activity, and notification integration.
- Reject requires a reason and notifies the reporter.
- Added private evidence upload/download with record scope, file ownership, checksum, audit, and activity checks.
- Added involved-person form repeater and reliable edit-submit intent.
- Added an authorized print-ready report containing workflow history and evidence checksums for browser Save as PDF.

## 3. Visitor Log and Customer Complaint

- List, detail, form options, write operations, and CSV export share backend site scope.
- Cross-site relational tampering is rejected server-side.
- Visitor checkout and complaint close use database transactions and parent-row locks.
- Critical lifecycle changes produce shared audit and activity records.
- Customer Complaint uses dedicated view/create/update/close/export permissions.
- Company scope fails closed for both modules because their schemas do not provide a company ownership relationship.
- Visitor identity type follows the UI/request contract: KTP, SIM, Passport, or Lainnya.

## 4. Admin Tooling

### Role–Permission Matrix

- Uses existing Spatie roles and permissions.
- Protected by `core.roles.manage` on the backend.
- Super Admin is immutable through the matrix.
- `core.roles.manage` cannot be newly granted through the matrix.
- Existing protected permissions are preserved.
- A role cannot receive multiple `core.scope.*` permissions.
- Every successful synchronization is transactional and audited.

### Bulk Import

- Supports employees, sites, and departments.
- CSV templates are downloadable and permission-gated.
- Maximum 1,000 data rows per file.
- Validates UTF-8, headers, column count, duplicates, required fields, booleans, database uniqueness, and organization-code relationships.
- Employee imports use the required schema field `employee_no` and resolve company/site/department/position by code.
- The entire file is validated before one transaction; one invalid row prevents every insert.
- Audit metadata stores only import type, row count, and filename—not sensitive row contents.

### Admin Dashboard

- Identity KPIs: users, active users, employees, sites, and companies.
- Ten most recent audit entries.
- Permission-aware quick links to master data, user management, roles, and import.

## 5. Security and Architecture Decisions

- Added a real `active` middleware alias and invalidated sessions for users disabled after login.
- Private Incident evidence is never exposed through a public path or a generic unscoped file endpoint.
- No new permission tables, module-specific audit tables, or major dependencies were introduced.
- Print-ready HTML was selected instead of adding a PDF engine.
- Visitor/Complaint company scope fails closed rather than treating company scope as global.

## 6. Verification Evidence

| Check | Result |
|---|---|
| Focused P1 combined suite | 59 tests / 284 assertions passing |
| Canonical `make test` | 403 tests / 1,737 assertions passing |
| Canonical test parallelism | 32 processes, 190.07 seconds |
| Visitor Log suite | 8 tests / 52 assertions passing |
| Customer Complaint suite | 9 tests / 59 assertions passing |
| Role Matrix suite | 7 tests / 36 assertions passing |
| Admin Tooling suite | 8 tests included in final combined run |
| Incident acceptance suite | 8 tests / 44 assertions passing |
| Frontend `npm run build` | Passing, 1,476 modules transformed |
| Changed-file Pint | Passing |
| `git diff --check` | Passing |
| Route/config/view cache | Passing with local array-cache fallback |
| Temporary SQLite `migrate:fresh --seed` | Passing |

The first concurrent full-suite attempt failed because a config-cache command was run at the same time and workers read the local PostgreSQL cache pointing to an offline Docker address. After `optimize:clear` and an isolated canonical run, all 403 tests passed. This was a verification race, not a product-test assertion failure.

## 7. Environment Notes

- Local Redis is not active. Cache verification succeeds with `CACHE_STORE=array SESSION_DRIVER=array`.
- `.hermes/` is local environment data and must remain untracked.
- Repository remote credentials were observed to be embedded in remote URLs; values are intentionally omitted. Rotate them and migrate to SSH or a credential helper as an operational security follow-up.

### Production preflight after push

- Source commit `0bcceb19cba1f5fa1ce4dcbd08c8d0cddb0318de` was pushed to `origin/develop`; local/remote ahead-behind is `0/0`.
- Public production `/login` returns HTTP 200 and `/register` returns HTTP 404, confirming the existing deployment is online.
- New `/admin`, `/core/roles`, and Incident print routes return HTTP 404, and the login page references the previous asset manifest. Production has not auto-deployed this commit.
- Manual SSH preflight was blocked with `Permission denied (publickey)`. The local SSH agent has no identities, no production host alias/control socket is available, and the repository has no deployment workflow.
- No production backup, pull, migration, seed, service restart, or authenticated UAT was attempted after the access check failed.

## 8. Remaining Release Gates

The source phase is complete, but the package must not be called Released until all items below are complete:

1. Commit and push the verified change set.
2. Back up production database and source/config.
3. Fast-forward production `develop` without destructive migration.
4. Run Composer install, non-destructive migration, permission/numbering/notification seeders as appropriate, and frontend build.
5. Rebuild caches and restart PHP-FPM/queue workers.
6. Verify production Vite manifest and generated assets.
7. Run anonymous and authenticated smoke checks.
8. Run production UAT for Incident evidence/reject/involved persons/print, Visitor Log, Customer Complaint, Role Matrix, Bulk Import, and Admin Dashboard.
9. Mark applicable Module Register rows Released only after UAT evidence exists.

**Current blocker:** provide an authorized production SSH identity/host account, or have an authorized operator execute the deployment runbook. Do not mark this package Released before the production commit, manifest, services, migrations, and authenticated UAT are verified.

## 9. Deferred Module 20 Scope

The P1 admin-tooling package is complete. Full Admin & Master Data module acceptance still includes items outside this package, including System Settings, notification-template management, and bulk user activation/deactivation. These remain backlog and are not represented as completed here.
