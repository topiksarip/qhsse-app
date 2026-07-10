# API Contract — Investigation & RCA

> Spec ini mendefinisikan semua route, request, response, dan error handling untuk modul Investigation & RCA.

## 1. Route Table

Semua route diawali dengan prefix `/investigations`, nama route `investigation.reports.*`, dan middleware `auth,verified`.

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/investigations` | `index` | `investigation.reports.index` | `investigation.reports.view` | List investigations with search/filter/pagination |
| GET | `/investigations/create` | `create` | `investigation.reports.create` | `investigation.reports.create` | Render create form |
| POST | `/investigations` | `store` | `investigation.reports.store` | `investigation.reports.create` | Save new investigation (draft or start) |
| GET | `/investigations/{investigation}` | `show` | `investigation.reports.show` | `investigation.reports.view` | Show investigation detail |
| GET | `/investigations/{investigation}/edit` | `edit` | `investigation.reports.edit` | `investigation.reports.update` | Render edit form |
| PUT | `/investigations/{investigation}` | `update` | `investigation.reports.update` | `investigation.reports.update` | Update investigation |
| POST | `/investigations/{investigation}/start` | `start` | `investigation.reports.start` | `investigation.reports.submit` | Transition draft → in_progress |
| POST | `/investigations/{investigation}/complete` | `complete` | `investigation.reports.complete` | `investigation.reports.close` | Transition in_progress → completed (requires reason) |
| POST | `/investigations/{investigation}/cancel` | `cancel` | `investigation.reports.cancel` | `investigation.reports.update` | Transition draft/in_progress → cancelled (requires reason) |
| GET | `/investigations/export` | `export` | `investigation.reports.export` | `investigation.reports.export` | Export filtered list as CSV |

### Route Registration

File: `routes/modules.php`

```php
use App\Http\Controllers\Modules\Investigation\InvestigationController;

