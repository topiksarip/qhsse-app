# Implementation Plan: Phase 1 — Incident Reporting

## Overview

Build the Incident Reporting module end-to-end as the first QHSSE business module on top of Phase 0 Core Foundation. Users can report incidents (accident, near miss, unsafe act/condition, environmental spill, security breach), attach evidence, and QHSSE team can review/reject/close through workflow.

## Architecture Decisions

- **Table name:** `incidents` (not `02_incident_reporting` — simpler, conventional Laravel naming; module identity via route prefix + permission namespace)
- **Permission namespace:** `incident.reports.*` (matches SOUL/IDEA convention)
- **Numbering format code:** `incident` (prefix `INC`, separator `-`, yearly reset, 5-digit padding → `INC-2026-00001`)
- **Workflow definition:** `incident.report` with states: draft, submitted, under_review, need_more_info, closed, rejected
- **Model location:** `app/Models/Modules/Incident/IncidentReport.php`
- **Controller location:** `app/Http/Controllers/Modules/Incident/IncidentReportController.php`
- **UI location:** `resources/js/Pages/Modules/Incident/`
- **Routes:** registered in `routes/modules.php`
- **Reuse:** NumberingService, WorkflowService, ManagedFileService, AuditService, ActivityService, CommentService, NotificationService, ListQuery, CsvExporter — all from Phase 0

## Task List

### Task 1: Migration + Model + Factory

**Description:** Create `incidents` table migration, Eloquent model with relationships, and factory for testing.

**Acceptance criteria:**
- [ ] Migration creates `incidents` table with all required fields
- [ ] Model has fillable, casts, relationships (site, area, department, reporter, severity, priority)
- [ ] Factory generates valid test data
- [ ] `php artisan migrate` succeeds

**Files:**
- `database/migrations/2026_07_11_000001_create_incidents_table.php`
- `app/Models/Modules/Incident/IncidentReport.php`
- `database/factories/Modules/Incident/IncidentReportFactory.php`

**Schema:**
```
id (bigint PK)
incident_number (string, unique) — generated via NumberingService
title (string)
category (enum: accident, incident, near_miss, unsafe_act, unsafe_condition, environmental_spill, security_breach)
occurred_at (datetime)
site_id (FK → sites)
area_id (FK → sites, nullable)
department_id (FK → departments, nullable)
reporter_id (FK → users)
severity_id (FK → severities)
priority_id (FK → priorities)
description (text)
immediate_action (text, nullable)
status (string, default: draft)
created_at, updated_at

Indexes: incident_number (unique), status, site_id, department_id, occurred_at
```

---

### Task 2: Incident Involved Persons Table

**Description:** Create `incident_involved_persons` pivot table for tracking people involved in an incident.

**Acceptance criteria:**
- [ ] Migration creates pivot table
- [ ] Model relationship `involvedPersons()` defined on IncidentReport
- [ ] `php artisan migrate` succeeds

**Files:**
- `database/migrations/2026_07_11_000002_create_incident_involved_persons_table.php`

**Schema:**
```
id (bigint PK)
incident_id (FK → incidents, cascade delete)
employee_id (FK → employees)
note (string, nullable)
created_at, updated_at
```

---

### Task 3: Permission Constants + Seeder Update

**Description:** Add incident permissions to CorePermissions and update RolesAndPermissionsSeeder.

**Acceptance criteria:**
- [ ] `CorePermissions::all()` includes 7 incident permissions
- [ ] `CorePermissions::roleMap()` assigns permissions to correct roles
- [ ] Seeder runs without error
- [ ] Permission cache cleared after seed

**Permissions:**
```
incident.reports.view
incident.reports.create
incident.reports.update
incident.reports.submit
incident.reports.review
incident.reports.close
incident.reports.export
```

