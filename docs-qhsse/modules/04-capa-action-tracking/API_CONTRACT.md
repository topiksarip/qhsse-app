# API Contract — CAPA / Corrective & Preventive Action Tracking

> Spec ini mendefinisikan semua route, request, response, dan error handling untuk modul CAPA.

## 1. Route Table

Semua route diawali dengan prefix `/capa-actions`, nama route `capa.actions.*`, dan middleware `auth,verified`.

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/capa-actions` | `index` | `capa.actions.index` | `capa.actions.view` | List CAPA actions with search/filter/pagination |
| GET | `/capa-actions/create` | `create` | `capa.actions.create` | `capa.actions.create` | Render create form |
| POST | `/capa-actions` | `store` | `capa.actions.store` | `capa.actions.create` | Save new CAPA action |
| GET | `/capa-actions/{capaAction}` | `show` | `capa.actions.show` | `capa.actions.view` | Show CAPA detail |
| GET | `/capa-actions/{capaAction}/edit` | `edit` | `capa.actions.edit` | `capa.actions.update` | Render edit form |
| PUT | `/capa-actions/{capaAction}` | `update` | `capa.actions.update` | `capa.actions.update` | Update CAPA action |
| POST | `/capa-actions/{capaAction}/start` | `start` | `capa.actions.start` | `capa.actions.update` | Transition open → in_progress |
| POST | `/capa-actions/{capaAction}/submit` | `submit` | `capa.actions.submit` | `capa.actions.submit` | Transition in_progress → waiting_verification |
| POST | `/capa-actions/{capaAction}/verify` | `verifyClose` | `capa.actions.verify` | `capa.actions.verify` + `capa.actions.close` | Transition waiting_verification → closed |
| POST | `/capa-actions/{capaAction}/reject` | `reject` | `capa.actions.reject` | `capa.actions.reject` | Transition waiting_verification → rejected |
| POST | `/capa-actions/{capaAction}/restart` | `restart` | `capa.actions.restart` | `capa.actions.update` | Transition rejected → in_progress |
| GET | `/capa-actions/export` | `export` | `capa.actions.export` | `capa.actions.export` | Export filtered list as CSV |

### Route Registration

File: `routes/modules.php`

```php
use App\Http\Controllers\Modules\Capa\CapaActionController;

