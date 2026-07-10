# UI Pages — Incident Reporting (Wireframe Spec)

> All labels in Indonesian. Dark mode supported. Mobile responsive.

## 1. INDEX PAGE — Laporan Insiden

Route: `GET /incident-reports` → `Modules/Incident/Index.tsx`

### ASCII Wireframe

```
┌──────────────────────────────────────────────────────────────────────┐
│  [QHSSE]  Core  Masters  Modul QHSSE ▼  Admin                [User ▼] │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  Laporan Insiden                          [Buat Laporan]  [Export CSV]│
│                                                                      │
│  [Search: _______________________] [▼ Status] [▼ Kategori] [▼ Severity]│
│  [▼ Site] [Dari: __________] [Sampai: __________] [Filter] [Reset]   │
│                                                                      │
│  ┌────────────────────────────────────────────────────────────────┐  │
│  │ Nomor         │ Judul              │ Kategori  │ Severity │ Status │ Tanggal    │ Reporter  │
│  ├───────────────┼────────────────────┼───────────┼──────────┼────────┼────────────┼───────────│
│  │ INC-2026-0001 │ Kecelakaan di...   │ Accident  │ Critical │ Draft │ 11/07 14:30│ Budi S.   │
│  │ INC-2026-0002 │ Near miss crane    │ Near Miss │ High     │ Submitted│ 10/07 09:15│ Andi P. │
│  │ INC-2026-0003 │ Lantai basah       │ Unsafe... │ Medium   │ Under Review│ 09/07 │ Citra W. │
│  │ INC-2026-0004 │ Tumpahan kimia     │ Env. Spill│ High     │ Closed │ 08/07 11:00│ Dewi R.   │
│  └────────────────────────────────────────────────────────────────┘  │
│                                                                      │
│  ← 1  2  3  →                          Showing 1-15 of 42            │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘
```

### Elements

| Element | Detail |
|---|---|
| Page header | "Laporan Insiden" |
| Buat Laporan button | Visible if `permissions.has('incident.reports.create')`. Links to `incident.reports.create`. Blue button. |
| Export CSV button | Visible if `permissions.has('incident.reports.export')`. Links to `incident.reports.export` with current filters. Gray button. |
| Search box | Searches `incident_number` and `title`. Debounced. |
| Status filter | Dropdown: All, Draft, Submitted, Under Review, Closed, Rejected |
| Kategori filter | Dropdown: All, Accident, Incident, Near Miss, Unsafe Act, Unsafe Condition, Environmental Spill, Security Breach |
| Severity filter | Dropdown: All + from `severities` table |
| Site filter | Dropdown: All + from `sites` table |
| Date range | Dari (date picker) + Sampai (date picker) |
| Table rows | Clickable → links to `incident.reports.show` |
| Pagination | Standard Laravel pagination with query string |
| Empty state | "Belum ada laporan insiden. Klik 'Buat Laporan' untuk membuat laporan pertama." |

### Color Coding — Status Badge

| Status | Tailwind Class |
|---|---|
| Draft | `bg-gray-100 text-gray-800` |
| Submitted | `bg-blue-100 text-blue-800` |
| Under Review | `bg-yellow-100 text-yellow-800` |
| Closed | `bg-green-100 text-green-800` |
| Rejected | `bg-red-100 text-red-800` |

### Color Coding — Severity Badge

| Severity Level | Tailwind Class |
|---|---|
| Critical | `bg-red-100 text-red-800` |
| High | `bg-orange-100 text-orange-800` |
| Medium | `bg-yellow-100 text-yellow-800` |
| Low | `bg-blue-100 text-blue-800` |

---

## 2. FORM PAGE — Buat/Edit Laporan Insiden

Route: `GET /incident-reports/create` → `Modules/Incident/Form.tsx`
Route: `GET /incident-reports/{id}/edit` → `Modules/Incident/Form.tsx`

### ASCII Wireframe

