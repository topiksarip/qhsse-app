# API Contract — Dashboard & KPI

> **Module ID:** `01-dashboard-kpi`
> **Route:** `GET /dashboard`
> **Controller:** `App\Http\Controllers\DashboardController` (single `__invoke` method)
> **Middleware:** `auth`, `verified`, `permission:core.dashboard.view`
>
> The dashboard has a single endpoint. All data is returned as Inertia props —
> there are no separate REST API endpoints for KPIs, charts, or widgets.

---

## 1. Route Table

| Method | URI | Controller | Route Name | Middleware | Permission | Description |
|---|---|---|---|---|---|---|
| GET | `/dashboard` | `DashboardController::__invoke` | `dashboard` | `auth`, `verified` | `core.dashboard.view` | Render dashboard page with KPIs, charts, widgets, filters |

### Route Registration

File: `routes/web.php`

```php
use App\Http\Controllers\DashboardController;

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'permission:core.dashboard.view'])
    ->name('dashboard');
```

> **Note:** The route already exists in `routes/web.php` with `['auth', 'verified']` middleware.
> Add `permission:core.dashboard.view` to the middleware array.

---

## 2. Query Parameters (Filters)

All parameters are optional. When omitted, defaults are applied.

| Parameter | Type | Default | Validation | Description |
|---|---|---|---|---|
| `from` | string (date) | First day of current month (`now()->startOfMonth()->toDateString()`) | `nullable\|date` | Start of date range filter. Applied to `incidents.occurred_at`. |
| `to` | string (date) | Today (`now()->toDateString()`) | `nullable\|date` | End of date range filter. Applied to `incidents.occurred_at`. |
| `site_id` | integer | `null` (all sites) | `nullable\|integer\|exists:sites,id` | Filter incidents by site. |
| `department_id` | integer | `null` (all departments) | `nullable\|integer\|exists:departments,id` | Filter incidents by department. If `site_id` is also set, department must belong to that site. |

### Example Requests

```http
# Default (no filters)
GET /dashboard

# With date range
GET /dashboard?from=2026-01-01&to=2026-06-30

# With site filter
GET /dashboard?site_id=3

# With all filters
GET /dashboard?from=2026-01-01&to=2026-06-30&site_id=3&department_id=7
```

---

## 3. Inertia Response Props

The `DashboardController::__invoke` returns an Inertia response rendering the `Dashboard` component with these props:

### 3.1 Full Response Shape

```typescript
{
    // ─── Filters (current state) ───
    filters: {
        from: string;              // '2026-07-01'
        to: string;                // '2026-07-11'
        site_id: number | null;    // 3 or null
        department_id: number | null; // 7 or null
    },

    // ─── Filter Options (for dropdowns) ───
    filterOptions: {
        sites: {
            id: number;
            name: string;
        }[];
        departments: {
            id: number;
            name: string;
            site_id: number | null;
        }[];
    },

    // ─── KPI Cards (6 cards) ───
    kpis: {
        label: string;     // 'Total Insiden'
        value: number;     // 142
        tone: 'emerald' | 'sky' | 'amber' | 'indigo' | 'red' | 'rose';
    }[];

    // ─── Charts (5 charts) ───
    charts: {
        type: 'line' | 'bar' | 'donut';
        title: string;
        data: {
            label: string;
            value: number;
            color?: string;
        }[];
    }[];

    // ─── Table Widgets (3 widgets) ───
    widgets: {
        type: 'table';
        title: string;
        columns: {
            key: string;
            label: string;
        }[];
        rows: Record<string, string | number | null>[];
    }[];

    // ─── Quick Links (role-aware) ───
    quickLinks: {
        label: string;
        route: string;
        permission: string;
    }[];

    // ─── Notification Summary ───
    notificationSummary: {
        unread: number;
    },
}
```

### 3.2 `filters` Prop

```typescript
filters: {
    from: string;              // ISO date: '2026-07-01'
    to: string;                // ISO date: '2026-07-11'
    site_id: number | null;    // null = all sites
    department_id: number | null; // null = all departments
}
```

Reflects the current filter state. Defaults applied server-side if params are missing.

