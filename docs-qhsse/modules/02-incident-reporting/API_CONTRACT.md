# API Contract — Incident Reporting

> Spec ini mendefinisikan semua route, request, response, dan error handling untuk modul Incident Reporting.

## 1. Route Table

Semua route diawali dengan prefix `/incident-reports`, nama route `incident.reports.*`, dan middleware `auth,verified`.

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/incident-reports` | `index` | `incident.reports.index` | `incident.reports.view` | List incidents with search/filter/pagination |
| GET | `/incident-reports/create` | `create` | `incident.reports.create` | `incident.reports.create` | Render create form |
| POST | `/incident-reports` | `store` | `incident.reports.store` | `incident.reports.create` | Save new incident (draft or submit) |
| GET | `/incident-reports/{incidentReport}` | `show` | `incident.reports.show` | `incident.reports.view` | Show incident detail |
| GET | `/incident-reports/{incidentReport}/edit` | `edit` | `incident.reports.edit` | `incident.reports.update` | Render edit form |
| PUT | `/incident-reports/{incidentReport}` | `update` | `incident.reports.update` | `incident.reports.update` | Update incident |
| POST | `/incident-reports/{incidentReport}/submit` | `submit` | `incident.reports.submit` | `incident.reports.submit` | Transition draft → submitted |
| POST | `/incident-reports/{incidentReport}/review` | `review` | `incident.reports.review` | `incident.reports.review` | Transition submitted → under_review |
| POST | `/incident-reports/{incidentReport}/close` | `close` | `incident.reports.close` | `incident.reports.close` | Transition under_review → closed |
| GET | `/incident-reports/export` | `export` | `incident.reports.export` | `incident.reports.export` | Export filtered list as CSV |

### Route Registration

File: `routes/modules.php`

```php
use App\Http\Controllers\Modules\Incident\IncidentReportController;

