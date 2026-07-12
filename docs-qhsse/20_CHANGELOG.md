# Changelog

## Unreleased

- Added permission-aware main navigation for Audit, Training, Emergency, Contractor, Asset, Campaign, Report Templates, and Saved Reports; fixed Asset read-only route middleware and added navigation configuration regression coverage.
- Repaired P0 Inertia UI resolution: aligned Emergency create/edit controllers with shared `CreateOrEdit` pages, normalized Asset certificate/inspection render paths to singular frontend folders, added Training Program detail, Report Template form/detail, and Saved Report detail pages, and added an architecture regression test requiring every literal `Inertia::render()` target to resolve to a TSX page.
- Hardened production deployment readiness: removed duplicate core update route names while retaining PUT/PATCH support, made baseline seeding independent of development-only Faker packages, ensured the Super Admin baseline user exists before business demo seeders run, disabled public self-registration by default for controlled RBAC onboarding, and added regression coverage for route caching and production-safe seeding.
- **Completed Phase 3.3 UI/UX EmptyState Implementation**: Added EmptyState component to all 22 Index pages across modules (Incident, Audit, Environmental, Permit, Security, RiskManagement, LegalCompliance, Contractor, Asset, Training, EmergencyPreparedness, DocumentControl, Inspection/Templates, Reporting, Communication/Campaign) with consistent UX, permission-aware actions, contextual messaging, and 100% coverage; implemented in 6 batches with 107+ surgical operations, all builds verified passing, zero violations, and pushed to GitHub (commits: fbcc746, 0b4061c, 09a2e69, 78f5542, bf98e25).
- Fixed browser/runtime regressions across later modules: restored backward-compatible fluent `ListQuery` contracts, corrected Asset/Contractor master-data namespaces and Legal evidence model namespace, and added authenticated business-index smoke coverage. Verified 340 tests, 1.212 assertions, production assets, and 25/25 live UAT business pages.
- Completed total debugging of the 19-test failure baseline: fixed Audit numbering/finding/evidence/audit-trail/workflow/Inertia contracts, Risk Management matrix schema and seeded-master usage, and Emergency Drill state authorization; restored a fully passing parallel suite and production frontend build.
- Fixed Dashboard monthly incident trend on PostgreSQL by resolving database-specific month expressions while preserving SQLite test compatibility; added regression coverage and verified the authenticated Dashboard in the browser.
- Restored the Legal Register vertical slice: aligned Numbering and Scope service namespaces, integrated Controlled Documents, corrected Inertia/Vite entrypoint loading, generated string register numbers, used shared Core activity logs, restored shared file/comment/activity relations, and added index/create/export/store/show plus permission regression tests.
- Initial documentation structure created.
- Core foundation blueprint created.
- All module specs created.
- Bootstrapped Laravel 12 + Inertia React TypeScript project in `/home/ubuntu/qhsse-app-v3`.
- Added Phase 0 Core/Modules folder structure and route placeholders.
- Added bootstrap handoff for Phase 0 Task 0.1-0.2.
- Added Phase 0 Task 0.3 authentication hardening: inactive users cannot sign in.
- Added Phase 0 Task 0.4 identity core: company, employee, and user admin CRUD baseline.
- Added Phase 0 Task 0.5 RBAC baseline with spatie/laravel-permission, seeded roles/permissions, scope permissions, protected core routes, and user role assignment UI.
- Added Phase 0 Task 0.6 organization master: sites, areas, departments, positions, employee organization links, CRUD pages, permissions, and tests.
- Added Phase 0 Task 0.7 general QHSSE master data: severities, priorities, statuses, categories, risk matrix levels, seeders, CRUD pages, permissions, and tests.
- Added Phase 0 Task 0.8 File Service: managed file metadata, private upload/download, module reference pattern, validation, file permissions, UI, tests, and handoff.
- Added Phase 0 Task 0.9 Numbering Service: configurable formats, atomic counters, generated number ledger, yearly reset, optional site code, baseline seeds, UI, tests, and handoff.
- Added Phase 0 Task 0.10 Workflow Core: reusable definitions, transitions, instances, histories, workflow service, baseline workflows, UI, tests, and handoff.
- Added Phase 0 Task 0.11 Audit Trail: reusable audit logs, audit service, auditable trait, selected core model hooks, workflow transition audit, viewer UI, tests, and handoff.
- Added Phase 0 Task 0.12 Comments and Activity Log: generic comments, mentions extraction, activity timeline, workflow activity integration, UI, tests, and handoff.
- Added Phase 0 Task 0.13 Notification Core: in-app notifications, templates, notification service, read/unread handling, notification center UI, tests, and handoff.
- Added Phase 0 Task 0.14 Search, Filter, Pagination, Export Base: shared list query service, CSV exporter, site/department filtered exports, export permission, UI filter updates, tests, and handoff.
- Added Phase 0 Task 0.15 Dashboard Shell: dashboard controller, KPI cards, chart placeholders, date/site/department filters, role-aware navigation, shared auth permissions, tests, and handoff.
- Completed Phase 0 Task 0.16 Core UAT: final UAT checklist, route/migration/seed verification, full tests/build, Phase 0 final handoff, and seeder cache refresh hardening.
- Initialized git repository; pushed Phase 0 codebase (478 files, 41.498 insertions) ke https://github.com/topiksarip/qhsse-app (branch: main + develop).
- Added GitHub MCP server ke Hermes (26 GitHub tools aktif: push, PR, issue, merge, dll).
- Added GitHub Actions CI workflow: auto-test PHP 8.3 + Node 22 + SQLite in-memory setiap push/PR.
- Added Makefile dan scripts/git-push.sh untuk otomasi developer workflow.
- Completed Phase 6 Document Control: controlled document register, private files, confidential download authorization, review/approval/effective/obsolete workflow, review history, module-aware audit context, comments/activity, notifications, expiry reminders, CSV export, role-aware UI, and feature tests.
- Hardened Phase 6 after independent review: blocked Document Control files from generic core download paths, enforced department/site/own scopes on reads and mutations, aligned draft/submit validation and the 50 MB PPT/PPTX file contract, completed the role matrix and database invariants, added atomic multi-recipient H-30/H-7/H-1 reminders at 08:00, and emitted explicit business audit events.
- Completed Phase 6 review remediation round 2: restored the released Document Control migration baseline, added an upgrade-safe corrective migration for PostgreSQL/SQLite lifecycle constraints and indexes, revalidated date invariants when submitting existing drafts, and closed rejected review cycles with decision `revise`.
- Completed Phase 7 Audit Management: audit register, lead auditor assignment, department scope, planned/in_progress/report_ready/closed workflow, audit findings dengan major/minor/observation/ofi classification, CAPA cross-module linking, finding close tracking, private evidence files, audit report generation, organization scope (all/site/department/own), 9 permissions, role mappings, React pages, seeder, dan vertical slice verification (192 tests/721 assertions, no regressions).
