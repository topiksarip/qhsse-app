# API Contract — Permit to Work

> Spec ini mendefinisikan semua route, request, response, dan error handling untuk modul Permit to Work.

## 1. Route Table

Semua route diawali dengan prefix `/permits`, nama route `permits.*`, dan middleware `auth,verified`.

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/permits` | `index` | `permits.index` | `permit.work.view` | List permits with search/filter/pagination |
| GET | `/permits/create` | `create` | `permits.create` | `permit.work.create` | Render create form |
| POST | `/permits` | `store` | `permits.store` | `permit.work.create` | Save new permit (draft or submit) |
| GET | `/permits/{permit}` | `show` | `permits.show` | `permit.work.view` | Show permit detail |
| GET | `/permits/{permit}/edit` | `edit` | `permits.edit` | `permit.work.update` | Render edit form |
| PUT | `/permits/{permit}` | `update` | `permits.update` | `permit.work.update` | Update permit (draft only) |
| POST | `/permits/{permit}/submit` | `submit` | `permits.submit` | `permit.work.submit` | Transition draft → submitted |
| POST | `/permits/{permit}/review` | `review` | `permits.review` | `permit.work.review` | Transition submitted → under_review |
| POST | `/permits/{permit}/approve` | `approve` | `permits.approve` | `permit.work.approve` | Transition under_review → approved |
| POST | `/permits/{permit}/activate` | `activate` | `permits.activate` | `permit.work.approve` | Transition approved → active (requires all checklist signed) |
| POST | `/permits/{permit}/reject` | `reject` | `permits.reject` | `permit.work.review` | Transition submitted/under_review → rejected |
| POST | `/permits/{permit}/close` | `close` | `permits.close` | `permit.work.close` | Transition active → closed (requires reason) |
| POST | `/permits/{permit}/checklists/{checklist}/sign` | `signChecklist` | `permits.checklists.sign` | `permit.work.update` | Sign a checklist item |
| GET | `/permits/export` | `export` | `permits.export` | `permit.work.export` | Export filtered list as CSV |

### Route Registration

File: `routes/modules.php`

```php
use App\Http\Controllers\Modules\PermitToWork\PermitController;

