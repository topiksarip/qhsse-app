# Module Spec — Dashboard & KPI

> **Module ID:** `01-dashboard-kpi`
> **Module Code:** `dashboard`
> **Phase:** Phase 1 (Dashboard & KPI)
> **Status:** Ready for coding
> **Route:** `GET /dashboard`
> **Controller:** `App\Http\Controllers\DashboardController` (already exists)
> **Page Component:** `resources/js/Pages/Dashboard.tsx` (already exists)

---

## 1. Tujuan Modul

Modul Dashboard & KPI adalah modul **read-only aggregation** yang menampilkan metrik dan visualisasi dari modul-modul QHSSE lainnya. Modul ini **tidak memiliki tabel data sendiri** — semua data di-aggregate dari tabel modul lain (IncidentReport, dan future modules seperti CAPA, Investigation, Inspection, Audit).

Tujuan utama:

- Memberikan **single-pane view** untuk seluruh aktivitas QHSSE: incident, CAPA, investigation, dll.
- Menampilkan **KPI cards** real-time: total incidents, open vs closed, critical, rejected, this month.
- Menyediakan **charts**: monthly trend (line), by category (bar), by severity (pie), by site (bar), by status (donut).
- Mendukung **filter**: date range, site, department — semua filter diterapkan server-side.
- Menampilkan **quick links** yang role-aware (hanya link yang user punya permission-nya).
- Menampilkan **notification summary** (unread count).
- Menyediakan **widgets** untuk future modules (overdue actions, aging report).

> **Penting:** Dashboard tidak memiliki CRUD. Tidak ada create, update, delete, submit, review, approve, reject, close, atau workflow. Dashboard hanya membaca dan meng-aggregate data dari modul lain.

---

## 2. Dependency

### Core Foundation (Phase 0 — COMPLETE)

| Dependency | Usage in This Module |
|---|---|
| **Auth** | User login, session, role identification |
| **PermissionService** (Spatie) | `core.dashboard.view` permission check |
| **MasterData** | Sites, Departments (for filter dropdowns) |
| **NotificationService** | Read unread count from `core_notifications` |
| **Inertia.js** | Server-side rendering to React component |

### Cross-Module Data Sources

| Module | Table | Data Used |
|---|---|---|
| `02-incident-reporting` | `incidents` | All incident KPIs and charts |
| `04-capa-action-tracking` (future) | `capa_actions` | Overdue actions widget (Phase 2+) |
| `03-investigation-rca` (future) | `investigations` | Investigation status widget (Phase 2+) |
| `05-inspection-management` (future) | `inspections` | Inspection compliance widget (Phase 2+) |
| `06-audit-management` (future) | `audit_findings` | Audit findings widget (Phase 2+) |

### Tech Stack

- Laravel 12 (backend: DashboardController, Eloquent aggregate queries)
- Inertia.js React + TypeScript (frontend)
- Tailwind CSS (styling, UI in Indonesian)
- PostgreSQL (aggregate queries via Eloquent)
- Spatie Laravel Permission (RBAC — `core.dashboard.view`)

---

## 3. User Roles

Semua role yang memiliki `core.dashboard.view` dapat mengakses dashboard:

| # | Role | Dashboard Access | Scope | KPIs Visible |
|---|---|---|---|---|
| 1 | **Super Admin** | ✅ | All | All data, all sites |
| 2 | **Admin** | ✅ | All | All data, all sites |
| 3 | **QHSSE Manager** | ✅ | All | All data, all sites |
| 4 | **QHSSE Officer** | ✅ | Site | Incidents in assigned site(s) |
| 5 | **Supervisor** | ✅ | Department | Incidents in their department |
| 6 | **Department Head** | ✅ | Department | Incidents in their department |
| 7 | **Employee/Reporter** | ✅ | Own | Own incidents only |
| 8 | **Contractor** | ✅ | Company | Incidents from their company |
| 9 | **Auditor** | ✅ | All | All data (read-only) |
| 10 | **Top Management** | ✅ | All | All data (read-only) |

> **Catatan:** Scope filtering diterapkan server-side di DashboardController. Dashboard menghormati scope user: `own`, `department`, `site`, `company`, `all`.

---

## 4. Fitur Lengkap

### 4.1 KPI Cards (6 cards)

| # | KPI Card | Label (ID) | Query Logic | Tone | Notes |
|---|---|---|---|---|---|
| 1 | **Total Incidents** | `Total Insiden` | `IncidentReport::count()` (scoped + date-filtered) | `indigo` | All incidents in scope and date range |
| 2 | **Open Incidents** | `Insiden Terbuka` | `IncidentReport::whereNotIn('status', ['closed', 'rejected'])->count()` | `amber` | Status: draft, submitted, under_review, investigation, action_open |
| 3 | **Closed Incidents** | `Insiden Selesai` | `IncidentReport::where('status', 'closed')->count()` | `emerald` | Terminal status |
| 4 | **Critical Incidents** | `Insiden Kritis` | `IncidentReport::whereHas('severity', fn($q) => $q->where('code', 'CRITICAL'))->whereNotIn('status', ['closed', 'rejected'])->count()` | `red` | Open + CRITICAL severity |
| 5 | **Rejected Incidents** | `Insiden Ditolak` | `IncidentReport::where('status', 'rejected')->count()` | `rose` | Terminal status |
| 6 | **This Month** | `Bulan Ini` | `IncidentReport::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count()` | `sky` | Created in current month |

