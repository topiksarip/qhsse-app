# API Contract — Inspection Checklist

> Spec ini mendefinisikan semua route, request, response, dan error handling untuk modul Inspection Checklist.

## 1. Route Table

Module ini memiliki dua kelompok resource: **Templates** dan **Inspections**.

### 1.1 Template Routes

Prefix: `/inspection-templates`, name: `inspection.templates.*`, middleware: `auth,verified`.

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/inspection-templates` | `index` | `inspection.templates.index` | `inspection.checklists.view` | List templates with search/filter/pagination |
| GET | `/inspection-templates/create` | `create` | `inspection.templates.create` | `inspection.checklists.create` | Render create template form |
| POST | `/inspection-templates` | `store` | `inspection.templates.store` | `inspection.checklists.create` | Save new template with items |
| GET | `/inspection-templates/{template}` | `show` | `inspection.templates.show` | `inspection.checklists.view` | Show template detail with items |
| GET | `/inspection-templates/{template}/edit` | `edit` | `inspection.templates.edit` | `inspection.checklists.update` | Render edit template form |
| PUT/PATCH | `/inspection-templates/{template}` | `update` | `inspection.templates.update` | `inspection.checklists.update` | Update template with items |
| DELETE | `/inspection-templates/{template}` | `destroy` | `inspection.templates.destroy` | `inspection.checklists.delete` | Delete template (if no inspections) |
| GET | `/inspection-templates/export` | `export` | `inspection.templates.export` | `inspection.checklists.export` | Export templates as CSV |

### 1.2 Inspection Routes

Prefix: `/inspections`, name: `inspection.inspections.*`, middleware: `auth,verified`.

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/inspections` | `index` | `inspection.inspections.index` | `inspection.results.view` | List inspections with search/filter/pagination |
| GET | `/inspections/create` | `create` | `inspection.inspections.create` | `inspection.checklists.execute` | Render create inspection form |
| POST | `/inspections` | `store` | `inspection.inspections.store` | `inspection.checklists.execute` | Save new inspection (pending) |
| GET | `/inspections/{inspection}` | `show` | `inspection.inspections.show` | `inspection.results.view` | Show inspection detail with results |
| GET | `/inspections/{inspection}/execute` | `execute` | `inspection.inspections.execute` | `inspection.checklists.execute` | Render execute inspection form |
| PUT/PATCH | `/inspections/{inspection}` | `update` | `inspection.inspections.update` | `inspection.checklists.execute` | Save inspection results (partial save) |
| POST | `/inspections/{inspection}/start` | `start` | `inspection.inspections.start` | `inspection.checklists.execute` | Transition pending → in_progress |
| POST | `/inspections/{inspection}/complete` | `complete` | `inspection.inspections.complete` | `inspection.checklists.execute` | Transition in_progress → completed |
| GET | `/inspections/export` | `export` | `inspection.inspections.export` | `inspection.checklists.export` | Export inspections as CSV |

### Route Registration

File: `routes/modules.php`