Route::middleware(['auth', 'verified'])
    ->prefix('incident-reports')
    ->name('incident.reports.')
    ->group(function (): void {
        Route::get('/', [IncidentReportController::class, 'index'])
            ->name('index')
            ->middleware('permission:incident.reports.view');

        Route::get('/create', [IncidentReportController::class, 'create'])
            ->name('create')
            ->middleware('permission:incident.reports.create');

        Route::post('/', [IncidentReportController::class, 'store'])
            ->name('store')
            ->middleware('permission:incident.reports.create');

        Route::get('/{incidentReport}', [IncidentReportController::class, 'show'])
            ->name('show')
            ->middleware('permission:incident.reports.view');

        Route::get('/{incidentReport}/edit', [IncidentReportController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:incident.reports.update');

        Route::put('/{incidentReport}', [IncidentReportController::class, 'update'])
            ->name('update')
            ->middleware('permission:incident.reports.update');

        Route::post('/{incidentReport}/submit', [IncidentReportController::class, 'submit'])
            ->name('submit')
            ->middleware('permission:incident.reports.submit');

        Route::post('/{incidentReport}/review', [IncidentReportController::class, 'review'])
            ->name('review')
            ->middleware('permission:incident.reports.review');

        Route::post('/{incidentReport}/close', [IncidentReportController::class, 'close'])
            ->name('close')
            ->middleware('permission:incident.reports.close');

        Route::get('/export', [IncidentReportController::class, 'export'])
            ->name('export')
            ->middleware('permission:incident.reports.export');
    });
```

### Route Model Binding

- Parameter name: `{incidentReport}` → Laravel resolves to `IncidentReport` model via route key (id).
- Custom key: default `id` (no need for `getRouteKeyName()` override).

---

## 2. Request Payloads

### POST `/incident-reports` (store)

```json
{
  "title": "Kecelakaan kerja di area produksi",
  "category": "accident",
  "occurred_at": "2026-07-11T14:30:00",
  "site_id": 1,
  "area_id": 2,
  "department_id": 3,
  "severity_id": 1,
  "priority_id": 2,
  "description": "Pekerja terpeleset di lantai basah dan jatuh.",
  "immediate_action": "Pertolongan pertama diberikan, area dipasang barrier.",
  "involved_persons": [
    { "employee_id": 5, "note": "Korban" },
    { "employee_id": 8, "note": "Saksi mata" }
  ],
  "action": "draft"
}
```

**Validation Rules (StoreIncidentReportRequest):**

| Field | Rule | Notes |
|---|---|---|
| `title` | `required|string|max:255` | |
| `category` | `required|in:accident,incident,near_miss,unsafe_act,unsafe_condition,environmental_spill,security_breach` | |
| `occurred_at` | `required|date` | |
| `site_id` | `required|exists:sites,id` | |
| `area_id` | `nullable|exists:areas,id` | |
| `department_id` | `nullable|exists:departments,id` | |
| `severity_id` | `required|exists:severities,id` | |
| `priority_id` | `required|exists:priorities,id` | |
| `description` | `required|string` | |
| `immediate_action` | `nullable|string` | |
| `involved_persons` | `nullable|array` | |
| `involved_persons.*.employee_id` | `required_with:involved_persons|exists:employees,id` | |
| `involved_persons.*.note` | `nullable|string|max:255` | |
| `action` | `nullable|in:draft,submit` | If `submit`, validates mandatory fields AND triggers workflow transition |

**Controller behavior (store):**
1. Validate request
2. Create `IncidentReport` with reporter_id = auth user
3. Generate `incident_number` via `NumberingService::generate('incident', $actor)`
4. Start workflow via `WorkflowService::start('incident', $incident->id, $actor)`
5. If `action === 'submit'`: call `WorkflowService::transition('incident', $incident->id, 'submit', $actor)`
6. Attach `involved_persons` if provided
7. `AuditService::created($incident, $actor, 'incident', $incident->id)`
8. `ActivityService::log('incident', $incident->id, 'incident.created', 'Incident report created', $actor)`
9. If submitted: `NotificationService::notifyMany($qhsseTeamUsers, 'incident.submitted', [...])`
10. Redirect to `incident.reports.show`

### PUT `/incident-reports/{incidentReport}` (update)

Same payload as store, but:
- `title`, `category`, `occurred_at`, `site_id`, `severity_id`, `priority_id`, `description` are **sometimes** (not required for draft)
- Only allowed if `status === 'draft'` (or `need_more_info` if that feature is added)
- Records audit trail for changed fields via `AuditService::updated()`

### POST `/incident-reports/{incidentReport}/submit` (submit)

No request body needed. Controller:
1. Check `incident.status === 'draft'`
2. `WorkflowService::transition('incident', $incident->id, 'submit', $actor)`
3. `ActivityService::log('incident', $incident->id, 'incident.submitted', ...)`
4. `NotificationService::notifyMany($qhsseTeamUsers, 'incident.submitted', [...])`
5. Redirect back with flash message

### POST `/incident-reports/{incidentReport}/review` (review)

No request body needed. Controller:
1. Check `incident.status === 'submitted'`
2. `WorkflowService::transition('incident', $incident->id, 'review', $actor)`
3. `NotificationService::notify($reporter, 'incident.reviewing', [...])`
4. Redirect back

### POST `/incident-reports/{incidentReport}/close` (close)

```json
{
  "reason": "Investigasi selesai, corrective action telah diimplementasi."
}
```

| Field | Rule |
|---|---|
| `reason` | `required|string|max:1000` |

Controller:
1. Check `incident.status === 'under_review'` (or `investigation` / `action_open`)
2. `WorkflowService::transition('incident', $incident->id, 'close', $actor, $reason)`
3. `NotificationService::notify($reporter, 'incident.closed', [...])`
4. Redirect back

---

## 3. Inertia Response Props

### Index Page (`Incident/Index.tsx`)

```typescript
{
  items: {
    data: IncidentReport[],
    current_page: number,
    last_page: number,
    per_page: number,
    total: number,
  },
  filters: {
    search: string,
    status: string | null,
    category: string | null,
    severity_id: number | null,
    site_id: number | null,
  },
}
```

### Create/Edit Page (`Incident/Form.tsx`)

```typescript
{
  item: IncidentReport | null,  // null for create, populated for edit
  sites: Site[],
  areas: Area[],
  departments: Department[],
  severities: Severity[],
  priorities: Priority[],
  categories: Category[],  // QHSSE categories for classification
  employees: Employee[],    // for involved persons dropdown
}
```

### Show Page (`Incident/Show.tsx`)

```typescript
{
  incident: IncidentReport & {
    site: Site,
    area: Area | null,
    department: Department | null,
    reporter: User,
    severity: Severity,
    priority: Priority,
    involved_persons: (Employee & { pivot: { note: string } })[],
  },
  evidence: ManagedFile[],
  comments: Comment[],
  activities: ActivityLog[],
  workflowHistory: WorkflowHistory[],
  availableTransitions: {
    action_key: string,
    action_label: string,
    requires_reason: boolean,
  }[],
}
```

---

## 4. ListQuery Parameters

The index page accepts these query parameters for filtering:

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `incident_number` and `title` (OR) |
| `status` | string | `null` | Filter by exact status: draft, submitted, under_review, closed, rejected |
| `category` | string | `null` | Filter by exact category |
| `severity_id` | int | `null` | Filter by severity |
| `site_id` | int | `null` | Filter by site |
| `per_page` | int | `15` | Items per page (max 100) |
| `sort` | string | `occurred_at` | Sort column |
| `direction` | string | `desc` | Sort direction (asc/desc) |

### Controller index method pattern:

```php
public function index(ListQuery $listQuery): Response
{
    $items = $listQuery->paginate(
        IncidentReport::query()->with(['site', 'severity', 'reporter']),
        ['incident_number', 'title'],
        ['occurred_at', 'created_at', 'incident_number'],
        'occurred_at',
        15,
    );

    return Inertia::render('Modules/Incident/Index', [
        'items' => $items,
        'filters' => $listQuery->filters(),
    ]);
}
```

---

## 5. CSV Export Specification

Endpoint: `GET /incident-reports/export?search=...&status=...`

Applies same ListQuery filters, then streams CSV via `CsvExporter`.

### CSV Columns:

| Column Header | Source |
|---|---|
| `Nomor` | `incident_number` |
| `Judul` | `title` |
| `Kategori` | `category` |
| `Severity` | `severity.name` |
| `Priority` | `priority.name` |
| `Status` | `status` |
| `Tanggal Kejadian` | `occurred_at` |
| `Reporter` | `reporter.name` |
| `Site` | `site.name` |

### Controller export method pattern:

```php
public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
{
    $query = $listQuery->apply(
        IncidentReport::query()->with(['site', 'severity', 'priority', 'reporter']),
        ['incident_number', 'title'],
        ['occurred_at', 'created_at'],
        'occurred_at',
    );

    return $exporter->stream($query, [
        'Nomor' => 'incident_number',
        'Judul' => 'title',
        'Kategori' => 'category',
        'Severity' => fn ($item) => $item->severity?->name ?? '',
        'Priority' => fn ($item) => $item->priority?->name ?? '',
        'Status' => 'status',
        'Tanggal Kejadian' => fn ($item) => $item->occurred_at?->format('Y-m-d H:i') ?? '',
        'Reporter' => fn ($item) => $item->reporter?->name ?? '',
        'Site' => fn ($item) => $item->site?->name ?? '',
    ], 'incidents-export.csv');
}
```

---

## 6. Error Responses

| Status | When | Response |
|---|---|---|
| `403` | User lacks required permission | Inertia redirects to dashboard with error flash (middleware handles) |
| `404` | Incident ID not found | Laravel default 404 page |
| `422` | Validation failure | Inertia sends errors back to form with `$page.props.errors` |
| `400` | Invalid workflow transition | RuntimeException caught → redirect back with error flash |
| `419` | CSRF token expired | Laravel default |

### Invalid workflow transition handling:

```php
try {
    $this->workflowService->transition('incident', $incident->id, $actionKey, $actor, $reason);
} catch (RuntimeException $e) {
    return back()->withErrors(['workflow' => $e->getMessage()]);
}
```

---

## 7. Permission Enforcement Points

| Layer | How |
|---|---|
| **Route middleware** | `->middleware('permission:incident.reports.view')` on each route |
| **Controller authorize()** | `$this->authorize('view', $incident)` for show/edit (optional, for scope filtering) |
| **Inertia shared props** | `auth.permissions` array → frontend checks via `permissions.has('incident.reports.create')` |
| **Export** | Route middleware `permission:incident.reports.export` |

---

## 8. Numbering Integration

On `store`:

```php
$generatedNumber = app(NumberingService::class)->generate(
    moduleName: 'incident',
    actor: $actor,
    referenceType: IncidentReport::class,
    referenceId: $incident->id,
);

$incident->update(['incident_number' => $generatedNumber->number]);
```

Numbering format (already seeded): `INC-2026-0001`

---

## 9. Workflow Integration

The workflow definition `incident` is already seeded in `WorkflowSeeder` with:

- Initial status: `draft`
- Terminal statuses: `closed`, `rejected`

### Transitions used by this module:

| Action | Controller Method | From | To | requires_reason |
|---|---|---|---|---|
| `submit` | `submit()` | draft | submitted | false |
| `review` | `review()` | submitted | under_review | false |
| `close` | `close()` | action_open | closed | **true** |
| `reject` | (future) | submitted/under_review | rejected | **true** |

> Note: The seeded workflow also has `investigate` and `open_action` transitions. For Phase 1 simplicity, we use `submit`, `review`, and `close`. The `close` action goes from `action_open → closed`. For Phase 1, we may need to add a direct `under_review → closed` transition OR transition through `action_open` first.

### Phase 1 simplified workflow path:

```
draft →(submit)→ submitted →(review)→ under_review →(close)→ closed
```

> If `close` only works from `action_open`, add a transition `under_review → closed` with action_key `close` to the seeder, OR transition `under_review → action_open` first then `action_open → closed`. Decision: **add a direct `under_review → closed` transition** for Phase 1 simplicity.

---

## 10. File Upload Integration

Evidence files are uploaded via the existing core `ManagedFileController` routes (not module-specific).

### Upload flow:

1. User creates incident → gets `incident.id`
2. User uploads file via `POST /core/files` with:
   - `module_name`: `incident`
   - `reference_id`: `$incident->id`
   - `collection`: `evidence`
   - `file`: the UploadedFile
3. `ManagedFileService::store($file, new FileReference('incident', $incident->id, 'evidence'), $uploader)`
4. File stored on `local` disk at `managed-files/incident/{id}/evidence/{uuid}.{ext}`
5. Download via `GET /core/files/{managedFile}/download` (permission-gated by `core.files.download` + module reference check)

### Show page loads evidence:

```php
'evidence' => ManagedFile::query()
    ->where('module_name', 'incident')
    ->where('reference_id', $incident->id)
    ->where('collection', 'evidence')
    ->whereNull('deleted_at')
    ->get(),
```
