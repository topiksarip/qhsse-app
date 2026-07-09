# Task List: QHSSE Phase 0 Core Foundation

## Task 0.1: Bootstrap Laravel/Inertia Project

**Description:** Create or normalize the application skeleton using Laravel 12, Inertia React, TypeScript, PostgreSQL, Redis-ready config, and Tailwind CSS.

**Acceptance criteria:**
- [ ] Laravel app runs locally.
- [ ] Inertia React page renders.
- [ ] PostgreSQL connection works.
- [ ] `.env.example` exists.

**Verification:**
- [ ] `php artisan --version`
- [ ] `php artisan migrate:status`
- [ ] `npm run build`

**Dependencies:** None

**Estimated scope:** Medium

## Task 0.2: Establish Project Rules and Modular Structure

**Description:** Add persistent project instructions and create the modular folder structure for Core and Modules.

**Acceptance criteria:**
- [ ] `AGENTS.md` exists.
- [ ] `app/Core` exists.
- [ ] `app/Modules` exists.
- [ ] Route/module convention documented.

**Verification:**
- [ ] Inspect file structure.
- [ ] Confirm docs reference active phase and handoff protocol.

**Dependencies:** Task 0.1

**Estimated scope:** Small

## Task 0.3: Authentication

**Description:** Implement login, logout, password reset/change baseline using Laravel auth conventions.

**Acceptance criteria:**
- [ ] User can login.
- [ ] User can logout.
- [ ] Inactive user cannot login.
- [ ] Password reset/change exists or documented if deferred.

**Verification:**
- [ ] Auth tests pass.
- [ ] Manual login/logout works.

**Dependencies:** Task 0.1

**Estimated scope:** Medium

## Task 0.4: User, Employee, Company Core

**Description:** Build admin-managed user, employee, company, and contractor records.

**Acceptance criteria:**
- [ ] Admin can CRUD users.
- [ ] Admin can CRUD employees.
- [ ] Admin can CRUD companies/contractors.
- [ ] User can link to employee/company.
- [ ] Active/inactive state works.

**Verification:**
- [ ] Create internal user.
- [ ] Create contractor user.
- [ ] Inactivate user and verify login blocked.

**Dependencies:** Task 0.3

**Estimated scope:** Medium

## Task 0.5: Role, Permission, and Scope

**Description:** Implement RBAC using standard roles, permissions, and data scopes.

**Acceptance criteria:**
- [ ] Standard roles seeded.
- [ ] Permission keys seeded.
- [ ] Role-permission assignment works.
- [ ] User-role assignment works.
- [ ] Scope values available: own, department, site, company, all.

**Verification:**
- [ ] User without permission cannot access protected action.
- [ ] Contractor cannot see other company data in test/manual fixture.

**Dependencies:** Task 0.4

**Estimated scope:** Medium

## Task 0.6: Organization Master

**Description:** Implement site, area, department, and position master data.

**Acceptance criteria:**
- [ ] Site CRUD works.
- [ ] Area CRUD works and belongs to site.
- [ ] Department CRUD works.
- [ ] Position CRUD works.
- [ ] Used records are inactivated, not hard deleted.

**Verification:**
- [ ] Create/edit/inactivate each master.
- [ ] Search/filter/pagination works.

**Dependencies:** Task 0.5

**Estimated scope:** Medium

## Task 0.7: General QHSSE Master Data

**Description:** Implement severity, priority, status, category, and risk matrix master data.

**Acceptance criteria:**
- [ ] Severity master exists.
- [ ] Priority master exists.
- [ ] Status master exists per module.
- [ ] Category master exists per module.
- [ ] Risk matrix baseline exists.

**Verification:**
- [ ] Seed data available for Incident phase.
- [ ] Inactive behavior works.

**Dependencies:** Task 0.6

**Estimated scope:** Medium

## Task 0.8: File Service

**Description:** Implement secure upload/download core using private storage and module reference pattern.

**Acceptance criteria:**
- [ ] Upload file with metadata.
- [ ] Download through authorized endpoint.
- [ ] Reference uses module_name/reference_id.
- [ ] Extension/MIME/size validation works.
- [ ] Delete follows permission.

**Verification:**
- [ ] Authorized download succeeds.
- [ ] Unauthorized download fails.
- [ ] Invalid file rejected.

