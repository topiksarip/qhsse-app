# API Contract — Document Control

> Spec ini mendefinisikan semua route, request, response, dan error handling untuk modul Document Control.

## 1. Route Table

Semua route diawali dengan prefix `/documents`, nama route `document.control.*`, dan middleware `auth,verified`.

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/documents` | `index` | `document.control.index` | `document.control.view` | List documents with search/filter/pagination |
| GET | `/documents/create` | `create` | `document.control.create` | `document.control.create` | Render create form |
| POST | `/documents` | `store` | `document.control.store` | `document.control.create` | Save new document (draft or submit_review) |
| GET | `/documents/{controlledDocument}` | `show` | `document.control.show` | `document.control.view` | Show document detail |
| GET | `/documents/{controlledDocument}/edit` | `edit` | `document.control.edit` | `document.control.update` | Render edit form |
| PUT | `/documents/{controlledDocument}` | `update` | `document.control.update` | `document.control.update` | Update document |
| POST | `/documents/{controlledDocument}/submit-review` | `submitReview` | `document.control.submitReview` | `document.control.submit_review` | Transition draft → review |
| POST | `/documents/{controlledDocument}/approve` | `approve` | `document.control.approve` | `document.control.approve` | Transition review → approved |
| POST | `/documents/{controlledDocument}/make-effective` | `makeEffective` | `document.control.makeEffective` | `document.control.make_effective` | Transition approved → effective |
| POST | `/documents/{controlledDocument}/obsolete` | `obsolete` | `document.control.obsolete` | `document.control.obsolete` | Transition effective → obsolete (requires reason) |
| POST | `/documents/{controlledDocument}/reject` | `reject` | `document.control.reject` | `document.control.approve` | Transition review → rejected (requires reason) |
| POST | `/documents/{controlledDocument}/revise` | `revise` | `document.control.revise` | `document.control.update` | Transition rejected → draft |
| GET | `/documents/export` | `export` | `document.control.export` | `document.control.export` | Export filtered list as CSV |

### Route Registration

File: `routes/modules.php`

```php
use App\Http\Controllers\Modules\Document\DocumentControlController;

