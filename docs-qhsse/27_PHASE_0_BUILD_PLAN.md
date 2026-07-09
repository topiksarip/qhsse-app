# Phase 0 Build Plan — Core Foundation Super Complete

## 1. Objective

Membangun Core Foundation super lengkap untuk menopang semua modul QHSSE secara bertahap tanpa rework besar.

Phase 0 harus menghasilkan platform dasar yang bisa dipakai oleh:

- Dashboard & KPI
- Incident Reporting
- Investigation & RCA
- CAPA
- Inspection
- Audit
- Document Control
- Training
- Permit to Work
- Risk Management
- Environmental Management
- Security Management
- Quality Management
- Legal Compliance
- Emergency Preparedness
- Contractor Management
- Asset Equipment Safety
- Communication Campaign
- Reporting Export
- Admin Master Data

## 2. Required Context

Sebelum mulai Phase 0, agent wajib membaca:

1. `docs-qhsse/23_EXECUTION_PLAN.md`
2. `docs-qhsse/24_HANDOFF_PROTOCOL.md`
3. `docs-qhsse/26_TECH_STACK_DECISION.md`
4. `docs-qhsse/22_FOUNDATION_SUPER_SPEC.md`
5. `docs-qhsse/21_BLUEPRINT.md`
6. `docs-qhsse/05_ROLE_PERMISSION_MATRIX.md`
7. `docs-qhsse/06_MASTER_DATA_SPEC.md`
8. `docs-qhsse/07_WORKFLOW_SPEC.md`
9. `docs-qhsse/08_DATA_MODEL_ERD.md`
10. `docs-qhsse/09_API_SPEC.md`
11. `docs-qhsse/modules/00-core-foundation/MODULE_SPEC.md`
12. `docs-qhsse/modules/00-core-foundation/DATA_MODEL.md`
13. `docs-qhsse/modules/00-core-foundation/UI_PAGES.md`
14. `docs-qhsse/modules/00-core-foundation/TEST_CASES.md`

## 3. Architecture Decisions for Phase 0

- Use modular monolith.
- Build core services before business modules.
- Use Laravel backend patterns: Form Request, Policy/Gate or permission middleware, Service classes where useful.
- Use Inertia React for pages.
- Use PostgreSQL migrations with explicit indexes.
- Use Redis-ready queue/notification setup, but keep sync fallback possible.
- Use custom audit trail if faster than package; keep schema extensible.
- Use generic file reference pattern: `module_name + reference_id`.
- Use generic comment/activity pattern: `module_name + reference_id`.
- Use generic workflow history pattern: `module_name + reference_id`.
- Use `spatie/laravel-permission` for RBAC.

## 4. Dependency Graph

```text
Project Bootstrap
  -> Auth
    -> Users/Employees/Companies
      -> Roles/Permissions/Scopes
        -> Organization Master
          -> General Master Data
            -> File Service
            -> Numbering Service
            -> Workflow Core
            -> Audit Trail
            -> Comments/Activity
            -> Notification Core
              -> Export Base
                -> Dashboard Shell
                  -> Core UAT + Handoff
```

## 5. Vertical Slice Strategy

Walaupun pondasi besar, implementasi harus tetap vertical slice:

1. Auth slice: login/logout usable.
2. Admin user slice: admin can create user.
3. Permission slice: role blocks/allows action.
4. Master slice: site/department/area CRUD usable.
5. File slice: upload/download against dummy reference.
6. Numbering slice: generate number for dummy module.
7. Workflow slice: transition dummy record.
8. Audit slice: log actual user/master changes.
9. Comment/activity slice: comment on dummy/core record.
10. Notification slice: send in-app/email test notification.
11. Export slice: export one master list.
12. Dashboard shell slice: display placeholder + filter controls.

## 6. Detailed Build Tasks

### Task 0.1 — Bootstrap Laravel/Inertia Project

Description: Create or normalize the application skeleton using the selected stack.

Acceptance:

- Laravel app runs locally.
- Inertia React page renders.
- PostgreSQL connection works.
- `.env.example` exists.
- Basic commands documented.

Verification:

- `php artisan --version`
- `php artisan migrate:status`
- `npm run build`

