# Shared Context — QHSSE Module Spec Writing Guide

> Read this file before writing any module spec. It contains all patterns, conventions, and core service APIs that every module must follow.

## Stack

Laravel 12 + Inertia React + TypeScript + Tailwind CSS + Spatie Permission + PostgreSQL + Redis + modular monolith. UI in Indonesian.

## Phase 0 Core Foundation (COMPLETE — must reuse, never duplicate)

### Core Services (in app/Core/)

| Service | Class | Key Methods |
|---|---|---|
| Permissions | `CorePermissions` | `::all()` returns array, `::roleMap()` returns role→permissions |
| Files | `ManagedFileService` | `store(UploadedFile, FileReference, User, metadata)` → `ManagedFile` |
| File Ref | `FileReference` | readonly(moduleName: string, referenceId: int, collection: string) |
| Numbering | `NumberingService` | `generate(moduleName, actor, siteCode, referenceType, referenceId, metadata)` → `GeneratedNumber` |
| Workflow | `WorkflowService` | `start(moduleName, referenceId, actor)`, `transition(moduleName, referenceId, actionKey, actor, reason, metadata)` |
| Audit | `AuditService` | `created(model, actor, moduleName, referenceId)`, `updated(model, oldValues, actor, moduleName, referenceId)`, `log(event, model, oldValues, newValues, actor, moduleName, referenceId)` |
| Activity | `ActivityService` | `log(moduleName, referenceId, event, description, actor, properties)` |
| Comments | `CommentService` | `add(moduleName, referenceId, body, author, parentId, isInternal)` |
| Notification | `NotificationService` | `notify(recipient, type, context, actor, moduleName, referenceId, actionUrl)`, `notifyMany(recipients, type, context, ...)` |
| List Query | `ListQuery` | `paginate(Builder, searchable[], allowedSorts[], defaultSort, perPage)`, `apply(Builder, ...)` |
| CSV Export | `CsvExporter` | `stream(Builder, columns[], filename)` → StreamedResponse |

### Numbering Formats Already Seeded

| Module | Prefix | Sample |
|---|---|---|
| incident | INC | INC-2026-0001 |
| investigation | INV | INV-2026-0001 |
| capa | ACT | ACT-2026-0001 |
| inspection | INS | INS-2026-0001 |
| audit | AUD | AUD-2026-0001 |
| document | DOC | DOC-2026-0001 |
| training | TRN | TRN-2026-0001 |
| permit | PTW | PTW-2026-0001 |
| risk | RSK | RSK-2026-0001 |
| environment | ENV | ENV-2026-0001 |
| security | SEC | SEC-2026-0001 |
| quality | NCR | NCR-2026-0001 |
| legal | LEG | LEG-2026-0001 |
| emergency | EMG | EMG-2026-0001 |
| contractor | CTR | CTR-2026-0001 |
| asset | AST | AST-2026-0001 |
| communication | COM | COM-2026-0001 |

All: padding=4, separator='-', yearly reset, include_year=true, include_site_code=false (except permit=true).

### Workflow Definitions Already Seeded

| Module | Initial Status | States |
|---|---|---|
| incident | draft | draft, submitted, under_review, investigation, action_open, closed, rejected |
| capa | open | open, in_progress, waiting_verification, closed, rejected |
| document | draft | draft, review, approved, effective, obsolete, rejected |

**For modules without seeded workflows**, create a WORKFLOW.md that proposes the workflow definition + transitions to add to WorkflowSeeder.

### CorePermissions Pattern

```php
// In CorePermissions::all()
'incident.reports.view',
'incident.reports.create',
// ...

// In CorePermissions::roleMap()
'Super Admin' => self::all(),
'Admin' => self::all(),
'QHSSE Manager' => [...$viewOnly, 'module.resource.action', ...],
'QHSSE Officer' => [...$viewOnly, 'module.resource.action', ...],
'Supervisor' => ['core.companies.view', ..., 'module.resource.action'],
'Employee / Reporter' => ['core.scope.own', 'module.resource.action'],
'Contractor' => ['core.scope.company', 'module.resource.action'],
'Auditor' => [...$viewOnly, 'core.scope.all'],
'Top Management' => [...$viewOnly, 'core.scope.all'],
```

### Existing Tables (FK targets)

