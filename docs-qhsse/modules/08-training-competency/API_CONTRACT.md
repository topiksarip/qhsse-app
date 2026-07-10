# API Contract — Training & Competency

> Spec ini mendefinisikan semua route, request, response, dan error handling untuk modul Training & Competency.

## 1. Route Table

Modul ini memiliki 3 route groups: training programs, training records, dan training matrix. Semua route menggunakan middleware `auth,verified`.

### 1.1 Training Programs

Prefix: `/training-programs`, nama route `training.programs.*`

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/training-programs` | `index` | `training.programs.index` | `training.programs.view` | List programs with search/filter/pagination |
| GET | `/training-programs/create` | `create` | `training.programs.create` | `training.programs.create` | Render create form |
| POST | `/training-programs` | `store` | `training.programs.store` | `training.programs.create` | Save new program |
| GET | `/training-programs/{program}` | `show` | `training.programs.show` | `training.programs.view` | Show program detail |
| GET | `/training-programs/{program}/edit` | `edit` | `training.programs.edit` | `training.programs.update` | Render edit form |
| PUT/PATCH | `/training-programs/{program}` | `update` | `training.programs.update` | `training.programs.update` | Update program |

### 1.2 Training Records

Prefix: `/training-records`, nama route `training.records.*`

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/training-records` | `index` | `training.records.index` | `training.records.view` | List records with search/filter/pagination |
| GET | `/training-records/create` | `create` | `training.records.create` | `training.records.create` | Render create form |
| POST | `/training-records` | `store` | `training.records.store` | `training.records.create` | Save new record (generates TRN number) |
| GET | `/training-records/{record}` | `show` | `training.records.show` | `training.records.view` | Show record detail |
| GET | `/training-records/{record}/edit` | `edit` | `training.records.edit` | `training.records.update` | Render edit form |
| PUT/PATCH | `/training-records/{record}` | `update` | `training.records.update` | `training.records.update` | Update record (status, score, certificate) |
| GET | `/training-records/export` | `export` | `training.records.export` | `training.records.export` | Export filtered list as CSV |

### 1.3 Training Matrix

Prefix: `/training/matrix`, nama route `training.matrix.*`

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/training/matrix` | `index` | `training.matrix.index` | `training.records.view` | Matrix grid: employees × programs |

### Route Registration

File: `routes/modules.php`

```php
use App\Http\Controllers\Modules\Training\TrainingProgramController;
use App\Http\Controllers\Modules\Training\TrainingRecordController;
use App\Http\Controllers\Modules\Training\TrainingMatrixController;