### 4.2 Charts (5 charts)

| # | Chart | Title (ID) | Type | Data Source | X-Axis | Y-Axis |
|---|---|---|---|---|---|---|
| 1 | **Monthly Trend** | `Tren Bulanan Insiden` | Line chart | `IncidentReport::selectRaw("TO_CHAR(occurred_at, 'YYYY-MM') as month, COUNT(*) as total")->groupBy('month')->orderBy('month')` | Last 12 months | Incident count per month |
| 2 | **By Category** | `Insiden per Kategori` | Bar chart (horizontal) | `IncidentReport::selectRaw('category, COUNT(*) as total')->groupBy('category')->orderByDesc('total')` | Category (accident, near_miss, etc.) | Count |
| 3 | **By Severity** | `Insiden per Severity` | Donut/Pie | `IncidentReport::join('severities', 'incidents.severity_id', '=', 'severities.id')->selectRaw('severities.name, COUNT(*) as total')->groupBy('severities.id', 'severities.name')->orderBy('severities.level')` | Severity name | Count |
| 4 | **By Site** | `Insiden per Site` | Bar chart (horizontal) | `IncidentReport::join('sites', 'incidents.site_id', '=', 'sites.id')->selectRaw('sites.name, COUNT(*) as total')->groupBy('sites.id', 'sites.name')->orderByDesc('total')->limit(10)` | Site name (top 10) | Count |
| 5 | **By Status** | `Status Insiden` | Donut | `IncidentReport::selectRaw('status, COUNT(*) as total')->groupBy('status')` | Status (draft, submitted, under_review, etc.) | Count |

### 4.3 Table Widgets

| # | Widget | Columns | Filter | Limit |
|---|---|---|---|---|
| 1 | **Recent Incidents** | Number, Title, Category, Severity, Site, Status, Created At | Scoped, date-filtered | 10 (latest) |
| 2 | **Critical Open** | Number, Title, Site, Reporter, Created At, Days Open | Severity=CRITICAL, status open | 10 |
| 3 | **Aging Report** | Number, Title, Status, Created At, Days Since Created | Status open, sorted oldest first | 10 |

### 4.4 Filter Controls

| # | Filter | Param | Type | Default | Options |
|---|---|---|---|---|---|
| 1 | **Date From** | `from` | Date input | First day of current month | — |
| 2 | **Date To** | `to` | Date input | Today | — |
| 3 | **Site** | `site_id` | Select dropdown | All Sites | All active sites |
| 4 | **Department** | `department_id` | Select dropdown (cascading from site) | All Departments | Active departments (filtered by site if site selected) |

### 4.5 Quick Links (role-aware)

Quick links are rendered only if the user has the required permission:

| # | Label | Route | Permission |
|---|---|---|---|
| 1 | Sites | `core.sites.index` | `core.sites.view` |
| 2 | Departments | `core.departments.index` | `core.departments.view` |
| 3 | Files | `core.files.index` | `core.files.view` |
| 4 | Notifications | `core.notifications.index` | `core.notifications.view` |
| 5 | Incident Reports | `incident.reports.index` | `incident.reports.view` |

### 4.6 Notification Summary

Displays unread notification count for the authenticated user:

```php
'notificationSummary' => [
    'unread' => CoreNotification::query()
        ->where('recipient_id', $request->user()->id)
        ->whereNull('read_at')
        ->count(),
],
```

---

## 5. Business Rules

### BR-01: Dashboard is Read-Only

- Dashboard **never writes** to any table.
- No create, update, delete, submit, review, approve, reject, close actions.
- All queries are SELECT-only aggregates.

### BR-02: Data Scope by Role

- Dashboard data respects the user's data scope:
  - `own` → only incidents where `reporter_id = auth user id`
  - `department` → only incidents where `department_id = user's department`
  - `site` → only incidents where `site_id = user's site`
  - `company` → only incidents where `reporter_id IN (users in same company)`
  - `all` → no scope filter
- Scope is applied **server-side** in DashboardController before aggregation.
- Frontend receives pre-scoped data — no client-side filtering.

### BR-03: Filter Application

- Date range filter applies to `incidents.occurred_at` (not `created_at`).
- Site filter applies to `incidents.site_id`.
- Department filter applies to `incidents.department_id`.
- If site is selected, department dropdown is filtered to only departments in that site.
- Filters are applied to ALL KPIs, charts, and widgets simultaneously.
- Default date range: first day of current month → today.

### BR-04: Authentication Required

- User must be authenticated (`auth` middleware).
- User must be email-verified (`verified` middleware).
- Unauthenticated users are redirected to login page.
- User must have `core.dashboard.view` permission.

### BR-05: No Audit Trail

- Dashboard reads do not create audit trail entries (read-only, high-frequency).
- Dashboard views are not logged in `activity_logs` or `audit_logs`.