### 3.3 `filterOptions` Prop

```typescript
filterOptions: {
    sites: [
        { id: 1, name: 'Site Jakarta' },
        { id: 2, name: 'Site Surabaya' },
        { id: 3, name: 'Site Bandung' },
    ],
    departments: [
        { id: 1, name: 'Production', site_id: 1 },
        { id: 2, name: 'Maintenance', site_id: 1 },
        { id: 3, name: 'HSE', site_id: 2 },
    ],
}
```

Populated from `Site::where('is_active', true)->orderBy('name')->get(['id', 'name'])` and `Department::where('is_active', true)->orderBy('name')->get(['id', 'name', 'site_id'])`.

### 3.4 `kpis` Prop

```typescript
kpis: [
    { label: 'Total Insiden', value: 142, tone: 'indigo' },
    { label: 'Insiden Terbuka', value: 37, tone: 'amber' },
    { label: 'Insiden Selesai', value: 98, tone: 'emerald' },
    { label: 'Insiden Kritis', value: 5, tone: 'red' },
    { label: 'Insiden Ditolak', value: 7, tone: 'rose' },
    { label: 'Bulan Ini', value: 12, tone: 'sky' },
]
```

### 3.5 `charts` Prop

```typescript
charts: [
    {
        type: 'line',
        title: 'Tren Bulanan Insiden',
        data: [
            { label: '2025-08', value: 18 },
            { label: '2025-09', value: 24 },
            { label: '2025-10', value: 30 },
            { label: '2025-11', value: 28 },
            { label: '2025-12', value: 36 },
            { label: '2026-01', value: 32 },
            { label: '2026-02', value: 45 },
            { label: '2026-03', value: 40 },
            { label: '2026-04', value: 38 },
            { label: '2026-05', value: 42 },
            { label: '2026-06', value: 35 },
            { label: '2026-07', value: 12 },
        ],
    },
    {
        type: 'bar',
        title: 'Insiden per Kategori',
        data: [
            { label: 'Accident', value: 42 },
            { label: 'Near Miss', value: 31 },
            { label: 'Unsafe Act', value: 18 },
            { label: 'Unsafe Condition', value: 12 },
            { label: 'Incident', value: 8 },
            { label: 'Environmental Spill', value: 3 },
            { label: 'Security Breach', value: 2 },
        ],
    },
    {
        type: 'donut',
        title: 'Insiden per Severity',
        data: [
            { label: 'Low', value: 60, color: 'green' },
            { label: 'Medium', value: 40, color: 'yellow' },
            { label: 'High', value: 28, color: 'orange' },
            { label: 'Critical', value: 14, color: 'red' },
        ],
    },
    {
        type: 'bar',
        title: 'Insiden per Site',
        data: [
            { label: 'Jakarta', value: 45 },
            { label: 'Surabaya', value: 32 },
            { label: 'Bandung', value: 24 },
            { label: 'Medan', value: 18 },
            { label: 'Makassar', value: 10 },
            { label: 'Bali', value: 5 },
        ],
    },
    {
        type: 'donut',
        title: 'Status Insiden',
        data: [
            { label: 'Draft', value: 15 },
            { label: 'Submitted', value: 8 },
            { label: 'Under Review', value: 12 },
            { label: 'Closed', value: 98 },
            { label: 'Rejected', value: 7 },
        ],
    },
]
```

### 3.6 `widgets` Prop

