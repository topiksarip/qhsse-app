# UI Pages — CAPA / Corrective & Preventive Action Tracking

Spesifikasi wireframe halaman UI untuk modul CAPA.

Semua label menggunakan Bahasa Indonesia. Tech stack: Laravel 12 + Inertia React + TypeScript + Tailwind CSS.

---

## Daftar Isi

1. [Navigasi](#1-navigasi)
2. [Color Coding](#2-color-coding)
3. [Halaman Index — Daftar Tindakan](#3-halaman-index--daftar-tindakan)
4. [Halaman Form — Buat/Edit Tindakan](#4-halaman-form--buatedit-tindakan)
5. [Halaman Show — Detail Tindakan](#5-halaman-show--detail-tindakan)
6. [Mobile Responsive](#6-mobile-responsive)
7. [Komponen Reusable](#7-komponen-reusable)

---

## 1. Navigasi

### Penempatan Menu

Tambahkan item pada group `Modul QHSSE` di array `menuGroups` pada `AuthenticatedLayout.tsx`:

```typescript
const menuGroups: { label: string; items: MenuItem[] }[] = [
    {
        label: 'Core',
        items: [
            { label: 'Dashboard', routeName: 'dashboard', active: 'dashboard' },
        ],
    },
    {
        label: 'Modul QHSSE',
        items: [
            { label: 'Laporan Insiden', routeName: 'incident.reports.index', active: 'incident.reports.*', permission: 'incident.reports.view' },
            { label: 'Tindakan (CAPA)', routeName: 'capa.actions.index', active: 'capa.actions.*', permission: 'capa.actions.view' },
        ],
    },
    {
        label: 'Masters',
        // ...
    },
    {
        label: 'Admin',
        // ...
    },
];
```

### Wireframe Navigasi (Desktop)

```
┌──────────────────────────────────────────────────────────────────────┐
│  [Logo] QHSSE   Core ▾   Modul QHSSE ▾   Masters ▾   Admin ▾  [User]│
│                        ┌──────────────────┐                         │
│                        │ Laporan Insiden  │                         │
│                        │ Tindakan (CAPA)  │                         │
│                        └──────────────────┘                         │
└──────────────────────────────────────────────────────────────────────┘
```

### Permission Filtering

Menu hanya tampil jika user memiliki permission `capa.actions.view`.

---

## 2. Color Coding

### Status Badge

| Status | Tailwind Class | Preview |
|---|---|---|
| Open | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | `⚪ Open` |
| In Progress | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | `🔵 In Progress` |
| Waiting Verification | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 Menunggu Verifikasi` |
| Closed | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Closed` |
| Rejected | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Ditolak` |

### Severity Badge

| Severity | Tailwind Class | Preview |
|---|---|---|
| Critical | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Critical` |
| High | `bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200` | `🟠 High` |
| Medium | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 Medium` |
| Low | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | `🔵 Low` |

### Priority Badge

| Priority | Tailwind Class | Preview |
|---|---|---|
| Urgent | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Urgent` |
| High | `bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200` | `🟠 High` |
| Medium | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 Medium` |
| Low | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Low` |

### Source Module Badge

| Source | Tailwind Class | Preview |
|---|---|---|
| Incident | `bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200` | `🟣 Insiden` |
| Inspection | `bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200` | `🔵 Inspection` |
| Audit | `bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200` | `🟢 Audit` |
| Manual | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | `⚪ Manual` |

### Overdue Highlight

| Element | Tailwind Class | Preview |
|---|---|---|
| Row background | `bg-red-50 dark:bg-red-900/20` | Light red background |
| Overdue badge | `bg-red-600 text-white dark:bg-red-700` | `⏰ Terlambat` |
| Due date text | `text-red-600 dark:text-red-400 font-semibold` | Red bold text |

### Pemetaan Helper

```typescript
// utils/badgeColors.ts

const statusColors: Record<string, BadgeColor> = {
    open:                  'gray',
    in_progress:           'blue',
    waiting_verification:  'yellow',
    closed:                'green',
    rejected:              'red',
};

const severityColors: Record<string, BadgeColor> = {
    critical: 'red',
    high:     'orange',
    medium:   'yellow',
    low:      'blue',
};

const priorityColors: Record<string, BadgeColor> = {
    urgent: 'red',
    high:   'orange',
    medium: 'yellow',
    low:    'green',
};

const sourceColors: Record<string, BadgeColor> = {
    incident:    'purple',
    inspection:  'indigo',
    audit:       'green',
    manual:      'gray',
};

const sourceLabels: Record<string, string> = {
    incident:    'Insiden',
    inspection:  'Inspection',
    audit:       'Audit',
    manual:      'Manual',
};

const statusLabels: Record<string, string> = {
    open:                  'Open',
    in_progress:           'In Progress',
    waiting_verification:   'Menunggu Verifikasi',
    closed:                'Closed',
    rejected:              'Ditolak',
};
```

---

## 3. Halaman Index — Daftar Tindakan

### Route: `GET /capa-actions` (`capa.actions.index`)

### Permission: `capa.actions.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                       │
│  Tindakan (CAPA)                                    [+ Buat Tindakan]        │
│  Kelola tindakan korektif dan preventif (CAPA)                             │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari nomor, judul...                               ]               │  │
│  │                                                                        │  │
│  │ Status: [Semua ▾]  Sumber: [Semua ▾]  Priority: [Semua ▾]             │  │
│  │ Site:   [Semua ▾]  Dept:   [Semua ▾]  Hanya Terlambat: [☐]           │  │
│  │ Dari:   [__/__/____]  Sampai: [__/__/____]  [Reset]                   │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Toolbar ─────────────────────────────────────────────────────────────┐  │
│  │ Menampilkan 1–15 dari 42 tindakan                  [⬇ Export CSV]     │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Table ─────────────────────────────────────────────────────────────────┐ │
│  │ Nomor       Judul           Sumber     Priority  Status     Due Date  │ │
│  ├─────────────────────────────────────────────────────────────────────────┤ │
│  │ ACT-0001   Perbaikan Pipa  🟣Insiden  🔴Urgent  ⚪Open     10/07/26   │ │
│  │            Bocor                  ⏰Terlambat                        │ │
│  ├─────────────────────────────────────────────────────────────────────────┤ │
│  │ ACT-0002   Training APD    🔵Inspect  🟠High    🔵In Prog  15/07/26   │ │
│  ├─────────────────────────────────────────────────────────────────────────┤ │
│  │ ACT-0003   Kalibrasi Alat  🟢Audit    🟡Medium  🟡Menunggu 20/07/26   │ │
│  ├─────────────────────────────────────────────────────────────────────────┤ │
│  │ ACT-0004   Perbaikan Pintu  ⚪Manual   🔵Low     ⚪Open     25/07/26   │ │
│  ├─────────────────────────────────────────────────────────────────────────┤ │
│  │ ACT-0005   Review SOP     🟣Insiden  🟡Medium  🟢Closed   05/07/26   │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│  ┌─ Table (cont.) ────────────────────────────────────────────────────────┐ │
│  │ ... Due Date  PIC          Aksi                                          │ │
│  │ ... 10/07/26 Budi S.       [👁 Lihat] [▶ Mulai]                        │ │
│  │ ... 15/07/26 Sari W.       [👁 Lihat] [📤 Submit]                       │ │
│  │ ... 20/07/26 Andi P.       [👁 Lihat] [✅ Verifikasi]                   │ │
│  │ ... 25/07/26 Joni K.       [👁 Lihat] [▶ Mulai]                        │ │
│  │ ... 05/07/26 Doni A.       [👁 Lihat]                                   │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  ┌─ Pagination ───────────────────────────────────────────────────────────┐  │
│  │                              ‹ Sebelumnya   1  2  3   Berikutnya ›     │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Wireframe — Overdue Row Highlight (Detail)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  ┌─ Overdue Row (bg-red-50 dark:bg-red-900/20) ───────────────────────────┐ │
│  │ ACT-0001   Perbaikan Pipa Bocor   🟣Insiden  🔴Urgent  ⚪Open         │ │
│  │            ⏰ Terlambat (2 hari)                                       │ │
│  │            Due: 10/07/26    PIC: Budi S.    [👁 Lihat] [▶ Mulai]     │ │
│  └────────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  ┌─ Normal Row ─────────────────────────────────────────────────────────┐  │
│  │ ACT-0002   Training APD   🔵Inspect  🟠High    🔵In Progress         │  │
│  │            Due: 15/07/26    PIC: Sari W.    [👁 Lihat] [📤 Submit]   │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Wireframe — Empty State

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                                                                              │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │                                                                      │   │
│  │                          📋                                          │   │
│  │                                                                      │   │
│  │                   Belum ada tindakan (CAPA)                          │   │
│  │                                                                      │   │
│  │           Belum ada tindakan yang dibuat. Klik tombol di bawah        │   │
│  │           untuk membuat tindakan pertama.                            │   │
│  │                                                                      │   │
│  │                      [+ Buat Tindakan Pertama]                       │   │
│  │                                                                      │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Element

#### Header

| Element | Type | Detail |
|---|---|---|
| Title | `<h1>` | "Tindakan (CAPA)" |
| Subtitle | `<p>` | "Kelola tindakan korektif dan preventif (CAPA)" |
| Button "Buat Tindakan" | `<Link>` | Route: `capa.actions.create`, permission: `capa.actions.create` |
| Button Style | Tailwind | `bg-blue-600 text-white hover:bg-blue-700` |

#### Search Box

| Element | Type | Detail |
|---|---|---|
| Placeholder | `<input>` | "Cari nomor, judul..." |
| Icon | SVG | Magnifying glass icon |
| Behavior | debounce | 300ms debounce, Inertia visit |
| Param | query | `?search=keyword` |

#### Filter Dropdowns

| Filter | Label | Options | Param |
|---|---|---|---|
| Status | "Status" | Semua, Open, In Progress, Menunggu Verifikasi, Closed, Ditolak | `?status=` |
| Sumber | "Sumber" | Semua, Insiden, Inspection, Audit, Manual | `?source_module=` |
| Priority | "Priority" | Semua, Urgent, High, Medium, Low | `?priority_id=` |
| Site | "Site" | Semua + dari master Sites | `?site_id=` |
| Department | "Departemen" | Semua + dari master Departments | `?department_id=` |
| Overdue Only | Checkbox | "Hanya Terlambat" | `?overdue=1` |
| Date Range | "Dari" / "Sampai" | Date picker | `?from=` `?to=` |
| Reset | Button | "Reset" — clear all filters | — |

#### Table Columns

| # | Column | Key | Width | Align | Badge? | Detail |
|---|---|---|---|---|---|---|
| 1 | Nomor | `action_number` | 120px | left | No | Link ke show page, monospace |
| 2 | Judul | `title` | flex | left | No | Truncate dengan `max-w-xs truncate` |
| 3 | Sumber | `source_module` | 100px | center | Yes | Lihat [Color Coding](#2-color-coding) |
| 4 | Priority | `priority.name` | 100px | center | Yes | Lihat [Color Coding](#2-color-coding) |
| 5 | Status | `status` | 140px | center | Yes | Lihat [Color Coding](#2-color-coding) |
| 6 | Due Date | `due_date` | 120px | center | No | Red text + ⏰ badge if overdue |
| 7 | PIC | `assigned_to.name` | 130px | left | No | Nama user |
| 8 | Aksi | — | 140px | center | No | Lihat di bawah |

#### Overdue Row Highlight

```tsx
<tr
    key={action.id}
    className={action.is_overdue
        ? 'bg-red-50 dark:bg-red-900/20'
        : 'hover:bg-gray-50 dark:hover:bg-gray-800'
    }
>
    {/* ... cells ... */}
    {action.is_overdue && (
        <td colSpan={1}>
            <span className="inline-flex items-center rounded-full bg-red-600 px-2 py-0.5 text-xs font-medium text-white">
                ⏰ Terlambat ({action.days_overdue} hari)
            </span>
        </td>
    )}
</tr>
```

#### Aksi Column (per row)

| Action | Icon | Permission | Condition |
|---|---|---|---|
| Lihat | 👁 | `capa.actions.view` | Selalu tampil |
| Mulai | ▶ | `capa.actions.update` | Status = open |
| Submit | 📤 | `capa.actions.submit` | Status = in_progress |
| Verifikasi | ✅ | `capa.actions.verify` | Status = waiting_verification |
| Edit | ✏ | `capa.actions.update` | Status in open/in_progress/rejected |

#### Pagination

```
Menampilkan 1–15 dari 42 tindakan

‹ Sebelumnya   1  2  3   Berikutnya ›
```

- 15 item per halaman (configurable: 15/25/50).

#### Export CSV

| Element | Detail |
|---|---|
| Button | `[⬇ Export CSV]` |
| Permission | `capa.actions.export` |
| Behavior | Export data sesuai filter aktif |
| Endpoint | `GET /capa-actions/export?status=...&source_module=...` |

### Inertia Props

```typescript
interface IndexProps {
    items: {
        data: CapaActionListItem[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
    filters: {
        search?: string;
        status?: string;
        source_module?: string;
        priority_id?: number;
        site_id?: number;
        department_id?: number;
        overdue?: boolean;
        from?: string;
        to?: string;
    };
    sites: Site[];
    departments: Department[];
    priorities: Priority[];
    can: {
        create: boolean;
        export: boolean;
    };
}

interface CapaActionListItem {
    id: number;
    action_number: string;
    title: string;
    source_module: string | null;
    source_type: string | null;
    status: string;
    due_date: string | null;
    is_overdue: boolean;
    days_overdue: number | null;
    priority: { id: number; name: string; code: string } | null;
    site: { id: number; name: string } | null;
    assigned_to: { id: number; name: string } | null;
}
```

---

## 4. Halaman Form — Buat/Edit Tindakan

### Route

- Create: `GET /capa-actions/create` (`capa.actions.create`)
- Edit: `GET /capa-actions/{capaAction}/edit` (`capa.actions.edit`)

### Permission

- Create: `capa.actions.create`
- Edit: `capa.actions.update` (hanya jika status open/in_progress/rejected)

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Buat Tindakan (CAPA)                                                            │
│  Isi data tindakan korektif/preventif dengan lengkap                            │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Sumber Tindakan ───────────────────────────────────────────────┐   │
│  │  SUMBER TINDAKAN                                                          │   │
│  │  ─────────────────────────────────────────────────────────────────────    │   │
│  │                                                                           │   │
│  │  Sumber *     [— Pilih Sumber —           ▾]                             │   │
│  │                ○ Dari Insiden                                             │   │
│  │                ○ Dari Inspection                                          │   │
│  │                ○ Dari Audit                                               │   │
│  │                ● Manual (dibuat langsung)                                 │   │
│  │                                                                           │   │
│  │  ┌─ If source != manual ───────────────────────────────────────────────┐ │   │
│  │  │ Referensi *  [— Cari nomor record sumber...        ▾]               │ │   │
│  │  │              (contoh: INC-2026-0001, INS-2026-0001)                 │ │   │
│  │  └─────────────────────────────────────────────────────────────────────┘ │   │
│  │                                                                           │   │
│  │  Tipe         [— Pilih Tipe —    ▾]                                     │   │
│  │                ○ Corrective Action   ○ Preventive Action               │   │
│  │                                                                           │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Section: Informasi Umum ──────────────────────────────────────────────┐   │
│  │  INFORMASI UMUM                                                          │   │
│  │  ─────────────────────────────────────────────────────────────────────    │   │
│  │                                                                           │   │
│  │  Nomor Tindakan     [Auto-generated — ACT-0005              ]  ⓘ        │   │
│  │                       Nomor dibuat otomatis saat simpan                    │   │
│  │                                                                           │   │
│  │  Judul *            [Masukkan judul tindakan...              ]           │   │
│  │                                                                           │   │
│  │  Deskripsi *        ┌──────────────────────────────────────────────┐    │   │
│  │  (Tindakan)          │ Jelaskan tindakan korektif/preventif...     │    │   │
│  │                       │                                              │    │   │
│  │                       └──────────────────────────────────────────────┘    │   │
│  │                       Minimal 20 karakter                                  │   │
│  │                                                                           │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Section: Penugasan ────────────────────────────────────────────────────┐   │
│  │  PENUGASAN                                                               │   │
│  │  ─────────────────────────────────────────────────────────────────────    │   │
│  │                                                                           │   │
│  │  Site *              [— Pilih Site —    ▾]                                │   │
│  │                                                                           │   │
│  │  Departemen          [— Pilih Departemen —    ▾]  (filtered by site)    │   │
│  │                                                                           │   │
│  │  PIC *              [— Cari user... —    ▾]   (Person In Charge)        │   │
│  │                                                                           │   │
│  │  Due Date *          [__/__/____] [📅]                                   │   │
│  │                       Batas waktu penyelesaian                            │   │
│  │                                                                           │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Section: Klasifikasi ──────────────────────────────────────────────────┐   │
│  │  KLASIFIKASI                                                             │   │
│  │  ─────────────────────────────────────────────────────────────────────    │   │
│  │                                                                           │   │
│  │  Priority *          [— Pilih Priority —    ▾]                           │   │
│  │                        ○ Urgent  ○ High  ○ Medium  ○ Low                │   │
│  │                                                                           │   │
│  │  Severity            [— Pilih Severity —    ▾]   (opsional)             │   │
│  │                        ○ Critical  ○ High  ○ Medium  ○ Low             │   │
│  │                                                                           │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Action Bar (sticky bottom) ────────────────────────────────────────────┐   │
│  │                                                                           │   │
│  │  [← Batal]                                          [Simpan Tindakan]    │   │
│  │                                                         (primary)         │   │
│  └───────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Wireframe — Source Dropdown Behavior

```
┌─ Source Dropdown ──────────────────────────┐
│  ─ Pilih Sumber ──                        │
│  ┌──────────────────────────────────────┐  │
│  │ 🟣 Dari Insiden                     │  │
│  │ 🔵 Dari Inspection                   │  │
│  │ 🟢 Dari Audit                        │  │
│  │ ⚪ Manual (dibuat langsung)          │  │
│  └──────────────────────────────────────┘  │
└─────────────────────────────────────────────┘

When "Dari Insiden" selected:
┌─ Referensi Sumber ─────────────────────────┐
│  Referensi *  [INC-2026-0001 — Kecelakaan   │
│               Kerja di Area Produksi     ▾] │
│                (dropdown searchable dari    │
│                 incidents table)              │
└─────────────────────────────────────────────┘

When "Manual" selected:
(Referensi field hidden — source_reference_id = NULL)
```

### Spesifikasi Element

#### Sumber Tindakan Section

| Element | Type | Detail |
|---|---|---|
| Sumber | Select | Options: Dari Insiden, Dari Inspection, Dari Audit, Manual |
| Referensi | Select (conditional) | Shown when source != manual. Searchable dropdown filtered by source module. |
| Tipe | Radio/Select | Options: Corrective Action, Preventive Action. Optional. |

#### Informasi Umum Section

| Element | Type | Detail |
|---|---|---|
| Nomor Tindakan | Text (readonly) | "Auto-generated — ACT-0005". Info: "Nomor dibuat otomatis saat simpan" |
| Judul | Input | required, max:255 |
| Deskripsi | Textarea | required, min:20 |

#### Penugasan Section

| Element | Type | Detail |
|---|---|---|
| Site | Select | required, filtered by user scope |
| Departemen | Select | optional, filtered by selected site |
| PIC | Select (searchable) | required, list of users |
| Due Date | Date picker | required, format dd/mm/yyyy |

#### Klasifikasi Section

| Element | Type | Detail |
|---|---|---|
| Priority | Select | required: Urgent, High, Medium, Low |
| Severity | Select | optional: Critical, High, Medium, Low |

### Inertia Props

```typescript
interface FormProps {
    item: CapaAction | null;  // null for create, populated for edit
    sites: Site[];
    departments: Department[];
    users: User[];            // for PIC dropdown
    priorities: Priority[];
    severities: Severity[];
    sourceRecords: {
        incident: { id: number; number: string; title: string }[];
        inspection: { id: number; number: string; title: string }[];
        audit: { id: number; number: string; title: string }[];
    };
}
```

---

## 5. Halaman Show — Detail Tindakan

### Route: `GET /capa-actions/{capaAction}` (`capa.actions.show`)

### Permission: `capa.actions.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  [← Kembali]  ACT-2026-0001                                                     │
│  Perbaikan Pipa Bocor di Area Produksi                                           │
│  ⚪ Open  🟣Insiden  🔴Urgent  ⏰ Terlambat (2 hari)                            │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Action Bar (workflow buttons) ─────────────────────────────────────────┐    │
│  │  [▶ Mulai Pengerjaan]  [✏ Edit]  [🗑 Hapus]   (conditional on status)  │    │
│  └─────────────────────────────────────────────────────────────────────────┘    │
│                                                                                  │
│  ┌─ Two Column Layout ─────────────────────────────────────────────────────┐    │
│  │                                                                        │  │
│  │  ┌─ Left Column (2/3) ──────────────────────┐  ┌─ Right Column (1/3) ─┐│  │
│  │  │                                          │  │                     ││  │
│  │  │  ┌─ Detail Tindakan ───────────────────┐  │  │  ┌─ Info ──────────┐││  │
│  │  │  │                                     │  │  │  │                 │││  │
│  │  │  │  Nomor:      ACT-2026-0001         │  │  │  │  Site:          │││  │
│  │  │  │  Judul:      Perbaikan Pipa Bocor  │  │  │  │  Plant A        │││  │
│  │  │  │  Sumber:     Dari Insiden          │  │  │  │                 │││  │
│  │  │  │              INC-2026-0001 [→ Link] │  │  │  │  Departemen:    │││  │
│  │  │  │  Tipe:       Corrective Action     │  │  │  │  Produksi       │││  │
│  │  │  │                                     │  │  │  │                 │││  │
│  │  │  │  Deskripsi:                        │  │  │  │  PIC:           │││  │
│  │  │  │  Pipa bocor di area produksi      │  │  │  │  Budi Santoso   │││  │
│  │  │  │  menyebabkan tumpahan minyak.     │  │  │  │                 │││  │
│  │  │  │  Perlu diganti segmen pipa sepan- │  │  │  │  Dibuat oleh:   │││  │
│  │  │  │ jang 5 meter dan inspeksi ulang   │  │  │  │  QHSSE Officer  │││  │
│  │  │  │  area sekitarnya.                 │  │  │  │                 │││  │
│  │  │  │                                     │  │  │  │  Due Date:     │││  │
│  │  │  │  Priority:   Urgent                │  │  │  │  10/07/2026    │││  │
│  │  │  │  Severity:   Critical             │  │  │  │  ⏰ Terlambat   │││  │
│  │  │  │  Status:     Open                 │  │  │  │                 │││  │
│  │  │  │                                     │  │  │  │  Created:      │││  │
│  │  │  └─────────────────────────────────────┘  │  │  │  08/07/2026    │││  │
│  │  │                                          │  │  │                 │││  │
│  │  │  ┌─ Evidence (Bukti) ────────────────┐   │  │  └─────────────────┘││  │
│  │  │  │                                  │   │  │                     ││  │
│  │  │  │  📎 foto_pipa_bocor.jpg   2.3 MB │   │  │  ┌─ Workflow ──────┐││  │
│  │  │  │     [⬇ Download] [🗑 Hapus]     │   │  │  │                 │││  │
│  │  │  │                                  │   │  │  │ ● Open          │││  │
│  │  │  │  📎 laporan_inspeksi.pdf 850 KB  │   │  │  │   08/07 14:00   │││  │
│  │  │  │     [⬇ Download]                 │   │  │  │   by QHSSE     │││  │
│  │  │  │                                  │   │  │  │                 │││  │
│  │  │  │  [📁 Upload Evidence]            │   │  │  │ ○ In Progress   │││  │
│  │  │  │  (min 1 file sebelum submit)     │   │  │  │ ○ Waiting Ver.  │││  │
│  │  │  └──────────────────────────────────┘   │  │  │ ○ Closed       │││  │
│  │  │                                          │  │  │                 │││  │
│  │  │  ┌─ Verification Panel ──────────────┐   │  │  └─────────────────┘││  │
│  │  │  │  (shown when status =             │   │  │                     ││  │
│  │  │  │   waiting_verification)           │   │  │  ┌─ Activity ─────┐││  │
│  │  │  │                                  │   │  │  │                 │││  │
│  │  │  │  ⚠ Tindakan menunggu verifikasi  │   │  │  │  • Tindakan      │││  │
│  │  │  │                                  │   │  │  │    dibuat       │││  │
│  │  │  │  Catatan Verifikasi *            │   │  │  │    08/07 14:00  │││  │
│  │  │  │  ┌────────────────────────────┐  │   │  │  │    by QHSSE     │││  │
│  │  │  │  │ Tulis catatan verifikasi.. │  │   │  │  │                 │││  │
│  │  │  │  │                            │  │   │  │  │  • Tindakan     │││  │
│  │  │  │  └────────────────────────────┘  │   │  │  │    dimulai      │││  │
│  │  │  │  Min 10 karakter                 │   │  │  │    08/07 15:00  │││  │
│  │  │  │                                  │   │  │  │    by Budi S.   │││  │
│  │  │  │  [✅ Verifikasi & Tutup]         │   │  │  │                 │││  │
│  │  │  │  [❌ Tolak (Reject)]             │   │  │  │  • Submit       │││  │
│  │  │  │                                  │   │  │  │    verifikasi  │││  │
│  │  │  │  ┌─ Reject Modal ─────────────┐ │   │  │  │    09/07 10:00  │││  │
│  │  │  │  │ Alasan Reject *            │ │   │  │  │    by Budi S.   │││  │
│  │  │  │  │ ┌────────────────────────┐ │ │   │  │  │                 │││  │
│  │  │  │  │ │ Tulis alasan penolakan.│ │ │   │  │  └─────────────────┘││  │
│  │  │  │  │ └────────────────────────┘ │ │   │  │                     ││  │
│  │  │  │  │ Min 10 karakter             │ │   │  │  ┌─ Comments ─────┐││  │
│  │  │  │  │ [Batal]  [Kirim Reject]     │ │   │  │  │                 │││  │
│  │  │  │  └──────────────────────────────┘ │   │  │  │  Budi S.       │││  │
│  │  │  └──────────────────────────────────┘   │  │  │  08/07 16:00    │││  │
│  │  │                                          │  │  │  "Sudah mulai   │││  │
│  │  │  ┌─ Verification Result (if closed) ──┐ │  │  │   perbaikan..." │││  │
│  │  │  │  ✅ Diverifikasi & Ditutup         │ │  │  │                 │││  │
│  │  │  │  Verifier: QHSSE Officer           │ │  │  │  QHSSE Officer  │││  │
│  │  │  │  Verified: 10/07/2026 14:00        │ │  │  │  08/07 17:00    │││  │
│  │  │  │  Closed:    10/07/2026 14:00       │ │  │  │  "Diperhatikan, │││  │
│  │  │  │  Catatan:   Pipa sudah diganti     │ │  │  │   mohon update  │││  │
│  │  │  │             dan area dibersihkan.  │ │  │  │   progres..."   │││  │
│  │  │  └──────────────────────────────────────┘ │  │                 │││  │
│  │  │                                          │  │  │  [💬 Tambah    │││  │
│  │  └──────────────────────────────────────────┘  │  │   Komentar]    │││  │
│  │                                                │  └─────────────────┘││  │
│  └────────────────────────────────────────────────┘                     ││  │
│                                                                         ││  │
└─────────────────────────────────────────────────────────────────────────┘
└──────────────────────────────────────────────────────────────────────────┘
│                                                                                  │
│  ┌─ Workflow Timeline (full width) ────────────────────────────────────────┐    │
│  │                                                                         │    │
│  │  ●─────●─────○─────○─────○                                             │    │
│  │  Open  In    Wait  Closed Rejected                                      │    │
│  │  08/07 Prog  Ver                                                      │    │
│  └─────────────────────────────────────────────────────────────────────────┘    │
│                                                                                  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Verification Panel — States

#### State 1: Status = `open` (Action buttons)

```
┌─ Action Bar ──────────────────────────────────────────────┐
│  [▶ Mulai Pengerjaan]  [✏ Edit]  [🗑 Hapus]             │
│   permission: update    permission: update  Admin only   │
└──────────────────────────────────────────────────────────┘
```

#### State 2: Status = `in_progress` (Action buttons)

```
┌─ Action Bar ──────────────────────────────────────────────┐
│  [📤 Submit untuk Verifikasi]  [✏ Edit]                  │
│   permission: submit           permission: update        │
│                                                            │
│  ⚠ Pastikan minimal 1 evidence sudah diunggah            │
└──────────────────────────────────────────────────────────┘
```

#### State 3: Status = `waiting_verification` (Verification panel)

```
┌─ Verification Panel ─────────────────────────────────────┐
│  ⚠ Tindakan menunggu verifikasi QHSSE                    │
│                                                           │
│  Catatan Verifikasi *                                     │
│  ┌────────────────────────────────────────────────────┐   │
│  │ Tulis catatan verifikasi...                        │   │
│  └────────────────────────────────────────────────────┘   │
│  Min 10 karakter                                          │
│                                                           │
│  [✅ Verifikasi & Tutup]    [❌ Tolak (Reject)]           │
│   permission: verify+close   permission: reject           │
└──────────────────────────────────────────────────────────┘
```

#### State 4: Status = `closed` (Verification result)

```
┌─ Verification Result ────────────────────────────────────┐
│  ✅ Diverifikasi & Ditutup                               │
│                                                           │
│  Verifier:    QHSSE Officer (John Doe)                   │
│  Verified:    10/07/2026 14:00                           │
│  Closed:      10/07/2026 14:00                           │
│  Catatan:     Pipa sudah diganti dan area dibersihkan.   │
│                                                           │
│  (read-only)                                              │
└──────────────────────────────────────────────────────────┘
```

#### State 5: Status = `rejected` (Reject info + restart)

```
┌─ Reject Info ────────────────────────────────────────────┐
│  🔴 Tindakan Ditolak                                      │
│                                                           │
│  Ditolak oleh: QHSSE Officer (John Doe)                  │
│  Tanggal:     09/07/2026 16:00                            │
│  Alasan:      Bukti foto tidak jelas, mohon              │
│               unggah ulang dengan resolusi lebih tinggi. │
│                                                           │
│  [🔄 Mulai Ulang (Restart)]                              │
│   permission: update                                      │
└──────────────────────────────────────────────────────────┘
```

### Cross-Module Source Link

```
┌─ Sumber Tindakan ────────────────────────────────────────┐
│  Sumber:     🟣 Dari Insiden                              │
│  Referensi:  INC-2026-0001 — Kecelakaan Kerja di Area    │
│              Produksi  [→ Buka Insiden]                  │
│  Tipe:       Corrective Action                           │
└──────────────────────────────────────────────────────────┘

[→ Buka Insiden] = Link to /incident-reports/{source_reference_id}
```

### Inertia Props

```typescript
interface ShowProps {
    action: CapaAction & {
        site: Site;
        department: Department | null;
        assignedTo: User;
        assignedBy: User;
        severity: Severity | null;
        priority: Priority;
        verifiedBy: User | null;
        sourceRecord?: {
            number: string;
            title: string;
            url: string;
        } | null;
    };
    evidence: ManagedFile[];
    comments: Comment[];
    activities: ActivityLog[];
    workflowHistory: WorkflowHistory[];
    availableTransitions: {
        action_key: string;
        action_label: string;
        requires_reason: boolean;
        permission: string;
    }[];
    isOverdue: boolean;
    daysOverdue: number | null;
    can: {
        update: boolean;
        submit: boolean;
        verify: boolean;
        close: boolean;
        reject: boolean;
        export: boolean;
    };
}
```

---

## 6. Mobile Responsive

### Mobile Index

```
┌──────────────────────┐
│  [☰]  Tindakan (CAPA) │
│       [+ Buat]        │
├──────────────────────┤
│  [🔍 Cari...]         │
│  [Status ▾] [Sumber ▾]│
├──────────────────────┤
│  ┌──────────────────┐ │
│  │ ACT-0001        │ │
│  │ Perbaikan Pipa  │ │
│  │ 🟣Insiden 🔴Urg │ │
│  │ ⚪Open ⏰Terlam │ │
│  │ Budi S. 10/07   │ │
│  │ [👁] [▶ Mulai]  │ │
│  └──────────────────┘ │
│  ┌──────────────────┐ │
│  │ ACT-0002        │ │
│  │ Training APD     │ │
│  │ 🔵Inspect 🟠High │ │
│  │ 🔵In Progress    │ │
│  │ Sari W. 15/07    │ │
│  │ [👁] [📤 Submit] │ │
│  └──────────────────┘ │
├──────────────────────┤
│  1  2  3   Berikutnya›│
└──────────────────────┘
```

### Mobile Show

```
┌──────────────────────┐
│  [←] ACT-2026-0001   │
│  Perbaikan Pipa Bocor │
│  ⚪Open ⏰Terlambat   │
├──────────────────────┤
│  [▶ Mulai] [✏ Edit]  │
├──────────────────────┤
│  INFORMASI            │
│  Sumber:  Insiden     │
│  Tipe:    Corrective  │
│  Site:    Plant A      │
│  PIC:     Budi S.      │
│  Due:     10/07/2026   │
│  Priority: Urgent      │
│  Severity: Critical    │
├──────────────────────┤
│  EVIDENCE             │
│  📎 foto_pipa.jpg     │
│     [⬇] [🗑]          │
│  [📁 Upload]           │
├──────────────────────┤
│  AKTIVITAS            │
│  • Dibuat 08/07       │
│  • Dimulai 08/07      │
├──────────────────────┤
│  KOMENTAR             │
│  Budi: Sudah mulai... │
│  [💬 Tambah]          │
└──────────────────────┘
```

---

## 7. Komponen Reusable

### Badge Component

```tsx
// components/Badge.tsx
type BadgeColor = 'gray' | 'blue' | 'yellow' | 'green' | 'red' | 'orange' | 'purple' | 'indigo' | 'teal';

function Badge({ label, color }: { label: string; color: BadgeColor }) {
    const colors: Record<BadgeColor, string> = {
        gray:   'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        blue:   'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        green:  'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        red:    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        orange: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        purple: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        indigo: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
        teal:   'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200',
    };
    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${colors[color]}`}>
            {label}
        </span>
    );
}
```

### OverdueBadge Component

```tsx
// components/OverdueBadge.tsx
function OverdueBadge({ daysOverdue }: { daysOverdue: number }) {
    return (
        <span className="inline-flex items-center rounded-full bg-red-600 px-2 py-0.5 text-xs font-medium text-white">
            ⏰ Terlambat ({daysOverdue} hari)
        </span>
    );
}
```

### VerificationPanel Component

```tsx
// components/Capa/VerificationPanel.tsx
function VerificationPanel({ action, can }: { action: CapaAction; can: CanProps }) {
    const [verificationNote, setVerificationNote] = useState('');
    const [showRejectModal, setShowRejectModal] = useState(false);
    const [rejectReason, setRejectReason] = useState('');

    if (action.status !== 'waiting_verification') return null;

    return (
        <div className="rounded-lg border border-yellow-300 bg-yellow-50 p-4 dark:bg-yellow-900/20">
            <h3 className="text-lg font-semibold text-yellow-800 dark:text-yellow-200">
                ⚠ Tindakan Menunggu Verifikasi
            </h3>
            {/* Verification form */}
            {/* Reject modal */}
        </div>
    );
}
```

### SourceLink Component

```tsx
// components/Capa/SourceLink.tsx
function SourceLink({ sourceModule, sourceRecord }: { sourceModule: string; sourceRecord?: SourceRecord | null }) {
    if (!sourceModule || sourceModule === 'manual') {
        return <Badge label="Manual" color="gray" />;
    }

    const labels: Record<string, string> = {
        incident: 'Insiden',
        inspection: 'Inspection',
        audit: 'Audit',
    };

    const colors: Record<string, BadgeColor> = {
        incident: 'purple',
        inspection: 'indigo',
        audit: 'teal',
    };

    return (
        <div className="flex items-center gap-2">
            <Badge label={labels[sourceModule] ?? sourceModule} color={colors[sourceModule] ?? 'gray'} />
            {sourceRecord && (
                <Link href={sourceRecord.url} className="text-blue-600 hover:underline text-sm">
                    {sourceRecord.number} — {sourceRecord.title}
                </Link>
            )}
        </div>
    );
}
```
