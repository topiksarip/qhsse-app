# API Contract — Audit Management

> Spec ini mendefinisikan semua route, request, response, dan error handling untuk modul Audit Management.

## 1. Route Table

Semua route diawali dengan prefix `/audits`, nama route `audits.*`, dan middleware `auth,verified`.

### 1.1 Audit Routes

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/audits` | `AuditController@index` | `audits.index` | `audit.management.view` | List audits with search/filter/pagination |
| GET | `/audits/create` | `AuditController@create` | `audits.create` | `audit.management.create` | Render create form |
| POST | `/audits` | `AuditController@store` | `audits.store` | `audit.management.create` | Save new audit |
| GET | `/audits/{audit}` | `AuditController@show` | `audits.show` | `audit.management.view` | Show audit detail |
| GET | `/audits/{audit}/edit` | `AuditController@edit` | `audits.edit` | `audit.management.update` | Render edit form |
| PUT | `/audits/{audit}` | `AuditController@update` | `audits.update` | `audit.management.update` | Update audit |
| POST | `/audits/{audit}/start` | `AuditController@start` | `audits.start` | `audit.management.execute` | Transition planned → in_progress |
| POST | `/audits/{audit}/generate-report` | `AuditController@generateReport` | `audits.generateReport` | `audit.management.execute` | Transition in_progress → report_ready (requires summary) |
| POST | `/audits/{audit}/close` | `AuditController@close` | `audits.close` | `audit.management.close` | Transition report_ready → closed |
| GET | `/audits/export` | `AuditController@export` | `audits.export` | `audit.management.export` | Export filtered list as CSV |

### 1.2 Audit Finding Routes

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| POST | `/audits/{audit}/findings` | `AuditFindingController@store` | `audits.findings.store` | `audit.findings.create` | Create new finding |
| PUT | `/audits/{audit}/findings/{finding}` | `AuditFindingController@update` | `audits.findings.update` | `audit.findings.update` | Update finding |
| POST | `/audits/{audit}/findings/{finding}/close` | `AuditFindingController@close` | `audits.findings.close` | `audit.findings.close` | Close finding |
| POST | `/audits/{audit}/findings/{finding}/link-capa` | `AuditFindingController@linkCapa` | `audits.findings.linkCapa` | `audit.findings.update` | Link existing CAPA to finding |
| DELETE | `/audits/{audit}/findings/{finding}/unlink-capa` | `AuditFindingController@unlinkCapa` | `audits.findings.unlinkCapa` | `audit.findings.update` | Unlink CAPA from finding |

### Route Registration

File: `routes/modules.php`

```php
use App\Http\Controllers\Modules\Audit\AuditController;
use App\Http\Controllers\Modules\Audit\AuditFindingController;