```php
use App\Http\Controllers\Modules\Inspection\InspectionTemplateController;
use App\Http\Controllers\Modules\Inspection\InspectionController;

// Template routes
Route::middleware(['auth', 'verified'])
    ->prefix('inspection-templates')
    ->name('inspection.templates.')
    ->group(function (): void {
        Route::get('/', [InspectionTemplateController::class, 'index'])
            ->name('index')
            ->middleware('permission:inspection.checklists.view');

        Route::get('/create', [InspectionTemplateController::class, 'create'])
            ->name('create')
            ->middleware('permission:inspection.checklists.create');

        Route::post('/', [InspectionTemplateController::class, 'store'])
            ->name('store')
            ->middleware('permission:inspection.checklists.create');

        Route::get('/{template}', [InspectionTemplateController::class, 'show'])
            ->name('show')
            ->middleware('permission:inspection.checklists.view');

        Route::get('/{template}/edit', [InspectionTemplateController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:inspection.checklists.update');

        Route::put('/{template}', [InspectionTemplateController::class, 'update'])
            ->name('update')
            ->middleware('permission:inspection.checklists.update');

        Route::delete('/{template}', [InspectionTemplateController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:inspection.checklists.delete');

        Route::get('/export', [InspectionTemplateController::class, 'export'])
            ->name('export')
            ->middleware('permission:inspection.checklists.export');
    });

// Inspection routes
Route::middleware(['auth', 'verified'])
    ->prefix('inspections')
    ->name('inspection.inspections.')
    ->group(function (): void {
        Route::get('/', [InspectionController::class, 'index'])
            ->name('index')
            ->middleware('permission:inspection.results.view');

        Route::get('/create', [InspectionController::class, 'create'])
            ->name('create')
            ->middleware('permission:inspection.checklists.execute');

        Route::post('/', [InspectionController::class, 'store'])
            ->name('store')
            ->middleware('permission:inspection.checklists.execute');

        Route::get('/{inspection}', [InspectionController::class, 'show'])
            ->name('show')
            ->middleware('permission:inspection.results.view');

        Route::get('/{inspection}/execute', [InspectionController::class, 'execute'])
            ->name('execute')
            ->middleware('permission:inspection.checklists.execute');

        Route::put('/{inspection}', [InspectionController::class, 'update'])
            ->name('update')
            ->middleware('permission:inspection.checklists.execute');

        Route::post('/{inspection}/start', [InspectionController::class, 'start'])
            ->name('start')
            ->middleware('permission:inspection.checklists.execute');

        Route::post('/{inspection}/complete', [InspectionController::class, 'complete'])
            ->name('complete')
            ->middleware('permission:inspection.checklists.execute');

        Route::get('/export', [InspectionController::class, 'export'])
            ->name('export')
            ->middleware('permission:inspection.checklists.export');
    });
```

### Route Model Binding

- Template parameter: `{template}` → `InspectionTemplate` model via `id`.
- Inspection parameter: `{inspection}` → `Inspection` model via `id`.

---

## 2. Request Payloads

### 2.1 POST `/inspection-templates` (store template)

```json
{
  "code": "SAF-001",
  "name": "Inspeksi Safety Harian",
  "description": "Template inspeksi keselamatan kerja harian untuk area produksi.",
  "category": "safety",
  "is_active": true,
  "items": [
    {
      "question": "Apakah semua pekerja memakai APD?",
      "type": "yes_no",
      "category": "PPE",
      "is_required": true,
      "order": 1
    },
    {
      "question": "Apakah fire extinguisher dalam kondisi baik?",
      "type": "safe_unsafe",
      "category": "Fire Safety",
      "is_required": true,
      "order": 2
    },
    {
      "question": "Rate kebersihan area kerja (1-5)",
      "type": "scale",
      "category": "Housekeeping",
      "is_required": false,
      "order": 3
    }
  ]
}
```

**Validation Rules (StoreInspectionTemplateRequest):**

| Field | Rule | Notes |
|---|---|---|
| `code` | `required|string|max:50|unique:inspection_templates,code` | |
| `name` | `required|string|max:255` | |
| `description` | `nullable|string` | |
| `category` | `required|in:safety,environment,equipment,fire,housekeeping,security,quality,compliance` | |
| `is_active` | `boolean` | Default `true` |
| `items` | `required|array|min:1` | At least 1 item |
| `items.*.question` | `required|string` | |
| `items.*.type` | `required|in:yes_no,safe_unsafe,na,scale,text` | |
| `items.*.category` | `nullable|string|max:50` | Item-level category for grouping |
| `items.*.is_required` | `boolean` | Default `true` |
| `items.*.order` | `integer|min:0` | Default `0` |

**Controller behavior (store):**
1. Validate request
2. Create `InspectionTemplate`
3. Create `InspectionItem` records for each item
4. `AuditService::created($template, $actor, 'inspection', $template->id)`
5. `ActivityService::log('inspection', $template->id, 'template.created', ...)`
6. Redirect to `inspection.templates.show`

### 2.2 PUT `/inspection-templates/{template}` (update template)

Same payload as store. Controller:
1. Validate request
2. Update template fields
3. Sync items: update existing, create new, delete removed (only if no inspection_results reference them)
4. `AuditService::updated($template, $oldValues, $actor, 'inspection', $template->id)`
5. Redirect to `inspection.templates.show`

