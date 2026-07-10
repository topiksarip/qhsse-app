# API Contract — Asset & Equipment Safety

> Spec ini mendefinisikan semua route, request, response, dan error handling untuk modul Asset & Equipment Safety.

## 1. Route Table

Semua route diawali dengan prefix `/assets`, nama route `assets.*`, dan middleware `auth,verified`.

### 1.1 Asset Routes

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/assets` | `AssetController@index` | `assets.index` | `asset.management.view` | List assets with search/filter/pagination |
| GET | `/assets/create` | `AssetController@create` | `assets.create` | `asset.management.create` | Render create form |
| POST | `/assets` | `AssetController@store` | `assets.store` | `asset.management.create` | Save new asset |
| GET | `/assets/{asset}` | `AssetController@show` | `assets.show` | `asset.management.view` | Show asset detail |
| GET | `/assets/{asset}/edit` | `AssetController@edit` | `assets.edit` | `asset.management.update` | Render edit form |
| PUT | `/assets/{asset}` | `AssetController@update` | `assets.update` | `asset.management.update` | Update asset |
| GET | `/assets/export` | `AssetController@export` | `assets.export` | `asset.management.export` | Export filtered list as CSV |

### 1.2 Asset Certificate Routes

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| POST | `/assets/{asset}/certificates` | `AssetCertificateController@store` | `assets.certificates.store` | `asset.certificates.create` | Create new certificate |
| PUT | `/assets/{asset}/certificates/{certificate}` | `AssetCertificateController@update` | `assets.certificates.update` | `asset.certificates.update` | Update certificate |

### 1.3 Asset Inspection Routes

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| POST | `/assets/{asset}/inspections` | `AssetInspectionController@store` | `assets.inspections.store` | `asset.inspections.create` | Create new inspection |

### Route Registration

File: `routes/modules.php`

```php
use App\Http\Controllers\Modules\Asset\AssetController;
use App\Http\Controllers\Modules\Asset\AssetCertificateController;
use App\Http\Controllers\Modules\Asset\AssetInspectionController;

