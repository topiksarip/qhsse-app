# UI Pages — Dashboard & KPI

> **Module ID:** `01-dashboard-kpi`
> **Page Component:** `resources/js/Pages/Dashboard.tsx`
> **Layout:** `AuthenticatedLayout`
> **Route:** `GET /dashboard`

---

## 1. Dashboard Page Wireframe

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  [AuthenticatedLayout — Sidebar | Top Bar]                                          │
│                                                                                      │
│  ┌─── Header ───────────────────────────────────────────────────────────────────┐   │
│  │  QHSSE Dashboard                                                  3 unread      │   │
│  │  notifications                                                                │   │
│  └──────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                      │
│  ┌─── Hero / Filter Section (dark gradient bg) ────────────────────────────────┐   │
│  │                                                                              │   │
│  │   PHASE 1 DASHBOARD                                                          │   │
│  │   QHSSE Operations Dashboard                                                  │   │
│  │   Ringkasan metrik QHSSE real-time dari seluruh modul.                       │   │
│  │                                                                              │   │
│  │   ┌─── Filter Form (glass card) ──────────────────────┐                      │   │
│  │   │                                                    │                      │   │
│  │   │  From           To                                 │                      │   │
│  │   │  [2026-07-01]   [2026-07-11]                       │                      │   │
│  │   │                                                    │                      │   │
│  │   │  Site                  Department                  │                      │   │
│  │   │  [All Sites ▾]        [All Departments ▾]         │                      │   │
│  │   │                                                    │                      │   │
│  │   │           [ APPLY FILTERS ]                         │                      │   │
│  │   └────────────────────────────────────────────────────┘                      │   │
│  └──────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                      │
│  ┌─── KPI Cards Section (grid: 4 cols on xl, 2 on md, 1 on mobile) ────────────┐   │
│  │                                                                              │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐   │   │
│  │  │ TOTAL        │  │ INSIDEN      │  │ INSIDEN      │  │ INSIDEN      │   │   │
│  │  │ INSIDEN      │  │ TERBUKA      │  │ SELESAI      │  │ KRITIS       │   │   │
│  │  │              │  │              │  │              │  │              │   │   │
│  │  │    142       │  │     37       │  │     98       │  │      5       │   │   │
│  │  │              │  │              │  │              │  │              │   │   │
│  │  │ ▰▰▰▱▱▱       │  │ ▰▰▰▱▱▱       │  │ ▰▰▰▱▱▱       │  │ ▰▰▰▱▱▱       │   │   │
│  │  │ (indigo)     │  │ (amber)      │  │ (emerald)    │  │ (red)        │   │   │
│  │  └──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘   │   │
│  │                                                                              │   │
│  │  ┌──────────────┐  ┌──────────────┐                                       │   │
│  │  │ INSIDEN      │  │ BULAN INI    │                                       │   │
│  │  │ DITOLAK      │  │              │                                       │   │
│  │  │              │  │              │                                       │   │
│  │  │     7        │  │     12       │                                       │   │
│  │  │              │  │              │                                       │   │
│  │  │ ▰▰▰▱▱▱       │  │ ▰▰▰▱▱▱       │                                       │   │
│  │  │ (rose)       │  │ (sky)        │                                       │   │
│  │  └──────────────┘  └──────────────┘                                       │   │
│  └──────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                      │
│  ┌─── Charts Section (grid: 2 cols on xl, 1 on mobile) ───────────────────────┐   │
│  │                                                                              │   │
│  │  ┌─── Monthly Trend (LINE) ──────────────────┐  ┌─── By Category (BAR) ───┐  │   │
│  │  │ Tren Bulanan Insiden                       │  │ Insiden per Kategori    │  │   │
│  │  │                                            │  │                         │  │   │
│  │  │  45│       ●                               │  │ Accident      ████████ 42│  │   │
│  │  │  36│   ●   │                               │  │ Near Miss     ██████ 31  │  │   │
│  │  │  27│  │    │      ●                        │  │ Unsafe Act    ████ 18    │  │   │
│  │  │  18│  │    │   │  │                        │  │ Unsafe Cond   ███ 12     │  │   │
│  │  │   9│  │    │   │  │  ●                     │  │ Incident      ██ 8       │  │   │
│  │  │   0└──────────────────────                 │  │ Env Spill     █ 3        │  │   │
│  │  │      Jul  Aug  Sep  Oct  Nov  Dec  Jan     │  │ Sec Breach    █ 2        │  │   │
│  │  │                                            │  │                         │  │   │
│  │  └────────────────────────────────────────────┘  └─────────────────────────┘  │   │
│  │                                                                              │   │
│  │  ┌─── By Severity (DONUT) ───────────────────┐  ┌─── By Site (BAR) ───────┐  │   │
│  │  │ Insiden per Severity                      │  │ Insiden per Site        │  │   │
│  │  │                                            │  │                         │  │   │
│  │  │         ╭───╮                             │  │ Jakarta     █████████ 45 │  │   │
│  │  │        ╱     ╲                            │  │ Surabaya    ███████ 32   │  │   │
│  │  │       │  42%  │ Low (green)               │  │ Bandung     █████ 24     │  │   │
│  │  │        ╲     ╱                            │  │ Medan       ████ 18      │  │   │
│  │  │         ╰───╯                             │  │ Makassar    ███ 10        │  │   │
│  │  │  Medium 28%  High 20%  Critical 10%      │  │ Bali        ██ 5          │  │   │
│  │  │                                            │  │                         │  │   │
│  │  └────────────────────────────────────────────┘  └─────────────────────────┘  │   │
│  │                                                                              │   │
│  │  ┌─── By Status (DONUT) ─────────────────────┐                               │   │
│  │  │ Status Insiden                            │                               │   │
│  │  │                                            │                               │   │
│  │  │         ╭───╮                             │                               │   │
│  │  │        ╱     ╲                            │                               │   │
│  │  │       │       │                           │                               │   │
│  │  │        ╲     ╱                            │                               │   │
│  │  │         ╰───╯                             │                               │   │
│  │  │  Draft 15  Submitted 8  Under Review 12  │                               │   │
│  │  │  Closed 98  Rejected 7  Cancelled 2      │                               │   │
│  │  └────────────────────────────────────────────┘                               │   │
│  └──────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                      │
│  ┌─── Table Widgets Section (grid: 3 cols on xl, 1 on mobile) ────────────────┐   │
│  │                                                                              │   │
│  │  ┌─── Recent Incidents ──────┐  ┌─── Critical Open ────────┐  ┌─── Aging ───┐│   │
│  │  │ Recent Incidents          │  │ Critical Open Incidents   │  │ Aging Report││   │
│  │  │                           │  │                          │  │             ││   │
│  │  │ #  Title    Cat  Sev  St │  │ #  Title  Site  Days Open│  │ #  Title   ││   │
│  │  │ ─────────────────────────│  │ ────────────────────────│  │ ──────────  ││   │
│  │  │ INC Spill.. ENV HIGH OPN │  │ INC Fire  JKT   5 days  │  │ INC Old..   ││   │
│  │  │ INC Fall.. ACC CRIT OPN │  │ INC Gas   SBY   3 days  │  │ 45 days     ││   │
│  │  │ INC Leak.. ENV MED  CLSD │  │ INC Chem  BDO   2 days  │  │ INC Stuck.. ││   │
│  │  │ INC PPE... UAC LOW  SUBM │  │                          │  │ 30 days     ││   │
│  │  │ INC Tool.. UAC MED  RVWD │  │                          │  │             ││   │
│  │  │                           │  │                          │  │             ││   │
│  │  └───────────────────────────┘  └──────────────────────────┘  └─────────────┘│   │
│  └──────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                      │
│  ┌─── Quick Links Section ─────────────────────────────────────────────────────┐   │
│  │  Role-aware Quick Access                                                    │   │
│  │  Only links allowed by permissions are rendered.                            │   │
│  │                                                                              │   │
│  │  [ Sites ]  [ Departments ]  [ Files ]  [ Notifications ]  [ Incidents ]  │   │
│  └──────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                      │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Component List

