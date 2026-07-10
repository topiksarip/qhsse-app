# API Contract — Legal & Compliance Register

> Spec ini mendefinisikan semua route, request, response, dan error handling untuk modul Legal & Compliance Register.

## 1. Route Table

Semua route diawali dengan prefix `/legal-register`, nama route `legal-register.*`, dan middleware `auth,verified`.

### 1.1 Legal Register Routes

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/legal-register` | `LegalRegisterController@index` | `legal-register.index` | `legal.register.view` | List registers with search/filter/pagination |
| GET | `/legal-register/create` | `LegalRegisterController@create` | `legal-register.create` | `legal.register.create` | Render create form |
| POST | `/legal-register` | `LegalRegisterController@store` | `legal-register.store` | `legal.register.create` | Save new register |
| GET | `/legal-register/{register}` | `LegalRegisterController@show` | `legal-register.show` | `legal.register.view` | Show register detail |
| GET | `/legal-register/{register}/edit` | `LegalRegisterController@edit` | `legal-register.edit` | `legal.register.update` | Render edit form |
| PUT | `/legal-register/{register}` | `LegalRegisterController@update` | `legal-register.update` | `legal.register.update` | Update register |
| GET | `/legal-register/export` | `LegalRegisterController@export` | `legal-register.export` | `legal.register.export` | Export filtered list as CSV |

### 1.2 Legal Obligations Routes

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| POST | `/legal-register/{register}/obligations` | `LegalObligationController@store` | `legal-obligations.store` | `legal.obligations.create` | Create new obligation |
| PUT | `/legal-register/{register}/obligations/{obligation}` | `LegalObligationController@update` | `legal-obligations.update` | `legal.obligations.update` | Update obligation |
| POST | `/legal-register/{register}/obligations/{obligation}/complete` | `LegalObligationController@complete` | `legal-obligations.complete` | `legal.obligations.update` | Complete obligation (requires evidence) |

### Route Registration

File: `routes/modules.php`

```php
use App\Http\Controllers\Modules\Legal\LegalRegisterController;
use App\Http\Controllers\Modules\Legal\LegalObligationController;

