# Data Model — Dashboard & KPI

> **Module ID:** `01-dashboard-kpi`
> **Nature:** Read-only aggregation module — NO tables, NO migrations, NO models.
>
> This document defines the **aggregation queries** and **data shapes** the DashboardController
> produces at runtime. All data comes from other modules' tables (primarily `incidents` in Phase 1).

---

## 1. No Tables — Aggregation Only

The Dashboard module is fundamentally different from other QHSSE modules:

| Property | Other Modules | Dashboard Module |
|---|---|---|
| Owns tables | Yes (e.g., `incidents`) | **No** |
| Has migrations | Yes | **No** |
| Has Eloquent models | Yes | **No** (reads from other modules' models) |
| Has factory | Yes | **No** |
| Has seeder | Yes | **No** |
| Writes data | Yes (CRUD) | **No** (read-only SELECT) |
| Has workflow | Yes | **No** |

---

## 2. Source Tables (Read From)

### 2.1 Phase 1 — Incident Reporting

The dashboard reads from these tables owned by the Incident Reporting module:

| Table | Owner Module | Columns Used by Dashboard |
|---|---|---|
| `incidents` | `02-incident-reporting` | `id`, `incident_number`, `title`, `category`, `occurred_at`, `site_id`, `area_id`, `department_id`, `reporter_id`, `severity_id`, `priority_id`, `status`, `created_at` |
| `sites` | Core Master Data | `id`, `code`, `name`, `is_active` |
| `departments` | Core Master Data | `id`, `code`, `name`, `site_id`, `is_active` |
| `severities` | Core Master Data | `id`, `code`, `name`, `level`, `color` |
| `priorities` | Core Master Data | `id`, `code`, `name`, `level`, `color` |
| `users` | Core Users | `id`, `name`, `email` |
| `employees` | Core Users | `id`, `name`, `company_id`, `site_id`, `department_id` |

### 2.2 Future Modules (Phase 2+)

| Table | Owner Module | Dashboard Use |
|---|---|---|
| `capa_actions` | `04-capa-action-tracking` | Overdue actions KPI + widget |
| `investigations` | `03-investigation-rca` | Investigation status widget |
| `inspections` | `05-inspection-management` | Inspection compliance KPI |
| `audit_findings` | `06-audit-management` | Audit findings widget |
| `documents` | `08-document-control` | Document expiry widget |

---

## 3. Aggregation Query Definitions

All queries below are scoped by:
1. **User scope** (own / department / site / company / all)
2. **Date range** (`occurred_at` between `from` and `to`)
3. **Site filter** (`site_id` if provided)
4. **Department filter** (`department_id` if provided)

### 3.1 Base Scope Builder

```php
/**
 * Build a base incident query with scope + filter applied.
 * All KPI/chart queries clone this base.
 */
private function scopedIncidentQuery(Request $request): Builder
{
    $user = $request->user();
    $from = $request->query('from', now()->startOfMonth()->toDateString());
    $to = $request->query('to', now()->toDateString());
    $siteId = $request->integer('site_id') ?: null;
    $departmentId = $request->integer('department_id') ?: null;

    return IncidentReport::query()
        // Date range filter (on occurred_at, not created_at)
        ->whereBetween('occurred_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
        // Site filter
        ->when($siteId, fn($q) => $q->where('site_id', $siteId))
        // Department filter
        ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
        // Role-based scope
        ->when($user->hasRole('Employee / Reporter'), fn($q) => $q->where('reporter_id', $user->id))
        ->when($user->hasRole('Contractor'), function ($q) use ($user) {
            $companyIds = \App\Models\Core\Users\Employee::where('company_id', $user->employee?->company_id)
                ->pluck('id');
            $userIds = User::whereIn('employee_id', $companyIds)->pluck('id');
            $q->whereIn('reporter_id', $userIds);
        })
        ->when($user->hasRole('Supervisor') || $user->hasRole('Department Head'), function ($q) use ($user) {
            $departmentId = $user->employee?->department_id;
            $q->where('department_id', $departmentId);
        })
        ->when($user->hasRole('QHSSE Officer'), function ($q) use ($user) {
            $siteId = $user->employee?->site_id;
            $q->where('site_id', $siteId);
        });
    // Super Admin, Admin, QHSSE Manager, Auditor, Top Management → no scope filter (all)
}
```

### 3.2 KPI Card Queries

```php
// 1. Total Incidents
$totalIncidents = (clone $query)->count();

// 2. Open Incidents (not closed, not rejected)
$openIncidents = (clone $query)
    ->whereNotIn('status', ['closed', 'rejected'])
    ->count();

// 3. Closed Incidents
$closedIncidents = (clone $query)
    ->where('status', 'closed')
    ->count();

// 4. Critical Incidents (open + CRITICAL severity)
$criticalIncidents = (clone $query)
    ->whereHas('severity', fn($q) => $q->where('code', 'CRITICAL'))
    ->whereNotIn('status', ['closed', 'rejected'])
    ->count();

// 5. Rejected Incidents
$rejectedIncidents = (clone $query)
    ->where('status', 'rejected')
    ->count();

// 6. This Month
$thisMonthIncidents = (clone $query)
    ->whereMonth('created_at', now()->month)
    ->whereYear('created_at', now()->year)
    ->count();
```

### 3.3 Chart Queries

```php
// Chart 1: Monthly Trend (last 12 months) — LINE CHART
$monthlyTrend = (clone $query)
    ->selectRaw("TO_CHAR(occurred_at, 'YYYY-MM') as month")
    ->selectRaw("COUNT(*) as total")
    ->where('occurred_at', '>=', now()->subMonths(12))
    ->groupByRaw("TO_CHAR(occurred_at, 'YYYY-MM')")
    ->orderBy('month')
    ->get()
    ->map(fn($row) => ['label' => $row->month, 'value' => (int) $row->total])
    ->toArray();

// Chart 2: By Category — BAR CHART (horizontal)
$byCategory = (clone $query)
    ->selectRaw('category')
    ->selectRaw('COUNT(*) as total')
    ->groupBy('category')
    ->orderByDesc('total')
    ->get()
    ->map(fn($row) => ['label' => ucfirst(str_replace('_', ' ', $row->category)), 'value' => (int) $row->total])
    ->toArray();

// Chart 3: By Severity — DONUT/PIE
$bySeverity = (clone $query)
    ->join('severities', 'incidents.severity_id', '=', 'severities.id')
    ->selectRaw('severities.name as label')
    ->selectRaw('severities.color as color')
    ->selectRaw('COUNT(*) as total')
    ->groupBy('severities.id', 'severities.name', 'severities.color')
    ->orderBy('severities.level')
    ->get()
    ->map(fn($row) => ['label' => $row->label, 'value' => (int) $row->total, 'color' => $row->color])
    ->toArray();

// Chart 4: By Site (top 10) — BAR CHART (horizontal)
$bySite = (clone $query)
    ->join('sites', 'incidents.site_id', '=', 'sites.id')
    ->selectRaw('sites.name as label')
    ->selectRaw('COUNT(*) as total')
    ->groupBy('sites.id', 'sites.name')
    ->orderByDesc('total')
    ->limit(10)
    ->get()
    ->map(fn($row) => ['label' => $row->label, 'value' => (int) $row->total])
    ->toArray();

// Chart 5: By Status — DONUT
$byStatus = (clone $query)
    ->selectRaw('status')
    ->selectRaw('COUNT(*) as total')
    ->groupBy('status')
    ->get()
    ->map(fn($row) => [
        'label' => ucfirst(str_replace('_', ' ', $row->status)),
        'value' => (int) $row->total,
    ])
    ->toArray();
```

### 3.4 Table Widget Queries

```php
// Widget 1: Recent Incidents (last 10)
$recentIncidents = (clone $query)
    ->with(['site:id,name', 'severity:id,name,code,color', 'reporter:id,name'])
    ->select(['id', 'incident_number', 'title', 'category', 'site_id', 'severity_id', 'reporter_id', 'status', 'created_at'])
    ->orderByDesc('created_at')
    ->limit(10)
    ->get();

// Widget 2: Critical Open (max 10)
$criticalOpen = (clone $query)
    ->whereHas('severity', fn($q) => $q->where('code', 'CRITICAL'))
    ->whereNotIn('status', ['closed', 'rejected'])
    ->with(['site:id,name', 'reporter:id,name'])
    ->select(['id', 'incident_number', 'title', 'site_id', 'reporter_id', 'status', 'created_at'])
    ->orderBy('created_at') // oldest first
    ->limit(10)
    ->get()
    ->each(fn($incident) => $incident->days_open = $incident->created_at->diffInDays(now()));

// Widget 3: Aging Report (open incidents, sorted oldest first, max 10)
$agingReport = (clone $query)
    ->whereNotIn('status', ['closed', 'rejected'])
    ->select(['id', 'incident_number', 'title', 'status', 'created_at'])
    ->orderBy('created_at') // oldest first
    ->limit(10)
    ->get()
    ->each(fn($incident) => $incident->days_since_created = $incident->created_at->diffInDays(now()));
```

---

## 4. Inertia Response Data Shape

The DashboardController returns this shape to `Dashboard.tsx`:

```typescript
{
    filters: {
        from: string;              // '2026-07-01'
        to: string;                // '2026-07-11'
        site_id: number | null;    // null = all sites
        department_id: number | null;
    },
    filterOptions: {
        sites: { id: number; name: string }[];
        departments: { id: number; name: string; site_id: number | null }[];
    },
    kpis: {
        label: string;     // 'Total Insiden'
        value: number;     // 42
        tone: 'emerald' | 'sky' | 'amber' | 'indigo' | 'red' | 'rose';
    }[],
    charts: {
        type: 'line' | 'bar' | 'pie' | 'donut';
        title: string;
        data: { label: string; value: number; color?: string }[];
    }[],
    widgets: {
        type: 'table';
        title: string;
        columns: { key: string; label: string }[];
        rows: Record<string, any>[];
    }[],
    quickLinks: {
        label: string;
        route: string;
        permission: string;
    }[],
    notificationSummary: {
        unread: number;
    },
}
```

---

## 5. ERD (Read Relationships)

The dashboard has no own entities but reads from:

```
┌─────────────────────────────────────────────────────────────────┐
│                     DASHBOARD (read-only)                       │
│                  DashboardController::__invoke()                 │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                    SELECT (aggregate)
                            │
         ┌──────────────────┼──────────────────┐
         │                  │                  │
         ▼                  ▼                  ▼
┌─────────────────┐ ┌───────────────┐  ┌───────────────┐
│    incidents    │ │     sites     │  │  departments  │
│  (02-incident)  │ │ (Core Master) │  │ (Core Master) │
├─────────────────┤ ├───────────────┤  ├───────────────┤
│ id              │ │ id            │  │ id            │
│ incident_number │ │ code          │  │ code          │
│ title           │ │ name          │  │ name          │
│ category        │ │ is_active     │  │ site_id  FK   │
│ occurred_at     │ └───────┬───────┘  │ is_active     │
│ site_id    FK   │         │          └───────┬───────┘
│ area_id    FK   │◄────────┘                  │
│ department_id FK│◄───────────────────────────┘
│ reporter_id FK  │
│ severity_id FK  │──► severities (Core Master)
│ priority_id FK  │──► priorities (Core Master)
│ status          │
│ created_at      │
└─────────────────┘

         ┌──────────────────────────────────┐
         │         users (Core Auth)         │
         ├──────────────────────────────────┤
         │ id, name, email, is_active        │
         │ company_id FK, employee_id FK     │
         └──────────────────────────────────┘

         ┌──────────────────────────────────┐
         │  core_notifications (Core Notif)  │
         ├──────────────────────────────────┤
         │ id, recipient_id FK, type        │
         │ read_at (nullable)               │
         │ ← dashboard reads unread count   │
         └──────────────────────────────────┘
```

---

## 6. Indexes Used by Dashboard Queries

The dashboard relies on indexes defined by the Incident Reporting module. No additional indexes needed:

| Index | Table | Columns | Used By |
|---|---|---|---|
| `incidents_site_id_index` | `incidents` | `site_id` | Site filter |
| `incidents_department_id_index` | `incidents` | `department_id` | Department filter |
| `incidents_status_index` | `incidents` | `status` | KPI: open/closed/rejected |
| `incidents_severity_id_index` | `incidents` | `severity_id` | KPI: critical |
| `incidents_occurred_at_index` | `incidents` | `occurred_at` | Date range filter |
| `incidents_category_index` | `incidents` | `category` | Chart: by category |
| `incidents_created_at_index` | `incidents` | `created_at` | KPI: this month |
| `incidents_reporter_id_index` | `incidents` | `reporter_id` | Scope: own |

---

## 7. Migration File Naming Convention

**None.** Dashboard module has no migrations.

---

## 8. Future Data Sources (Phase 2+)

When future modules are implemented, add their data sources to the dashboard:

### 8.1 CAPA Actions (Module 04)

```php
// KPI: Overdue Actions
$overdueActions = CapaAction::query()
    ->where('status', '!=', 'closed')
    ->where('due_date', '<', now())
    ->count();

// Widget: Overdue Actions Table
$overdueActionsWidget = CapaAction::query()
    ->where('status', '!=', 'closed')
    ->where('due_date', '<', now())
    ->with(['incident', 'assignee'])
    ->orderBy('due_date')
    ->limit(10)
    ->get();
```

### 8.2 Investigations (Module 03)

```php
// KPI: Open Investigations
$openInvestigations = Investigation::query()
    ->whereNotIn('status', ['closed', 'cancelled'])
    ->count();
```

### 8.3 Inspections (Module 05)

```php
// KPI: Inspection Compliance Rate
$totalInspections = Inspection::count();
$completedInspections = Inspection::where('status', 'completed')->count();
$complianceRate = $totalInspections > 0
    ? round(($completedInspections / $totalInspections) * 100, 1)
    : 0;
```

### 8.4 Audit Findings (Module 06)

```php
// KPI: Open Audit Findings
$openFindings = AuditFinding::query()
    ->whereNotIn('status', ['closed', 'resolved'])
    ->count();
```

---

## 9. Caching Strategy (Future)

For production performance with large datasets:

```php
// Cache dashboard data per user + filter combination, 5-minute TTL
$cacheKey = "dashboard:{$user->id}:{$from}:{$to}:{$siteId}:{$departmentId}";
return Cache::remember($cacheKey, now()->addMinutes(5), function () use (...) {
    // Run all aggregation queries
    return [...];
});
```

**Phase 1 decision:** No caching — direct queries. Revisit when incident count exceeds 10,000.