Route::middleware(['auth', 'verified'])
    ->prefix('documents')
    ->name('document.control.')
    ->group(function (): void {
        Route::get('/', [DocumentControlController::class, 'index'])
            ->name('index')
            ->middleware('permission:document.control.view');

        Route::get('/create', [DocumentControlController::class, 'create'])
            ->name('create')
            ->middleware('permission:document.control.create');

        Route::post('/', [DocumentControlController::class, 'store'])
            ->name('store')
            ->middleware('permission:document.control.create');

        Route::get('/{controlledDocument}', [DocumentControlController::class, 'show'])
            ->name('show')
            ->middleware('permission:document.control.view');

        Route::get('/{controlledDocument}/edit', [DocumentControlController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:document.control.update');

        Route::put('/{controlledDocument}', [DocumentControlController::class, 'update'])
            ->name('update')
            ->middleware('permission:document.control.update');

        Route::post('/{controlledDocument}/submit-review', [DocumentControlController::class, 'submitReview'])
            ->name('submitReview')
            ->middleware('permission:document.control.submit_review');

        Route::post('/{controlledDocument}/approve', [DocumentControlController::class, 'approve'])
            ->name('approve')
            ->middleware('permission:document.control.approve');

        Route::post('/{controlledDocument}/make-effective', [DocumentControlController::class, 'makeEffective'])
            ->name('makeEffective')
            ->middleware('permission:document.control.make_effective');

        Route::post('/{controlledDocument}/obsolete', [DocumentControlController::class, 'obsolete'])
            ->name('obsolete')
            ->middleware('permission:document.control.obsolete');

        Route::post('/{controlledDocument}/reject', [DocumentControlController::class, 'reject'])
            ->name('reject')
            ->middleware('permission:document.control.approve');

        Route::post('/{controlledDocument}/revise', [DocumentControlController::class, 'revise'])
            ->name('revise')
            ->middleware('permission:document.control.update');

        Route::get('/export', [DocumentControlController::class, 'export'])
            ->name('export')
            ->middleware('permission:document.control.export');
    });
```

### Route Model Binding

- Parameter name: `{controlledDocument}` → Laravel resolves to `ControlledDocument` model via route key (id).
- Custom key: default `id` (no need for `getRouteKeyName()` override).

---

## 2. Request Payloads

### POST `/documents` (store)

```json
{
  "title": "SOP Penggunaan APD di Area Produksi",
  "type": "sop",
  "version": "1.0",
  "revision_notes": "Pembuatan SOP baru untuk area produksi",
  "effective_date": "2026-08-01",
  "review_date": "2027-08-01",
  "expiry_date": null,
  "department_id": 3,
  "owner_id": 5,
  "is_confidential": false,
  "action": "draft"
}
```

**Validation Rules (StoreDocumentRequest):**

| Field | Rule | Notes |
|---|---|---|
| `title` | `required|string|max:255` | |
| `type` | `required|in:sop,wi,jsa,hiradc,msds,policy,form,manual,other` | |
| `version` | `required|string|max:20` | |
| `revision_notes` | `nullable|string` | |
| `effective_date` | `nullable|date` | Set at make_effective, but can be pre-set |
| `review_date` | `nullable|date|after_or_equal:today` | For expiry reminder |
| `expiry_date` | `nullable|date|after:review_date` | Must be after review_date |
| `department_id` | `nullable|exists:departments,id` | |
| `owner_id` | `required|exists:users,id` | Auto-filled from auth user if not provided |
| `is_confidential` | `boolean` | Default false |
| `action` | `nullable|in:draft,submit_review` | If `submit_review`, validates mandatory fields + triggers workflow |

**Controller behavior (store):**
1. Validate request
2. Create `ControlledDocument` with `owner_id` = auth user (or provided)
3. Generate `document_number` via `NumberingService::generate('document', $actor, ...)`
4. Start workflow via `WorkflowService::start('document', $document->id, $actor)`
5. If `action === 'submit_review'`: call `WorkflowService::transition('document', $document->id, 'submit_review', $actor)` + create `DocumentReview` record
6. If file uploaded: `ManagedFileService::store($file, new FileReference('document', $document->id, 'document_file'), $actor)`
7. `AuditService::created($document, $actor, 'document', $document->id)`
8. `ActivityService::log('document', $document->id, 'document.created', 'Document created', $actor)`
9. If submitted: `NotificationService::notifyMany($qhsseManagers, 'document.submitted', [...])`
10. Redirect to `document.control.show`

### PUT `/documents/{controlledDocument}` (update)

Same payload as store, but:
- `title`, `type`, `version` are **sometimes** (not required for draft)
- Only allowed if `status === 'draft'` or `status === 'rejected'`
- Records audit trail for changed fields via `AuditService::updated()`
- If file uploaded, replaces/adds to existing files

### POST `/documents/{controlledDocument}/submit-review` (submitReview)

```json
{
  "review_notes": "Dokumen siap untuk review. Mohon ditinjau."
}
```

| Field | Rule | Notes |
|---|---|---|
| `review_notes` | `nullable|string|max:2000` | Optional notes for reviewer |

**Controller:**
1. Check `document.status === 'draft'`
2. Validate mandatory fields (title, type, version, owner_id, file uploaded)
3. `WorkflowService::transition('document', $document->id, 'submit_review', $actor)`
4. Create `DocumentReview::create(['document_id' => $document->id, 'decision' => 'pending'])`
5. `ActivityService::log('document', $document->id, 'document.submitted', ...)`
6. `NotificationService::notifyMany($qhsseManagers, 'document.submitted', [...])`
7. Redirect back with flash message

### POST `/documents/{controlledDocument}/approve` (approve)

```json
{
  "review_notes": "Dokumen sudah memenuhi standar. Disetujui."
}
```

| Field | Rule | Notes |
|---|---|---|
| `review_notes` | `nullable|string|max:2000` | Approver notes |

**Controller:**
1. Check `document.status === 'review'`
2. `WorkflowService::transition('document', $document->id, 'approve', $actor)`
3. Update latest `DocumentReview`: set `reviewer_id`, `review_date`, `review_notes`, `decision = 'approve'`
4. Update `document.approver_id = $actor->id`
5. `NotificationService::notify($owner, 'document.approved', [...])`
6. Redirect back

### POST `/documents/{controlledDocument}/make-effective` (makeEffective)

```json
{
  "effective_date": "2026-08-01"
}
```

| Field | Rule | Notes |
|---|---|---|
| `effective_date` | `nullable|date` | Defaults to today if not provided |

**Controller:**
1. Check `document.status === 'approved'`
2. `WorkflowService::transition('document', $document->id, 'make_effective', $actor)`
3. Update `document.effective_date = $effective_date ?? today()`
4. `NotificationService::notifyMany($stakeholders, 'document.effective', [...])`
5. Redirect back

### POST `/documents/{controlledDocument}/obsolete` (obsolete)

```json
{
  "reason": "Dokumen sudah tidak relevan dengan proses terbaru. Diganti dengan DOC-2026-0015."
}
```

| Field | Rule | Notes |
|---|---|---|
| `reason` | `required|string|min:10|max:2000` | Wajib, min 10 karakter |

**Controller:**
1. Check `document.status === 'effective'`
2. `WorkflowService::transition('document', $document->id, 'obsolete', $actor, $reason)`
3. `NotificationService::notifyMany($stakeholders, 'document.obsolete', [...])`
4. Redirect back

### POST `/documents/{controlledDocument}/reject` (reject)

```json
{
  "reason": "Dokumen perlu revisi pada bagian prosedur penggunaan APD. Mohon dilengkapi."
}
```

| Field | Rule | Notes |
|---|---|---|
| `reason` | `required|string|min:10|max:2000` | Wajib, min 10 karakter |

**Controller:**
1. Check `document.status === 'review'`
2. `WorkflowService::transition('document', $document->id, 'reject', $actor, $reason)`
3. Update latest `DocumentReview`: set `reviewer_id`, `review_date`, `review_notes = $reason`, `decision = 'reject'`
4. `NotificationService::notify($owner, 'document.rejected', [...])`
5. Redirect back

### POST `/documents/{controlledDocument}/revise` (revise)

No request body needed.

**Controller:**
1. Check `document.status === 'rejected'`
2. `WorkflowService::transition('document', $document->id, 'revise', $actor)`
3. Update latest `DocumentReview`: set `decision = 'revise'`
4. `ActivityService::log('document', $document->id, 'document.revised', ...)`
5. Redirect back — owner can now edit the document

---

## 3. Inertia Response Props

### Index Page (`Document/Index.tsx`)

```typescript
{
  items: {
    data: ControlledDocument[],
    current_page: number,
    last_page: number,
    per_page: number,
    total: number,
  },
  filters: {
    search: string,
    type: string | null,
    status: string | null,
    department_id: number | null,
  },
}
```

### Create/Edit Page (`Document/Form.tsx`)

```typescript
{
  item: ControlledDocument | null,  // null for create, populated for edit
  departments: Department[],
  users: User[],                    // for owner selection
  documentTypes: { value: string; label: string }[],
  can: {
    submit_review: boolean;
    approve: boolean;
    make_effective: boolean;
    obsolete: boolean;
  },
}
```

### Show Page (`Document/Show.tsx`)

```typescript
{
  document: ControlledDocument & {
    department: Department | null,
    owner: User,
    approver: User | null,
  },
  files: ManagedFile[],
  reviews: DocumentReview[] & {
    reviewer: User | null,
  }[],
  comments: Comment[],
  activities: ActivityLog[],
  workflowHistory: WorkflowHistory[],
  availableTransitions: {
    action_key: string,
    action_label: string,
    requires_reason: boolean,
  }[],
  can: {
    update: boolean;
    submit_review: boolean;
    approve: boolean;
    make_effective: boolean;
    obsolete: boolean;
    revise: boolean;
    download_file: boolean;
  },
}
```

---

## 4. ListQuery Parameters

The index page accepts these query parameters for filtering:

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `document_number` and `title` (OR) |
| `type` | string | `null` | Filter by exact type: sop, wi, jsa, hiradc, msds, policy, form, manual, other |
| `status` | string | `null` | Filter by exact status: draft, review, approved, effective, obsolete, rejected |
| `department_id` | int | `null` | Filter by department |
| `per_page` | int | `15` | Items per page (max 100) |
| `sort` | string | `created_at` | Sort column |
| `direction` | string | `desc` | Sort direction (asc/desc) |

### Controller index method pattern:

```php
public function index(ListQuery $listQuery): Response
{
    $items = $listQuery->paginate(
        ControlledDocument::query()->with(['department', 'owner', 'approver']),
        ['document_number', 'title'],
        ['created_at', 'effective_date', 'document_number'],
        'created_at',
        15,
    );

    return Inertia::render('Modules/Document/Index', [
        'items' => $items,
        'filters' => $listQuery->filters(),
    ]);
}
```

---

## 5. CSV Export Specification

Endpoint: `GET /documents/export?search=...&type=...&status=...`

Applies same ListQuery filters, then streams CSV via `CsvExporter`.

### CSV Columns:

| Column Header | Source |
|---|---|
| `Nomor` | `document_number` |
| `Judul` | `title` |
| `Tipe` | `type` |
| `Versi` | `version` |
| `Status` | `status` |
| `Tanggal Berlaku` | `effective_date` |
| `Tanggal Review` | `review_date` |
| `Tanggal Kadaluarsa` | `expiry_date` |
| `Department` | `department.name` |
| `Owner` | `owner.name` |
| `Approver` | `approver.name` |
| `Rahasia` | `is_confidential` |
| `Dibuat Pada` | `created_at` |

### Controller export method pattern:

```php
public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
{
    $query = $listQuery->apply(
        ControlledDocument::query()->with(['department', 'owner', 'approver']),
        ['document_number', 'title'],
        ['created_at', 'effective_date'],
        'created_at',
    );

    return $exporter->stream($query, [
        'Nomor' => 'document_number',
        'Judul' => 'title',
        'Tipe' => 'type',
        'Versi' => 'version',
        'Status' => 'status',
        'Tanggal Berlaku' => fn ($item) => $item->effective_date?->format('Y-m-d') ?? '',
        'Tanggal Review' => fn ($item) => $item->review_date?->format('Y-m-d') ?? '',
        'Tanggal Kadaluarsa' => fn ($item) => $item->expiry_date?->format('Y-m-d') ?? '',
        'Department' => fn ($item) => $item->department?->name ?? '',
        'Owner' => fn ($item) => $item->owner?->name ?? '',
        'Approver' => fn ($item) => $item->approver?->name ?? '',
        'Rahasia' => fn ($item) => $item->is_confidential ? 'Ya' : 'Tidak',
        'Dibuat Pada' => fn ($item) => $item->created_at?->format('Y-m-d H:i:s') ?? '',
    ], 'documents-export.csv');
}
```

---

## 6. Error Responses

| Status | When | Response |
|---|---|---|
| `403` | User lacks required permission | Inertia redirects to dashboard with error flash (middleware handles) |
| `404` | Document ID not found | Laravel default 404 page |
| `422` | Validation failure | Inertia sends errors back to form with `$page.props.errors` |
| `400` | Invalid workflow transition | RuntimeException caught → redirect back with error flash |
| `419` | CSRF token expired | Laravel default |

### Invalid workflow transition handling:

```php
try {
    $this->workflowService->transition('document', $document->id, $actionKey, $actor, $reason);
} catch (RuntimeException $e) {
    return back()->withErrors(['workflow' => $e->getMessage()]);
}
```

---

## 7. Permission Enforcement Points

| Layer | How |
|---|---|
| **Route middleware** | `->middleware('permission:document.control.view')` on each route |
| **Controller authorize()** | `$this->authorize('view', $document)` for show/edit (scope filtering) |
| **Inertia shared props** | `auth.permissions` array → frontend checks via `permissions.has('document.control.create')` |
| **Export** | Route middleware `permission:document.control.export` |
| **File download** | Controller checks `document.control.view` + confidential access + scope |

---

## 8. Numbering Integration

On `store`:

```php
$generatedNumber = app(NumberingService::class)->generate(
    moduleName: 'document',
    actor: $actor,
    referenceType: ControlledDocument::class,
    referenceId: $document->id,
);

$document->update(['document_number' => $generatedNumber->number]);
```

Numbering format (already seeded): `DOC-2026-0001`

---

## 9. Workflow Integration

The workflow definition `document` is already seeded in `WorkflowSeeder` with:

- Initial status: `draft`
- Terminal statuses: `obsolete`, `rejected` (rejected is not truly terminal — can revise back to draft)

### Transitions used by this module:

| Action Key | Controller Method | From | To | requires_reason |
|---|---|---|---|---|
| `submit_review` | `submitReview()` | draft | review | false |
| `approve` | `approve()` | review | approved | false |
| `make_effective` | `makeEffective()` | approved | effective | false |
| `obsolete` | `obsolete()` | effective | obsolete | **true** |
| `reject` | `reject()` | review | rejected | **true** |
| `revise` | `revise()` | rejected | draft | false |

### Full workflow path:

```
draft ──(submit_review)──→ review ──(approve)──→ approved ──(make_effective)──→ effective ──(obsolete)──→ obsolete
                                  ↘(reject)──→ rejected ──(revise)──→ draft
```

---

## 10. File Upload Integration

Document files are uploaded via the existing core `ManagedFileController` routes (not module-specific).

### Upload flow:

1. User creates document → gets `controlled_documents.id`
2. User uploads file via `POST /core/files` with:
   - `module_name`: `document`
   - `reference_id`: `$document->id`
   - `collection`: `document_file`
   - `file`: the UploadedFile
3. `ManagedFileService::store($file, new FileReference('document', $document->id, 'document_file'), $uploader)`
4. File stored on `local` disk at `document/{id}/document_file/{uuid}.{ext}`
5. Download via `GET /core/files/{managedFile}/download` (permission-gated by `document.control.view` + confidential check + module reference check)

### Show page loads document files:

```php
'files' => ManagedFile::query()
    ->where('module_name', 'document')
    ->where('reference_id', $document->id)
    ->where('collection', 'document_file')
    ->whereNull('deleted_at')
    ->get(),
```

### Confidential file download check:

```php
// In file download controller/policy
if ($managedFile->module_name === 'document') {
    $document = ControlledDocument::find($managedFile->reference_id);
    if ($document && $document->is_confidential) {
        $authorized = in_array($user->id, [
            $document->owner_id,
            $document->approver_id,
        ]) || $user->hasRole(['Super Admin', 'Admin', 'QHSSE Manager']);
        
        if (!$authorized) {
            abort(403, 'Anda tidak memiliki akses untuk mengunduh file rahasia.');
        }
    }
}
```

---

## 11. Expiry Reminder Integration

### Scheduled Command

File: `app/Console/Commands/CheckDocumentExpiry.php`

```php
protected $signature = 'documents:check-expiry';
protected $description = 'Check documents approaching review/expiry date and send notifications';

public function handle(NotificationService $notificationService): void
{
    $thresholds = [30, 7, 1];
    
    foreach ($thresholds as $days) {
        $documents = ControlledDocument::where('status', 'effective')
            ->where(function ($query) use ($days) {
                $query->whereDate('review_date', now()->addDays($days)->toDateString())
                      ->orWhereDate('expiry_date', now()->addDays($days)->toDateString());
            })
            ->get();
        
        foreach ($documents as $document) {
            $notificationService->notify(
                $document->owner,
                'document.expiry_reminder',
                [
                    'document' => $document,
                    'days_remaining' => $days,
                ],
                null,
                'document',
                $document->id,
                "/documents/{$document->id}"
            );
        }
    }
}
```

### Cron Schedule

```php
// app/Console/Kernel.php or routes/console.php
Schedule::command('documents:check-expiry')->dailyAt('08:00');
```

---

## 12. Integration Points

### 12.1 Core Services Used

| Service | Usage |
|---|---|
| `NumberingService` | Generate `DOC-YYYY-NNNN` |
| `WorkflowService` | All status transitions |
| `ManagedFileService` | File upload/download |
| `NotificationService` | 6 notification events |
| `AuditService` | Audit trail for all critical changes |
| `ActivityService` | Activity timeline |
| `CommentService` | Comments on documents |
| `ListQuery` | Paginated list with search/filter |
| `CsvExporter` | CSV export |

### 12.2 Cross-Module References

| Module | How |
|---|---|
| `08-training-management` | Training records can reference `document_number` as training material |
| `13-risk-management` | HIRADC documents produced by risk module are managed here |
| `15-emergency-preparedness` | Emergency response manuals managed here |
| `20-admin-master-data` | Departments, Users master data used for FK |
