# API Contract — Contractor Management

> Spec ini mendefinisikan semua route, request, response, dan error handling untuk modul Contractor Management.

## 1. Route Table

Semua route diawali dengan prefix `/contractors`, nama route `contractors.*`, dan middleware `auth,verified`.

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/contractors` | `index` | `contractors.index` | `contractor.management.view` | List contractors with search/filter/pagination |
| GET | `/contractors/create` | `create` | `contractors.create` | `contractor.management.create` | Render create form |
| POST | `/contractors` | `store` | `contractors.store` | `contractor.management.create` | Save new contractor |
| GET | `/contractors/{contractor}` | `show` | `contractors.show` | `contractor.management.view` | Show contractor detail |
| GET | `/contractors/{contractor}/edit` | `edit` | `contractors.edit` | `contractor.management.update` | Render edit form |
| PUT | `/contractors/{contractor}` | `update` | `contractors.update` | `contractor.management.update` | Update contractor |
| POST | `/contractors/{contractor}/evaluations` | `storeEvaluation` | `contractors.evaluations.store` | `contractor.management.evaluate` | Add evaluation to contractor |
| POST | `/contractors/{contractor}/prequalify` | `setPrequalified` | `contractors.prequalify` | `contractor.management.update` | Set prequalification status |
| DELETE | `/contractors/{contractor}/prequalify` | `revokePrequalified` | `contractors.prequalify.revoke` | `contractor.management.update` | Revoke prequalification |
| GET | `/contractors/export` | `export` | `contractors.export` | `contractor.management.export` | Export filtered list as CSV |

### Route Registration

File: `routes/modules.php`

```php
use App\Http\Controllers\Modules\ContractorManagement\ContractorController;

