# API Contract — Security Management

> Spec ini mendefinisikan semua route, request, response, dan error handling untuk modul Security Management.

---

## 1. Route Table

Modul ini memiliki 3 resource groups dengan prefix terpisah.

### Resource Group 1: Security Incidents

Prefix: `/security-incidents`, route name: `security.incidents.*`, middleware: `auth,verified`

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/security-incidents` | `index` | `security.incidents.index` | `security.incidents.view` | List incidents with search/filter/pagination |
| GET | `/security-incidents/create` | `create` | `security.incidents.create` | `security.incidents.create` | Render create form |
| POST | `/security-incidents` | `store` | `security.incidents.store` | `security.incidents.create` | Save new security incident |
| GET | `/security-incidents/{securityIncident}` | `show` | `security.incidents.show` | `security.incidents.view` | Show incident detail |
| GET | `/security-incidents/{securityIncident}/edit` | `edit` | `security.incidents.edit` | `security.incidents.update` | Render edit form |
| PUT/PATCH | `/security-incidents/{securityIncident}` | `update` | `security.incidents.update` | `security.incidents.update` | Update incident |
| POST | `/security-incidents/{securityIncident}/investigate` | `investigate` | `security.incidents.investigate` | `security.incidents.update` | Transition reported → under_investigation |
| POST | `/security-incidents/{securityIncident}/close` | `close` | `security.incidents.close` | `security.incidents.close` | Close incident with resolution |
| GET | `/security-incidents/export` | `export` | `security.incidents.export` | `security.incidents.export` | Export filtered list as CSV |

### Resource Group 2: Visitor Logs

Prefix: `/visitor-logs`, route name: `security.visitors.*`, middleware: `auth,verified`

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/visitor-logs` | `index` | `security.visitors.index` | `security.visitors.view` | List visitor logs |
| GET | `/visitor-logs/create` | `create` | `security.visitors.create` | `security.visitors.create` | Render check-in form |
| POST | `/visitor-logs` | `store` | `security.visitors.store` | `security.visitors.create` | Check-in new visitor |
| GET | `/visitor-logs/{visitorLog}` | `show` | `security.visitors.show` | `security.visitors.view` | Show visitor detail |
| GET | `/visitor-logs/{visitorLog}/edit` | `edit` | `security.visitors.edit` | `security.visitors.update` | Render edit form |
| PUT/PATCH | `/visitor-logs/{visitorLog}` | `update` | `security.visitors.update` | `security.visitors.update` | Update visitor log |
| POST | `/visitor-logs/{visitorLog}/check-out` | `checkOut` | `security.visitors.check-out` | `security.visitors.update` | Check-out visitor |
| GET | `/visitor-logs/export` | `export` | `security.visitors.export` | `security.visitors.view` | Export visitor logs as CSV |

### Resource Group 3: Patrol Checklists

Prefix: `/patrol-checklists`, route name: `security.patrols.*`, middleware: `auth,verified`

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/patrol-checklists` | `index` | `security.patrols.index` | `security.patrols.view` | List patrol checklists |
| GET | `/patrol-checklists/create` | `create` | `security.patrols.create` | `security.patrols.create` | Render create form |
| POST | `/patrol-checklists` | `store` | `security.patrols.store` | `security.patrols.create` | Save new patrol checklist |
| GET | `/patrol-checklists/{patrolChecklist}` | `show` | `security.patrols.show` | `security.patrols.view` | Show patrol detail (execute page) |
| GET | `/patrol-checklists/{patrolChecklist}/edit` | `edit` | `security.patrols.edit` | `security.patrols.create` | Render edit form |
| PUT/PATCH | `/patrol-checklists/{patrolChecklist}` | `update` | `security.patrols.update` | `security.patrols.create` | Update patrol checklist |
| POST | `/patrol-checklists/{patrolChecklist}/execute` | `execute` | `security.patrols.execute` | `security.patrols.execute` | Start patrol (scheduled → in_progress) |
| POST | `/patrol-checklists/{patrolChecklist}/complete` | `complete` | `security.patrols.complete` | `security.patrols.execute` | Complete patrol (in_progress → completed) |
| POST | `/patrol-checklists/{patrolChecklist}/results` | `storeResult` | `security.patrols.results.store` | `security.patrols.execute` | Save checkpoint result |
| GET | `/patrol-checklists/export` | `export` | `security.patrols.export` | `security.patrols.export` | Export patrol checklists as CSV |

---

## 2. Route Registration

File: `routes/modules.php`

```php
use App\Http\Controllers\Modules\Security\SecurityIncidentController;
use App\Http\Controllers\Modules\Security\VisitorLogController;
use App\Http\Controllers\Modules\Security\PatrolChecklistController;