Route::middleware(['auth', 'verified'])
    ->prefix('investigations')
    ->name('investigation.reports.')
    ->group(function (): void {
        Route::get('/', [InvestigationController::class, 'index'])
            ->name('index')
            ->middleware('permission:investigation.reports.view');

        Route::get('/create', [InvestigationController::class, 'create'])
            ->name('create')
            ->middleware('permission:investigation.reports.create');

        Route::post('/', [InvestigationController::class, 'store'])
            ->name('store')
            ->middleware('permission:investigation.reports.create');

        Route::get('/{investigation}', [InvestigationController::class, 'show'])
            ->name('show')
            ->middleware('permission:investigation.reports.view');

        Route::get('/{investigation}/edit', [InvestigationController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:investigation.reports.update');

        Route::put('/{investigation}', [InvestigationController::class, 'update'])
            ->name('update')
            ->middleware('permission:investigation.reports.update');

        Route::post('/{investigation}/start', [InvestigationController::class, 'start'])
            ->name('start')
            ->middleware('permission:investigation.reports.submit');

        Route::post('/{investigation}/complete', [InvestigationController::class, 'complete'])
            ->name('complete')
            ->middleware('permission:investigation.reports.close');

        Route::post('/{investigation}/cancel', [InvestigationController::class, 'cancel'])
            ->name('cancel')
            ->middleware('permission:investigation.reports.update');

        Route::get('/export', [InvestigationController::class, 'export'])
            ->name('export')
            ->middleware('permission:investigation.reports.export');
    });
```

### Route Model Binding

- Parameter name: `{investigation}` → Laravel resolves to `Investigation` model via route key (id).
- Custom key: default `id` (no need for `getRouteKeyName()` override).

---

## 2. Request Payloads

### POST `/investigations` (store)

```json
{
  "incident_id": 1,
  "title": "Analisis Kecelakaan Kerja di Area Produksi",
  "investigator_id": 5,
  "five_whys": [
    {
      "level": 1,
      "question": "Mengapa kecelakaan terjadi?",
      "answer": "Pekerja terpeleset di lantai basah.",
      "is_root_cause": false
    },
    {
      "level": 2,
      "question": "Mengapa lantai basah?",
      "answer": "Terdapat tumpahan oli dari mesin.",
      "is_root_cause": false
    },
    {
      "level": 3,
      "question": "Mengapa terjadi tumpahan oli?",
      "answer": "Seal pada mesin rusak dan tidak terdeteksi.",
      "is_root_cause": false
    },
    {
      "level": 4,
      "question": "Mengapa seal rusak tidak terdeteksi?",
      "answer": "Maintenance preventif tidak sesuai jadwal.",
      "is_root_cause": false
    },
    {
      "level": 5,
      "question": "Mengapa maintenance tidak sesuai jadwal?",
      "answer": "Tidak ada sistem monitoring maintenance yang efektif.",
      "is_root_cause": true
    }
  ],
  "fishbone": [
    { "category": "Man", "causes": ["Operator tidak training SOP terbaru", "Kelelahan karena lembur"] },
    { "category": "Method", "causes": ["Prosedur LOTO tidak diikuti"] },
    { "category": "Machine", "causes": ["Seal mesin rusak"] },
    { "category": "Material", "causes": [] },
    { "category": "Environment", "causes": ["Pencahayaan area kurang optimal"] },
    { "category": "Management", "causes": ["Tidak ada sistem monitoring maintenance"] }
  ],
  "contributing_factors": [
    { "factor": "APD sepatu anti-slip tidak tersedia", "category": "Material", "impact": "direct" },
    { "factor": "Operator baru transfer dari department lain", "category": "Man", "impact": "indirect" }
  ],
  "timeline_events": [
    {
      "timestamp": "2026-07-11T14:00:00+07:00",
      "event": "Mesin mulai beroperasi",
      "description": "Shift pagi dimulai, operator menyalakan mesin produksi #3.",
      "source": "witness_statement"
    },
    {
      "timestamp": "2026-07-11T14:30:00+07:00",
      "event": "Kecelakaan terjadi",
      "description": "Pekerja terpeleset di lantai basah akibat tumpahan oli.",
      "source": "incident_report"
    }
  ],
  "root_cause": "Tidak ada sistem monitoring maintenance yang efektif, menyebabkan seal mesin rusak tidak terdeteksi, menyebabkan tumpahan oli, menyebabkan lantai basah, menyebabkan pekerja terpeleset.",
  "recommendations": "1. Implementasi sistem monitoring maintenance terjadwal.\n2. Training ulang SOP untuk semua operator.\n3. Pemasangan sensor kebocoran oli pada mesin.\n4. Penyediaan APD sepatu anti-slip di area produksi.",
  "team_members": [
    { "user_id": 5, "role": "lead_investigator" },
    { "user_id": 8, "role": "investigator" },
    { "user_id": 12, "role": "subject_matter_expert" }
  ],
  "action": "draft"
}
```

**Validation Rules (StoreInvestigationRequest):**

| Field | Rule | Notes |
|---|---|---|
| `incident_id` | `required|exists:incidents,id` | Must have status `under_review` or `investigation` |
| `title` | `required|string|min:5|max:255` | |
| `investigator_id` | `required|exists:users,id` | Default: authenticated user |
| `five_whys` | `nullable|array` | Required if `action=start` |
| `five_whys.*.level` | `required|integer|min:1|max:7` | |
| `five_whys.*.question` | `required|string|min:5|max:500` | |
| `five_whys.*.answer` | `required|string|min:5` | |
| `five_whys.*.is_root_cause` | `boolean` | Only 1 can be true |
| `fishbone` | `nullable|array` | Required if `action=start` |
| `fishbone.*.category` | `required|in:Man,Method,Machine,Material,Environment,Management` | |
| `fishbone.*.causes` | `array` | |
| `fishbone.*.causes.*` | `string|min:3|max:500` | |
| `contributing_factors` | `nullable|array` | |
| `contributing_factors.*.factor` | `required|string|min:5|max:500` | |
| `contributing_factors.*.category` | `required|in:Man,Method,Machine,Material,Environment,Management` | |
| `contributing_factors.*.impact` | `required|in:direct,indirect` | |
| `timeline_events` | `nullable|array` | |
| `timeline_events.*.timestamp` | `required|date` | ISO 8601 |
| `timeline_events.*.event` | `required|string|min:3|max:255` | |
| `timeline_events.*.description` | `nullable|string|max:1000` | |
| `timeline_events.*.source` | `required|in:incident_report,witness_statement,cctv_footage,document_review,site_inspection,other` | |
| `root_cause` | `nullable|string` | Required if `action=complete` |
| `recommendations` | `nullable|string` | Required if `action=complete` |
| `team_members` | `nullable|array` | |
| `team_members.*.user_id` | `required_with:team_members|exists:users,id` | |
| `team_members.*.role` | `required_with:team_members|in:lead_investigator,investigator,subject_matter_expert,recorder` | |
| `action` | `nullable|in:draft,start` | If `start`, validates mandatory fields AND triggers workflow transition |

**Controller behavior (store):**
1. Validate request
2. Create `Investigation` with investigator_id (default: auth user)
3. Generate `investigation_number` via `NumberingService::generate('investigation', $actor)`
4. Start workflow via `WorkflowService::start('investigation', $investigation->id, $actor)`
5. If `action === 'start'`: call `WorkflowService::transition('investigation', $investigation->id, 'start', $actor)` + set `started_at`
6. Attach `team_members` if provided
7. `AuditService::created($investigation, $actor, 'investigation', $investigation->id)`
8. `ActivityService::log('investigation', $investigation->id, 'investigation.created', 'Investigation report created', $actor)`
9. If started: `NotificationService::notifyMany($stakeholders, 'investigation.started', [...])`
10. Redirect to `investigation.reports.show`

### PUT `/investigations/{investigation}` (update)

Same payload as store, but:
- `incident_id`, `investigator_id` are **sometimes** (not required for draft update)
- Only allowed if `status` is `draft` or `in_progress`
- Records audit trail for changed fields via `AuditService::updated()`
- Team members sync: add new, remove missing, update roles

### POST `/investigations/{investigation}/start` (start)

No request body needed (or optional `five_whys`, `fishbone` if updating before start). Controller:

1. Check `investigation.status === 'draft'`
2. Validate mandatory fields: `title`, `incident_id`, `investigator_id`, `five_whys` (min 1), `fishbone` (min 1 category with min 1 cause)
3. `WorkflowService::transition('investigation', $investigation->id, 'start', $actor)`
4. Set `started_at = now()`
5. `ActivityService::log('investigation', $investigation->id, 'investigation.started', ...)`
6. `NotificationService::notifyMany($stakeholders, 'investigation.started', [...])`
7. Redirect back with flash message

### POST `/investigations/{investigation}/complete` (complete)

```json
{
  "reason": "Investigasi selesai, root cause teridentifikasi, rekomendasi telah disusun."
}
```

| Field | Rule |
|---|---|
| `reason` | `required|string|min:10|max:1000` |

Controller:

1. Check `investigation.status === 'in_progress'`
2. Validate `root_cause` is not empty
3. Validate `recommendations` is not empty
4. `WorkflowService::transition('investigation', $investigation->id, 'complete', $actor, $reason)`
5. Set `completed_at = now()`
6. `ActivityService::log('investigation', $investigation->id, 'investigation.completed', ...)`
7. `NotificationService::notifyMany($stakeholders, 'investigation.completed', [...])`
8. Redirect back

### POST `/investigations/{investigation}/cancel` (cancel)

```json
{
  "reason": "Investigasi tidak dapat dilanjutkan karena keterbatasan data dan saksi."
}
```

| Field | Rule |
|---|---|
| `reason` | `required|string|min:10|max:1000` |

Controller:

1. Check `investigation.status` IN (`'draft'`, `'in_progress'`)
2. `WorkflowService::transition('investigation', $investigation->id, 'cancel', $actor, $reason)`
3. `ActivityService::log('investigation', $investigation->id, 'investigation.cancelled', ...)`
4. `NotificationService::notifyMany($stakeholders, 'investigation.cancelled', [...])`
5. Redirect back

---

## 3. Inertia Response Props

### Index Page (`Investigation/Index.tsx`)

```typescript
{
  items: {
    data: Investigation[],
    current_page: number,
    last_page: number,
    per_page: number,
    total: number,
  },
  filters: {
    search: string,
    status: string | null,
    investigator_id: number | null,
    site_id: number | null,
    from: string | null,
    to: string | null,
  },
  investigators: { id: number; name: string }[],
  sites: Site[],
  can: {
    create: boolean,
    export: boolean,
  },
}
```

### Create/Edit Page (`Investigation/Form.tsx`)

```typescript
{
  investigation: Investigation | null,  // null for create, populated for edit
  incidents: {
    id: number;
    incident_number: string;
    title: string;
  }[],  // incidents with status under_review or investigation
  investigators: { id: number; name: string }[],
  teamMembers: { id: number; name: string; email: string }[],  // all users for team selection
  can: {
    submit: boolean;  // can start investigation
  },
}
```

### Show Page (`Investigation/Show.tsx`)

```typescript
{
  investigation: Investigation & {
    incident: Incident & {
      incident_number: string;
      title: string;
      site: Site;
      severity: Severity;
      reporter: User;
    },
    investigator: User,
    team_members: (User & { pivot: { role: string } })[],
    files: ManagedFile[],
    comments: Comment[],
    activities: ActivityLog[],
    workflow_histories: WorkflowHistory[],
  },
  can: {
    update: boolean;
    submit: boolean;   // start
    close: boolean;    // complete
    export: boolean;
  },
  available_transitions: {
    action_key: string;
    action_label: string;
    requires_reason: boolean;
  }[],
}
```

---

## 4. ListQuery Parameters

The index page accepts these query parameters for filtering:

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `investigation_number`, `title`, and `incident.incident_number` (OR) |
| `status` | string | `null` | Filter by exact status: draft, in_progress, completed, cancelled |
| `investigator_id` | int | `null` | Filter by investigator |
| `site_id` | int | `null` | Filter by incident's site |
| `from` | string | `null` | Date range start (created_at >= from) |
| `to` | string | `null` | Date range end (created_at <= to) |
| `per_page` | int | `15` | Items per page (max 100) |
| `sort` | string | `created_at` | Sort column |
| `direction` | string | `desc` | Sort direction (asc/desc) |

### Controller index method pattern:

```php
public function index(ListQuery $listQuery): Response
{
    $items = $listQuery->paginate(
        Investigation::query()->with(['incident', 'investigator']),
        ['investigation_number', 'title'],
        ['created_at', 'started_at', 'completed_at', 'investigation_number'],
        'created_at',
        15,
    );

    return Inertia::render('Modules/Investigation/Index', [
        'items' => $items,
        'filters' => $listQuery->filters(),
        'investigators' => User::whereHas('roles', fn($q) => $q->whereIn('name', ['QHSSE Officer', 'QHSSE Manager']))
            ->select('id', 'name')
            ->get(),
        'sites' => Site::select('id', 'code', 'name')->get(),
        'can' => [
            'create' => auth()->user()->can('investigation.reports.create'),
            'export' => auth()->user()->can('investigation.reports.export'),
        ],
    ]);
}
```

---

## 5. CSV Export Specification

Endpoint: `GET /investigations/export?search=...&status=...`

Applies same ListQuery filters, then streams CSV via `CsvExporter`.

### CSV Columns:

| Column Header | Source |
|---|---|
| `Nomor` | `investigation_number` |
| `Judul` | `title` |
| `Nomor Incident` | `incident.incident_number` |
| `Judul Incident` | `incident.title` |
| `Status` | `status` |
| `Investigator` | `investigator.name` |
| `Root Cause` | `root_cause` (truncated 500 chars) |
| `Dimulai` | `started_at` |
| `Selesai` | `completed_at` |
| `Durasi (hari)` | calculated: `completed_at - started_at` |
| `Dibuat` | `created_at` |

### Controller export method pattern:

```php
public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
{
    $query = $listQuery->apply(
        Investigation::query()->with(['incident', 'investigator']),
        ['investigation_number', 'title'],
        ['created_at', 'started_at', 'completed_at'],
        'created_at',
    );

    return $exporter->stream($query, [
        'Nomor'          => 'investigation_number',
        'Judul'          => 'title',
        'Nomor Incident' => fn ($item) => $item->incident?->incident_number ?? '',
        'Judul Incident' => fn ($item) => $item->incident?->title ?? '',
        'Status'         => 'status',
        'Investigator'   => fn ($item) => $item->investigator?->name ?? '',
        'Root Cause'     => fn ($item) => \Illuminate\Support\Str::limit($item->root_cause ?? '', 500),
        'Dimulai'        => fn ($item) => $item->started_at?->format('Y-m-d H:i') ?? '',
        'Selesai'        => fn ($item) => $item->completed_at?->format('Y-m-d H:i') ?? '',
        'Durasi (hari)'  => fn ($item) => $item->completed_at && $item->started_at
            ? $item->completed_at->diffInDays($item->started_at)
            : '',
        'Dibuat'         => fn ($item) => $item->created_at?->format('Y-m-d H:i:s') ?? '',
    ], 'investigations-export.csv');
}
```

---

## 6. Error Responses

| Status | When | Response |
|---|---|---|
| `403` | User lacks required permission | Inertia redirects to dashboard with error flash (middleware handles) |
| `404` | Investigation ID not found | Laravel default 404 page |
| `422` | Validation failure | Inertia sends errors back to form with `$page.props.errors` |
| `400` | Invalid workflow transition | RuntimeException caught → redirect back with error flash |
| `419` | CSRF token expired | Laravel default |

### Invalid workflow transition handling:

```php
try {
    $this->workflowService->transition('investigation', $investigation->id, 'start', $actor);
} catch (RuntimeException $e) {
    return back()->withErrors(['workflow' => $e->getMessage()]);
}
```

---

## 7. Permission Enforcement Points

| Layer | How |
|---|---|
| **Route middleware** | `->middleware('permission:investigation.reports.view')` on each route |
| **Controller authorize()** | `$this->authorize('view', $investigation)` for show/edit (scope filtering) |
| **Inertia shared props** | `auth.permissions` array → frontend checks via `permissions.has('investigation.reports.create')` |
| **Export** | Route middleware `permission:investigation.reports.export` |

---

## 8. Numbering Integration

On `store`:

```php
$generatedNumber = app(NumberingService::class)->generate(
    moduleName: 'investigation',
    actor: $actor,
    referenceType: Investigation::class,
    referenceId: $investigation->id,
);

$investigation->update(['investigation_number' => $generatedNumber->number]);
```

Numbering format (already seeded): `INV-2026-0001`

---

## 9. Workflow Integration

The workflow definition `investigation` needs to be **added** to the `WorkflowSeeder` (not yet seeded — see WORKFLOW.md for the seeder code).

- Initial status: `draft`
- Terminal statuses: `completed`, `cancelled`

### Transitions used by this module:

| Action | Controller Method | From | To | requires_reason |
|---|---|---|---|---|
| `start` | `start()` | draft | in_progress | false |
| `complete` | `complete()` | in_progress | completed | **true** |
| `cancel` | `cancel()` | draft | cancelled | **true** |
| `cancel` | `cancel()` | in_progress | cancelled | **true** |

### Phase 2 workflow path:

```
draft ──(start)──→ in_progress ──(complete)──→ completed
  │                     │
  └──(cancel)──→ cancelled ←──(cancel)──┘
```

---

## 10. File Upload Integration

Investigation files are uploaded via the existing core `ManagedFileController` routes (not module-specific).

### Upload flow:

1. User creates investigation → gets `investigation.id`
2. User uploads file via `POST /core/files` with:
   - `module_name`: `investigation`
   - `reference_id`: `$investigation->id`
   - `collection`: `evidence`, `report`, or `attachment`
   - `file`: the UploadedFile
3. `ManagedFileService::store($file, new FileReference('investigation', $investigation->id, 'evidence'), $uploader)`
4. File stored on `local` disk at `managed-files/investigation/{id}/{collection}/{uuid}.{ext}`
5. Download via `GET /core/files/{managedFile}/download` (permission-gated by `core.files.download` + module reference check)

### Show page loads files:

```php
'files' => ManagedFile::query()
    ->where('module_name', 'investigation')
    ->where('reference_id', $investigation->id)
    ->whereNull('deleted_at')
    ->get(),
```

---

## 11. Comments Integration

Comments are added via the core `CommentController` routes.

### Add comment flow:

1. User views investigation detail
2. User adds comment via `POST /core/comments` with:
   - `module_name`: `investigation`
   - `reference_id`: `$investigation->id`
   - `body`: comment text
   - `is_internal`: boolean
3. `CommentService::add('investigation', $investigation->id, $body, $author, parentId, isInternal)`
4. Comment appears in the Comments section

### Show page loads comments:

```php
'comments' => Comment::query()
    ->where('module_name', 'investigation')
    ->where('reference_id', $investigation->id)
    ->whereNull('deleted_at')
    ->with('author')
    ->orderBy('created_at', 'asc')
    ->get(),
```

---

## 12. Cross-Module Integration Points

### 12.1 Incident → Investigation

When creating an investigation from an incident:

```php
// In InvestigationController::store()
$incident = Incident::findOrFail($request->incident_id);

// Verify incident is in a valid state for investigation
if (!in_array($incident->status, ['under_review', 'investigation'])) {
    throw ValidationException::withMessages([
        'incident_id' => 'Incident harus berstatus under_review atau investigation.',
    ]);
}

// Optionally transition incident to 'investigation' status
if ($incident->status === 'under_review') {
    $this->workflowService->transition('incident', $incident->id, 'investigate', $actor);
}
```

### 12.2 Investigation → CAPA (Phase 3)

When creating a CAPA from investigation recommendations:

```php
// Future Phase 3 endpoint: POST /investigations/{id}/create-capa
// Creates a CAPA record with:
// source_module = 'investigation'
// source_reference_id = $investigation->id
```

### 12.3 Data Scope Filtering

```php
// In InvestigationPolicy
public function view(User $user, Investigation $investigation): bool
{
    // Super Admin / Admin: all access
    if ($user->hasRole(['Super Admin', 'Admin'])) {
        return true;
    }

    // QHSSE Manager: all sites
    if ($user->hasRole('QHSSE Manager')) {
        return true;
    }

    // QHSSE Officer: assigned site(s)
    if ($user->hasRole('QHSSE Officer')) {
        return $investigation->incident->site_id === $user->employee?->site_id;
    }

    // Supervisor / Department Head: department scope
    if ($user->hasRole(['Supervisor', 'Department Head'])) {
        return $investigation->incident->department_id === $user->employee?->department_id;
    }

    // Employee/Reporter: own incidents
    if ($user->hasRole('Employee / Reporter')) {
        return $investigation->incident->reporter_id === $user->id;
    }

    // Contractor: company scope
    if ($user->hasRole('Contractor')) {
        return $investigation->incident->reporter_id === $user->id
            || $investigation->incident->reporter?->company_id === $user->company_id;
    }

    // Auditor / Top Management: all (read-only)
    if ($user->hasRole(['Auditor', 'Top Management'])) {
        return true;
    }

    return false;
}
```

---

## 13. Controller Code Pattern

File: `app/Http/Controllers/Modules/Investigation/InvestigationController.php`

```php
<?php

namespace App\Http\Controllers\Modules\Investigation;

use App\Core\Audit\AuditService;
use App\Core\Activity\ActivityService;
use App\Core\Notification\NotificationService;
use App\Core\Workflow\WorkflowService;
use App\Core\ListQuery;
use App\Core\Csv\CsvExporter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Investigation\StoreInvestigationRequest;
use App\Http\Requests\Modules\Investigation\UpdateInvestigationRequest;
use App\Models\Modules\Investigation\Investigation;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvestigationController extends Controller
{
    public function __construct(
        private readonly WorkflowService $workflowService,
        private readonly AuditService $auditService,
        private readonly ActivityService $activityService,
        private readonly NotificationService $notificationService,
    ) {}

    public function index(ListQuery $listQuery): Response
    {
        $items = $listQuery->paginate(
            Investigation::query()->with(['incident', 'investigator']),
            ['investigation_number', 'title'],
            ['created_at', 'started_at', 'completed_at', 'investigation_number'],
            'created_at',
            15,
        );

        return Inertia::render('Modules/Investigation/Index', [
            'items' => $items,
            'filters' => $listQuery->filters(),
        ]);
    }

    public function create(): Response
    {
        $incidents = \App\Models\Modules\Incident\Incident::query()
            ->whereIn('status', ['under_review', 'investigation'])
            ->select('id', 'incident_number', 'title')
            ->get();

        return Inertia::render('Modules/Investigation/Form', [
            'investigation' => null,
            'incidents' => $incidents,
            'investigators' => \App\Models\User::whereHas('roles', fn($q) => 
                $q->whereIn('name', ['QHSSE Officer', 'QHSSE Manager'])
            )->select('id', 'name')->get(),
            'teamMembers' => \App\Models\User::select('id', 'name', 'email')->get(),
        ]);
    }

    public function store(StoreInvestigationRequest $request): RedirectResponse
    {
        $actor = $request->user();
        $data = $request->validated();

        $investigation = Investigation::create([
            ...$data,
            'investigator_id' => $data['investigator_id'] ?? $actor->id,
            'status' => 'draft',
        ]);

        // Generate number
        $number = app(\App\Core\Numbering\NumberingService::class)
            ->generate('investigation', $actor, Investigation::class, $investigation->id);
        $investigation->update(['investigation_number' => $number->number]);

        // Start workflow
        $this->workflowService->start('investigation', $investigation->id, $actor);

        // Attach team members
        if (isset($data['team_members'])) {
            $this->syncTeamMembers($investigation, $data['team_members']);
        }

        // Audit + Activity
        $this->auditService->created($investigation, $actor, 'investigation', $investigation->id);
        $this->activityService->log('investigation', $investigation->id, 'investigation.created', 'Investigasi dibuat', $actor);

        // If action=start, trigger transition
        if (($data['action'] ?? null) === 'start') {
            $this->doStart($investigation, $actor);
        }

        return redirect()->route('investigation.reports.show', $investigation)
            ->with('success', 'Investigasi berhasil dibuat.');
    }

    public function show(Investigation $investigation, Request $request): Response
    {
        $investigation->load([
            'incident.site',
            'incident.severity',
            'incident.reporter',
            'investigator',
            'teamMembers',
        ]);

        $files = \App\Models\Core\ManagedFile::query()
            ->where('module_name', 'investigation')
            ->where('reference_id', $investigation->id)
            ->whereNull('deleted_at')
            ->get();

        $comments = \App\Models\Core\Comment::query()
            ->where('module_name', 'investigation')
            ->where('reference_id', $investigation->id)
            ->whereNull('deleted_at')
            ->with('author')
            ->orderBy('created_at', 'asc')
            ->get();

        $activities = \App\Models\Core\ActivityLog::query()
            ->where('module_name', 'investigation')
            ->where('reference_id', $investigation->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $workflowHistories = \App\Models\Core\WorkflowHistory::query()
            ->where('module_name', 'investigation')
            ->where('reference_id', $investigation->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $availableTransitions = $this->workflowService->getAvailableTransitions(
            'investigation', $investigation->id
        );

        return Inertia::render('Modules/Investigation/Show', [
            'investigation' => $investigation,
            'files' => $files,
            'comments' => $comments,
            'activities' => $activities,
            'workflow_histories' => $workflowHistories,
            'available_transitions' => $availableTransitions,
            'can' => [
                'update' => $request->user()->can('update', $investigation),
                'submit' => $request->user()->can('investigation.reports.submit'),
                'close' => $request->user()->can('investigation.reports.close'),
                'export' => $request->user()->can('investigation.reports.export'),
            ],
        ]);
    }

    public function edit(Investigation $investigation): Response
    {
        $investigation->load(['incident', 'investigator', 'teamMembers']);

        $incidents = \App\Models\Modules\Incident\Incident::query()
            ->whereIn('status', ['under_review', 'investigation'])
            ->orWhere('id', $investigation->incident_id)
            ->select('id', 'incident_number', 'title')
            ->get();

        return Inertia::render('Modules/Investigation/Form', [
            'investigation' => $investigation,
            'incidents' => $incidents,
            'investigators' => \App\Models\User::whereHas('roles', fn($q) => 
                $q->whereIn('name', ['QHSSE Officer', 'QHSSE Manager'])
            )->select('id', 'name')->get(),
            'teamMembers' => \App\Models\User::select('id', 'name', 'email')->get(),
        ]);
    }

    public function update(UpdateInvestigationRequest $request, Investigation $investigation): RedirectResponse
    {
        if (!in_array($investigation->status, ['draft', 'in_progress'])) {
            return back()->withErrors(['workflow' => 'Investigasi tidak dapat diedit pada status ini.']);
        }

        $actor = $request->user();
        $oldValues = $investigation->toArray();
        $data = $request->validated();

        $investigation->update($data);

        if (isset($data['team_members'])) {
            $this->syncTeamMembers($investigation, $data['team_members']);
        }

        $this->auditService->updated($investigation, $oldValues, $actor, 'investigation', $investigation->id);
        $this->activityService->log('investigation', $investigation->id, 'investigation.updated', 'Investigasi diperbarui', $actor);

        return redirect()->route('investigation.reports.show', $investigation)
            ->with('success', 'Investigasi berhasil diperbarui.');
    }

    public function start(Investigation $investigation, Request $request): RedirectResponse
    {
        $actor = $request->user();

        if ($investigation->status !== 'draft') {
            return back()->withErrors(['workflow' => 'Hanya investigasi berstatus draft yang dapat dimulai.']);
        }

        // Validate mandatory fields
        if (empty($investigation->five_whys) || count($investigation->five_whys) < 1) {
            return back()->withErrors(['five_whys' => 'Analisis 5-Why wajib diisi minimal 1 level.']);
        }

        $hasFishboneCause = collect($investigation->fishbone ?? [])
            ->pluck('causes')
            ->flatten()
            ->isNotEmpty();

        if (!$hasFishboneCause) {
            return back()->withErrors(['fishbone' => 'Fishbone wajib diisi minimal 1 penyebab.']);
        }

        $this->doStart($investigation, $actor);

        return back()->with('success', 'Investigasi dimulai.');
    }

    public function complete(Investigation $investigation, Request $request): RedirectResponse
    {
        $actor = $request->user();
        $validated = $request->validate([
            'reason' => 'required|string|min:10|max:1000',
        ]);

        if ($investigation->status !== 'in_progress') {
            return back()->withErrors(['workflow' => 'Hanya investigasi berstatus in_progress yang dapat diselesaikan.']);
        }

        if (empty($investigation->root_cause)) {
            return back()->withErrors(['root_cause' => 'Root cause wajib diisi sebelum menyelesaikan investigasi.']);
        }

        if (empty($investigation->recommendations)) {
            return back()->withErrors(['recommendations' => 'Rekomendasi wajib diisi sebelum menyelesaikan investigasi.']);
        }

        try {
            $this->workflowService->transition('investigation', $investigation->id, 'complete', $actor, $validated['reason']);
            $investigation->update(['completed_at' => now()]);

            $this->activityService->log('investigation', $investigation->id, 'investigation.completed', 'Investigasi diselesaikan', $actor);

            $this->notifyStakeholders($investigation, 'investigation.completed', $actor, [
                'reason' => $validated['reason'],
            ]);

        } catch (\RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back()->with('success', 'Investigasi diselesaikan.');
    }

    public function cancel(Investigation $investigation, Request $request): RedirectResponse
    {
        $actor = $request->user();
        $validated = $request->validate([
            'reason' => 'required|string|min:10|max:1000',
        ]);

        if (!in_array($investigation->status, ['draft', 'in_progress'])) {
            return back()->withErrors(['workflow' => 'Investigasi tidak dapat dibatalkan pada status ini.']);
        }

        try {
            $this->workflowService->transition('investigation', $investigation->id, 'cancel', $actor, $validated['reason']);

            $this->activityService->log('investigation', $investigation->id, 'investigation.cancelled', 'Investigasi dibatalkan', $actor);

            $this->notifyStakeholders($investigation, 'investigation.cancelled', $actor, [
                'reason' => $validated['reason'],
            ]);

        } catch (\RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back()->with('success', 'Investigasi dibatalkan.');
    }

    public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
    {
        $query = $listQuery->apply(
            Investigation::query()->with(['incident', 'investigator']),
            ['investigation_number', 'title'],
            ['created_at', 'started_at', 'completed_at'],
            'created_at',
        );

        return $exporter->stream($query, [
            'Nomor'          => 'investigation_number',
            'Judul'          => 'title',
            'Nomor Incident' => fn ($item) => $item->incident?->incident_number ?? '',
            'Judul Incident' => fn ($item) => $item->incident?->title ?? '',
            'Status'         => 'status',
            'Investigator'   => fn ($item) => $item->investigator?->name ?? '',
            'Root Cause'     => fn ($item) => \Illuminate\Support\Str::limit($item->root_cause ?? '', 500),
            'Dimulai'        => fn ($item) => $item->started_at?->format('Y-m-d H:i') ?? '',
            'Selesai'        => fn ($item) => $item->completed_at?->format('Y-m-d H:i') ?? '',
            'Durasi (hari)'  => fn ($item) => $item->completed_at && $item->started_at
                ? $item->completed_at->diffInDays($item->started_at)
                : '',
            'Dibuat'         => fn ($item) => $item->created_at?->format('Y-m-d H:i:s') ?? '',
        ], 'investigations-export.csv');
    }

    // ── Private helpers ──────────────────────────────────────────

    private function doStart(Investigation $investigation, $actor): void
    {
        $this->workflowService->transition('investigation', $investigation->id, 'start', $actor);
        $investigation->update(['started_at' => now()]);

        $this->activityService->log('investigation', $investigation->id, 'investigation.started', 'Investigasi dimulai', $actor);

        $this->notifyStakeholders($investigation, 'investigation.started', $actor);
    }

    private function syncTeamMembers(Investigation $investigation, array $members): void
    {
        $syncData = collect($members)->mapWithKeys(fn ($m) => [
            $m['user_id'] => ['role' => $m['role']],
        ]);
        $investigation->teamMembers()->sync($syncData);
    }

    private function notifyStakeholders(Investigation $investigation, string $type, $actor, array $extra = []): void
    {
        $recipients = collect();

        // Incident reporter
        if ($investigation->incident?->reporter) {
            $recipients->push($investigation->incident->reporter);
        }

        // QHSSE Managers
        $managers = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'QHSSE Manager'))->get();
        $recipients = $recipients->merge($managers);

        if ($recipients->isNotEmpty()) {
            $this->notificationService->notifyMany(
                $recipients->unique('id'),
                $type,
                array_merge([
                    'investigation_number' => $investigation->investigation_number,
                    'title'                => $investigation->title,
                    'incident_number'      => $investigation->incident?->incident_number,
                    'investigator_name'    => $actor->name,
                    'action_url'           => "/investigations/{$investigation->id}",
                ], $extra),
                $actor,
                'investigation',
                $investigation->id,
                "/investigations/{$investigation->id}"
            );
        }
    }
}
```

---

## 14. Form Request Validation

File: `app/Http/Requests/Modules/Investigation/StoreInvestigationRequest.php`

```php
<?php

namespace App\Http\Requests\Modules\Investigation;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvestigationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('investigation.reports.create');
    }

    public function rules(): array
    {
        $rules = [
            'incident_id'     => 'required|exists:incidents,id',
            'title'           => 'required|string|min:5|max:255',
            'investigator_id' => 'nullable|exists:users,id',
            'five_whys'       => 'nullable|array',
            'fishbone'        => 'nullable|array',
            'contributing_factors' => 'nullable|array',
            'timeline_events'      => 'nullable|array',
            'root_cause'      => 'nullable|string',
            'recommendations' => 'nullable|string',
            'team_members'    => 'nullable|array',
            'action'          => 'nullable|in:draft,start',
        ];

        // 5-Why validation
        $rules['five_whys.*.level'] = 'required|integer|min:1|max:7';
        $rules['five_whys.*.question'] = 'required|string|min:5|max:500';
        $rules['five_whys.*.answer'] = 'required|string|min:5';
        $rules['five_whys.*.is_root_cause'] = 'boolean';

        // Fishbone validation
        $rules['fishbone.*.category'] = 'required|in:Man,Method,Machine,Material,Environment,Management';
        $rules['fishbone.*.causes'] = 'array';
        $rules['fishbone.*.causes.*'] = 'string|min:3|max:500';

        // Contributing factors validation
        $rules['contributing_factors.*.factor'] = 'required|string|min:5|max:500';
        $rules['contributing_factors.*.category'] = 'required|in:Man,Method,Machine,Material,Environment,Management';
        $rules['contributing_factors.*.impact'] = 'required|in:direct,indirect';

        // Timeline events validation
        $rules['timeline_events.*.timestamp'] = 'required|date';
        $rules['timeline_events.*.event'] = 'required|string|min:3|max:255';
        $rules['timeline_events.*.description'] = 'nullable|string|max:1000';
        $rules['timeline_events.*.source'] = 'required|in:incident_report,witness_statement,cctv_footage,document_review,site_inspection,other';

        // Team members validation
        $rules['team_members.*.user_id'] = 'required_with:team_members|exists:users,id';
        $rules['team_members.*.role'] = 'required_with:team_members|in:lead_investigator,investigator,subject_matter_expert,recorder';

        return $rules;
    }

    public function messages(): array
    {
        return [
            'incident_id.required' => 'Incident terkait wajib dipilih.',
            'incident_id.exists'   => 'Incident tidak ditemukan.',
            'title.required'       => 'Judul investigasi wajib diisi.',
            'title.min'            => 'Judul minimal 5 karakter.',
        ];
    }
}
```

File: `app/Http/Requests/Modules/Investigation/UpdateInvestigationRequest.php`

```php
<?php

namespace App\Http\Requests\Modules\Investigation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvestigationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('investigation.reports.update');
    }

    public function rules(): array
    {
        // Same validation rules as StoreInvestigationRequest,
        // but all fields are sometimes (not required for draft update)
        return (new StoreInvestigationRequest())->rules();
    }
}
```