// Training Programs
Route::middleware(['auth', 'verified'])
    ->prefix('training-programs')
    ->name('training.programs.')
    ->group(function (): void {
        Route::get('/', [TrainingProgramController::class, 'index'])
            ->name('index')
            ->middleware('permission:training.programs.view');

        Route::get('/create', [TrainingProgramController::class, 'create'])
            ->name('create')
            ->middleware('permission:training.programs.create');

        Route::post('/', [TrainingProgramController::class, 'store'])
            ->name('store')
            ->middleware('permission:training.programs.create');

        Route::get('/{program}', [TrainingProgramController::class, 'show'])
            ->name('show')
            ->middleware('permission:training.programs.view');

        Route::get('/{program}/edit', [TrainingProgramController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:training.programs.update');

        Route::put('/{program}', [TrainingProgramController::class, 'update'])
            ->name('update')
            ->middleware('permission:training.programs.update');
    });

// Training Records
Route::middleware(['auth', 'verified'])
    ->prefix('training-records')
    ->name('training.records.')
    ->group(function (): void {
        Route::get('/', [TrainingRecordController::class, 'index'])
            ->name('index')
            ->middleware('permission:training.records.view');

        Route::get('/create', [TrainingRecordController::class, 'create'])
            ->name('create')
            ->middleware('permission:training.records.create');

        Route::post('/', [TrainingRecordController::class, 'store'])
            ->name('store')
            ->middleware('permission:training.records.create');

        Route::get('/{record}', [TrainingRecordController::class, 'show'])
            ->name('show')
            ->middleware('permission:training.records.view');

        Route::get('/{record}/edit', [TrainingRecordController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:training.records.update');

        Route::put('/{record}', [TrainingRecordController::class, 'update'])
            ->name('update')
            ->middleware('permission:training.records.update');

        Route::get('/export', [TrainingRecordController::class, 'export'])
            ->name('export')
            ->middleware('permission:training.records.export');
    });

// Training Matrix
Route::middleware(['auth', 'verified'])
    ->prefix('training/matrix')
    ->name('training.matrix.')
    ->group(function (): void {
        Route::get('/', [TrainingMatrixController::class, 'index'])
            ->name('index')
            ->middleware('permission:training.records.view');
    });
```

### Route Model Binding

- Program: `{program}` → `TrainingProgram` model via route key (id).
- Record: `{record}` → `TrainingRecord` model via route key (id).

---

## 2. Request Payloads

### 2.1 POST `/training-programs` (store program)

```json
{
  "code": "HSE-IND",
  "name": "HSE Induction",
  "description": "Pelatihan induksi keselamatan, kesehatan kerja, dan lingkungan untuk karyawan baru.",
  "category": "safety",
  "duration_hours": 8,
  "is_certification": true,
  "validity_months": 12,
  "is_active": true
}
```

**Validation Rules (StoreTrainingProgramRequest):**

| Field | Rule | Notes |
|---|---|---|
| `code` | `required|string|max:50|unique:training_programs,code` | Unique program code |
| `name` | `required|string|max:255` | |
| `description` | `nullable|string` | |
| `category` | `required|string|in:safety,technical,compliance,soft_skill,environment,security,quality,first_aid` | |
| `duration_hours` | `required|integer|min:1` | |
| `is_certification` | `boolean` | Default: false |
| `validity_months` | `nullable|integer|min:1` | Required if `is_certification` is true (conditional) |
| `is_active` | `boolean` | Default: true |

**Controller behavior (store):**

1. Validate request
2. Create `TrainingProgram`
3. `AuditService::created($program, $actor, 'training', $program->id)`
4. `ActivityService::log('training', $program->id, 'program.created', 'Program pelatihan dibuat', $actor)`
5. Redirect to `training.programs.show`

### 2.2 PUT `/training-programs/{program}` (update program)

Same payload as store, but `code` uses `unique:training_programs,code,{id}` (ignore self).

### 2.3 POST `/training-records` (store record)

```json
{
  "employee_id": 5,
  "training_program_id": 1,
  "provider": "PT Safety First Indonesia",
  "start_date": "2026-07-01",
  "end_date": "2026-07-02",
  "status": "scheduled",
  "score": null,
  "result": null,
  "certificate_number": null,
  "expiry_date": null,
  "notes": "Pelatihan induksi untuk karyawan baru departemen produksi."
}
```

**Validation Rules (StoreTrainingRecordRequest):**

| Field | Rule | Notes |
|---|---|---|
| `employee_id` | `required|exists:employees,id` | |
| `training_program_id` | `required|exists:training_programs,id` | Must also have `is_active=true` (checked in controller) |
| `provider` | `nullable|string|max:255` | |
| `start_date` | `required|date` | |
| `end_date` | `nullable|date|after_or_equal:start_date` | |
| `status` | `required|string|in:scheduled,in_progress,completed,expired,cancelled` | Default: scheduled |
| `score` | `nullable|numeric|between:0,100` | |
| `result` | `nullable|string|in:pass,fail,pending` | |
| `certificate_number` | `nullable|string|max:255` | |
| `expiry_date` | `nullable|date` | Auto-calculated if not provided and program has validity_months |
| `notes` | `nullable|string` | |

**Controller behavior (store):**

1. Validate request
2. Check program is active (`is_active = true`)
3. Create `TrainingRecord` with `status = 'scheduled'`
4. Generate `training_number` via `NumberingService::generate('training', $actor, ...)`
5. Auto-calculate `expiry_date` if not provided: `end_date + program.validity_months` (if both exist)
6. `AuditService::created($record, $actor, 'training', $record->id)`
7. `ActivityService::log('training', $record->id, 'record.created', 'Record pelatihan dibuat', $actor)`
8. `NotificationService::notify($employee->user, 'training.record_created', [...], $actor, 'training', $record->id, route('training.records.show', $record))`
9. Redirect to `training.records.show`

### 2.4 PUT `/training-records/{record}` (update record)

Same fields as store, plus:

| Field | Rule | Notes |
|---|---|---|
| `certificate_file` | `nullable|file|mimes:pdf,jpg,jpeg,png|max:10240` | File upload (multipart/form-data) |

**Controller behavior (update):**

1. Validate request
2. Load old values for audit
3. If `certificate_file` uploaded:
   - If existing `certificate_file_id` is set, soft-delete old file
   - Store new file via `ManagedFileService::store($file, new FileReference('training', $record->id, 'certificate'), $actor)`
   - Update `certificate_file_id` on record
4. Auto-calculate `expiry_date` if `end_date` changed and program has `validity_months` and `expiry_date` not manually set
5. Check expiry: if `expiry_date < now()` and `status = 'completed'`, set `status = 'expired'`
6. Update record
7. `AuditService::updated($record, $oldValues, $actor, 'training', $record->id)`
8. `ActivityService::log('training', $record->id, 'record.updated', 'Record pelatihan diperbarui', $actor)`
9. If status changed: `ActivityService::log('training', $record->id, 'record.status_changed', "Status berubah: {$oldStatus} → {$newStatus}", $actor)`
10. Redirect to `training.records.show`

---

## 3. Inertia Response Props

### 3.1 Program Index Page (`Training/Program/Index.tsx`)

```typescript
{
  items: {
    data: TrainingProgram[],
    current_page: number,
    last_page: number,
    per_page: number,
    total: number,
    from: number | null,
    to: number | null,
  },
  filters: {
    search: string,
    category: string | null,
    is_active: string | null,
  },
  can: {
    create: boolean,
    update: boolean,
  },
}
```

### 3.2 Program Form Page (`Training/Program/Form.tsx`)

```typescript
{
  item: TrainingProgram | null,  // null for create, populated for edit
}
```

### 3.3 Record Index Page (`Training/Record/Index.tsx`)

```typescript
{
  items: {
    data: (TrainingRecord & {
      employee: { id: number; name: string };
      program: { id: number; name: string; category: string };
    })[],
    current_page: number,
    last_page: number,
    per_page: number,
    total: number,
    from: number | null,
    to: number | null,
  },
  filters: {
    search: string,
    training_program_id: number | null,
    employee_id: number | null,
    status: string | null,
    site_id: number | null,
    department_id: number | null,
  },
  programs: TrainingProgram[],  // active programs for filter dropdown
  employees: Employee[],         // scoped employees for filter dropdown
  sites: Site[],
  departments: Department[],
  can: {
    create: boolean,
    export: boolean,
  },
}
```

### 3.4 Record Form Page (`Training/Record/Form.tsx`)

```typescript
{
  item: TrainingRecord | null,  // null for create, populated for edit
  programs: TrainingProgram[],  // active programs only
  employees: Employee[],         // scoped by user permissions
}
```

### 3.5 Record Show Page (`Training/Record/Show.tsx`)

```typescript
{
  record: TrainingRecord & {
    employee: Employee & {
      department: Department | null;
      site: Site | null;
      position: Position | null;
    };
    program: TrainingProgram;
    certificateFile: ManagedFile | null;
  },
  activities: ActivityLog[],
  isExpired: boolean,
  daysUntilExpiry: number | null,  // positive = days remaining, negative = days overdue
  can: {
    update: boolean,
  },
}
```

### 3.6 Matrix Page (`Training/Matrix/Index.tsx`)

```typescript
{
  employees: {
    data: (Employee & {
      trainingRecords: (TrainingRecord & {
        program: { id: number; name: string };
      })[];
    })[],
    current_page: number,
    last_page: number,
    per_page: number,
    total: number,
  },
  programs: TrainingProgram[],
  filters: {
    site_id: number | null,
    department_id: number | null,
  },
  sites: Site[],
  departments: Department[],
  can: {
    export: boolean,
  },
}
```

---

## 4. ListQuery Parameters

### 4.1 Program Index

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `code` and `name` (OR) |
| `category` | string | `null` | Filter by exact category |
| `is_active` | string | `null` | Filter by active status: `1` (active), `0` (inactive) |
| `per_page` | int | `15` | Items per page (max 100) |
| `sort` | string | `created_at` | Sort column |
| `direction` | string | `desc` | Sort direction (asc/desc) |

### 4.2 Record Index

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `training_number` and `employee.name` (OR) |
| `training_program_id` | int | `null` | Filter by program |
| `employee_id` | int | `null` | Filter by employee |
| `status` | string | `null` | Filter by exact status |
| `site_id` | int | `null` | Filter by employee's site |
| `department_id` | int | `null` | Filter by employee's department |
| `per_page` | int | `15` | Items per page (max 100) |
| `sort` | string | `created_at` | Sort column |
| `direction` | string | `desc` | Sort direction (asc/desc) |

### Controller index method pattern (Programs):

```php
public function index(ListQuery $listQuery): Response
{
    $items = $listQuery->paginate(
        TrainingProgram::query(),
        ['code', 'name'],
        ['created_at', 'updated_at', 'code', 'name'],
        'created_at',
        15,
    );

    return Inertia::render('Modules/Training/Program/Index', [
        'items' => $items,
        'filters' => $listQuery->filters(),
        'can' => [
            'create' => auth()->user()->can('training.programs.create'),
            'update' => auth()->user()->can('training.programs.update'),
        ],
    ]);
}
```

### Controller index method pattern (Records):

```php
public function index(ListQuery $listQuery): Response
{
    $query = TrainingRecord::query()
        ->with(['employee:id,name,department_id,site_id', 'program:id,name,category'])
        ->scoped(); // applies data scope filter based on role

    $items = $listQuery->paginate(
        $query,
        ['training_number'],
        ['created_at', 'updated_at', 'training_number', 'start_date', 'expiry_date'],
        'created_at',
        15,
    );

    return Inertia::render('Modules/Training/Record/Index', [
        'items' => $items,
        'filters' => $listQuery->filters(),
        'programs' => TrainingProgram::where('is_active', true)->select('id', 'name')->get(),
        'employees' => Employee::scoped()->select('id', 'name')->get(),
        'sites' => Site::where('is_active', true)->select('id', 'name')->get(),
        'departments' => Department::where('is_active', true)->select('id', 'name')->get(),
        'can' => [
            'create' => auth()->user()->can('training.records.create'),
            'export' => auth()->user()->can('training.records.export'),
        ],
    ]);
}
```

---

## 5. CSV Export Specification

Endpoint: `GET /training-records/export?search=...&status=...&training_program_id=...`

Applies same ListQuery filters, then streams CSV via `CsvExporter`.

### CSV Columns:

| Column Header | Source |
|---|---|
| `Nomor` | `training_number` |
| `Karyawan` | `employee.name` |
| `Program` | `program.name` |
| `Kategori` | `program.category` |
| `Provider` | `provider` |
| `Tanggal Mulai` | `start_date` |
| `Tanggal Selesai` | `end_date` |
| `Status` | `status` |
| `Skor` | `score` |
| `Hasil` | `result` |
| `Nomor Sertifikat` | `certificate_number` |
| `Tanggal Kedaluwarsa` | `expiry_date` |
| `Site` | `employee.site.name` |
| `Department` | `employee.department.name` |

### Controller export method pattern:

```php
public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
{
    $query = $listQuery->apply(
        TrainingRecord::query()
            ->with(['employee.department', 'employee.site', 'program'])
            ->scoped(),
        ['training_number'],
        ['created_at', 'start_date', 'expiry_date'],
        'created_at',
    );

    return $exporter->stream($query, [
        'Nomor' => 'training_number',
        'Karyawan' => fn ($item) => $item->employee?->name ?? '',
        'Program' => fn ($item) => $item->program?->name ?? '',
        'Kategori' => fn ($item) => $item->program?->category ?? '',
        'Provider' => fn ($item) => $item->provider ?? '',
        'Tanggal Mulai' => fn ($item) => $item->start_date?->format('Y-m-d') ?? '',
        'Tanggal Selesai' => fn ($item) => $item->end_date?->format('Y-m-d') ?? '',
        'Status' => 'status',
        'Skor' => fn ($item) => $item->score !== null ? number_format($item->score, 2) : '',
        'Hasil' => fn ($item) => $item->result ?? '',
        'Nomor Sertifikat' => fn ($item) => $item->certificate_number ?? '',
        'Tanggal Kedaluwarsa' => fn ($item) => $item->expiry_date?->format('Y-m-d') ?? '',
        'Site' => fn ($item) => $item->employee?->site?->name ?? '',
        'Department' => fn ($item) => $item->employee?->department?->name ?? '',
    ], 'training_records_export.csv');
}
```

---

## 6. Error Responses

| Status | When | Response |
|---|---|---|
| `403` | User lacks required permission | Inertia redirects to dashboard with error flash (middleware handles) |
| `404` | Program/Record ID not found | Laravel default 404 page |
| `422` | Validation failure | Inertia sends errors back to form with `$page.props.errors` |
| `400` | Inactive program selected for new record | Redirect back with error flash |
| `419` | CSRF token expired | Laravel default |

### Inactive program error handling:

```php
$program = TrainingProgram::findOrFail($request->training_program_id);

if (!$program->is_active) {
    return back()->withErrors([
        'training_program_id' => 'Program pelatihan tidak aktif. Pilih program yang aktif.',
    ]);
}
```

---

## 7. Permission Enforcement Points

| Layer | How |
|---|---|
| **Route middleware** | `->middleware('permission:training.programs.view')` on each route |
| **Controller authorize()** | `$this->authorize('view', $record)` for show/edit (scope filtering) |
| **Inertia shared props** | `auth.permissions` array → frontend checks via `permissions.has('training.records.create')` |
| **Export** | Route middleware `permission:training.records.export` |
| **Scope filter** | Policy applies `own`, `department`, `site`, or `all` scope based on role |

---

## 8. Numbering Integration

On `store` (training record):

```php
$generatedNumber = app(NumberingService::class)->generate(
    moduleName: 'training',
    actor: $actor,
    referenceType: TrainingRecord::class,
    referenceId: $record->id,
);

$record->update(['training_number' => $generatedNumber->number]);
```

Numbering format (already seeded): `TRN-2026-0001`

---

## 9. File Upload Integration (Certificate)

Certificate files are uploaded via the core `ManagedFileController` routes OR directly in the TrainingRecord update endpoint.

### Upload flow (via core file controller):

1. User creates training record → gets `record.id`
2. User uploads certificate via `POST /core/files` with:
   - `module_name`: `training`
   - `reference_id`: `$record->id`
   - `collection`: `certificate`
   - `file`: the UploadedFile
3. `ManagedFileService::store($file, new FileReference('training', $record->id, 'certificate'), $uploader)`
4. File stored on `local` disk at `training/{record_id}/certificate/{uuid}.{ext}`
5. Update `training_records.certificate_file_id` with the returned `ManagedFile->id`
6. Download via `GET /core/files/{managedFile}/download` (permission-gated)

### Upload flow (via record update endpoint):

```php
// In TrainingRecordController::update()
if ($request->hasFile('certificate_file')) {
    // Soft-delete old certificate if exists
    if ($record->certificate_file_id) {
        $oldFile = ManagedFile::find($record->certificate_file_id);
        $oldFile?->delete(); // soft delete
    }

    $file = $request->file('certificate_file');
    $managedFile = app(ManagedFileService::class)->store(
        $file,
        new FileReference('training', $record->id, 'certificate'),
        $actor,
    );

    $record->certificate_file_id = $managedFile->id;
}
```

### Show page loads certificate:

```php
'certificateFile' => $record->certificateFile
    ?->only(['id', 'original_name', 'size', 'mime_type', 'path']),
```

---

## 10. Expiry Tracking Integration

### Scheduled Command

File: `app/Console/Commands/CheckTrainingExpiry.php`

```php
class CheckTrainingExpiry extends Command
{
    protected $signature = 'training:check-expiry';
    protected $description = 'Check and update expired training records';

    public function handle(): int
    {
        $records = TrainingRecord::where('status', 'completed')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now()->toDateString())
            ->get();

        foreach ($records as $record) {
            $record->update(['status' => 'expired']);

            ActivityService::log(
                'training',
                $record->id,
                'record.expired',
                "Sertifikat kedaluwarsa pada {$record->expiry_date}",
                null,
            );
        }

        $this->info("Updated {$records->count()} expired records.");
        return self::SUCCESS;
    }
}
```

### Scheduled in `routes/console.php` or `app/Console/Kernel.php`:

```php
Schedule::command('training:check-expiry')->dailyAt('00:01');
```

### Expiry Reminder Notifications

```php
// In CheckTrainingExpiry command or separate command:
$expiring30d = TrainingRecord::where('status', 'completed')
    ->whereBetween('expiry_date', [now(), now()->addDays(30)])
    ->get();

foreach ($expiring30d as $record) {
    NotificationService::notifyMany(
        $this->getRecipients($record),
        'training.expiry_reminder_30d',
        [
            'record' => $record->only(['id', 'training_number', 'expiry_date']),
            'program' => $record->program->only(['name', 'code']),
            'employee' => $record->employee->only(['name']),
        ],
        null,
        'training',
        $record->id,
        route('training.records.show', $record),
    );
}
```

### On-Access Expiry Check

In `TrainingRecordController::show()`:

```php
if ($record->expiry_date && $record->expiry_date < now()->toDateString() && $record->status === 'completed') {
    $record->update(['status' => 'expired']);
    $record->refresh();
}
```

---

## 11. Data Scope Filtering

Training records are scope-filtered based on the user's role:

```php
// In TrainingRecordPolicy or Controller scope:
public function scope(Builder $query, User $user): Builder
{
    if ($user->hasRole(['Super Admin', 'Admin', 'QHSSE Manager', 'Top Management', 'Auditor'])) {
        return $query; // all
    }

    if ($user->hasRole('QHSSE Officer')) {
        return $query->whereHas('employee', fn ($q) => 
            $q->whereIn('site_id', $user->assignedSiteIds())
        );
    }

    if ($user->hasRole(['Supervisor', 'Department Head'])) {
        return $query->whereHas('employee', fn ($q) => 
            $q->where('department_id', $user->employee?->department_id)
        );
    }

    // Employee/Reporter, Contractor
    return $query->where('employee_id', $user->employee_id);
}
```
