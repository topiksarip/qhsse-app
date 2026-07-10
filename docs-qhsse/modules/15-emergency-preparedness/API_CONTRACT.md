# API Contract — Emergency Preparedness

> Spec ini mendefinisikan semua route, request, response, dan error handling untuk modul Emergency Preparedness.

## 1. Route Table

Modul ini memiliki 3 resource groups dengan prefix terpisah. Semua route menggunakan middleware `auth,verified`.

### 1.1 Emergency Plans — prefix `/emergency-plans`, name `emergency-plans.*`

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/emergency-plans` | `index` | `emergency-plans.index` | `emergency.plans.view` | List plans with search/filter/pagination |
| GET | `/emergency-plans/create` | `create` | `emergency-plans.create` | `emergency.plans.create` | Render create form |
| POST | `/emergency-plans` | `store` | `emergency-plans.store` | `emergency.plans.create` | Save new plan |
| GET | `/emergency-plans/{plan}` | `show` | `emergency-plans.show` | `emergency.plans.view` | Show plan detail |
| GET | `/emergency-plans/{plan}/edit` | `edit` | `emergency-plans.edit` | `emergency.plans.update` | Render edit form |
| PUT | `/emergency-plans/{plan}` | `update` | `emergency-plans.update` | `emergency.plans.update` | Update plan |
| GET | `/emergency-plans/export` | `export` | `emergency-plans.export` | `emergency.plans.export` | Export filtered list as CSV |

### 1.2 Emergency Drills — prefix `/emergency-drills`, name `emergency-drills.*`

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/emergency-drills` | `index` | `emergency-drills.index` | `emergency.drills.view` | List drills with search/filter/pagination |
| GET | `/emergency-drills/create` | `create` | `emergency-drills.create` | `emergency.drills.create` | Render create form |
| POST | `/emergency-drills` | `store` | `emergency-drills.store` | `emergency.drills.create` | Save new drill |
| GET | `/emergency-drills/{drill}` | `show` | `emergency-drills.show` | `emergency.drills.view` | Show drill detail |
| GET | `/emergency-drills/{drill}/edit` | `edit` | `emergency-drills.edit` | `emergency.drills.update` | Render edit form |
| PUT | `/emergency-drills/{drill}` | `update` | `emergency-drills.update` | `emergency.drills.update` | Update drill (before execution) |
| POST | `/emergency-drills/{drill}/execute` | `execute` | `emergency-drills.execute` | `emergency.drills.execute` | Execute drill: set result, findings, status → executed |
| GET | `/emergency-drills/export` | `export` | `emergency-drills.export` | `emergency.drills.export` | Export filtered list as CSV |

### 1.3 Emergency Contacts — prefix `/emergency-contacts`, name `emergency-contacts.*`

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/emergency-contacts` | `index` | `emergency-contacts.index` | `emergency.contacts.view` | List contacts with search/filter/pagination |
| GET | `/emergency-contacts/create` | `create` | `emergency-contacts.create` | `emergency.contacts.create` | Render create form |
| POST | `/emergency-contacts` | `store` | `emergency-contacts.store` | `emergency.contacts.create` | Save new contact |
| GET | `/emergency-contacts/{contact}/edit` | `edit` | `emergency-contacts.edit` | `emergency.contacts.update` | Render edit form |
| PUT | `/emergency-contacts/{contact}` | `update` | `emergency-contacts.update` | `emergency.contacts.update` | Update contact |

### Route Registration

File: `routes/modules.php`

```php
use App\Http\Controllers\Modules\Emergency\EmergencyPlanController;
use App\Http\Controllers\Modules\Emergency\EmergencyDrillController;
use App\Http\Controllers\Modules\Emergency\EmergencyContactController;