```
┌──────────────────────────────────────────────────────────────────────┐
│  Laporan Insiden / Buat Laporan                                      │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌─ Informasi Umum ─────────────────────────────────────────────┐    │
│  │ Nomor Insiden: [Auto-generated, display only]                 │    │
│  │ Judul *:       [_______________________________________]      │    │
│  │ Kategori *:    [▼ Accident / Incident / Near Miss / ...]      │    │
│  │ Tanggal Kejadian *: [📅 2026-07-11 14:30]                     │    │
│  └───────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  ┌─ Lokasi ─────────────────────────────────────────────────────┐    │
│  │ Site *:      [▼ Pilih Site]                                   │    │
│  │ Area:        [▼ Pilih Area (filtered by site)]                │    │
│  │ Department:  [▼ Pilih Department (filtered by site)]          │    │
│  └───────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  ┌─ Klasifikasi ────────────────────────────────────────────────┐    │
│  │ Severity *:  [▼ Critical / High / Medium / Low]               │    │
│  │ Priority *:  [▼ Urgent / High / Normal / Low]                  │    │
│  └───────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  ┌─ Deskripsi ──────────────────────────────────────────────────┐    │
│  │ Deskripsi *:                                                      │
│  │ [_______________________________________________________]      │    │
│  │ [_______________________________________________________]      │    │
│  │ [_______________________________________________________]      │    │
│  │                                                                  │    │
│  │ Tindakan Immediate:                                              │    │
│  │ [_______________________________________________________]      │    │
│  │ [_______________________________________________________]      │    │
│  └───────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  ┌─ Orang Terlibat ────────────────────────────────────────────┐     │
│  │ [▼ Pilih Karyawan] [Note: ____________] [✕ Remove]            │     │
│  │ [▼ Pilih Karyawan] [Note: ____________] [✕ Remove]            │     │
│  │ [+ Tambah Orang]                                               │     │
│  └───────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  ┌─ Evidence ──────────────────────────────────────────────────┐     │
│  │ ┌─────────────────────────────────────────────────────────┐  │     │
│  │ │                    📁 Drop files here                    │  │     │
│  │ │                    or click to browse                    │  │     │
│  │ │                    Max 10MB per file                     │  │     │
│  │ └─────────────────────────────────────────────────────────┘  │     │
│  │ Uploaded files:                                                │     │
│  │ 📎 photo_kejadian.jpg (245KB) [Download] [Delete]             │     │
│  │ 📎 laporan_polisi.pdf (1.2MB) [Download] [Delete]             │     │
│  └───────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  [Batal]                              [Simpan Draft]  [Submit]       │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘
```

### Elements

| Section | Fields | Validation |
|---|---|---|
| Informasi Umum | incident_number (readonly, auto), title (text input), category (select), occurred_at (datetime-local) | title required max 255, category required enum, occurred_at required |
| Lokasi | site_id (select, required), area_id (select, optional, filtered by site), department_id (select, optional, filtered by site) | site_id required |
| Klasifikasi | severity_id (select, required), priority_id (select, required) | both required |
| Deskripsi | description (textarea, required), immediate_action (textarea, optional) | description required |
| Orang Terlibat | Repeater: employee_id (select), note (text, optional) | Optional. If employee_id provided, must exist |
| Evidence | File upload (drag-drop or click). Shows uploaded files with name, size, download, delete buttons | Max 10MB per file. Accepted: jpg, png, webp, pdf, docx |

### Buttons

| Button | Behavior |
|---|---|
| Batal | Link back to `incident.reports.index` |
| Simpan Draft | POST store with `action=draft`. Saves without full validation. Status stays `draft`. |
| Submit | POST store with `action=submit`. Validates all mandatory fields. Transitions to `submitted`. |

### Validation Error Display

- Field with error gets `border-red-500` class
- Error message below field: `<p class="mt-1 text-sm text-red-600">{{ error }}</p>`
- Errors come from `$page.props.errors`

### Edit Mode Differences

- If status !== 'draft': all fields readonly, buttons hidden (only "Kembali")
- If status === 'draft': same as create, but fields pre-populated
- "Submit" button only visible if status === 'draft'

---

## 3. SHOW PAGE — Detail Laporan Insiden

Route: `GET /incident-reports/{id}` → `Modules/Incident/Show.tsx`

### ASCII Wireframe