Route::middleware(['auth', 'verified'])
    ->prefix('assets')
    ->name('assets.')
    ->group(function (): void {
        // Asset CRUD
        Route::get('/', [AssetController::class, 'index'])
            ->name('index')
            ->middleware('permission:asset.management.view');

        Route::get('/create', [AssetController::class, 'create'])
            ->name('create')
            ->middleware('permission:asset.management.create');

        Route::post('/', [AssetController::class, 'store'])
            ->name('store')
            ->middleware('permission:asset.management.create');

        Route::get('/{asset}', [AssetController::class, 'show'])
            ->name('show')
            ->middleware('permission:asset.management.view');

        Route::get('/{asset}/edit', [AssetController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:asset.management.update');

        Route::put('/{asset}', [AssetController::class, 'update'])
            ->name('update')
            ->middleware('permission:asset.management.update');

        // Export
        Route::get('/export', [AssetController::class, 'export'])
            ->name('export')
            ->middleware('permission:asset.management.export');

        // Certificates (nested under asset)
        Route::post('/{asset}/certificates', [AssetCertificateController::class, 'store'])
            ->name('certificates.store')
            ->middleware('permission:asset.certificates.create');

        Route::put('/{asset}/certificates/{certificate}', [AssetCertificateController::class, 'update'])
            ->name('certificates.update')
            ->middleware('permission:asset.certificates.update');

        // Inspections (nested under asset)
        Route::post('/{asset}/inspections', [AssetInspectionController::class, 'store'])
            ->name('inspections.store')
            ->middleware('permission:asset.inspections.create');
    });
```

### Route Model Binding

- Asset parameter: `{asset}` → Laravel resolves to `Asset` model via route key (id).
- Certificate parameter: `{certificate}` → Laravel resolves to `AssetCertificate` model. Scoped to asset: `where('asset_id', $asset->id)`.

---

## 2. Request Payloads

### POST `/assets` (store)

```json
{
  "name": "Crane 50 Ton Kapasitas",
  "category": "lifting",
  "serial_number": "CRN-50T-001",
  "model": "XCMC-QY50K",
  "manufacturer": "XCMG",
  "site_id": 1,
  "area_id": 3,
  "department_id": 2,
  "purchase_date": "2025-01-15",
  "installation_date": "2025-02-20",
  "warranty_expiry": "2027-01-15",
  "safety_critical": true
}
```

**Validation Rules (StoreAssetRequest):**

| Field | Rule | Notes |
|---|---|---|
| `name` | `required\|string\|max:255` | |
| `category` | `required\|in:equipment,machinery,vehicle,safety_equipment,fire_equipment,lifting,other` | |
| `serial_number` | `nullable\|string\|max:255` | |
| `model` | `nullable\|string\|max:255` | |
| `manufacturer` | `nullable\|string\|max:255` | |
| `site_id` | `required\|exists:sites,id` | |
| `area_id` | `nullable\|exists:areas,id` | |
| `department_id` | `nullable\|exists:departments,id` | |
| `purchase_date` | `nullable\|date` | |
| `installation_date` | `nullable\|date` | |
| `warranty_expiry` | `nullable\|date` | |
| `safety_critical` | `boolean` | Default false |

**Controller behavior (store):**
1. Validate request
2. Create `Asset` with status `active`
3. Generate `asset_number` via `NumberingService::generate('asset', $actor, ...)`
4. `AuditService::created($asset, $actor, 'asset', $asset->id)`
5. `ActivityService::log('asset', $asset->id, 'asset.created', 'Asset created', $actor)`
6. Redirect to `assets.show`

### PUT `/assets/{asset}` (update)

Same payload as store. Only allowed if `status === 'active'`.

```json
{
  "name": "Crane 50 Ton Kapasitas (Updated)",
  "category": "lifting",
  "serial_number": "CRN-50T-001-REV",
  "model": "XCMC-QY50K",
  "manufacturer": "XCMG",
  "site_id": 1,
  "area_id": 3,
  "department_id": 2,
  "purchase_date": "2025-01-15",
  "installation_date": "2025-02-20",
  "warranty_expiry": "2027-01-15",
  "safety_critical": true,
  "status": "active"
}
```

**Validation Rules (UpdateAssetRequest):**

| Field | Rule | Notes |
|---|---|---|
| `name` | `required\|string\|max:255` | |
| `category` | `required\|in:equipment,machinery,vehicle,safety_equipment,fire_equipment,lifting,other` | |
| `serial_number` | `nullable\|string\|max:255` | |
| `model` | `nullable\|string\|max:255` | |
| `manufacturer` | `nullable\|string\|max:255` | |
| `site_id` | `required\|exists:sites,id` | |
| `area_id` | `nullable\|exists:areas,id` | |
| `department_id` | `nullable\|exists:departments,id` | |
| `purchase_date` | `nullable\|date` | |
| `installation_date` | `nullable\|date` | |
| `warranty_expiry` | `nullable\|date` | |
| `safety_critical` | `boolean` | |
| `status` | `in:active,inactive,decommissioned` | Only Admin/QHSSE Manager can decommission |

**Controller behavior (update):**
1. Check `asset.status === 'active'` (abort 403 if `decommissioned`)
2. Validate request
3. Record old values
4. Update asset
5. `AuditService::updated($asset, $oldValues, $actor, 'asset', $asset->id)`
6. Redirect to `assets.show`

### POST `/assets/{asset}/certificates` (store certificate)

```json
{
  "certificate_type": "Sertifikat Kalibrasi",
  "certificate_number": "SK-KAL-2025-001",
  "issued_date": "2025-01-15",
  "expiry_date": "2026-01-15",
  "issuing_body": "Sucofindo",
  "certificate_file": "(file upload)"
}
```

**Validation Rules (StoreAssetCertificateRequest):**

| Field | Rule | Notes |
|---|---|---|
| `certificate_type` | `required\|string\|max:100` | |
| `certificate_number` | `required\|string\|max:100` | |
| `issued_date` | `required\|date` | |
| `expiry_date` | `nullable\|date\|after_or_equal:issued_date` | Null = permanent |
| `issuing_body` | `required\|string\|max:255` | |
| `certificate_file` | `nullable\|file\|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx\|max:10240` | Max 10MB |

**Controller behavior (store certificate):**
1. Check `asset.status` is `active` or `inactive` (abort 403 if `decommissioned`)
2. Validate request
3. Upload file via `ManagedFileService` if provided (module_name='asset', reference_id=asset_id, collection='certificate')
4. Calculate initial status based on `expiry_date`
5. Create `AssetCertificate`
6. `AuditService::created($certificate, $actor, 'asset', $certificate->id)`
7. `ActivityService::log('asset', $asset->id, 'asset.certificate.created', "Certificate {$certificate->certificate_number} created", $actor)`
8. Redirect back to asset show page (certificates tab)

### PUT `/assets/{asset}/certificates/{certificate}` (update certificate)

```json
{
  "certificate_type": "Sertifikat Kalibrasi (Updated)",
  "certificate_number": "SK-KAL-2025-001-REV",
  "issued_date": "2025-01-15",
  "expiry_date": "2026-03-15",
  "issuing_body": "Sucofindo",
  "certificate_file": "(file upload, optional new file)"
}
```

Same validation as store. Controller:
1. Check `asset.status` is `active` or `inactive`
2. Validate request
3. If new file uploaded, replace old file via `ManagedFileService`
4. Update `AssetCertificate`
5. Recalculate status based on new `expiry_date`
6. `AuditService::updated($certificate, $oldValues, $actor, 'asset', $certificate->id)`
7. Redirect back

### POST `/assets/{asset}/inspections` (store inspection)

```json
{
  "inspection_date": "2026-02-15",
  "inspector_id": 5,
  "result": "fail",
  "notes": "Ditemukan crack pada hook crane. Crane tidak boleh dioperasikan.",
  "next_inspection_date": "2026-05-15"
}
```

**Validation Rules (StoreAssetInspectionRequest):**

| Field | Rule | Notes |
|---|---|---|
| `inspection_date` | `required\|date` | |
| `inspector_id` | `required\|exists:users,id` | |
| `result` | `required\|in:pass,fail,maintenance_required` | |
| `notes` | `nullable\|string` | |
| `next_inspection_date` | `nullable\|date\|after_or_equal:inspection_date` | |

**Controller behavior (store inspection):**
1. Check `asset.status` is `active` or `inactive` (abort 403 if `decommissioned`)
2. Validate request
3. Create `AssetInspection`
4. `AuditService::created($inspection, $actor, 'asset', $inspection->id)`
5. `ActivityService::log('asset', $asset->id, 'asset.inspection.created', "Inspection created: {$result}", $actor)`
6. If `result = 'fail'`, return with warning: "Inspeksi gagal. Mohon buat CAPA untuk menindaklanjuti."
7. Redirect back to asset show page (inspections tab)

---

## 3. Inertia Response Props

### Index Page (`Asset/Index.tsx`)

```typescript
{
  assets: {
    data: Asset[],
    current_page: number,
    last_page: number,
    per_page: number,
    total: number,
  },
  filters: {
    search: string,
    status: string | null,
    category: string | null,
    site_id: number | null,
    safety_critical: boolean | null,
  },
  sites: Site[],
  can: {
    create: boolean,
    export: boolean,
  },
}
```

### Create/Edit Page (`Asset/Form.tsx`)

```typescript
{
  asset: Asset | null,        // null for create, populated for edit
  sites: Site[],
  areas: Area[],              // pre-filtered by site if edit
  departments: Department[],
  can: {
    update: boolean,
  },
}
```

### Show Page (`Asset/Show.tsx`)

```typescript
{
  asset: Asset & {
    site: Site,
    area: Area | null,
    department: Department | null,
    certificates: (AssetCertificate & {
      certificate_file: ManagedFile | null,
    })[],
    inspections: (AssetInspection & {
      inspector: User,
      capa_action: CapaAction | null,
    })[],
    comments: Comment[],
    activities: ActivityLog[],
  },
  can: {
    update: boolean,
    create_certificate: boolean,
    update_certificate: boolean,
    create_inspection: boolean,
  },
}
```

---

## 4. ListQuery Parameters

The index page accepts these query parameters for filtering:

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `asset_number`, `name`, and `serial_number` |
| `status` | string | `null` | Filter by: active, inactive, decommissioned |
| `category` | string | `null` | Filter by: equipment, machinery, vehicle, safety_equipment, fire_equipment, lifting, other |
| `site_id` | int | `null` | Filter by site |
| `safety_critical` | boolean | `null` | Filter safety-critical assets only (1=true) |
| `per_page` | int | `15` | Items per page (max 100) |
| `sort` | string | `created_at` | Sort column |
| `direction` | string | `desc` | Sort direction (asc/desc) |

### Controller index method pattern:

```php
public function index(ListQuery $listQuery): Response
{
    $items = $listQuery->paginate(
        Asset::query()->with(['site', 'area', 'department', 'certificates']),
        ['asset_number', 'name', 'serial_number'],
        ['created_at', 'asset_number', 'name'],
        'created_at',
        15,
    );

    return Inertia::render('Modules/Asset/Index', [
        'assets' => $items,
        'filters' => $listQuery->filters(),
        'sites' => Site::where('is_active', true)->get(['id', 'name']),
        'can' => [
            'create' => auth()->user()->can('asset.management.create'),
            'export' => auth()->user()->can('asset.management.export'),
        ],
    ]);
}
```

---

## 5. CSV Export Specification

Endpoint: `GET /assets/export?search=...&status=...&category=...`

Applies same ListQuery filters, then streams CSV via `CsvExporter`.

### CSV Columns:

| Column Header | Source |
|---|---|
| `Nomor Aset` | `asset_number` |
| `Nama` | `name` |
| `Kategori` | `category` |
| `Serial Number` | `serial_number` |
| `Model` | `model` |
| `Manufacturer` | `manufacturer` |
| `Site` | `site.name` |
| `Area` | `area.name` |
| `Department` | `department.name` |
| `Tanggal Pembelian` | `purchase_date` |
| `Tanggal Instalasi` | `installation_date` |
| `Masa Garansi` | `warranty_expiry` |
| `Status` | `status` |
| `Safety Critical` | `safety_critical` (Yes/No) |
| `Total Sertifikat` | count of certificates |
| `Sertifikat Expired` | count(certificates where status=expired) |
| `Sertifikat Expiring` | count(certificates where status in expiring_soon/expiring_critical) |
| `Inspeksi Terakhir` | latest inspection_date |
| `Inspeksi Berikutnya` | next_inspection_date of latest inspection |
| `Created At` | `created_at` |

### Controller export method pattern:

```php
public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
{
    $query = $listQuery->apply(
        Asset::query()->with(['site', 'area', 'department', 'certificates', 'inspections']),
        ['asset_number', 'name', 'serial_number'],
        ['created_at', 'asset_number'],
        'created_at',
    );

    return $exporter->stream($query, [
        'Nomor Aset' => 'asset_number',
        'Nama' => 'name',
        'Kategori' => 'category',
        'Serial Number' => fn ($item) => $item->serial_number ?? '',
        'Model' => fn ($item) => $item->model ?? '',
        'Manufacturer' => fn ($item) => $item->manufacturer ?? '',
        'Site' => fn ($item) => $item->site?->name ?? '',
        'Area' => fn ($item) => $item->area?->name ?? '',
        'Department' => fn ($item) => $item->department?->name ?? '',
        'Tanggal Pembelian' => fn ($item) => $item->purchase_date?->format('Y-m-d') ?? '',
        'Tanggal Instalasi' => fn ($item) => $item->installation_date?->format('Y-m-d') ?? '',
        'Masa Garansi' => fn ($item) => $item->warranty_expiry?->format('Y-m-d') ?? '',
        'Status' => 'status',
        'Safety Critical' => fn ($item) => $item->safety_critical ? 'Yes' : 'No',
        'Total Sertifikat' => fn ($item) => $item->certificates->count(),
        'Sertifikat Expired' => fn ($item) => $item->certificates->where('status', 'expired')->count(),
        'Sertifikat Expiring' => fn ($item) => $item->certificates->whereIn('status', ['expiring_soon', 'expiring_critical'])->count(),
        'Inspeksi Terakhir' => fn ($item) => $item->inspections->sortByDesc('inspection_date')->first()?->inspection_date?->format('Y-m-d') ?? '',
        'Inspeksi Berikutnya' => fn ($item) => $item->inspections->sortByDesc('inspection_date')->first()?->next_inspection_date?->format('Y-m-d') ?? '',
        'Created At' => fn ($item) => $item->created_at?->format('Y-m-d H:i:s') ?? '',
    ], 'assets-export.csv');
}
```

---

## 6. Error Responses

### Standard Error Responses

| Status Code | Condition | Response |
|---|---|---|
| 403 | User lacks permission | Redirect back with error "Anda tidak memiliki izin untuk melakukan aksi ini." |
| 403 | Asset status is `decommissioned` and user tries to edit | Redirect back with error "Aset yang telah decommissioned tidak dapat diedit." |
| 404 | Asset not found | Laravel default 404 page |
| 422 | Validation failed | Redirect back with `$errors` bag containing field errors |
| 422 | Certificate expiry_date before issued_date | Error on `expiry_date` field |

### Validation Error Example

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."],
    "category": ["The selected category is invalid."],
    "site_id": ["The selected site_id is invalid."]
  }
}
```

---

## 7. Integration Points

### 7.1 CAPA Module (04)

When an inspection result is `fail`, a CAPA action can be created:

```
POST /capa-actions
{
  "source_module": "asset_inspection",
  "source_reference_id": {asset_inspections.id},
  "title": "CAPA untuk inspeksi gagal aset {asset.asset_number}",
  ...
}
```

The CAPA module stores the linkage via `source_module` and `source_reference_id` columns on the `capa_actions` table. No FK from `asset_inspections` to `capa_actions` — the relation is polymorphic and queried from the CAPA side.

### 7.2 Inspection Module (05)

Future integration: asset inspections may reference inspection checklists from module 05. The `asset_inspections` table does not have a direct FK to `inspections` (module 05) but can be extended with a nullable `inspection_id` FK in the future.

### 7.3 Document Control (07)

Certificate files are stored via the core `ManagedFileService` with `module_name='asset'` and `collection='certificate'`. Future integration may link certificates to controlled document versions in module 07.

### 7.4 Scheduled Jobs

| Job | Schedule | Description |
|---|---|---|
| `AssetCertificateStatusJob` | Daily at 06:00 | Updates certificate status based on expiry_date, sends notifications |
| `AssetInspectionDueJob` | Daily at 06:30 | Checks for assets with next_inspection_date approaching, sends notifications |

### 7.5 Certificate Status Calculation Logic

```php
// App\Models\Modules\Asset\AssetCertificate.php

public function calculateStatus(): string
{
    if ($this->expiry_date === null) {
        return 'valid';
    }

    $now = now();
    $expiry = Carbon::parse($this->expiry_date);

    if ($expiry->isPast()) {
        return 'expired';
    }

    $daysUntilExpiry = $now->diffInDays($expiry);

    if ($daysUntilExpiry <= 7) {
        return 'expiring_critical';
    }

    if ($daysUntilExpiry <= 30) {
        return 'expiring_soon';
    }

    return 'valid';
}
```

### 7.6 Notification Integration

Notifications are sent via `NotificationService`:

```php
// When certificate status changes to expired
$this->notificationService->notifyMany(
    recipients: $this->getAssetStakeholders($asset),
    type: 'asset.certificate.expired',
    context: [
        'asset' => $asset->toArray(),
        'certificate' => $certificate->toArray(),
    ],
    actor: $systemUser,
    moduleName: 'asset',
    referenceId: $asset->id,
    actionUrl: "/assets/{$asset->id}?tab=certificates",
);
```