Route::middleware(['auth', 'verified'])
    ->prefix('audits')
    ->name('audits.')
    ->group(function (): void {
        // Audit CRUD
        Route::get('/', [AuditController::class, 'index'])
            ->name('index')
            ->middleware('permission:audit.management.view');

        Route::get('/create', [AuditController::class, 'create'])
            ->name('create')
            ->middleware('permission:audit.management.create');

        Route::post('/', [AuditController::class, 'store'])
            ->name('store')
            ->middleware('permission:audit.management.create');

        Route::get('/{audit}', [AuditController::class, 'show'])
            ->name('show')
            ->middleware('permission:audit.management.view');

        Route::get('/{audit}/edit', [AuditController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:audit.management.update');

        Route::put('/{audit}', [AuditController::class, 'update'])
            ->name('update')
            ->middleware('permission:audit.management.update');

        // Workflow transitions
        Route::post('/{audit}/start', [AuditController::class, 'start'])
            ->name('start')
            ->middleware('permission:audit.management.execute');

        Route::post('/{audit}/generate-report', [AuditController::class, 'generateReport'])
            ->name('generateReport')
            ->middleware('permission:audit.management.execute');

        Route::post('/{audit}/close', [AuditController::class, 'close'])
            ->name('close')
            ->middleware('permission:audit.management.close');

        // Export
        Route::get('/export', [AuditController::class, 'export'])
            ->name('export')
            ->middleware('permission:audit.management.export');

        // Findings (nested under audit)
        Route::post('/{audit}/findings', [AuditFindingController::class, 'store'])
            ->name('findings.store')
            ->middleware('permission:audit.findings.create');

        Route::put('/{audit}/findings/{finding}', [AuditFindingController::class, 'update'])
            ->name('findings.update')
            ->middleware('permission:audit.findings.update');

        Route::post('/{audit}/findings/{finding}/close', [AuditFindingController::class, 'close'])
            ->name('findings.close')
            ->middleware('permission:audit.findings.close');

        Route::post('/{audit}/findings/{finding}/link-capa', [AuditFindingController::class, 'linkCapa'])
            ->name('findings.linkCapa')
            ->middleware('permission:audit.findings.update');

        Route::delete('/{audit}/findings/{finding}/unlink-capa', [AuditFindingController::class, 'unlinkCapa'])
            ->name('findings.unlinkCapa')
            ->middleware('permission:audit.findings.update');
    });
```

### Route Model Binding

- Audit parameter: `{audit}` → Laravel resolves to `Audit` model via route key (id).
- Finding parameter: `{finding}` → Laravel resolves to `AuditFinding` model. Scoped to audit: `where('audit_id', $audit->id)`.

---

## 2. Request Payloads

### POST `/audits` (store)

```json
{
  "title": "Audit Internal QHSSE Q3 2026",
  "type": "internal",
  "standard": "ISO 45001:2018",
  "scope": "Audit mencakup sistem manajemen K3 di area produksi Plant A.",
  "site_id": 1,
  "department_id": 3,
  "lead_auditor_id": 5,
  "start_date": "2026-07-15",
  "end_date": "2026-07-17"
}
```

**Validation Rules (StoreAuditRequest):**

| Field | Rule | Notes |
|---|---|---|
| `title` | `required\|string\|max:255` | |
| `type` | `required\|in:internal,external,supplier` | |
| `standard` | `nullable\|string\|max:100` | |
| `scope` | `required\|string\|min:10` | |
| `site_id` | `required\|exists:sites,id` | |
| `department_id` | `nullable\|exists:departments,id` | |
| `lead_auditor_id` | `required\|exists:users,id` | |
| `start_date` | `required\|date` | |
| `end_date` | `nullable\|date\|after_or_equal:start_date` | |

**Controller behavior (store):**
1. Validate request
2. Create `Audit` with status `planned`
3. Generate `audit_number` via `NumberingService::generate('audit', $actor, ...)`
4. Start workflow via `WorkflowService::start('audit', $audit->id, $actor)`
5. `AuditService::created($audit, $actor, 'audit', $audit->id)`
6. `ActivityService::log('audit', $audit->id, 'audit.created', 'Audit created', $actor)`
7. Redirect to `audits.show`

### PUT `/audits/{audit}` (update)

Same payload as store. Only allowed if `status === 'planned'`.

```json
{
  "title": "Audit Internal QHSSE Q3 2026 (Updated)",
  "type": "internal",
  "standard": "ISO 45001:2018",
  "scope": "Updated scope description...",
  "site_id": 1,
  "department_id": 3,
  "lead_auditor_id": 5,
  "start_date": "2026-07-15",
  "end_date": "2026-07-18"
}
```

**Validation Rules (UpdateAuditRequest):** Same as store.

**Controller behavior (update):**
1. Check `audit.status === 'planned'` (abort 403 if not)
2. Validate request
3. Record old values
4. Update audit
5. `AuditService::updated($audit, $oldValues, $actor, 'audit', $audit->id)`
6. Redirect to `audits.show`

### POST `/audits/{audit}/start` (start)

No request body needed. Controller:
1. Check `audit.status === 'planned'`
2. `WorkflowService::transition('audit', $audit->id, 'start', $actor)`
3. `ActivityService::log('audit', $audit->id, 'audit.started', 'Audit started', $actor)`
4. `NotificationService::notifyMany($auditeeUsers, 'audit.started', [...])`
5. Redirect back

### POST `/audits/{audit}/generate-report` (generateReport)

```json
{
  "summary": "Audit telah dilaksanakan selama 3 hari. Ditemukan 3 temuan: 1 Major (LOTO), 1 Minor (APD), 1 OFI (digitalisasi checklist). Sistem manajemen K3 pada dasarnya efektif namun perlu perbaikan di area prosedur LOTO."
}
```

**Validation Rules (GenerateAuditReportRequest):**

| Field | Rule | Notes |
|---|---|---|
| `summary` | `required\|string\|min:20` | Required for report generation |

**Controller behavior (generateReport):**
1. Check `audit.status === 'in_progress'`
2. Validate request
3. Update `audit.summary` with provided summary
4. `WorkflowService::transition('audit', $audit->id, 'generate_report', $actor)`
5. `ActivityService::log('audit', $audit->id, 'audit.report_generated', 'Audit report generated', $actor)`
6. `NotificationService::notifyMany($qhsseManagers, 'audit.report_ready', [...])`
7. Redirect back

### POST `/audits/{audit}/close` (close)

No request body needed. Controller:
1. Check `audit.status === 'report_ready'`
2. Check all findings are `closed` (abort 422 if any open)
3. Check all Major findings have `capa_action_id` (abort 422 if any Major finding without CAPA)
4. `WorkflowService::transition('audit', $audit->id, 'close', $actor)`
5. `ActivityService::log('audit', $audit->id, 'audit.closed', 'Audit closed', $actor)`
6. `NotificationService::notifyMany($stakeholders, 'audit.closed', [...])`
7. Redirect back

### POST `/audits/{audit}/findings` (store finding)

```json
{
  "description": "Prosedur lockout/tagout tidak diimplementasikan di area mesin produksi.",
  "classification": "major",
  "area": "Produksi — Mesin CNC Line 2",
  "recommendation": "Implementasi prosedur LOTO untuk semua mesin produksi."
}
```

**Validation Rules (StoreAuditFindingRequest):**

| Field | Rule | Notes |
|---|---|---|
| `description` | `required\|string\|min:10` | |
| `classification` | `required\|in:major,minor,observation,ofi` | |
| `area` | `nullable\|string\|max:255` | |
| `recommendation` | `nullable\|string` | |

**Controller behavior (store finding):**
1. Check `audit.status` is `in_progress` or `report_ready`
2. Validate request
3. Generate `finding_number` (format: `{audit.audit_number}-F{NN}`, sequence per audit)
4. Create `AuditFinding` with `audit_id` and `status='open'`
5. `AuditService::created($finding, $actor, 'audit', $finding->id)`
6. `ActivityService::log('audit', $audit->id, 'audit.finding.created', "Finding {$finding->finding_number} created", $actor)`
7. `NotificationService::notifyMany($auditeeUsers, 'audit.finding.created', [...])`
8. Redirect back to audit show page (findings tab)

### PUT `/audits/{audit}/findings/{finding}` (update finding)

```json
{
  "description": "Updated description of the finding.",
  "classification": "minor",
  "area": "Produksi — Assembly Line",
  "recommendation": "Updated recommendation."
}
```

Same validation as store. Only allowed if `finding.status === 'open'` and `audit.status` is `in_progress` or `report_ready`.

### POST `/audits/{audit}/findings/{finding}/close` (close finding)

No request body needed. Controller:
1. Check `finding.status === 'open'`
2. If `finding.classification === 'major'`, check `finding.capa_action_id` is not null (abort 422 if null)
3. Update `finding.status = 'closed'`
4. `AuditService::updated($finding, $oldValues, $actor, 'audit', $finding->id)`
5. `ActivityService::log('audit', $audit->id, 'audit.finding.closed', "Finding {$finding->finding_number} closed", $actor)`
6. Redirect back

### POST `/audits/{audit}/findings/{finding}/link-capa` (link existing CAPA)

```json
{
  "capa_action_id": 12
}
```

**Validation Rules:**

| Field | Rule | Notes |
|---|---|---|
| `capa_action_id` | `required\|exists:capa_actions,id` | Must exist and belong to same site scope |

**Controller behavior:**
1. Validate request
2. Update `finding.capa_action_id`
3. `AuditService::updated($finding, $oldValues, $actor, 'audit', $finding->id)`
4. `ActivityService::log('audit', $audit->id, 'audit.finding.capa_linked', "CAPA linked to finding {$finding->finding_number}", $actor)`
5. Redirect back

### DELETE `/audits/{audit}/findings/{finding}/unlink-capa` (unlink CAPA)

No request body. Controller:
1. Set `finding.capa_action_id = null`
2. `AuditService::updated($finding, $oldValues, $actor, 'audit', $finding->id)`
3. `ActivityService::log('audit', $audit->id, 'audit.finding.capa_unlinked', ...)`
4. Redirect back

---

## 3. Inertia Response Props

### Index Page (`Audit/Index.tsx`)

```typescript
{
  audits: {
    data: Audit[],
    current_page: number,
    last_page: number,
    per_page: number,
    total: number,
  },
  filters: {
    search: string,
    status: string | null,
    type: string | null,
    site_id: number | null,
    from: string | null,
    to: string | null,
  },
  sites: Site[],
  can: {
    create: boolean,
    export: boolean,
  },
}
```

### Create/Edit Page (`Audit/Form.tsx`)

```typescript
{
  audit: Audit | null,  // null for create, populated for edit
  sites: Site[],
  departments: Department[],
  auditors: User[],     // users eligible to be lead auditor
  can: {
    update: boolean,
  },
}
```

### Show Page (`Audit/Show.tsx`)

```typescript
{
  audit: Audit & {
    site: Site,
    department: Department | null,
    lead_auditor: User,
    findings: (AuditFinding & {
      capa_action: CapaAction | null,
    })[],
    evidence_files: ManagedFile[],
    comments: Comment[],
    activities: ActivityLog[],
    workflow_history: WorkflowHistory[],
  },
  can: {
    update: boolean,
    execute: boolean,
    close: boolean,
    export: boolean,
    create_finding: boolean,
    update_finding: boolean,
    close_finding: boolean,
  },
  availableTransitions: {
    action_key: string,
    action_label: string,
    requires_reason: boolean,
    requires_summary: boolean,
  }[],
}
```

---

## 4. ListQuery Parameters

The index page accepts these query parameters for filtering:

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `audit_number`, `title`, and `lead_auditor.name` |
| `status` | string | `null` | Filter by: planned, in_progress, report_ready, closed |
| `type` | string | `null` | Filter by: internal, external, supplier |
| `site_id` | int | `null` | Filter by site |
| `from` | string | `null` | Start date filter (start_date >= from) |
| `to` | string | `null` | End date filter (start_date <= to) |
| `per_page` | int | `15` | Items per page (max 100) |
| `sort` | string | `start_date` | Sort column |
| `direction` | string | `desc` | Sort direction (asc/desc) |

### Controller index method pattern:

```php
public function index(ListQuery $listQuery): Response
{
    $items = $listQuery->paginate(
        Audit::query()->with(['site', 'leadAuditor', 'department']),
        ['audit_number', 'title'],
        ['start_date', 'created_at', 'audit_number'],
        'start_date',
        15,
    );

    return Inertia::render('Modules/Audit/Index', [
        'audits' => $items,
        'filters' => $listQuery->filters(),
        'sites' => Site::where('is_active', true)->get(['id', 'name']),
        'can' => [
            'create' => auth()->user()->can('audit.management.create'),
            'export' => auth()->user()->can('audit.management.export'),
        ],
    ]);
}
```

---

## 5. CSV Export Specification

Endpoint: `GET /audits/export?search=...&status=...&type=...`

Applies same ListQuery filters, then streams CSV via `CsvExporter`.

### CSV Columns:

| Column Header | Source |
|---|---|
| `Nomor Audit` | `audit_number` |
| `Judul` | `title` |
| `Tipe` | `type` |
| `Standar` | `standard` |
| `Site` | `site.name` |
| `Department` | `department.name` |
| `Lead Auditor` | `lead_auditor.name` |
| `Tanggal Mulai` | `start_date` |
| `Tanggal Selesai` | `end_date` |
| `Status` | `status` |
| `Total Findings` | count of findings |
| `Major Findings` | count(major) |
| `Minor Findings` | count(minor) |
| `Observation Findings` | count(observation) |
| `OFI Findings` | count(ofi) |
| `Findings Linked to CAPA` | count(capa_action_id not null) |
| `Summary` | `summary` (truncated 500 chars) |
| `Created At` | `created_at` |
| `Closed At` | from workflow_histories |

### Controller export method pattern:

```php
public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
{
    $query = $listQuery->apply(
        Audit::query()->with(['site', 'department', 'leadAuditor', 'findings']),
        ['audit_number', 'title'],
        ['start_date', 'created_at'],
        'start_date',
    );

    return $exporter->stream($query, [
        'Nomor Audit' => 'audit_number',
        'Judul' => 'title',
        'Tipe' => 'type',
        'Standar' => fn ($item) => $item->standard ?? '',
        'Site' => fn ($item) => $item->site?->name ?? '',
        'Department' => fn ($item) => $item->department?->name ?? '',
        'Lead Auditor' => fn ($item) => $item->leadAuditor?->name ?? '',
        'Tanggal Mulai' => fn ($item) => $item->start_date?->format('Y-m-d') ?? '',
        'Tanggal Selesai' => fn ($item) => $item->end_date?->format('Y-m-d') ?? '',
        'Status' => 'status',
        'Total Findings' => fn ($item) => $item->findings->count(),
        'Major Findings' => fn ($item) => $item->findings->where('classification', 'major')->count(),
        'Minor Findings' => fn ($item) => $item->findings->where('classification', 'minor')->count(),
        'Observation Findings' => fn ($item) => $item->findings->where('classification', 'observation')->count(),
        'OFI Findings' => fn ($item) => $item->findings->where('classification', 'ofi')->count(),
        'Findings Linked to CAPA' => fn ($item) => $item->findings->whereNotNull('capa_action_id')->count(),
        'Summary' => fn ($item) => Str::limit($item->summary ?? '', 500),
        'Created At' => fn ($item) => $item->created_at?->format('Y-m-d H:i:s') ?? '',
    ], 'audits-export.csv');
}
```

---

## 6. Error Responses

| Status | When | Response |
|---|---|---|
| `403` | User lacks required permission | Inertia redirects to dashboard with error flash |
| `404` | Audit or Finding ID not found | Laravel default 404 page |
| `422` | Validation failure | Inertia sends errors back to form with `$page.props.errors` |
| `422` | Invalid workflow transition (e.g., start non-planned audit) | JSON error with message |
| `422` | Close audit with open findings | JSON error listing open findings |
| `422` | Close Major finding without CAPA link | JSON error: "Major finding requires CAPA link before closing" |
| `419` | CSRF token expired | Laravel default |

### Invalid workflow transition handling:

```php
try {
    $this->workflowService->transition('audit', $audit->id, 'start', $actor);
} catch (RuntimeException $e) {
    return back()->withErrors(['workflow' => $e->getMessage()]);
}
```

### Close audit validation error:

```php
if ($audit->findings()->where('status', 'open')->exists()) {
    return back()->withErrors([
        'close' => 'Tidak dapat menutup audit. Masih ada temuan yang berstatus Open.'
    ]);
}

$majorWithoutCapa = $audit->findings()
    ->where('classification', 'major')
    ->whereNull('capa_action_id')
    ->exists();

if ($majorWithoutCapa) {
    return back()->withErrors([
        'close' => 'Tidak dapat menutup audit. Masih ada temuan Major yang belum terhubung ke CAPA.'
    ]);
}
```

---

## 7. Permission Enforcement Points

| Layer | How |
|---|---|
| **Route middleware** | `->middleware('permission:audit.management.view')` on each route |
| **Controller authorize()** | `$this->authorize('view', $audit)` for show/edit (scope filtering) |
| **Inertia shared props** | `auth.permissions` array → frontend checks via `permissions.has('audit.management.create')` |
| **Finding operations** | Route middleware `permission:audit.findings.create` etc. |
| **Export** | Route middleware `permission:audit.management.export` |

---

## 8. Numbering Integration

### Audit Number

On `store`:

```php
$generatedNumber = app(NumberingService::class)->generate(
    moduleName: 'audit',
    actor: $actor,
    referenceType: Audit::class,
    referenceId: $audit->id,
);

$audit->update(['audit_number' => $generatedNumber->number]);
```

Numbering format (already seeded): `AUD-2026-0001`

### Finding Number

On `store finding`:

```php
$count = AuditFinding::where('audit_id', $audit->id)->count();
$findingNumber = $audit->audit_number . '-F' . str_pad((string)($count + 1), 2, '0', STR_PAD_LEFT);

// e.g., AUD-2026-0001-F01
```

---

## 9. Workflow Integration

The workflow definition `audit` needs to be seeded (new for Phase 6).

### Workflow Definition to Seed

| Property | Value |
|---|---|
| `module_name` | `audit` |
| `code` | `AUDIT_WORKFLOW` |
| `name` | `Audit Workflow` |
| `initial_status` | `planned` |
| `is_active` | `true` |

### Transitions

| Action | Controller Method | From | To | requires_reason | Required Permission |
|---|---|---|---|---|---|
| `start` | `start()` | planned | in_progress | ❌ | `audit.management.execute` |
| `generate_report` | `generateReport()` | in_progress | report_ready | ❌ (but requires `summary` field) | `audit.management.execute` |
| `close` | `close()` | report_ready | closed | ❌ (but requires all findings resolved) | `audit.management.close` |

### Phase 6 workflow path:

```
planned ──(start)──→ in_progress ──(generate_report)──→ report_ready ──(close)──→ closed
```

### Terminal status:

- `closed` is terminal (per `WorkflowService::isTerminalStatus()`)
- No reopen in Phase 6

---

## 10. File Upload Integration

Evidence files are uploaded via the existing core `ManagedFileController` routes.

### Upload flow:

1. User creates audit → gets `audit.id`
2. User uploads file via `POST /core/files` with:
   - `module_name`: `audit`
   - `reference_id`: `$audit->id`
   - `collection`: `evidence`
   - `file`: the UploadedFile
3. `ManagedFileService::store($file, new FileReference('audit', $audit->id, 'evidence'), $uploader)`
4. File stored on `local` disk at `managed-files/audit/{id}/evidence/{uuid}.{ext}`
5. Download via `GET /core/files/{managedFile}/download`

### Show page loads evidence:

```php
'evidence_files' => ManagedFile::query()
    ->where('module_name', 'audit')
    ->where('reference_id', $audit->id)
    ->whereNull('deleted_at')
    ->get(),
```

---

## 11. CAPA Cross-Module Integration

### Create CAPA from Finding

When user clicks "Create CAPA" on a finding:

1. Frontend redirects to: `GET /capa-actions/create?source_module=audit&source_reference_id={finding.id}&title={finding.description (truncated)}`
2. CAPA form pre-fills `source_module='audit'` and `source_reference_id={finding.id}`
3. On CAPA store, CAPA controller:
   a. Creates CAPA record
   b. Updates `audit_findings.capa_action_id` with new CAPA ID
   c. Logs activity: `audit.finding.capa_linked`
4. User redirected back to audit show page

### Link Existing CAPA

When user clicks "Link CAPA" on a finding:

1. Modal opens with CAPA search/select
2. User selects existing CAPA
3. `POST /audits/{audit}/findings/{finding}/link-capa` with `capa_action_id`
4. Finding updated with `capa_action_id`
5. Activity logged: `audit.finding.capa_linked`

### View Linked CAPA

When finding has `capa_action_id`:

- Finding card displays: `CAPA: [ACT-2026-0005] ✓ Terhubung [👁 Lihat CAPA]`
- "Lihat CAPA" links to: `GET /capa-actions/{capa_action_id}`
- Permission: `audit.findings.view` (view finding) + `capa.actions.view` (view CAPA)

### Unlink CAPA

- `DELETE /audits/{audit}/findings/{finding}/unlink-capa`
- Sets `finding.capa_action_id = null`
- Only allowed if finding status is `open` and audit status is not `closed`