```
┌──────────────────────────────────────────────────────────────────────┐
│  Laporan Insiden / INC-2026-0001                                     │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌─ Summary ──────────────────────────────────────────────────────┐  │
│  │ INC-2026-0001                              [Draft] [Critical]    │  │
│  │ Kecelakaan di area produksi                                       │  │
│  │ Tanggal: 11 Juli 2026 14:30  │  Reporter: Budi Santoso           │  │
│  │ Site: Plant A  │  Area: Produksi  │  Department: Operasi        │  │
│  │ Priority: Urgent                                                  │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                                                      │
│  [Submit]  [Review]  [Close]  [Edit]  [Export]                      │
│  (buttons visible based on status + permission)                      │
│                                                                      │
│  ┌─ Deskripsi ────────────────────────────────────────────────────┐  │
│  │ Pekerja terpeleset di lantai basah dan jatuh. Luka di lutut     │  │
│  │ kanan.                                                            │  │
│  │                                                                  │  │
│  │ Tindakan Immediate:                                             │  │
│  │ Pertolongan pertama diberikan oleh tim medis. Area dipasang      │  │
│  │ barrier dan tanda peringatan.                                     │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                                                      │
│  ┌─ Orang Terlibat ───────────────────────────────────────────────┐  │
│  │ • Budi Santoso (Korban)                                          │  │
│  │ • Andi Pratama (Saksi mata)                                      │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                                                      │
│  ┌─ Evidence ────────────────────────────────────────────────────┐  │
│  │ 📎 photo_kejadian.jpg (245KB)  [Download]                        │  │
│  │ 📎 laporan_polisi.pdf (1.2MB)  [Download]                        │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                                                      │
│  ┌─ Status Timeline ─────────────────────────────────────────────┐  │
│  │ ● Draft          — 11/07 14:30 — Budi Santoso (created)        │  │
│  │ ● Submitted      — 11/07 14:35 — Budi Santoso (submit)         │  │
│  │ ● Under Review   — 11/07 15:00 — QHSSE Officer (review)        │  │
│  │ ○ Closed         — pending                                      │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                                                      │
│  ┌─ Comments ────────────────────────────────────────────────────┐  │
│  │ [Tulis komentar...]                                  [Kirim]     │  │
│  │ ────────────────────────────────────────────────────────       │  │
│  │ QHSSE Officer — 11/07 15:30                                     │  │
│  │ "Mohon tambahkan foto area setelah barrier dipasang."            │  │
│  │ ────────────────────────────────────────────────────────       │  │
│  │ Budi Santoso — 11/07 15:45                                      │  │
│  │ "Sudah ditambahkan, lihat evidence."                             │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                                                      │
│  ┌─ Activity Log ────────────────────────────────────────────────┐  │
│  │ 11/07 15:45 — Comment added by Budi Santoso                    │  │
│  │ 11/07 15:30 — Comment added by QHSSE Officer                    │  │
│  │ 11/07 15:00 — Workflow: submitted → under_review                │  │
│  │ 11/07 14:35 — Workflow: draft → submitted                       │  │
│  │ 11/07 14:30 — File uploaded: photo_kejadian.jpg                  │  │
│  │ 11/07 14:30 — Incident created by Budi Santoso                  │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘
```

### Action Buttons (permission + status gated)

| Button | Visible When | Permission Required | Action |
|---|---|---|---|
| Submit | status === 'draft' | `incident.reports.submit` | POST `incident.reports.submit` |
| Review | status === 'submitted' | `incident.reports.review` | POST `incident.reports.review` |
| Close | status === 'under_review' | `incident.reports.close` | POST `incident.reports.close` (with reason modal) |
| Edit | status === 'draft' | `incident.reports.update` | Link to `incident.reports.edit` |
| Export | always | `incident.reports.export` | Link to `incident.reports.export` |

### Close Reason Modal

When "Close" is clicked, show modal:

```
┌──────────────────────────────────────┐
│  Tutup Laporan Insiden              │
│                                      │
│  Alasan Penutupan *                  │
│  [________________________________]  │
│  [________________________________]  │
│  [________________________________]  │
│                                      │
│              [Batal]  [Tutup]        │
└──────────────────────────────────────┘
```

---

## 4. Navigation Placement

Add new group "Modul QHSSE" to `menuGroups` in `AuthenticatedLayout.tsx`:

```typescript
{
    label: 'Modul QHSSE',
    items: [
        { label: 'Laporan Insiden', routeName: 'incident.reports.index', active: 'incident.reports.*', permission: 'incident.reports.view' },
    ],
},
```

Place between "Masters" and "Admin" groups.

---

## 5. Mobile Responsive Notes

- Table → card layout on mobile (< 640px): each incident becomes a card with stacked fields
- Form sections stack vertically (they already are, but ensure no horizontal scroll)
- Action buttons wrap to full width on mobile
- Search + filters collapse into a "Filter" button that opens a sheet
- Detail page sections stack vertically with full width
- Comments and activity remain scrollable lists

---

## 6. Component Reuse

Use existing components where possible:

| Component | Used For |
|---|---|
| `NavLink` / `ResponsiveNavLink` | Navigation menu items |
| `Dropdown` | Navigation groups, filter selects |
| `Pagination` | List pagination (Laravel default) |
| Status badges | Custom `<Badge>` component or inline Tailwind spans |
| File upload | Can use existing file upload pattern from core |

### Suggested new shared components:

| Component | Props | Used In |
|---|---|---|
| `StatusBadge` | `status: string` | Index, Show |
| `SeverityBadge` | `severity: { name, color, level }` | Index, Show |
| `CategoryBadge` | `category: string` | Index, Show |
| `WorkflowTimeline` | `history: WorkflowHistory[]` | Show |
| `CommentSection` | `comments: Comment[], module: string, referenceId: number` | Show |
| `ActivityTimeline` | `activities: ActivityLog[]` | Show |
| `EvidenceList` | `files: ManagedFile[]` | Show, Form |