### 2.3 POST `/inspections` (store inspection)

```json
{
  "inspection_template_id": 1,
  "site_id": 1,
  "area_id": 2,
  "inspector_id": 5,
  "scheduled_at": "2026-07-11",
  "notes": "Inspeksi rutin mingguan."
}
```

**Validation Rules (StoreInspectionRequest):**

| Field | Rule | Notes |
|---|---|---|
| `inspection_template_id` | `required|exists:inspection_templates,id` | Template must be active |
| `site_id` | `required|exists:sites,id` | |
| `area_id` | `nullable|exists:areas,id` | |
| `inspector_id` | `required|exists:users,id` | User must have inspector role |
| `scheduled_at` | `required|date` | |
| `notes` | `nullable|string` | |

**Controller behavior (store):**
1. Validate request
2. Verify template `is_active=true`
3. Create `Inspection` with `status='pending'`, `overall_result='pending'`
4. Generate `inspection_number` via `NumberingService::generate('inspection', $actor)`
5. Start workflow via `WorkflowService::start('inspection', $inspection->id, $actor)`
6. Create empty `InspectionResult` records for each item in the template (answer=NULL)
7. `AuditService::created($inspection, $actor, 'inspection', $inspection->id)`
8. `ActivityService::log('inspection', $inspection->id, 'inspection.created', ...)`
9. Redirect to `inspection.inspections.show`

### 2.4 PUT `/inspections/{inspection}` (save inspection results — partial save)

```json
{
  "results": [
    {
      "inspection_item_id": 1,
      "answer": "yes",
      "remark": "Semua pekerja memakai APD lengkap."
    },
    {
      "inspection_item_id": 2,
      "answer": "unsafe",
      "remark": "APAR di area produksi sudah kedaluwarsa (exp: 06/2026)."
    },
    {
      "inspection_item_id": 3,
      "answer": "4",
      "remark": "Area cukup bersih, perlu perbaikan di zona B."
    }
  ],
  "notes": "Inspeksi umum berjalan baik. Dua item perlu tindak lanjut."
}
```

**Validation Rules (UpdateInspectionRequest):**

| Field | Rule | Notes |
|---|---|---|
| `results` | `nullable|array` | |
| `results.*.inspection_item_id` | `required|exists:inspection_items,id` | Must belong to the same template |
| `results.*.answer` | `nullable|string|max:255` | Validated against item type at controller |
| `results.*.remark` | `nullable|string` | |
| `notes` | `nullable|string` | |

**Controller behavior (update):**
1. Check `inspection.status === 'in_progress'` (can only save results when in progress)
2. For each result:
   - Find or create `InspectionResult` by `inspection_id` + `inspection_item_id`
   - Update `answer` and `remark`
   - Auto-calculate `is_unsafe` based on item type and answer:
     - `safe_unsafe` + `unsafe` → `is_unsafe=true`
     - `yes_no` + `no` → `is_unsafe=true`
     - `scale` + `1` or `2` → `is_unsafe=true`
     - Otherwise → `is_unsafe=false`
   - `AuditService::log('result.saved', ...)`
3. Update `notes` if provided
4. Return JSON response (not redirect — this is an AJAX save for the execute form)

### 2.5 POST `/inspections/{inspection}/start` (start inspection)

No request body needed. Controller:
1. Check `inspection.status === 'pending'`
2. `WorkflowService::transition('inspection', $inspection->id, 'start', $actor)`
3. Set `executed_at = now()`
4. `ActivityService::log('inspection', $inspection->id, 'inspection.started', ...)`
5. Redirect to `inspection.inspections.execute`

### 2.6 POST `/inspections/{inspection}/complete` (complete inspection)

```json
{
  "notes": "Inspeksi umum berjalan baik. Dua item perlu tindak lanjut."
}
```

| Field | Rule | Notes |
|---|---|---|
| `notes` | `nullable|string` | Final notes |

