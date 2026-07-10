# UI Pages — Permit to Work

Spesifikasi wireframe halaman UI untuk modul Permit to Work.

Semua label menggunakan Bahasa Indonesia. Tech stack: Laravel 12 + Inertia React + TypeScript + Tailwind CSS.

---

## Daftar Isi

1. [Navigasi](#1-navigasi)
2. [Color Coding](#2-color-coding)
3. [Halaman Index — Daftar Izin Kerja](#3-halaman-index--daftar-izin-kerja)
4. [Halaman Form — Buat/Edit Izin Kerja](#4-halaman-form--buatedit-izin-kerja)
5. [Halaman Show — Detail Izin Kerja](#5-halaman-show--detail-izin-kerja)
6. [Mobile Responsive](#6-mobile-responsive)

---

## 1. Navigasi

### Penempatan Menu

Tambahkan item baru pada group `Modul QHSSE` di array `menuGroups` pada `AuthenticatedLayout.tsx`:

```typescript
const menuGroups: { label: string; items: MenuItem[] }[] = [
    {
        label: 'Core',
        items: [
            { label: 'Dashboard', routeName: 'dashboard', active: 'dashboard' },
            // ... existing Core items
        ],
    },
    {
        label: 'Modul QHSSE',
        items: [
            { label: 'Laporan Insiden', routeName: 'incident-reporting.index', active: 'incident-reporting.*', permission: 'incident-reporting.view' },
            // ... other modules
            { label: 'Izin Kerja', routeName: 'permits.index', active: 'permits.*', permission: 'permit.work.view' },
        ],
    },
    {
        label: 'Masters',
        // ... existing Masters items
    },
    {
        label: 'Admin',
        // ... existing Admin items
    },
];
```

### Wireframe Navigasi (Desktop)

```
┌──────────────────────────────────────────────────────────────────────┐
│  [Logo] QHSSE   Core ▾   Modul QHSSE ▾   Masters ▾   Admin ▾  [User]│
│                        ┌──────────────────────┐                       │
│                        │ Laporan Insiden      │                       │
│                        │ Izin Kerja           │                       │
│                        │ ...                  │                       │
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
│   Sites              │
│   ...                │
│                      │
│  MODUL QHSSE         │
│   Laporan Insiden    │
│   Izin Kerja         │
│   ...                │
│                      │
│  MASTERS             │
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

Menu hanya tampil jika user memiliki permission `permit.work.view`. Filtering dilakukan via `auth.permissions` pada layout (sudah ada mekanisme `permissions.has(item.permission)`).

---

## 2. Color Coding

### Permit Type Badge

| Type | Tailwind Class | Preview |
|---|---|---|
| Hot Work | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔥 Hot Work` |
| Working at Height | `bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200` | `🧗 Working at Height` |
| Confined Space | `bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200` | `🕳️ Confined Space` |
| Electrical | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `⚡ Electrical` |
| Excavation | `bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200` | `⛏️ Excavation` |
| Lifting | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | `🏗️ Lifting` |
| Other | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | `📋 Other` |

### Status Badge

| Status | Tailwind Class | Preview |
|---|---|---|
| Draft | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | `⚪ Draft` |
| Submitted | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | `🔵 Submitted` |
| Under Review | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 Under Review` |
| Approved | `bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200` | `🟣 Approved` |
| Active | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Active` |
| Closed | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `✅ Closed` |
| Rejected | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Rejected` |

### Validity Status Badge

| Validity Status | Tailwind Class | Preview | Condition |
|---|---|---|---|
| Active | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Aktif` | status='active' AND now ≤ end_datetime |
| Expired | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Kedaluwarsa` | status='active' AND now > end_datetime |
| Expiring Soon | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 Akan Berakhir` | status='active' AND end_datetime - now ≤ 24h |
| Not Started | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | `⚪ Belum Aktif` | status NOT IN ('active','closed') |

### Risk Level Badge

| Risk Level | Tailwind Class | Preview |
|---|---|---|
| Critical | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Critical` |
| High | `bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200` | `🟠 High` |
| Medium | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 Medium` |
| Low | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | `🔵 Low` |

### Komponen Badge (Reusable)

```tsx
// Komponen: components/Badge.tsx
type BadgeColor = 'gray' | 'blue' | 'yellow' | 'green' | 'red' | 'orange' | 'purple' | 'indigo' | 'amber';

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
        amber:  'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
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

const permitTypeColors: Record<string, BadgeColor> = {
    hot_work:           'red',
    working_at_height:  'orange',
    confined_space:     'purple',
    electrical:         'yellow',
    excavation:         'amber',
    lifting:            'blue',
    other:              'gray',
};

const permitTypeLabels: Record<string, string> = {
    hot_work:           'Hot Work',
    working_at_height:  'Working at Height',
    confined_space:     'Confined Space',
    electrical:         'Electrical',
    excavation:         'Excavation',
    lifting:            'Lifting',
    other:              'Other',
};

const statusColors: Record<string, BadgeColor> = {
    draft:         'gray',
    submitted:     'blue',
    under_review:  'yellow',
    approved:      'indigo',
    active:        'green',
    closed:        'green',
    rejected:      'red',
};

const statusLabels: Record<string, string> = {
    draft:         'Draft',
    submitted:     'Submitted',
    under_review:  'Under Review',
    approved:      'Approved',
    active:        'Active',
    closed:        'Closed',
    rejected:      'Rejected',
};

const riskLevelColors: Record<string, BadgeColor> = {
    critical: 'red',
    high:     'orange',
    medium:   'yellow',
    low:      'blue',
};
```

---

## 3. Halaman Index — Daftar Izin Kerja

### Route: `GET /permits` (`permits.index`)

### Permission: `permit.work.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Izin Kerja                                               [+ Buat Izin Kerja]   │
│  Kelola izin kerja untuk aktivitas berisiko tinggi                              │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Summary Cards ────────────────────────────────────────────────────────────┐  │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐                  │  │
│  │  │ 🟢 Aktif │  │🟡 Akan   │  │🔴 Kedalu-│  │⚪ Draft/ │                  │  │
│  │  │    12    │  │Berakhir  │  │  warsa   │  │ Pending  │                  │  │
│  │  │          │  │    3     │  │    2     │  │    8     │                  │  │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────┘                  │  │
│  └──────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari nomor, judul, lokasi kerja...      ]                              │  │
│  │                                                                            │  │
│  │ Jenis: [Semua ▾]    Status: [Semua ▾]    Validitas: [Semua ▾]            │  │
│  │ Site:  [Semua ▾]    Contractor: [Semua ▾]                                 │  │
│  │ Dari:  [__/__/____]  Sampai: [__/__/____]  [Reset]                       │  │
│  └────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Toolbar ─────────────────────────────────────────────────────────────────┐  │
│  │ Menampilkan 1–15 dari 47 izin kerja                 [⬇ Export CSV]       │  │
│  └────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Table ─────────────────────────────────────────────────────────────────────┐ │
│  │ Nomor         Judul              Jenis        Status     Validitas  Periode│ │
│  ├──────────────────────────────────────────────────────────────────────────────┤ │
│  │ PTW-JKT-0001  Pengelasan Strip  🔥Hot Work  🟡Under Rev ⚪Belum    11/07 14:00│ │
│  │ PTW-JKT-0002  Scaffold Tower    🧗Height     🟢Active   🟢Aktif    11/07 08:00│ │
│  │ PTW-SBY-0003  Tank Cleaning     🕳️Confined   🟢Active   🟡Akan     11/07 06:00│
│  │                                                                Berakhir       │
│  │ PTW-JKT-0004  Panel Upgrade    ⚡Electrical  🟣Approved  ⚪Belum    11/07 16:00│ │
│  │ PTW-SBY-0005  Trench Digging  ⛏️Excavation  🔴Rejected  ⚪Belum    10/07 09:00│ │
│  │ ...                                                                         │ │
│  └──────────────────────────────────────────────────────────────────────────────┘ │
│  ┌─ Table (cont.) ────────────────────────────────────────────────────────────┐ │
│  │ ... Periode      Durasi   Site       Contractor      Aksi                  │ │
│  │ ... 11/07 18:00   4 jam   JKT Plant  PT Maju Jaya   [👁 Lihat]            │ │
│  │ ... 11/07 17:00   9 jam   JKT Plant  —              [👁 Lihat]            │ │
│  │ ... 11/07 14:00   8 jam   SBY Refinery CV Bangun    [👁 Lihat]            │ │
│  │ ... 11/07 22:00   6 jam   JKT Plant  —              [👁 Lihat] [✏ Edit]   │ │
│  │ ... 10/07 15:00   6 jam   SBY Refinery PT Gamma     [👁 Lihat]            │ │
│  └──────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                  │
│  ┌─ Pagination ───────────────────────────────────────────────────────────────┐  │
│  │                              ‹ Sebelumnya   1  2  3  4   Berikutnya ›      │  │
│  └────────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Wireframe — Empty State

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Izin Kerja                                               [+ Buat Izin Kerja]   │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌──────────────────────────────────────────────────────────────────────────┐   │
│  │                                                                          │   │
│  │                              📋                                         │   │
│  │                                                                          │   │
│  │                   Belum ada izin kerja                                  │   │
│  │                                                                          │   │
│  │           Belum ada izin kerja yang dibuat. Klik tombol di bawah         │   │
│  │             untuk membuat izin kerja pertama Anda.                       │   │
│  │                                                                          │   │
│  │                      [+ Buat Izin Kerja Pertama]                        │   │
│  │                                                                          │   │
│  └──────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Element

#### Header

| Element | Type | Detail |
|---|---|---|
| Title | `<h1>` | "Izin Kerja" |
| Subtitle | `<p>` | "Kelola izin kerja untuk aktivitas berisiko tinggi" |
| Button "Buat Izin Kerja" | `<Link>` | Route: `permits.create`, permission: `permit.work.create` |
| Button Style | Tailwind | `bg-blue-600 text-white hover:bg-blue-700` |

#### Summary Cards

| Card | Query | Color | Icon |
|---|---|---|---|
| Aktif | status='active' AND now ≤ end_datetime | green | 🟢 |
| Akan Berakhir | status='active' AND end_datetime - now ≤ 24h | yellow | 🟡 |
| Kedaluwarsa | status='active' AND now > end_datetime | red | 🔴 |
| Draft/Pending | status IN (draft, submitted, under_review) | gray | ⚪ |

#### Search Box

| Element | Type | Detail |
|---|---|---|
| Placeholder | `<input>` | "Cari nomor, judul, lokasi kerja..." |
| Icon | SVG | Magnifying glass icon di kiri input |
| Behavior | debounce | 300ms debounce, kirim ke server via Inertia visit |
| Param | query | `?search=keyword` |

#### Filter Dropdowns

| Filter | Label | Options | Param |
|---|---|---|---|
| Jenis | "Jenis" | Semua, Hot Work, Working at Height, Confined Space, Electrical, Excavation, Lifting, Other | `?type=` |
| Status | "Status" | Semua, Draft, Submitted, Under Review, Approved, Active, Closed, Rejected | `?status=` |
| Validitas | "Validitas" | Semua, Aktif, Akan Berakhir, Kedaluwarsa, Belum Aktif | `?validity=` |
| Site | "Site" | Semua + dari master Sites | `?site_id=` |
| Contractor | "Contractor" | Semua + dari master Companies where type='contractor' | `?contractor_id=` |
| Date Range | "Dari" / "Sampai" | Date picker untuk rentang tanggal start_datetime | `?from=` `?to=` |
| Reset Button | Button | "Reset" — clear all filters | — |

#### Table Columns

| # | Column | Key | Width | Align | Badge? | Detail |
|---|---|---|---|---|---|---|
| 1 | Nomor | `permit_number` | 140px | left | No | Link ke show page, monospace font |
| 2 | Judul | `title` | flex | left | No | Truncate dengan `max-w-xs truncate` |
| 3 | Jenis | `type` | 130px | left | Yes | Lihat [Color Coding](#2-color-coding) |
| 4 | Status | `status` | 120px | center | Yes | Lihat [Color Coding](#2-color-coding) |
| 5 | Validitas | `validity_status` | 120px | center | Yes | Computed: active/expiring/expired/not_started |
| 6 | Periode | `start_datetime` → `end_datetime` | 200px | center | No | Format: `dd/mm/yy HH:mm → dd/mm/yy HH:mm` |
| 7 | Durasi | `validity_hours` | 80px | center | No | Format: `X jam` |
| 8 | Site | `site.name` | 100px | left | No | |
| 9 | Contractor | `contractor.name` | 130px | left | No | Nullable, "—" jika tidak ada |
| 10 | Aksi | — | 120px | center | No | Lihat di bawah |

#### Aksi Column (per row)

| Action | Icon | Permission | Condition |
|---|---|---|---|
| Lihat | 👁 | `permit.work.view` | Selalu tampil |
| Edit | ✏ | `permit.work.update` | Status = Draft |

#### Pagination

```
Menampilkan 1–15 dari 47 izin kerja

‹ Sebelumnya   1  2  3  4   Berikutnya ›
```

- Menggunakan komponen Tailwind pagination standar.
- Tampilkan "Menampilkan X–Y dari Z izin kerja".
- 15 item per halaman (dapat di-configurable: 15/25/50).

#### Export CSV

| Element | Detail |
|---|---|
| Button | `[⬇ Export CSV]` |
| Permission | `permit.work.export` |
| Behavior | Export data sesuai filter aktif saat ini |
| Endpoint | `GET /permits/export?type=...&status=...&...` |
| Response | CSV download dengan kolom: Nomor, Judul, Jenis, Deskripsi, Site, Contractor, Periode, Durasi, Risk Level, Status, Approved By, Closed By, Created At |

### Inertia Props

```typescript
interface IndexProps {
    permits: {
        data: Permit[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
    filters: {
        search?: string;
        type?: string;
        status?: string;
        validity?: string;
        site_id?: number;
        contractor_id?: number;
        from?: string;
        to?: string;
    };
    sites: Site[];
    contractors: Company[];
    summary: {
        active: number;
        expiring_soon: number;
        expired: number;
        pending: number;
    };
    can: {
        create: boolean;
        export: boolean;
    };
}
```

---

## 4. Halaman Form — Buat/Edit Izin Kerja

### Route

- Create: `GET /permits/create` (`permits.create`)
- Edit: `GET /permits/{permit}/edit` (`permits.edit`)

### Permission

- Create: `permit.work.create`
- Edit: `permit.work.update` (hanya jika status = Draft)

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Buat Izin Kerja                                                                 │
│  Isi data izin kerja dengan lengkap                                             │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Izin ─────────────────────────────────────────────────┐   │
│  │  INFORMASI IZIN                                                            │   │
│  │  ─────────────────────────────────────────────────────────────────────────  │   │
│  │                                                                            │   │
│  │  Nomor Izin        [Auto-generated — PTW-0006                  ]  ⓘ      │   │
│  │                     Nomor akan dibuat otomatis saat simpan                 │   │
│  │                                                                            │   │
│  │  Jenis Izin *      [— Pilih Jenis Izin —    ▾]                             │   │
│  │                     ○ Hot Work   ○ Working at Height  ○ Confined Space   │   │
│  │                     ○ Electrical ○ Excavation         ○ Lifting           │   │
│  │                     ○ Other                                                │   │
│  │                     ⚠ Pemilihan jenis menentukan checklist dinamis         │   │
│  │                                                                            │   │
│  │  Judul *           [Masukkan judul izin kerja...              ]            │   │
│  │                                                                            │   │
│  │  Deskripsi *       ┌──────────────────────────────────────────────┐       │   │
│  │                     │ Jelaskan ringkasan izin kerja...             │       │   │
│  │                     │                                              │       │   │
│  │                     └──────────────────────────────────────────────┘       │   │
│  │                                                                            │   │
│  └────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Section: Lokasi & Pekerja ───────────────────────────────────────────────┐  │
│  │  LOKASI & PEKERJA                                                          │   │
│  │  ─────────────────────────────────────────────────────────────────────────  │   │
│  │                                                                            │   │
│  │  Site *                [— Pilih Site —    ▾]                               │   │
│  │                                                                            │   │
│  │  Area                  [— Pilih Area —    ▾]    (filtered by site)         │   │
│  │                                                                            │   │
│  │  Department            [— Pilih Department —    ▾]  (filtered by site)    │   │
│  │                                                                            │   │
│  │  Contractor            [— Pilih Contractor —    ▾]  (optional)           │   │
│  │                                                                            │   │
│  │  Lokasi Kerja *        [Lokasi spesifik pekerjaan...         ]            │   │
│  │                        Contoh: "Tower B Lantai 3, Area Welding Bay"        │   │
│  │                                                                            │   │
│  │  Deskripsi Pekerjaan * ┌──────────────────────────────────────────────┐   │   │
│  │  (Work Description)    │ Jelaskan detail pekerjaan yang akan          │   │   │
│  │                        │ dilakukan...                                 │   │   │
│  │                        │                                              │   │   │
│  │                        └──────────────────────────────────────────────┘   │   │
│  │                                                                            │   │
│  └────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Section: Periode & Risiko ───────────────────────────────────────────────┐  │
│  │  PERIODE & RISIKO                                                          │   │
│  │  ─────────────────────────────────────────────────────────────────────────  │   │
│  │                                                                            │   │
│  │  Mulai Berlaku *     [__/__/____] [__:____]  🕐                            │   │
│  │  (start_datetime)    Tanggal dan jam mulai izin berlaku                    │   │
│  │                                                                            │   │
│  │  Berakhir Pada *     [__/__/____] [__:____]  🕐                            │   │
│  │  (end_datetime)      Tanggal dan jam izin berakhir                         │   │
│  │                                                                            │   │
│  │  Durasi              [Auto-calculated: 8 jam]                              │   │
│  │  (validity_hours)    Dihitung otomatis dari selisih mulai dan berakhir     │   │
│  │                                                                            │   │
│  │  Risk Level          [— Pilih Risk Level —    ▾]   (optional)             │   │
│  │                       ○ Low  ○ Medium  ○ High  ○ Critical               │   │
│  │                                                                            │   │
│  │  JSA Reference        [Nomor referensi JSA/Risk Assessment... ]            │   │
│  │                       (optional, link ke modul Risk/JSA)                   │   │
│  │                                                                            │   │
│  └────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Section: Checklist Dinamis (berdasarkan jenis) ──────────────────────────┐  │
│  │  CHECKLIST KESELAMATAN                                                     │   │
│  │  ─────────────────────────────────────────────────────────────────────────  │   │
│  │  ⚠ Checklist otomatis dibuat berdasarkan jenis izin yang dipilih.          │   │
│  │    Checklist harus di-sign (tandai) sebelum izin dapat diaktifkan.         │   │
│  │                                                                            │   │
│  │  ┌─ Checklist Item 1 ─────────────────────────────────────────────────┐   │   │
│  │  │ ☐ APD tahan api tersedia dan dipakai (goggles, gloves, apron)     │   │   │
│  │  └────────────────────────────────────────────────────────────────────┘   │   │
│  │                                                                            │   │
│  │  ┌─ Checklist Item 2 ─────────────────────────────────────────────────┐   │   │
│  │  │ ☐ Fire extinguisher tersedia di area kerja (min. 2 unit)          │   │   │
│  │  └────────────────────────────────────────────────────────────────────┘   │   │
│  │                                                                            │   │
│  │  ┌─ Checklist Item 3 ─────────────────────────────────────────────────┐   │   │
│  │  │ ☐ Area 10 meter bebas bahan mudah terbakar                        │   │   │
│  │  └────────────────────────────────────────────────────────────────────┘   │   │
│  │                                                                            │   │
│  │  ... (items berubah berdasarkan jenis izin yang dipilih)                  │   │
│  │                                                                            │   │
│  │  Catatan: Checklist dapat di-sign di halaman detail setelah izin          │   │
│  │  di-approve.                                                              │   │
│  │                                                                            │   │
│  └────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Section: Evidence ───────────────────────────────────────────────────────┐  │
│  │  EVIDENCE / DOKUMEN PENDUKUNG                                             │   │
│  │  ─────────────────────────────────────────────────────────────────────────  │   │
│  │                                                                            │   │
│  │  ┌─────────────────────────────────────────────────────────────────────┐   │   │
│  │  │                                                                     │   │   │
│  │  │              📁  Drag & drop file di sini                           │   │   │
│  │  │                  atau [Pilih File]                                  │   │   │
│  │  │                                                                     │   │   │
│  │  │              Maks 25MB per file. Format: jpg, png, pdf, docx        │   │   │
│  │  │                                                                     │   │
│  │  └─────────────────────────────────────────────────────────────────────┘   │   │
│  │                                                                            │   │
│  │  File terunggah:                                                          │   │
│  │  ┌──────────────────────────────────────────────────────────────────┐     │   │
│  │  │ 📎 jsa_hot_work.pdf                              1.2 MB   [🗑]    │     │   │
│  │  │ 📎 foto_area_kerja.jpg                           2.3 MB   [🗑]    │     │   │
│  │  └──────────────────────────────────────────────────────────────────┘     │   │
│  │                                                                            │   │
│  └────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Action Bar (sticky bottom) ──────────────────────────────────────────────┐  │
│  │                                                                           │  │
│  │  [← Batal]                                    [Simpan Draft]  [Submit]  │  │
│  │                                                              (primary)   │  │
│  └───────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Dynamic Checklist Behavior

Saat user memilih `Jenis Izin` (type), checklist section di-render ulang secara dinamis:

```typescript
// Data checklist template per jenis izin (dari backend atau config)
const checklistTemplates: Record<string, string[]> = {
    hot_work: [
        'APD tahan api tersedia dan dipakai (goggles, gloves, apron)',
        'Fire extinguisher tersedia di area kerja (min. 2 unit)',
        'Area 10 meter bebas bahan mudah terbakar',
        'Fire watch ditunjuk dan siap',
        'Hot work permit area di-barricade',
        'Sistem ventilasi memadai',
        'Emergency response plan diketahui semua pekerja',
    ],
    working_at_height: [
        'Full body harness dipakai dan di-inspect',
        'Anchor point terverifikasi (min. 22 kN)',
        'Scaffolding di-inspect oleh competent person',
        'Edge protection / guard rail terpasang',
        'Fall protection system aktif',
        'Tidak ada pekerjaan di bawah area tanpa proteksi',
        'Emergency rescue plan siap',
    ],
    confined_space: [
        'Gas test dilakukan (O2, LEL, H2S, CO)',
        'Ventilasi mekanis aktif',
        'Entry permit ditandatangani',
        'Standby person ditunjuk di entrance',
        'Rescue equipment siap (tripod, winch, SCBA)',
        'Komunikasi antara entrant dan attendant',
        'Lockout/Tagout semua sumber energi',
        'Continuous gas monitoring aktif',
    ],
    electrical: [
        'LOTO procedure dijalankan dan diverifikasi',
        'Voltage test dilakukan (verify zero energy)',
        'PPE electrical rated dipakai (gloves, mats)',
        'Grounding temporary terpasang',
        'Barricade dan warning sign terpasang',
        'Competent person melakukan pekerjaan',
        'Emergency procedure untuk electrical shock diketahui',
    ],
    excavation: [
        'Underground utility scan dilakukan dan didokumentasikan',
        'Shoring/sloping sesuai depth (≥ 1.2m wajib shoring)',
        'Safe access/egress (ladder setiap 7.5m)',
        'Spoil pile ≥ 0.6m dari edge',
        'Gas test untuk confined space trench',
        'Barricade dan warning sign terpasang',
        'Daily inspection oleh competent person',
    ],
    lifting: [
        'Lift plan disiapkan dan di-approve',
        'Load calculation dilakukan',
        'Crane/hoist certification valid',
        'Rigger dan signalman certified',
        'Sling dan rigging gear di-inspect',
        'Area lifting di-barricade',
        'Weather condition sesuai (wind speed < limit)',
        'Communication radio tersedia',
    ],
    other: [
        'Risk assessment / JSA dilakukan',
        'APD sesuai pekerjaan dipakai',
        'Emergency procedure diketahui',
        'Pekerja competent dan tersertifikasi',
        'Area kerja di-barricade',
    ],
};

// Saat type berubah, render checklist items baru
const handleTypeChange = (newType: string) => {
    const items = checklistTemplates[newType] || [];
    setChecklistItems(items.map(text => ({ item_text: text, is_checked: false })));
};
```

### Inertia Props

```typescript
interface FormProps {
    permit: Permit | null;  // null for create, populated for edit
    sites: Site[];
    areas: Area[];
    departments: Department[];
    contractors: Company[];
    checklistTemplates: Record<string, string[]>;  // type → items
}
```

---

## 5. Halaman Show — Detail Izin Kerja

### Route: `GET /permits/{permit}` (`permits.show`)

### Permission: `permit.work.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  [← Kembali]  PTW-JKT-2026-0001                              [✏ Edit] [🖨 PDF]  │
│  Pengelasan Strip Plate Tower B                                                  │
│  🔥 Hot Work  🟡 Under Review  ⚪ Belum Aktif                                    │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Izin ─────────────────────────────────────────────────┐   │
│  │  INFORMASI IZIN                                                            │   │
│  │  ─────────────────────────────────────────────────────────────────────────  │   │
│  │                                                                            │   │
│  │  Nomor Izin        PTW-JKT-2026-0001                                       │   │
│  │  Jenis             🔥 Hot Work                                             │   │
│  │  Judul             Pengelasan Strip Plate Tower B                           │   │
│  │  Deskripsi         Pengelasan strip plate pada struktur Tower B lantai 3   │   │
│  │  Risk Level        🟠 High                                                 │   │
│  │  JSA Reference     RSK-2026-0012                                           │   │
│  │                                                                            │   │
│  └────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Section: Lokasi & Pekerja ───────────────────────────────────────────────┐  │
│  │  LOKASI & PEKERJA                                                          │   │
│  │  ─────────────────────────────────────────────────────────────────────────  │   │
│  │                                                                            │   │
│  │  Site              Jakarta Plant                                           │   │
│  │  Area              Production Area B                                       │   │
│  │  Department        Maintenance                                             │   │
│  │  Contractor        PT Maju Jaya                                            │   │
│  │  Lokasi Kerja      Tower B Lantai 3, Area Welding Bay                      │   │
│  │  Deskripsi Pekerjaan  Pengelasan strip plate sepanjang 15 meter,           │   │
│  │                       menggunakan MIG welding dengan argon gas             │   │
│  │                                                                            │   │
│  └────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Section: Periode Berlaku ────────────────────────────────────────────────┐  │
│  │  PERIODE BERLAKU                                                           │   │
│  │  ─────────────────────────────────────────────────────────────────────────  │   │
│  │                                                                            │   │
│  │  Mulai Berlaku     11/07/2026 14:00                                        │   │
│  │  Berakhir Pada     11/07/2026 18:00                                       │   │
│  │  Durasi            4 jam                                                   │   │
│  │  Validity Status   ⚪ Belum Aktif                                           │   │
│  │  Countdown          —                                                      │   │
│  │                                                                            │   │
│  │  ⏱️  Countdown Timer (jika status = active):                               │   │
│  │  ┌─────────────────────────────────────────────────────────────────────┐   │   │
│  │  │  ⏰ Berakhir dalam 3 jam 24 menit                                   │   │   │
│  │  │  [████████████████████░░░░░░░░] 68%                                 │   │   │
│  │  └─────────────────────────────────────────────────────────────────────┘   │   │
│  │                                                                            │   │
│  └────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Section: Checklist ──────────────────────────────────────────────────────┐   │
│  │  CHECKLIST KESELAMATAN                                                     │   │
│  │  ─────────────────────────────────────────────────────────────────────────  │   │
│  │  Progress: 5/7 items di-sign                                               │   │
│  │  [████████████████████████░░░░░░░░░░░░░░░░] 71%                          │   │
│  │                                                                            │   │
│  │  ┌─ Checklist Item 1 ─────────────────────────────────────────────────┐   │   │
│  │  │ ☑ APD tahan api tersedia dan dipakai (goggles, gloves, apron)     │   │   │
│  │  │   Signed by: Budi Santoso — 11/07/2026 13:45                      │   │   │
│  │  └────────────────────────────────────────────────────────────────────┘   │   │
│  │                                                                            │   │
│  │  ┌─ Checklist Item 2 ─────────────────────────────────────────────────┐   │   │
│  │  │ ☑ Fire extinguisher tersedia di area kerja (min. 2 unit)          │   │   │
│  │  │   Signed by: Budi Santoso — 11/07/2026 13:46                      │   │   │
│  │  └────────────────────────────────────────────────────────────────────┘   │   │
│  │                                                                            │   │
│  │  ┌─ Checklist Item 3 ─────────────────────────────────────────────────┐   │   │
│  │  │ ☑ Area 10 meter bebas bahan mudah terbakar                        │   │   │
│  │  │   Signed by: Budi Santoso — 11/07/2026 13:47                      │   │   │
│  │  └────────────────────────────────────────────────────────────────────┘   │   │
│  │                                                                            │   │
│  │  ┌─ Checklist Item 4 ─────────────────────────────────────────────────┐   │   │
│  │  │ ☑ Fire watch ditunjuk dan siap                                    │   │   │
│  │  │   Signed by: Budi Santoso — 11/07/2026 13:48                      │   │   │
│  │  └────────────────────────────────────────────────────────────────────┘   │   │
│  │                                                                            │   │
│  │  ┌─ Checklist Item 5 ─────────────────────────────────────────────────┐   │   │
│  │  │ ☑ Hot work permit area di-barricade                               │   │   │
│  │  │   Signed by: Budi Santoso — 11/07/2026 13:49                      │   │   │
│  │  └────────────────────────────────────────────────────────────────────┘   │   │
│  │                                                                            │   │
│  │  ┌─ Checklist Item 6 ─────────────────────────────────────────────────┐   │   │
│  │  │ ☐ Sistem ventilasi memadai                                        │   │   │
│  │  │   [✍ Tanda Tangani]  (button to sign this item)                    │   │   │
│  │  └────────────────────────────────────────────────────────────────────┘   │   │
│  │                                                                            │   │
│  │  ┌─ Checklist Item 7 ─────────────────────────────────────────────────┐   │   │
│  │  │ ☐ Emergency response plan diketahui semua pekerja                  │   │   │
│  │  │   [✍ Tanda Tangani]  (button to sign this item)                    │   │   │
│  │  └────────────────────────────────────────────────────────────────────┘   │   │
│  │                                                                            │   │
│  │  ⚠ Semua checklist harus di-sign sebelum izin dapat diaktifkan.           │   │
│  │                                                                            │   │
│  └────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Section: Approval & Workflow ────────────────────────────────────────────┐   │
│  │  ALUR APPROVAL                                                             │   │
│  │  ─────────────────────────────────────────────────────────────────────────  │   │
│  │                                                                            │   │
│  │  Requester        John Doe (john@example.com)                             │   │
│  │  Approved By      — (belum di-approve)                                    │   │
│  │  Approved At      —                                                        │   │
│  │  Closed By        —                                                        │   │
│  │  Closed At        —                                                        │   │
│  │                                                                            │   │
│  │  Workflow Timeline:                                                       │   │
│  │  ┌──────────────────────────────────────────────────────────────────┐     │   │
│  │  │ ● 11/07 13:30  Draft created          by John Doe                 │     │   │
│  │  │ ● 11/07 13:35  Submitted              by John Doe                 │     │   │
│  │  │ ● 11/07 13:40  Under Review            by QHSSE Officer           │     │   │
│  │  │ ○ ─ ─ ─ ─ ─    Approved               (pending)                   │     │   │
│  │  │ ○ ─ ─ ─ ─ ─    Activated              (pending)                   │     │   │
│  │  │ ○ ─ ─ ─ ─ ─    Closed                 (pending)                   │     │   │
│  │  └──────────────────────────────────────────────────────────────────┘     │   │
│  │                                                                            │   │
│  │  ┌─ Action Buttons (conditional by status & permission) ─────────────┐   │   │
│  │  │                                                                    │   │   │
│  │  │  Status: Under Review                                              │   │   │
│  │  │  [✅ Approve]  [🔴 Reject]                                        │   │   │
│  │  │  (permit.work.approve)  (permit.work.review)                      │   │   │
│  │  │                                                                    │   │   │
│  │  └────────────────────────────────────────────────────────────────────┘   │   │
│  └────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Section: Evidence ───────────────────────────────────────────────────────┐  │
│  │  EVIDENCE / DOKUMEN PENDUKUNG                                             │   │
│  │  ─────────────────────────────────────────────────────────────────────────  │   │
│  │                                                                            │   │
│  │  ┌──────────────────────────────────────────────────────────────────┐     │   │
│  │  │ 📄 jsa_hot_work.pdf                     1.2 MB   [⬇ Download]   │     │   │
│  │  │ 📷 foto_area_kerja.jpg                  2.3 MB   [⬇ Download]   │     │   │
│  │  │ 📷 foto_barricade.jpg                   1.8 MB   [⬇ Download]   │     │   │
│  │  └──────────────────────────────────────────────────────────────────┘     │   │
│  │                                                                            │   │
│  │  [📁 Upload File]  (permit.work.update, if not closed)                    │   │
│  │                                                                            │   │
│  └────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Section: Comments ───────────────────────────────────────────────────────┐  │
│  │  KOMENTAR                                                                  │   │
│  │  ─────────────────────────────────────────────────────────────────────────  │   │
│  │                                                                            │   │
│  │  ┌─ Comment 1 ───────────────────────────────────────────────────────┐   │   │
│  │  │ 👤 QHSSE Officer — 11/07/2026 13:42                               │   │   │
│  │  │ Mohon pastikan fire watch sudah ditunjuk sebelum approve.         │   │   │
│  │  │ [💬 Balas]                                                        │   │   │
│  │  └────────────────────────────────────────────────────────────────────┘   │   │
│  │                                                                            │   │
│  │  ┌─ Add Comment ─────────────────────────────────────────────────────┐   │   │
│  │  │ [Tulis komentar...                              ]                  │   │   │
│  │  │ ☐ Internal only   [Kirim]                                          │   │   │
│  │  └────────────────────────────────────────────────────────────────────┘   │   │
│  │                                                                            │   │
│  └────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Section: Activity Log ────────────────────────────────────────────────────┐  │
│  │  LOG AKTIVITAS                                                             │   │
│  │  ─────────────────────────────────────────────────────────────────────────  │   │
│  │                                                                            │   │
│  │  ● 11/07 13:49  Checklist item signed: "Hot work permit area..."         │   │
│  │  ● 11/07 13:48  Checklist item signed: "Fire watch ditunjuk..."          │   │
│  │  ● 11/07 13:47  Checklist item signed: "Area 10 meter bebas..."          │   │
│  │  ● 11/07 13:46  Checklist item signed: "Fire extinguisher..."            │   │
│  │  ● 11/07 13:45  Checklist item signed: "APD tahan api..."               │   │
│  │  ● 11/07 13:42  Comment added by QHSSE Officer                           │   │
│  │  ● 11/07 13:40  Status changed: Submitted → Under Review                 │   │
│  │  ● 11/07 13:35  Status changed: Draft → Submitted                       │   │
│  │  ● 11/07 13:30  Permit created                                           │   │
│  │                                                                            │   │
│  └────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Action Bar (sticky bottom, conditional) ─────────────────────────────────┐  │
│  │                                                                           │  │
│  │  Status: Under Review                                                     │  │
│  │                                                                           │  │
│  │  [✅ Approve]  [🔴 Reject]  (if under_review & permit.work.approve)      │  │
│  │                                                                           │  │
│  │  [🟢 Activate Izin]  (if approved & all checklist signed & permit.work.approve) │
│  │  ⚠ Activate hanya tersedia jika semua checklist sudah di-sign             │  │
│  │                                                                           │  │
│  │  [🔒 Tutup Izin]  (if active & permit.work.close)                       │  │
│  │  Prompts: "Alasan penutupan (min 10 karakter)"                           │  │
│  │                                                                           │  │
│  └───────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Action Buttons by Status

| Status | Button | Permission | Condition |
|---|---|---|---|
| `draft` | [Submit] | `permit.work.submit` | — |
| `submitted` | [Start Review] | `permit.work.review` | — |
| `submitted` | [Reject] | `permit.work.review` | Requires reason |
| `under_review` | [Approve] | `permit.work.approve` | — |
| `under_review` | [Reject] | `permit.work.review` | Requires reason |
| `approved` | [Activate] | `permit.work.approve` | All checklist items signed |
| `active` | [Close] | `permit.work.close` | Requires reason |
| `closed` | — | — | No actions (terminal) |
| `rejected` | — | — | No actions (terminal) |

### Inertia Props

```typescript
interface ShowProps {
    permit: Permit & {
        site: Site;
        area: Area | null;
        department: Department | null;
        contractor: Company | null;
        creator: User;
        approver: User | null;
        closer: User | null;
        checklists: PermitChecklist[];
    };
    evidence: ManagedFile[];
    comments: Comment[];
    activities: ActivityLog[];
    workflowHistory: WorkflowHistory[];
    availableTransitions: {
        action_key: string;
        action_label: string;
        requires_reason: boolean;
    }[];
    checklistProgress: {
        total: number;
        signed: number;
        all_signed: boolean;
    };
    validityStatus: 'active' | 'expired' | 'expiring_soon' | 'not_started';
    can: {
        update: boolean;
        submit: boolean;
        review: boolean;
        approve: boolean;
        close: boolean;
        export: boolean;
    };
}
```

---

## 6. Mobile Responsive

### Breakpoints

| Breakpoint | Width | Layout |
|---|---|---|
| `sm` | < 640px | Single column, hamburger menu, stacked table |
| `md` | 640-1024px | Two column form, collapsible sidebar |
| `lg` | > 1024px | Full desktop layout |

### Mobile Index Page

- Summary cards: 2x2 grid
- Filter bar: collapsible accordion
- Table: horizontal scroll, priority columns (Nomor, Judul, Jenis, Status, Validitas)
- Action buttons: icon-only

### Mobile Form Page

- All sections stacked vertically
- Date/time pickers: native mobile picker
- Checklist: full-width items
- Action bar: fixed bottom, full-width buttons

### Mobile Show Page

- All sections stacked vertically
- Checklist: full-width items with sign button
- Workflow timeline: vertical only
- Action buttons: fixed bottom bar, full-width
- Countdown timer: prominent at top

### Component List

| Component | File | Description |
|---|---|---|
| `PermitIndex` | `Pages/Modules/PermitToWork/Index.tsx` | List page with filters, summary cards, table |
| `PermitForm` | `Pages/Modules/PermitToWork/Form.tsx` | Create/edit form with dynamic checklist |
| `PermitShow` | `Pages/Modules/PermitToWork/Show.tsx` | Detail page with checklist signing, workflow |
| `ChecklistItem` | `components/PermitToWork/ChecklistItem.tsx` | Reusable checklist item with sign button |
| `ValidityBadge` | `components/PermitToWork/ValidityBadge.tsx` | Reusable validity status badge |
| `CountdownTimer` | `components/PermitToWork/CountdownTimer.tsx` | Live countdown for active permits |
| `PermitTypeBadge` | `components/PermitToWork/PermitTypeBadge.tsx` | Reusable type badge with icon |
| `WorkflowTimeline` | `components/PermitToWork/WorkflowTimeline.tsx` | Vertical workflow step indicator |