```typescript
widgets: [
    {
        type: 'table',
        title: 'Recent Incidents',
        columns: [
            { key: 'incident_number', label: 'Nomor' },
            { key: 'title', label: 'Judul' },
            { key: 'category', label: 'Kategori' },
            { key: 'severity', label: 'Severity' },
            { key: 'site', label: 'Site' },
            { key: 'status', label: 'Status' },
            { key: 'created_at', label: 'Tanggal' },
        ],
        rows: [
            {
                id: 145,
                incident_number: 'INC-2026-0145',
                title: 'Tumpahan kimia di area produksi',
                category: 'environmental_spill',
                severity: 'High',
                site: 'Jakarta',
                status: 'under_review',
                created_at: '2026-07-10',
            },
            // ... up to 10 rows
        ],
    },
    {
        type: 'table',
        title: 'Critical Open Incidents',
        columns: [
            { key: 'incident_number', label: 'Nomor' },
            { key: 'title', label: 'Judul' },
            { key: 'site', label: 'Site' },
            { key: 'reporter', label: 'Reporter' },
            { key: 'days_open', label: 'Days Open' },
        ],
        rows: [
            {
                id: 132,
                incident_number: 'INC-2026-0132',
                title: 'Kebakaran di gudang penyimpanan',
                site: 'Jakarta',
                reporter: 'Budi Santoso',
                days_open: 5,
            },
            // ... up to 10 rows
        ],
    },
    {
        type: 'table',
        title: 'Aging Report',
        columns: [
            { key: 'incident_number', label: 'Nomor' },
            { key: 'title', label: 'Judul' },
            { key: 'status', label: 'Status' },
            { key: 'days_since_created', label: 'Days Since Created' },
        ],
        rows: [
            {
                id: 98,
                incident_number: 'INC-2026-0098',
                title: 'Peralatan tidak sesuai standar',
                status: 'under_review',
                days_since_created: 45,
            },
            // ... up to 10 rows
        ],
    },
]
```

### 3.7 `quickLinks` Prop

```typescript
quickLinks: [
    { label: 'Sites', route: 'core.sites.index', permission: 'core.sites.view' },
    { label: 'Departments', route: 'core.departments.index', permission: 'core.departments.view' },
    { label: 'Files', route: 'core.files.index', permission: 'core.files.view' },
    { label: 'Notifications', route: 'core.notifications.index', permission: 'core.notifications.view' },
    { label: 'Incident Reports', route: 'incident.reports.index', permission: 'incident.reports.view' },
]
```

### 3.8 `notificationSummary` Prop

```typescript
notificationSummary: {
    unread: 3,
}
```

---

## 4. Controller Implementation

### Full DashboardController (target implementation)