### BR-06: No File Attachments

- Dashboard has no file upload, download, or attachment capability.
- No `managed_files` entries with `module_name = 'dashboard'`.

### BR-07: No Comments

- Dashboard has no comment capability.
- No `comments` entries with `module_name = 'dashboard'`.

---

## 6. Permission Keys

Dashboard uses a single permission key:

| # | Permission Key | Description | Registered In |
|---|---|---|---|
| 1 | `core.dashboard.view` | View the QHSSE dashboard | `CorePermissions::all()` |

### Implementation Notes

- `core.dashboard.view` must be added to `CorePermissions::all()` array.
- Must be assigned to ALL roles in `CorePermissions::roleMap()` since every authenticated user should see their scoped dashboard.
- Route middleware: `['auth', 'verified', 'permission:core.dashboard.view']`.
- The existing route in `routes/web.php` currently uses `['auth', 'verified']` — add `permission:core.dashboard.view` middleware.

---

## 7. Role-Permission Matrix

| Role | `core.dashboard.view` | Scope |
|---|:---:|---|
| Super Admin | ✅ | All |
| Admin | ✅ | All |
| QHSSE Manager | ✅ | All |
| QHSSE Officer | ✅ | Site |
| Supervisor | ✅ | Department |
| Department Head | ✅ | Department |
| Employee/Reporter | ✅ | Own |
| Contractor | ✅ | Company |
| Auditor | ✅ | All |
| Top Management | ✅ | All |

---

## 8. Notification Events

**None.** Dashboard is read-only and does not generate notifications.

The dashboard **reads** notification data (unread count) but never **creates** notifications.

---

## 9. File Attachment Rules

**None.** Dashboard has no file attachment capability.

---

## 10. Dashboard Metrics

This section IS the module — see Section 4 (Fitur Lengkap) for all KPI cards, charts, and widgets.

### Aggregation Query Patterns

All dashboard queries follow this pattern:

```php
// Base scoped query
$query = IncidentReport::query()
    ->when($siteId, fn($q) => $q->where('site_id', $siteId))
    ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
    ->whereBetween('occurred_at', [$from, $to . ' 23:59:59'])
    ->when($scopeFilter, fn($q) => $q->...($scopeFilter));

// KPI counts
$totalIncidents = $query->count();
$openIncidents = (clone $query)->whereNotIn('status', ['closed', 'rejected'])->count();
$closedIncidents = (clone $query)->where('status', 'closed')->count();
```

### Performance Considerations

- All aggregate queries use indexed columns (`site_id`, `department_id`, `status`, `occurred_at`, `severity_id`).
- Consider Redis caching for dashboard data with 5-minute TTL (future optimization).
- Chart data queries use `groupBy` with raw expressions for efficiency.
- Table widgets limited to 10 rows to prevent large payloads.

---

## 11. Export Spec

**Not applicable in Phase 1.** Dashboard does not support CSV/Excel export.

Future: Dashboard PDF export for management reports (Phase 2+).

---

## 12. Acceptance Criteria

1. Authenticated user with `core.dashboard.view` can access `GET /dashboard` and see the dashboard page (HTTP 200).
2. Unauthenticated user is redirected to login page (HTTP 302).
3. User without `core.dashboard.view` permission gets HTTP 403 Forbidden.
4. KPI cards display correct counts matching the `incidents` table (scoped + filtered).
5. Charts render with correct aggregate data (monthly trend, by category, by severity, by site, by status).
6. Date range filter narrows all KPIs and charts to incidents within the selected date range.
7. Site filter narrows all KPIs and charts to incidents at the selected site.
8. Department filter narrows all KPIs and charts (and cascades from site filter).
9. Data scope is enforced: Employee sees only own incidents, Supervisor sees department, QHSSE Officer sees site, etc.
10. Quick links only render for permissions the user has.
11. Notification summary shows correct unread count for the authenticated user.
12. Dashboard page renders in under 2 seconds with 10,000 incidents in database.

---

## 13. Open Questions

1. **Redis caching:** Should dashboard queries be cached with a short TTL (e.g., 5 minutes) for performance? Decision: Defer to Phase 2 optimization.
2. **Dashboard variants:** Should there be role-specific dashboard layouts (Executive, QHSSE, Supervisor) or a single adaptive dashboard? Decision: Single adaptive dashboard with scope-based data filtering in Phase 1.
3. **Overdue actions widget:** When CAPA module (Phase 2) is implemented, add an "Overdue Actions" KPI card and widget. Placeholder reserved.
4. **Real-time updates:** Should dashboard auto-refresh via WebSocket/polling? Decision: No — manual page refresh in Phase 1. Consider Livewire/polling in Phase 2.
5. **Export:** Should dashboard data be exportable as PDF/Excel for management reports? Decision: Defer to Phase 2.
6. **Custom widgets:** Should users be able to customize which widgets appear? Decision: No in Phase 1. Fixed layout.
7. **Drill-down:** Should clicking a KPI card navigate to the filtered incident list (e.g., clicking "Open Incidents" goes to incident list filtered by open status)? Decision: Yes, implement in Phase 1 if straightforward.
