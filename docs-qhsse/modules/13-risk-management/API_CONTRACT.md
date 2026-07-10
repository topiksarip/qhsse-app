# API Contract — Risk Management (HIRADC/JSA)

> Spec ini mendefinisikan semua route, request, response, dan error handling untuk modul Risk Management.

## 1. Route Table

Semua route diawali dengan prefix `/risk-registers`, nama route `risk.registers.*`, dan middleware `auth,verified`.

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/risk-registers` | `index` | `risk.registers.index` | `risk.registers.view` | List risk registers with search/filter/pagination |
| GET | `/risk-registers/create` | `create` | `risk.registers.create` | `risk.registers.create` | Render create form |
| POST | `/risk-registers` | `store` | `risk.registers.store` | `risk.registers.create` | Save new risk register |
| GET | `/risk-registers/{riskRegister}` | `show` | `risk.registers.show` | `risk.registers.view` | Show risk register detail |
| GET | `/risk-registers/{riskRegister}/edit` | `edit` | `risk.registers.edit` | `risk.registers.update` | Render edit form |
| PUT/PATCH | `/risk-registers/{riskRegister}` | `update` | `risk.registers.update` | `risk.registers.update` | Update risk register |
| POST | `/risk-registers/{riskRegister}/assess` | `assess` | `risk.registers.assess` | `risk.registers.assess` | Transition identified → assessed |
| POST | `/risk-registers/{riskRegister}/needs-controls` | `needsControls` | `risk.registers.needs_controls` | `risk.registers.assess` | Transition assessed → controls_needed |
| POST | `/risk-registers/{riskRegister}/implement-controls` | `implementControls` | `risk.registers.implement_controls` | `risk.registers.assess` | Transition controls_needed → controls_in_place |
| POST | `/risk-registers/{riskRegister}/monitor` | `monitor` | `risk.registers.monitor` | `risk.registers.assess` | Transition controls_in_place → monitored |
| POST | `/risk-registers/{riskRegister}/obsolete` | `obsolete` | `risk.registers.obsolete` | `risk.registers.assess` | Transition any → obsolete |
| GET | `/risk-registers/export` | `export` | `risk.registers.export` | `risk.registers.export` | Export filtered list as CSV |

### Route Registration

File: `routes/modules.php`

```php
use App\Http\Controllers\Modules\RiskManagement\RiskRegisterController;