### Task 0.2 — Establish Project Rules and Structure

Description: Add project-wide AI/developer rules and modular folder structure.

Acceptance:

- `AGENTS.md` exists.
- `app/Core` and `app/Modules` exist.
- Route organization plan exists.
- Naming conventions documented.

Verification:

- File structure exists.
- Future agent can read `AGENTS.md` and know constraints.

### Task 0.3 — Authentication

Description: Implement login, logout, password reset/change baseline.

Acceptance:

- User can login/logout.
- Inactive user cannot login.
- Password reset/change path exists or documented if deferred by starter kit.
- Auth pages render in Inertia.

Verification:

- Auth tests pass.
- Manual login/logout works.

### Task 0.4 — User, Employee, Company Core

Description: Implement core identity and organization person/company records.

Acceptance:

- User CRUD by admin.
- Employee CRUD.
- Company/contractor CRUD.
- User links to employee/company.
- Active/inactive state works.

Verification:

- Admin can create internal user.
- Admin can create contractor user.
- Inactive user blocked.

### Task 0.5 — Role, Permission, and Scope

Description: Implement RBAC and data scope rules.

Acceptance:

- Standard roles seeded.
- Permission keys seeded.
- Role-permission assignment works.
- User-role assignment works.
- Scope concepts available: own, department, site, company, all.

Verification:

- User without permission cannot access protected page/action.
- Contractor cannot see other company data in test fixture.

### Task 0.6 — Organization Master

Description: Implement site, area, department, position master.

Acceptance:

- CRUD pages and endpoints exist.
- Area belongs to site.
- Department can relate to site optionally.
- Used records are inactivated, not hard deleted.

Verification:

- Create/edit/inactivate master data.
- Search/filter/pagination works.

### Task 0.7 — General Master Data

Description: Implement severity, priority, status, category, risk matrix.

Acceptance:

- Core QHSSE master data seeded.
- Risk matrix configurable baseline exists.
- Status per module can be seeded.
- Category per module supported.

Verification:

- Master data available for Incident phase.
- Inactive behavior works.

### Task 0.8 — File Service

Description: Implement secure file upload/download core.

Acceptance:

- Upload file with metadata.
- Download through authorized endpoint.
- Reference uses module_name/reference_id.
- Extension/MIME/size validation.
- Delete follows permission.

Verification:

- Authorized user downloads file.
- Unauthorized user blocked.
- Invalid extension/oversize rejected.

### Task 0.9 — Numbering Service

Description: Implement atomic generated number service.

Acceptance:

- Prefix per module.
- Year reset.
- Optional site code support.
- Unique constraint prevents duplicates.
- Service can generate `INC-YYYY-0001` style numbers.

Verification:

- Generate multiple numbers without duplicates.
- Test concurrency if feasible or enforce transaction/locking.

### Task 0.10 — Workflow Core

Description: Implement reusable status transition and workflow history.

Acceptance:

- Transition validation.
- Actor permission check.
- Reject/cancel reason required.
- Workflow history created.

Verification:

- Invalid transition rejected.
- Valid transition recorded.

### Task 0.11 — Audit Trail

Description: Implement audit logging for critical changes.

Acceptance:

- Create/update/delete audited for selected core models.
- Workflow actions audited.
- Permission/master changes audited.
- Audit viewer/list available for admin.

Verification:

- Change role permission and see audit trail.
- Update master data and see old/new value.

### Task 0.12 — Comments and Activity Log

Description: Implement generic comment and system activity timeline.

Acceptance:

- Add comment to module/reference.
- View comments in detail component.
- Activity log records system events.
- Mention stored or explicitly deferred.

Verification:

- Comment appears on dummy reference.
- Activity timeline shows workflow/status event.

### Task 0.13 — Notification Core

Description: Implement notification center and email-ready event interface.

Acceptance:

- In-app notification model and UI.
- Mark as read.
- Event trigger interface.
- Email channel configured or safely stubbed in local.
- Notification template baseline.

Verification:

- Trigger test notification to user.
- User sees notification.
- Mark as read works.

### Task 0.14 — Search, Filter, Pagination, Export Base

Description: Standardize list pages and export baseline.

Acceptance:

