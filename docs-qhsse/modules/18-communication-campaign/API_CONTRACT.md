# API Contract — Communication & Campaign

> Spec ini mendefinisikan semua route, request, response, dan error handling untuk modul Communication & Campaign.

## 1. Route Table

Modul ini memiliki 1 route group: campaigns. Semua route menggunakan middleware `auth,verified`.

### 1.1 Campaigns

Prefix: `/campaigns`, nama route `communication.campaigns.*`

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/campaigns` | `index` | `communication.campaigns.index` | `communication.campaigns.view` | List campaigns with search/filter/pagination |
| GET | `/campaigns/create` | `create` | `communication.campaigns.create` | `communication.campaigns.create` | Render create form |
| POST | `/campaigns` | `store` | `communication.campaigns.store` | `communication.campaigns.create` | Save new campaign (generates COM number) |
| GET | `/campaigns/{campaign}` | `show` | `communication.campaigns.show` | `communication.campaigns.view` | Show campaign detail (increments view_count) |
| GET | `/campaigns/{campaign}/edit` | `edit` | `communication.campaigns.edit` | `communication.campaigns.update` | Render edit form (draft only) |
| PUT/PATCH | `/campaigns/{campaign}` | `update` | `communication.campaigns.update` | `communication.campaigns.update` | Update campaign (draft only) |
| POST | `/campaigns/{campaign}/publish` | `publish` | `communication.campaigns.publish` | `communication.campaigns.publish` | Publish campaign (draft → published, triggers notification blast) |
| POST | `/campaigns/{campaign}/acknowledge` | `acknowledge` | `communication.campaigns.acknowledge` | `auth` (any authenticated user in target audience) | Acknowledge campaign |
| GET | `/campaigns/export` | `export` | `communication.campaigns.export` | `communication.campaigns.export` | Export filtered list as CSV |

### Route Registration

File: `routes/modules.php`

```php
use App\Http\Controllers\Modules\Communication\CampaignController;