**Role mapping:**
- Super Admin / Admin: all incident permissions
- QHSSE Manager: view, create, update, submit, review, close, export
- QHSSE Officer: view, create, update, submit, review, close, export
- Supervisor: view, create, update, submit
- Employee / Reporter: view, create, submit
- Contractor: view, create, submit
- Department Head: view, create, update, submit
- Auditor / Top Management: view, export

**Files:**
- `app/Core/Permissions/CorePermissions.php` (patch — add incident block)
- `database/seeders/RolesAndPermissionsSeeder.php` (no change needed if it reads from CorePermissions dynamically — verify)

---

### Task 4: Form Request Validation

**Description:** Create StoreIncidentReportRequest and UpdateIncidentReportRequest.

**Acceptance criteria:**
- [ ] Store request validates: title (required, max:255), category (required, enum), occurred_at (required, date), site_id (required, exists), reporter_id (required, exists), severity_id (required, exists), priority_id (required, exists), description (required), immediate_action (nullable, string), area_id (nullable, exists), department_id (nullable, exists)
- [ ] Update request allows same fields but relaxed for draft
- [ ] Submit request validates mandatory fields are filled before transition

**Files:**
- `app/Http/Requests/Modules/Incident/StoreIncidentReportRequest.php`
- `app/Http/Requests/Modules/Incident/UpdateIncidentReportRequest.php`

---

### Task 5: Controller — CRUD + Workflow Actions

**Description:** Build IncidentReportController with index, create, store, show, edit, update, submit, review, close, export.

**Acceptance criteria:**
- [ ] index: uses ListQuery (search title/number, filter status/category/severity/site/date), paginated, Inertia render
- [ ] create: renders form with master data dropdowns (sites, areas, departments, severities, priorities, categories)
- [ ] store: validates, creates record, generates incident_number via NumberingService, starts workflow via WorkflowService, records audit trail, flashes notification
- [ ] show: loads incident with all relationships, evidence files, comments, activity log, workflow history
- [ ] edit: renders edit form (only if status=draft or user has update permission)
- [ ] update: validates, updates, records audit trail for changed fields
- [ ] submit: transitions workflow draft→submitted, records audit, sends notification
- [ ] review: transitions submitted→under_review, records audit, sends notification to reporter
- [ ] close: transitions under_review→closed, records audit, sends notification
- [ ] export: uses CsvExporter with permission gate

**Files:**
- `app/Http/Controllers/Modules/Incident/IncidentReportController.php`

---

### Task 6: Routes

**Description:** Register incident routes in routes/modules.php.

**Acceptance criteria:**
- [ ] All routes behind `auth` + `verified` middleware
- [ ] Permission middleware on each route group
- [ ] Route names follow `incident.reports.*` convention
- [ ] `php artisan route:list` shows new routes

**Routes:**
```php
Route::middleware(['auth', 'verified'])->prefix('incident-reports')->name('incident.reports.')->group(function () {
    Route::get('/', [IncidentReportController::class, 'index'])->name('index')->middleware('permission:incident.reports.view');
    Route::get('/create', [IncidentReportController::class, 'create'])->name('create')->middleware('permission:incident.reports.create');
    Route::post('/', [IncidentReportController::class, 'store'])->name('store')->middleware('permission:incident.reports.create');
    Route::get('/{incidentReport}', [IncidentReportController::class, 'show'])->name('show')->middleware('permission:incident.reports.view');
    Route::get('/{incidentReport}/edit', [IncidentReportController::class, 'edit'])->name('edit')->middleware('permission:incident.reports.update');
    Route::put('/{incidentReport}', [IncidentReportController::class, 'update'])->name('update')->middleware('permission:incident.reports.update');
    Route::post('/{incidentReport}/submit', [IncidentReportController::class, 'submit'])->name('submit')->middleware('permission:incident.reports.submit');
    Route::post('/{incidentReport}/review', [IncidentReportController::class, 'review'])->name('review')->middleware('permission:incident.reports.review');
    Route::post('/{incidentReport}/close', [IncidentReportController::class, 'close'])->name('close')->middleware('permission:incident.reports.close');
    Route::get('/export', [IncidentReportController::class, 'export'])->name('export')->middleware('permission:incident.reports.export');
});
```