| # | Component | File Path | Props | Notes |
|---|---|---|---|---|
| 1 | **Dashboard** (page) | `resources/js/Pages/Dashboard.tsx` | `filters`, `filterOptions`, `kpis`, `charts`, `widgets`, `quickLinks`, `notificationSummary` | Main page, already exists |
| 2 | **KpiCard** | `resources/js/Components/Dashboard/KpiCard.tsx` | `label: string`, `value: number\|string`, `tone?: 'emerald'\|'sky'\|'amber'\|'indigo'\|'red'\|'rose'` | Already exists — add `red` and `rose` tones |
| 3 | **ChartPlaceholder** | `resources/js/Components/Dashboard/ChartPlaceholder.tsx` | `title: string`, `description: string`, `points: number[]` | Already exists — to be replaced with real chart component |
| 4 | **LineChart** (new) | `resources/js/Components/Dashboard/LineChart.tsx` | `title: string`, `data: {label: string, value: number}[]` | Replace placeholder with actual line chart (recharts or chart.js) |
| 5 | **BarChart** (new) | `resources/js/Components/Dashboard/BarChart.tsx` | `title: string`, `data: {label: string, value: number}[]`, `orientation?: 'horizontal'\|'vertical'` | Bar chart for category & site breakdown |
| 6 | **DonutChart** (new) | `resources/js/Components/Dashboard/DonutChart.tsx` | `title: string`, `data: {label: string, value: number, color?: string}[]` | Donut for severity & status |
| 7 | **DashboardTable** (new) | `resources/js/Components/Dashboard/DashboardTable.tsx` | `title: string`, `columns: {key: string, label: string}[]`, `rows: Record<string, any>[]` | Table widget for recent/critical/aging |
| 8 | **FilterBar** (new) | `resources/js/Components/Dashboard/FilterBar.tsx` | `filters`, `filterOptions` | Extract filter form into reusable component |