// Security Incidents
Route::middleware(['auth', 'verified'])
    ->prefix('security-incidents')
    ->name('security.incidents.')
    ->group(function (): void {
        Route::get('/', [SecurityIncidentController::class, 'index'])
            ->name('index')
            ->middleware('permission:security.incidents.view');

        Route::get('/create', [SecurityIncidentController::class, 'create'])
            ->name('create')
            ->middleware('permission:security.incidents.create');

        Route::post('/', [SecurityIncidentController::class, 'store'])
            ->name('store')
            ->middleware('permission:security.incidents.create');

        Route::get('/{securityIncident}', [SecurityIncidentController::class, 'show'])
            ->name('show')
            ->middleware('permission:security.incidents.view');

        Route::get('/{securityIncident}/edit', [SecurityIncidentController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:security.incidents.update');

        Route::put('/{securityIncident}', [SecurityIncidentController::class, 'update'])
            ->name('update')
            ->middleware('permission:security.incidents.update');

        Route::post('/{securityIncident}/investigate', [SecurityIncidentController::class, 'investigate'])
            ->name('investigate')
            ->middleware('permission:security.incidents.update');

        Route::post('/{securityIncident}/close', [SecurityIncidentController::class, 'close'])
            ->name('close')
            ->middleware('permission:security.incidents.close');

        Route::get('/export', [SecurityIncidentController::class, 'export'])
            ->name('export')
            ->middleware('permission:security.incidents.export');
    });

// Visitor Logs
Route::middleware(['auth', 'verified'])
    ->prefix('visitor-logs')
    ->name('security.visitors.')
    ->group(function (): void {
        Route::get('/', [VisitorLogController::class, 'index'])
            ->name('index')
            ->middleware('permission:security.visitors.view');

        Route::get('/create', [VisitorLogController::class, 'create'])
            ->name('create')
            ->middleware('permission:security.visitors.create');

        Route::post('/', [VisitorLogController::class, 'store'])
            ->name('store')
            ->middleware('permission:security.visitors.create');

        Route::get('/{visitorLog}', [VisitorLogController::class, 'show'])
            ->name('show')
            ->middleware('permission:security.visitors.view');

        Route::get('/{visitorLog}/edit', [VisitorLogController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:security.visitors.update');

        Route::put('/{visitorLog}', [VisitorLogController::class, 'update'])
            ->name('update')
            ->middleware('permission:security.visitors.update');

        Route::post('/{visitorLog}/check-out', [VisitorLogController::class, 'checkOut'])
            ->name('check-out')
            ->middleware('permission:security.visitors.update');

        Route::get('/export', [VisitorLogController::class, 'export'])
            ->name('export')
            ->middleware('permission:security.visitors.view');
    });

// Patrol Checklists
Route::middleware(['auth', 'verified'])
    ->prefix('patrol-checklists')
    ->name('security.patrols.')
    ->group(function (): void {
        Route::get('/', [PatrolChecklistController::class, 'index'])
            ->name('index')
            ->middleware('permission:security.patrols.view');

        Route::get('/create', [PatrolChecklistController::class, 'create'])
            ->name('create')
            ->middleware('permission:security.patrols.create');

        Route::post('/', [PatrolChecklistController::class, 'store'])
            ->name('store')
            ->middleware('permission:security.patrols.create');

        Route::get('/{patrolChecklist}', [PatrolChecklistController::class, 'show'])
            ->name('show')
            ->middleware('permission:security.patrols.view');

        Route::get('/{patrolChecklist}/edit', [PatrolChecklistController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:security.patrols.create');

        Route::put('/{patrolChecklist}', [PatrolChecklistController::class, 'update'])
            ->name('update')
            ->middleware('permission:security.patrols.create');

        Route::post('/{patrolChecklist}/execute', [PatrolChecklistController::class, 'execute'])
            ->name('execute')
            ->middleware('permission:security.patrols.execute');

        Route::post('/{patrolChecklist}/complete', [PatrolChecklistController::class, 'complete'])
            ->name('complete')
            ->middleware('permission:security.patrols.execute');

        Route::post('/{patrolChecklist}/results', [PatrolChecklistController::class, 'storeResult'])
            ->name('results.store')
            ->middleware('permission:security.patrols.execute');

        Route::get('/export', [PatrolChecklistController::class, 'export'])
            ->name('export')
            ->middleware('permission:security.patrols.export');
    });
```

---

## 3. Request Payloads

### POST `/security-incidents` (store)

```json
{
  "type": "unauthorized_access",
  "title": "Akses Tidak Sah ke Server Room",
  "description": "Pada tanggal 11 Juli 2026 pukul 14:30 WIB, terdeteksi akses tidak sah ke Server Room melalui CCTV.",
  "site_id": 1,
  "area_id": 2,
  "occurred_at": "2026-07-11T14:30:00",
  "severity_id": 4,
  "checkpoints": []
}
```

**Validation Rules (StoreSecurityIncidentRequest):**

| Field | Rule | Notes |
|---|---|---|
| `type` | `required\|in:unauthorized_access,theft,vandalism,trespass,suspicious_activity,other` | |
| `title` | `required\|string\|min:5\|max:255` | |
| `description` | `required\|string\|min:20` | |
| `site_id` | `required\|exists:sites,id` | |
| `area_id` | `nullable\|exists:areas,id` | |
| `occurred_at` | `required\|date\|before_or_equal:now` | |
| `severity_id` | `required\|exists:severities,id` | |

**Controller behavior (store):**
1. Validate request
2. Create `SecurityIncident` with `reported_by` = auth user, `status` = `reported`
3. Generate `security_number` via `NumberingService::generate('security', $actor, ...)`
4. `AuditService::created($incident, $actor, 'security', $incident->id)`
5. `ActivityService::log('security', $incident->id, 'security.incident.created', ...)`
6. `NotificationService::notifyMany($securityTeamUsers, 'security.incident.reported', [...])`
7. Redirect to `security.incidents.show`

### PUT `/security-incidents/{securityIncident}` (update)

Same payload as store. Only allowed if `status` is `reported` or `under_investigation`.

### POST `/security-incidents/{securityIncident}/investigate`

No request body needed. Controller:
1. Check `incident.status === 'reported'`
2. Update status to `under_investigation`
3. `AuditService::updated($incident, [...], $actor, 'security', $incident->id)`
4. `ActivityService::log('security', $incident->id, 'investigation_started', ...)`
5. Redirect back

### POST `/security-incidents/{securityIncident}/close`

```json
{
  "resolution": "Investigasi menunjukkan pintu darurat tidak terkunci. Sistem akses telah diperbaiki dan prosedur diperbarui."
}
```

| Field | Rule |
|---|---|
| `resolution` | `required\|string\|min:10\|max:2000` |

**Controller behavior:**
1. Check `incident.status` is `reported` or `under_investigation`
2. Update status to `closed`, set `resolution`, set `resolved_at` = now()
3. `AuditService::updated($incident, [...], $actor, 'security', $incident->id)`
4. `ActivityService::log('security', $incident->id, 'security.incident.closed', ...)`
5. `NotificationService::notify($reporter, 'security.incident.closed', [...])`
6. Redirect back

### POST `/visitor-logs` (store — check-in)

```json
{
  "visitor_name": "Andi Pratama",
  "visitor_company": "PT Maju Jaya",
  "purpose": "Meeting dengan tim engineering untuk diskusi project",
  "host_id": 5,
  "site_id": 1,
  "id_type": "KTP",
  "id_number": "3201234567890001",
  "vehicle_plate": "B 1234 ABC",
  "check_in_at": "2026-07-11T09:00:00"
}
```

**Validation Rules (StoreVisitorLogRequest):**

| Field | Rule | Notes |
|---|---|---|
| `visitor_name` | `required\|string\|max:255` | |
| `visitor_company` | `nullable\|string\|max:255` | |
| `purpose` | `required\|string\|min:5` | |
| `host_id` | `required\|exists:users,id` | |
| `site_id` | `required\|exists:sites,id` | |
| `id_type` | `required\|string\|max:50` | KTP, SIM, Passport, Lainnya |
| `id_number` | `required\|string\|max:100` | |
| `vehicle_plate` | `nullable\|string\|max:20` | |
| `check_in_at` | `required\|date` | Default: now() |

**Controller behavior:**
1. Validate request, set `check_in_at` to now() if not provided
2. Create `VisitorLog`
3. `AuditService::created($visitor, $actor, 'security', $visitor->id)`
4. `ActivityService::log('security', $visitor->id, 'security.visitor.checked_in', ...)`
5. `NotificationService::notify($host, 'security.visitor.checked_in', [...])`
6. Redirect to `security.visitors.show`

### POST `/visitor-logs/{visitorLog}/check-out`

No request body needed. Controller:
1. Check `visitor.check_out_at` is NULL
2. Set `check_out_at` = now()
3. `AuditService::updated($visitor, [...], $actor, 'security', $visitor->id)`
4. `ActivityService::log('security', $visitor->id, 'security.visitor.checked_out', ...)`
5. `NotificationService::notify($host, 'security.visitor.checked_out', [...])`
6. Redirect back

### POST `/patrol-checklists` (store)

```json
{
  "site_id": 1,
  "patrol_route": "Rute Malam — Gerbang Utama ke Gudang",
  "officer_id": 3,
  "scheduled_at": "2026-07-11T22:00:00",
  "checkpoints": [
    { "checkpoint": "Gerbang Utama" },
    { "checkpoint": "Area Parkir" },
    { "checkpoint": "Gudang Bahan Baku" }
  ],
  "notes": "Patroli rutin malam"
}
```

**Validation Rules (StorePatrolChecklistRequest):**

| Field | Rule | Notes |
|---|---|---|
| `site_id` | `required\|exists:sites,id` | |
| `patrol_route` | `required\|string\|max:255` | |
| `officer_id` | `required\|exists:users,id` | |
| `scheduled_at` | `required\|date` | |
| `checkpoints` | `required\|array\|min:1` | At least 1 checkpoint |
| `checkpoints.*.checkpoint` | `required\|string\|max:255` | |
| `notes` | `nullable\|string` | |

**Controller behavior:**
1. Validate request
2. Create `PatrolChecklist` with `status` = `scheduled`
3. Generate `patrol_number` via `NumberingService::generate('security', $actor, ...)` (SPL prefix)
4. Create `PatrolResult` records for each checkpoint with `status` = null (pending)
5. `AuditService::created($patrol, $actor, 'security', $patrol->id)`
6. `ActivityService::log('security', $patrol->id, 'security.patrol.created', ...)`
7. Redirect to `security.patrols.show`

### POST `/patrol-checklists/{patrolChecklist}/execute`

No request body. Controller:
1. Check `patrol.status === 'scheduled'`
2. Update status to `in_progress`, set `executed_at` = now()
3. `ActivityService::log('security', $patrol->id, 'security.patrol.executed', ...)`
4. `NotificationService::notifyMany($qhsseTeam, 'security.patrol.executed', [...])`
5. Redirect back

### POST `/patrol-checklists/{patrolChecklist}/results`

```json
{
  "patrol_result_id": 1,
  "status": "issue",
  "remark": "Pintu belakang tidak terkunci. Perlu perbaikan segera."
}
```

| Field | Rule | Notes |
|---|---|---|
| `patrol_result_id` | `required\|exists:patrol_results,id` | |
| `status` | `required\|in:ok,issue,na` | |
| `remark` | `required_if:status,issue\|nullable\|string\|min:5` | Wajib jika status = issue |

**Controller behavior:**
1. Validate request
2. Update `PatrolResult` with status and remark
3. `ActivityService::log('security', $patrol->id, 'security.patrol.result_recorded', ...)`
4. If `status === 'issue'`: `NotificationService::notifyMany($qhsseTeam, 'security.patrol.issue_found', [...])`
5. Return updated patrol results (JSON response for AJAX/Inertia partial reload)

### POST `/patrol-checklists/{patrolChecklist}/complete`

No request body. Controller:
1. Check `patrol.status === 'in_progress'`
2. Check all `PatrolResult` records have non-null `status`
3. Update status to `completed`
4. `ActivityService::log('security', $patrol->id, 'security.patrol.completed', ...)`
5. Redirect back

---

## 4. Inertia Response Props

### Security Incident Index

```typescript
{
  incidents: {
    data: SecurityIncident[],
    current_page: number,
    last_page: number,
    per_page: number,
    total: number,
  },
  filters: {
    search: string,
    status: string | null,
    type: string | null,
    severity_id: number | null,
    site_id: number | null,
    from: string | null,
    to: string | null,
  },
  sites: Site[],
  severities: Severity[],
  can: {
    create: boolean,
    export: boolean,
  },
}
```

### Security Incident Form

```typescript
{
  incident: SecurityIncident | null,
  sites: Site[],
  areas: Area[],
  severities: Severity[],
}
```

### Security Incident Show

```typescript
{
  incident: SecurityIncident & {
    site: Site,
    area: Area | null,
    reported_by: User,
    severity: Severity,
  },
  evidence: ManagedFile[],
  comments: Comment[],
  activities: ActivityLog[],
  can: {
    update: boolean,
    close: boolean,
  },
}
```

### Visitor Log Index

```typescript
{
  visitors: {
    data: VisitorLog[],
    current_page: number,
    last_page: number,
    per_page: number,
    total: number,
  },
  filters: {
    search: string,
    status: string | null,  // 'on_site', 'checked_out', null
    site_id: number | null,
    from: string | null,
    to: string | null,
  },
  sites: Site[],
  onSiteCount: number,
  can: {
    create: boolean,
    export: boolean,
  },
}
```

### Visitor Log Form

```typescript
{
  visitor: VisitorLog | null,
  sites: Site[],
  hosts: User[],  // users that can be hosts
}
```

### Patrol Checklist Index

```typescript
{
  patrols: {
    data: (PatrolChecklist & { issue_count: number })[],
    current_page: number,
    last_page: number,
    per_page: number,
    total: number,
  },
  filters: {
    search: string,
    status: string | null,
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

### Patrol Checklist Show (Execute)

```typescript
{
  patrol: PatrolChecklist & {
    site: Site,
    officer: User,
    results: PatrolResult[],
  },
  activities: ActivityLog[],
  can: {
    execute: boolean,
    update: boolean,
  },
}
```

---

## 5. ListQuery Parameters

### Security Incidents

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `security_number` and `title` (OR) |
| `status` | string | `null` | Filter: reported, under_investigation, closed |
| `type` | string | `null` | Filter by incident type |
| `severity_id` | int | `null` | Filter by severity |
| `site_id` | int | `null` | Filter by site |
| `from` | date | `null` | Filter occurred_at >= from |
| `to` | date | `null` | Filter occurred_at <= to |
| `per_page` | int | `15` | Items per page (max 100) |
| `sort` | string | `occurred_at` | Sort column |
| `direction` | string | `desc` | Sort direction |

### Controller index method pattern:

```php
public function index(ListQuery $listQuery): Response
{
    $items = $listQuery->paginate(
        SecurityIncident::query()->with(['site', 'area', 'severity', 'reportedBy']),
        ['security_number', 'title'],
        ['occurred_at', 'created_at', 'security_number'],
        'occurred_at',
        15,
    );

    return Inertia::render('Modules/Security/Incident/Index', [
        'incidents' => $items,
        'filters' => $listQuery->filters(),
    ]);
}
```

### Visitor Logs

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `visitor_name`, `visitor_company` |
| `status` | string | `null` | `on_site` (check_out_at IS NULL), `checked_out` |
| `site_id` | int | `null` | Filter by site |
| `from` | date | `null` | Filter check_in_at >= from |
| `to` | date | `null` | Filter check_in_at <= to |
| `per_page` | int | `15` | Items per page |
| `sort` | string | `check_in_at` | Sort column |
| `direction` | string | `desc` | Sort direction |

### Patrol Checklists

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `patrol_number` and `patrol_route` |
| `status` | string | `null` | scheduled, in_progress, completed |
| `site_id` | int | `null` | Filter by site |
| `from` | date | `null` | Filter scheduled_at >= from |
| `to` | date | `null` | Filter scheduled_at <= to |
| `per_page` | int | `15` | Items per page |
| `sort` | string | `scheduled_at` | Sort column |
| `direction` | string | `desc` | Sort direction |

---

## 6. CSV Export Specification

### Security Incidents Export

Endpoint: `GET /security-incidents/export?search=...&status=...`

```php
public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
{
    $query = $listQuery->apply(
        SecurityIncident::query()->with(['site', 'area', 'severity', 'reportedBy']),
        ['security_number', 'title'],
        ['occurred_at', 'created_at'],
        'occurred_at',
    );

    return $exporter->stream($query, [
        'Nomor' => 'security_number',
        'Judul' => 'title',
        'Tipe' => 'type',
        'Deskripsi' => fn ($item) => Str::limit($item->description, 500),
        'Severity' => fn ($item) => $item->severity?->name ?? '',
        'Site' => fn ($item) => $item->site?->name ?? '',
        'Area' => fn ($item) => $item->area?->name ?? '',
        'Pelapor' => fn ($item) => $item->reportedBy?->name ?? '',
        'Waktu Kejadian' => fn ($item) => $item->occurred_at?->format('Y-m-d H:i') ?? '',
        'Status' => 'status',
        'Resolusi' => fn ($item) => $item->resolution ?? '',
        'Ditutup Pada' => fn ($item) => $item->resolved_at?->format('Y-m-d H:i') ?? '',
        'Dibuat Pada' => fn ($item) => $item->created_at?->format('Y-m-d H:i') ?? '',
    ], 'security_incidents_export.csv');
}
```

### Visitor Logs Export

Endpoint: `GET /visitor-logs/export?search=...&status=...`

```php
return $exporter->stream($query, [
    'Nama Pengunjung' => 'visitor_name',
    'Perusahaan' => fn ($item) => $item->visitor_company ?? '',
    'Tujuan' => 'purpose',
    'Host' => fn ($item) => $item->host?->name ?? '',
    'Site' => fn ($item) => $item->site?->name ?? '',
    'Jenis ID' => 'id_type',
    'Nomor ID' => 'id_number',
    'Plat Kendaraan' => fn ($item) => $item->vehicle_plate ?? '',
    'Check-In' => fn ($item) => $item->check_in_at?->format('Y-m-d H:i') ?? '',
    'Check-Out' => fn ($item) => $item->check_out_at?->format('Y-m-d H:i') ?? '',
], 'visitor_logs_export.csv');
```

### Patrol Checklists Export

Endpoint: `GET /patrol-checklists/export?search=...&status=...`

```php
return $exporter->stream($query, [
    'Nomor' => 'patrol_number',
    'Site' => fn ($item) => $item->site?->name ?? '',
    'Rute' => 'patrol_route',
    'Officer' => fn ($item) => $item->officer?->name ?? '',
    'Terjadwal' => fn ($item) => $item->scheduled_at?->format('Y-m-d H:i') ?? '',
    'Dieksekusi' => fn ($item) => $item->executed_at?->format('Y-m-d H:i') ?? '',
    'Status' => 'status',
    'Catatan' => fn ($item) => $item->notes ?? '',
    'Total Checkpoint' => fn ($item) => $item->results()->count(),
    'Issue Found' => fn ($item) => $item->results()->where('status', 'issue')->count(),
], 'patrol_checklists_export.csv');
```

---

## 7. Error Responses

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
    $incident->update(['status' => 'under_investigation']);
} catch (RuntimeException $e) {
    return back()->withErrors(['status' => $e->getMessage()]);
}
```

---

## 8. Permission Enforcement Points

| Layer | How |
|---|---|
| **Route middleware** | `->middleware('permission:security.incidents.view')` on each route |
| **Controller authorize()** | `$this->authorize('view', $incident)` for scope filtering |
| **Inertia shared props** | `auth.permissions` array → frontend checks |
| **Export** | Route middleware `permission:security.incidents.export` |

---

## 9. Numbering Integration

### Security Incident (on store):

```php
$generatedNumber = app(NumberingService::class)->generate(
    moduleName: 'security',
    actor: $actor,
    referenceType: SecurityIncident::class,
    referenceId: $incident->id,
);

$incident->update(['security_number' => $generatedNumber->number]);
```

Numbering format (already seeded): `SEC-2026-0001`

### Patrol Checklist (on store):

```php
$generatedNumber = app(NumberingService::class)->generate(
    moduleName: 'security',
    actor: $actor,
    referenceType: PatrolChecklist::class,
    referenceId: $patrol->id,
    metadata: ['sub_type' => 'patrol'],
);

$patrol->update(['patrol_number' => $generatedNumber->number]);
```

> **Note:** Patrol numbering uses the same `security` module name but the `SPL` prefix is configured via metadata. Alternatively, register a separate numbering format entry for `security_patrol` with prefix `SPL`.

---

## 10. File Upload Integration

Evidence files for security incidents are uploaded via the existing core `ManagedFileController` routes:

### Upload flow:

1. User creates security incident → gets `incident.id`
2. User uploads file via `POST /core/files` with:
   - `module_name`: `security`
   - `reference_id`: `$incident->id`
   - `collection`: `evidence`
   - `file`: the UploadedFile
3. `ManagedFileService::store($file, new FileReference('security', $incident->id, 'evidence'), $uploader)`
4. File stored on `local` disk at `security/{id}/evidence/{uuid}.{ext}`
5. Download via `GET /core/files/{managedFile}/download`

### Show page loads evidence:

```php
'evidence' => ManagedFile::query()
    ->where('module_name', 'security')
    ->where('reference_id', $incident->id)
    ->where('collection', 'evidence')
    ->whereNull('deleted_at')
    ->get(),
```

---

## 11. Integration Points

| Integration | Description |
|---|---|
| **Core Permissions** | 12 permission keys registered in `CorePermissions::all()` and `roleMap()` |
| **NumberingService** | `SEC-YYYY-NNNN` for incidents, `SPL-YYYY-NNNN` for patrols |
| **ManagedFileService** | Evidence file upload/download for security incidents |
| **CommentService** | Comments on security incidents via `module_name='security'` |
| **ActivityLogService** | Activity timeline for all security records |
| **AuditService** | Audit trail for all critical changes |
| **NotificationService** | 6 notification events |
| **CsvExporter** | CSV export for all 3 resource groups |
| **ListQuery** | Search, filter, pagination for all 3 resources |
| **Master Data** | Sites, Areas, Users, Severities |