**Files:**
- `routes/modules.php` (patch)

---

### Task 7: Numbering Format + Workflow Definition + Notification Templates Seed

**Description:** Seed the `incident` numbering format, `incident.report` workflow definition with transitions, and notification templates.

**Acceptance criteria:**
- [ ] NumberingFormat for `incident` (prefix: INC, separator: -, include_year: true, reset: yearly, padding: 5)
- [ ] WorkflowDefinition for `incident.report` (initial_status: draft)
- [ ] WorkflowTransitions: draft→submitted (submit), submitted→under_review (review), under_review→need_more_info (request_info), need_more_info→submitted (resubmit), under_review→closed (close), submitted→rejected (reject), under_review→rejected (reject)
- [ ] Notification templates: incident.submitted, incident.reviewing, incident.need_info, incident.closed, incident.rejected
- [ ] `php artisan db:seed` runs clean

**Files:**
- `database/seeders/IncidentReportingSeeder.php` (new)
- `database/seeders/DatabaseSeeder.php` (patch — call new seeder)

---

### Task 8: React — Index Page

**Description:** Build the incident list page with table, filters, search, pagination, and action buttons.

**Acceptance criteria:**
- [ ] Table columns: incident_number, title, category badge, severity badge, status badge, occurred_at, reporter
- [ ] Filters: status, category, severity, site, date range
- [ ] Search by number or title
- [ ] "Buat Laporan" button (permission-gated via Inertia shared props)
- [ ] Export CSV button
- [ ] Pagination controls
- [ ] Empty state explains "Belum ada laporan insiden. Buat laporan pertama."
- [ ] Indonesian labels

**Files:**
- `resources/js/Pages/Modules/Incident/Index.tsx`

---

### Task 9: React — Form Page (Create/Edit)

**Description:** Build the incident create/edit form with sectioned layout.

**Acceptance criteria:**
- [ ] Section: Informasi Umum (incident_number auto-generated display, title, category, occurred_at)
- [ ] Section: Lokasi (site, area, department)
- [ ] Section: Klasifikasi (severity, priority)
- [ ] Section: Deskripsi (description, immediate_action)
- [ ] Section: Orang Terlibat (add/remove employees — simple repeater)
- [ ] Section: Evidence (file upload via managed file endpoint)
- [ ] Save as Draft + Submit buttons
- [ ] Validation error display
- [ ] Indonesian labels
- [ ] Mobile responsive (basic)

**Files:**
- `resources/js/Pages/Modules/Incident/Form.tsx`

---

### Task 10: React — Show Page (Detail)

**Description:** Build the incident detail page with summary, status, evidence, comments, activity timeline, and workflow actions.

**Acceptance criteria:**
- [ ] Summary card: number, title, category, status badge, severity, priority, occurred_at
- [ ] Location & reporter info
- [ ] Description + immediate action
- [ ] Involved persons list
- [ ] Evidence files with download links (authorized endpoint)
- [ ] Workflow status timeline (from workflow_histories)
- [ ] Comments section (add comment via shared comment endpoint)
- [ ] Activity log timeline
- [ ] Action buttons: Submit (if draft), Review (if submitted), Close (if under_review) — permission-gated
- [ ] Indonesian labels

**Files:**
- `resources/js/Pages/Modules/Incident/Show.tsx`

---

### Task 11: Navigation Update

**Description:** Add incident reporting menu item to the authenticated layout navigation.

**Acceptance criteria:**
- [ ] "Laporan Insiden" menu item appears under a "Modul QHSSE" group in navigation
- [ ] Visible only to roles with `incident.reports.view` permission
- [ ] Links to `incident.reports.index` route
- [ ] Active state highlight when on incident pages

**Files:**
- `resources/js/Layouts/AuthenticatedLayout.tsx` (patch)
- `resources/js/types/index.d.ts` (patch — add navigation type if needed)