Route::middleware(['auth', 'verified'])
    ->prefix('risk-registers')
    ->name('risk.registers.')
    ->group(function (): void {
        Route::get('/', [RiskRegisterController::class, 'index'])
            ->name('index')
            ->middleware('permission:risk.registers.view');

        Route::get('/create', [RiskRegisterController::class, 'create'])
            ->name('create')
            ->middleware('permission:risk.registers.create');

        Route::post('/', [RiskRegisterController::class, 'store'])
            ->name('store')
            ->middleware('permission:risk.registers.create');

        Route::get('/{riskRegister}', [RiskRegisterController::class, 'show'])
            ->name('show')
            ->middleware('permission:risk.registers.view');

        Route::get('/{riskRegister}/edit', [RiskRegisterController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:risk.registers.update');

        Route::put('/{riskRegister}', [RiskRegisterController::class, 'update'])
            ->name('update')
            ->middleware('permission:risk.registers.update');

        Route::post('/{riskRegister}/assess', [RiskRegisterController::class, 'assess'])
            ->name('assess')
            ->middleware('permission:risk.registers.assess');

        Route::post('/{riskRegister}/needs-controls', [RiskRegisterController::class, 'needsControls'])
            ->name('needs_controls')
            ->middleware('permission:risk.registers.assess');

        Route::post('/{riskRegister}/implement-controls', [RiskRegisterController::class, 'implementControls'])
            ->name('implement_controls')
            ->middleware('permission:risk.registers.assess');

        Route::post('/{riskRegister}/monitor', [RiskRegisterController::class, 'monitor'])
            ->name('monitor')
            ->middleware('permission:risk.registers.assess');

        Route::post('/{riskRegister}/obsolete', [RiskRegisterController::class, 'obsolete'])
            ->name('obsolete')
            ->middleware('permission:risk.registers.assess');

        Route::get('/export', [RiskRegisterController::class, 'export'])
            ->name('export')
            ->middleware('permission:risk.registers.export');
    });
```

### Route Model Binding

- Parameter name: `{riskRegister}` → Laravel resolves to `RiskRegister` model via route key (id).
- Custom key: default `id`.

---

## 2. Request Payloads

### POST `/risk-registers` (store)

```json
{
  "title": "Risiko Jatuh dari Ketinggian",
  "type": "hiradc",
  "site_id": 1,
  "area_id": 2,
  "department_id": 3,
  "activity": "Bekerja di atas scaffolding",
  "hazard": "Jatuh dari ketinggian saat bekerja di atas scaffolding tanpa harness",
  "existing_controls": "Guard rail di scaffolding, safety harness tersedia tetapi tidak selalu digunakan",
  "severity_id": 4,
  "probability_id": 4,
  "risk_level_id": 7,
  "additional_controls": "1. Wajib pakai full body harness\n2. Inspection scaffolding sebelum digunakan\n3. Training height safety",
  "residual_severity_id": 3,
  "residual_probability_id": 3,
  "residual_risk_level_id": 12,
  "owner_id": 5,
  "review_date": "2026-10-01"
}
```

**Validation Rules (StoreRiskRegisterRequest):**

| Field | Rule | Notes |
|---|---|---|
| `title` | `required|string|max:255` | |
| `type` | `required|in:hazard_identification,jsa,hiradc,risk_assessment` | |
| `site_id` | `required|exists:sites,id` | |
| `area_id` | `nullable|exists:areas,id` | |
| `department_id` | `nullable|exists:departments,id` | |
| `activity` | `required|string|max:500` | |
| `hazard` | `required|string` | |
| `existing_controls` | `nullable|string` | |
| `severity_id` | `nullable|exists:severities,id` | Required for assess action |
| `probability_id` | `nullable|integer|min:1|max:5` | Required for assess action |
| `risk_level_id` | `nullable|exists:risk_matrix_levels,id` | Auto-calculated from severity × probability |
| `additional_controls` | `nullable|string` | Required for implement_controls action |
| `residual_severity_id` | `nullable|exists:severities,id` | |
| `residual_probability_id` | `nullable|integer|min:1|max:5` | |
| `residual_risk_level_id` | `nullable|exists:risk_matrix_levels,id` | Auto-calculated |
| `owner_id` | `required|exists:users,id` | |
| `review_date` | `nullable|date` | |

**Controller behavior (store):**

1. Validate request
2. Generate `register_number` via `NumberingService::generate('risk', $actor, ...)`
3. Create `RiskRegister` with validated data
4. If `severity_id` and `probability_id` provided: lookup `risk_level_id` from `risk_matrix_levels` and set it
5. If `residual_severity_id` and `residual_probability_id` provided: lookup `residual_risk_level_id`
6. `AuditService::created($riskRegister, $actor, 'risk', $riskRegister->id)`
7. `ActivityService::log('risk', $riskRegister->id, 'risk.created', 'Risk register created', $actor)`
8. Redirect to `risk.registers.show`

### PUT `/risk-registers/{riskRegister}` (update)

Same payload as store, but:
- `register_number` is read-only (ignored if sent)
- `title`, `type`, `site_id`, `activity`, `hazard`, `owner_id` are **sometimes** required (present in payload)
- Only allowed if `status !== 'obsolete'`
- Records audit trail for changed fields via `AuditService::updated()`

### POST `/risk-registers/{riskRegister}/assess` (assess)

```json
{
  "severity_id": 4,
  "probability_id": 4,
  "risk_level_id": 7,
  "additional_controls": "1. Wajib pakai full body harness\n2. Inspection scaffolding sebelum digunakan",
  "residual_severity_id": 3,
  "residual_probability_id": 3,
  "residual_risk_level_id": 12
}
```

| Field | Rule | Notes |
|---|---|---|
| `severity_id` | `required|exists:severities,id` | |
| `probability_id` | `required|integer|min:1|max:5` | |
| `risk_level_id` | `required|exists:risk_matrix_levels,id` | Auto-calculated, validated |
| `additional_controls` | `nullable|string` | |
| `residual_severity_id` | `nullable|exists:severities,id` | |
| `residual_probability_id` | `nullable|integer|min:1|max:5` | |
| `residual_risk_level_id` | `nullable|exists:risk_matrix_levels,id` | |

**Controller behavior (assess):**

1. Check `riskRegister.status === 'identified'`
2. Validate request
3. Verify `risk_level_id` matches `risk_matrix_levels` lookup for `severity_id` × `probability_id`
4. Update `RiskRegister` with severity, probability, risk_level, additional_controls, residual fields
5. Set status to `assessed`
6. `AuditService::updated($riskRegister, $oldValues, $actor, 'risk', $riskRegister->id)`
7. `ActivityService::log('risk', $riskRegister->id, 'risk.assessed', 'Risk assessment completed', $actor)`
8. `NotificationService::notifyMany($recipients, 'risk.assessed', [...])`
9. Redirect back with flash message

### POST `/risk-registers/{riskRegister}/needs-controls` (needsControls)

No request body needed.

**Controller behavior:**

1. Check `riskRegister.status === 'assessed'`
2. Set status to `controls_needed`
3. `AuditService::updated(...)` with status change
4. `ActivityService::log('risk', $id, 'risk.status_changed', 'Status: assessed → controls_needed', $actor)`
5. `NotificationService::notifyMany($recipients, 'risk.controls_needed', [...])`
6. Redirect back

### POST `/risk-registers/{riskRegister}/implement-controls` (implementControls)

```json
{
  "additional_controls": "1. Wajib pakai full body harness\n2. Inspection scaffolding sebelum digunakan\n3. Training height safety"
}
```

| Field | Rule | Notes |
|---|---|---|
| `additional_controls` | `required|string` | Must not be empty |

**Controller behavior:**

1. Check `riskRegister.status === 'controls_needed'`
2. Validate `additional_controls` is not empty
3. Update `additional_controls` and set status to `controls_in_place`
4. `AuditService::updated(...)`
5. `ActivityService::log(...)`
6. Redirect back

### POST `/risk-registers/{riskRegister}/monitor` (monitor)

No request body needed.

**Controller behavior:**

1. Check `riskRegister.status === 'controls_in_place'`
2. Set status to `monitored`
3. `AuditService::updated(...)`
4. `ActivityService::log(...)`
5. Redirect back

### POST `/risk-registers/{riskRegister}/obsolete` (obsolete)

```json
{
  "reason": "Aktivitas tidak lagi dilakukan, hazard tereliminasi."
}
```

| Field | Rule | Notes |
|---|---|---|
| `reason` | `nullable|string|max:1000` | Optional but recommended |

**Controller behavior:**

1. Check `riskRegister.status !== 'obsolete'`
2. Set status to `obsolete`
3. `AuditService::updated(...)`
4. `ActivityService::log('risk', $id, 'risk.obsolete', 'Status: → obsolete', $actor)`
5. `NotificationService::notifyMany($recipients, 'risk.obsolete', [...])`
6. Redirect back

---

## 3. Inertia Response Props

### Index Page (`RiskManagement/Index.tsx`)

```typescript
{
  items: {
    data: RiskRegister[],
    current_page: number,
    last_page: number,
    per_page: number,
    total: number,
  },
  filters: {
    search: string,
    status: string | null,
    type: string | null,
    risk_level: string | null,
    site_id: number | null,
  },
}
```

### Create/Edit Page (`RiskManagement/Form.tsx`)

```typescript
{
  item: RiskRegister | null,  // null for create, populated for edit
  sites: Site[],
  areas: Area[],
  departments: Department[],
  severities: Severity[],
  riskMatrixLevels: RiskMatrixLevel[],
  probabilities: ProbabilityOption[],  // distinct probability_level from risk_matrix_levels
  users: User[],  // for owner dropdown
}
```

### Show Page (`RiskManagement/Show.tsx`)

```typescript
{
  riskRegister: RiskRegister & {
    site: Site,
    area: Area | null,
    department: Department | null,
    severity: Severity | null,
    riskLevel: RiskMatrixLevel | null,
    residualSeverity: Severity | null,
    residualRiskLevel: RiskMatrixLevel | null,
    owner: User,
  },
  riskMatrixLevels: RiskMatrixLevel[],
  attachments: ManagedFile[],
  comments: Comment[],
  activities: ActivityLog[],
  availableActions: {
    action_key: string,
    action_label: string,
    route_name: string,
  }[],
}
```

---

## 4. ListQuery Parameters

The index page accepts these query parameters for filtering:

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `register_number`, `title`, `activity` (OR) |
| `status` | string | `null` | Filter by exact status |
| `type` | string | `null` | Filter by exact type |
| `risk_level` | string | `null` | Filter by risk level color (RED/ORANGE/YELLOW/GREEN) via risk_matrix_levels.risk_level |
| `site_id` | int | `null` | Filter by site |
| `per_page` | int | `15` | Items per page (max 100) |
| `sort` | string | `created_at` | Sort column |
| `direction` | string | `desc` | Sort direction (asc/desc) |

### Controller index method pattern:

```php
public function index(ListQuery $listQuery): Response
{
    $items = $listQuery->paginate(
        RiskRegister::query()->with(['site', 'area', 'department', 'severity', 'riskLevel', 'owner']),
        ['register_number', 'title', 'activity'],
        ['created_at', 'register_number', 'review_date'],
        'created_at',
        15,
    );

    return Inertia::render('Modules/RiskManagement/Index', [
        'items' => $items,
        'filters' => $listQuery->filters(),
    ]);
}
```

---

## 5. CSV Export Specification

Endpoint: `GET /risk-registers/export?search=...&status=...&type=...`

Applies same ListQuery filters, then streams CSV via `CsvExporter`.

### CSV Columns:

| Column Header | Source |
|---|---|
| `Nomor` | `register_number` |
| `Judul` | `title` |
| `Tipe` | `type` |
| `Site` | `site.name` |
| `Area` | `area.name` |
| `Department` | `department.name` |
| `Aktivitas` | `activity` |
| `Hazard` | `hazard` |
| `Existing Controls` | `existing_controls` |
| `Initial Severity` | `severity.name` |
| `Initial Probability` | `probability_id` (mapped to label) |
| `Initial Risk Level` | `riskLevel.risk_level` |
| `Additional Controls` | `additional_controls` |
| `Residual Severity` | `residualSeverity.name` |
| `Residual Probability` | `residual_probability_id` (mapped to label) |
| `Residual Risk Level` | `residualRiskLevel.risk_level` |
| `Owner` | `owner.name` |
| `Status` | `status` |
| `Review Date` | `review_date` |
| `Created At` | `created_at` |

### Controller export method pattern:

```php
public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
{
    $query = $listQuery->apply(
        RiskRegister::query()
            ->with(['site', 'area', 'department', 'severity', 'riskLevel',
                    'residualSeverity', 'residualRiskLevel', 'owner']),
        ['register_number', 'title', 'activity'],
        ['created_at', 'register_number'],
        'created_at',
    );

    $probabilityLabels = [1 => 'Jarang', 2 => 'Tidak Mungkin', 3 => 'Mungkin',
                         4 => 'Kemungkinan Besar', 5 => 'Hampir Pasti'];

    return $exporter->stream($query, [
        'Nomor' => 'register_number',
        'Judul' => 'title',
        'Tipe' => 'type',
        'Site' => fn ($item) => $item->site?->name ?? '',
        'Area' => fn ($item) => $item->area?->name ?? '',
        'Department' => fn ($item) => $item->department?->name ?? '',
        'Aktivitas' => 'activity',
        'Hazard' => 'hazard',
        'Existing Controls' => fn ($item) => $item->existing_controls ?? '',
        'Initial Severity' => fn ($item) => $item->severity?->name ?? '',
        'Initial Probability' => fn ($item) => $probabilityLabels[$item->probability_id] ?? '',
        'Initial Risk Level' => fn ($item) => $item->riskLevel?->risk_level ?? '',
        'Additional Controls' => fn ($item) => $item->additional_controls ?? '',
        'Residual Severity' => fn ($item) => $item->residualSeverity?->name ?? '',
        'Residual Probability' => fn ($item) => $probabilityLabels[$item->residual_probability_id] ?? '',
        'Residual Risk Level' => fn ($item) => $item->residualRiskLevel?->risk_level ?? '',
        'Owner' => fn ($item) => $item->owner?->name ?? '',
        'Status' => 'status',
        'Review Date' => fn ($item) => $item->review_date?->format('Y-m-d') ?? '',
        'Created At' => fn ($item) => $item->created_at?->format('Y-m-d H:i:s') ?? '',
    ], 'risk_registers_export.csv');
}
```

---

## 6. Error Responses

| Status | When | Response |
|---|---|---|
| `403` | User lacks required permission | Inertia redirects to dashboard with error flash |
| `404` | Risk register ID not found | Laravel default 404 page |
| `422` | Validation failure | Inertia sends errors back to form with `$page.props.errors` |
| `400` | Invalid status transition | RuntimeException caught → redirect back with error flash |
| `419` | CSRF token expired | Laravel default |

### Invalid status transition handling:

```php
try {
    $this->transitionStatus($riskRegister, 'assessed', $actor);
} catch (RuntimeException $e) {
    return back()->withErrors(['status' => $e->getMessage()]);
}
```

---

## 7. Permission Enforcement Points

| Layer | How |
|---|---|
| **Route middleware** | `->middleware('permission:risk.registers.view')` on each route |
| **Controller authorize()** | `$this->authorize('view', $riskRegister)` for show/edit (scope filtering) |
| **Inertia shared props** | `auth.permissions` array → frontend checks via `permissions.has('risk.registers.create')` |
| **Export** | Route middleware `permission:risk.registers.export` |

---

## 8. Numbering Integration

On `store`:

```php
$generatedNumber = app(NumberingService::class)->generate(
    moduleName: 'risk',
    actor: $actor,
    referenceType: RiskRegister::class,
    referenceId: $riskRegister->id,
);

$riskRegister->update(['register_number' => $generatedNumber->number]);
```

Numbering format (already seeded): `RSK-2026-0001`

---

## 9. Risk Matrix Lookup Integration

When severity_id and probability_id are set (during store, update, or assess):

```php
// Lookup initial risk level
$severity = Severity::find($request->severity_id);
$riskMatrixLevel = RiskMatrixLevel::where('severity_level', $severity->level)
    ->where('probability_level', $request->probability_id)
    ->where('is_active', true)
    ->first();

$riskRegister->risk_level_id = $riskMatrixLevel?->id;

// Lookup residual risk level (if residual fields provided)
if ($request->residual_severity_id && $request->residual_probability_id) {
    $residualSeverity = Severity::find($request->residual_severity_id);
    $residualRiskLevel = RiskMatrixLevel::where('severity_level', $residualSeverity->level)
        ->where('probability_level', $request->residual_probability_id)
        ->where('is_active', true)
        ->first();

    $riskRegister->residual_risk_level_id = $residualRiskLevel?->id;
}
```

---

## 10. File Upload Integration

Attachment files are uploaded via the existing core `ManagedFileController` routes.

### Upload flow:

1. User creates risk register → gets `risk_register.id`
2. User uploads file via `POST /core/files` with:
   - `module_name`: `risk`
   - `reference_id`: `$riskRegister->id`
   - `collection`: `attachments`
   - `file`: the UploadedFile
3. `ManagedFileService::store($file, new FileReference('risk', $riskRegister->id, 'attachments'), $uploader)`
4. File stored on `local` disk at `managed-files/risk/{id}/attachments/{uuid}.{ext}`
5. Download via `GET /core/files/{managedFile}/download`

### Show page loads attachments:

```php
'attachments' => ManagedFile::query()
    ->where('module_name', 'risk')
    ->where('reference_id', $riskRegister->id)
    ->where('collection', 'attachments')
    ->whereNull('deleted_at')
    ->get(),
```

---

## 11. Integration Points

### 11.1 CAPA Module

Risk register can trigger CAPA records when additional controls require action items:

- CAPA record's `source_module = 'risk'`
- CAPA record's `source_reference_id = risk_register.id`
- Link is created from the CAPA module side, not from risk module
- Risk register show page can display linked CAPA records (future enhancement)

### 11.2 Incident Module

Risk register can be linked to incidents when a risk materializes:

- Incident record can reference `risk_register_id` (future field)
- Risk register show page displays related incidents
- Link is created from the incident module side

### 11.3 PTW Module

Risk assessment can be linked to Permit to Work:

- PTW record can reference `risk_register_id` for high-risk activities
- Risk register show page displays related permits
- Link is created from the PTW module side