---

## 3. Filter Controls

### 3.1 Filter Form (inside Hero section)

```
┌─────────────────────────────────────────────────────┐
│  Filter Form                                        │
│                                                     │
│  ┌─────────────┐  ┌─────────────┐                  │
│  │ From        │  │ To          │                  │
│  │ [date input]│  │ [date input]│                  │
│  └─────────────┘  └─────────────┘                  │
│                                                     │
│  ┌─────────────┐  ┌─────────────┐                  │
│  │ Site        │  │ Department  │                  │
│  │ [select ▾]  │  │ [select ▾]  │                  │
│  └─────────────┘  └─────────────┘                  │
│                                                     │
│         [ APPLY FILTERS ]                           │
└─────────────────────────────────────────────────────┘
```

### 3.2 Filter Behavior

| Filter | Input Type | Default | Options | Cascade |
|---|---|---|---|---|
| `from` | `<input type="date">` | First day of current month | — | — |
| `to` | `<input type="date">` | Today | — | — |
| `site_id` | `<select>` | `""` (All Sites) | All active sites from `filterOptions.sites` | When changed, department dropdown resets and filters to departments in selected site |
| `department_id` | `<select>` | `""` (All Departments) | Active departments filtered by site (if site selected) | Cascading from site |

### 3.3 Filter Submission

- Form uses `router.get(route('dashboard'), { from, to, site_id, department_id }, { preserveState: true, replace: true })`.
- No page reload — Inertia handles partial reload.
- Filter state preserved in URL query params (shareable links).

---

## 4. KPI Card Layout

```
┌──────────────────────────────────┐
│  [gradient bg based on tone]    │
│                                  │
│  TOTAL INSIDEN                   │  ← label (uppercase, tracked)
│                                  │
│  142                             │  ← value (4xl, font-black)
│                                  │
│  ▰▰▰▰▱▱                         │  ← decorative progress bar
│                                  │
└──────────────────────────────────┘
```

### Tone → Gradient Mapping

| Tone | Gradient Classes | Use For |
|---|---|---|
| `indigo` | `from-indigo-500 to-blue-700` | Total Incidents |
| `amber` | `from-amber-500 to-orange-600` | Open Incidents |
| `emerald` | `from-emerald-500 to-teal-600` | Closed Incidents |
| `red` | `from-red-500 to-rose-600` | Critical Incidents |
| `rose` | `from-rose-500 to-pink-600` | Rejected Incidents |
| `sky` | `from-sky-500 to-cyan-600` | This Month |

### KPI Card Click (Drill-down)

Future: Clicking a KPI card navigates to `incident.reports.index` with pre-applied filters:
- "Total Insiden" → no extra filter
- "Insiden Terbuka" → `?status[]=draft&status[]=submitted&status[]=under_review`
- "Insiden Selesai" → `?status=closed`
- "Insiden Kritis" → `?severity=CRITICAL`
- "Insiden Ditolak" → `?status=rejected`
- "Bulan Ini" → `?from={first_day_of_month}&to={today}`