---

### Task 12: Feature Tests

**Description:** Write comprehensive feature tests covering happy path, permission, validation, workflow, and integration.

**Acceptance criteria:**
- [ ] Authorized user can view incident list
- [ ] Unauthorized user gets 403 on list
- [ ] Authorized user can create incident (draft)
- [ ] Incident number auto-generated on create
- [ ] Invalid payload rejected (422)
- [ ] Draft can be submitted (workflow transition)
- [ ] Submitted incident can be reviewed
- [ ] Under review incident can be closed
- [ ] Audit trail records status change
- [ ] Activity log records creation
- [ ] Notification created on submit
- [ ] Incident appears in list after create
- [ ] Export CSV works with permission
- [ ] Export blocked without permission
- [ ] File evidence can be attached
- [ ] Invalid workflow transition rejected
- [ ] `php artisan test` all green

**Files:**
- `tests/Feature/Modules/Incident/IncidentReportTest.php`
- `tests/Feature/Modules/Incident/IncidentReportPermissionTest.php` (optional split)

---

### Checkpoint: After Tasks 1-7 (Backend Complete)

- [ ] Migration runs clean
- [ ] Seeder runs clean
- [ ] `php artisan route:list` shows all incident routes
- [ ] Can create incident via tinker

### Checkpoint: After Tasks 8-11 (Frontend Complete)

- [ ] `npm run build` passes
- [ ] Can navigate to incident list page
- [ ] Can create incident via UI
- [ ] Can view incident detail

### Checkpoint: After Task 12 (Tests + Verification)

- [ ] `php artisan test` — all pass (Phase 0 + Phase 1)
- [ ] `npm run build` — passes
- [ ] `php artisan migrate:status` — all ran
- [ ] `php artisan db:seed` — clean

---

### Task 13: Docs + Handoff

**Description:** Update changelog, decision log, and create Phase 1 handoff.

**Acceptance criteria:**
- [ ] `docs-qhsse/20_CHANGELOG.md` updated with Phase 1 entries
- [ ] `docs-qhsse/19_DECISION_LOG.md` updated with table naming + permission decisions
- [ ] `handoff/PHASE-01-incident-reporting-HANDOFF.md` created using template

**Files:**
- `docs-qhsse/20_CHANGELOG.md` (patch)
- `docs-qhsse/19_DECISION_LOG.md` (patch)
- `handoff/PHASE-01-incident-reporting-HANDOFF.md` (new)

---

## Risks and Mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| CorePermissions seeder doesn't auto-pick new permissions | Medium | Verify seeder reads from `CorePermissions::all()` dynamically; if not, patch seeder |
| Workflow transition action_key mismatch | Medium | Seed transitions with explicit action keys matching controller calls |
| File upload needs existing incident ID | Low | Create incident first, then upload via separate request or multi-step form |
| Route model binding `incidentReport` | Low | Use explicit `{incidentReport}` parameter name in routes |

## Open Questions

- Should draft incidents be visible to all viewers or only the reporter? → **Default: visible to all with `incident.reports.view`** (can refine with scope later)
- Should involved persons be required on submit or optional? → **Default: optional** (can add validation rule later)
- Need More Info loop: does reporter update the same record or create a new one? → **Default: same record, transition need_more_info→submitted after reporter updates**

## Build Order Summary

```text
Task 1-2: DB layer (migration, model, factory, pivot)
Task 3:   Permission layer (CorePermissions + seeder)
Task 4:   Validation layer (form requests)
Task 5:   Controller layer (CRUD + workflow actions)
Task 6:   Routes
Task 7:   Seeds (numbering, workflow, notifications)
  ─── Checkpoint: Backend complete ───
Task 8-10: React pages (Index, Form, Show)
Task 11:   Navigation
  ─── Checkpoint: Frontend complete ───
Task 12:   Feature tests
  ─── Checkpoint: Tests green ───
Task 13:   Docs + handoff
  ─── Phase 1 DONE ───
```
