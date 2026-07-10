# API Contract — Environmental Management

> Spec ini mendefinisikan semua route, request, response, dan error handling untuk modul Environmental Management.

## 1. Route Table

Semua route diawali dengan prefix `/environmental-records`, nama route `environmental-records.*`, dan middleware `auth,verified`.

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/environmental-records` | `index` | `environmental-records.index` | `environment.records.view` | List records with search/filter/pagination |
| GET | `/environmental-records/create` | `create` | `environmental-records.create` | `environment.records.create` | Render create form |
| POST | `/environmental-records` | `store` | `environmental-records.store` | `environment.records.create` | Save new record |
| GET | `/environmental-records/{record}` | `show` | `environmental-records.show` | `environment.records.view` | Show record detail |
| GET | `/environmental-records/{record}/edit` | `edit` | `environmental-records.edit` | `environment.records.update` | Render edit form |
| PUT | `/environmental-records/{record}` | `update` | `environmental-records.update` | `environment.records.update` | Update record |
| POST | `/environmental-records/{record}/investigate` | `investigate` | `environmental-records.investigate` | `environment.records.investigate` | Transition → investigated |
| POST | `/environmental-records/{record}/open-action` | `openAction` | `environmental-records.open-action` | `environment.records.investigate` | Open CAPA → action_open |
| POST | `/environmental-records/{record}/close` | `close` | `environmental-records.close` | `environment.records.close` | Close record (requires reason) |
| GET | `/environmental-records/export` | `export` | `environmental-records.export` | `environment.records.export` | Export filtered list as CSV |

### Route Registration

File: `routes/modules.php`

```php
use App\Http\Controllers\Modules\Environmental\EnvironmentalRecordController;

