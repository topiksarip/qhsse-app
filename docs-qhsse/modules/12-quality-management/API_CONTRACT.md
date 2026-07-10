# API Contract — Quality Management

> Spec ini mendefinisikan semua route, request, response, dan error handling untuk modul Quality Management (NCR + Customer Complaints).

---

## 1. Route Table

### 1.1 NCR Routes

Semua route diawali dengan prefix `/ncrs`, nama route `quality.ncrs.*`, dan middleware `auth,verified`.

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/ncrs` | `NcrController@index` | `quality.ncrs.index` | `quality.ncrs.view` | List NCRs with search/filter/pagination |
| GET | `/ncrs/create` | `NcrController@create` | `quality.ncrs.create` | `quality.ncrs.create` | Render create form |
| POST | `/ncrs` | `NcrController@store` | `quality.ncrs.store` | `quality.ncrs.create` | Save new NCR |
| GET | `/ncrs/{ncr}` | `NcrController@show` | `quality.ncrs.show` | `quality.ncrs.view` | Show NCR detail |
| GET | `/ncrs/{ncr}/edit` | `NcrController@edit` | `quality.ncrs.edit` | `quality.ncrs.update` | Render edit form |
| PUT | `/ncrs/{ncr}` | `NcrController@update` | `quality.ncrs.update` | `quality.ncrs.update` | Update NCR |
| POST | `/ncrs/{ncr}/submit` | `NcrController@submit` | `quality.ncrs.submit` | `quality.ncrs.update` | Transition open → under_review |
| POST | `/ncrs/{ncr}/review` | `NcrController@review` | `quality.ncrs.review` | `quality.ncrs.update` | Transition under_review → in_progress |
| POST | `/ncrs/{ncr}/close` | `NcrController@close` | `quality.ncrs.close` | `quality.ncrs.close` | Transition in_progress → closed (requires RCA) |
| GET | `/ncrs/export` | `NcrController@export` | `quality.ncrs.export` | `quality.ncrs.export` | Export filtered list as CSV |

### 1.2 Customer Complaint Routes

Semua route diawali dengan prefix `/customer-complaints`, nama route `quality.complaints.*`, dan middleware `auth,verified`.

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/customer-complaints` | `CustomerComplaintController@index` | `quality.complaints.index` | `quality.complaints.view` | List complaints with search/filter/pagination |
| GET | `/customer-complaints/create` | `CustomerComplaintController@create` | `quality.complaints.create` | `quality.complaints.create` | Render create form |
| POST | `/customer-complaints` | `CustomerComplaintController@store` | `quality.complaints.store` | `quality.complaints.create` | Save new complaint |
| GET | `/customer-complaints/{complaint}` | `CustomerComplaintController@show` | `quality.complaints.show` | `quality.complaints.view` | Show complaint detail |
| GET | `/customer-complaints/{complaint}/edit` | `CustomerComplaintController@edit` | `quality.complaints.edit` | `quality.complaints.update` | Render edit form |
| PUT | `/customer-complaints/{complaint}` | `CustomerComplaintController@update` | `quality.complaints.update` | `quality.complaints.update` | Update complaint |
| POST | `/customer-complaints/{complaint}/close` | `CustomerComplaintController@close` | `quality.complaints.close` | `quality.complaints.close` | Transition in_progress → closed (requires resolution) |
| GET | `/customer-complaints/export` | `CustomerComplaintController@export` | `quality.complaints.export` | `quality.complaints.export` | Export filtered list as CSV |

### Route Registration

File: `routes/modules.php`