**Dependencies:** Task 0.5

**Estimated scope:** Medium

## Task 0.9: Numbering Service

**Description:** Implement atomic generated numbering per module/year/site optional.

**Acceptance criteria:**
- [ ] Prefix per module supported.
- [ ] Year reset supported.
- [ ] Optional site code supported.
- [ ] Unique constraint exists.
- [ ] Service can generate sample numbers.

**Verification:**
- [ ] Generate multiple numbers without duplicates.
- [ ] Confirm transaction/locking strategy.

**Dependencies:** Task 0.7

**Estimated scope:** Small

## Task 0.10: Workflow Core

**Description:** Implement reusable status transition validation and workflow history.

**Acceptance criteria:**
- [ ] Transition validation works.
- [ ] Actor permission check exists.
- [ ] Reject/cancel reason required.
- [ ] Workflow history created.

**Verification:**
- [ ] Invalid transition rejected.
- [ ] Valid transition recorded.

**Dependencies:** Task 0.5, Task 0.7

**Estimated scope:** Medium

## Task 0.11: Audit Trail

**Description:** Implement audit logging for critical create/update/delete and workflow events.

**Acceptance criteria:**
- [ ] Core model changes audited.
- [ ] Workflow actions audited.
- [ ] Permission/master changes audited.
- [ ] Admin audit viewer/list exists.

**Verification:**
- [ ] Change permission and inspect audit trail.
- [ ] Update master data and inspect old/new value.

**Dependencies:** Task 0.5, Task 0.10

**Estimated scope:** Medium

## Task 0.12: Comments and Activity Log

**Description:** Implement generic comment and activity timeline by module reference.

**Acceptance criteria:**
- [ ] Add comment to module/reference.
- [ ] View comments.
- [ ] Activity log records system events.
- [ ] Mention is implemented or explicitly deferred.

**Verification:**
- [ ] Comment appears on dummy/core reference.
- [ ] Activity timeline shows status event.

**Dependencies:** Task 0.11

**Estimated scope:** Medium

## Task 0.13: Notification Core

**Description:** Implement notification center, mark-as-read, event interface, and email-ready baseline.

**Acceptance criteria:**
- [ ] In-app notification model exists.
- [ ] Notification center UI exists.
- [ ] Mark as read works.
- [ ] Event trigger interface exists.
- [ ] Email channel configured or safely stubbed in local.

**Verification:**
- [ ] Trigger test notification.
- [ ] User sees notification.
- [ ] Mark as read works.

**Dependencies:** Task 0.5

**Estimated scope:** Medium

## Task 0.14: Search, Filter, Pagination, Export Base

**Description:** Standardize list query patterns and export baseline for core admin lists.

**Acceptance criteria:**
- [ ] Search/filter/pagination pattern exists.
- [ ] Core admin lists use the pattern.
- [ ] CSV/Excel export for at least one list.
- [ ] Export respects permission.

**Verification:**
- [ ] Export filtered site/department list.
- [ ] Unauthorized export blocked.

**Dependencies:** Task 0.6, Task 0.7

**Estimated scope:** Medium

## Task 0.15: Dashboard Shell

**Description:** Build main dashboard shell and reusable KPI/chart placeholder components.

**Acceptance criteria:**
- [ ] Main dashboard route exists.
- [ ] KPI card component exists.
- [ ] Chart placeholder exists.
- [ ] Date/site/department filter UI exists.
- [ ] Role-aware menu shell exists.

**Verification:**
- [ ] Login lands on dashboard.
- [ ] Filters render.
- [ ] Sidebar/menu role visibility works.

**Dependencies:** Task 0.5, Task 0.6

**Estimated scope:** Medium

## Task 0.16: Core UAT, Documentation, and Handoff

**Description:** Verify Phase 0, update documentation, and create handoff.

**Acceptance criteria:**
- [ ] Core test checklist completed.
- [ ] `handoff/PHASE-00-core-foundation-HANDOFF.md` created.
- [ ] Decision Log updated if decisions changed.
- [ ] Changelog updated.
- [ ] Known issues recorded.

**Verification:**
- [ ] `php artisan test`
- [ ] `npm run build`
- [ ] Manual smoke test: login, admin CRUD, permission block, file upload, notification.

**Dependencies:** Tasks 0.1-0.15

**Estimated scope:** Small