```php
<?php

namespace App\Http\Controllers;

use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Notifications\CoreNotification;
use App\Models\Core\Users\Employee;
use App\Models\Modules\Incident\IncidentReport;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->toDateString());
        $siteId = $request->integer('site_id') ?: null;
        $departmentId = $request->integer('department_id') ?: null;

        // Build scoped query
        $query = $this->scopedIncidentQuery($user, $from, $to, $siteId, $departmentId);

        return Inertia::render('Dashboard', [
            'filters' => [
                'from' => $from,
                'to' => $to,
                'site_id' => $siteId,
                'department_id' => $departmentId,
            ],
            'filterOptions' => [
                'sites' => Site::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'site_id']),
            ],
            'kpis' => $this->buildKpis($query),
            'charts' => $this->buildCharts($query),
            'widgets' => $this->buildWidgets($query),
            'quickLinks' => $this->buildQuickLinks(),
            'notificationSummary' => [
                'unread' => CoreNotification::query()
                    ->where('recipient_id', $user->id)
                    ->whereNull('read_at')
                    ->count(),
            ],
        ]);
    }

    private function scopedIncidentQuery(User $user, string $from, string $to, ?int $siteId, ?int $departmentId): \Illuminate\Database\Eloquent\Builder
    {
        return IncidentReport::query()
            ->whereBetween('occurred_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->when($user->hasRole('Employee / Reporter'), fn($q) => $q->where('reporter_id', $user->id))
            ->when($user->hasRole('Contractor'), function ($q) use ($user) {
                $companyId = $user->employee?->company_id;
                $employeeIds = Employee::where('company_id', $companyId)->pluck('id');
                $userIds = User::whereIn('employee_id', $employeeIds)->pluck('id');
                $q->whereIn('reporter_id', $userIds);
            })
            ->when($user->hasRole(['Supervisor', 'Department Head']), function ($q) use ($user) {
                $deptId = $user->employee?->department_id;
                $q->where('department_id', $deptId);
            })
            ->when($user->hasRole('QHSSE Officer'), function ($q) use ($user) {
                $siteId = $user->employee?->site_id;
                $q->where('site_id', $siteId);
            });
        // Super Admin, Admin, QHSSE Manager, Auditor, Top Management → no scope filter
    }

    private function buildKpis(\Illuminate\Database\Eloquent\Builder $baseQuery): array
    {
        return [
            ['label' => 'Total Insiden', 'value' => (clone $baseQuery)->count(), 'tone' => 'indigo'],
            ['label' => 'Insiden Terbuka', 'value' => (clone $baseQuery)->whereNotIn('status', ['closed', 'rejected'])->count(), 'tone' => 'amber'],
            ['label' => 'Insiden Selesai', 'value' => (clone $baseQuery)->where('status', 'closed')->count(), 'tone' => 'emerald'],
            ['label' => 'Insiden Kritis', 'value' => (clone $baseQuery)
                ->whereHas('severity', fn($q) => $q->where('code', 'CRITICAL'))
                ->whereNotIn('status', ['closed', 'rejected'])
                ->count(), 'tone' => 'red'],
            ['label' => 'Insiden Ditolak', 'value' => (clone $baseQuery)->where('status', 'rejected')->count(), 'tone' => 'rose'],
            ['label' => 'Bulan Ini', 'value' => (clone $baseQuery)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(), 'tone' => 'sky'],
        ];
    }

    private function buildCharts(\Illuminate\Database\Eloquent\Builder $baseQuery): array
    {
        return [
            [
                'type' => 'line',
                'title' => 'Tren Bulanan Insiden',
                'data' => (clone $baseQuery)
                    ->selectRaw("TO_CHAR(occurred_at, 'YYYY-MM') as month")
                    ->selectRaw('COUNT(*) as total')
                    ->where('occurred_at', '>=', now()->subMonths(12))
                    ->groupByRaw("TO_CHAR(occurred_at, 'YYYY-MM')")
                    ->orderBy('month')
                    ->get()
                    ->map(fn($r) => ['label' => $r->month, 'value' => (int) $r->total])
                    ->toArray(),
            ],
            [
                'type' => 'bar',
                'title' => 'Insiden per Kategori',
                'data' => (clone $baseQuery)
                    ->selectRaw('category')
                    ->selectRaw('COUNT(*) as total')
                    ->groupBy('category')
                    ->orderByDesc('total')
                    ->get()
                    ->map(fn($r) => ['label' => ucfirst(str_replace('_', ' ', $r->category)), 'value' => (int) $r->total])
                    ->toArray(),
            ],
            [
                'type' => 'donut',
                'title' => 'Insiden per Severity',
                'data' => (clone $baseQuery)
                    ->join('severities', 'incidents.severity_id', '=', 'severities.id')
                    ->selectRaw('severities.name as label, severities.color as color, COUNT(*) as total')
                    ->groupBy('severities.id', 'severities.name', 'severities.color')
                    ->orderBy('severities.level')
                    ->get()
                    ->map(fn($r) => ['label' => $r->label, 'value' => (int) $r->total, 'color' => $r->color])
                    ->toArray(),
            ],
            [
                'type' => 'bar',
                'title' => 'Insiden per Site',
                'data' => (clone $baseQuery)
                    ->join('sites', 'incidents.site_id', '=', 'sites.id')
                    ->selectRaw('sites.name as label, COUNT(*) as total')
                    ->groupBy('sites.id', 'sites.name')
                    ->orderByDesc('total')
                    ->limit(10)
                    ->get()
                    ->map(fn($r) => ['label' => $r->label, 'value' => (int) $r->total])
                    ->toArray(),
            ],
            [
                'type' => 'donut',
                'title' => 'Status Insiden',
                'data' => (clone $baseQuery)
                    ->selectRaw('status, COUNT(*) as total')
                    ->groupBy('status')
                    ->get()
                    ->map(fn($r) => ['label' => ucfirst(str_replace('_', ' ', $r->status)), 'value' => (int) $r->total])
                    ->toArray(),
            ],
        ];
    }

    private function buildWidgets(\Illuminate\Database\Eloquent\Builder $baseQuery): array
    {
        // Widget 1: Recent Incidents
        $recent = (clone $baseQuery)
            ->with(['site:id,name', 'severity:id,name,code,color', 'reporter:id,name'])
            ->select(['id', 'incident_number', 'title', 'category', 'site_id', 'severity_id', 'reporter_id', 'status', 'created_at'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($i) => [
                'id' => $i->id,
                'incident_number' => $i->incident_number,
                'title' => $i->title,
                'category' => ucfirst(str_replace('_', ' ', $i->category)),
                'severity' => $i->severity?->name ?? '-',
                'site' => $i->site?->name ?? '-',
                'status' => ucfirst(str_replace('_', ' ', $i->status)),
                'created_at' => $i->created_at?->format('Y-m-d'),
            ])
            ->toArray();

        // Widget 2: Critical Open
        $critical = (clone $baseQuery)
            ->whereHas('severity', fn($q) => $q->where('code', 'CRITICAL'))
            ->whereNotIn('status', ['closed', 'rejected'])
            ->with(['site:id,name', 'reporter:id,name'])
            ->select(['id', 'incident_number', 'title', 'site_id', 'reporter_id', 'status', 'created_at'])
            ->orderBy('created_at')
            ->limit(10)
            ->get()
            ->map(fn($i) => [
                'id' => $i->id,
                'incident_number' => $i->incident_number,
                'title' => $i->title,
                'site' => $i->site?->name ?? '-',
                'reporter' => $i->reporter?->name ?? '-',
                'days_open' => $i->created_at->diffInDays(now()),
            ])
            ->toArray();

        // Widget 3: Aging Report
        $aging = (clone $baseQuery)
            ->whereNotIn('status', ['closed', 'rejected'])
            ->select(['id', 'incident_number', 'title', 'status', 'created_at'])
            ->orderBy('created_at')
            ->limit(10)
            ->get()
            ->map(fn($i) => [
                'id' => $i->id,
                'incident_number' => $i->incident_number,
                'title' => $i->title,
                'status' => ucfirst(str_replace('_', ' ', $i->status)),
                'days_since_created' => $i->created_at->diffInDays(now()),
            ])
            ->toArray();

        return [
            [
                'type' => 'table',
                'title' => 'Recent Incidents',
                'columns' => [
                    ['key' => 'incident_number', 'label' => 'Nomor'],
                    ['key' => 'title', 'label' => 'Judul'],
                    ['key' => 'category', 'label' => 'Kategori'],
                    ['key' => 'severity', 'label' => 'Severity'],
                    ['key' => 'site', 'label' => 'Site'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'created_at', 'label' => 'Tanggal'],
                ],
                'rows' => $recent,
            ],
            [
                'type' => 'table',
                'title' => 'Critical Open Incidents',
                'columns' => [
                    ['key' => 'incident_number', 'label' => 'Nomor'],
                    ['key' => 'title', 'label' => 'Judul'],
                    ['key' => 'site', 'label' => 'Site'],
                    ['key' => 'reporter', 'label' => 'Reporter'],
                    ['key' => 'days_open', 'label' => 'Days Open'],
                ],
                'rows' => $critical,
            ],
            [
                'type' => 'table',
                'title' => 'Aging Report',
                'columns' => [
                    ['key' => 'incident_number', 'label' => 'Nomor'],
                    ['key' => 'title', 'label' => 'Judul'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'days_since_created', 'label' => 'Days Since Created'],
                ],
                'rows' => $aging,
            ],
        ];
    }

    private function buildQuickLinks(): array
    {
        return [
            ['label' => 'Sites', 'route' => 'core.sites.index', 'permission' => 'core.sites.view'],
            ['label' => 'Departments', 'route' => 'core.departments.index', 'permission' => 'core.departments.view'],
            ['label' => 'Files', 'route' => 'core.files.index', 'permission' => 'core.files.view'],
            ['label' => 'Notifications', 'route' => 'core.notifications.index', 'permission' => 'core.notifications.view'],
            ['label' => 'Incident Reports', 'route' => 'incident.reports.index', 'permission' => 'incident.reports.view'],
        ];
    }
}
```