```php
use App\Http\Controllers\Modules\Quality\NcrController;
use App\Http\Controllers\Modules\Quality\CustomerComplaintController;

// NCR Routes
Route::middleware(['auth', 'verified'])
    ->prefix('ncrs')
    ->name('quality.ncrs.')
    ->group(function (): void {
        Route::get('/', [NcrController::class, 'index'])
            ->name('index')
            ->middleware('permission:quality.ncrs.view');

        Route::get('/create', [NcrController::class, 'create'])
            ->name('create')
            ->middleware('permission:quality.ncrs.create');

        Route::post('/', [NcrController::class, 'store'])
            ->name('store')
            ->middleware('permission:quality.ncrs.create');

        Route::get('/{ncr}', [NcrController::class, 'show'])
            ->name('show')
            ->middleware('permission:quality.ncrs.view');

        Route::get('/{ncr}/edit', [NcrController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:quality.ncrs.update');

        Route::put('/{ncr}', [NcrController::class, 'update'])
            ->name('update')
            ->middleware('permission:quality.ncrs.update');

        Route::post('/{ncr}/submit', [NcrController::class, 'submit'])
            ->name('submit')
            ->middleware('permission:quality.ncrs.update');

        Route::post('/{ncr}/review', [NcrController::class, 'review'])
            ->name('review')
            ->middleware('permission:quality.ncrs.update');

        Route::post('/{ncr}/close', [NcrController::class, 'close'])
            ->name('close')
            ->middleware('permission:quality.ncrs.close');

        Route::get('/export', [NcrController::class, 'export'])
            ->name('export')
            ->middleware('permission:quality.ncrs.export');
    });

// Customer Complaint Routes
Route::middleware(['auth', 'verified'])
    ->prefix('customer-complaints')
    ->name('quality.complaints.')
    ->group(function (): void {
        Route::get('/', [CustomerComplaintController::class, 'index'])
            ->name('index')
            ->middleware('permission:quality.complaints.view');

        Route::get('/create', [CustomerComplaintController::class, 'create'])
            ->name('create')
            ->middleware('permission:quality.complaints.create');

        Route::post('/', [CustomerComplaintController::class, 'store'])
            ->name('store')
            ->middleware('permission:quality.complaints.create');

        Route::get('/{complaint}', [CustomerComplaintController::class, 'show'])
            ->name('show')
            ->middleware('permission:quality.complaints.view');

        Route::get('/{complaint}/edit', [CustomerComplaintController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:quality.complaints.update');

        Route::put('/{complaint}', [CustomerComplaintController::class, 'update'])
            ->name('update')
            ->middleware('permission:quality.complaints.update');

        Route::post('/{complaint}/close', [CustomerComplaintController::class, 'close'])
            ->name('close')
            ->middleware('permission:quality.complaints.close');

        Route::get('/export', [CustomerComplaintController::class, 'export'])
            ->name('export')
            ->middleware('permission:quality.complaints.export');
    });
```

### Route Model Binding

- NCR parameter: `{ncr}` → resolves to `Ncr` model via `id`.
- Complaint parameter: `{complaint}` → resolves to `CustomerComplaint` model via `id`.
- Custom key: default `id` (no `getRouteKeyName()` override needed).

---

## 2. NCR Request Payloads

### POST `/ncrs` (store)

```json
{
  "title": "Produk Cacat di Lini Produksi A",
  "source": "internal",
  "description": "Ditemukan 5 unit produk dengan dimensi tidak sesuai spesifikasi pada lini produksi A saat inspeksi shift pagi.",
  "site_id": 1,
  "department_id": 3,
  "product_service": "Panel Kontrol X-100",
  "batch_lot": "LOT-2026-0711-A",
  "customer_name": null,
  "severity_id": 1,
  "capa_action_id": null,
  "action": "save"
}
```

**Validation Rules (StoreNcrRequest):**

| Field | Rule | Notes |
|---|---|---|
| `title` | `required|string|max:255` | |
| `source` | `required|in:internal,external,customer_complaint,audit,supplier` | |
| `description` | `required|string|min:20` | |
| `site_id` | `required|exists:sites,id` | |
| `department_id` | `nullable|exists:departments,id` | |
| `product_service` | `nullable|string|max:255` | |
| `batch_lot` | `nullable|string|max:100` | |
| `customer_name` | `nullable|string|max:255` | |
| `severity_id` | `required|exists:severities,id` | |
| `capa_action_id` | `nullable|exists:capa_actions,id` | Link to CAPA module |
| `action` | `nullable|in:save,submit` | If `submit`, transitions to `under_review` |

