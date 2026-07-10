# UI Pages — Investigation & RCA

Spesifikasi wireframe halaman UI untuk modul Investigation & RCA.

Semua label menggunakan Bahasa Indonesia. Tech stack: Laravel 12 + Inertia React + TypeScript + Tailwind CSS.

---

## Daftar Isi

1. [Navigasi](#1-navigasi)
2. [Color Coding](#2-color-coding)
3. [Halaman Index — Daftar Investigasi](#3-halaman-index--daftar-investigasi)
4. [Halaman Form — Buat/Edit Investigasi](#4-halaman-form--buatedit-investigasi)
5. [Halaman Show — Detail Investigasi](#5-halaman-show--detail-investigasi)
6. [Mobile Responsive](#6-mobile-responsive)

---

## 1. Navigasi

### Penempatan Menu

Tambahkan item `Investigasi & RCA` pada group `Modul QHSSE` di array `menuGroups` pada `AuthenticatedLayout.tsx`, setelah item `Laporan Insiden`.

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
            { label: 'Investigasi & RCA', routeName: 'investigation.reports.index', active: 'investigation.reports.*', permission: 'investigation.reports.view' },
        ],
    },
    {
        label: 'Masters',
        // ... existing Masters items
    },
];
```

### Wireframe Navigasi (Desktop)

```
┌──────────────────────────────────────────────────────────────────────┐
│  [Logo] QHSSE   Core ▾   Modul QHSSE ▾   Masters ▾   Admin ▾  [User]│
│                        ┌──────────────────────┐                       │
│                        │ Laporan Insiden      │                       │
│                        │ Investigasi & RCA    │                       │
│                        └──────────────────────┘                       │
└──────────────────────────────────────────────────────────────────────┘
```

### Wireframe Navigasi (Mobile — Hamburger)

```
┌──────────────────────┐
│  [Logo] QHSSE   [☰]  │
├──────────────────────┤
│  CORE                │
│   Dashboard          │
│                      │
│  MODUL QHSSE         │
│   Laporan Insiden    │
│   Investigasi & RCA  │
│                      │
│  MASTERS             │
│   Severities         │
│   ...                │
│                      │
│  ADMIN               │
│   ...                │
├──────────────────────┤
│  John Doe            │
│  john@example.com    │
│  Profile   Log Out   │
└──────────────────────┘
```

### Permission Filtering

Menu hanya tampil jika user memiliki permission `investigation.reports.view`. Filtering via `auth.permissions` pada layout.

---

## 2. Color Coding

### Status Badge

| Status | Tailwind Class | Preview |
|---|---|---|
| Draft | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | `⚪ Draft` |
| In Progress | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | `🔵 Berlangsung` |
| Completed | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Selesai` |
| Cancelled | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Dibatalkan` |

### Fishbone Category Badge

| Category | Indonesian | Tailwind Class | Preview |
|---|---|---|---|
| Man | Manusia | `bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200` | `🟣 Manusia` |
| Method | Metode | `bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200` | `🔵 Metode` |
| Machine | Mesin | `bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200` | `🟠 Mesin` |
| Material | Material | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 Material` |
| Environment | Lingkungan | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Lingkungan` |
| Management | Manajemen | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Manajemen` |

### Impact Badge (Contributing Factors)

| Impact | Indonesian | Tailwind Class | Preview |
|---|---|---|---|
| direct | Langsung | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Langsung` |
| indirect | Tidak Langsung | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 Tidak Langsung` |

### Team Role Badge

| Role | Indonesian | Tailwind Class | Preview |
|---|---|---|---|
| lead_investigator | Lead Investigator | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | `🔵 Lead Investigator` |
| investigator | Investigator | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | `⚪ Investigator` |
| subject_matter_expert | SME | `bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200` | `🟣 SME` |
| recorder | Pencatat | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Pencatat` |

### Komponen Badge (Reusable)

```tsx
// Komponen: components/Badge.tsx
type BadgeColor = 'gray' | 'blue' | 'yellow' | 'green' | 'red' | 'orange' | 'purple' | 'indigo';

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
    };
    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${colors[color]}`}>
            {label}
        </span>
    );
}
```

### Pemetaan Helper

```typescript
// utils/badgeColors.ts

const statusColors: Record<string, BadgeColor> = {
    draft:       'gray',
    in_progress: 'blue',
    completed:   'green',
    cancelled:   'red',
};

const fishboneCategoryColors: Record<string, BadgeColor> = {
    Man:          'purple',
    Method:       'indigo',
    Machine:      'orange',
    Material:     'yellow',
    Environment:  'green',
    Management:   'red',
};

const fishboneCategoryLabels: Record<string, string> = {
    Man:         'Manusia',
    Method:      'Metode',
    Machine:     'Mesin',
    Material:    'Material',
    Environment: 'Lingkungan',
    Management:  'Manajemen',
};

const teamRoleColors: Record<string, BadgeColor> = {
    lead_investigator:    'blue',
    investigator:         'gray',
    subject_matter_expert:'purple',
    recorder:             'green',
};

const teamRoleLabels: Record<string, string> = {
    lead_investigator:     'Lead Investigator',
    investigator:          'Investigator',
    subject_matter_expert: 'SME',
    recorder:              'Pencatat',
};

const impactColors: Record<string, BadgeColor> = {
    direct:   'red',
    indirect: 'yellow',
};

const impactLabels: Record<string, string> = {
    direct:   'Langsung',
    indirect: 'Tidak Langsung',
};
```

---

## 3. Halaman Index — Daftar Investigasi

### Route: `GET /investigations` (`investigation.reports.index`)

### Permission: `investigation.reports.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                       │
│  Investigasi & RCA                                      [+ Buat Investigasi] │
│  Kelola investigasi dan analisis root cause                                  │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari nomor, judul, nomor incident...     ]                          │  │
│  │                                                                        │  │
│  │ Status: [Semua ▾]  Investigator: [Semua ▾]  Site: [Semua ▾]           │  │
│  │ Dari:   [__/__/____]  Sampai: [__/__/____]  [Reset]                   │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Toolbar ─────────────────────────────────────────────────────────────┐  │
│  │ Menampilkan 1–15 dari 32 investigasi                  [⬇ Export CSV]  │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Table ─────────────────────────────────────────────────────────────────┐ │
│  │ Nomor        Judul              Incident     Status      Investigator   │ │
│  ├─────────────────────────────────────────────────────────────────────────┤ │
│  │ INV-0001    Analisis Kecelakaan  INC-0001    🔵Berlangsung Sari W.      │ │
│  │ INV-0002    Near Miss Lift       INC-0003    🟢Selesai     Andi P.      │ │
│  │ INV-0003    Tumpahan Kimia       INC-0005    ⚪Draft       Budi S.      │ │
│  │ INV-0004    Pintu Darurat        INC-0007    🔴Dibatalkan  Joni K.      │ │
│  │ ...                                                                     │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│  ┌─ Table (cont.) ────────────────────────────────────────────────────────┐ │
│  │ ... Investigator   Dibuat       Aksi                                      │ │
│  │ ... Sari W.       11/07/2026   [👁 Lihat]                                 │ │
│  │ ... Andi P.       10/07/2026   [👁 Lihat]                                 │ │
│  │ ... Budi S.       09/07/2026   [👁 Lihat] [✏ Edit]                       │ │
│  │ ... Joni K.       08/07/2026   [👁 Lihat]                                 │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  ┌─ Pagination ───────────────────────────────────────────────────────────┐  │
│  │                              ‹ Sebelumnya   1  2  3   Berikutnya ›      │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Wireframe — Empty State

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                       │
│  Investigasi & RCA                                      [+ Buat Investigasi] │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │                                                                      │   │
│  │                          🔍                                          │   │
│  │                                                                      │   │
│  │                   Belum ada investigasi                              │   │
│  │                                                                      │   │
│  │           Belum ada investigasi yang dibuat. Klik tombol di           │   │
│  │           bawah untuk membuat investigasi pertama.                   │   │
│  │                                                                      │   │
│  │                      [+ Buat Investigasi Pertama]                   │   │
│  │                                                                      │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Element

#### Header

| Element | Type | Detail |
|---|---|---|
| Title | `<h1>` | "Investigasi & RCA" |
| Subtitle | `<p>` | "Kelola investigasi dan analisis root cause" |
| Button "Buat Investigasi" | `<Link>` | Route: `investigation.reports.create`, permission: `investigation.reports.create` |
| Button Style | Tailwind | `bg-blue-600 text-white hover:bg-blue-700` |

#### Search Box

| Element | Type | Detail |
|---|---|---|
| Placeholder | `<input>` | "Cari nomor, judul, nomor incident..." |
| Icon | SVG | Magnifying glass icon di kiri input |
| Behavior | debounce | 300ms debounce, kirim ke server via Inertia visit |
| Param | query | `?search=keyword` |

#### Filter Dropdowns

| Filter | Label | Options | Param |
|---|---|---|---|
| Status | "Status" | Semua, Draft, Berlangsung, Selesai, Dibatalkan | `?status=` |
| Investigator | "Investigator" | Semua + list users with QHSSE role | `?investigator_id=` |
| Site | "Site" | Semua + from master Sites | `?site_id=` |
| Date Range | "Dari" / "Sampai" | Date picker untuk rentang tanggal | `?from=` `?to=` |
| Reset Button | Button | "Reset" — clear all filters | — |

#### Table Columns

| # | Column | Key | Width | Align | Badge? | Detail |
|---|---|---|---|---|---|---|
| 1 | Nomor | `investigation_number` | 120px | left | No | Link ke show page, monospace font |
| 2 | Judul | `title` | flex | left | No | Truncate dengan `max-w-xs truncate` |
| 3 | Incident | `incident.incident_number` | 120px | left | No | Link ke incident show page |
| 4 | Status | `status` | 130px | center | Yes | Lihat [Color Coding](#2-color-coding) |
| 5 | Investigator | `investigator.name` | 130px | left | No | Nama user |
| 6 | Dibuat | `created_at` | 100px | center | No | Format: `dd/mm/yy` |
| 7 | Aksi | — | 120px | center | No | Lihat di bawah |

#### Aksi Column (per row)

| Action | Icon | Permission | Condition |
|---|---|---|---|
| Lihat | 👁 | `investigation.reports.view` | Selalu tampil |
| Edit | ✏ | `investigation.reports.update` | Status = Draft atau In Progress |

#### Pagination

```
Menampilkan 1–15 dari 32 investigasi

‹ Sebelumnya   1  2  3   Berikutnya ›
```

- Menggunakan komponen Tailwind pagination standar.
- Tampilkan "Menampilkan X–Y dari Z investigasi".
- 15 item per halaman (configurable: 15/25/50).

#### Export CSV

| Element | Detail |
|---|---|
| Button | `[⬇ Export CSV]` |
| Permission | `investigation.reports.export` |
| Behavior | Export data sesuai filter aktif saat ini |
| Endpoint | `GET /investigations/export?status=...&investigator_id=...&...` |
| Response | CSV download dengan kolom: Nomor, Judul, Nomor Incident, Status, Investigator, Root Cause, Started At, Completed At, Duration, Created At |

### Inertia Props

```typescript
interface IndexProps {
    items: {
        data: Investigation[];
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
        investigator_id?: number;
        site_id?: number;
        from?: string;
        to?: string;
    };
    investigators: { id: number; name: string }[];
    sites: Site[];
    can: {
        create: boolean;
        export: boolean;
    };
}
```

---

## 4. Halaman Form — Buat/Edit Investigasi

### Route

- Create: `GET /investigations/create` (`investigation.reports.create`)
- Edit: `GET /investigations/{id}/edit` (`investigation.reports.edit`)

### Permission

- Create: `investigation.reports.create`
- Edit: `investigation.reports.update` (hanya jika status = Draft atau In Progress)

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Buat Investigasi                                                                │
│  Isi data investigasi dan analisis root cause                                    │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Umum ──────────────────────────────────────────────────┐  │
│  │  INFORMASI UMUM                                                             │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Nomor Investigasi    [Auto-generated — INV-0001              ]  ⓘ          │  │
│  │                       Nomor akan dibuat otomatis saat simpan                  │  │
│  │                                                                             │  │
│  │  Incident Terkait *  [— Cari incident... —    ▾]                            │  │
│  │                       Hanya incident dengan status under_review/investigation│  │
│  │                       Menampilkan: INC-0001 - Kecelakaan Kerja di Produksi   │  │
│  │                                                                             │  │
│  │  Judul Investigasi *  [Masukkan judul investigasi...          ]             │  │
│  │                                                                             │  │
│  │  Investigator *      [— Pilih Investigator —    ▾]                         │  │
│  │                       Default: user yang sedang login                       │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: 5-Why Analysis ───────────────────────────────────────────────────┐  │
│  │  ANALISIS 5-WHY                                                             │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  ┌─ Why 1 ──────────────────────────────────────────────────────────────┐  │  │
│  │  │ Pertanyaan *  [Mengapa kejadian terjadi?                               ]  │  │
│  │  │ Jawaban *    [Jelaskan penyebab langsung...                            ]  │  │
│  │  │ ☐ Tandai sebagai Root Cause                                            │  │
│  │  └────────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  ┌─ Why 2 ──────────────────────────────────────────────────────────────┐  │  │
│  │  │ Pertanyaan *  [Mengapa hal di atas terjadi?                           ]  │  │
│  │  │ Jawaban *    [Jelaskan lebih dalam...                                 ]  │  │
│  │  │ ☐ Tandai sebagai Root Cause                                            │  │  │
│  │  └────────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  ┌─ Why 3 ──────────────────────────────────────────────────────────────┐  │  │
│  │  │ Pertanyaan *  [Mengapa hal di atas terjadi?                           ]  │  │
│  │  │ Jawaban *    [Jelaskan lebih dalam...                                 ]  │  │
│  │  │ ☐ Tandai sebagai Root Cause                                            │  │  │
│  │  └────────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  ┌─ Why 4 ──────────────────────────────────────────────────────────────┐  │  │
│  │  │ Pertanyaan *  [Mengapa hal di atas terjadi?                           ]  │  │
│  │  │ Jawaban *    [Jelaskan lebih dalam...                                 ]  │  │
│  │  │ ☐ Tandai sebagai Root Cause                                            │  │  │
│  │  └────────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  ┌─ Why 5 ──────────────────────────────────────────────────────────────┐  │  │
│  │  │ Pertanyaan *  [Mengapa hal di atas terjadi?                           ]  │  │
│  │  │ Jawaban *    [Jelaskan root cause...                                  ]  │  │
│  │  │ ☑ Tandai sebagai Root Cause                                            │  │  │
│  │  └────────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  [+ Tambah Why 6]                                                           │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Fishbone Diagram ────────────────────────────────────────────────┐  │
│  │  DIAGRAM FISHBONE (ISHIKAWA)                                                │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  ┌─ Man (Manusia) ─────────────────────────────────────────────────────┐   │  │
│  │  │ 🟣 Kategori: Manusia                                                │   │  │
│  │  │ Penyebab:                                                            │   │  │
│  │  │  ┌──────────────────────────────────────────────────────┐  [🗑]      │   │  │
│  │  │  │ Operator tidak mendapat training SOP terbaru        │            │   │  │
│  │  │  └──────────────────────────────────────────────────────┘            │   │  │
│  │  │  ┌──────────────────────────────────────────────────────┐  [🗑]      │   │  │
│  │  │  │ Kelelahan karena lembur berlebihan                  │            │   │  │
│  │  │  └──────────────────────────────────────────────────────┘            │   │  │
│  │  │  [+ Tambah Penyebab]                                               │   │  │
│  │  └────────────────────────────────────────────────────────────────────┘   │  │
│  │                                                                             │  │
│  │  ┌─ Method (Metode) ───────────────────────────────────────────────────┐   │  │
│  │  │ 🔵 Kategori: Metode                                                 │   │  │
│  │  │ Penyebab:                                                            │   │  │
│  │  │  ┌──────────────────────────────────────────────────────┐  [🗑]      │   │  │
│  │  │  │ Prosedur lockout/tagout tidak diikuti                 │            │   │  │
│  │  │  └──────────────────────────────────────────────────────┘            │   │  │
│  │  │  [+ Tambah Penyebab]                                               │   │  │
│  │  └────────────────────────────────────────────────────────────────────┘   │  │
│  │                                                                             │  │
│  │  ┌─ Machine (Mesin) ───────────────────────────────────────────────────┐   │  │
│  │  │ 🟠 Kategori: Mesin                                                  │   │  │
│  │  │ Penyebab:                                                            │   │  │
│  │  │  ┌──────────────────────────────────────────────────────┐  [🗑]      │   │  │
│  │  │  │ Seal mesin rusak                                      │            │   │  │
│  │  │  └──────────────────────────────────────────────────────┘            │   │  │
│  │  │  [+ Tambah Penyebab]                                               │   │  │
│  │  └────────────────────────────────────────────────────────────────────┘   │  │
│  │                                                                             │  │
│  │  ┌─ Material ─────────────────────────────────────────────────────────┐   │  │
│  │  │ 🟡 Kategori: Material                                               │   │  │
│  │  │ Penyebab: (belum ada)                                               │   │  │
│  │  │  [+ Tambah Penyebab]                                               │   │  │
│  │  └────────────────────────────────────────────────────────────────────┘   │  │
│  │                                                                             │  │
│  │  ┌─ Environment (Lingkungan) ──────────────────────────────────────────┐   │  │
│  │  │ 🟢 Kategori: Lingkungan                                             │   │  │
│  │  │ Penyebab:                                                            │   │  │
│  │  │  ┌──────────────────────────────────────────────────────┐  [🗑]      │   │  │
│  │  │  │ Pencahayaan area kurang optimal                      │            │   │  │
│  │  │  └──────────────────────────────────────────────────────┘            │   │  │
│  │  │  [+ Tambah Penyebab]                                               │   │  │
│  │  └────────────────────────────────────────────────────────────────────┘   │  │
│  │                                                                             │  │
│  │  ┌─ Management (Manajemen) ────────────────────────────────────────────┐   │  │
│  │  │ 🔴 Kategori: Manajemen                                             │   │  │
│  │  │ Penyebab:                                                            │   │  │
│  │  │  ┌──────────────────────────────────────────────────────┐  [🗑]      │   │  │
│  │  │  │ Tidak ada sistem monitoring maintenance              │            │   │  │
│  │  │  └──────────────────────────────────────────────────────┘            │   │  │
│  │  │  [+ Tambah Penyebab]                                               │   │  │
│  │  └────────────────────────────────────────────────────────────────────┘   │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Faktor Kontribusi ───────────────────────────────────────────────┐  │
│  │  FAKTOR KONTRIBUSI                                                          │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  ┌─ Repeater Item 1 ────────────────────────────────────────────────────┐  │  │
│  │  │ Faktor *     [Jelaskan faktor kontribusi...              ]            │  │  │
│  │  │ Kategori *   [— Pilih Kategori —    ▾]                              │  │  │
│  │  │               ○ Man  ○ Method  ○ Machine  ○ Material               │  │  │
│  │  │               ○ Environment  ○ Management                          │  │  │
│  │  │ Dampak *    [— Pilih Dampak —    ▾]                                │  │  │
│  │  │               ○ Langsung  ○ Tidak Langsung                          │  │  │
│  │  │                                                          [🗑 Hapus]  │  │  │
│  │  └──────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  ┌─ Repeater Item 2 ────────────────────────────────────────────────────┐  │  │
│  │  │ Faktor *     [APD sepatu anti-slip tidak tersedia        ]            │  │  │
│  │  │ Kategori *   [Material ▾]                                           │  │  │
│  │  │ Dampak *    [Langsung ▾]                                             │  │  │
│  │  │                                                          [🗑 Hapus]  │  │  │
│  │  └──────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  [+ Tambah Faktor Kontribusi]                                               │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Timeline Kejadian ────────────────────────────────────────────────┐  │
│  │  TIMELINE KEJADIAN                                                          │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  ┌─ Event 1 ────────────────────────────────────────────────────────────┐  │  │
│  │  │ Waktu *     [__/__/____ __:__]                                        │  │  │
│  │  │ Event *    [Masukkan nama event...              ]                     │  │  │
│  │  │ Deskripsi  ┌────────────────────────────────────────────────┐         │  │  │
│  │  │            │ Jelaskan detail event...                       │         │  │  │
│  │  │            └────────────────────────────────────────────────┘         │  │  │
│  │  │ Sumber *   [— Pilih Sumber —    ▾]                                    │  │  │
│  │  │             ○ Laporan Insiden  ○ Keterangan Saksi                   │  │  │
│  │  │             ○ Rekaman CCTV  ○ Tinjauan Dokumen                      │  │  │
│  │  │             ○ Inspeksi Lokasi  ○ Lainnya                             │  │  │
│  │  │                                                          [🗑 Hapus]  │  │  │
│  │  └──────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  [+ Tambah Event]                                                           │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Root Cause & Recommendations ────────────────────────────────────┐  │
│  │  ROOT CAUSE & REKOMENDASI                                                   │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Root Cause *       ┌──────────────────────────────────────────────┐       │  │
│  │  (Ringkasan)         │ Ringkas root cause dari analisis 5-Why...   │       │  │
│  │                       │                                              │       │  │
│  │                       └──────────────────────────────────────────────┘       │  │
│  │                       Wajib diisi sebelum menyelesaikan investigasi          │  │
│  │                                                                             │  │
│  │  Rekomendasi *      ┌──────────────────────────────────────────────┐       │  │
│  │                       │ Rekomendasi tindakan korektif/preventif... │       │  │
│  │                       │                                              │       │  │
│  │                       │                                              │       │  │
│  │                       └──────────────────────────────────────────────┘       │  │
│  │                       Wajib diisi sebelum menyelesaikan investigasi          │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Tim Investigasi ─────────────────────────────────────────────────┐  │
│  │  TIM INVESTIGASI                                                            │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  ┌─ Anggota 1 ──────────────────────────────────────────────────────────┐  │  │
│  │  │ Anggota *   [— Cari user... —    ▾]                                  │  │  │
│  │  │ Peran *     [— Pilih Peran —    ▾]                                   │  │  │
│  │  │              ○ Lead Investigator  ○ Investigator                     │  │  │
│  │  │              ○ SME  ○ Pencatat                                       │  │  │
│  │  │                                                          [🗑 Hapus]  │  │  │
│  │  └──────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  [+ Tambah Anggota]                                                         │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Lampiran ────────────────────────────────────────────────────────┐  │
│  │  LAMPIRAN                                                                   │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  ┌─────────────────────────────────────────────────────────────────────┐   │  │
│  │  │                                                                     │   │  │
│  │  │              📁  Drag & drop file di sini                           │   │  │
│  │  │                  atau [Pilih File]                                  │   │  │
│  │  │                                                                     │   │  │
│  │  │              Maks 25MB per file. Format: jpg, png, pdf, docx, xlsx  │   │  │
│  │  │                                                                     │   │  │
│  │  └─────────────────────────────────────────────────────────────────────┘   │  │
│  │                                                                             │  │
│  │  File terunggah:                                                           │  │
│  │  ┌──────────────────────────────────────────────────────────────────┐     │  │
│  │  │ 📎 laporan_investigasi.pdf                        1.2 MB   [🗑]    │     │  │
│  │  │ 📷 foto_bukti_1.jpg                               2.3 MB   [🗑]    │     │  │
│  │  └──────────────────────────────────────────────────────────────────┘     │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Action Bar (sticky bottom) ──────────────────────────────────────────────┐  │
│  │                                                                           │  │
│  │  [← Batal]                                    [Simpan Draft]  [Mulai]   │  │
│  │                                                              (primary)    │  │
│  └───────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Section

#### Section: Informasi Umum

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Nomor Investigasi | Text (readonly) | No | — | Auto-generated saat simpan. Placeholder "Auto-generated" |
| Incident Terkait | Select (search) | Yes | `required, exists:incidents,id` | Hanya incident dengan status `under_review` atau `investigation` |
| Judul Investigasi | Text input | Yes | `required, min:5, max:255` | Placeholder: "Masukkan judul investigasi..." |
| Investigator | Select dropdown | Yes | `required, exists:users,id` | Default: user yang sedang login |

#### Section: 5-Why Analysis (Repeater)

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Pertanyaan | Text input | Yes | `required, min:5, max:500` | Auto-filled: "Mengapa hal di atas terjadi?" untuk level > 1 |
| Jawaban | Textarea | Yes | `required, min:5` | Jelaskan jawaban untuk pertanyaan ini |
| Tandai sebagai Root Cause | Checkbox | No | `boolean` | Hanya 1 level yang dapat ditandai sebagai root cause |

Repeater behavior:
- Default: 5 level (Why 1–5)
- Tambah level: klik `[+ Tambah Why 6]` (maks 7 level)
- Hapus level: klik tombol `[🗑 Hapus]` per item (minimum 1 level)
- Why 1 selalu ada, tidak bisa dihapus
- Hanya 1 checkbox "Root Cause" yang aktif pada satu waktu (radio behavior)
- Saat start, minimum 1 level wajib diisi (question + answer)

#### Section: Fishbone Diagram

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Category (per group) | Fixed label | — | — | 6 kategori tetap: Man, Method, Machine, Material, Environment, Management |
| Cause (per category) | Text input | No | `nullable, min:3, max:500` | Tambah penyebab per kategori |
| Tambah Penyebab | Button | — | — | `[+ Tambah Penyebab]` per kategori |
| Hapus Penyebab | Button | — | — | `[🗑]` per penyebab |

Fishbone behavior:
- 6 kategori selalu tampil (tidak bisa dihapus)
- Setiap kategori dapat memiliki 0–N causes
- Saat start, minimum 1 kategori harus memiliki min:1 cause
- Tampilan: 6 card vertikal, masing-masing dengan badge kategori berwarna

#### Section: Faktor Kontribusi (Repeater)

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Faktor | Text input | Yes | `required, min:5, max:500` | Jelaskan faktor kontribusi |
| Kategori | Select dropdown | Yes | `required, in:Man,Method,Machine,Material,Environment,Management` | 6 kategori fishbone |
| Dampak | Select dropdown | Yes | `required, in:direct,indirect` | Langsung / Tidak Langsung |

Repeater behavior:
- Tambah item: klik `[+ Tambah Faktor Kontribusi]`
- Hapus item: klik tombol `[🗑 Hapus]` per item
- Minimum 0 items (opsional), tidak ada maksimum
- Setiap item dalam card terpisah dengan border

#### Section: Timeline Kejadian (Repeater)

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Waktu | DateTime picker | Yes | `required, date` | Format: `dd/mm/yyyy HH:mm` |
| Event | Text input | Yes | `required, min:3, max:255` | Nama event |
| Deskripsi | Textarea | No | `nullable, max:1000` | Detail event |
| Sumber | Select dropdown | Yes | `required, in:incident_report,witness_statement,cctv_footage,document_review,site_inspection,other` | Sumber informasi event |

Repeater behavior:
- Tambah item: klik `[+ Tambah Event]`
- Hapus item: klik tombol `[🗑 Hapus]` per item
- Minimum 0 items (opsional)
- Events diurutkan berdasarkan timestamp secara otomatis

#### Section: Root Cause & Recommendations

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Root Cause | Textarea | Conditional | `required_if:action,complete` | Ringkasan root cause. Wajib sebelum complete |
| Rekomendasi | Textarea | Conditional | `required_if:action,complete` | Rekomendasi tindakan korektif. Wajib sebelum complete |

#### Section: Tim Investigasi (Repeater)

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Anggota | Select (search) | Yes | `required, exists:users,id` | Cari user berdasarkan nama/email |
| Peran | Select dropdown | Yes | `required, in:lead_investigator,investigator,subject_matter_expert,recorder` | Lead Investigator / Investigator / SME / Pencatat |

Repeater behavior:
- Tambah item: klik `[+ Tambah Anggota]`
- Hapus item: klik tombol `[🗑 Hapus]` per item
- Lead investigator tidak bisa dihapus (harus ada minimal 1 lead)
- Setiap user hanya bisa ditambahkan sekali per investigation

#### Section: Lampiran (File Upload)

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Files | File upload | No | `nullable, max:30 files, max:25600kb` | Drag & drop atau klik untuk pilih |

Accepted formats: `jpg, jpeg, png, gif, webp, pdf, doc, docx, xls, xlsx, ppt, pptx`
Maksimal: 30 file, 25MB per file
Collections: `evidence`, `report`, `attachment`
Uploaded files tampil dalam list dengan: icon, filename, size, delete button

### Action Buttons

| Button | Type | Style | Behavior |
|---|---|---|---|
| Batal | Link | `text-slate-600 hover:text-slate-900` | Redirect ke index page |
| Simpan Draft | Submit | `bg-gray-200 text-gray-800 hover:bg-gray-300` | `POST` atau `PUT` dengan `action=draft`. Tidak validasi field mandatory |
| Mulai | Submit | `bg-blue-600 text-white hover:bg-blue-700` | `POST` atau `PUT` dengan `action=start`. Validasi mandatory fields (title, incident_id, five_whys min 1, fishbone min 1 cause) |

### Edit Mode Notes

- Saat edit (status = Draft atau In Progress), semua section dapat di-edit
- Nomor investigasi tampil sebagai readonly
- Jika status = In Progress, tombol "Mulai" berubah menjadi "Simpan Perubahan"
- Jika status = Completed atau Cancelled, form read-only (hanya view)

### Inertia Props

```typescript
interface FormProps {
    investigation: Investigation | null;  // null untuk create, filled untuk edit
    incidents: { id: number; incident_number: string; title: string }[];  // incidents with status under_review/investigation
    investigators: { id: number; name: string }[];  // users with QHSSE role
    teamMembers: { id: number; name: string }[];  // all users (for team selection)
    can: {
        submit: boolean;  // can start investigation
    };
}
```

---

## 5. Halaman Show — Detail Investigasi

### Route: `GET /investigations/{id}` (`investigation.reports.show`)

### Permission: `investigation.reports.view`

### Wireframe — Desktop

```
┌───────────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                                │
│  ← Kembali ke Daftar                                                                  │
├───────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                       │
│  ┌─ Summary Card ──────────────────────────────────────────────────────────────────┐  │
│  │                                                                                 │  │
│  │  INV-0001                                    [🔵 Berlangsung]                  │  │
│  │  Analisis Kecelakaan Kerja di Area Produksi                                     │  │
│  │                                                                                 │  │
│  │  🔗 Incident: INC-0001 - Kecelakaan Kerja di Area Produksi                     │  │
│  │  👤 Investigator: Sari Wulandari (QHSSE Officer)                                │  │
│  │  📅 Dimulai: 11/07/2026 14:00   ⏱ Durasi: 3 hari                               │  │
│  │                                                                                 │  │
│  │  ┌─ Action Buttons (permission-gated) ──────────────────────────────────────┐  │  │
│  │  │  [✏ Edit]  [▶ Mulai]  [✓ Selesai]  [✗ Batalkan]  [⬇ Export CSV]        │  │  │
│  │  └─────────────────────────────────────────────────────────────────────────┘  │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Detail Layout: 2 columns ──────────────────────────────────────────────────────┐  │
│  │                                                                                  │  │
│  │  ┌─ Left Column (2/3) ─────────────────────────────────┐  ┌─ Right Column (1/3) ──────────────────┐ │  │
│  │  │                                                     │  │                                     │ │  │
│  │  │  ANALISIS 5-WHY                                     │  │  ┌─ INFO INVESTIGASI ─────────────┐ │ │  │
│  │  │  ─────────────────────────────────────────────      │  │  │ Nomor:    INV-0001             │ │ │  │
│  │  │                                                     │  │  │ Status:   🔵 Berlangsung       │ │ │  │
│  │  │  Why 1: Mengapa kecelakaan terjadi?                 │  │  │ Dibuat:   11/07/2026 09:00     │ │ │  │
│  │  │  → Pekerja terpeleset di lantai basah.             │  │  │ Dimulai:  11/07/2026 14:00     │ │ │  │
│  │  │                                                     │  │  │ Selesai:  —                     │ │ │  │
│  │  │  Why 2: Mengapa lantai basah?                       │  │  └─────────────────────────────────┘ │ │  │
│  │  │  → Terdapat tumpahan oli dari mesin.               │  │  │                                     │ │  │
│  │  │                                                     │  │  ┌─ INVESTIGATOR ─────────────────┐ │ │  │
│  │  │  Why 3: Mengapa terjadi tumpahan oli?               │  │  │ 👤 Sari Wulandari               │ │ │  │
│  │  │  → Seal pada mesin rusak dan tidak terdeteksi.     │  │  │    QHSSE Officer                │ │ │  │
│  │  │                                                     │  │  │    sari.w@company.com           │ │ │  │
│  │  │  Why 4: Mengapa seal rusak tidak terdeteksi?       │  │  └─────────────────────────────────┘ │ │  │
│  │  │  → Maintenance preventif tidak sesuai jadwal.      │  │  │                                     │ │  │
│  │  │                                                     │  │  ┌─ INCIDENT TERKAIT ────────────┐ │ │  │
│  │  │  Why 5: Mengapa maintenance tidak sesuai jadwal?   │  │  │ INC-0001                       │ │ │  │
│  │  │  → Tidak ada sistem monitoring maintenance.        │  │  │ Kecelakaan Kerja di Produksi   │ │ │  │
│  │  │  ★ ROOT CAUSE IDENTIFIED                          │  │  │ 🔴 Critical  🟡 Under Review   │ │ │  │
│  │  │                                                     │  │  │ [👁 Lihat Incident]            │ │ │  │
│  │  │  ┌─ FISHBONE DIAGRAM ──────────────────────────┐   │  │ └─────────────────────────────────┘ │ │  │
│  │  │  │                                                │  │  │                                     │ │  │
│  │  │  │  🟣 Manusia:                                   │  │  │  ┌─ TIM INVESTIGASI ──────────────┐ │ │  │
│  │  │  │    • Operator tidak training SOP terbaru     │  │  │  │ 👤 Sari W.  🔵 Lead Investigator│ │ │  │
│  │  │  │    • Kelelahan karena lembur                  │  │  │  │ 👤 Andi P.  ⚪ Investigator      │ │ │  │
│  │  │  │                                                │  │  │  │ 👤 Budi S.  🟣 SME              │ │ │  │
│  │  │  │  🔵 Metode:                                    │  │  │  └─────────────────────────────────┘ │ │  │
│  │  │  │    • Prosedur LOTO tidak diikuti              │  │  │                                     │ │  │
│  │  │  │                                                │  │  │  ┌─ LAMPIRAN ──────────────────────┐ │ │  │
│  │  │  │  🟠 Mesin:                                     │  │  │  │                                 │ │ │  │
│  │  │  │    • Seal mesin rusak                          │  │  │  │ 📎 laporan_investigasi.pdf       │ │ │  │
│  │  │  │                                                │  │  │  │    1.2 MB   [⬇ Download]      │ │ │  │
│  │  │  │  🟡 Material: (tidak ada)                     │  │  │  │                                 │ │ │  │
│  │  │  │                                                │  │  │  │ 📷 foto_bukti_1.jpg              │ │ │  │
│  │  │  │  🟢 Lingkungan:                                │  │  │  │    2.3 MB   [⬇ Download]      │ │ │  │
│  │  │  │    • Pencahayaan area kurang optimal          │  │  │  │                                 │ │ │  │
│  │  │  │                                                │  │  │  └─────────────────────────────────┘ │ │  │
│  │  │  │  🔴 Manajemen:                                 │  │  │                                     │ │  │
│  │  │  │    • Tidak ada sistem monitoring maintenance   │  │  │  ┌─ DURASI ────────────────────────┐ │ │  │
│  │  │  └────────────────────────────────────────────────┘  │  │  │ Dimulai:  11/07/2026           │ │ │  │
│  │  │                                                     │  │  │ Hari ke: 3 (berlangsung)       │ │ │  │
│  │  │  FAKTOR KONTRIBUSI                                 │  │  └─────────────────────────────────┘ │ │  │
│  │  │  ─────────────────────────────────────────────      │  │                                     │ │  │
│  │  │  • [🟡 Material] APD sepatu anti-slip tidak        │  │  ┌─ REKOMENDASI CAPA ──────────────┐ │ │  │
│  │  │    tersedia [🔴 Langsung]                          │  │  │                                 │ │ │  │
│  │  │  • [🟣 Manusia] Operator baru transfer [🟡 Tidak   │  │  │ [+ Buat CAPA dari Rekomendasi] │ │ │  │
│  │  │    Langsung]                                       │  │  │                                 │ │ │  │
│  │  │                                                     │  │  │ (Phase 3 — link ke CAPA records)│ │ │  │
│  │  │  TIMELINE KEJADIAN                                  │  │  │                                 │ │ │  │
│  │  │  ─────────────────────────────────────────────      │  │  └─────────────────────────────────┘ │ │  │
│  │  │                                                     │  │                                     │ │  │
│  │  │  ●━━ 14:00  Mesin mulai beroperasi                  │  │                                     │ │  │
│  │  │  │        Shift pagi dimulai, operator menyalakan   │  │                                     │ │  │
│  │  │  │        mesin produksi #3. [Keterangan Saksi]     │  │                                     │ │  │
│  │  │  │                                                   │  │                                     │ │  │
│  │  │  ●━━ 14:15  Kebocoran oli terdeteksi                │  │                                     │ │  │
│  │  │  │        Tampak oli menetes dari bagian seal.      │  │                                     │ │  │
│  │  │  │        [Rekaman CCTV]                             │  │                                     │ │  │
│  │  │  │                                                   │  │                                     │ │  │
│  │  │  ●━━ 14:30  Kecelakaan terjadi                       │  │                                     │ │  │
│  │  │  │        Pekerja terpeleset di lantai basah.        │  │                                     │ │  │
│  │  │  │        [Laporan Insiden]                         │  │                                     │ │  │
│  │  │  │                                                   │  │                                     │ │  │
│  │  │  ●━━ 14:32  Pertolongan pertama                      │  │                                     │ │  │
│  │  │           First responder memberikan pertolongan.    │  │                                     │ │  │
│  │  │           [Keterangan Saksi]                        │  │                                     │ │  │
│  │  │                                                     │  │                                     │ │  │
│  │  │  ROOT CAUSE                                        │  │                                     │ │  │
│  │  │  ─────────────────────────────────────────────      │  │                                     │ │  │
│  │  │  Tidak ada sistem monitoring maintenance yang       │  │                                     │ │  │
│  │  │  efektif, menyebabkan seal mesin rusak tidak        │  │                                     │ │  │
│  │  │  terdeteksi, menyebabkan tumpahan oli,              │  │                                     │ │  │
│  │  │  menyebabkan lantai basah, menyebabkan               │  │                                     │ │  │
│  │  │  pekerja terpeleset.                               │  │                                     │ │  │
│  │  │                                                     │  │                                     │ │  │
│  │  │  REKOMENDASI                                       │  │                                     │ │  │
│  │  │  ─────────────────────────────────────────────      │  │                                     │ │  │
│  │  │  1. Implementasi sistem monitoring maintenance      │  │                                     │ │  │
│  │  │     terjadwal dengan notifikasi otomatis.          │  │                                     │ │  │
│  │  │  2. Training ulang SOP untuk semua operator.       │  │                                     │ │  │
│  │  │  3. Pemasangan sensor kebocoran oli pada mesin.    │  │                                     │ │  │
│  │  │  4. Penyediaan APD sepatu anti-slip di area         │  │                                     │ │  │
│  │  │     produksi.                                      │  │                                     │ │  │
│  │  │                                                     │  │                                     │ │  │
│  │  └─────────────────────────────────────────────────────┘  └───────────────────────────────────┘ │  │
│  │                                                                                  │  │
│  └──────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Workflow Timeline ──────────────────────────────────────────────────────────────┐  │
│  │  RIWAYAT WORKFLOW                                                                 │  │
│  │  ─────────────────────────────────────────────────────────────────────────────    │  │
│  │                                                                                   │  │
│  │  ●━━━ Draft                     11/07/2026 09:00   Sari Wulandari                │  │
│  │  │     Investigasi dibuat sebagai draft                                            │  │
│  │  │                                                                                 │  │
│  │  ●━━━ In Progress              11/07/2026 14:00   Sari Wulandari                │  │
│  │  │     Investigasi dimulai                                                         │  │
│  │  │                                                                                 │  │
│  │  ○━━━ (Menunggu) Completed                                                         │  │
│  │                                                                                   │  │
│  └───────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Comments Section ───────────────────────────────────────────────────────────────┐  │
│  │  KOMENTAR (2)                                                                     │  │
│  │  ─────────────────────────────────────────────────────────────────────────────    │  │
│  │                                                                                   │  │
│  │  ┌─ Comment 1 ───────────────────────────────────────────────────────────────┐   │  │
│  │  │ 👤 Andi P. (Investigator)                               11/07 14:30     │   │  │
│  │  │ Hasil 5-Why sudah saya review. Root cause teridentifikasi dengan jelas.    │   │  │
│  │  └───────────────────────────────────────────────────────────────────────────┘   │  │
│  │                                                                                   │  │
│  │  ┌─ Add Comment ─────────────────────────────────────────────────────────────┐   │  │
│  │  │ [Tulis komentar...                                          ]  [Kirim]    │   │  │
│  │  └───────────────────────────────────────────────────────────────────────────┘   │  │
│  │                                                                                   │  │
│  └───────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Activity Log ───────────────────────────────────────────────────────────────────┐  │
│  │  LOG AKTIVITAS                                                                    │  │
│  │  ─────────────────────────────────────────────────────────────────────────────    │  │
│  │                                                                                   │  │
│  │  📝 11/07/2026 09:00  Sari W.   Membuat investigasi (draft)                      │  │
│  │  ▶  11/07/2026 14:00  Sari W.   Memulai investigasi                              │  │
│  │  👤 11/07/2026 14:05  Sari W.   Menambah anggota tim: Andi P. (Investigator)    │  │
│  │  👤 11/07/2026 14:10  Sari W.   Menambah anggota tim: Budi S. (SME)             │  │
│  │  💬 11/07/2026 14:30  Andi P.  Menambah komentar                                 │  │
│  │  📎 11/07/2026 15:00  Sari W.   Mengunggah file: laporan_investigasi.pdf         │  │
│  │                                                                                   │  │
│  └───────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
└───────────────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Section

#### Summary Card

| Element | Type | Detail |
|---|---|---|
| Nomor Investigasi | `<h2>` | Monospace font, contoh: `INV-0001` |
| Judul | `<h1>` | Judul investigasi |
| Status Badge | Badge | Lihat [Color Coding](#2-color-coding) |
| Incident Link | `<Link>` | Link ke incident show page |
| Investigator | Text | Nama + role |
| Dimulai | Text | "Dimulai: dd/mm/yyyy HH:mm" |
| Durasi | Text | "Durasi: X hari" atau "Hari ke: X (berlangsung)" |

#### Action Buttons (Permission-Gated)

| Button | Permission | Condition (status) | Route |
|---|---|---|---|
| Edit | `investigation.reports.update` | status = Draft atau In Progress | `investigation.reports.edit` |
| Mulai | `investigation.reports.submit` | status = Draft | `POST investigation.reports.start` |
| Selesai | `investigation.reports.close` | status = In Progress | `POST investigation.reports.complete` |
| Batalkan | `investigation.reports.update` | status = Draft atau In Progress | `POST investigation.reports.cancel` |
| Export CSV | `investigation.reports.export` | Selalu tersedia | `GET investigation.reports.export` |

Button styling:
- Primary action: `bg-blue-600 text-white hover:bg-blue-700`
- Secondary action: `bg-gray-200 text-gray-800 hover:bg-gray-300`
- Destructive (Batalkan): `bg-red-600 text-white hover:bg-red-700`

Untuk Selesai dan Batalkan, tampilkan modal dialog untuk input alasan sebelum konfirmasi.

#### 5-Why Analysis Section

Menampilkan tabel 5-Why secara vertikal:

```
Why 1: Mengapa kecelakaan terjadi?
→ Pekerja terpeleset di lantai basah.

Why 2: Mengapa lantai basah?
→ Terdapat tumpahan oli dari mesin.

...

Why 5: Mengapa maintenance tidak sesuai jadwal?
→ Tidak ada sistem monitoring maintenance.
★ ROOT CAUSE IDENTIFIED
```

- Level dengan `is_root_cause = true` disorot dengan badge "★ ROOT CAUSE"
- Background highlight: `bg-yellow-50 dark:bg-yellow-900/20`

#### Fishbone Diagram Section

Menampilkan 6 kategori fishbone dengan daftar causes:

```
🟣 Manusia:
  • Operator tidak training SOP terbaru
  • Kelelahan karena lembur

🔵 Metode:
  • Prosedur LOTO tidak diikuti

🟠 Mesin:
  • Seal mesin rusak

🟡 Material:
  (tidak ada penyebab teridentifikasi)

🟢 Lingkungan:
  • Pencahayaan area kurang optimal

🔴 Manajemen:
  • Tidak ada sistem monitoring maintenance
```

- Setiap kategori dalam card dengan border berwarna sesuai kategori
- Kategori tanpa cause menampilkan "(tidak ada penyebab teridentifikasi)"

#### Contributing Factors Section

Menampilkan list faktor kontribusi dengan badge:

```
• [🟡 Material] APD sepatu anti-slip tidak tersedia [🔴 Langsung]
• [🟣 Manusia] Operator baru transfer dari department lain [🟡 Tidak Langsung]
```

#### Timeline Events Section

Menampilkan timeline vertikal:

```
●━━ 14:00  Mesin mulai beroperasi
│        Shift pagi dimulai... [Keterangan Saksi]
│
●━━ 14:15  Kebocoran oli terdeteksi
│        Tampak oli menetes... [Rekaman CCTV]
│
●━━ 14:30  Kecelakaan terjadi
│        Pekerja terpeleset... [Laporan Insiden]
```

- `●` = event marker, `text-blue-600`
- Timestamp format: `HH:mm` (atau `dd/mm HH:mm` jika spanning multiple days)
- Source badge di sebelah deskripsi

#### Root Cause Section

Menampilkan text `root_cause` dengan styling berbeda:

```
┌─ ROOT CAUSE ──────────────────────────────────────────────┐
│                                                            │
│  Tidak ada sistem monitoring maintenance yang efektif,    │
│  menyebabkan seal mesin rusak tidak terdeteksi...         │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

- Background: `bg-yellow-50 dark:bg-yellow-900/20`
- Border: `border-yellow-200 dark:border-yellow-800`

#### Recommendations Section

Menampilkan text `recommendations` sebagai numbered list:

```
REKOMENDASI
─────────────────────────────────────────────────────────────
1. Implementasi sistem monitoring maintenance terjadwal...
2. Training ulang SOP untuk semua operator.
3. Pemasangan sensor kebocoran oli pada mesin.
4. Penyediaan APD sepatu anti-slip di area produksi.
```

- Tombol `[+ Buat CAPA dari Rekomendasi]` (Phase 3, disabled jika Phase 3 belum aktif)

#### Workflow Timeline

Menampilkan riwayat status record secara vertikal:

```
●━━━ Draft                     11/07/2026 09:00  Sari W.
│     Investigasi dibuat sebagai draft
│
●━━━ In Progress              11/07/2026 14:00  Sari W.
│     Investigasi dimulai
│
○━━━ (Menunggu) Completed
```

- `●` = completed step (filled circle, `text-green-600`)
- `○` = pending step (hollow circle, `text-gray-400`)
- Data dari `workflow_histories` table (module_name = 'investigation')

#### Comments Section

| Element | Detail |
|---|---|
| Header | "KOMENTAR (N)" dengan count |
| Comment Card | Avatar/initial, nama, role, timestamp, body text |
| Add Comment | Textarea + "Kirim" button |
| Permission | User dengan `investigation.reports.view` dapat melihat, `investigation.reports.update` dapat menambah komentar |
| Endpoint | `POST /investigations/{id}/comments` |
| Data source | `comments` table (module_name = 'investigation', reference_id = investigation.id) |

#### Activity Log

| Element | Detail |
|---|---|
| Header | "LOG AKTIVITAS" |
| Entry | Icon + timestamp + actor + description |
| Icons | 📝 create, ▶ start, ✓ complete, ✗ cancel, 💬 comment, 📎 file, 👤 team_add, 📤 export |
| Data source | `activity_logs` table (module_name = 'investigation', reference_id = investigation.id) |
| Permission | `investigation.reports.view` (read-only) |

### Inertia Props

```typescript
interface ShowProps {
    investigation: Investigation & {
        incident: Incident & {
            site: Site;
            severity: Severity;
            reporter: User;
        };
        investigator: User;
        team_members: (User & { pivot: { role: string } })[];
        files: ManagedFile[];
        comments: Comment[];
        activities: ActivityLog[];
        workflow_histories: WorkflowHistory[];
    };
    can: {
        update: boolean;
        submit: boolean;   // start
        close: boolean;    // complete
        export: boolean;
    };
    available_transitions: {
        action_key: string;
        action_label: string;
        requires_reason: boolean;
    }[];
}
```

---

## 6. Mobile Responsive

### Breakpoints

Tailwind default breakpoints:

| Prefix | Min-width | Deskripsi |
|---|---|---|
| `sm` | 640px | Small phones (landscape) |
| `md` | 768px | Tablets |
| `lg` | 1024px | Desktop |
| `xl` | 1280px | Large desktop |

### Index Page — Mobile

```
┌──────────────────────────┐
│  Investigasi & RCA       │
│           [+ Buat]       │
├──────────────────────────┤
│ [🔍 Cari...]             │
│ [Status ▾] [Site ▾]     │
│ [Dari] [Sampai] [Reset]  │
├──────────────────────────┤
│ 1–15 dari 32  [⬇ CSV]   │
├──────────────────────────┤
│ ┌──────────────────────┐ │
│ │ INV-0001            │ │
│ │ Analisis Kecelakaan  │ │
│ │ INC-0001             │ │
│ │ [🔵Berlangsung]      │ │
│ │ 11/07  Sari W.       │ │
│ │              [👁]    │ │
│ └──────────────────────┘ │
│ ┌──────────────────────┐ │
│ │ INV-0002            │ │
│ │ Near Miss Lift       │ │
│ │ INC-0003             │ │
│ │ [🟢Selesai]          │ │
│ │ 10/07  Andi P.       │ │
│ │              [👁]    │ │
│ └──────────────────────┘ │
├──────────────────────────┤
│      ‹  1  2  3  ›      │
└──────────────────────────┘
```

Perubahan pada mobile:
- **Table → Card list**: Setiap row berubah menjadi card vertikal.
- **Filter**: Dropdown wrap ke baris berikutnya.
- **Search**: Full width, di atas filter.
- **Pagination**: Tombol lebih kecil.
- **Button "Buat Investigasi"**: Label disingkat menjadi "Buat" atau icon-only `+`.

### Form Page — Mobile

```
┌──────────────────────────┐
│  ← Buat Investigasi      │
├──────────────────────────┤
│                          │
│  INFORMASI UMUM          │
│  ─────────────────       │
│  Nomor: Auto-generated   │
│  Incident * [Cari ▾]    │
│  Judul *                 │
│  [......................]│
│  Investigator *          │
│  [Pilih ▾]               │
│                          │
│  ANALISIS 5-WHY          │
│  ─────────────────       │
│  Why 1                   │
│  Pertanyaan *            │
│  [......................]│
│  Jawaban *               │
│  [......................]│
│  ☐ Root Cause            │
│                          │
│  Why 2                   │
│  Pertanyaan *            │
│  [......................]│
│  Jawaban *               │
│  [......................]│
│  ☐ Root Cause            │
│                          │
│  [+ Tambah Why]          │
│                          │
│  FISHBONE                │
│  ─────────────────       │
│  🟣 Manusia              │
│  • Operator tidak...     │
│  [+ Tambah Penyebab]     │
│  🔵 Metode               │
│  • LOTO tidak diikuti    │
│  [+ Tambah Penyebab]     │
│  🟠 Mesin                │
│  (belum ada)             │
│  [+ Tambah Penyebab]     │
│  ...                     │
│                          │
│  FAKTOR KONTRIBUSI       │
│  ─────────────────       │
│  ┌──────────────────┐    │
│  │ Faktor [.......]  │    │
│  │ Kategori [Pilih▾] │    │
│  │ Dampak [Pilih ▾]  │    │
│  │           [🗑]    │    │
│  └──────────────────┘    │
│  [+ Tambah Faktor]       │
│                          │
│  TIMELINE                │
│  ─────────────────       │
│  ┌──────────────────┐    │
│  │ Waktu [__/__/__]  │    │
│  │ Event [.........]  │    │
│  │ Sumber [Pilih ▾]  │    │
│  │           [🗑]    │    │
│  └──────────────────┘    │
│  [+ Tambah Event]        │
│                          │
│  ROOT CAUSE & REKOMENDASI│
│  ─────────────────       │
│  Root Cause              │
│  ┌──────────────────┐    │
│  │                  │    │
│  └──────────────────┘    │
│  Rekomendasi             │
│  ┌──────────────────┐    │
│  │                  │    │
│  └──────────────────┘    │
│                          │
│  TIM INVESTIGASI         │
│  ─────────────────       │
│  ┌──────────────────┐    │
│  │ Anggota [Cari ▾]  │    │
│  │ Peran   [Pilih ▾] │    │
│  │           [🗑]    │    │
│  └──────────────────┘    │
│  [+ Tambah Anggota]      │
│                          │
│  LAMPIRAN                │
│  ─────────────────       │
│  ┌──────────────────┐    │
│  │  📁 Drop here     │    │
│  │  [Pilih File]     │    │
│  └──────────────────┘    │
│  📎 file.pdf [🗑]        │
│                          │
├──────────────────────────┤
│ [Batal] [Draft] [Mulai] │ ← sticky bottom
└──────────────────────────┘
```

Perubahan pada mobile:
- **Layout**: Single column. Semua section ditumpuk vertikal.
- **5-Why**: Setiap level dalam card terpisah, full width.
- **Fishbone**: 6 kategori ditumpuk vertikal (tidak side-by-side).
- **Repeater**: Setiap item card full width, tombol hapus lebih kecil (icon-only).
- **Action bar**: Sticky di bawah layar.

### Show Page — Mobile

```
┌──────────────────────────┐
│  ← Kembali               │
├──────────────────────────┤
│                          │
│  INV-0001                │
│  [🔵 Berlangsung]        │
│  Analisis Kecelakaan     │
│  Kerja di Area Produksi  │
│                          │
│  🔗 INC-0001             │
│  👤 Sari W. (QHSSE Off)  │
│  📅 11/07/2026 14:00     │
│  ⏱ Hari ke: 3            │
│                          │
│  [✏ Edit] [✓ Selesai]   │
│  [✗ Batalkan]            │
├──────────────────────────┤
│  ANALISIS 5-WHY          │
│  ─────────────────       │
│  Why 1: Mengapa?         │
│  → Pekerja terpeleset    │
│                          │
│  Why 2: Mengapa basah?   │
│  → Tumpahan oli           │
│                          │
│  ...                     │
│                          │
│  Why 5: Mengapa?         │
│  → Tidak ada monitoring  │
│  ★ ROOT CAUSE            │
│                          │
├──────────────────────────┤
│  FISHBONE                │
│  ─────────────────       │
│  🟣 Manusia              │
│   • Operator tidak...    │
│  🔵 Metode               │
│   • LOTO tidak diikuti   │
│  🟠 Mesin                │
│   • Seal rusak           │
│  ...                     │
│                          │
├──────────────────────────┤
│  FAKTOR KONTRIBUSI       │
│  ─────────────────       │
│  • [🟡 Material] APD...   │
│    [🔴 Langsung]         │
│                          │
├──────────────────────────┤
│  TIMELINE                │
│  ─────────────────       │
│  ● 14:00 Mesin mulai     │
│  ● 14:15 Kebocoran oli   │
│  ● 14:30 Kecelakaan      │
│  ● 14:32 Pertolongan     │
│                          │
├──────────────────────────┤
│  ROOT CAUSE              │
│  ─────────────────       │
│  Tidak ada sistem...     │
│                          │
│  REKOMENDASI             │
│  ─────────────────       │
│  1. Implementasi...      │
│  2. Training ulang...    │
│  3. Pemasangan sensor... │
│  4. Penyediaan APD...    │
│                          │
├──────────────────────────┤
│  TIM INVESTIGASI         │
│  ─────────────────       │
│  👤 Sari W. 🔵Lead       │
│  👤 Andi P. ⚪Investigator│
│  👤 Budi S. 🟣SME        │
│                          │
├──────────────────────────┤
│  LAMPIRAN                │
│  ─────────────────       │
│  📎 laporan.pdf  [⬇]    │
│  📷 foto_1.jpg   [⬇]    │
│                          │
├──────────────────────────┤
│  WORKFLOW TIMELINE       │
│  ─────────────────       │
│  ● Draft    11/07 09:00  │
│  ● Progress 11/07 14:00  │
│  ○ Completed             │
│                          │
├──────────────────────────┤
│  KOMENTAR (2)            │
│  ─────────────────       │
│  👤 Andi P. 11/07 14:30  │
│  Hasil 5-Why sudah...    │
│  [Tulis komentar...]     │
│  [Kirim]                 │
│                          │
├──────────────────────────┤
│  LOG AKTIVITAS           │
│  ─────────────────       │
│  📝 09:00 Sari — Create  │
│  ▶  14:00 Sari — Start   │
│  👤 14:05 Sari — Team    │
│  💬 14:30 Andi — Comment │
│  📎 15:00 Sari — Upload  │
│                          │
└──────────────────────────┘
```

Perubahan pada mobile:
- **2-column layout → 1-column**: Semua section ditumpuk vertikal.
- **Fishbone**: 6 kategori dalam list vertikal (tidak grid).
- **Action buttons**: Wrap ke 2 baris jika perlu.
- **5-Why**: Setiap level compact, answer di bawah question.
- **Timeline**: Compact vertical, hanya timestamp + event name.

---

## Component List

Berikut adalah komponen React yang perlu dibuat untuk modul ini:

| # | Component Name | File Path | Description |
|---|---|---|---|
| 1 | `InvestigationIndex` | `Pages/Modules/Investigation/Index.tsx` | Halaman list investigasi |
| 2 | `InvestigationForm` | `Pages/Modules/Investigation/Form.tsx` | Halaman form create/edit |
| 3 | `InvestigationShow` | `Pages/Modules/Investigation/Show.tsx` | Halaman detail investigasi |
| 4 | `FiveWhyTable` | `components/Investigation/FiveWhyTable.tsx` | Tabel 5-Why (form + display) |
| 5 | `FishboneDiagram` | `components/Investigation/FishboneDiagram.tsx` | Fishbone diagram (form + display) |
| 6 | `ContributingFactors` | `components/Investigation/ContributingFactors.tsx` | List faktor kontribusi (form + display) |
| 7 | `TimelineEvents` | `components/Investigation/TimelineEvents.tsx` | Timeline kejadian (form + display) |
| 8 | `TeamMembers` | `components/Investigation/TeamMembers.tsx` | Tim investigasi (form + display) |
| 9 | `InvestigationStatusBadge` | `components/Investigation/StatusBadge.tsx` | Status badge |
| 10 | `FishboneCategoryBadge` | `components/Investigation/FishboneCategoryBadge.tsx` | Fishbone category badge |