---

## 5. Chart Layout

### 5.1 Line Chart — Monthly Trend

```
┌────────────────────────────────────────────┐
│  Tren Bulanan Insiden           [Line]     │
│                                            │
│  45│                            ●          │
│  36│                    ●                 │
│  27│              ●         │             │
│  18│        ●               │             │
│   9│  ●                     │             │
│   0└─────────────────────────────         │
│      Jul  Aug  Sep  Oct  Nov  Dec  Jan    │
└────────────────────────────────────────────┘
```

### 5.2 Bar Chart — By Category / By Site

```
┌────────────────────────────────────────────┐
│  Insiden per Kategori          [Bar]       │
│                                            │
│  Accident      ████████████████████  42   │
│  Near Miss     ████████████        31     │
│  Unsafe Act    ███████             18     │
│  Unsafe Cond   ████                12     │
│  Incident      ██                   8     │
│  Env Spill     █                    3     │
│  Sec Breach    █                    2     │
└────────────────────────────────────────────┘
```

### 5.3 Donut Chart — By Severity / By Status

```
┌────────────────────────────────────────────┐
│  Insiden per Severity        [Donut]      │
│                                            │
│              ╭─────────╮                  │
│             ╱           ╲                 │
│            │    42%      │                │
│             ╲           ╱                 │
│              ╰─────────╯                  │
│                                            │
│  ● Low 42  ● Medium 28  ● High 20        │
│  ● Critical 10                            │
└────────────────────────────────────────────┘
```

---

## 6. Table Widget Layout

```
┌─────────────────────────────────────────────────────┐
│  Recent Incidents                                  │
│                                                    │
│  Nomor     Judul        Kategori   Severity Status│
│  ─────────────────────────────────────────────────  │
│  INC-001   Spill...     ENV        HIGH     Open   │
│  INC-002   Fall...      ACC        CRIT     Open   │
│  INC-003   Leak...      ENV        MED      Closed │
│  INC-004   PPE...       UAC        LOW      Sub.   │
│  ...                                               │
└─────────────────────────────────────────────────────┘
```

### Widget Column Definitions

| Widget | Col 1 | Col 2 | Col 3 | Col 4 | Col 5 | Col 6 |
|---|---|---|---|---|---|---|
| Recent Incidents | Nomor | Judul | Kategori | Severity | Status | Tanggal |
| Critical Open | Nomor | Judul | Site | Reporter | Days Open | — |
| Aging Report | Nomor | Judul | Status | Days Since Created | — | — |

---

## 7. Quick Links Section

```
┌──────────────────────────────────────────────────────────────┐
│  Role-aware Quick Access                                     │
│  Only links allowed by permissions are rendered.             │
│                                                              │
│  ┌────────┐ ┌──────────────┐ ┌────────┐ ┌──────────────┐   │
│  │ Sites  │ │ Departments  │ │ Files  │ │ Notifications│   │
│  └────────┘ └──────────────┘ └────────┘ └──────────────┘   │
│  ┌───────────────┐                                         │
│  │ Incident Rep. │                                         │
│  └───────────────┘                                         │
└──────────────────────────────────────────────────────────────┘
```

- Each link is a pill button (`rounded-full`).
- Links are filtered by `permissions.has(item.permission)`.
- If no links visible: "No quick links available for this role."

---

## 8. Navigation Placement

```
Sidebar Menu:
├── Dashboard ◄── THIS MODULE (default landing page after login)
├── Master Data
│   ├── Sites
│   ├── Areas
│   ├── Departments
│   └── ...
├── Incident Reporting
│   ├── Incident List
│   └── ...
├── (future modules...)
└── Settings
```

- Dashboard is the **default redirect** after login (`Route::get('/dashboard', DashboardController::class)`).
- Dashboard is the first item in the sidebar navigation.
- No sub-navigation needed — single page.

---

## 9. Color Coding