Route::middleware(['auth', 'verified'])
    ->prefix('permits')
    ->name('permits.')
    ->group(function (): void {
        Route::get('/', [PermitController::class, 'index'])
            ->name('index')
            ->middleware('permission:permit.work.view');

        Route::get('/create', [PermitController::class, 'create'])
            ->name('create')
            ->middleware('permission:permit.work.create');

        Route::post('/', [PermitController::class, 'store'])
            ->name('store')
            ->middleware('permission:permit.work.create');

        Route::get('/{permit}', [PermitController::class, 'show'])
            ->name('show')
            ->middleware('permission:permit.work.view');

        Route::get('/{permit}/edit', [PermitController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:permit.work.update');

        Route::put('/{permit}', [PermitController::class, 'update'])
            ->name('update')
            ->middleware('permission:permit.work.update');

        Route::post('/{permit}/submit', [PermitController::class, 'submit'])
            ->name('submit')
            ->middleware('permission:permit.work.submit');

        Route::post('/{permit}/review', [PermitController::class, 'review'])
            ->name('review')
            ->middleware('permission:permit.work.review');

        Route::post('/{permit}/approve', [PermitController::class, 'approve'])
            ->name('approve')
            ->middleware('permission:permit.work.approve');

        Route::post('/{permit}/activate', [PermitController::class, 'activate'])
            ->name('activate')
            ->middleware('permission:permit.work.approve');

        Route::post('/{permit}/reject', [PermitController::class, 'reject'])
            ->name('reject')
            ->middleware('permission:permit.work.review');

        Route::post('/{permit}/close', [PermitController::class, 'close'])
            ->name('close')
            ->middleware('permission:permit.work.close');

        Route::post('/{permit}/checklists/{checklist}/sign', [PermitController::class, 'signChecklist'])
            ->name('checklists.sign')
            ->middleware('permission:permit.work.update');

        Route::get('/export', [PermitController::class, 'export'])
            ->name('export')
            ->middleware('permission:permit.work.export');
    });
```

### Route Model Binding

- Parameter name: `{permit}` → Laravel resolves to `Permit` model via route key (id).
- Parameter name: `{checklist}` → Laravel resolves to `PermitChecklist` model via route key (id).
- Custom key: default `id` (no need for `getRouteKeyName()` override).

---

## 2. Request Payloads

### POST `/permits` (store)

```json
{
  "type": "hot_work",
  "title": "Pengelasan Strip Plate Tower B",
  "description": "Pengelasan strip plate pada struktur Tower B lantai 3",
  "site_id": 1,
  "area_id": 2,
  "department_id": 3,
  "contractor_id": 5,
  "work_location": "Tower B Lantai 3, Area Welding Bay",
  "work_description": "Pengelasan strip plate sepanjang 15 meter menggunakan MIG welding dengan argon gas",
  "start_datetime": "2026-07-11T14:00:00",
  "end_datetime": "2026-07-11T18:00:00",
  "risk_level": "high",
  "jsa_reference": "RSK-2026-0012",
  "action": "draft"
}
```

**Validation Rules (StorePermitRequest):**

| Field | Rule | Notes |
|---|---|---|
| `type` | `required|in:hot_work,working_at_height,confined_space,electrical,excavation,lifting,other` | |
| `title` | `required|string|max:255` | |
| `description` | `required|string` | |
| `site_id` | `required|exists:sites,id` | |
| `area_id` | `nullable|exists:areas,id` | |
| `department_id` | `nullable|exists:departments,id` | |
| `contractor_id` | `nullable|exists:companies,id` | |
| `work_location` | `required|string|max:255` | |
| `work_description` | `required|string` | |
| `start_datetime` | `required|date` | Must be after_or_equal: now (validated on submit) |
| `end_datetime` | `required|date|after:start_datetime` | |
| `risk_level` | `nullable|in:low,medium,high,critical` | |
| `jsa_reference` | `nullable|string|max:255` | |
| `action` | `nullable|in:draft,submit` | If `submit`, validates mandatory fields AND triggers workflow transition |

**Controller behavior (store):**
1. Validate request
2. Create `Permit` with `created_by` = auth user
3. Generate `permit_number` via `NumberingService::generate('permit', $actor, $siteCode)`
4. Auto-calculate `validity_hours` = `round((end_datetime - start_datetime) / 3600)`
5. Start workflow via `WorkflowService::start('permit', $permit->id, $actor)`
6. Generate checklist items based on `type` (from checklist template config)
7. Insert checklist items into `permit_checklists`
8. If `action === 'submit'`: call `WorkflowService::transition('permit', $permit->id, 'submit', $actor)`
9. `AuditService::created($permit, $actor, 'permit', $permit->id)`
10. `ActivityService::log('permit', $permit->id, 'permit.created', 'Permit created', $actor)`
11. If submitted: `NotificationService::notifyMany($qhsseTeamUsers, 'permit.submitted', [...])`
12. Redirect to `permits.show`

### PUT `/permits/{permit}` (update)

Same payload as store, but:
- Only allowed if `status === 'draft'`
- `type` cannot be changed after checklist items are generated
- `permit_number` cannot be changed
- `validity_hours` auto-recalculated if start/end datetime changes
- Records audit trail for changed fields via `AuditService::updated()`

### POST `/permits/{permit}/submit` (submit)

No request body needed. Controller:
1. Check `permit.status === 'draft'`
2. Validate mandatory fields (see BR-03 in MODULE_SPEC)
3. `WorkflowService::transition('permit', $permit->id, 'submit', $actor)`
4. `ActivityService::log('permit', $permit->id, 'permit.submitted', ...)` 
5. `NotificationService::notifyMany($qhsseTeamUsers, 'permit.submitted', [...])`
6. Redirect back with flash message

### POST `/permits/{permit}/review` (review)

No request body needed. Controller:
1. Check `permit.status === 'submitted'`
2. `WorkflowService::transition('permit', $permit->id, 'review', $actor)`
3. `NotificationService::notify($requester, 'permit.reviewing', [...])`
4. Redirect back

### POST `/permits/{permit}/approve` (approve)

No request body needed. Controller:
1. Check `permit.status === 'under_review'`
2. Check `$actor->id !== $permit->created_by` (conflict of interest)
3. `WorkflowService::transition('permit', $permit->id, 'approve', $actor)`
4. Set `approved_by = $actor->id`, `approved_at = now()`
5. `NotificationService::notify($requester, 'permit.approved', [...])`
6. Redirect back

### POST `/permits/{permit}/activate` (activate)

No request body needed. Controller:
1. Check `permit.status === 'approved'`
2. Check all checklist items are signed: `PermitChecklist::where('permit_id', $permit->id)->where('is_checked', false)->doesntExist()`
3. If unsigned items exist: return error "Semua checklist items harus di-sign sebelum izin dapat diaktifkan."
4. `WorkflowService::transition('permit', $permit->id, 'activate', $actor)`
5. `ActivityService::log('permit', $permit->id, 'permit.activated', ...)` 
6. Redirect back

### POST `/permits/{permit}/reject` (reject)

```json
{
  "reason": "JSA reference tidak valid, mohon lampirkan JSA yang sesuai."
}
```

| Field | Rule |
|---|---|
| `reason` | `required|string|min:10|max:1000` |

Controller:
1. Check `permit.status IN ('submitted', 'under_review')`
2. `WorkflowService::transition('permit', $permit->id, 'reject', $actor, $reason)`
3. Set `cancellation_reason = $reason`
4. `NotificationService::notify($requester, 'permit.rejected', [...])`
5. Redirect back

### POST `/permits/{permit}/close` (close)

```json
{
  "reason": "Pekerjaan selesai, area telah dibersihkan dan diverifikasi aman."
}
```

| Field | Rule |
|---|---|
| `reason` | `required|string|min:10|max:1000` |

Controller:
1. Check `permit.status === 'active'`
2. `WorkflowService::transition('permit', $permit->id, 'close', $actor, $reason)`
3. Set `closed_by = $actor->id`, `closed_at = now()`
4. `NotificationService::notify($requester, 'permit.closed', [...])`
5. Redirect back

### POST `/permits/{permit}/checklists/{checklist}/sign` (signChecklist)

No request body needed. Controller:
1. Check `checklist.permit_id === $permit->id`
2. Check `permit.status IN ('approved', 'under_review', 'submitted')` — can sign after submit
3. Check user has `permit.work.update` or `permit.work.approve`
4. Set `is_checked = true`, `checked_by = $actor->id`, `checked_at = now()`
5. `AuditService::log('checklist.signed', $checklist, oldValues, newValues, $actor, 'permit', $permit->id)`
6. `ActivityService::log('permit', $permit->id, 'checklist.signed', "Checklist item signed: {$checklist->item_text}", $actor)`
7. Return updated checklist item (JSON or Inertia redirect)

---

## 3. Inertia Response Props

### Index Page (`PermitToWork/Index.tsx`)

```typescript
{
  permits: {
    data: Permit[],
    current_page: number,
    last_page: number,
    per_page: number,
    total: number,
    from: number | null,
    to: number | null,
  },
  filters: {
    search: string,
    type: string | null,
    status: string | null,
    validity: string | null,
    site_id: number | null,
    contractor_id: number | null,
    from: string | null,
    to: string | null,
  },
  sites: Site[],
  contractors: Company[],
  summary: {
    active: number,
    expiring_soon: number,
    expired: number,
    pending: number,
  },
  can: {
    create: boolean,
    export: boolean,
  },
}
```

### Create/Edit Page (`PermitToWork/Form.tsx`)

```typescript
{
  permit: Permit | null,  // null for create, populated for edit
  sites: Site[],
  areas: Area[],
  departments: Department[],
  contractors: Company[],
  checklistTemplates: Record<string, string[]>,  // type → checklist item texts
}
```

### Show Page (`PermitToWork/Show.tsx`)

```typescript
{
  permit: Permit & {
    site: Site,
    area: Area | null,
    department: Department | null,
    contractor: Company | null,
    creator: User,
    approver: User | null,
    closer: User | null,
    checklists: PermitChecklist[],
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
  checklistProgress: {
    total: number,
    signed: number,
    all_signed: boolean,
  },
  validityStatus: 'active' | 'expired' | 'expiring_soon' | 'not_started',
  can: {
    update: boolean,
    submit: boolean,
    review: boolean,
    approve: boolean,
    close: boolean,
    export: boolean,
  },
}
```

---

## 4. ListQuery Parameters

The index page accepts these query parameters for filtering:

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `permit_number`, `title`, and `work_location` (OR) |
| `type` | string | `null` | Filter by permit type: hot_work, working_at_height, confined_space, electrical, excavation, lifting, other |
| `status` | string | `null` | Filter by exact status: draft, submitted, under_review, approved, active, closed, rejected |
| `validity` | string | `null` | Filter by validity status: active, expiring_soon, expired, not_started |
| `site_id` | int | `null` | Filter by site |
| `contractor_id` | int | `null` | Filter by contractor |
| `from` | date | `null` | Filter start_datetime ≥ from |
| `to` | date | `null` | Filter start_datetime ≤ to |
| `per_page` | int | `15` | Items per page (max 100) |
| `sort` | string | `created_at` | Sort column |
| `direction` | string | `desc` | Sort direction (asc/desc) |

### Validity Status Query Logic

```php
// validity=active
$query->where('status', 'active')
    ->where('start_datetime', '<=', now())
    ->where('end_datetime', '>', now());

// validity=expiring_soon
$query->where('status', 'active')
    ->where('end_datetime', '>', now())
    ->where('end_datetime', '<=', now()->addHours(24));

// validity=expired
$query->where('status', 'active')
    ->where('end_datetime', '<', now());

// validity=not_started
$query->whereIn('status', ['draft', 'submitted', 'under_review', 'approved']);
```

### Controller index method pattern:

```php
public function index(ListQuery $listQuery): Response
{
    $items = $listQuery->paginate(
        Permit::query()->with(['site', 'contractor', 'creator']),
        ['permit_number', 'title', 'work_location'],
        ['created_at', 'start_datetime', 'end_datetime', 'permit_number'],
        'created_at',
        15,
    );

    $summary = [
        'active' => Permit::where('status', 'active')
            ->where('start_datetime', '<=', now())
            ->where('end_datetime', '>', now())->count(),
        'expiring_soon' => Permit::where('status', 'active')
            ->where('end_datetime', '>', now())
            ->where('end_datetime', '<=', now()->addHours(24))->count(),
        'expired' => Permit::where('status', 'active')
            ->where('end_datetime', '<', now())->count(),
        'pending' => Permit::whereIn('status', ['draft', 'submitted', 'under_review'])->count(),
    ];

    return Inertia::render('Modules/PermitToWork/Index', [
        'permits' => $items,
        'filters' => $listQuery->filters(),
        'sites' => Site::where('is_active', true)->get(['id', 'code', 'name']),
        'contractors' => Company::where('is_active', true)->where('type', 'contractor')->get(['id', 'code', 'name']),
        'summary' => $summary,
        'can' => [
            'create' => auth()->user()->can('permit.work.create'),
            'export' => auth()->user()->can('permit.work.export'),
        ],
    ]);
}
```

---

## 5. CSV Export Specification

Endpoint: `GET /permits/export?search=...&type=...&status=...`

Applies same ListQuery filters, then streams CSV via `CsvExporter`.

### CSV Columns:

| Column Header | Source |
|---|---|
| `Nomor` | `permit_number` |
| `Judul` | `title` |
| `Jenis` | `type` |
| `Deskripsi Pekerjaan` | `work_description` |
| `Site` | `site.name` |
| `Area` | `area.name` |
| `Department` | `department.name` |
| `Contractor` | `contractor.name` |
| `Lokasi Kerja` | `work_location` |
| `Mulai` | `start_datetime` (formatted: Y-m-d H:i) |
| `Berakhir` | `end_datetime` (formatted: Y-m-d H:i) |
| `Durasi (jam)` | `validity_hours` |
| `Risk Level` | `risk_level` |
| `JSA Reference` | `jsa_reference` |
| `Status` | `status` |
| `Approved By` | `approver.name` |
| `Closed By` | `closer.name` |
| `Created At` | `created_at` (formatted: Y-m-d H:i) |

### Controller export method pattern:

```php
public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
{
    $query = $listQuery->apply(
        Permit::query()->with(['site', 'area', 'department', 'contractor', 'approver', 'closer']),
        ['permit_number', 'title', 'work_location'],
        ['created_at', 'start_datetime'],
        'created_at',
    );

    return $exporter->stream($query, [
        'Nomor' => 'permit_number',
        'Judul' => 'title',
        'Jenis' => 'type',
        'Deskripsi Pekerjaan' => fn ($item) => Str::limit($item->work_description, 500),
        'Site' => fn ($item) => $item->site?->name ?? '',
        'Area' => fn ($item) => $item->area?->name ?? '',
        'Department' => fn ($item) => $item->department?->name ?? '',
        'Contractor' => fn ($item) => $item->contractor?->name ?? '',
        'Lokasi Kerja' => 'work_location',
        'Mulai' => fn ($item) => $item->start_datetime?->format('Y-m-d H:i') ?? '',
        'Berakhir' => fn ($item) => $item->end_datetime?->format('Y-m-d H:i') ?? '',
        'Durasi (jam)' => 'validity_hours',
        'Risk Level' => fn ($item) => $item->risk_level ?? '',
        'JSA Reference' => fn ($item) => $item->jsa_reference ?? '',
        'Status' => 'status',
        'Approved By' => fn ($item) => $item->approver?->name ?? '',
        'Closed By' => fn ($item) => $item->closer?->name ?? '',
        'Created At' => fn ($item) => $item->created_at?->format('Y-m-d H:i') ?? '',
    ], 'permits-export.csv');
}
```

---

## 6. Error Responses

| Status | When | Response |
|---|---|---|
| `403` | User lacks required permission | Inertia redirects to dashboard with error flash (middleware handles) |
| `404` | Permit ID not found | Laravel default 404 page |
| `422` | Validation failure | Inertia sends errors back to form with `$page.props.errors` |
| `400` | Invalid workflow transition | RuntimeException caught → redirect back with error flash |
| `400` | Activate with unsigned checklist | JSON error: "Semua checklist items harus di-sign sebelum izin dapat diaktifkan." |
| `400` | Approver is same as requester | JSON error: "Anda tidak dapat menyetujui izin yang Anda ajukan sendiri." |
| `419` | CSRF token expired | Laravel default |

### Invalid workflow transition handling:

```php
try {
    $this->workflowService->transition('permit', $permit->id, $actionKey, $actor, $reason);
} catch (RuntimeException $e) {
    return back()->withErrors(['workflow' => $e->getMessage()]);
}
```

### Checklist not fully signed error:

```php
$unsignedCount = PermitChecklist::where('permit_id', $permit->id)
    ->where('is_checked', false)
    ->count();

if ($unsignedCount > 0) {
    return back()->withErrors([
        'checklist' => "Semua checklist items harus di-sign sebelum izin dapat diaktifkan. {$unsignedCount} item belum di-sign."
    ]);
}
```

### Conflict of interest error:

```php
if ($permit->created_by === $actor->id) {
    return back()->withErrors([
        'approve' => 'Anda tidak dapat menyetujui izin yang Anda ajukan sendiri.'
    ]);
}
```

---

## 7. Permission Enforcement Points

| Layer | How |
|---|---|
| **Route middleware** | `->middleware('permission:permit.work.view')` on each route |
| **Controller authorize()** | `$this->authorize('view', $permit)` for show/edit (optional, for scope filtering) |
| **Inertia shared props** | `auth.permissions` array → frontend checks via `permissions.has('permit.work.create')` |
| **Export** | Route middleware `permission:permit.work.export` |
| **Approve** | Route middleware `permission:permit.work.approve` + conflict of interest check in controller |
| **Activate** | Route middleware `permission:permit.work.approve` + checklist validation in controller |

---

## 8. Numbering Integration

On `store`:

```php
$site = Site::find($validated['site_id']);
$generatedNumber = app(NumberingService::class)->generate(
    moduleName: 'permit',
    actor: $actor,
    siteCode: $site->code,
    referenceType: Permit::class,
    referenceId: $permit->id,
);

$permit->update(['permit_number' => $generatedNumber->number]);
```

Numbering format (already seeded): `PTW-2026-0001` (with site code: `PTW-JKT-2026-0001`)

---

## 9. Workflow Integration

The workflow definition `permit` needs to be created in `PermitWorkflowSeeder`.

### Workflow Definition

| Property | Value |
|---|---|
| `module_name` | `permit` |
| `code` | `PERMIT_WORKFLOW` |
| `name` | `Permit to Work Workflow` |
| `initial_status` | `draft` |
| `is_active` | `true` |

### Transitions

| Action | Controller Method | From | To | requires_reason | Required Permission |
|---|---|---|---|---|---|
| `submit` | `submit()` | draft | submitted | false | `permit.work.submit` |
| `review` | `review()` | submitted | under_review | false | `permit.work.review` |
| `approve` | `approve()` | under_review | approved | false | `permit.work.approve` |
| `activate` | `activate()` | approved | active | false | `permit.work.approve` |
| `close` | `close()` | active | closed | **true** | `permit.work.close` |
| `reject` | `reject()` | submitted | rejected | **true** | `permit.work.review` |
| `reject` | `reject()` | under_review | rejected | **true** | `permit.work.review` |

### Full workflow path:

```
draft ──(submit)──→ submitted ──(review)──→ under_review ──(approve)──→ approved ──(activate)──→ active ──(close)──→ closed
                         │                      │
                         └──(reject)────────────┘──(reject)──→ rejected
```

### Controller integration:

```php
// Submit
$this->workflowService->transition('permit', $permit->id, 'submit', $actor);

// Review
$this->workflowService->transition('permit', $permit->id, 'review', $actor);

// Approve (with conflict of interest check)
if ($permit->created_by === $actor->id) {
    return back()->withErrors(['approve' => 'Anda tidak dapat menyetujui izin yang Anda ajukan sendiri.']);
}
$this->workflowService->transition('permit', $permit->id, 'approve', $actor);
$permit->update(['approved_by' => $actor->id, 'approved_at' => now()]);

// Activate (with checklist validation)
if (!$this->allChecklistsSigned($permit)) {
    return back()->withErrors(['checklist' => 'Semua checklist items harus di-sign sebelum izin dapat diaktifkan.']);
}
$this->workflowService->transition('permit', $permit->id, 'activate', $actor);

// Reject (with reason)
$this->workflowService->transition('permit', $permit->id, 'reject', $actor, $reason);
$permit->update(['cancellation_reason' => $reason]);

// Close (with reason)
$this->workflowService->transition('permit', $permit->id, 'close', $actor, $reason);
$permit->update(['closed_by' => $actor->id, 'closed_at' => now()]);
```

Invalid transition throws `RuntimeException` → caught in controller → flash error.

---

## 10. File Upload Integration

Evidence files are uploaded via the existing core `ManagedFileController` routes (not module-specific).

### Upload flow:

1. User creates permit → gets `permit.id`
2. User uploads file via `POST /core/files` with:
   - `module_name`: `permit`
   - `reference_id`: `$permit->id`
   - `collection`: `evidence`
   - `file`: the UploadedFile
3. `ManagedFileService::store($file, new FileReference('permit', $permit->id, 'evidence'), $uploader)`
4. File stored on `local` disk at `managed-files/permit/{id}/evidence/{uuid}.{ext}`
5. Download via `GET /core/files/{managedFile}/download` (permission-gated by `core.files.download` + module reference check)

### Show page loads evidence:

```php
'evidence' => ManagedFile::query()
    ->where('module_name', 'permit')
    ->where('reference_id', $permit->id)
    ->where('collection', 'evidence')
    ->whereNull('deleted_at')
    ->get(),
```

---

## 11. Checklist Generation on Create

When a permit is created, checklist items are auto-generated based on the selected `type`.

### Controller store method checklist generation:

```php
$checklistTemplates = config('permit.checklists');

$items = $checklistTemplates[$validated['type']] ?? $checklistTemplates['other'];

foreach ($items as $itemText) {
    PermitChecklist::create([
        'permit_id' => $permit->id,
        'item_text' => $itemText,
        'is_checked' => false,
    ]);
}
```

### Config file: `config/permit.php`

```php
return [
    'checklists' => [
        'hot_work' => [
            'APD tahan api tersedia dan dipakai (goggles, gloves, apron)',
            'Fire extinguisher tersedia di area kerja (min. 2 unit)',
            'Area 10 meter bebas bahan mudah terbakar',
            'Fire watch ditunjuk dan siap',
            'Hot work permit area di-barricade',
            'Sistem ventilasi memadai',
            'Emergency response plan diketahui semua pekerja',
        ],
        'working_at_height' => [
            'Full body harness dipakai dan di-inspect',
            'Anchor point terverifikasi (min. 22 kN)',
            'Scaffolding di-inspect oleh competent person',
            'Edge protection / guard rail terpasang',
            'Fall protection system aktif',
            'Tidak ada pekerjaan di bawah area tanpa proteksi',
            'Emergency rescue plan siap',
        ],
        'confined_space' => [
            'Gas test dilakukan (O2, LEL, H2S, CO)',
            'Ventilasi mekanis aktif',
            'Entry permit ditandatangani',
            'Standby person ditunjuk di entrance',
            'Rescue equipment siap (tripod, winch, SCBA)',
            'Komunikasi antara entrant dan attendant',
            'Lockout/Tagout semua sumber energi',
            'Continuous gas monitoring aktif',
        ],
        'electrical' => [
            'LOTO procedure dijalankan dan diverifikasi',
            'Voltage test dilakukan (verify zero energy)',
            'PPE electrical rated dipakai (gloves, mats)',
            'Grounding temporary terpasang',
            'Barricade dan warning sign terpasang',
            'Competent person melakukan pekerjaan',
            'Emergency procedure untuk electrical shock diketahui',
        ],
        'excavation' => [
            'Underground utility scan dilakukan dan didokumentasikan',
            'Shoring/sloping sesuai depth (≥ 1.2m wajib shoring)',
            'Safe access/egress (ladder setiap 7.5m)',
            'Spoil pile ≥ 0.6m dari edge',
            'Gas test untuk confined space trench',
            'Barricade dan warning sign terpasang',
            'Daily inspection oleh competent person',
        ],
        'lifting' => [
            'Lift plan disiapkan dan di-approve',
            'Load calculation dilakukan',
            'Crane/hoist certification valid',
            'Rigger dan signalman certified',
            'Sling dan rigging gear di-inspect',
            'Area lifting di-barricade',
            'Weather condition sesuai (wind speed < limit)',
            'Communication radio tersedia',
        ],
        'other' => [
            'Risk assessment / JSA dilakukan',
            'APD sesuai pekerjaan dipakai',
            'Emergency procedure diketahui',
            'Pekerja competent dan tersertifikasi',
            'Area kerja di-barricade',
        ],
    ],
];
```

---

## 12. Integration Points

### 12.1 Risk/JSA Module (`13-risk-management`)

- Permit references JSA via `jsa_reference` (free-text field, stores the risk assessment number).
- Future: FK to `risks` table when Risk module is implemented.

### 12.2 Contractor Module (`16-contractor-management`)

- Permit references contractor via `contractor_id` → `companies.id`.
- Contractor scope: users with role `Contractor` can only see permits where `contractor_id` matches their `company_id`.

### 12.3 Asset Module (`17-asset-equipment-safety`)

- Permit references work location via `work_location` (free-text field).
- Future: FK to `assets` table for equipment-specific permits.

### 12.4 Notification Module (`06-notification`)

- 6 notification events: `permit.submitted`, `permit.reviewing`, `permit.approved`, `permit.rejected`, `permit.closed`, `permit.expiring_soon`.
- `permit.expiring_soon` is triggered by a scheduled command (Laravel Scheduler, hourly) that finds active permits where `end_datetime` is within 24 hours.

### 12.5 Scheduled Command for Expiry Check

```php
// app/Console/Commands/CheckPermitExpiry.php
class CheckPermitExpiry extends Command
{
    protected $signature = 'permit:check-expiry';
    protected $description = 'Check for permits expiring soon and send notifications';

    public function handle(NotificationService $notificationService): int
    {
        $permits = Permit::where('status', 'active')
            ->where('end_datetime', '>', now())
            ->where('end_datetime', '<=', now()->addHours(24))
            ->get();

        foreach ($permits as $permit) {
            $recipients = collect([$permit->creator])
                ->merge($this->getQhsseOfficers($permit->site_id))
                ->merge($this->getSupervisor($permit->department_id));

            $notificationService->notifyMany(
                $recipients,
                'permit.expiring_soon',
                [...],
                null,
                'permit',
                $permit->id,
                route('permits.show', $permit)
            );
        }

        return Command::SUCCESS;
    }
}
```

Register in `routes/console.php` or `app/Console/Kernel.php`:

```php
Schedule::command('permit:check-expiry')->hourly();
```