Route::middleware(['auth', 'verified'])
    ->prefix('contractors')
    ->name('contractors.')
    ->group(function (): void {
        Route::get('/', [ContractorController::class, 'index'])
            ->name('index')
            ->middleware('permission:contractor.management.view');

        Route::get('/create', [ContractorController::class, 'create'])
            ->name('create')
            ->middleware('permission:contractor.management.create');

        Route::post('/', [ContractorController::class, 'store'])
            ->name('store')
            ->middleware('permission:contractor.management.create');

        Route::get('/{contractor}', [ContractorController::class, 'show'])
            ->name('show')
            ->middleware('permission:contractor.management.view');

        Route::get('/{contractor}/edit', [ContractorController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:contractor.management.update');

        Route::put('/{contractor}', [ContractorController::class, 'update'])
            ->name('update')
            ->middleware('permission:contractor.management.update');

        Route::post('/{contractor}/evaluations', [ContractorController::class, 'storeEvaluation'])
            ->name('evaluations.store')
            ->middleware('permission:contractor.management.evaluate');

        Route::post('/{contractor}/prequalify', [ContractorController::class, 'setPrequalified'])
            ->name('prequalify')
            ->middleware('permission:contractor.management.update');

        Route::delete('/{contractor}/prequalify', [ContractorController::class, 'revokePrequalified'])
            ->name('prequalify.revoke')
            ->middleware('permission:contractor.management.update');

        Route::get('/export', [ContractorController::class, 'export'])
            ->name('export')
            ->middleware('permission:contractor.management.export');
    });
```

### Route Model Binding

- Parameter name: `{contractor}` → Laravel resolves to `Contractor` model via route key (id).
- Custom key: default `id` (no need for `getRouteKeyName()` override).

---

## 2. Request Payloads

### POST `/contractors` (store)

```json
{
  "company_id": 5,
  "contact_person": "Budi Santoso",
  "contact_phone": "0812-3456-7890",
  "contact_email": "budi@karya-konstruksi.com",
  "service_type": "Konstruksi Sipil",
  "is_prequalified": false,
  "prequalified_until": null
}
```

**Validation Rules (StoreContractorRequest):**

| Field | Rule | Notes |
|---|---|---|
| `company_id` | `required, exists:companies,id` | Must be a valid company. Controller checks no existing active contractor for this company. |
| `contact_person` | `required, string, max:255` | |
| `contact_phone` | `required, string, max:50` | |
| `contact_email` | `nullable, email, max:255` | |
| `service_type` | `required, string, max:255` | Free-text |
| `is_prequalified` | `boolean` | Default: false |
| `prequalified_until` | `required_if:is_prequalified,true, nullable, date, after:today` | Required when is_prequalified=true |

**Controller behavior (store):**

1. Validate request.
2. Check no existing active contractor for `company_id` (partial unique constraint).
3. Generate `contractor_number` via `NumberingService::generate('contractor', $actor)`.
4. Create `Contractor` with `status = 'active'`.
5. `AuditService::created($contractor, $actor, 'contractor', $contractor->id)`.
6. `ActivityService::log('contractor', $contractor->id, 'contractor.created', 'Contractor registered', $actor)`.
7. `NotificationService::notifyMany($qhsseTeamUsers, 'contractor.registered', [...])`.
8. Redirect to `contractors.show`.

```php
public function store(StoreContractorRequest $request): RedirectResponse
{
    $actor = $request->user();
    $data = $request->validated();

    // Check no existing active contractor for this company
    $existing = Contractor::where('company_id', $data['company_id'])
        ->where('status', 'active')
        ->exists();

    if ($existing) {
        return back()->withErrors([
            'company_id' => 'Perusahaan ini sudah terdaftar sebagai kontraktor aktif.',
        ])->withInput();
    }

    // Generate contractor number
    $data['contractor_number'] = $this->numberingService
        ->generate('contractor', $actor)
        ->number;

    $contractor = Contractor::create($data);

    $this->auditService->created($contractor, $actor, 'contractor', $contractor->id);

    $this->activityService->log(
        moduleName: 'contractor',
        referenceId: $contractor->id,
        event: 'contractor.created',
        description: "Contractor {$contractor->contractor_number} registered by {$actor->name}",
        actor: $actor,
    );

    $this->notificationService->notifyMany(
        recipients: $this->getQhsseTeamUsers(),
        type: 'contractor.registered',
        context: [
            'contractor' => $contractor->toArray(),
            'company' => $contractor->company->toArray(),
            'actor' => $actor->toArray(),
        ],
        actor: $actor,
        moduleName: 'contractor',
        referenceId: $contractor->id,
        actionUrl: "/contractors/{$contractor->id}",
    );

    return redirect()
        ->route('contractors.show', $contractor)
        ->with('success', 'Kontraktor berhasil didaftarkan.');
}
```

### PUT `/contractors/{contractor}` (update)

Same payload as store, but:
- `contractor_number` cannot be changed (immutable).
- `company_id` can be changed only if no linked PTW exists for the current company.
- If `is_prequalified` is changed from `false` to `true`, `prequalified_until` is required.
- If `is_prequalified` is changed from `true` to `false`, `prequalified_until` is set to NULL.
- Records audit trail for changed fields via `AuditService::updated()`.

```php
public function update(UpdateContractorRequest $request, Contractor $contractor): RedirectResponse
{
    $actor = $request->user();
    $oldValues = $contractor->toArray();
    $data = $request->validated();

    // If revoking prequalification
    if ($oldValues['is_prequalified'] && !$data['is_prequalified']) {
        $data['prequalified_until'] = null;
    }

    $contractor->update($data);

    $this->auditService->updated($contractor, $oldValues, $actor, 'contractor', $contractor->id);

    $this->activityService->log(
        moduleName: 'contractor',
        referenceId: $contractor->id,
        event: 'contractor.updated',
        description: "Contractor {$contractor->contractor_number} updated by {$actor->name}",
        actor: $actor,
    );

    return redirect()
        ->route('contractors.show', $contractor)
        ->with('success', 'Data kontraktor berhasil diperbarui.');
}
```

### POST `/contractors/{contractor}/evaluations` (storeEvaluation)

```json
{
  "evaluation_date": "2026-07-11",
  "criteria": {
    "compliance_dokumen": 18,
    "rekam_jejak_keselamatan": 22,
    "kompetensi_personel": 17,
    "ketersediaan_apd": 13,
    "program_k3": 16
  },
  "notes": "Kontraktor menunjukkan komitmen tinggi terhadap keselamatan. Dokumen prequalification lengkap."
}
```

**Validation Rules (StoreContractorEvaluationRequest):**

| Field | Rule | Notes |
|---|---|---|
| `evaluation_date` | `required, date, before_or_equal:today` | |
| `criteria` | `required, array, min:1` | JSON object with at least 1 criterion |
| `criteria.*` | `integer, min:0` | Each criterion value must be a non-negative integer |
| `notes` | `nullable, string` | |

**Controller behavior (storeEvaluation):**

1. Validate request.
2. Calculate `total_score` = sum of all criteria values.
3. Derive `result` from `total_score`:
   - `pass` — total_score ≥ 80
   - `conditional` — total_score 60–79.99
   - `fail` — total_score < 60
4. Create `ContractorEvaluation` with `evaluator_id` = auth user.
5. Recalculate `safety_rating` on contractor (average of 3 latest evaluations).
6. Update contractor `safety_rating`.
7. `AuditService::created($evaluation, $actor, 'contractor', $contractor->id)`.
8. `AuditService::log('safety_rating_updated', $contractor, oldRating, newRating, $actor, 'contractor', $contractor->id)`.
9. `ActivityService::log('contractor', $contractor->id, 'contractor.evaluated', ...)`.
10. `NotificationService::notifyMany($qhsseManagers, 'contractor.evaluated', [...])`.
11. Redirect back.

```php
public function storeEvaluation(
    Contractor $contractor,
    StoreContractorEvaluationRequest $request
): RedirectResponse {
    $actor = $request->user();
    $data = $request->validated();

    // Calculate total_score
    $totalScore = array_sum($data['criteria']);

    // Derive result
    $result = match (true) {
        $totalScore >= 80 => 'pass',
        $totalScore >= 60 => 'conditional',
        default           => 'fail',
    };

    // Create evaluation
    $evaluation = $contractor->evaluations()->create([
        'evaluation_date' => $data['evaluation_date'],
        'evaluator_id'    => $actor->id,
        'criteria'         => $data['criteria'],
        'total_score'      => $totalScore,
        'result'           => $result,
        'notes'            => $data['notes'] ?? null,
    ]);

    // Recalculate safety rating
    $oldRating = $contractor->safety_rating;
    $newRating = $this->calculateSafetyRating($contractor);

    $contractor->update(['safety_rating' => $newRating]);

    // Audit trail
    $this->auditService->created($evaluation, $actor, 'contractor', $contractor->id);

    if ($oldRating !== $newRating) {
        $this->auditService->log(
            event: 'contractor.safety_rating_updated',
            model: $contractor,
            oldValues: ['safety_rating' => $oldRating],
            newValues: ['safety_rating' => $newRating],
            actor: $actor,
            moduleName: 'contractor',
            referenceId: $contractor->id,
        );
    }

    $this->activityService->log(
        moduleName: 'contractor',
        referenceId: $contractor->id,
        event: 'contractor.evaluated',
        description: "Evaluation created by {$actor->name}. Score: {$totalScore}/100 ({$result}). Safety rating: " . ($newRating ?? 'N/A'),
        actor: $actor,
    );

    $this->notificationService->notifyMany(
        recipients: $this->getQhsseManagers(),
        type: 'contractor.evaluated',
        context: [
            'contractor' => $contractor->fresh()->toArray(),
            'evaluation' => $evaluation->toArray(),
            'evaluator' => $actor->toArray(),
        ],
        actor: $actor,
        moduleName: 'contractor',
        referenceId: $contractor->id,
        actionUrl: "/contractors/{$contractor->id}",
    );

    return back()->with('success', 'Evaluasi berhasil ditambahkan.');
}

private function calculateSafetyRating(Contractor $contractor): ?string
{
    $evaluations = $contractor->evaluations()
        ->orderBy('evaluation_date', 'desc')
        ->limit(3)
        ->get();

    if ($evaluations->isEmpty()) {
        return null;
    }

    $avgScore = $evaluations->avg('total_score');

    return match (true) {
        $avgScore >= 85 => 'excellent',
        $avgScore >= 70 => 'good',
        $avgScore >= 55 => 'fair',
        default         => 'poor',
    };
}
```

### POST `/contractors/{contractor}/prequalify` (setPrequalified)

```json
{
  "prequalified_until": "2026-12-31"
}
```

**Validation Rules (UpdateContractorPrequalificationRequest):**

| Field | Rule | Notes |
|---|---|---|
| `prequalified_until` | `required, date, after:today` | Must be a future date |

**Controller behavior (setPrequalified):**

1. Validate request.
2. Check `is_prequalified` is currently `false`.
3. Set `is_prequalified = true`, `prequalified_until = $request->prequalified_until`.
4. `AuditService::log('contractor.prequalified', ...)`.
5. `ActivityService::log('contractor', $contractor->id, 'contractor.prequalified', ...)`.
6. `NotificationService::notifyMany($stakeholders, 'contractor.prequalified', [...])`.
7. Redirect back.

```php
public function setPrequalified(
    Contractor $contractor,
    UpdateContractorPrequalificationRequest $request
): RedirectResponse {
    $actor = $request->user();
    $oldValues = $contractor->toArray();

    $contractor->update([
        'is_prequalified'    => true,
        'prequalified_until' => $request->validated()['prequalified_until'],
    ]);

    $this->auditService->updated($contractor, $oldValues, $actor, 'contractor', $contractor->id);

    $this->activityService->log(
        moduleName: 'contractor',
        referenceId: $contractor->id,
        event: 'contractor.prequalified',
        description: "Contractor {$contractor->contractor_number} prequalified until {$contractor->prequalified_until} by {$actor->name}",
        actor: $actor,
    );

    $this->notificationService->notifyMany(
        recipients: $this->getContractorStakeholders($contractor),
        type: 'contractor.prequalified',
        context: [
            'contractor' => $contractor->fresh()->toArray(),
            'actor' => $actor->toArray(),
        ],
        actor: $actor,
        moduleName: 'contractor',
        referenceId: $contractor->id,
        actionUrl: "/contractors/{$contractor->id}",
    );

    return back()->with('success', 'Prequalification berhasil diaktifkan.');
}
```

### DELETE `/contractors/{contractor}/prequalify` (revokePrequalified)

No request body needed.

**Controller behavior (revokePrequalified):**

1. Check `is_prequalified` is currently `true`.
2. Set `is_prequalified = false`, `prequalified_until = null`.
3. `AuditService::log('contractor.prequalification_revoked', ...)`.
4. `ActivityService::log('contractor', $contractor->id, 'contractor.prequalification_revoked', ...)`.
5. Redirect back.

```php
public function revokePrequalified(Contractor $contractor, Request $request): RedirectResponse
{
    $actor = $request->user();
    $oldValues = $contractor->toArray();

    $contractor->update([
        'is_prequalified'    => false,
        'prequalified_until' => null,
    ]);

    $this->auditService->updated($contractor, $oldValues, $actor, 'contractor', $contractor->id);

    $this->activityService->log(
        moduleName: 'contractor',
        referenceId: $contractor->id,
        event: 'contractor.prequalification_revoked',
        description: "Contractor {$contractor->contractor_number} prequalification revoked by {$actor->name}",
        actor: $actor,
    );

    return back()->with('success', 'Prequalification berhasil dicabut.');
}
```

---

## 3. Inertia Response Props

### Index Page (`ContractorManagement/Index.tsx`)

```typescript
{
  contractors: {
    data: Contractor[],
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
    prequalification: string | null,
    service_type: string | null,
    safety_rating: string | null,
  },
  serviceTypes: string[],
  summary: {
    total: number,
    prequalified: number,
    expiring_soon: number,
    expired: number,
    not_prequalified: number,
  },
  can: {
    create: boolean,
    export: boolean,
  },
}
```

### Create/Edit Page (`ContractorManagement/Form.tsx`)

```typescript
{
  contractor: Contractor | null,  // null for create, populated for edit
  companies: Company[],          // companies with type contractor/vendor
  can: {
    update: boolean,
  },
}
```

### Show Page (`ContractorManagement/Show.tsx`)

```typescript
{
  contractor: Contractor & {
    company: Company,
    evaluations: ContractorEvaluation[] & {
      evaluator: User,
    },
    creator: User,
  },
  linkedPermits: {
    data: Permit[],
    total: number,
    active: number,
    expired: number,
    draft: number,
    closed: number,
  },
  linkedIncidents: {
    data: Incident[],
    total: number,
    critical: number,
    major: number,
    minor: number,
  },
  files: ManagedFile[],
  comments: Comment[],
  activities: ActivityLog[],
  safetyScore: {
    rating: string | null,
    average_score: number | null,
    total_evaluations: number,
    latest_evaluation_date: string | null,
  },
  can: {
    update: boolean,
    evaluate: boolean,
    export: boolean,
  },
}
```

### Contractor TypeScript Type

```typescript
interface Contractor {
    id: number;
    contractor_number: string;
    company_id: number;
    contact_person: string;
    contact_phone: string;
    contact_email: string | null;
    service_type: string;
    safety_rating: 'excellent' | 'good' | 'fair' | 'poor' | null;
    is_prequalified: boolean;
    prequalified_until: string | null;
    status: 'active' | 'inactive' | 'blacklisted';
    created_at: string;
    updated_at: string;
}

interface ContractorEvaluation {
    id: number;
    contractor_id: number;
    evaluation_date: string;
    evaluator_id: number;
    criteria: Record<string, number>;
    total_score: number;
    result: 'pass' | 'conditional' | 'fail';
    notes: string | null;
    created_at: string;
    updated_at: string;
    evaluator?: {
        id: number;
        name: string;
        email: string;
    };
}
```

---

## 4. ListQuery Parameters

The index page accepts these query parameters for filtering:

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `contractor_number`, `company.name`, `contact_person` (OR) |
| `status` | string | `null` | Filter by exact status: active, inactive, blacklisted |
| `prequalification` | string | `null` | Filter by prequalification status: prequalified, expiring_soon, expired, not_prequalified |
| `service_type` | string | `null` | Filter by exact service_type |
| `safety_rating` | string | `null` | Filter by safety_rating: excellent, good, fair, poor |
| `per_page` | int | `15` | Items per page (max 100) |
| `sort` | string | `created_at` | Sort column |
| `direction` | string | `desc` | Sort direction (asc/desc) |

### Prequalification Status Query Logic

```php
// prequalification=prequalified
$query->where('is_prequalified', true)
    ->where('prequalified_until', '>', now()->addDays(30));

// prequalification=expiring_soon
$query->where('is_prequalified', true)
    ->where('prequalified_until', '>', now())
    ->where('prequalified_until', '<=', now()->addDays(30));

// prequalification=expired
$query->where('is_prequalified', true)
    ->where('prequalified_until', '<', now());

// prequalification=not_prequalified
$query->where('is_prequalified', false);
```

### Controller index method pattern:

```php
public function index(ListQuery $listQuery): Response
{
    $items = $listQuery->paginate(
        Contractor::query()->with(['company']),
        ['contractor_number', 'contact_person'],
        ['created_at', 'contractor_number', 'prequalified_until'],
        'created_at',
        15,
    );

    // Search includes company name
    $items->getCollection()->load('company:id,code,name');

    $summary = [
        'total'            => Contractor::count(),
        'prequalified'     => Contractor::where('is_prequalified', true)
            ->where('prequalified_until', '>', now()->addDays(30))->count(),
        'expiring_soon'    => Contractor::where('is_prequalified', true)
            ->where('prequalified_until', '>', now())
            ->where('prequalified_until', '<=', now()->addDays(30))->count(),
        'expired'          => Contractor::where('is_prequalified', true)
            ->where('prequalified_until', '<', now())->count(),
        'not_prequalified' => Contractor::where('is_prequalified', false)->count(),
    ];

    $serviceTypes = Contractor::where('status', 'active')
        ->distinct()
        ->pluck('service_type')
        ->sort()
        ->values();

    return Inertia::render('Modules/ContractorManagement/Index', [
        'contractors'  => $items,
        'filters'      => $listQuery->filters(),
        'serviceTypes' => $serviceTypes,
        'summary'      => $summary,
        'can' => [
            'create' => auth()->user()->can('contractor.management.create'),
            'export' => auth()->user()->can('contractor.management.export'),
        ],
    ]);
}
```

### Show method pattern:

```php
public function show(Contractor $contractor, Request $request): Response
{
    $contractor->load([
        'company',
        'evaluations' => fn ($q) => $q->with('evaluator')->orderBy('evaluation_date', 'desc'),
    ]);

    // Linked permits (via company_id)
    $linkedPermits = Permit::where('contractor_id', $contractor->company_id)
        ->with(['site', 'creator'])
        ->orderBy('created_at', 'desc')
        ->paginate(10, ['*'], 'permits_page');

    // Linked incidents (via company_id)
    $linkedIncidents = Incident::where('contractor_id', $contractor->company_id)
        ->with(['severity', 'site'])
        ->orderBy('created_at', 'desc')
        ->paginate(10, ['*'], 'incidents_page');

    // Safety score calculation
    $evaluations = $contractor->evaluations;
    $safetyScore = [
        'rating'                  => $contractor->safety_rating,
        'average_score'           => $evaluations->isNotEmpty() ? round($evaluations->avg('total_score'), 2) : null,
        'total_evaluations'       => $evaluations->count(),
        'latest_evaluation_date'  => $evaluations->isNotEmpty() ? $evaluations->first()->evaluation_date : null,
    ];

    // Files, comments, activities via shared services
    $files = ManagedFile::where('module_name', 'contractor')
        ->where('reference_id', $contractor->id)
        ->get();

    $comments = Comment::where('module_name', 'contractor')
        ->where('reference_id', $contractor->id)
        ->with('author')
        ->orderBy('created_at', 'desc')
        ->get();

    $activities = ActivityLog::where('module_name', 'contractor')
        ->where('reference_id', $contractor->id)
        ->orderBy('created_at', 'desc')
        ->limit(50)
        ->get();

    return Inertia::render('Modules/ContractorManagement/Show', [
        'contractor'      => $contractor,
        'linkedPermits'   => [
            'data'    => $linkedPermits->items(),
            'total'   => $linkedPermits->total(),
            'active'  => $linkedPermits->where('status', 'active')->count(),
            'expired'  => $linkedPermits->filter(fn ($p) => $p->end_datetime < now() && $p->status === 'active')->count(),
            'draft'   => $linkedPermits->where('status', 'draft')->count(),
            'closed'  => $linkedPermits->where('status', 'closed')->count(),
        ],
        'linkedIncidents' => [
            'data'     => $linkedIncidents->items(),
            'total'    => $linkedIncidents->total(),
            'critical' => $linkedIncidents->where('severity.level', '>=', 4)->count(),
            'major'    => $linkedIncidents->where('severity.level', 3)->count(),
            'minor'    => $linkedIncidents->where('severity.level', '<=', 2)->count(),
        ],
        'files'           => $files,
        'comments'        => $comments,
        'activities'      => $activities,
        'safetyScore'     => $safetyScore,
        'can' => [
            'update'   => $request->user()->can('contractor.management.update'),
            'evaluate' => $request->user()->can('contractor.management.evaluate'),
            'export'   => $request->user()->can('contractor.management.export'),
        ],
    ]);
}
```

---

## 5. CSV Export Specification

Endpoint: `GET /contractors/export?search=...&status=...&prequalification=...`

Applies same ListQuery filters, then streams CSV via `CsvExporter`.

### CSV Columns:

| Column Header | Source |
|---|---|
| `Nomor` | `contractor_number` |
| `Perusahaan` | `company.name` |
| `Contact Person` | `contact_person` |
| `Telepon` | `contact_phone` |
| `Email` | `contact_email` |
| `Jenis Layanan` | `service_type` |
| `Safety Rating` | `safety_rating` |
| `Prequalified` | `is_prequalified` (Ya/Tidak) |
| `Berlaku Sampai` | `prequalified_until` (formatted: Y-m-d) |
| `Status` | `status` |
| `Tanggal Dibuat` | `created_at` (formatted: Y-m-d H:i) |

### Controller export method pattern:

```php
public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
{
    $query = $listQuery->apply(
        Contractor::query()->with(['company']),
        ['contractor_number', 'contact_person'],
        ['created_at', 'contractor_number'],
        'created_at',
    );

    return $exporter->stream($query, [
        'Nomor'           => 'contractor_number',
        'Perusahaan'      => fn ($item) => $item->company?->name ?? '',
        'Contact Person'  => 'contact_person',
        'Telepon'         => 'contact_phone',
        'Email'           => fn ($item) => $item->contact_email ?? '',
        'Jenis Layanan'   => 'service_type',
        'Safety Rating'   => fn ($item) => $item->safety_rating ?? 'Belum dievaluasi',
        'Prequalified'    => fn ($item) => $item->is_prequalified ? 'Ya' : 'Tidak',
        'Berlaku Sampai'  => fn ($item) => $item->prequalified_until?->format('Y-m-d') ?? '',
        'Status'          => 'status',
        'Tanggal Dibuat'  => fn ($item) => $item->created_at?->format('Y-m-d H:i') ?? '',
    ], 'contractors-export.csv');
}
```

---

## 6. Error Responses

| Status | When | Response |
|---|---|---|
| 403 | User lacks required permission | Inertia redirect with error flash |
| 404 | Contractor not found | Laravel 404 page |
| 422 | Validation failed (missing required fields, invalid company_id, etc.) | Inertia redirect with `errors` bag |
| 422 | Company already has active contractor (store) | `errors: { company_id: "Perusahaan ini sudah terdaftar sebagai kontraktor aktif." }` |
| 422 | Prequalification revoke when not prequalified | `errors: { prequalify: "Kontraktor belum prequalified." }` |
| 500 | NumberingService fails (race condition after retries) | `errors: { contractor_number: "Gagal membuat nomor kontraktor. Silakan coba lagi." }` |

### Error Response Examples

**Validation Error (422):**

```json
{
  "message": "The company id field is required.",
  "errors": {
    "company_id": ["The company id field is required."]
  }
}
```

**Business Rule Violation (422):**

```json
{
  "message": "Perusahaan ini sudah terdaftar sebagai kontraktor aktif.",
  "errors": {
    "company_id": ["Perusahaan ini sudah terdaftar sebagai kontraktor aktif."]
  }
}
```

---

## 7. Integration Points

### 7.1 Permit to Work (Module 09)

Contractor terkait dengan PTW melalui `permits.contractor_id` yang mereferensikan `companies.id`.

**Query di ContractorController::show():**

```php
$linkedPermits = Permit::where('contractor_id', $contractor->company_id)
    ->with(['site', 'creator'])
    ->orderBy('created_at', 'desc')
    ->paginate(10, ['*'], 'permits_page');
```

**Validasi di PermitController (Module 09):**

- Saat membuat PTW, jika `contractor_id` dipilih, sistem dapat memvalidasi apakah contractor tersebut sudah prequalified.
- Jika belum prequalified, PTW dapat ditolak (opsional, tergantung kebijakan — validasi ini di modul PTW, bukan di modul Contractor).

### 7.2 Incident Reporting (Module 01)

Contractor terkait dengan insiden melalui `incidents.contractor_id` yang mereferensikan `companies.id`.

**Query di ContractorController::show():**

```php
$linkedIncidents = Incident::where('contractor_id', $contractor->company_id)
    ->with(['severity', 'site'])
    ->orderBy('created_at', 'desc')
    ->paginate(10, ['*'], 'incidents_page');
```

### 7.3 Audit Management (Module 06)

Contractor dapat menjadi subjek audit supplier. Hubungan ini bersifat logical (berdasarkan `company_id` di audit, jika audit memiliki field supplier).

**Query (future, jika audit memiliki `supplier_id`):**

```php
$linkedAudits = Audit::where('type', 'supplier')
    ->where('supplier_id', $contractor->company_id)
    ->orderBy('created_at', 'desc')
    ->get();
```

### 7.4 File Service (Core)

Upload/download file dokumen prequalification melalui `ManagedFileService`.

```php
// Upload file
$file = $managedFileService->store(
    uploadedFile: $request->file('document'),
    reference: new FileReference('contractor', $contractor->id, 'prequalification'),
    user: $actor,
    metadata: ['description' => 'Sertifikat SMK3'],
);
```

### 7.5 Comment Service (Core)

```php
// Add comment
$comment = $commentService->add(
    moduleName: 'contractor',
    referenceId: $contractor->id,
    body: $request->input('body'),
    author: $actor,
    parentId: null,
    isInternal: $request->boolean('is_internal'),
);
```

### 7.6 Numbering Service (Core)

```php
// Generate contractor number
$generatedNumber = $numberingService->generate(
    moduleName: 'contractor',
    actor: $actor,
);

$contractorNumber = $generatedNumber->number; // e.g., "CTR-2026-0001"
```

### 7.7 Scheduled Job — Prequalification Expiry Check

```php
// app/Console/Commands/CheckPrequalificationExpiry.php

class CheckPrequalificationExpiry extends Command
{
    protected $signature = 'contractor:check-prequalification-expiry';
    protected $description = 'Check contractor prequalification expiry and send notifications';

    public function handle(
        NotificationService $notificationService
    ): int {
        $expiringSoon = Contractor::where('is_prequalified', true)
            ->where('prequalified_until', '>', now())
            ->where('prequalified_until', '<=', now()->addDays(30))
            ->get();

        foreach ($expiringSoon as $contractor) {
            $notificationService->notifyMany(
                recipients: $this->getQhsseTeamUsers($contractor),
                type: 'contractor.expiring_soon',
                context: [
                    'contractor' => $contractor->toArray(),
                    'company' => $contractor->company->toArray(),
                ],
                actor: SystemUser::get(),
                moduleName: 'contractor',
                referenceId: $contractor->id,
                actionUrl: "/contractors/{$contractor->id}",
            );
        }

        $this->info("Sent {$expiringSoon->count()} prequalification expiry notifications.");
        return self::SUCCESS;
    }
}
```

**Laravel Scheduler registration (`routes/console.php` or `app/Console/Kernel.php`):**

```php
Schedule::command('contractor:check-prequalification-expiry')
    ->dailyAt('08:00')
    ->withoutOverlapping();
```

### 7.8 Policy Registration

File: `app/Policies/Modules/ContractorManagement/ContractorPolicy.php`

```php
class ContractorPolicy
{
    public function view(User $user, Contractor $contractor): bool
    {
        return $user->can('contractor.management.view')
            && $this->withinScope($user, $contractor);
    }

    public function create(User $user): bool
    {
        return $user->can('contractor.management.create');
    }

    public function update(User $user, Contractor $contractor): bool
    {
        return $user->can('contractor.management.update')
            && $this->withinScope($user, $contractor);
    }

    public function evaluate(User $user, Contractor $contractor): bool
    {
        return $user->can('contractor.management.evaluate')
            && $this->withinScope($user, $contractor);
    }

    public function export(User $user): bool
    {
        return $user->can('contractor.management.export');
    }

    private function withinScope(User $user, Contractor $contractor): bool
    {
        // Super Admin / Admin bypass scope
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return true;
        }

        // Contractor role: only see own company
        if ($user->hasRole('Contractor')) {
            return $user->company_id === $contractor->company_id;
        }

        // QHSSE roles, Top Management, Auditor: all
        if ($user->hasRole(['QHSSE Manager', 'QHSSE Officer', 'Top Management', 'Auditor'])) {
            return true;
        }

        // Supervisor, Department Head, Employee: view all contractors
        return true;
    }
}
```

Register in `AuthServiceProvider` or `AppServiceProvider`:

```php
protected $policies = [
    Contractor::class => ContractorPolicy::class,
];
```