**Controller behavior (store):**
1. Validate request
2. Create `Ncr` with `status = 'open'`
3. Generate `ncr_number` via `NumberingService::generate('quality', $actor, ...)`
4. Start workflow via `WorkflowService::start('quality', $ncr->id, $actor)`
5. If `action === 'submit'`: call `WorkflowService::transition('quality', $ncr->id, 'submit', $actor)` → status becomes `under_review`
6. `AuditService::created($ncr, $actor, 'quality', $ncr->id)`
7. `ActivityService::log('quality', $ncr->id, 'ncr.created', 'NCR dibuat', $actor)`
8. If submitted: `NotificationService::notifyMany($qhsseTeamUsers, 'quality.ncr.submitted', [...])`
9. Redirect to `quality.ncrs.show`

### PUT `/ncrs/{ncr}` (update)

Same payload as store, but:
- Only allowed if `status === 'open'` or `status === 'under_review'`
- `title`, `source`, `description`, `site_id`, `severity_id` are **sometimes** (not required for partial update)
- `root_cause`, `corrective_action`, `preventive_action` can be updated here
- `capa_action_id` can be set/updated here
- Records audit trail for changed fields via `AuditService::updated()`

**Additional validation for update:**

| Field | Rule | Notes |
|---|---|---|
| `root_cause` | `nullable|string` | RCA — required before close |
| `corrective_action` | `nullable|string` | Required before close |
| `preventive_action` | `nullable|string` | Required before close |

### POST `/ncrs/{ncr}/submit` (submit)

No request body needed. Controller:
1. Check `ncr.status === 'open'`
2. `WorkflowService::transition('quality', $ncr->id, 'submit', $actor)`
3. `ActivityService::log('quality', $ncr->id, 'ncr.submitted', ...)` 
4. `NotificationService::notifyMany($qhsseTeamUsers, 'quality.ncr.submitted', [...])`
5. Redirect back with flash message

### POST `/ncrs/{ncr}/review` (review)

No request body needed. Controller:
1. Check `ncr.status === 'under_review'`
2. `WorkflowService::transition('quality', $ncr->id, 'review', $actor)`
3. `ActivityService::log('quality', $ncr->id, 'ncr.reviewed', ...)`
4. Redirect back

### POST `/ncrs/{ncr}/close` (close)

No request body needed. Controller:
1. Check `ncr.status === 'in_progress'`
2. **Validate RCA fields are filled:**
   - `root_cause` must not be null/empty
   - `corrective_action` must not be null/empty
   - `preventive_action` must not be null/empty
3. `WorkflowService::transition('quality', $ncr->id, 'close', $actor)`
4. Set `$ncr->closed_at = now()`
5. `NotificationService::notify($reporter, 'quality.ncr.closed', [...])`
6. Redirect back with flash message

**Error if RCA not filled:**

```php
if (empty($ncr->root_cause) || empty($ncr->corrective_action) || empty($ncr->preventive_action)) {
    return back()->withErrors([
        'rca' => 'Root Cause, Corrective Action, dan Preventive Action wajib diisi sebelum menutup NCR.'
    ]);
}
```

---

## 3. Customer Complaint Request Payloads

### POST `/customer-complaints` (store)

```json
{
  "customer_name": "PT Maju Jaya",
  "customer_contact": "021-555-1234 (Bapak Andi)",
  "complaint_date": "2026-07-10",
  "description": "Pelanggan melaporkan 3 unit panel kontrol yang diterima dalam kondisi rusak. Komponen internal ditemukan lepas pada inspeksi penerimaan.",
  "severity_id": 2,
  "ncr_id": 1
}
```

**Validation Rules (StoreCustomerComplaintRequest):**

| Field | Rule | Notes |
|---|---|---|
| `customer_name` | `required|string|max:255` | |
| `customer_contact` | `nullable|string|max:255` | |
| `complaint_date` | `required|date` | |
| `description` | `required|string|min:20` | |
| `severity_id` | `required|exists:severities,id` | |
| `ncr_id` | `nullable|exists:ncrs,id` | Link to NCR |