---

## 5. Error Responses

| Status | When | Response |
|---|---|---|
| `302` | User not authenticated | Redirect to `/login` (handled by `auth` middleware) |
| `403` | User lacks `core.dashboard.view` permission | Redirect to dashboard with error flash OR `Abort(403)` (handled by `permission` middleware) |
| `419` | CSRF token expired | Laravel default CSRF error page |

> **Note:** No `422` validation errors — filter params are optional and have safe defaults. Invalid `site_id` or `department_id` values silently fall back to null (all sites/departments).

---

## 6. Permission Enforcement Points

| Layer | How |
|---|---|
| **Route middleware** | `permission:core.dashboard.view` on the route definition |
| **Auth middleware** | `auth` — redirect to login if not authenticated |
| **Verified middleware** | `verified` — block if email not verified |
| **Data scope** | Applied server-side in `scopedIncidentQuery()` based on user's role |
| **Quick links** | Filtered client-side via `permissions.has(item.permission)` in `Dashboard.tsx` |

### Permission Registration

Add to `CorePermissions::all()`:

```php
'core.dashboard.view',
```

Add to every role in `CorePermissions::roleMap()`:

```php
// All roles get dashboard view:
'Super Admin' => self::all(),
'Admin' => self::all(),
'QHSSE Manager' => [...$viewOnly, 'core.dashboard.view', 'core.scope.all'],
'QHSSE Officer' => [...$viewOnly, 'core.dashboard.view', 'core.scope.site'],
'Supervisor' => ['core.dashboard.view', 'core.companies.view', ...],
'Department Head' => ['core.dashboard.view', 'core.companies.view', ...],
'Employee / Reporter' => ['core.dashboard.view', 'core.scope.own'],
'Contractor' => ['core.dashboard.view', 'core.scope.company'],
'Auditor' => [...$viewOnly, 'core.dashboard.view', 'core.scope.all'],
'Top Management' => [...$viewOnly, 'core.dashboard.view', 'core.scope.all'],
```