**Controller behavior (complete):**
1. Check `inspection.status === 'in_progress'`
2. Validate all required items have non-null answers:
   - Query `InspectionResult` where `inspection_id` and `item.is_required=true` and `answer IS NULL`
   - If count > 0, return 422 with error: "Masih ada item wajib yang belum dijawab"
3. Calculate `overall_result`:
   - If any `InspectionResult` where `is_unsafe=true` → `overall_result='fail'`
   - Else → `overall_result='pass'`
4. `WorkflowService::transition('inspection', $inspection->id, 'complete', $actor)`
5. Update `overall_result` and `notes`
6. `ActivityService::log('inspection', $inspection->id, 'inspection.completed', ...)`
7. If `overall_result='fail'`: `NotificationService::notifyMany($qhsseManagers, 'inspection.unsafe_found', [...])`
8. `NotificationService::notifyMany($qhsseManagers, 'inspection.completed', [...])`
9. Redirect to `inspection.inspections.show`

---

## 3. Inertia Response Props

### 3.1 Template Index Page (`Inspection/Template/Index.tsx`)

```typescript
{
  templates: {
    data: InspectionTemplate[],
    current_page: number,
    last_page: number,
    per_page: number,
    total: number,
  },
  filters: {
    search: string,
    category: string | null,
    is_active: string | null,
  },
  can: {
    create: boolean,
    update: boolean,
    delete: boolean,
    export: boolean,
  },
}
```

### 3.2 Template Form Page (`Inspection/Template/Form.tsx`)

```typescript
{
  template: InspectionTemplate | null,  // null for create
  items: InspectionItem[],
}
```

### 3.3 Template Show Page (`Inspection/Template/Show.tsx`)

```typescript
{
  template: InspectionTemplate & {
    items: InspectionItem[],
  },
  inspectionsCount: number,  // number of inspections using this template
  can: {
    update: boolean,
    delete: boolean,
  },
}
```

### 3.4 Inspection Index Page (`Inspection/Inspection/Index.tsx`)

```typescript
{
  inspections: {
    data: Inspection[],
    current_page: number,
    last_page: number,
    per_page: number,
    total: number,
  },
  filters: {
    search: string,
    status: string | null,
    template_id: number | null,
    site_id: number | null,
    overall_result: string | null,
    from: string | null,
    to: string | null,
  },
  templates: InspectionTemplate[],  // for filter dropdown
  sites: Site[],  // for filter dropdown
  can: {
    create: boolean,
    execute: boolean,
    export: boolean,
    createCapa: boolean,
  },
}
```

### 3.5 Inspection Create Page (`Inspection/Inspection/Create.tsx`)

```typescript
{
  templates: InspectionTemplate[],  // active templates only
  sites: Site[],
  areas: Area[],
  inspectors: User[],  // users with QHSSE Officer/Manager role
}
```

### 3.6 Inspection Execute Page (`Inspection/Inspection/Execute.tsx`)

```typescript
{
  inspection: Inspection & {
    template: InspectionTemplate & {
      items: InspectionItem[],
    },
    site: Site,
    area: Area | null,
    inspector: User,
    results: InspectionResult[],  // keyed by inspection_item_id
  },
  can: {
    complete: boolean,
  },
}
```

### 3.7 Inspection Show Page (`Inspection/Inspection/Show.tsx`)

```typescript
{
  inspection: Inspection & {
    template: InspectionTemplate,
    site: Site,
    area: Area | null,
    inspector: User,
    results: (InspectionResult & {
      item: InspectionItem,
    })[],
  },
  evidence: ManagedFile[],
  activities: ActivityLog[],
  workflowHistory: WorkflowHistory[],
  unsafeCount: number,
  capaLinks: {
    createFromInspection: boolean,
    createFromItem: boolean,
  },
  can: {
    execute: boolean,
    createCapa: boolean,
    export: boolean,
  },
}
```

---

## 4. ListQuery Parameters

### 4.1 Template Index

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `code` and `name` (OR) |
| `category` | string | `null` | Filter by category |
| `is_active` | string | `null` | Filter: `active`, `inactive` |
| `per_page` | int | `15` | Items per page (max 100) |
| `sort` | string | `created_at` | Sort column |
| `direction` | string | `desc` | Sort direction (asc/desc) |