Route::middleware(['auth', 'verified'])
    ->prefix('capa-actions')
    ->name('capa.actions.')
    ->group(function (): void {
        Route::get('/', [CapaActionController::class, 'index'])
            ->name('index')
            ->middleware('permission:capa.actions.view');

        Route::get('/create', [CapaActionController::class, 'create'])
            ->name('create')
            ->middleware('permission:capa.actions.create');

        Route::post('/', [CapaActionController::class, 'store'])
            ->name('store')
            ->middleware('permission:capa.actions.create');

        Route::get('/{capaAction}', [CapaActionController::class, 'show'])
            ->name('show')
            ->middleware('permission:capa.actions.view');

        Route::get('/{capaAction}/edit', [CapaActionController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:capa.actions.update');

        Route::put('/{capaAction}', [CapaActionController::class, 'update'])
            ->name('update')
            ->middleware('permission:capa.actions.update');

        Route::post('/{capaAction}/start', [CapaActionController::class, 'start'])
            ->name('start')
            ->middleware('permission:capa.actions.update');

        Route::post('/{capaAction}/submit', [CapaActionController::class, 'submit'])
            ->name('submit')
            ->middleware('permission:capa.actions.submit');

        Route::post('/{capaAction}/verify', [CapaActionController::class, 'verifyClose'])
            ->name('verify')
            ->middleware('permission:capa.actions.verify,capa.actions.close');

        Route::post('/{capaAction}/reject', [CapaActionController::class, 'reject'])
            ->name('reject')
            ->middleware('permission:capa.actions.reject');

        Route::post('/{capaAction}/restart', [CapaActionController::class, 'restart'])
            ->name('restart')
            ->middleware('permission:capa.actions.update');

        Route::get('/export', [CapaActionController::class, 'export'])
            ->name('export')
            ->middleware('permission:capa.actions.export');
    });
```

### Route Model Binding

- Parameter name: `{capaAction}` → Laravel resolves to `CapaAction` model via route key (id).
- Custom key: default `id` (no need for `getRouteKeyName()` override).

---

## 2. Request Payloads

### POST `/capa-actions` (store)

```json
{
  "title": "Perbaikan pipa bocor di area produksi",
  "description": "Pipa bocor di area produksi menyebabkan tumpahan minyak. Perlu diganti segmen pipa sepanjang 5 meter dan inspeksi ulang area sekitarnya.",
  "source_module": "incident",
  "source_reference_id": 1,
  "source_type": "corrective",
  "site_id": 1,
  "department_id": 3,
  "assigned_to": 5,
  "due_date": "2026-07-20",
  "severity_id": 4,
  "priority_id": 4
}
```

**Validation Rules (StoreCapaActionRequest):**

| Field | Rule | Notes |
|---|---|---|
| `title` | `required\|string\|max:255` | |
| `description` | `required\|string\|min:20` | |
| `source_module` | `required\|in:incident,inspection,audit,manual` | |
| `source_reference_id` | `nullable\|integer\|min:1` | Required if source_module != 'manual'. Must be NULL if source_module='manual'. |
| `source_type` | `nullable\|in:corrective,preventive` | |
| `site_id` | `required\|exists:sites,id` | |
| `department_id` | `nullable\|exists:departments,id` | |
| `assigned_to` | `required\|exists:users,id` | PIC |
| `due_date` | `required\|date\|after_or_equal:today` | |
| `severity_id` | `nullable\|exists:severities,id` | |
| `priority_id` | `required\|exists:priorities,id` | |

**Custom Validation (after method):**

```php
public function after(): array
{
    return [
        function (Validator $validator) {
            if ($this->source_module !== 'manual' && !$this->source_reference_id) {
                $validator->errors()->add(
                    'source_reference_id',
                    'Referensi sumber wajib diisi untuk sumber non-manual.'
                );
            }
            if ($this->source_module === 'manual' && $this->source_reference_id) {
                $validator->errors()->add(
                    'source_reference_id',
                    'Referensi sumber harus kosong untuk sumber manual.'
                );
            }
        },
    ];
}
```

**Controller behavior (store):**

1. Validate request
2. Create `CapaAction` with `assigned_by` = auth user, `status` = 'open'
3. Generate `action_number` via `NumberingService::generate('capa', $actor, ...)`
4. Start workflow via `WorkflowService::start('capa', $action->id, $actor)`
5. `AuditService::created($action, $actor, 'capa', $action->id)`
6. `ActivityService::log('capa', $action->id, 'capa.created', 'Tindakan CAPA dibuat', $actor)`
7. `NotificationService::notify($assignedTo, 'capa.assigned', [...])`
8. Redirect to `capa.actions.show`

### PUT `/capa-actions/{capaAction}` (update)

Same payload as store, but:

- Only allowed if `status` in `['open', 'in_progress', 'rejected']`
- `action_number` cannot be changed (ignored if present)
- `assigned_by` cannot be changed
- If `assigned_to` changed, reset `assigned_at` to now() and send notification
- Records audit trail for changed fields via `AuditService::updated()`

### POST `/capa-actions/{capaAction}/start` (start)

No request body needed. Controller:

1. Check `action.status === 'open'`
2. `WorkflowService::transition('capa', $action->id, 'start', $actor)`
3. Set `assigned_at` = now() if not already set
4. `ActivityService::log('capa', $action->id, 'capa.started', 'Tindakan dimulai', $actor)`
5. Redirect back

### POST `/capa-actions/{capaAction}/submit` (submit)

No request body needed. Controller:

1. Check `action.status === 'in_progress'`
2. **Check evidence exists**: count `managed_files` where `module_name='capa'` AND `reference_id=$action->id` AND `collection='evidence'` AND `deleted_at IS NULL` must be >= 1. If 0, return back with error.
3. `WorkflowService::transition('capa', $action->id, 'submit_verification', $actor)`
4. `ActivityService::log('capa', $action->id, 'capa.submitted_verification', 'Tindakan di-submit untuk verifikasi', $actor)`
5. `NotificationService::notifyMany($qhsseUsers, 'capa.submitted_verification', [...])`
6. Redirect back

### POST `/capa-actions/{capaAction}/verify` (verifyClose)

```json
{
  "verification_note": "Pipa sudah diganti dan area dibersihkan. Inspeksi ulang selesai dengan hasil baik."
}
```

| Field | Rule |
|---|---|
| `verification_note` | `required\|string\|min:10` |

Controller:

1. Check `action.status === 'waiting_verification'`
2. Check user has both `capa.actions.verify` AND `capa.actions.close` permissions
3. `WorkflowService::transition('capa', $action->id, 'verify_close', $actor, $verificationNote)`
4. Set `verification_note`, `verified_by` = auth user, `verified_at` = now(), `closed_at` = now()
5. `ActivityService::log('capa', $action->id, 'capa.verified_closed', 'Tindakan diverifikasi & ditutup', $actor)`
6. `NotificationService::notify($assignedTo, 'capa.verified_closed', [...])`
7. Redirect back

### POST `/capa-actions/{capaAction}/reject` (reject)

```json
{
  "reason": "Bukti foto tidak jelas, mohon unggah ulang dengan resolusi lebih tinggi."
}
```

| Field | Rule |
|---|---|
| `reason` | `required\|string\|min:10` |

Controller:

1. Check `action.status === 'waiting_verification'`
2. `WorkflowService::transition('capa', $action->id, 'reject', $actor, $reason)`
3. `ActivityService::log('capa', $action->id, 'capa.rejected', 'Tindakan ditolak', $actor, ['reason' => $reason])`
4. `NotificationService::notify($assignedTo, 'capa.rejected', [...])`
5. Redirect back

### POST `/capa-actions/{capaAction}/restart` (restart)

No request body needed. Controller:

1. Check `action.status === 'rejected'`
2. `WorkflowService::transition('capa', $action->id, 'restart', $actor)`
3. `ActivityService::log('capa', $action->id, 'capa.restarted', 'Tindakan di-restart', $actor)`
4. `NotificationService::notifyMany($qhsseUsers, 'capa.restarted', [...])`
5. Redirect back

---

## 3. Inertia Response Props

### Index Page (`Capa/Index.tsx`)

```typescript
{
  items: {
    data: CapaActionListItem[],
    current_page: number,
    last_page: number,
    per_page: number,
    total: number,
    from: number | null,
    to: number | null,
  },
  filters: {
    search: string,
    status: string | null,
    source_module: string | null,
    priority_id: number | null,
    site_id: number | null,
    department_id: number | null,
    overdue: boolean,
    from: string | null,
    to: string | null,
  },
  sites: Site[],
  departments: Department[],
  priorities: Priority[],
  can: {
    create: boolean,
    export: boolean,
  },
}
```

### Create/Edit Page (`Capa/Form.tsx`)

```typescript
{
  item: CapaAction | null,
  sites: Site[],
  departments: Department[],
  users: User[],
  priorities: Priority[],
  severities: Severity[],
  sourceRecords: {
    incident: { id: number; number: string; title: string }[],
    inspection: { id: number; number: string; title: string }[],
    audit: { id: number; number: string; title: string }[],
  },
}
```

### Show Page (`Capa/Show.tsx`)

```typescript
{
  action: CapaAction & {
    site: Site,
    department: Department | null,
    assignedTo: User,
    assignedBy: User,
    severity: Severity | null,
    priority: Priority,
    verifiedBy: User | null,
    sourceRecord: {
      number: string,
      title: string,
      url: string,
    } | null,
  },
  evidence: ManagedFile[],
  comments: Comment[],
  activities: ActivityLog[],
  workflowHistory: WorkflowHistory[],
  availableTransitions: {
    action_key: string,
    action_label: string,
    requires_reason: boolean,
    permission: string,
  }[],
  isOverdue: boolean,
  daysOverdue: number | null,
  can: {
    update: boolean,
    submit: boolean,
    verify: boolean,
    close: boolean,
    reject: boolean,
    export: boolean,
  },
}
```

---

## 4. ListQuery Parameters

The index page accepts these query parameters for filtering:

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `action_number` and `title` (OR) |
| `status` | string | `null` | Filter by exact status: open, in_progress, waiting_verification, closed, rejected |
| `source_module` | string | `null` | Filter by source module: incident, inspection, audit, manual |
| `priority_id` | int | `null` | Filter by priority |
| `site_id` | int | `null` | Filter by site |
| `department_id` | int | `null` | Filter by department |
| `overdue` | bool | `false` | If true, filter only overdue actions |
| `from` | date | `null` | Filter due_date >= from |
| `to` | date | `null` | Filter due_date <= to |
| `per_page` | int | `15` | Items per page (max 100) |
| `sort` | string | `created_at` | Sort column |
| `direction` | string | `desc` | Sort direction (asc/desc) |

### Controller index method pattern:

```php
public function index(ListQuery $listQuery): Response
{
    $items = $listQuery->paginate(
        CapaAction::query()
            ->with(['site', 'department', 'assignedTo', 'priority', 'severity'])
            ->when($listQuery->filters('overdue'), function ($query) {
                $query->whereNotNull('due_date')
                    ->where('due_date', '<', now())
                    ->whereNotIn('status', ['closed', 'rejected']);
            })
            ->when($listQuery->filters('source_module'), function ($query, $source) {
                $query->where('source_module', $source);
            }),
        ['action_number', 'title'],
        ['created_at', 'due_date', 'action_number'],
        'created_at',
        15,
    );

    // Append is_overdue and days_overdue to each item
    $items->getCollection()->transform(function ($item) {
        $item->is_overdue = $item->isOverdue();
        $item->days_overdue = $item->isOverdue()
            ? now()->diffInDays($item->due_date)
            : null;
        return $item;
    });

    return Inertia::render('Modules/Capa/Index', [
        'items' => $items,
        'filters' => $listQuery->filters(),
        'sites' => Site::where('is_active', true)->get(['id', 'name']),
        'departments' => Department::where('is_active', true)->get(['id', 'name']),
        'priorities' => Priority::where('is_active', true)->get(['id', 'name', 'code']),
        'can' => [
            'create' => auth()->user()->can('capa.actions.create'),
            'export' => auth()->user()->can('capa.actions.export'),
        ],
    ]);
}
```

---

## 5. CSV Export Specification

Endpoint: `GET /capa-actions/export?search=...&status=...&source_module=...`

Applies same ListQuery filters, then streams CSV via `CsvExporter`.

### CSV Columns:

| Column Header | Source |
|---|---|
| `Nomor` | `action_number` |
| `Judul` | `title` |
| `Deskripsi` | `description` (truncated 500 chars) |
| `Sumber` | `source_module` |
| `Tipe` | `source_type` |
| `Severity` | `severity.name` |
| `Priority` | `priority.name` |
| `Site` | `site.name` |
| `Departemen` | `department.name` |
| `PIC` | `assignedTo.name` |
| `Status` | `status` |
| `Due Date` | `due_date` |
| `Overdue` | computed: "Ya" if overdue, "" otherwise |
| `Verified By` | `verifiedBy.name` |
| `Closed At` | `closed_at` |

### Controller export method pattern:

```php
public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
{
    $query = $listQuery->apply(
        CapaAction::query()->with(['site', 'department', 'assignedTo', 'severity', 'priority', 'verifiedBy']),
        ['action_number', 'title'],
        ['created_at', 'due_date'],
        'created_at',
    );

    return $exporter->stream($query, [
        'Nomor'       => 'action_number',
        'Judul'       => 'title',
        'Deskripsi'   => fn ($item) => Str::limit($item->description, 500),
        'Sumber'      => 'source_module',
        'Tipe'        => 'source_type',
        'Severity'    => fn ($item) => $item->severity?->name ?? '',
        'Priority'    => fn ($item) => $item->priority?->name ?? '',
        'Site'        => fn ($item) => $item->site?->name ?? '',
        'Departemen'  => fn ($item) => $item->department?->name ?? '',
        'PIC'         => fn ($item) => $item->assignedTo?->name ?? '',
        'Status'      => 'status',
        'Due Date'    => fn ($item) => $item->due_date?->format('Y-m-d') ?? '',
        'Overdue'     => fn ($item) => $item->isOverdue() ? 'Ya' : '',
        'Verified By' => fn ($item) => $item->verifiedBy?->name ?? '',
        'Closed At'   => fn ($item) => $item->closed_at?->format('Y-m-d H:i') ?? '',
    ], 'capa_actions_export.csv');
}
```

---

## 6. Error Responses

| Status | When | Response |
|---|---|---|
| `403` | User lacks required permission | Inertia redirects to dashboard with error flash (middleware handles) |
| `404` | CAPA action ID not found | Laravel default 404 page |
| `422` | Validation failure | Inertia sends errors back to form with `$page.props.errors` |
| `400` | Invalid workflow transition | RuntimeException caught → redirect back with error flash |
| `400` | Submit without evidence | Return back with error: "Wajib melampirkan minimal 1 bukti sebelum submit verifikasi." |
| `419` | CSRF token expired | Laravel default |

### Invalid workflow transition handling:

```php
try {
    $this->workflowService->transition('capa', $action->id, $actionKey, $actor, $reason);
} catch (RuntimeException $e) {
    return back()->withErrors(['workflow' => $e->getMessage()]);
}
```

### Submit without evidence handling:

```php
$evidenceCount = ManagedFile::where('module_name', 'capa')
    ->where('reference_id', $action->id)
    ->where('collection', 'evidence')
    ->whereNull('deleted_at')
    ->count();

if ($evidenceCount === 0) {
    return back()->withErrors([
        'evidence' => 'Wajib melampirkan minimal 1 bukti sebelum submit verifikasi.'
    ]);
}
```

---

## 7. Permission Enforcement Points

| Layer | How |
|---|---|
| **Route middleware** | `->middleware('permission:capa.actions.view')` on each route |
| **Controller authorize()** | `$this->authorize('view', $capaAction)` for show/edit (scope filtering) |
| **Verify+Close** | Route middleware checks both `capa.actions.verify,capa.actions.close` |
| **Inertia shared props** | `auth.permissions` array → frontend checks via `permissions.has('capa.actions.create')` |
| **Export** | Route middleware `permission:capa.actions.export` |
| **Scope filtering** | Policy applies scope: own (assigned_to), department, site, all |

### Policy Example:

```php
class CapaActionPolicy
{
    public function view(User $user, CapaAction $action): bool
    {
        if ($user->hasRole(['Super Admin', 'Admin', 'QHSSE Manager', 'Top Management', 'Auditor'])) {
            return true;
        }
        if ($user->hasRole('QHSSE Officer')) {
            return $action->site_id === $user->employee?->site_id;
        }
        if ($user->hasRole(['Supervisor', 'Department Head'])) {
            return $action->department_id === $user->employee?->department_id;
        }
        // Employee / Reporter, Contractor
        return $action->assigned_to === $user->id;
    }

    public function update(User $user, CapaAction $action): bool
    {
        if (!$action->isEditable()) {
            return false;
        }
        return $this->view($user, $action);
    }
}
```

---

## 8. Numbering Integration

On `store`:

```php
$generatedNumber = app(NumberingService::class)->generate(
    moduleName: 'capa',
    actor: $actor,
    referenceType: CapaAction::class,
    referenceId: $action->id,
);

$action->update(['action_number' => $generatedNumber->number]);
```

Numbering format (already seeded): `ACT-2026-0001`

---

## 9. Workflow Integration

The workflow definition `capa` is already seeded in `WorkflowSeeder` with:

- Initial status: `open`
- Terminal statuses: `closed`, `rejected`

### Transitions used by this module:

| Action | Controller Method | From | To | requires_reason |
|---|---|---|---|---|
| `start` | `start()` | open | in_progress | false |
| `submit_verification` | `submit()` | in_progress | waiting_verification | false |
| `verify_close` | `verifyClose()` | waiting_verification | closed | **true** (verification_note) |
| `reject` | `reject()` | waiting_verification | rejected | **true** (reason) |
| `restart` | `restart()` | rejected | in_progress | false |

### Full workflow path:

```
open ──(start)──→ in_progress ──(submit_verification)──→ waiting_verification ──(verify_close)──→ closed
                                                                        ↘(reject)──→ rejected ──(restart)──→ in_progress
```

---

## 10. File Upload Integration

Evidence files are uploaded via the existing core `ManagedFileController` routes (not module-specific).

### Upload flow:

1. User creates CAPA action → gets `capa_action.id`
2. User uploads file via `POST /core/files` with:
   - `module_name`: `capa`
   - `reference_id`: `$action->id`
   - `collection`: `evidence`
   - `file`: the UploadedFile
3. `ManagedFileService::store($file, new FileReference('capa', $action->id, 'evidence'), $uploader)`
4. File stored on `local` disk at `managed-files/capa/{id}/evidence/{uuid}.{ext}`
5. Download via `GET /core/files/{managedFile}/download` (permission-gated)

### Show page loads evidence:

```php
'evidence' => ManagedFile::query()
    ->where('module_name', 'capa')
    ->where('reference_id', $action->id)
    ->where('collection', 'evidence')
    ->whereNull('deleted_at')
    ->get(),
```

### Evidence count check (for submit validation):

```php
$evidenceCount = ManagedFile::query()
    ->where('module_name', 'capa')
    ->where('reference_id', $action->id)
    ->where('collection', 'evidence')
    ->whereNull('deleted_at')
    ->count();
```

---

## 11. Cross-Module Integration

### 11.1 Source Record Resolution

When loading the Show page, the controller resolves the source record link:

```php
$sourceRecord = null;
if ($action->source_module && $action->source_module !== 'manual' && $action->source_reference_id) {
    $sourceRecord = match ($action->source_module) {
        'incident' => Incident::where('id', $action->source_reference_id)
            ->select('id', 'incident_number', 'title')
            ->first()
            ?->only(['id', 'incident_number', 'title']),
        'inspection' => Inspection::where('id', $action->source_reference_id)
            ->select('id', 'inspection_number', 'title')
            ->first()
            ?->only(['id', 'inspection_number', 'title']),
        'audit' => AuditFinding::where('id', $action->source_reference_id)
            ->select('id', 'finding_number', 'title')
            ->first()
            ?->only(['id', 'finding_number', 'title']),
        default => null,
    };

    if ($sourceRecord) {
        $sourceRecord['url'] = match ($action->source_module) {
            'incident'   => route('incident.reports.show', $action->source_reference_id),
            'inspection' => route('inspections.show', $action->source_reference_id),
            'audit'       => route('audit-findings.show', $action->source_reference_id),
            default       => null,
        };
    }
}
```

### 11.2 Programmatic CAPA Creation from Other Modules

Other modules can create CAPA actions via a service:

```php
// In IncidentReportController or similar:
app(CapaActionService::class)->createFromSource(
    sourceModule: 'incident',
    sourceReferenceId: $incident->id,
    title: "Corrective action for {$incident->incident_number}",
    description: $incident->description,
    siteId: $incident->site_id,
    departmentId: $incident->department_id,
    assignedTo: $assignedUserId,
    dueDate: now()->addDays($priority->sla_days ?? 14),
    priorityId: $priorityId,
    sourceType: 'corrective',
    actor: $actor,
);
```

### 11.3 CapaActionService::createFromSource

```php
class CapaActionService
{
    public function createFromSource(
        string $sourceModule,
        int $sourceReferenceId,
        string $title,
        string $description,
        int $siteId,
        ?int $departmentId,
        int $assignedTo,
        ?string $dueDate,
        int $priorityId,
        ?string $sourceType,
        User $actor,
        ?int $severityId = null,
    ): CapaAction {
        return DB::transaction(function () use (...) {
            $action = CapaAction::create([
                'title' => $title,
                'description' => $description,
                'source_module' => $sourceModule,
                'source_reference_id' => $sourceReferenceId,
                'source_type' => $sourceType,
                'site_id' => $siteId,
                'department_id' => $departmentId,
                'assigned_to' => $assignedTo,
                'assigned_by' => $actor->id,
                'due_date' => $dueDate,
                'severity_id' => $severityId,
                'priority_id' => $priorityId,
                'status' => 'open',
            ]);

            $generatedNumber = app(NumberingService::class)->generate(
                moduleName: 'capa',
                actor: $actor,
                referenceType: CapaAction::class,
                referenceId: $action->id,
            );
            $action->update(['action_number' => $generatedNumber->number]);

            app(WorkflowService::class)->start('capa', $action->id, $actor);
            app(AuditService::class)->created($action, $actor, 'capa', $action->id);
            app(ActivityService::class)->log('capa', $action->id, 'capa.created', 'Tindakan dibuat dari ' . $sourceModule, $actor);
            app(NotificationService::class)->notify(
                User::find($assignedTo),
                'capa.assigned',
                [...],
                $actor,
                'capa',
                $action->id,
                route('capa.actions.show', $action->id),
            );

            return $action;
        });
    }
}
```