---

## 7. Integration Points

### 7.1 Incident Reporting Module (Phase 1)

| Data | Source | Query |
|---|---|---|
| KPI counts | `incidents` table | Count with status/severity filters |
| Monthly trend chart | `incidents.occurred_at` | Group by `YYYY-MM` |
| By category chart | `incidents.category` | Group by category |
| By severity chart | `incidents` JOIN `severities` | Group by severity |
| By site chart | `incidents` JOIN `sites` | Group by site |
| By status chart | `incidents.status` | Group by status |
| Recent incidents widget | `incidents` | Latest 10, with relations |
| Critical open widget | `incidents` | Severity=CRITICAL, status open |
| Aging report widget | `incidents` | Status open, oldest 10 |

### 7.2 Future Module Integration Points

| Module | Data | When |
|---|---|---|
| `04-capa-action-tracking` | Overdue actions count + widget | When CAPA module is implemented |
| `03-investigation-rca` | Investigation status KPI | When Investigation module is implemented |
| `05-inspection-management` | Inspection compliance KPI | When Inspection module is implemented |
| `06-audit-management` | Audit findings KPI | When Audit module is implemented |

### 7.3 Notification Service Integration

Dashboard reads unread notification count:

```php
CoreNotification::query()
    ->where('recipient_id', $request->user()->id)
    ->whereNull('read_at')
    ->count();
```

No notification creation — dashboard is read-only.

---

## 8. No CSV Export

The dashboard does not support CSV export in Phase 1. No export endpoint exists.

---

## 9. No File Upload

The dashboard does not support file upload. No file endpoint exists.

---

## 10. No Workflow Integration

The dashboard does not interact with the WorkflowService. No workflow transitions.

---

## 11. Performance Notes

| Concern | Mitigation |
|---|---|
| Multiple count queries on large `incidents` table | All queries use indexed columns; consider combining into fewer queries |
| Chart group-by queries | Indexed on `occurred_at`, `category`, `status`, `site_id`, `severity_id` |
| Widget eager loading | `with(['site:id,name', 'severity:id,name', 'reporter:id,name'])` — selects only needed columns |
| Future: large dataset | Redis caching with 5-min TTL per user+filter combination |
| Clone overhead | Each KPI/chart query clones the base query builder — negligible overhead |