### Controller index method (templates):

```php
public function index(ListQuery $listQuery): Response
{
    $items = $listQuery->paginate(
        InspectionTemplate::query()->withCount('items'),
        ['code', 'name'],
        ['created_at', 'name', 'code'],
        'created_at',
        15,
    );

    return Inertia::render('Modules/Inspection/Template/Index', [
        'templates' => $items,
        'filters' => $listQuery->filters(),
    ]);
}
```

### 4.2 Inspection Index

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `inspection_number` and `template.name` |
| `status` | string | `null` | Filter: `pending`, `in_progress`, `completed` |
| `template_id` | int | `null` | Filter by template |
| `site_id` | int | `null` | Filter by site |
| `overall_result` | string | `null` | Filter: `pass`, `fail`, `pending` |
| `from` | string | `null` | Date range start (scheduled_at) |
| `to` | string | `null` | Date range end (scheduled_at) |
| `per_page` | int | `15` | Items per page (max 100) |
| `sort` | string | `scheduled_at` | Sort column |
| `direction` | string | `desc` | Sort direction |

### Controller index method (inspections):

```php
public function index(ListQuery $listQuery): Response
{
    $items = $listQuery->paginate(
        Inspection::query()->with(['template', 'site', 'inspector'])
            ->withCount(['results as unsafe_count' => function ($q) {
                $q->where('is_unsafe', true);
            }]),
        ['inspection_number'],
        ['scheduled_at', 'created_at', 'inspection_number'],
        'scheduled_at',
        15,
    );

    return Inertia::render('Modules/Inspection/Inspection/Index', [
        'inspections' => $items,
        'filters' => $listQuery->filters(),
        'templates' => InspectionTemplate::where('is_active', true)->get(),
        'sites' => Site::where('is_active', true)->get(),
    ]);
}
```

---

## 5. CSV Export Specification

### 5.1 Template Export

Endpoint: `GET /inspection-templates/export?search=...&category=...`

```php
public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
{
    $query = $listQuery->apply(
        InspectionTemplate::query()->withCount('items'),
        ['code', 'name'],
        ['created_at', 'name'],
        'created_at',
    );

    return $exporter->stream($query, [
        'Kode' => 'code',
        'Nama' => 'name',
        'Kategori' => 'category',
        'Jumlah Item' => fn ($item) => $item->items_count ?? 0,
        'Status' => fn ($item) => $item->is_active ? 'Aktif' : 'Nonaktif',
        'Dibuat Pada' => fn ($item) => $item->created_at?->format('Y-m-d H:i') ?? '',
    ], 'inspection-templates-export.csv');
}
```

### CSV Columns (Templates):

| Column Header | Source |
|---|---|
| `Kode` | `code` |
| `Nama` | `name` |
| `Kategori` | `category` |
| `Jumlah Item` | `items_count` |
| `Status` | `is_active` (Aktif/Nonaktif) |
| `Dibuat Pada` | `created_at` |

### 5.2 Inspection Export

Endpoint: `GET /inspections/export?search=...&status=...`

```php
public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
{
    $query = $listQuery->apply(
        Inspection::query()->with(['template', 'site', 'area', 'inspector'])
            ->withCount(['results as unsafe_count' => function ($q) {
                $q->where('is_unsafe', true);
            }]),
        ['inspection_number'],
        ['scheduled_at', 'created_at'],
        'scheduled_at',
    );

    return $exporter->stream($query, [
        'Nomor' => 'inspection_number',
        'Template' => fn ($item) => $item->template?->name ?? '',
        'Site' => fn ($item) => $item->site?->name ?? '',
        'Area' => fn ($item) => $item->area?->name ?? '',
        'Inspector' => fn ($item) => $item->inspector?->name ?? '',
        'Jadwal' => fn ($item) => $item->scheduled_at?->format('Y-m-d') ?? '',
        'Dieksekusi' => fn ($item) => $item->executed_at?->format('Y-m-d H:i') ?? '',
        'Status' => 'status',
        'Hasil' => 'overall_result',
        'Jumlah Unsafe' => fn ($item) => $item->unsafe_count ?? 0,
        'Catatan' => 'notes',
    ], 'inspections-export.csv');
}
```