**Controller behavior (store):**
1. Validate request
2. Create `CustomerComplaint` with `status = 'open'`
3. Generate `complaint_number` via `NumberingService::generate('quality', $actor, ...)`
4. Start workflow via `WorkflowService::start('quality_complaint', $complaint->id, $actor)`
5. `AuditService::created($complaint, $actor, 'quality_complaint', $complaint->id)`
6. `ActivityService::log('quality_complaint', $complaint->id, 'complaint.created', 'Keluhan dibuat', $actor)`
7. `NotificationService::notifyMany($qhsseTeamUsers, 'quality.complaint.created', [...])`
8. Redirect to `quality.complaints.show`

### PUT `/customer-complaints/{complaint}` (update)

Same payload as store, but:
- Only allowed if `status === 'open'` or `status === 'in_progress'`
- `resolution` can be updated here
- `ncr_id` can be set/updated/cleared here
- Records audit trail for changed fields

**Additional validation for update:**

| Field | Rule | Notes |
|---|---|---|
| `resolution` | `nullable|string` | Required before close |

### POST `/customer-complaints/{complaint}/close` (close)

No request body needed. Controller:
1. Check `complaint.status === 'in_progress'` (or `open`)
2. **Validate resolution is filled:**
   - `resolution` must not be null/empty
3. `WorkflowService::transition('quality_complaint', $complaint->id, 'close', $actor)`
4. Set `$complaint->resolved_at = now()`
5. `NotificationService::notify($reporter, 'quality.complaint.closed', [...])`
6. Redirect back with flash message

**Error if resolution not filled:**

```php
if (empty($complaint->resolution)) {
    return back()->withErrors([
        'resolution' => 'Resolusi wajib diisi sebelum menutup keluhan.'
    ]);
}
```

---

## 4. Inertia Response Props

### 4.1 NCR Index Page (`Quality/Ncr/Index.tsx`)