Route::middleware(['auth', 'verified'])
    ->prefix('environmental-records')
    ->name('environmental-records.')
    ->group(function (): void {
        Route::get('/', [EnvironmentalRecordController::class, 'index'])
            ->name('index')
            ->middleware('permission:environment.records.view');

        Route::get('/create', [EnvironmentalRecordController::class, 'create'])
            ->name('create')
            ->middleware('permission:environment.records.create');

        Route::post('/', [EnvironmentalRecordController::class, 'store'])
            ->name('store')
            ->middleware('permission:environment.records.create');

        Route::get('/{record}', [EnvironmentalRecordController::class, 'show'])
            ->name('show')
            ->middleware('permission:environment.records.view');

        Route::get('/{record}/edit', [EnvironmentalRecordController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:environment.records.update');

        Route::put('/{record}', [EnvironmentalRecordController::class, 'update'])
            ->name('update')
            ->middleware('permission:environment.records.update');

        Route::post('/{record}/investigate', [EnvironmentalRecordController::class, 'investigate'])
            ->name('investigate')
            ->middleware('permission:environment.records.investigate');

        Route::post('/{record}/open-action', [EnvironmentalRecordController::class, 'openAction'])
            ->name('open-action')
            ->middleware('permission:environment.records.investigate');

        Route::post('/{record}/close', [EnvironmentalRecordController::class, 'close'])
            ->name('close')
            ->middleware('permission:environment.records.close');

        Route::get('/export', [EnvironmentalRecordController::class, 'export'])
            ->name('export')
            ->middleware('permission:environment.records.export');
    });
```

### Route Model Binding

- Parameter name: `{record}` → Laravel resolves to `EnvironmentalRecord` model via route key (id).
- Custom key: default `id` (no need for `getRouteKeyName()` override).

---

## 2. Request Payloads

### POST `/environmental-records` (store)

```json
{
  "type": "emission",
  "title": "Emisi SOx Stack #1",
  "description": "Pengukuran emisi SOx dari stack #1 menunjukkan nilai melebihi batas regulasi.",
  "site_id": 1,
  "area_id": 2,
  "occurred_at": "2026-07-11T14:30:00",
  "measured_value": 450.0000,
  "unit": "mg/m³",
  "limit_value": 300.0000,
  "parameter": "SOx"
}
```

**Validation Rules (StoreEnvironmentalRecordRequest):**

| Field | Rule | Notes |
|---|---|---|
| `type` | `required|in:waste,spill,emission,noise,water_monitoring,other` | |
| `title` | `required|string|max:255` | |
| `description` | `required|string` | |
| `site_id` | `required|exists:sites,id` | |
| `area_id` | `nullable|exists:areas,id` | |
| `occurred_at` | `nullable|date` | |
| `measured_value` | `nullable|numeric|min:0` | Required for emission, noise, water_monitoring |
| `unit` | `nullable|string|max:50` | |
| `limit_value` | `nullable|numeric|min:0` | Required for emission, water_monitoring |
| `waste_type` | `nullable|string|max:255` | Required if type=waste |
| `quantity` | `nullable|numeric|min:0` | Required if type=waste |
| `disposal_method` | `nullable|string|max:255` | Required if type=waste |
| `material` | `nullable|string|max:255` | Required if type=spill |
| `volume` | `nullable|numeric|min:0` | Required if type=spill |
| `containment` | `nullable|string|max:255` | Required if type=spill |
| `parameter` | `nullable|string|max:255` | Required if type=emission or water_monitoring |
| `location` | `nullable|string|max:255` | Required if type=noise |

**Conditional Validation (in FormRequest):**

```php
public function rules(): array
{
    $rules = [
        'type'            => 'required|in:waste,spill,emission,noise,water_monitoring,other',
        'title'           => 'required|string|max:255',
        'description'     => 'required|string',
        'site_id'         => 'required|exists:sites,id',
        'area_id'         => 'nullable|exists:areas,id',
        'occurred_at'     => 'nullable|date',
        'measured_value'  => 'nullable|numeric|min:0',
        'unit'            => 'nullable|string|max:50',
        'limit_value'     => 'nullable|numeric|min:0',
        'waste_type'      => 'nullable|string|max:255',
        'quantity'        => 'nullable|numeric|min:0',
        'disposal_method' => 'nullable|string|max:255',
        'material'        => 'nullable|string|max:255',
        'volume'          => 'nullable|numeric|min:0',
        'containment'     => 'nullable|string|max:255',
        'parameter'       => 'nullable|string|max:255',
        'location'        => 'nullable|string|max:255',
    ];

    // Type-specific conditional rules
    $type = $this->input('type');

    return match ($type) {
        'waste' => array_merge($rules, [
            'waste_type'      => 'required|string|max:255',
            'quantity'        => 'required|numeric|min:0',
            'disposal_method' => 'required|string|max:255',
        ]),
        'spill' => array_merge($rules, [
            'material'    => 'required|string|max:255',
            'volume'      => 'required|numeric|min:0',
            'containment' => 'required|string|max:255',
        ]),
        'emission' => array_merge($rules, [
            'parameter'      => 'required|string|max:255',
            'measured_value'  => 'required|numeric|min:0',
            'limit_value'     => 'required|numeric|min:0',
        ]),
        'noise' => array_merge($rules, [
            'measured_value' => 'required|numeric|min:0',
            'location'       => 'required|string|max:255',
            'occurred_at'     => 'required|date',
            'limit_value'    => 'required|numeric|min:0',
        ]),
        'water_monitoring' => array_merge($rules, [
            'parameter'      => 'required|string|max:255',
            'measured_value'  => 'required|numeric|min:0',
            'limit_value'     => 'required|numeric|min:0',
        ]),
        default => $rules, // 'other' — all optional
    };
}
```

**Controller behavior (store):**

1. Validate request
2. Create `EnvironmentalRecord` with `reporter_id` = auth user, `status` = `'recorded'`
3. Generate `record_number` via `NumberingService::generate('environment', $actor, ...)`
4. Auto-calculate `is_exceedance`: if `measured_value` AND `limit_value` not null AND `measured_value > limit_value` → `true`
5. `AuditService::created($record, $actor, 'environment', $record->id)`
6. `ActivityService::log('environment', $record->id, 'environment.created', ...)`
7. If `is_exceedance = true`: `NotificationService::notifyMany()` to QHSSE team
8. Redirect to `environmental-records.show`

### PUT `/environmental-records/{record}` (update)

Same payload as store, but:

- Only allowed if `status` is `recorded` or `investigated`
- `type` cannot be changed after creation (locked)
- Records audit trail for changed fields via `AuditService::updated()`
- Re-evaluates `is_exceedance` if `measured_value` or `limit_value` changed
- If exceedance newly detected (false → true), sends notification

### POST `/environmental-records/{record}/investigate` (investigate)

No request body needed. Controller:

1. Check `record.status === 'recorded'`
2. Update `record.status` = `'investigated'`
3. `AuditService::log('environment.investigated', ...)`
4. `ActivityService::log('environment', ..., 'environment.investigated', ...)`
5. `NotificationService::notify($reporter, 'environment.investigated', [...])`
6. Redirect back with success message

### POST `/environmental-records/{record}/open-action` (openAction)

```json
{
  "capa_title": "Investigasi emisi SOx — instalasi scrubber",
  "capa_description": "CAPA untuk menangani exceedance emisi SOx dari stack #1"
}
```

| Field | Rule |
|---|---|
| `capa_title` | `required|string|max:255` |
| `capa_description` | `required|string` |

Controller:

1. Check `record.status === 'investigated'`
2. Create `CapaAction` with `source_module='environment'`, `source_reference_id=$record->id`
3. Update `record.status` = `'action_open'`, `record.capa_action_id` = `$capaAction->id`
4. `AuditService::log('environment.action_opened', ...)`
5. `ActivityService::log(...)`
6. Redirect back with success message

### POST `/environmental-records/{record}/close` (close)

```json
{
  "reason": "Investigasi selesai, corrective action telah diimplementasi. Emisi sudah dalam batas normal."
}
```

| Field | Rule |
|---|---|
| `reason` | `required|string|min:10|max:1000` |

Controller:

1. Check `record.status` is NOT `closed`
2. Update `record.status` = `'closed'`
3. `AuditService::log('environment.closed', ...)` with reason
4. `ActivityService::log(...)`
5. `NotificationService::notify($reporter, 'environment.closed', [...])`
6. Redirect back with success message

---

## 3. Inertia Response Props

### Index Page (`Environmental/Index.tsx`)

```typescript
{
  records: {
    data: EnvironmentalRecord[],
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
    exceedance_only: boolean,
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

### Create/Edit Page (`Environmental/Form.tsx`)

```typescript
{
  record: EnvironmentalRecord | null,
  sites: Site[],
  areas: Area[],
  can: {
    create: boolean,
    update: boolean,
  },
}
```

### Show Page (`Environmental/Show.tsx`)

```typescript
{
  record: EnvironmentalRecord & {
    site: Site,
    area: Area | null,
    reporter: User,
    capaAction: CapaAction | null,
  },
  evidence: ManagedFile[],
  comments: Comment[],
  activities: ActivityLog[],
  can: {
    update: boolean,
    investigate: boolean,
    close: boolean,
    export: boolean,
  },
  availableTransitions: {
    action: string,
    label: string,
    permission: string,
    requires_reason: boolean,
  }[],
}
```

---

## 4. ListQuery Parameters

The index page accepts these query parameters for filtering:

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `record_number` and `title` (OR) |
| `type` | string | `null` | Filter by exact type: waste, spill, emission, noise, water_monitoring, other |
| `status` | string | `null` | Filter by exact status: recorded, investigated, action_open, closed |
| `exceedance_only` | boolean | `false` | If true, only show records where `is_exceedance = true` |
| `site_id` | int | `null` | Filter by site |
| `per_page` | int | `15` | Items per page (max 100) |
| `sort` | string | `created_at` | Sort column |
| `direction` | string | `desc` | Sort direction (asc/desc) |

### Controller index method pattern:

```php
public function index(ListQuery $listQuery): Response
{
    $items = $listQuery->paginate(
        EnvironmentalRecord::query()->with(['site', 'area', 'reporter']),
        ['record_number', 'title'],
        ['created_at', 'occurred_at', 'record_number'],
        'created_at',
        15,
    );

    // Apply exceedance filter
    if (request()->boolean('exceedance_only')) {
        $items->where('is_exceedance', true);
    }

    return Inertia::render('Modules/Environmental/Index', [
        'items' => $items,
        'filters' => $listQuery->filters(),
        'sites' => Site::where('is_active', true)->get(['id', 'name']),
        'can' => [
            'create' => auth()->user()->can('environment.records.create'),
            'export' => auth()->user()->can('environment.records.export'),
        ],
    ]);
}
```

---

## 5. CSV Export Specification

Endpoint: `GET /environmental-records/export?search=...&type=...&status=...`

Applies same ListQuery filters, then streams CSV via `CsvExporter`.

### CSV Columns:

| Column Header | Source |
|---|---|
| `Nomor` | `record_number` |
| `Tipe` | `type` |
| `Judul` | `title` |
| `Deskripsi` | `description` (truncated 500 chars) |
| `Site` | `site.name` |
| `Area` | `area.name` |
| `Tanggal Kejadian` | `occurred_at` |
| `Nilai Terukur` | `measured_value` |
| `Satuan` | `unit` |
| `Batas` | `limit_value` |
| `Exceedance` | `is_exceedance` (Yes/No) |
| `Status` | `status` |
| `CAPA` | `capaAction.number` |
| `Dibuat Oleh` | `reporter.name` |
| `Dibuat Pada` | `created_at` |

### Controller export method pattern:

```php
public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
{
    $query = $listQuery->apply(
        EnvironmentalRecord::query()->with(['site', 'area', 'reporter', 'capaAction']),
        ['record_number', 'title'],
        ['created_at', 'occurred_at'],
        'created_at',
    );

    if (request()->boolean('exceedance_only')) {
        $query->where('is_exceedance', true);
    }

    return $exporter->stream($query, [
        'Nomor'           => 'record_number',
        'Tipe'            => 'type',
        'Judul'           => 'title',
        'Deskripsi'       => fn ($item) => Str::limit($item->description, 500),
        'Site'            => fn ($item) => $item->site?->name ?? '',
        'Area'            => fn ($item) => $item->area?->name ?? '',
        'Tanggal Kejadian' => fn ($item) => $item->occurred_at?->format('Y-m-d H:i') ?? '',
        'Nilai Terukur'   => fn ($item) => $item->measured_value ?? '',
        'Satuan'          => fn ($item) => $item->unit ?? '',
        'Batas'           => fn ($item) => $item->limit_value ?? '',
        'Exceedance'      => fn ($item) => $item->is_exceedance ? 'Yes' : 'No',
        'Status'          => 'status',
        'CAPA'            => fn ($item) => $item->capaAction?->number ?? '',
        'Dibuat Oleh'     => fn ($item) => $item->reporter?->name ?? '',
        'Dibuat Pada'     => fn ($item) => $item->created_at->format('Y-m-d H:i'),
    ], 'environmental_records_export_' . now()->format('Ymd_His') . '.csv');
}
```

---

## 6. Error Responses

| Status | When | Response |
|---|---|---|
| `403` | User lacks required permission | Inertia redirects to dashboard with error flash |
| `404` | Record ID not found | Laravel default 404 page |
| `422` | Validation failure | Inertia sends errors back to form with `$page.props.errors` |
| `400` | Invalid status transition | RuntimeException caught → redirect back with error flash |
| `419` | CSRF token expired | Laravel default |

### Invalid status transition handling:

```php
try {
    if ($record->status === 'closed') {
        throw new RuntimeException('Record sudah ditutup dan tidak dapat diubah.');
    }

    if ($request->routeIs('environmental-records.investigate') && $record->status !== 'recorded') {
        throw new RuntimeException('Record hanya dapat diinvestigasi dari status "Tercatat".');
    }

    // ... perform transition
} catch (RuntimeException $e) {
    return back()->withErrors(['status' => $e->getMessage()]);
}
```

---

## 7. Permission Enforcement Points

| Layer | How |
|---|---|
| **Route middleware** | `->middleware('permission:environment.records.view')` on each route |
| **Controller authorize()** | `$this->authorize('view', $record)` for show/edit (scope filtering) |
| **Inertia shared props** | `auth.permissions` array → frontend checks via `permissions.has('environment.records.create')` |
| **Export** | Route middleware `permission:environment.records.export` |

---

## 8. Numbering Integration

On `store`:

```php
$generatedNumber = app(NumberingService::class)->generate(
    moduleName: 'environment',
    actor: $actor,
    referenceType: EnvironmentalRecord::class,
    referenceId: $record->id,
);

$record->update(['record_number' => $generatedNumber->number]);
```

Numbering format (already seeded): `ENV-2026-0001`

---

## 9. Exceedance Detection Integration

Exceedance is auto-calculated in the model observer or controller service layer:

```php
// In EnvironmentalRecordObserver::saving()
public function saving(EnvironmentalRecord $record): void
{
    if ($record->measured_value !== null && $record->limit_value !== null) {
        $record->is_exceedance = (float) $record->measured_value > (float) $record->limit_value;
    } else {
        $record->is_exceedance = false;
    }
}

// In EnvironmentalRecordObserver::saved()
public function saved(EnvironmentalRecord $record): void
{
    if ($record->wasChanged('is_exceedance') && $record->is_exceedance) {
        // Log exceedance detection
        ActivityService::log(
            moduleName: 'environment',
            referenceId: $record->id,
            event: 'environment.exceedance_detected',
            description: "Exceedance terdeteksi: nilai {$record->measured_value} {$record->unit} melebihi batas {$record->limit_value} {$record->unit}",
            actor: $record->reporter,
        );

        // Notify QHSSE team
        $qhsseUsers = User::role(['QHSSE Officer', 'QHSSE Manager'])
            ->where('site_id', $record->site_id)
            ->get();

        NotificationService::notifyMany(
            $qhsseUsers,
            'environment.exceedance_detected',
            [
                'record_number' => $record->record_number,
                'title' => $record->title,
                'measured_value' => $record->measured_value,
                'limit_value' => $record->limit_value,
                'unit' => $record->unit,
            ],
            $record->reporter,
            'environment',
            $record->id,
            route('environmental-records.show', $record),
        );
    }
}
```

---

## 10. CAPA Integration

### Creating CAPA from Environmental Record

When a user clicks "Buka CAPA" on the Show page:

1. POST `/environmental-records/{record}/open-action` with `capa_title` and `capa_description`
2. Controller creates a new `CapaAction`:
   - `source_module` = `'environment'`
   - `source_reference_id` = `$record->id`
   - `title` = `$request->capa_title`
   - `description` = `$request->capa_description`
3. Updates `environmental_records`:
   - `status` = `'action_open'`
   - `capa_action_id` = `$capaAction->id`
4. Logs audit + activity

### Show page loads CAPA:

```php
'capaAction' => $record->capaAction()
    ->select(['id', 'number', 'title', 'status'])
    ->first(),
```

---

## 11. File Upload Integration

Evidence files are uploaded via the existing core `ManagedFileController` routes.

### Upload flow:

1. User creates record → gets `record.id`
2. User uploads file via `POST /core/files` with:
   - `module_name`: `environment`
   - `reference_id`: `$record->id`
   - `collection`: `evidence`
   - `file`: the UploadedFile
3. `ManagedFileService::store($file, new FileReference('environment', $record->id, 'evidence'), $uploader)`
4. File stored on `local` disk at `managed-files/environment/{id}/evidence/{uuid}.{ext}`
5. Download via `GET /core/files/{managedFile}/download`

### Show page loads evidence:

```php
'evidence' => ManagedFile::query()
    ->where('module_name', 'environment')
    ->where('reference_id', $record->id)
    ->where('collection', 'evidence')
    ->whereNull('deleted_at')
    ->get(),
```

---

## 12. Integration Points Summary

| Integration | Module | How |
|---|---|---|
| **Numbering** | Core `NumberingService` | `generate('environment', ...)` → `ENV-2026-0001` |
| **Files** | Core `ManagedFileService` | `module_name='environment'`, `collection='evidence'` |
| **Comments** | Core `CommentService` | `module_name='environment'` |
| **Activity Logs** | Core `ActivityService` | `module_name='environment'` |
| **Audit Logs** | Core `AuditService` | `module_name='environment'` |
| **Notifications** | Core `NotificationService` | Types: `environment.exceedance_detected`, `environment.investigated`, `environment.closed` |
| **CAPA** | Module `04-capa-action-tracking` | `capa_action_id` FK + `source_module='environment'` |
| **Legal** | Module `14-legal-compliance` | Manual reference in description (future: automated) |
| **Export** | Core `CsvExporter` | 15-column CSV export |
| **List Query** | Core `ListQuery` | Paginated search/filter/sort |