---

## 6. Error Responses

| Status | When | Response |
|---|---|---|
| `403` | User lacks required permission | Inertia redirects to dashboard with error flash |
| `404` | Template/Inspection ID not found | Laravel default 404 page |
| `422` | Validation failure | Inertia sends errors back to form with `$page.props.errors` |
| `400` | Invalid workflow transition | RuntimeException caught → redirect back with error flash |
| `419` | CSRF token expired | Laravel default |

### Invalid workflow transition handling:

```php
try {
    $this->workflowService->transition('inspection', $inspection->id, 'start', $actor);
} catch (RuntimeException $e) {
    return back()->withErrors(['workflow' => $e->getMessage()]);
}
```

### Required items not answered on complete:

```php
$unansweredRequired = $inspection->results()
    ->whereHas('item', fn ($q) => $q->where('is_required', true))
    ->whereNull('answer')
    ->count();

if ($unansweredRequired > 0) {
    return back()->withErrors([
        'complete' => "Masih ada {$unansweredRequired} item wajib yang belum dijawab."
    ]);
}
```

---

## 7. Permission Enforcement Points

| Layer | How |
|---|---|
| **Route middleware** | `->middleware('permission:inspection.checklists.view')` on each route |
| **Controller authorize()** | `$this->authorize('view', $inspection)` for show (scope filtering) |
| **Inertia shared props** | `auth.permissions` array → frontend checks via `permissions.has('inspection.checklists.create')` |
| **Export** | Route middleware `permission:inspection.checklists.export` |
| **Template delete** | Check no inspections reference template before allowing delete |

---

## 8. Numbering Integration

On `store` (inspection):

```php
$generatedNumber = app(NumberingService::class)->generate(
    moduleName: 'inspection',
    actor: $actor,
    referenceType: Inspection::class,
    referenceId: $inspection->id,
);

$inspection->update(['inspection_number' => $generatedNumber->number]);
```

Numbering format (already seeded): `INS-2026-0001`

---

## 9. Workflow Integration

The workflow definition `inspection` needs to be seeded in `WorkflowSeeder` (see WORKFLOW.md).

### Transitions used by this module:

| Action | Controller Method | From | To | requires_reason |
|---|---|---|---|---|
| `start` | `start()` | pending | in_progress | false |
| `complete` | `complete()` | in_progress | completed | false |

### Phase 4 simplified workflow path:

```
pending ──(start)──→ in_progress ──(complete)──→ completed
```

---

## 10. CAPA Integration

When an inspection has items with `is_unsafe=true` and is `completed`:

### CAPA Link on Show Page:

The Show page renders a "Buat CAPA" button that links to:

```
GET /capa/create?source_module=inspection&source_reference_id={inspection.id}&item_id={result.id}
```

The CAPA module's create form reads these query params and pre-fills:
- `source_module`: `inspection`
- `source_reference_id`: `{inspection.id}`
- `description`: Auto-generated from the unsafe item details (question, answer, remark)

### Multiple CAPA per Inspection:

One inspection can have multiple CAPA records — one per unsafe item, or one combined. The link passes `item_id` to pre-fill from a specific item, or without `item_id` for a combined CAPA.

---

## 11. File Upload Integration

Evidence files are uploaded via the existing core `ManagedFileController` routes.

### Upload flow:

1. User creates inspection → gets `inspection.id`
2. User uploads file via `POST /core/files` with:
   - `module_name`: `inspection`
   - `reference_id`: `$inspection->id`
   - `collection`: `evidence`
   - `file`: the UploadedFile
3. `ManagedFileService::store($file, new FileReference('inspection', $inspection->id, 'evidence'), $uploader)`
4. File stored on `local` disk at `inspection/{id}/evidence/{uuid}.{ext}`
5. Download via `GET /core/files/{managedFile}/download`

### Show page loads evidence:

```php
'evidence' => ManagedFile::query()
    ->where('module_name', 'inspection')
    ->where('reference_id', $inspection->id)
    ->where('collection', 'evidence')
    ->whereNull('deleted_at')
    ->get(),
```