| Element | Color | Tailwind Classes |
|---|---|---|
| Hero section background | Dark gradient | `bg-slate-950` / `dark:bg-black` |
| Filter form card | Glass | `border-white/10 bg-white/10 backdrop-blur` |
| KPI Card (Total) | Indigo gradient | `from-indigo-500 to-blue-700` |
| KPI Card (Open) | Amber gradient | `from-amber-500 to-orange-600` |
| KPI Card (Closed) | Emerald gradient | `from-emerald-500 to-teal-600` |
| KPI Card (Critical) | Red gradient | `from-red-500 to-rose-600` |
| KPI Card (Rejected) | Rose gradient | `from-rose-500 to-pink-600` |
| KPI Card (This Month) | Sky gradient | `from-sky-500 to-cyan-600` |
| Chart cards | White / dark | `bg-white dark:bg-gray-900 border-slate-200 dark:border-gray-800` |
| Table widgets | White / dark | `bg-white dark:bg-gray-900 border-slate-200 dark:border-gray-800` |
| Quick link pills | Slate outline | `border-slate-200 dark:border-gray-700` hover `emerald` |
| Apply Filters button | Emerald | `bg-emerald-400 text-slate-950` |

### Status Badge Colors

| Status | Color | Tailwind |
|---|---|---|
| draft | gray | `bg-gray-100 text-gray-800` |
| submitted | blue | `bg-blue-100 text-blue-800` |
| under_review | yellow | `bg-yellow-100 text-yellow-800` |
| investigation | purple | `bg-purple-100 text-purple-800` |
| action_open | orange | `bg-orange-100 text-orange-800` |
| closed | green | `bg-green-100 text-green-800` |
| rejected | red | `bg-red-100 text-red-800` |

### Severity Badge Colors

| Severity | Color | Tailwind |
|---|---|---|
| Low | green | `bg-green-100 text-green-800` |
| Medium | yellow | `bg-yellow-100 text-yellow-800` |
| High | orange | `bg-orange-100 text-orange-800` |
| Critical | red | `bg-red-100 text-red-800` |

---

## 10. Mobile Notes

| Section | Mobile Behavior |
|---|---|
| Hero section | Stack vertically: text on top, filter form below |
| Filter form | Grid becomes 1 column (`sm:grid-cols-2` → 1 col on mobile) |
| KPI cards | Stack 1 per row (`md:grid-cols-2` → 1 col) |
| Charts | Stack 1 per row (`xl:grid-cols-2` → 1 col) |
| Table widgets | Stack 1 per row; tables become horizontally scrollable |
| Quick links | Wrap flexibly |
| Sidebar | Collapsible (hamburger menu) |

### Responsive Breakpoints

| Breakpoint | KPI Cards | Charts | Table Widgets |
|---|---|---|---|
| Mobile (<640px) | 1 column | 1 column | 1 column |
| Tablet (≥768px) | 2 columns | 1 column | 2 columns |
| Desktop (≥1280px) | 4 columns | 2 columns | 3 columns |

---

## 11. Empty States

| Scenario | Display |
|---|---|
| No incidents in scope/date range | KPI cards show `0`. Charts show empty state with message "Belum ada data insiden untuk periode ini." |
| No critical open incidents | Widget shows "Tidak ada insiden kritis terbuka." |
| No aging incidents | Widget shows "Tidak ada insiden yang membutuhkan perhatian." |
| User has no quick links | "No quick links available for this role." |
| User has 0 unread notifications | Show "0 unread notifications" in header |

---

## 12. Loading States

| Element | Loading Indicator |
|---|---|
| Page load | Inertia loading bar at top |
| Filter apply | `preserveState: true` — page stays, data refreshes |
| Chart rendering | Show `ChartPlaceholder` until real chart library loads |

---

## 13. Accessibility

| Element | ARIA |
|---|---|
| KPI cards | `role="status"`, `aria-label` with label + value |
| Charts | `role="img"`, `aria-label` with chart title and summary |
| Filter form | `<form>` with proper `<label>` associations |
| Table widgets | `<table>` with `<th scope="col">` headers |
| Quick links | `role="navigation"`, `aria-label="Quick access"` |

---

## 14. Dark Mode

Dashboard fully supports dark mode via Tailwind's `dark:` variant:

- Hero section: `bg-slate-950 dark:bg-black`
- Cards: `bg-white dark:bg-gray-900`
- Borders: `border-slate-200 dark:border-gray-800`
- Text: `text-slate-950 dark:text-white`, `text-slate-500 dark:text-slate-400`
- KPI cards: gradient backgrounds work in both modes (overlay on dark)