- `sites(id, code, name, address, is_active)`
- `areas(id, site_id FK, code, name, is_active)`
- `departments(id, site_id FK nullable, code, name, is_active)`
- `positions(id, department_id FK nullable, code, name, is_active)`
- `companies(id, code, name, type, is_active)`
- `employees(id, company_id FK, name, email, phone, site_id FK, department_id FK, position_id FK, is_active)`
- `users(id, name, email, password, is_active, company_id FK, employee_id FK)`
- `severities(id, code, name, level, color, is_active)`
- `priorities(id, code, name, level, color, is_active)`
- `statuses(id, code, name, is_active)`
- `categories(id, code, name, is_active)`
- `risk_matrix_levels(id, code, name, severity_level, probability_level, risk_level, is_active)`

### Shared Tables (via module_name + reference_id)

- `managed_files(module_name, reference_id, collection, disk, path, original_name, stored_name, mime_type, extension, size, checksum, metadata, uploaded_by, deleted_at, deleted_by)`
- `comments(module_name, reference_id, parent_id, author_id, body, mentions, is_internal, deleted_at, deleted_by)`
- `activity_logs(module_name, reference_id, event, description, actor_id, actor_name, properties)`
- `audit_logs(event, auditable_type, auditable_id, module_name, reference_id, actor_id, actor_name, ip_address, user_agent, old_values, new_values, metadata)`
- `workflow_instances(workflow_definition_id, module_name, reference_id, current_status, started_by, completed_at)`
- `workflow_histories(workflow_instance_id, module_name, reference_id, from_status, to_status, action_key, action_label, reason, actor_id, metadata)`

## Conventions

### Table Naming
- Use simple Laravel convention: `incidents`, `capa_actions`, `inspections`, `audit_findings`, etc.
- Do NOT prefix with module numbers (no `02_incident_reporting`).
- Pivot tables: `{singular}_{singular}`: `incident_involved_persons`, `inspection_items`.

### Permission Naming
- Format: `module.resource.action` (e.g., `capa.actions.view`, `inspection.checklists.create`)
- 7 standard permissions per resource: view, create, update, submit, review, close, export
- Some modules may need extra: approve, reject, verify, reopen, assign, cancel

### File Structure
```
app/Models/Modules/{ModuleName}/
app/Http/Controllers/Modules/{ModuleName}/
app/Http/Requests/Modules/{ModuleName}/
database/migrations/
database/factories/Modules/{ModuleName}/
database/seeders/
resources/js/Pages/Modules/{ModuleName}/
tests/Feature/Modules/{ModuleName}/
docs-qhsse/modules/{nn}-{slug}/
handoff/PHASE-XX-{slug}-HANDOFF.md
```

### Controller Pattern (from SiteController)
```php
public function index(ListQuery $listQuery): Response {
    $items = $listQuery->paginate(Model::query()->with(['relations']), ['search_fields'], ['sort_fields'], 'default_sort', 15);
    return Inertia::render('Modules/Module/Index', ['items' => $items, 'filters' => $listQuery->filters()]);
}
```

### Test Pattern (Pest PHP)
```php
test('authorized user can view list', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);
    $response = $this->get(route('module.resource.index'));
    $response->assertStatus(200);
});
```

### UI Patterns
- Tailwind CSS, dark mode supported
- Status badges: `bg-{color}-100 text-{color}-800`
- Inertia shared props: `auth.user`, `auth.permissions`, `auth.roles`
- Navigation: `menuGroups` array in `AuthenticatedLayout.tsx`
- All labels in Indonesian

## Each Module Spec Must Have 6 Files

1. **MODULE_SPEC.md** — Tujuan, dependency, roles, fitur, kategori, business rules, permission keys, role-permission matrix, notification events, file rules, dashboard metrics, export spec, acceptance criteria, open questions
2. **DATA_MODEL.md** — Main table schema (all columns with types/constraints/defaults), pivot tables, ERD ASCII diagram, indexes, shared relations, migration naming
3. **UI_PAGES.md** — ASCII wireframes for Index/Form/Show pages, navigation placement, color coding, mobile notes, component list
4. **API_CONTRACT.md** — Route table, request payloads, validation rules, Inertia response props, ListQuery params, CSV export columns, error responses, integration points
5. **TEST_CASES.md** — 20 Pest test cases with code: functional(8), permission(4), integration(5), negative(3), factory definition, helper trait
6. **WORKFLOW.md** — Workflow states, transition table, Phase 1 simplified path, audit trail, controller integration code

## Quality Bar

- Concrete, not generic. Every field has a type. Every route has a permission. Every test has code.
- Match existing codebase patterns exactly.
- Indonesian for business sections, English for technical sections.
- Each file 200-600 lines. Total per module: ~2000-3500 lines.