// Emergency Plans
Route::middleware(['auth', 'verified'])
    ->prefix('emergency-plans')
    ->name('emergency-plans.')
    ->group(function (): void {
        Route::get('/', [EmergencyPlanController::class, 'index'])
            ->name('index')->middleware('permission:emergency.plans.view');
        Route::get('/create', [EmergencyPlanController::class, 'create'])
            ->name('create')->middleware('permission:emergency.plans.create');
        Route::post('/', [EmergencyPlanController::class, 'store'])
            ->name('store')->middleware('permission:emergency.plans.create');
        Route::get('/{plan}', [EmergencyPlanController::class, 'show'])
            ->name('show')->middleware('permission:emergency.plans.view');
        Route::get('/{plan}/edit', [EmergencyPlanController::class, 'edit'])
            ->name('edit')->middleware('permission:emergency.plans.update');
        Route::put('/{plan}', [EmergencyPlanController::class, 'update'])
            ->name('update')->middleware('permission:emergency.plans.update');
        Route::get('/export', [EmergencyPlanController::class, 'export'])
            ->name('export')->middleware('permission:emergency.plans.export');
    });

// Emergency Drills
Route::middleware(['auth', 'verified'])
    ->prefix('emergency-drills')
    ->name('emergency-drills.')
    ->group(function (): void {
        Route::get('/', [EmergencyDrillController::class, 'index'])
            ->name('index')->middleware('permission:emergency.drills.view');
        Route::get('/create', [EmergencyDrillController::class, 'create'])
            ->name('create')->middleware('permission:emergency.drills.create');
        Route::post('/', [EmergencyDrillController::class, 'store'])
            ->name('store')->middleware('permission:emergency.drills.create');
        Route::get('/{drill}', [EmergencyDrillController::class, 'show'])
            ->name('show')->middleware('permission:emergency.drills.view');
        Route::get('/{drill}/edit', [EmergencyDrillController::class, 'edit'])
            ->name('edit')->middleware('permission:emergency.drills.update');
        Route::put('/{drill}', [EmergencyDrillController::class, 'update'])
            ->name('update')->middleware('permission:emergency.drills.update');
        Route::post('/{drill}/execute', [EmergencyDrillController::class, 'execute'])
            ->name('execute')->middleware('permission:emergency.drills.execute');
        Route::get('/export', [EmergencyDrillController::class, 'export'])
            ->name('export')->middleware('permission:emergency.drills.export');
    });

// Emergency Contacts
Route::middleware(['auth', 'verified'])
    ->prefix('emergency-contacts')
    ->name('emergency-contacts.')
    ->group(function (): void {
        Route::get('/', [EmergencyContactController::class, 'index'])
            ->name('index')->middleware('permission:emergency.contacts.view');
        Route::get('/create', [EmergencyContactController::class, 'create'])
            ->name('create')->middleware('permission:emergency.contacts.create');
        Route::post('/', [EmergencyContactController::class, 'store'])
            ->name('store')->middleware('permission:emergency.contacts.create');
        Route::get('/{contact}/edit', [EmergencyContactController::class, 'edit'])
            ->name('edit')->middleware('permission:emergency.contacts.update');
        Route::put('/{contact}', [EmergencyContactController::class, 'update'])
            ->name('update')->middleware('permission:emergency.contacts.update');
    });