```typescript
{
  items: {
    data: Ncr[],
    current_page: number,
    last_page: number,
    per_page: number,
    total: number,
  },
  filters: {
    search: string,
    status: string | null,
    source: string | null,
    severity_id: number | null,
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

### 4.2 NCR Create/Edit Page (`Quality/Ncr/Form.tsx`)

```typescript
{
  item: Ncr | null,  // null for create, populated for edit
  sites: Site[],
  departments: Department[],
  severities: Severity[],
  capaActions: { id: number, capa_number: string, title: string }[],
}
```

### 4.3 NCR Show Page (`Quality/Ncr/Show.tsx`)

```typescript
{
  ncr: Ncr & {
    site: Site,
    department: Department | null,
    severity: Severity,
    capaAction: CapaAction | null,
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
  can: {
    update: boolean,
    close: boolean,
    export: boolean,
  },
}
```

### 4.4 Complaint Index Page (`Quality/Complaint/Index.tsx`)

```typescript
{
  items: {
    data: CustomerComplaint[],
    current_page: number,
    last_page: number,
    per_page: number,
    total: number,
  },
  filters: {
    search: string,
    status: string | null,
    severity_id: number | null,
    from: string | null,
    to: string | null,
  },
  can: {
    create: boolean,
    export: boolean,
  },
}
```

### 4.5 Complaint Create/Edit Page (`Quality/Complaint/Form.tsx`)

```typescript
{
  item: CustomerComplaint | null,
  severities: Severity[],
  ncrs: { id: number, ncr_number: string, title: string }[],
}
```

### 4.6 Complaint Show Page (`Quality/Complaint/Show.tsx`)

```typescript
{
  complaint: CustomerComplaint & {
    severity: Severity,
    ncr: Ncr | null,
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
  can: {
    update: boolean,
    close: boolean,
    export: boolean,
  },
}
```

---

## 5. ListQuery Parameters

### 5.1 NCR Index

The NCR index page accepts these query parameters:

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `ncr_number`, `title`, `product_service` (OR) |
| `status` | string | `null` | Filter: `open`, `under_review`, `in_progress`, `closed`, `rejected` |
| `source` | string | `null` | Filter: `internal`, `external`, `customer_complaint`, `audit`, `supplier` |
| `severity_id` | int | `null` | Filter by severity |
| `site_id` | int | `null` | Filter by site |
| `from` | date | `null` | Created date from (Y-m-d) |
| `to` | date | `null` | Created date to (Y-m-d) |
| `per_page` | int | `15` | Items per page (max 100) |
| `sort` | string | `created_at` | Sort column |
| `direction` | string | `desc` | Sort direction (asc/desc) |

**Controller index method pattern:**

```php
public function index(ListQuery $listQuery): Response
{
    $items = $listQuery->paginate(
        Ncr::query()->with(['site', 'severity', 'department']),
        ['ncr_number', 'title', 'product_service'],
        ['created_at', 'ncr_number', 'closed_at'],
        'created_at',
        15,
    );

    return Inertia::render('Modules/Quality/Ncr/Index', [
        'items' => $items,
        'filters' => $listQuery->filters(),
        'sites' => Site::where('is_active', true)->get(),
        'can' => [
            'create' => auth()->user()->can('quality.ncrs.create'),
            'export' => auth()->user()->can('quality.ncrs.export'),
        ],
    ]);
}
```

### 5.2 Complaint Index

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `complaint_number`, `customer_name`, `description` (OR) |
| `status` | string | `null` | Filter: `open`, `in_progress`, `closed` |
| `severity_id` | int | `null` | Filter by severity |
| `from` | date | `null` | Complaint date from |
| `to` | date | `null` | Complaint date to |
| `per_page` | int | `15` | Items per page (max 100) |
| `sort` | string | `complaint_date` | Sort column |
| `direction` | string | `desc` | Sort direction |

**Controller index method pattern:**

```php
public function index(ListQuery $listQuery): Response
{
    $items = $listQuery->paginate(
        CustomerComplaint::query()->with(['severity', 'ncr']),
        ['complaint_number', 'customer_name', 'description'],
        ['complaint_date', 'created_at'],
        'complaint_date',
        15,
    );

    return Inertia::render('Modules/Quality/Complaint/Index', [
        'items' => $items,
        'filters' => $listQuery->filters(),
        'can' => [
            'create' => auth()->user()->can('quality.complaints.create'),
            'export' => auth()->user()->can('quality.complaints.export'),
        ],
    ]);
}
```

---

## 6. CSV Export Specification

### 6.1 NCR Export

Endpoint: `GET /ncrs/export?search=...&status=...&source=...`

Applies same ListQuery filters, then streams CSV via `CsvExporter`.

**CSV Columns:**

| Column Header | Source |
|---|---|
| `Nomor NCR` | `ncr_number` |
| `Judul` | `title` |
| `Sumber` | `source` |
| `Severity` | `severity.name` |
| `Status` | `status` |
| `Site` | `site.name` |
| `Departemen` | `department.name` |
| `Produk/Jasa` | `product_service` |
| `Batch/Lot` | `batch_lot` |
| `Customer` | `customer_name` |
| `Tanggal Dibuat` | `created_at` |
| `Tanggal Ditutup` | `closed_at` |

**Controller export method pattern:**

```php
public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
{
    $query = $listQuery->apply(
        Ncr::query()->with(['site', 'severity', 'department']),
        ['ncr_number', 'title', 'product_service'],
        ['created_at', 'closed_at'],
        'created_at',
    );

    return $exporter->stream($query, [
        'Nomor NCR' => 'ncr_number',
        'Judul' => 'title',
        'Sumber' => 'source',
        'Severity' => fn ($item) => $item->severity?->name ?? '',
        'Status' => 'status',
        'Site' => fn ($item) => $item->site?->name ?? '',
        'Departemen' => fn ($item) => $item->department?->name ?? '',
        'Produk/Jasa' => fn ($item) => $item->product_service ?? '',
        'Batch/Lot' => fn ($item) => $item->batch_lot ?? '',
        'Customer' => fn ($item) => $item->customer_name ?? '',
        'Tanggal Dibuat' => fn ($item) => $item->created_at?->format('Y-m-d H:i') ?? '',
        'Tanggal Ditutup' => fn ($item) => $item->closed_at?->format('Y-m-d H:i') ?? '',
    ], 'ncrs-export.csv');
}
```

### 6.2 Customer Complaint Export

Endpoint: `GET /customer-complaints/export?search=...&status=...`

**CSV Columns:**

| Column Header | Source |
|---|---|
| `Nomor Keluhan` | `complaint_number` |
| `NCR Terkait` | `ncr.ncr_number` |
| `Nama Pelanggan` | `customer_name` |
| `Kontak` | `customer_contact` |
| `Tanggal Complaint` | `complaint_date` |
| `Severity` | `severity.name` |
| `Status` | `status` |
| `Tanggal Selesai` | `resolved_at` |
| `Resolusi` | `resolution` |

**Controller export method pattern:**

```php
public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
{
    $query = $listQuery->apply(
        CustomerComplaint::query()->with(['severity', 'ncr']),
        ['complaint_number', 'customer_name', 'description'],
        ['complaint_date', 'created_at'],
        'complaint_date',
    );

    return $exporter->stream($query, [
        'Nomor Keluhan' => 'complaint_number',
        'NCR Terkait' => fn ($item) => $item->ncr?->ncr_number ?? '',
        'Nama Pelanggan' => 'customer_name',
        'Kontak' => fn ($item) => $item->customer_contact ?? '',
        'Tanggal Complaint' => fn ($item) => $item->complaint_date?->format('Y-m-d') ?? '',
        'Severity' => fn ($item) => $item->severity?->name ?? '',
        'Status' => 'status',
        'Tanggal Selesai' => fn ($item) => $item->resolved_at?->format('Y-m-d H:i') ?? '',
        'Resolusi' => fn ($item) => $item->resolution ?? '',
    ], 'complaints-export.csv');
}
```

---

## 7. Error Responses

| Status | When | Response |
|---|---|---|
| `403` | User lacks required permission | Inertia redirects to dashboard with error flash (middleware handles) |
| `404` | NCR/Complaint ID not found | Laravel default 404 page |
| `422` | Validation failure | Inertia sends errors back to form with `$page.props.errors` |
| `400` | Invalid workflow transition | RuntimeException caught → redirect back with error flash |
| `400` | Close without RCA fields | Redirect back with errors on `rca` key |
| `400` | Close complaint without resolution | Redirect back with errors on `resolution` key |
| `419` | CSRF token expired | Laravel default |

### Invalid workflow transition handling:

```php
try {
    $this->workflowService->transition('quality', $ncr->id, 'close', $actor, $reason);
} catch (RuntimeException $e) {
    return back()->withErrors(['workflow' => $e->getMessage()]);
}
```

### RCA validation on close:

```php
if (empty($ncr->root_cause) || empty($ncr->corrective_action) || empty($ncr->preventive_action)) {
    return back()->withErrors([
        'rca' => 'Root Cause, Corrective Action, dan Preventive Action wajib diisi sebelum menutup NCR.'
    ]);
}
```

---

## 8. Permission Enforcement Points

| Layer | How |
|---|---|
| **Route middleware** | `->middleware('permission:quality.ncrs.view')` on each route |
| **Controller authorize()** | `$this->authorize('view', $ncr)` for show/edit (scope filtering) |
| **Inertia shared props** | `auth.permissions` array → frontend checks via `permissions.has('quality.ncrs.create')` |
| **Export** | Route middleware `permission:quality.ncrs.export` |
| **Close action** | Route middleware `permission:quality.ncrs.close` + business rule validation (RCA filled) |
| **Complaint close** | Route middleware `permission:quality.complaints.close` + business rule validation (resolution filled) |

---

## 9. Numbering Integration

### NCR Numbering

On `store`:

```php
$generatedNumber = app(NumberingService::class)->generate(
    moduleName: 'quality',
    actor: $actor,
    referenceType: Ncr::class,
    referenceId: $ncr->id,
);

$ncr->update(['ncr_number' => $generatedNumber->number]);
```

Numbering format (already seeded): `NCR-2026-0001`

### Customer Complaint Numbering

On `store`:

```php
$generatedNumber = app(NumberingService::class)->generate(
    moduleName: 'quality',
    actor: $actor,
    referenceType: CustomerComplaint::class,
    referenceId: $complaint->id,
);

$complaint->update(['complaint_number' => $generatedNumber->number]);
```

> Note: Both NCR and Complaint use the `quality` numbering module, producing `NCR-{YYYY}-{0001}` format. The numbering service maintains a single counter per module per year.

---

## 10. Workflow Integration

### NCR Workflow

The workflow definition `quality` needs to be added to `WorkflowSeeder` (or `QualityManagementSeeder`).

- Initial status: `open`
- Terminal statuses: `closed`, `rejected`

**Transitions used by this module:**

| Action | Controller Method | From | To | requires_reason |
|---|---|---|---|---|
| `submit` | `submit()` | `open` | `under_review` | false |
| `review` | `review()` | `under_review` | `in_progress` | false |
| `close` | `close()` | `in_progress` | `closed` | false (but RCA business rule check) |
| `reject` | (future) | `under_review` | `rejected` | true |

### Customer Complaint Workflow

The workflow definition `quality_complaint` needs to be added to `WorkflowSeeder` (or `QualityManagementSeeder`).

- Initial status: `open`
- Terminal statuses: `closed`

**Transitions:**

| Action | Controller Method | From | To | requires_reason |
|---|---|---|---|---|
| `start_review` | (auto on update) | `open` | `in_progress` | false |
| `close` | `close()` | `in_progress` | `closed` | false (but resolution business rule check) |

> Lihat [WORKFLOW.md](./WORKFLOW.md) untuk seeder code dan detail lengkap.

---

## 11. File Upload Integration

Evidence files are uploaded via the existing core `ManagedFileController` routes.

### NCR File Upload Flow:

1. User creates NCR → gets `ncr.id`
2. User uploads file via `POST /core/files` with:
   - `module_name`: `'quality'`
   - `reference_id`: `$ncr->id`
   - `collection`: `'evidence'` (or `'photos'`, `'documents'`)
   - `file`: the UploadedFile
3. `ManagedFileService::store($file, new FileReference('quality', $ncr->id, 'evidence'), $uploader)`
4. File stored on `local` disk at `managed-files/quality/{id}/evidence/{uuid}.{ext}`
5. Download via `GET /core/files/{managedFile}/download`

### Complaint File Upload Flow:

1. User creates complaint → gets `complaint.id`
2. User uploads file via `POST /core/files` with:
   - `module_name`: `'quality_complaint'`
   - `reference_id`: `$complaint->id`
   - `collection`: `'evidence'` (or `'resolution'`)
   - `file`: the UploadedFile
3. Same storage and download pattern as NCR.

### Show page loads evidence:

**NCR Show:**

```php
'evidence' => ManagedFile::query()
    ->where('module_name', 'quality')
    ->where('reference_id', $ncr->id)
    ->whereNull('deleted_at')
    ->get(),
```

**Complaint Show:**

```php
'evidence' => ManagedFile::query()
    ->where('module_name', 'quality_complaint')
    ->where('reference_id', $complaint->id)
    ->whereNull('deleted_at')
    ->get(),
```

---

## 12. Integration Points

### 12.1 NCR → CAPA (Module 04)

- NCR table has `capa_action_id` FK to `capa_actions.id`
- When set, NCR show page displays CAPA info (number, title, status)
- "Buat CAPA Baru dari NCR ini" button on NCR show page
- CAPA show page can display "Related NCRs" via reverse lookup

### 12.2 Customer Complaint → NCR

- `customer_complaints.ncr_id` FK to `ncrs.id`
- When set, complaint show page displays NCR info (number, title, status)
- "Buat NCR dari Keluhan ini" button on complaint show page
- NCR show page can display "Related Complaints" via reverse lookup

### 12.3 NCR ← Audit (Module 06)

- NCR with `source = 'audit'` references audit findings
- In Phase 1, the link is informational only (mentioned in description)
- Phase 2: add `audit_finding_id` FK to `ncrs` table

### 12.4 Document Control (Module 07)

- Quality-related documents (SOPs, work instructions) can be attached as files
- No direct FK — uses file attachment pattern via `managed_files`

### 12.5 Dashboard (Module 01)

- Dashboard widgets receive data via API or shared props
- NCR and Complaint metrics are computed from the database