Route::middleware(['auth', 'verified'])
    ->prefix('legal-register')
    ->name('legal-register.')
    ->group(function (): void {
        // Register CRUD
        Route::get('/', [LegalRegisterController::class, 'index'])
            ->name('index')
            ->middleware('permission:legal.register.view');

        Route::get('/create', [LegalRegisterController::class, 'create'])
            ->name('create')
            ->middleware('permission:legal.register.create');

        Route::post('/', [LegalRegisterController::class, 'store'])
            ->name('store')
            ->middleware('permission:legal.register.create');

        Route::get('/{register}', [LegalRegisterController::class, 'show'])
            ->name('show')
            ->middleware('permission:legal.register.view');

        Route::get('/{register}/edit', [LegalRegisterController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:legal.register.update');

        Route::put('/{register}', [LegalRegisterController::class, 'update'])
            ->name('update')
            ->middleware('permission:legal.register.update');

        // Export
        Route::get('/export', [LegalRegisterController::class, 'export'])
            ->name('export')
            ->middleware('permission:legal.register.export');

        // Obligations (nested under register)
        Route::post('/{register}/obligations', [LegalObligationController::class, 'store'])
            ->name('obligations.store')
            ->middleware('permission:legal.obligations.create');

        Route::put('/{register}/obligations/{obligation}', [LegalObligationController::class, 'update'])
            ->name('obligations.update')
            ->middleware('permission:legal.obligations.update');

        Route::post('/{register}/obligations/{obligation}/complete', [LegalObligationController::class, 'complete'])
            ->name('obligations.complete')
            ->middleware('permission:legal.obligations.update');
    });
```

### Route Model Binding

- Register parameter: `{register}` → Laravel resolves to `LegalRegister` model via route key (id).
- Obligation parameter: `{obligation}` → Laravel resolves to `LegalObligation` model. Scoped to register: `where('legal_register_id', $register->id)`.

---

## 2. Request Payloads

### POST `/legal-register` (store)

```json
{
  "title": "UU No. 1 Tahun 1970 tentang Keselamatan Kerja",
  "regulation_name": "Undang-Undang Keselamatan Kerja",
  "regulation_number": "UU No. 1 Tahun 1970",
  "issuing_body": "Pemerintah RI",
  "category": "national",
  "compliance_status": "in_progress",
  "site_id": 1,
  "department_id": 3,
  "owner_id": 5,
  "next_review_date": "2026-08-15",
  "document_id": 3,
  "notes": "Regulasi ini mencakup ketentuan keselamatan kerja untuk semua tempat kerja."
}
```

**Validation Rules (StoreLegalRegisterRequest):**

| Field | Rule | Notes |
|---|---|---|
| `title` | `required\|string\|min:5\|max:255` | |
| `regulation_name` | `required\|string\|max:255` | |
| `regulation_number` | `required\|string\|max:255` | |
| `issuing_body` | `required\|string\|max:255` | |
| `category` | `required\|in:national,regional,industry,internal` | |
| `compliance_status` | `required\|in:compliant,non_compliant,in_progress,not_applicable` | Default: `in_progress` |
| `site_id` | `nullable\|exists:sites,id` | |
| `department_id` | `nullable\|exists:departments,id` | |
| `owner_id` | `required\|exists:users,id` | |
| `next_review_date` | `nullable\|date` | |
| `document_id` | `nullable\|exists:documents,id` | |
| `notes` | `nullable\|string` | |

**Controller behavior (store):**
1. Validate request
2. Create `LegalRegister` with status `active`, compliance_status default `in_progress`
3. Generate `register_number` via `NumberingService::generate('legal', $actor, ...)`
4. `AuditService::created($register, $actor, 'legal', $register->id)`
5. `ActivityService::log('legal', $register->id, 'legal.register.created', 'Register created', $actor)`
6. `NotificationService::notifyMany($qhsseManagers, 'legal.register.created', [...])`
7. Redirect to `legal-register.show`

### PUT `/legal-register/{register}` (update)

Same payload as store.

```json
{
  "title": "UU No. 1 Tahun 1970 tentang Keselamatan Kerja (Updated)",
  "regulation_name": "Undang-Undang Keselamatan Kerja",
  "regulation_number": "UU No. 1 Tahun 1970",
  "issuing_body": "Pemerintah RI",
  "category": "national",
  "compliance_status": "compliant",
  "site_id": 1,
  "department_id": 3,
  "owner_id": 5,
  "next_review_date": "2026-09-15",
  "document_id": 3,
  "notes": "Updated notes..."
}
```

**Validation Rules (UpdateLegalRegisterRequest):** Same as store.

**Controller behavior (update):**
1. Check `register.status === 'active'` (abort 403 if inactive)
2. Validate request
3. Record old values (especially `compliance_status` for change detection)
4. Update register
5. `AuditService::updated($register, $oldValues, $actor, 'legal', $register->id)`
6. If `compliance_status` changed:
   - `ActivityService::log('legal', $register->id, 'legal.compliance.changed', "Compliance changed from {$oldStatus} to {$newStatus}", $actor)`
   - `NotificationService::notifyMany($stakeholders, 'legal.compliance.changed', [...])`
7. Redirect to `legal-register.show`

### POST `/legal-register/{register}/obligations` (store obligation)

```json
{
  "obligation_description": "Lapor kepatuhan K3 bulanan ke Disnaker setiap akhir bulan.",
  "frequency": "monthly",
  "last_completed": "2026-06-01",
  "next_due": "2026-07-01"
}
```

**Validation Rules (StoreLegalObligationRequest):**

| Field | Rule | Notes |
|---|---|---|
| `obligation_description` | `required\|string\|min:10` | |
| `frequency` | `required\|in:monthly,quarterly,annual` | |
| `last_completed` | `nullable\|date` | |
| `next_due` | `nullable\|date` | Auto-calculated if last_completed is set |

**Controller behavior (store obligation):**
1. Check `register.status === 'active'`
2. Validate request
3. If `last_completed` is set and `next_due` is empty:
   - Auto-calculate `next_due` based on `frequency`:
     - `monthly`: `last_completed + 1 month`
     - `quarterly`: `last_completed + 3 months`
     - `annual`: `last_completed + 1 year`
4. Create `LegalObligation` with `legal_register_id` and `status='pending'`
5. `AuditService::created($obligation, $actor, 'legal', $obligation->id)`
6. `ActivityService::log('legal', $register->id, 'legal.obligation.created', "Obligation created", $actor)`
7. Redirect back to register show page (obligations tab)

### PUT `/legal-register/{register}/obligations/{obligation}` (update obligation)

```json
{
  "obligation_description": "Updated description of the obligation.",
  "frequency": "quarterly",
  "last_completed": "2026-04-01",
  "next_due": "2026-07-01"
}
```

Same validation as store. Only allowed if `register.status === 'active'`.

**Controller behavior (update obligation):**
1. Check `register.status === 'active'`
2. Check `obligation.legal_register_id === $register->id`
3. Validate request
4. If `last_completed` changed, auto-recalculate `next_due`
5. Record old values
6. Update obligation
7. `AuditService::updated($obligation, $oldValues, $actor, 'legal', $obligation->id)`
8. `ActivityService::log('legal', $register->id, 'legal.obligation.updated', "Obligation updated", $actor)`
9. Redirect back

### POST `/legal-register/{register}/obligations/{obligation}/complete` (complete obligation)

```json
{
  "last_completed": "2026-07-01",
  "evidence_file_id": 42
}
```

**Validation Rules (CompleteObligationRequest):**

| Field | Rule | Notes |
|---|---|---|
| `last_completed` | `required\|date` | Tanggal pelaksanaan |
| `evidence_file_id` | `required\|exists:managed_files,id` | Wajib — bukti pelaksanaan |

**Controller behavior (complete obligation):**
1. Check `register.status === 'active'`
2. Check `obligation.legal_register_id === $register->id`
3. Check `obligation.status === 'pending'`
4. Validate request
5. Record old values
6. Calculate new `next_due`:
   - `monthly`: `last_completed + 1 month`
   - `quarterly`: `last_completed + 3 months`
   - `annual`: `last_completed + 1 year`
7. Update obligation:
   - `status = 'completed'`
   - `last_completed = $request->last_completed`
   - `next_due = calculated next due`
   - `evidence_file_id = $request->evidence_file_id`
8. `AuditService::updated($obligation, $oldValues, $actor, 'legal', $obligation->id)`
9. `ActivityService::log('legal', $register->id, 'legal.obligation.completed', "Obligation completed", $actor)`
10. Redirect back with success message

---

## 3. Inertia Response Props

### Index Page (`Legal/Index.tsx`)

```typescript
{
  registers: {
    data: LegalRegister[],
    current_page: number,
    last_page: number,
    per_page: number,
    total: number,
  },
  filters: {
    search: string,
    compliance_status: string | null,
    category: string | null,
    site_id: number | null,
    department_id: number | null,
    owner_id: number | null,
  },
  sites: Site[],
  departments: Department[],
  owners: User[],
  kpiSummary: {
    total: number,
    compliant: number,
    non_compliant: number,
    in_progress: number,
    not_applicable: number,
    overdue_obligations: number,
    due_soon_obligations: number,
  },
  can: {
    create: boolean,
    export: boolean,
  },
}
```

### Create/Edit Page (`Legal/Form.tsx`)

```typescript
{
  register: LegalRegister | null,  // null for create, populated for edit
  sites: Site[],
  departments: Department[],
  owners: User[],                  // users eligible to be owner
  documents: Document[],           // available controlled documents
  can: {
    update: boolean,
  },
}
```

### Show Page (`Legal/Show.tsx`)

```typescript
{
  register: LegalRegister & {
    site: Site | null,
    department: Department | null,
    owner: User,
    document: Document | null,
    obligations: (LegalObligation & {
      evidence_file: ManagedFile | null,
      is_overdue: boolean,
      is_due_soon: boolean,
      days_overdue: number | null,
    })[],
    evidence_files: ManagedFile[],
    comments: Comment[],
    activities: ActivityLog[],
  },
  can: {
    update: boolean,
    export: boolean,
    create_obligation: boolean,
    update_obligation: boolean,
  },
}
```

---

## 4. ListQuery Parameters

The index page accepts these query parameters for filtering:

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `register_number`, `title`, `regulation_name` |
| `compliance_status` | string | `null` | Filter by: compliant, non_compliant, in_progress, not_applicable |
| `category` | string | `null` | Filter by: national, regional, industry, internal |
| `site_id` | int | `null` | Filter by site |
| `department_id` | int | `null` | Filter by department |
| `owner_id` | int | `null` | Filter by owner |
| `per_page` | int | `15` | Items per page (max 100) |
| `sort` | string | `created_at` | Sort column |
| `direction` | string | `desc` | Sort direction (asc/desc) |

### Controller index method pattern:

```php
public function index(ListQuery $listQuery): Response
{
    $items = $listQuery->paginate(
        LegalRegister::query()->with(['site', 'department', 'owner', 'obligations']),
        ['register_number', 'title', 'regulation_name'],
        ['created_at', 'register_number', 'next_review_date'],
        'created_at',
        15,
    );

    // KPI Summary
    $baseQuery = LegalRegister::where('status', 'active');
    $kpiSummary = [
        'total' => $baseQuery->count(),
        'compliant' => (clone $baseQuery)->where('compliance_status', 'compliant')->count(),
        'non_compliant' => (clone $baseQuery)->where('compliance_status', 'non_compliant')->count(),
        'in_progress' => (clone $baseQuery)->where('compliance_status', 'in_progress')->count(),
        'not_applicable' => (clone $baseQuery)->where('compliance_status', 'not_applicable')->count(),
        'overdue_obligations' => LegalObligation::where('status', 'pending')
            ->whereNotNull('next_due')
            ->where('next_due', '<', now()->toDateString())
            ->count(),
        'due_soon_obligations' => LegalObligation::where('status', 'pending')
            ->whereNotNull('next_due')
            ->where('next_due', '<=', now()->addDays(7)->toDateString())
            ->where('next_due', '>=', now()->toDateString())
            ->count(),
    ];

    return Inertia::render('Modules/Legal/Index', [
        'registers' => $items,
        'filters' => $listQuery->filters(),
        'sites' => Site::where('is_active', true)->get(['id', 'name']),
        'departments' => Department::where('is_active', true)->get(['id', 'name']),
        'owners' => User::where('is_active', true)->get(['id', 'name']),
        'kpiSummary' => $kpiSummary,
        'can' => [
            'create' => auth()->user()->can('legal.register.create'),
            'export' => auth()->user()->can('legal.register.export'),
        ],
    ]);
}
```

---

## 5. CSV Export Specification

Endpoint: `GET /legal-register/export?search=...&compliance_status=...&category=...`

Applies same ListQuery filters, then streams CSV via `CsvExporter`.

### CSV Columns:

| Column Header | Source |
|---|---|
| `Nomor Register` | `register_number` |
| `Judul` | `title` |
| `Nama Regulasi` | `regulation_name` |
| `Nomor Regulasi` | `regulation_number` |
| `Issuing Body` | `issuing_body` |
| `Kategori` | `category` |
| `Status Kepatuhan` | `compliance_status` |
| `Site` | `site.name` |
| `Department` | `department.name` |
| `Owner` | `owner.name` |
| `Next Review Date` | `next_review_date` |
| `Total Obligations` | count of obligations |
| `Overdue Obligations` | count overdue |
| `Pending Obligations` | count pending |
| `Status Record` | `status` |
| `Notes` | `notes` (truncated 500 chars) |
| `Created At` | `created_at` |

### Controller export method pattern:

```php
public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
{
    $query = $listQuery->apply(
        LegalRegister::query()->with(['site', 'department', 'owner', 'obligations']),
        ['register_number', 'title', 'regulation_name'],
        ['created_at', 'register_number'],
    );

    return $exporter->stream($query, [
        'Nomor Register' => 'register_number',
        'Judul' => 'title',
        'Nama Regulasi' => 'regulation_name',
        'Nomor Regulasi' => 'regulation_number',
        'Issuing Body' => 'issuing_body',
        'Kategori' => 'category',
        'Status Kepatuhan' => 'compliance_status',
        'Site' => fn ($item) => $item->site?->name ?? '',
        'Department' => fn ($item) => $item->department?->name ?? '',
        'Owner' => fn ($item) => $item->owner?->name ?? '',
        'Next Review Date' => fn ($item) => $item->next_review_date?->format('Y-m-d') ?? '',
        'Total Obligations' => fn ($item) => $item->obligations->count(),
        'Overdue Obligations' => fn ($item) => $item->obligations->filter(fn ($o) =>
            $o->status === 'pending' && $o->next_due && $o->next_due < now()->toDateString()
        )->count(),
        'Pending Obligations' => fn ($item) => $item->obligations->where('status', 'pending')->count(),
        'Status Record' => 'status',
        'Notes' => fn ($item) => Str::limit($item->notes ?? '', 500),
        'Created At' => fn ($item) => $item->created_at?->format('Y-m-d H:i:s') ?? '',
    ], 'legal-register-export.csv');
}
```

---

## 6. Error Responses

| Status | When | Response |
|---|---|---|
| `403` | User lacks required permission | Inertia redirects to dashboard with error flash |
| `404` | Register or Obligation ID not found | Laravel default 404 page |
| `422` | Validation failure | Inertia sends errors back to form with `$page.props.errors` |
| `422` | Complete obligation without evidence_file_id | JSON error: "Evidence file wajib diupload" |
| `422` | Complete already-completed obligation | JSON error: "Kewajiban sudah diselesaikan" |
| `422` | Create obligation on inactive register | JSON error: "Register tidak aktif" |
| `419` | CSRF token expired | Laravel default |

### Complete obligation validation error:

```php
if ($obligation->status !== 'pending') {
    return back()->withErrors([
        'obligation' => 'Kewajiban sudah diselesaikan.'
    ]);
}

if (!$request->has('evidence_file_id') || !$request->input('evidence_file_id')) {
    return back()->withErrors([
        'evidence_file_id' => 'Evidence file wajib diupload saat menyelesaikan kewajiban.'
    ]);
}
```

### Inactive register error:

```php
if ($register->status !== 'active') {
    return back()->withErrors([
        'register' => 'Register tidak aktif. Tidak dapat menambah atau mengubah kewajiban.'
    ]);
}
```

---

## 7. Permission Enforcement Points

| Layer | How |
|---|---|
| **Route middleware** | `->middleware('permission:legal.register.view')` on each route |
| **Controller authorize()** | `$this->authorize('view', $register)` for show/edit (scope filtering) |
| **Inertia shared props** | `auth.permissions` array → frontend checks via `permissions.has('legal.register.create')` |
| **Obligation operations** | Route middleware `permission:legal.obligations.create` etc. |
| **Export** | Route middleware `permission:legal.register.export` |

---

## 8. Numbering Integration

### Register Number

On `store`:

```php
$generatedNumber = app(NumberingService::class)->generate(
    moduleName: 'legal',
    actor: $actor,
    referenceType: LegalRegister::class,
    referenceId: $register->id,
);

$register->update(['register_number' => $generatedNumber->number]);
```

Numbering format (already seeded): `LEG-2026-0001`

---

## 9. Due Date Calculation Integration

### Auto-calculate next_due on obligation create/update:

```php
private function calculateNextDue(string $frequency, ?string $lastCompleted): ?string
{
    if (!$lastCompleted) {
        return null;
    }

    $date = \Carbon\Carbon::parse($lastCompleted);

    return match ($frequency) {
        'monthly' => $date->addMonth()->toDateString(),
        'quarterly' => $date->addMonths(3)->toDateString(),
        'annual' => $date->addYear()->toDateString(),
        default => null,
    };
}
```

### On obligation complete:

```php
$obligation->update([
    'status' => 'completed',
    'last_completed' => $request->input('last_completed'),
    'next_due' => $this->calculateNextDue($obligation->frequency, $request->input('last_completed')),
    'evidence_file_id' => $request->input('evidence_file_id'),
]);
```

---

## 10. Scheduled Job for Overdue Detection

### Daily Overdue Check

File: `app/Console/Commands/CheckOverdueObligations.php`

```php
class CheckOverdueObligations extends Command
{
    protected $signature = 'legal:check-overdue';
    protected $description = 'Check for overdue obligations and send notifications';

    public function handle(NotificationService $notificationService): int
    {
        $overdueObligations = LegalObligation::with(['legalRegister', 'legalRegister.owner'])
            ->where('status', 'pending')
            ->whereNotNull('next_due')
            ->where('next_due', '<', now()->toDateString())
            ->whereDoesntHave('overdueNotification') // Prevent duplicate notifications
            ->get();

        foreach ($overdueObligations as $obligation) {
            $recipients = $this->getRecipients($obligation);

            $notificationService->notifyMany(
                recipients: $recipients,
                type: 'legal.obligation.overdue',
                context: [
                    'obligation' => $obligation->toArray(),
                    'register' => $obligation->legalRegister->toArray(),
                    'days_overdue' => now()->diffInDays($obligation->next_due),
                ],
                actor: null, // System
                moduleName: 'legal',
                referenceId: $obligation->legalRegister->id,
                actionUrl: "/legal-register/{$obligation->legalRegister->id}?tab=obligations",
            );
        }

        // Check due soon (7 days)
        $dueSoonObligations = LegalObligation::with(['legalRegister', 'legalRegister.owner'])
            ->where('status', 'pending')
            ->whereNotNull('next_due')
            ->where('next_due', '<=', now()->addDays(7)->toDateString())
            ->where('next_due', '>=', now()->toDateString())
            ->get();

        foreach ($dueSoonObligations as $obligation) {
            $notificationService->notifyMany(
                recipients: $this->getRecipients($obligation),
                type: 'legal.obligation.due_soon',
                context: [
                    'obligation' => $obligation->toArray(),
                    'register' => $obligation->legalRegister->toArray(),
                    'days_until_due' => now()->diffInDays($obligation->next_due, false),
                ],
                actor: null,
                moduleName: 'legal',
                referenceId: $obligation->legalRegister->id,
                actionUrl: "/legal-register/{$obligation->legalRegister->id}?tab=obligations",
            );
        }

        $this->info("Processed {$overdueObligations->count()} overdue and {$dueSoonObligations->count()} due soon obligations.");

        return self::SUCCESS;
    }
}
```

### Schedule Registration

File: `app/Console/Kernel.php` or `routes/console.php`

```php
Schedule::command('legal:check-overdue')->dailyAt('00:01');
```

---

## 11. File Upload Integration

Evidence files are uploaded via the existing core `ManagedFileController` routes.

### Upload flow:

1. User creates register → gets `register.id`
2. User uploads file via `POST /core/files` with:
   - `module_name`: `legal`
   - `reference_id`: `$register->id` (register-level) or `$obligation.id` (obligation-level)
   - `collection`: `evidence` (register-level) or `obligation_evidence` (obligation-level)
   - `file`: the UploadedFile
3. `ManagedFileService::store($file, new FileReference('legal', $register->id, 'evidence'), $uploader)`
4. File stored on `local` disk at `managed-files/legal/{id}/evidence/{uuid}.{ext}`
5. Download via `GET /core/files/{managedFile}/download`

### Obligation evidence upload flow:

1. User clicks "Tandai Selesai" on obligation
2. Modal opens with file upload + date input
3. User selects file and date
4. File uploaded via `POST /core/files` with:
   - `module_name`: `legal`
   - `reference_id`: `$obligation.id`
   - `collection`: `obligation_evidence`
5. Returns `managed_file.id`
6. Frontend sends `POST /legal-register/{register}/obligations/{obligation}/complete` with `evidence_file_id` and `last_completed`
7. Obligation updated with evidence_file_id

### Show page loads evidence:

```php
'evidence_files' => ManagedFile::query()
    ->where('module_name', 'legal')
    ->where('reference_id', $register->id)
    ->whereNull('deleted_at')
    ->get(),
```

---

## 12. Document Control Cross-Module Integration

### Link Document to Register

When user selects a document in the register form:

1. `document_id` is saved on `legal_register` table
2. Show page displays: `📄 Dokumen: [DOC-2026-0003] Lihat Dokumen`
3. "Lihat Dokumen" links to: `GET /documents/{document_id}`
4. Permission: `legal.register.view` (view register) + `document.control.view` (view document)

### Document dropdown in form:

```php
'documents' => Document::where('status', 'effective')
    ->orWhere('status', 'approved')
    ->get(['id', 'doc_number', 'title', 'status']),
```