```

### Route Model Binding

- Plans: parameter `{plan}` → `EmergencyPlan` model via `id`
- Drills: parameter `{drill}` → `EmergencyDrill` model via `id`
- Contacts: parameter `{contact}` → `EmergencyContact` model via `id`

---

## 2. Request Payloads

### 2.1 POST `/emergency-plans` (store)

```json
{
  "name": "Rencana Kebakaran Plant A",
  "type": "fire",
  "site_id": 1,
  "description": "Rencana respons untuk kebakaran di area produksi Plant A.",
  "response_procedure": "1. Aktifkan alarm kebakaran\n2. Hubungi pemadam kebakaran (119)\n3. Evakuasi karyawan ke titik kumpul\n4. Lakukan headcount\n5. Gunakan APAR untuk api kecil",
  "escalation_procedure": "1. Laporkan ke Supervisor\n2. Eskalasi ke QHSSE Officer dalam 5 menit\n3. Eskalasi ke QHSSE Manager dalam 15 menit",
  "contact_person_id": 3,
  "emergency_contacts": [
    {"name": "Budi Santoso", "role": "Fire Warden", "phone": "+62-812-3456-7890"},
    {"name": "Sari Wijaya", "role": "First Aider", "phone": "+62-813-9876-5432"}
  ],
  "equipment_needed": "APAR (4 unit), Hydrant (2 unit), Smoke Detector, Eye Wash Station"
}
```

**Validation Rules (StoreEmergencyPlanRequest):**

| Field | Rule | Notes |
|---|---|---|
| `name` | `required|string|max:255` | |
| `type` | `required|in:fire,medical,spill,evacuation,natural_disaster,security,other` | |
| `site_id` | `required|exists:sites,id` | |
| `description` | `required|string` | |
| `response_procedure` | `required|string` | |
| `escalation_procedure` | `required|string` | |
| `contact_person_id` | `required|exists:users,id` | |
| `emergency_contacts` | `nullable|array` | JSON array of contact objects |
| `emergency_contacts.*.name` | `required_with:emergency_contacts|string|max:255` | |
| `emergency_contacts.*.role` | `required_with:emergency_contacts|string|max:255` | |
| `emergency_contacts.*.phone` | `required_with:emergency_contacts|string|max:50` | |
| `equipment_needed` | `nullable|string` | |

**Controller behavior (store):**

1. Validate request
2. Create `EmergencyPlan` with all fields
3. Generate `plan_number` via `NumberingService::generate('emergency', $actor, ...)`
4. `AuditService::created($plan, $actor, 'emergency', $plan->id)`
5. `ActivityService::log('emergency', $plan->id, 'emergency.plan_created', ...)`
6. `NotificationService::notifyMany()` to QHSSE team in same site
7. Redirect to `emergency-plans.show`

### 2.2 PUT `/emergency-plans/{plan}` (update)

Same payload as store. `plan_number` cannot be changed (locked). Records audit trail for changed fields via `AuditService::updated()`.

### 2.3 POST `/emergency-drills` (store)

```json
{
  "emergency_plan_id": 1,
  "scheduled_date": "2026-07-15",
  "site_id": 1,
  "observer_id": 5
}
```

**Validation Rules (StoreEmergencyDrillRequest):**

| Field | Rule | Notes |
|---|---|---|
| `emergency_plan_id` | `required|exists:emergency_plans,id` | |
| `scheduled_date` | `required|date` | |
| `site_id` | `required|exists:sites,id` | |
| `observer_id` | `required|exists:users,id` | |

**Controller behavior (store):**

1. Validate request
2. Create `EmergencyDrill` with `status` = `'scheduled'`
3. Generate `drill_number` via `NumberingService::generate('emergency', $actor, ...)`
4. `AuditService::created($drill, $actor, 'emergency', $drill->id)`
5. `ActivityService::log('emergency', $drill->id, 'emergency.drill_scheduled', ...)`
6. `NotificationService::notifyMany()` to QHSSE team + observer
7. Redirect to `emergency-drills.show`

### 2.4 PUT `/emergency-drills/{drill}` (update)

Same payload as store. Only allowed if `status` = `scheduled`. Cannot change `drill_number`.

### 2.5 POST `/emergency-drills/{drill}/execute` (execute)

```json
{
  "executed_date": "2026-07-15",
  "participants_count": 50,
  "result": "pass",
  "findings": "Semua peserta berhasil evakuasi dalam 3 menit. Alarm berfungsi dengan baik.",
  "recommendations": "Tambah APAR di area warehouse. Latihan ulang dalam 6 bulan."
}
```

**Validation Rules (ExecuteEmergencyDrillRequest):**

| Field | Rule | Notes |
|---|---|---|
| `executed_date` | `required|date` | |
| `participants_count` | `required|integer|min:0` | |
| `result` | `required|in:pass,fail,needs_improvement` | |
| `findings` | `nullable|string` | |
| `recommendations` | `nullable|string` | |

**Controller behavior (execute):**

1. Check `drill.status === 'scheduled'`
2. Update drill with `executed_date`, `participants_count`, `result`, `findings`, `recommendations`
3. Set `status` = `'executed'`
4. `AuditService::log('emergency.drill_executed', $drill, old, new, $actor, 'emergency', $drill->id)`
5. `ActivityService::log('emergency', $drill->id, 'emergency.drill_executed', ...)`
6. `NotificationService::notifyMany()` to QHSSE Manager + plan contact_person
7. If `result` = `fail` or `needs_improvement`: send `emergency.drill_failed` notification
8. Redirect to `emergency-drills.show`

### 2.6 POST `/emergency-contacts` (store)

```json
{
  "name": "Budi Santoso",
  "role": "Fire Warden",
  "phone": "+62-812-3456-7890",
  "email": "budi.santoso@company.com",
  "site_id": 1,
  "is_active": true
}
```

**Validation Rules (StoreEmergencyContactRequest):**

| Field | Rule | Notes |
|---|---|---|
| `name` | `required|string|max:255` | |
| `role` | `required|string|max:255` | |
| `phone` | `required|string|max:50` | |
| `email` | `nullable|email|max:255` | |
| `site_id` | `required|exists:sites,id` | |
| `is_active` | `boolean` | Default: true |

### 2.7 PUT `/emergency-contacts/{contact}` (update)

Same payload as store.

---

## 3. Inertia Response Props

### 3.1 Plan Index Page

```typescript
{
  plans: {
    data: EmergencyPlan[],
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
    site_id: number | null,
  },
  sites: Site[],
  can: {
    create: boolean,
    export: boolean,
  },
}
```

### 3.2 Plan Form Page

```typescript
{
  plan: EmergencyPlan | null,
  sites: Site[],
  users: User[],
  can: {
    create: boolean,
    update: boolean,
  },
}
```

### 3.3 Plan Show Page

```typescript
{
  plan: EmergencyPlan & {
    site: Site,
    contactPerson: User,
    drills: EmergencyDrill[],
  },
  evidence: ManagedFile[],
  comments: Comment[],
  activities: ActivityLog[],
  can: {
    update: boolean,
    export: boolean,
    createDrill: boolean,
  },
}
```

### 3.4 Drill Index Page

```typescript
{
  drills: {
    data: EmergencyDrill[],
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
    result: string | null,
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

### 3.5 Drill Form Page

```typescript
{
  drill: EmergencyDrill | null,
  plans: EmergencyPlan[],
  sites: Site[],
  users: User[],
  can: {
    create: boolean,
    update: boolean,
  },
}
```

### 3.6 Drill Show Page

```typescript
{
  drill: EmergencyDrill & {
    emergencyPlan: EmergencyPlan,
    site: Site,
    observer: User,
  },
  activities: ActivityLog[],
  can: {
    update: boolean,
    execute: boolean,
    export: boolean,
  },
}
```

### 3.7 Contact Index Page

```typescript
{
  contacts: {
    data: EmergencyContact[],
    current_page: number,
    last_page: number,
    per_page: number,
    total: number,
    from: number | null,
    to: number | null,
  },
  filters: {
    search: string,
    site_id: number | null,
    is_active: boolean | null,
  },
  sites: Site[],
  can: {
    create: boolean,
    update: boolean,
  },
}
```

---

## 4. ListQuery Parameters

### 4.1 Emergency Plans Index

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `plan_number` and `name` (OR) |
| `type` | string | `null` | Filter by exact type |
| `site_id` | int | `null` | Filter by site |
| `per_page` | int | `15` | Items per page (max 100) |
| `sort` | string | `created_at` | Sort column |
| `direction` | string | `desc` | Sort direction (asc/desc) |

```php
public function index(ListQuery $listQuery): Response
{
    $items = $listQuery->paginate(
        EmergencyPlan::query()->with(['site', 'contactPerson']),
        ['plan_number', 'name'],
        ['created_at', 'plan_number'],
        'created_at',
        15,
    );

    return Inertia::render('Modules/Emergency/Plans/Index', [
        'plans' => $items,
        'filters' => $listQuery->filters(),
        'sites' => Site::where('is_active', true)->get(['id', 'name']),
        'can' => [
            'create' => auth()->user()->can('emergency.plans.create'),
            'export' => auth()->user()->can('emergency.plans.export'),
        ],
    ]);
}
```

### 4.2 Emergency Drills Index

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `drill_number` |
| `status` | string | `null` | Filter by status: scheduled, executed |
| `result` | string | `null` | Filter by result: pass, fail, needs_improvement |
| `site_id` | int | `null` | Filter by site |
| `from` | string | `null` | Filter scheduled_date from (YYYY-MM-DD) |
| `to` | string | `null` | Filter scheduled_date to (YYYY-MM-DD) |
| `per_page` | int | `15` | Items per page (max 100) |
| `sort` | string | `scheduled_date` | Sort column |
| `direction` | string | `desc` | Sort direction |

```php
public function index(ListQuery $listQuery): Response
{
    $items = $listQuery->paginate(
        EmergencyDrill::query()->with(['emergencyPlan', 'site', 'observer']),
        ['drill_number'],
        ['scheduled_date', 'executed_date', 'drill_number', 'created_at'],
        'scheduled_date',
        15,
    );

    return Inertia::render('Modules/Emergency/Drills/Index', [
        'drills' => $items,
        'filters' => $listQuery->filters(),
        'sites' => Site::where('is_active', true)->get(['id', 'name']),
        'can' => [
            'create' => auth()->user()->can('emergency.drills.create'),
            'export' => auth()->user()->can('emergency.drills.export'),
        ],
    ]);
}
```

### 4.3 Emergency Contacts Index

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `name`, `phone` (OR) |
| `site_id` | int | `null` | Filter by site |
| `is_active` | boolean | `null` | Filter by active status |
| `per_page` | int | `15` | Items per page (max 100) |
| `sort` | string | `created_at` | Sort column |
| `direction` | string | `desc` | Sort direction |

---

## 5. CSV Export Specification

### 5.1 Emergency Plans Export

Endpoint: `GET /emergency-plans/export?search=...&type=...&site_id=...`

```php
public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
{
    $query = $listQuery->apply(
        EmergencyPlan::query()->with(['site', 'contactPerson']),
        ['plan_number', 'name'],
        ['created_at'],
        'created_at',
    );

    return $exporter->stream($query, [
        'Nomor'         => 'plan_number',
        'Nama'          => 'name',
        'Tipe'          => 'type',
        'Site'          => fn ($item) => $item->site?->name ?? '',
        'Deskripsi'     => fn ($item) => Str::limit($item->description, 500),
        'Kontak Person' => fn ($item) => $item->contactPerson?->name ?? '',
        'Peralatan'     => fn ($item) => $item->equipment_needed ?? '',
        'Dibuat Pada'   => fn ($item) => $item->created_at->format('Y-m-d H:i'),
    ], 'emergency_plans_export_' . now()->format('Ymd_His') . '.csv');
}
```

### 5.2 Emergency Drills Export

Endpoint: `GET /emergency-drills/export?search=...&status=...&result=...&site_id=...`

```php
public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
{
    $query = $listQuery->apply(
        EmergencyDrill::query()->with(['emergencyPlan', 'site', 'observer']),
        ['drill_number'],
        ['scheduled_date', 'executed_date'],
        'scheduled_date',
    );

    return $exporter->stream($query, [
        'Nomor'            => 'drill_number',
        'Rencana Darurat'  => fn ($item) => $item->emergencyPlan?->plan_number ?? '',
        'Site'             => fn ($item) => $item->site?->name ?? '',
        'Tanggal Terjadwal'=> fn ($item) => $item->scheduled_date?->format('Y-m-d') ?? '',
        'Tanggal Eksekusi' => fn ($item) => $item->executed_date?->format('Y-m-d') ?? '',
        'Peserta'          => fn ($item) => $item->participants_count ?? '',
        'Observer'         => fn ($item) => $item->observer?->name ?? '',
        'Hasil'            => 'result',
        'Status'           => 'status',
        'Temuan'           => fn ($item) => Str::limit($item->findings ?? '', 500),
        'Rekomendasi'      => fn ($item) => Str::limit($item->recommendations ?? '', 500),
    ], 'emergency_drills_export_' . now()->format('Ymd_His') . '.csv');
}
```

---

## 6. Error Responses

| Status | When | Response |
|---|---|---|
| `403` | User lacks required permission | Inertia redirects to dashboard with error flash |
| `404` | Record ID not found | Laravel default 404 page |
| `422` | Validation failure | Inertia sends errors back to form with `$page.props.errors` |
| `400` | Invalid drill execution (wrong status) | RuntimeException caught → redirect back with error flash |
| `419` | CSRF token expired | Laravel default |

### Invalid drill execution handling:

```php
try {
    if ($drill->status !== 'scheduled') {
        throw new RuntimeException('Latihan darurat sudah dieksekusi dan tidak dapat diubah.');
    }

    $drill->update([
        'executed_date' => $request->executed_date,
        'participants_count' => $request->participants_count,
        'result' => $request->result,
        'findings' => $request->findings,
        'recommendations' => $request->recommendations,
        'status' => 'executed',
    ]);

    // ... audit, activity, notification

} catch (RuntimeException $e) {
    return back()->withErrors(['status' => $e->getMessage()]);
}
```

---

## 7. Permission Enforcement Points

| Layer | How |
|---|---|
| **Route middleware** | `->middleware('permission:emergency.plans.view')` on each route |
| **Controller authorize()** | `$this->authorize('view', $plan)` for show/edit (scope filtering) |
| **Inertia shared props** | `auth.permissions` array → frontend checks via `permissions.has('emergency.plans.create')` |
| **Export** | Route middleware `permission:emergency.plans.export` / `emergency.drills.export` |

---

## 8. Numbering Integration

On `store` (plans):

```php
$generatedNumber = app(NumberingService::class)->generate(
    moduleName: 'emergency',
    actor: $actor,
    referenceType: EmergencyPlan::class,
    referenceId: $plan->id,
);
// $generatedNumber->formatted → 'EMG-2026-0001'
$plan->update(['plan_number' => $generatedNumber->formatted]);
```

On `store` (drills):

```php
$generatedNumber = app(NumberingService::class)->generate(
    moduleName: 'emergency',
    actor: $actor,
    referenceType: EmergencyDrill::class,
    referenceId: $drill->id,
);
// $generatedNumber->formatted → 'EMG-2026-0005'
$drill->update(['drill_number' => $generatedNumber->formatted]);
```

> **Note:** Plans and drills share the same `emergency` numbering sequence. The next number after `EMG-2026-0001` (plan) will be `EMG-2026-0002` (could be either a plan or a drill).

---

## 9. Integration Points

### 9.1 Training Module (08-training-competency)

- Drill `observer_id` can reference a user who is also a training instructor.
- Future: link drill to training session for competency tracking.

### 9.2 Asset Module (16-asset-management)

- `equipment_needed` text field can reference asset names.
- Future: structured equipment linkage with asset IDs.

### 9.3 Communication Module (18-communication)

- `emergency_contacts` table provides contact directory for emergency broadcast.
- `emergency_contacts` JSON on plans provides plan-specific contacts for targeted alerts.
- Future: one-click broadcast to all active emergency contacts for a site.

### 9.4 Core File Service

- Evidence files attached to emergency plans via `managed_files` table.
- `module_name='emergency'`, `reference_id=plan.id`, `collection='evidence'`.

### 9.5 Core Comment Service

- Comments on emergency plans via `comments` table.
- `module_name='emergency'`, `reference_id=plan.id`.

### 9.6 Core Notification Service

- 4 notification types: `emergency.plan_created`, `emergency.drill_scheduled`, `emergency.drill_executed`, `emergency.drill_failed`.
- Sent via `NotificationService::notify()` / `notifyMany()`.