- Shared query/filter pattern.
- Search/filter/pagination on core admin lists.
- CSV/Excel export for at least one master list.
- Export respects permission.

Verification:

- Export filtered department/site list.
- Unauthorized export blocked.

### Task 0.15 — Dashboard Shell

Description: Build dashboard shell and reusable widget components.

Acceptance:

- Main dashboard route exists.
- KPI card component.
- Chart placeholder component.
- Date/site/department filter UI.
- Role-aware menu shell.

Verification:

- Login lands on dashboard.
- Filters render.
- Sidebar/menu role visibility works.

### Task 0.16 — Core UAT, Documentation, and Handoff

Description: Verify Phase 0, update docs, create handoff.

Acceptance:

- Core test checklist completed.
- `handoff/PHASE-00-core-foundation-HANDOFF.md` created.
- `docs-qhsse/19_DECISION_LOG.md` updated if decisions changed.
- `docs-qhsse/20_CHANGELOG.md` updated.
- Known issues recorded.

Verification:

- `php artisan test`
- `npm run build`
- Manual smoke test: login, admin CRUD, permission block, file upload, notification.

## 7. Phase 0 Acceptance Criteria

Phase 0 is done only if:

1. Admin can login and manage users.
2. Role/permission protects backend and UI actions.
3. Site/area/department/company/employee master data works.
4. General master data required for Incident exists.
5. File upload/download is secure.
6. Numbering service generates unique numbers.
7. Workflow transition history works.
8. Audit trail captures critical changes.
9. Comments/activity can attach to module/reference.
10. Notification center works.
11. Export base works.
12. Dashboard shell works.
13. Tests/build pass or known failures are documented.
14. Handoff exists.

## 8. Stop Conditions

Do not continue to Incident phase if:

- Auth is unstable.
- Permission is UI-only and not server-side.
- File download can bypass permission.
- Numbering can duplicate.
- Audit trail not working for permission/master changes.
- Core master data incomplete for Incident.
- No handoff exists.

## 9. Parallelization

Safe to parallelize after project bootstrap:

- UI shell components.
- Documentation updates.
- Seed data drafting.
- Test case drafting.

Must be sequential:

- Database migrations affecting shared models.
- RBAC schema and middleware.
- File service security pattern.
- Numbering service.

## 10. Risks and Mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Over-engineering workflow | High | Status-based workflow only in Phase 0 |
| Permission gaps | High | Server-side permission tests |
| File access leak | High | Private storage + authorized download |
| Too many modules at once | High | Phase 0 only core, no business module UI except placeholders |
| Dashboard premature complexity | Medium | Shell only, metrics added by modules |
| Dependency bloat | Medium | Decision Log for each new dependency |
| Incomplete handoff | Medium | Use `24_HANDOFF_PROTOCOL.md` template |

## 11. Open Questions with Defaults

| Question | Default |
|---|---|
| Hosting target | Local Docker-ready, VPS later |
| SSO | Later |
| WhatsApp/Telegram | Later |
| PDF template | Basic first, company template later |
| Field-level permission | Later for sensitive data |
| Offline mode | Later |
| Native mobile | Later |

## 12. Prompt to Start Phase 0

```text
Mulai Phase 0 — Core Foundation Super Complete.

Wajib baca:
- docs-qhsse/23_EXECUTION_PLAN.md
- docs-qhsse/24_HANDOFF_PROTOCOL.md
- docs-qhsse/26_TECH_STACK_DECISION.md
- docs-qhsse/27_PHASE_0_BUILD_PLAN.md
- docs-qhsse/22_FOUNDATION_SUPER_SPEC.md
- docs-qhsse/21_BLUEPRINT.md
- docs-qhsse/modules/00-core-foundation/MODULE_SPEC.md
- tasks/plan.md
- tasks/todo.md

Gunakan skill set otomatis:
- context-engineering
- source-driven-development
- incremental-implementation
- test-driven-development
- api-and-interface-design
- frontend-ui-engineering
- security-and-hardening
- code-review-and-quality
- documentation-and-adrs

Kerjakan hanya Phase 0. Jangan membuat modul Incident dulu.
Setelah selesai, buat handoff di `handoff/PHASE-00-core-foundation-HANDOFF.md`.
```