Route::middleware(['auth', 'verified'])
    ->prefix('campaigns')
    ->name('communication.campaigns.')
    ->group(function (): void {
        Route::get('/', [CampaignController::class, 'index'])
            ->name('index')
            ->middleware('permission:communication.campaigns.view');

        Route::get('/create', [CampaignController::class, 'create'])
            ->name('create')
            ->middleware('permission:communication.campaigns.create');

        Route::post('/', [CampaignController::class, 'store'])
            ->name('store')
            ->middleware('permission:communication.campaigns.create');

        Route::get('/export', [CampaignController::class, 'export'])
            ->name('export')
            ->middleware('permission:communication.campaigns.export');

        Route::get('/{campaign}', [CampaignController::class, 'show'])
            ->name('show')
            ->middleware('permission:communication.campaigns.view');

        Route::get('/{campaign}/edit', [CampaignController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:communication.campaigns.update');

        Route::put('/{campaign}', [CampaignController::class, 'update'])
            ->name('update')
            ->middleware('permission:communication.campaigns.update');

        Route::post('/{campaign}/publish', [CampaignController::class, 'publish'])
            ->name('publish')
            ->middleware('permission:communication.campaigns.publish');

        Route::post('/{campaign}/acknowledge', [CampaignController::class, 'acknowledge'])
            ->name('acknowledge');
    });
```

### Route Model Binding

- Campaign: `{campaign}` → `Campaign` model via route key (id).

---

## 2. Request Payloads

### 2.1 POST `/campaigns` (store campaign)

```json
{
  "title": "Safety Alert: Kebocoran Pipa Gas di Area Produksi",
  "type": "safety_alert",
  "content": "<h2>Kronologi Kejadian</h2><p>Pada tanggal 10 Juli 2026...</p>",
  "target_audience": "all",
  "site_id": null,
  "department_id": null,
  "target_role": null,
  "expires_at": "2026-08-11 00:00:00",
  "attachments": []
}
```

**Validation Rules (StoreCampaignRequest):**

| Field | Rule | Notes |
|---|---|---|
| `title` | `required|string|max:255` | |
| `type` | `required|string|in:safety_alert,lesson_learned,campaign,announcement,newsletter` | |
| `content` | `required|string` | Rich text HTML |
| `target_audience` | `required|string|in:all,specific_site,specific_department,specific_role` | |
| `site_id` | `nullable|required_if:target_audience,specific_site|exists:sites,id` | Required when target_audience = specific_site |
| `department_id` | `nullable|required_if:target_audience,specific_department|exists:departments,id` | Required when target_audience = specific_department |
| `target_role` | `nullable|required_if:target_audience,specific_role|string|in:Super Admin,Admin,QHSSE Manager,QHSSE Officer,Supervisor,Department Head,Employee / Reporter,Contractor,Auditor,Top Management` | Required when target_audience = specific_role |
| `expires_at` | `nullable|date|after:now` | Optional expiry |
| `attachments` | `nullable|array|max:5` | Max 5 files |
| `attachments.*` | `file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,ppt,pptx|max:10240` | 10 MB per file |

**Controller behavior (store):**

1. Validate request
2. Create `Campaign` with `status = 'draft'`, `author_id = auth()->id()`
3. Generate `campaign_number` via `NumberingService::generate('communication', $actor, ...)`
4. If attachments uploaded, store each via `ManagedFileService::store($file, new FileReference('communication', $campaign->id, 'attachment'), $actor)`
5. `AuditService::created($campaign, $actor, 'communication', $campaign->id)`
6. `ActivityService::log('communication', $campaign->id, 'campaign.created', 'Kampanye dibuat', $actor)`
7. Redirect to `communication.campaigns.show`

### 2.2 PUT `/campaigns/{campaign}` (update campaign)

Same payload as store. Only allowed when `status = 'draft'`.

```json
{
  "title": "Safety Alert: Kebocoran Pipa Gas di Area Produksi (Updated)",
  "type": "safety_alert",
  "content": "<h2>Kronologi Kejadian</h2><p>Updated content...</p>",
  "target_audience": "specific_site",
  "site_id": 3,
  "department_id": null,
  "target_role": null,
  "expires_at": "2026-09-11 00:00:00",
  "attachments": [],
  "deleted_attachment_ids": [5, 6]
}
```

**Additional Validation Rules (UpdateCampaignRequest):**

| Field | Rule | Notes |
|---|---|---|
| `deleted_attachment_ids` | `nullable|array` | IDs of managed_files to soft-delete |
| `deleted_attachment_ids.*` | `exists:managed_files,id` | Must exist |

**Controller behavior (update):**

1. Validate request
2. Check campaign status = `draft` (return 422 error if published)
3. Load old values for audit
4. If `deleted_attachment_ids` provided, soft-delete those files
5. If new attachments uploaded, store via `ManagedFileService`
6. Update campaign
7. `AuditService::updated($campaign, $oldValues, $actor, 'communication', $campaign->id)`
8. `ActivityService::log('communication', $campaign->id, 'campaign.updated', 'Kampanye diperbarui', $actor)`
9. Redirect to `communication.campaigns.show`

### 2.3 POST `/campaigns/{campaign}/publish` (publish campaign)

No request body needed.

**Controller behavior (publish):**

1. Check campaign status = `draft` (return 422 error if already published)
2. Set `status = 'published'`, `published_at = now()`
3. Resolve target audience users:
   - `all`: all active users
   - `specific_site`: users with employee.site_id = campaign.site_id
   - `specific_department`: users with employee.department_id = campaign.department_id
   - `specific_role`: users with role = campaign.target_role
4. Send notification blast via `NotificationService::notifyMany($recipients, 'communication.campaign_published', $context, $actor, 'communication', $campaign->id, route('communication.campaigns.show', $campaign))`
5. `AuditService::log('published', $campaign, ['status' => 'draft'], ['status' => 'published'], $actor, 'communication', $campaign->id)`
6. `ActivityService::log('communication', $campaign->id, 'campaign.published', 'Kampanye dipublikasi', $actor, ['published_at' => $campaign->published_at])`
7. Redirect to `communication.campaigns.show` with success message

### 2.4 POST `/campaigns/{campaign}/acknowledge` (acknowledge campaign)

No request body needed. User must be in target audience.

**Controller behavior (acknowledge):**

1. Check campaign status = `published` (return 422 error if draft)
2. Check user is in target audience (via `Campaign::isTargetedAt($user)`)
3. Check user hasn't already acknowledged (unique constraint enforces)
4. Create `CampaignAcknowledgment` with `user_id`, `acknowledged_at = now()`, `ip_address = request()->ip()`
5. `AuditService::log('acknowledged', $campaignAcknowledgment, null, $acknowledgment->toArray(), $user, 'communication', $campaign->id)`
6. `ActivityService::log('communication', $campaign->id, 'campaign.acknowledged', "Dikonfirmasi oleh {$user->name}", $user)`
7. Return JSON response or redirect back with success message

---

## 3. Inertia Response Props

### 3.1 Index Page (`Communication/Campaign/Index.tsx`)

```typescript
{
  items: {
    data: (Campaign & {
      author: { id: number; name: string };
      acknowledgments_count: number;
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
    type: string | null,
    status: string | null,
  },
  can: {
    create: boolean,
    export: boolean,
    publish: boolean,
  },
}
```

### 3.2 Form Page (`Communication/Campaign/Form.tsx`)

```typescript
{
  item: Campaign | null,  // null for create, populated for edit
  sites: { id: number; name: string; code: string }[],
  departments: { id: number; name: string; code: string }[],
  roles: string[],  // available role names
}
```

### 3.3 Show Page (`Communication/Campaign/Show.tsx`)

```typescript
{
  campaign: Campaign & {
    author: { id: number; name: string };
    site: { id: number; name: string } | null;
    department: { id: number; name: string } | null;
    attachments: ManagedFile[];
    isAcknowledged: boolean;  // whether current user has acknowledged
    acknowledgedAt: string | null;  // ISO timestamp if acknowledged
  },
  acknowledgments: {
    data: (CampaignAcknowledgment & {
      user: { id: number; name: string; email: string };
    })[],
    current_page: number,
    last_page: number,
    total: number,
  } | null,  // null if user lacks communication.acknowledgments.view
  targetAudienceCount: number,  // total users in target audience
  activities: ActivityLog[],
  isExpired: boolean,
  can: {
    update: boolean,
    publish: boolean,
    export: boolean,
    viewAcknowledgments: boolean,
  },
}
```

---

## 4. ListQuery Parameters

### 4.1 Campaign Index

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `campaign_number` and `title` (OR) |
| `type` | string | `null` | Filter by exact type: safety_alert, lesson_learned, campaign, announcement, newsletter |
| `status` | string | `null` | Filter by exact status: draft, published |
| `per_page` | int | `15` | Items per page (max 100) |
| `sort` | string | `created_at` | Sort column |
| `direction` | string | `desc` | Sort direction (asc/desc) |

### Controller index method pattern:

```php
public function index(ListQuery $listQuery): Response
{
    $query = Campaign::query()
        ->with(['author:id,name'])
        ->withCount('acknowledgments')
        ->scoped(); // applies data scope filter based on role + target_audience

    $items = $listQuery->paginate(
        $query,
        ['campaign_number', 'title'],
        ['created_at', 'updated_at', 'campaign_number', 'title', 'published_at', 'expires_at'],
        'created_at',
        15,
    );

    return Inertia::render('Modules/Communication/Campaign/Index', [
        'items' => $items,
        'filters' => $listQuery->filters(),
        'can' => [
            'create' => auth()->user()->can('communication.campaigns.create'),
            'export' => auth()->user()->can('communication.campaigns.export'),
            'publish' => auth()->user()->can('communication.campaigns.publish'),
        ],
    ]);
}
```

---

## 5. CSV Export Specification

Endpoint: `GET /campaigns/export?search=...&type=...&status=...`

Applies same ListQuery filters, then streams CSV via `CsvExporter`.

### CSV Columns:

| Column Header | Source |
|---|---|
| `Nomor` | `campaign_number` |
| `Judul` | `title` |
| `Tipe` | `type` |
| `Target Audiens` | `target_audience` |
| `Site` | `site.name` |
| `Departemen` | `department.name` |
| `Role Target` | `target_role` |
| `Status` | `status` |
| `Published At` | `published_at` |
| `Expires At` | `expires_at` |
| `Views` | `view_count` |
| `Acknowledgments` | `acknowledgments_count` |
| `Author` | `author.name` |
| `Dibuat` | `created_at` |

### Controller export method pattern:

```php
public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
{
    $query = $listQuery->apply(
        Campaign::query()
            ->with(['author:id,name', 'site:id,name', 'department:id,name'])
            ->withCount('acknowledgments')
            ->scoped(),
        ['campaign_number', 'title'],
        ['created_at', 'updated_at', 'campaign_number', 'title', 'published_at', 'expires_at'],
        'created_at',
    );

    return $exporter->stream(
        $query,
        [
            'campaign_number' => 'Nomor',
            'title' => 'Judul',
            'type' => 'Tipe',
            'target_audience' => 'Target Audiens',
            'site.name' => 'Site',
            'department.name' => 'Departemen',
            'target_role' => 'Role Target',
            'status' => 'Status',
            'published_at' => 'Published At',
            'expires_at' => 'Expires At',
            'view_count' => 'Views',
            'acknowledgments_count' => 'Acknowledgments',
            'author.name' => 'Author',
            'created_at' => 'Dibuat',
        ],
        'campaigns_export_' . now()->format('Ymd_His') . '.csv',
    );
}
```

---

## 6. Error Responses

### 6.1 Validation Errors (422)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "title": ["Field judul wajib diisi."],
    "type": ["Field tipe tidak valid."],
    "site_id": ["Field site wajib diisi ketika target audiens adalah Site Tertentu."]
  }
}
```

### 6.2 Authorization Errors (403)

```json
{
  "message": "This action is unauthorized."
}
```

### 6.3 Business Rule Errors (422)

#### Cannot edit published campaign:

```json
{
  "message": "Kampanye yang sudah dipublikasi tidak dapat diubah.",
  "errors": {
    "status": ["Kampanye sudah dipublikasi. Hanya draft yang dapat diubah."]
  }
}
```

#### Cannot publish already published campaign:

```json
{
  "message": "Kampanye sudah dipublikasi.",
  "errors": {
    "status": ["Kampanye sudah berstatus published."]
  }
}
```

#### Cannot acknowledge draft campaign:

```json
{
  "message": "Kampanye belum dipublikasi.",
  "errors": {
    "status": ["Hanya kampanye yang sudah dipublikasi yang dapat dikonfirmasi."]
  }
}
```

#### User not in target audience:

```json
{
  "message": "Anda tidak termasuk target audiens kampanye ini.",
  "errors": {
    "target_audience": ["Anda tidak memiliki akses untuk mengkonfirmasi kampanye ini."]
  }
}
```

#### Double acknowledgment:

```json
{
  "message": "Anda sudah mengkonfirmasi kampanye ini.",
  "errors": {
    "acknowledgment": ["Anda sudah mengkonfirmasi (acknowledge) kampanye ini sebelumnya."]
  }
}
```

### 6.4 Not Found (404)

```json
{
  "message": "Not found."
}
```

---

## 7. Integration Points

### 7.1 NotificationService (Publish Blast)

When a campaign is published, the controller resolves target audience and sends a notification blast:

```php
// In CampaignController::publish()

$recipients = $this->resolveTargetAudience($campaign);

$notificationService->notifyMany(
    recipients: $recipients,
    type: 'communication.campaign_published',
    context: [
        'campaign' => $campaign->only(['id', 'campaign_number', 'title', 'type', 'published_at']),
        'type_label' => $typeLabels[$campaign->type] ?? $campaign->type,
        'acknowledgment_message' => $this->getAcknowledgmentMessage($campaign->type),
    ],
    actor: $actor,
    moduleName: 'communication',
    referenceId: $campaign->id,
    actionUrl: route('communication.campaigns.show', $campaign),
);
```

### 7.2 Target Audience Resolution

```php
private function resolveTargetAudience(Campaign $campaign): Collection
{
    $query = User::where('is_active', true);

    match ($campaign->target_audience) {
        'all' => $query, // all active users
        'specific_site' => $query->whereHas('employee', fn ($q) =>
            $q->where('site_id', $campaign->site_id)
        ),
        'specific_department' => $query->whereHas('employee', fn ($q) =>
            $q->where('department_id', $campaign->department_id)
        ),
        'specific_role' => $query->whereHas('roles', fn ($q) =>
            $q->where('name', $campaign->target_role)
        ),
    };

    return $query->get();
}
```

### 7.3 NumberingService Integration

```php
// In CampaignController::store()

$generatedNumber = app(NumberingService::class)->generate(
    moduleName: 'communication',
    actor: $actor,
    siteCode: null,
    referenceType: 'campaign',
    referenceId: $campaign->id,
    metadata: ['type' => $campaign->type],
);

$campaign->update(['campaign_number' => $generatedNumber->number]);
```

### 7.4 ManagedFileService Integration

```php
// In CampaignController::store() and update()

if ($request->hasFile('attachments')) {
    foreach ($request->file('attachments') as $file) {
        app(ManagedFileService::class)->store(
            file: $file,
            reference: new FileReference('communication', $campaign->id, 'attachment'),
            user: auth()->user(),
            metadata: ['uploaded_at' => now()->toISOString()],
        );
    }
}
```

### 7.5 View Count Increment

```php
// In CampaignController::show()

public function show(Campaign $campaign): Response
{
    $user = auth()->user();

    // Increment view count (deduplication via cache)
    $cacheKey = "campaign:{$campaign->id}:viewed:{$user->id}";
    if (!cache()->has($cacheKey) && $campaign->author_id !== $user->id) {
        $campaign->increment('view_count');
        cache()->put($cacheKey, true, now()->addDays(30));
    }

    // Check if user has acknowledged
    $acknowledgment = $campaign->acknowledgments()
        ->where('user_id', $user->id)
        ->first();

    return Inertia::render('Modules/Communication/Campaign/Show', [
        'campaign' => $campaign->load(['author', 'site', 'department', 'attachments']),
        'isAcknowledged' => $acknowledgment !== null,
        'acknowledgedAt' => $acknowledgment?->acknowledged_at,
        'acknowledgments' => $user->can('communication.acknowledgments.view')
            ? $campaign->acknowledgments()
                ->with('user:id,name,email')
                ->latest('acknowledged_at')
                ->paginate(15, '*', 'ack_page')
            : null,
        'targetAudienceCount' => $this->resolveTargetAudience($campaign)->count(),
        'activities' => $campaign->activities()->latest()->limit(20)->get(),
        'isExpired' => $campaign->expires_at && $campaign->expires_at < now(),
        'can' => [
            'update' => $user->can('update', $campaign),
            'publish' => $user->can('publish', $campaign),
            'export' => $user->can('communication.campaigns.export'),
            'viewAcknowledgments' => $user->can('communication.acknowledgments.view'),
        ],
    ]);
}
```

### 7.6 Acknowledge Endpoint

```php
// In CampaignController::acknowledge()

public function acknowledge(Campaign $campaign): RedirectResponse
{
    $user = auth()->user();

    // Check campaign is published
    if ($campaign->status !== 'published') {
        return back()->withErrors(['status' => 'Hanya kampanye yang sudah dipublikasi yang dapat dikonfirmasi.']);
    }

    // Check user is in target audience
    if (!$campaign->isTargetedAt($user)) {
        return back()->withErrors(['target_audience' => 'Anda tidak termasuk target audiens kampanye ini.']);
    }

    // Check if already acknowledged (unique constraint also enforces)
    if ($campaign->isAcknowledgedBy($user)) {
        return back()->withErrors(['acknowledgment' => 'Anda sudah mengkonfirmasi kampanye ini.']);
    }

    // Create acknowledgment
    $acknowledgment = CampaignAcknowledgment::create([
        'campaign_id' => $campaign->id,
        'user_id' => $user->id,
        'acknowledged_at' => now(),
        'ip_address' => request()->ip(),
    ]);

    // Log
    app(AuditService::class)->log(
        event: 'acknowledged',
        model: $acknowledgment,
        oldValues: null,
        newValues: $acknowledgment->toArray(),
        actor: $user,
        moduleName: 'communication',
        referenceId: $campaign->id,
    );

    app(ActivityService::class)->log(
        moduleName: 'communication',
        referenceId: $campaign->id,
        event: 'campaign.acknowledged',
        description: "Dikonfirmasi oleh {$user->name}",
        actor: $user,
    );

    return redirect()
        ->route('communication.campaigns.show', $campaign)
        ->with('success', 'Konfirmasi berhasil. Terima kasih telah membaca.');
}
```
