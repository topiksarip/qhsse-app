# UI Pages — Asset & Equipment Safety

Spesifikasi wireframe halaman UI untuk modul Asset & Equipment Safety.

Semua label menggunakan Bahasa Indonesia. Tech stack: Laravel 12 + Inertia React + TypeScript + Tailwind CSS.

---

## Daftar Isi

1. [Navigasi](#1-navigasi)
2. [Color Coding](#2-color-coding)
3. [Halaman Index — Daftar Aset](#3-halaman-index--daftar-aset)
4. [Halaman Form — Buat/Edit Aset](#4-halaman-form--buatedit-aset)
5. [Halaman Show — Detail Aset (dengan Tab Certificates & Inspections)](#5-halaman-show--detail-aset)
6. [Certificate Form (Inline di Asset Show)](#6-certificate-form--inline-di-asset-show)
7. [Inspection Form (Inline di Asset Show)](#7-inspection-form--inline-di-asset-show)
8. [Mobile Responsive](#8-mobile-responsive)

---

## 1. Navigasi

### Penempatan Menu

Tambahkan item menu `Aset & Peralatan` pada group `Modul QHSSE` di array `menuGroups` pada `AuthenticatedLayout.tsx`.

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
            { label: 'Laporan Insiden', routeName: 'incident-reporting.index', active: 'incident-reporting.*', permission: 'incident-reporting.view' },
            { label: 'Aset & Peralatan', routeName: 'assets.index', active: 'assets.*', permission: 'asset.management.view' },  // ← NEW
        ],
    },
    {
        label: 'Masters',
        items: [/* ... */],
    },
    {
        label: 'Admin',
        items: [/* ... */],
    },
];
```

### Wireframe Navigasi (Desktop)

```
┌──────────────────────────────────────────────────────────────────────┐
│  [Logo] QHSSE   Core ▾   Modul QHSSE ▾   Masters ▾   Admin ▾  [User]│
│                        ┌──────────────────┐                         │
│                        │ Laporan Insiden  │                         │
│                        │ Aset & Peralatan  │                         │
│                        └──────────────────┘                         │
└──────────────────────────────────────────────────────────────────────┘
```

### Permission Filtering

Menu hanya tampil jika user memiliki permission `asset.management.view`. Filtering dilakukan via `auth.permissions` pada layout.

---

## 2. Color Coding

### Asset Status Badge

| Status | Tailwind Class | Preview |
|---|---|---|
| Active | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Aktif` |
| Inactive | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | `⚪ Tidak Aktif` |
| Decommissioned | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Decommissioned` |

### Asset Category Badge

| Category | Tailwind Class | Preview |
|---|---|---|
| Equipment | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | `🔵 Equipment` |
| Machinery | `bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200` | `🔵 Machinery` |
| Vehicle | `bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200` | `🔵 Vehicle` |
| Safety Equipment | `bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200` | `🟣 Safety Equipment` |
| Fire Equipment | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Fire Equipment` |
| Lifting | `bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200` | `🟠 Lifting` |
| Other | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | `⚪ Other` |

### Safety-Critical Highlight

| Element | Tailwind Class | Preview |
|---|---|---|
| Row highlight | `bg-red-50 dark:bg-red-950/30 border-l-4 border-red-500` | Left red border on table row |
| Badge | `bg-red-600 text-white` | `⚠ Safety Critical` |
| Icon | `⚠` | Warning icon |

### Certificate Status Badge

| Status | Tailwind Class | Preview |
|---|---|---|
| Valid | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Valid` |
| Expiring Soon | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 Akan Kedaluwarsa` |
| Expiring Critical | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Kedaluwarsa <7 Hari` |
| Expired | `bg-red-600 text-white dark:bg-red-800` | `🔴 KEDALUWARSA` |

### Inspection Result Badge

| Result | Tailwind Class | Preview |
|---|---|---|
| Pass | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `✅ Pass` |
| Fail | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `❌ Fail` |
| Maintenance Required | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `⚠ Maintenance` |

### Certificate Expiry Warning Indicators (Index Page)

| Condition | Visual | Tailwind Class |
|---|---|---|
| Sertifikat expired | RED badge on row | `bg-red-50 border-l-4 border-red-500` |
| Sertifikat expiring (<7 hari) | RED badge on row | `bg-red-50 border-l-4 border-red-500` |
| Sertifikat expiring (<30 hari) | YELLOW badge on row | `bg-yellow-50 border-l-4 border-yellow-500` |
| Safety-critical + cert expired | RED + bold row | `bg-red-100 border-l-4 border-red-600 font-semibold` |

### Pemetaan Helper

```typescript
// utils/badgeColors.ts

const assetStatusColors: Record<string, BadgeColor> = {
    active:         'green',
    inactive:       'gray',
    decommissioned: 'red',
};

const assetCategoryColors: Record<string, BadgeColor> = {
    equipment:         'blue',
    machinery:         'indigo',
    vehicle:           'cyan',
    safety_equipment:  'purple',
    fire_equipment:    'red',
    lifting:           'orange',
    other:             'gray',
};

const certificateStatusColors: Record<string, BadgeColor> = {
    valid:               'green',
    expiring_soon:       'yellow',
    expiring_critical:   'red',
    expired:             'red',
};

const inspectionResultColors: Record<string, BadgeColor> = {
    pass:                  'green',
    fail:                  'red',
    maintenance_required:  'yellow',
};
```

---

## 3. Halaman Index — Daftar Aset

### Route: `GET /assets` (`assets.index`)

### Permission: `asset.management.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                       │
│  Aset & Peralatan                                       [+ Tambah Aset]     │
│  Kelola aset dan peralatan keselamatan kerja                                 │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari nomor, nama, serial number...       ]                          │  │
│  │                                                                        │  │
│  │ Status: [Semua ▾]  Kategori: [Semua ▾]  Site: [Semua ▾]               │  │
│  │ ☐ Safety Critical Only    [Reset]                                       │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Toolbar ─────────────────────────────────────────────────────────────┐  │
│  │ Menampilkan 1–15 dari 48 aset                     [⬇ Export CSV]     │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Table ───────────────────────────────────────────────────────────────┐ │
│  │ Nomor       Nama           Kategori      Status   Safety  Sertifikat  │ │
│  ├─────────────────────────────────────────────────────────────────────────┤ │
│  │ AST-0001  Crane 50Ton    🟠Lifting     🟢Aktif   ⚠YES   🟢 Valid    │ │
│  │ AST-0002  APAR CO2 #12   🔴Fire Equ.   🟢Aktif   ⚠YES   🔴 EXPIRED  │ │
│  │ AST-0003  Forklift #03   🔵Vehicle     🟢Aktif   ⚠YES   🟡 <30 Hari  │ │
│  │ AST-0004  Harness Set A 🟣Safety Equ.  🟢Aktif   ❌NO    🟢 Valid    │ │
│  │ AST-0005  Mesin CNC L2  🔵Machinery   🟢Aktif   ❌NO    —            │ │
│  │ ...                                                                     │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│  ┌─ Table (cont.) ────────────────────────────────────────────────────────┐ │
│  │ ... Sertifikat  Area            Tgl Beli    Aksi                     │ │
│  │ ... 🟢 Valid    Produksi L1     15/01/25   [👁 Lihat] [✏ Edit]      │ │
│  │ ... 🔴 EXPIRED  Area Fire        20/03/24   [👁 Lihat] [✏ Edit]      │ │
│  │ ... 🟡 <30 Hari  Warehouse       10/06/25   [👁 Lihat] [✏ Edit]      │ │
│  │ ... 🟢 Valid    Assembly         05/02/25   [👁 Lihat] [✏ Edit]      │ │
│  │ ... —           Produksi L2      01/12/24   [👁 Lihat] [✏ Edit]      │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  ┌─ Pagination ───────────────────────────────────────────────────────────┐  │
│  │                         ‹ Sebelumnya   1  2  3  4   Berikutnya ›      │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Wireframe — Empty State

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                                                                              │
│  ┌──────────────────────────────────────────────────────────────┐           │
│  │                          📦                                   │           │
│  │                                                              │           │
│  │                   Belum ada aset                            │           │
│  │                                                              │           │
│  │           Belum ada aset yang terdaftar. Klik tombol          │           │
│  │           di bawah untuk menambahkan aset pertama.            │           │
│  │                                                              │           │
│  │                      [+ Tambah Aset Pertama]                  │           │
│  │                                                              │           │
│  └──────────────────────────────────────────────────────────────┘           │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Element

#### Header

| Element | Type | Detail |
|---|---|---|
| Title | `<h1>` | "Aset & Peralatan" |
| Subtitle | `<p>` | "Kelola aset dan peralatan keselamatan kerja" |
| Button "Tambah Aset" | `<Link>` | Route: `assets.create`, permission: `asset.management.create` |
| Button Style | Tailwind | `bg-blue-600 text-white hover:bg-blue-700` |

#### Search Box

| Element | Type | Detail |
|---|---|---|
| Placeholder | `<input>` | "Cari nomor, nama, serial number..." |
| Behavior | debounce | 300ms, Inertia visit |
| Param | query | `?search=keyword` |

#### Filter Dropdowns

| Filter | Label | Options | Param |
|---|---|---|---|
| Status | "Status" | Semua, Aktif, Tidak Aktif, Decommissioned | `?status=` |
| Kategori | "Kategori" | Semua, Equipment, Machinery, Vehicle, Safety Equipment, Fire Equipment, Lifting, Other | `?category=` |
| Site | "Site" | Semua + dari master Sites | `?site_id=` |
| Safety Critical | Checkbox | "Safety Critical Only" | `?safety_critical=1` |
| Reset | Button | "Reset" — clear all filters | — |

#### Table Columns

| # | Column | Key | Width | Align | Badge? | Detail |
|---|---|---|---|---|---|---|
| 1 | Nomor | `asset_number` | 120px | left | No | Link ke show page, monospace |
| 2 | Nama | `name` | flex | left | No | Truncate `max-w-xs` |
| 3 | Kategori | `category` | 130px | center | Yes | Lihat Color Coding |
| 4 | Status | `status` | 100px | center | Yes | Lihat Color Coding |
| 5 | Safety | `safety_critical` | 80px | center | Yes | ⚠ badge if true |
| 6 | Sertifikat | certificate status summary | 120px | center | Yes | Worst status badge |
| 7 | Area | `area.name` | 120px | left | No | Nullable, tampil "—" jika null |
| 8 | Tgl Beli | `purchase_date` | 90px | center | No | Format: `dd/mm/yy` |
| 9 | Aksi | — | 120px | center | No | Lihat di bawah |

#### Certificate Status Summary Column

Kolom "Sertifikat" menampilkan status terburuk (worst-case) dari semua sertifikat aset:
- Jika ada sertifikat `expired` → tampilkan `🔴 EXPIRED`
- Jika ada sertifikat `expiring_critical` → tampilkan `🔴 <7 Hari`
- Jika ada sertifikat `expiring_soon` → tampilkan `🟡 <30 Hari`
- Jika semua sertifikat `valid` → tampilkan `🟢 Valid`
- Jika tidak ada sertifikat → tampilkan `—`

#### Row Highlight Rules

| Condition | Row Style |
|---|---|
| Safety-critical + cert expired | `bg-red-100 border-l-4 border-red-600 font-semibold` |
| Safety-critical (no expired cert) | `bg-red-50 border-l-4 border-red-500` |
| Cert expired (non safety-critical) | `bg-red-50 border-l-4 border-red-400` |
| Cert expiring soon (non safety-critical) | `bg-yellow-50 border-l-4 border-yellow-400` |
| Normal | Default |

#### Aksi Column (per row)

| Action | Icon | Permission | Condition |
|---|---|---|---|
| Lihat | 👁 | `asset.management.view` | Selalu tampil |
| Edit | ✏ | `asset.management.update` | Status = Active |

### Inertia Props

```typescript
interface IndexProps {
    assets: {
        data: Asset[];
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
        category?: string;
        site_id?: number;
        safety_critical?: boolean;
    };
    sites: Site[];
    can: {
        create: boolean;
        export: boolean;
    };
}
```

---

## 4. Halaman Form — Buat/Edit Aset

### Route

- Create: `GET /assets/create` (`assets.create`)
- Edit: `GET /assets/{asset}/edit` (`assets.edit`)

### Permission

- Create: `asset.management.create`
- Edit: `asset.management.update` (hanya jika status = Active)

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Tambah Aset                                                                     │
│  Isi data aset dengan lengkap                                                    │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Aset ──────────────────────────────────────────────────┐  │
│  │  INFORMASI ASET                                                              │  │
│  │  ────────────────────────────────────────────────────────────────────────── │  │
│  │                                                                             │  │
│  │  Nomor Aset            [Auto-generated — AST-0001              ]  ⓘ        │  │
│  │                        Nomor akan dibuat otomatis saat simpan                │  │
│  │                                                                             │  │
│  │  Nama Aset *           [Masukkan nama aset...                  ]            │  │
│  │                                                                             │  │
│  │  Kategori *            [— Pilih Kategori —    ▾]                            │  │
│  │                        ○ Equipment  ○ Machinery  ○ Vehicle                   │  │
│  │                        ○ Safety Equipment  ○ Fire Equipment                 │  │
│  │                        ○ Lifting  ○ Other                                    │  │
│  │                                                                             │  │
│  │  Serial Number         [Masukkan serial number...             ]  (opsional) │  │
│  │                                                                             │  │
│  │  Model                  [Masukkan model...                    ]  (opsional) │  │
│  │                                                                             │  │
│  │  Manufacturer           [Masukkan nama manufacturer...        ]  (opsional) │  │
│  │                                                                             │  │
│  │  ☐ Safety Critical      Centang jika aset ini critical untuk keselamatan   │  │
│  │                        Aset safety-critical mendapat prioritas monitoring   │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Lokasi ─────────────────────────────────────────────────────────┐  │
│  │  LOKASI                                                                      │  │
│  │  ────────────────────────────────────────────────────────────────────────── │  │
│  │                                                                             │  │
│  │  Site *                [— Pilih Site —    ▾]                                │  │
│  │                                                                             │  │
│  │  Area                   [— Pilih Area —    ▾]   (opsional, filtered by site) │  │
│  │                                                                             │  │
│  │  Department             [— Pilih Department —    ▾]  (opsional)             │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Tanggal Penting ────────────────────────────────────────────────┐  │
│  │  TANGGAL PENTING                                                             │  │
│  │  ────────────────────────────────────────────────────────────────────────── │  │
│  │                                                                             │  │
│  │  Tanggal Pembelian      [__/__/____]   (opsional)                           │  │
│  │                                                                             │  │
│  │  Tanggal Instalasi      [__/__/____]   (opsional)                           │  │
│  │                                                                             │  │
│  │  Masa Garansi           [__/__/____]   (opsional)                           │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Action Bar (sticky bottom) ──────────────────────────────────────────────────┐  │
│  │                                                                           │  │
│  │  [← Batal]                                          [Simpan Aset]       │  │
│  │                                                     (primary)              │  │
│  └───────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Section

#### Section: Informasi Aset

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Nomor Aset | Text (readonly) | No | — | Auto-generated saat create. Placeholder "Auto-generated" |
| Nama Aset | Text input | Yes | `required\|string\|max:255` | Placeholder: "Masukkan nama aset..." |
| Kategori | Select/Radio | Yes | `required\|in:equipment,machinery,vehicle,safety_equipment,fire_equipment,lifting,other` | 7 pilihan |
| Serial Number | Text input | No | `nullable\|string\|max:255` | |
| Model | Text input | No | `nullable\|string\|max:255` | |
| Manufacturer | Text input | No | `nullable\|string\|max:255` | |
| Safety Critical | Checkbox | No | `boolean` | Default false |

#### Section: Lokasi

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Site | Select dropdown | Yes | `required\|exists:sites,id` | Dari master Sites |
| Area | Select dropdown | No | `nullable\|exists:areas,id` | Filtered by site_id |
| Department | Select dropdown | No | `nullable\|exists:departments,id` | |

#### Section: Tanggal Penting

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Tanggal Pembelian | Date picker | No | `nullable\|date` | Format: `dd/mm/yyyy` |
| Tanggal Instalasi | Date picker | No | `nullable\|date` | |
| Masa Garansi | Date picker | No | `nullable\|date` | |

### Action Buttons

| Button | Type | Style | Behavior |
|---|---|---|---|
| Batal | Link | `text-slate-600 hover:text-slate-900` | Redirect ke index page |
| Simpan Aset | Submit | `bg-blue-600 text-white hover:bg-blue-700` | POST atau PUT, redirect ke show page |

### Edit Mode Notes

- Saat edit (status = Active), nomor aset tampil sebagai readonly
- Jika status Decommissioned, redirect ke show page (tidak bisa edit)
- Field sama dengan create mode

### Inertia Props

```typescript
interface FormProps {
    asset: Asset | null;        // null untuk create, filled untuk edit
    sites: Site[];
    areas: Area[];              // pre-filtered by site jika edit
    departments: Department[];
    can: {
        update: boolean;
    };
}
```

---

## 5. Halaman Show — Detail Aset

### Route: `GET /assets/{asset}` (`assets.show`)

### Permission: `asset.management.view`

### Wireframe — Desktop

```
┌───────────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                                │
│  ← Kembali ke Daftar                                                                  │
├───────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                       │
│  ┌─ Summary Card ──────────────────────────────────────────────────────────────────┐  │
│  │                                                                                 │  │
│  │  AST-0001                                    [🟠 Lifting] [🟢 Aktif] [⚠ Safety Critical] │
│  │  Crane 50 Ton Kapasitas                                                          │  │
│  │                                                                                 │  │
│  │  🏭 Site: Plant A   📍 Area: Produksi L1   🏢 Dept: Produksi                    │  │
│  │  🔢 Serial: CRN-50T-001   🏭 Model: XCMC-QY50K   🏭 Manuf: XCMG                │  │
│  │  📅 Pembelian: 15/01/2025   📅 Instalasi: 20/02/2025   📅 Garansi: 15/01/2027  │  │
│  │                                                                                 │  │
│  │  ┌─ Certificate Status Summary ──────────────────────────────────────────────┐ │  │
│  │  │  📋 Sertifikat: 3 total  🟢 Valid: 1  🟡 <30 Hari: 1  🔴 EXPIRED: 1      │ │  │
│  │  └─────────────────────────────────────────────────────────────────────────────┘ │  │
│  │                                                                                 │  │
│  │  ┌─ Action Buttons (permission-gated) ──────────────────────────────────────┐   │  │
│  │  │  [✏ Edit]  [🚫 Decommission]                                            │   │  │
│  │  └─────────────────────────────────────────────────────────────────────────┘   │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Tab Bar ────────────────────────────────────────────────────────────────────────┐  │
│  │  [📋 Overview]  [📜 Sertifikat (3)]  [🔍 Inspeksi (5)]  [💬 Comments]  [📝 Activity] │  │
│  └──────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Tab: Overview ──────────────────────────────────────────────────────────────────┐  │
│  │                                                                                  │  │
│  │  ┌─ Left Column (2/3) ─────────────────────────────────┐  ┌─ Right Column (1/3) ──────────────────┐ │  │
│  │  │                                                     │  │                                     │ │  │
│  │  │  DETAIL ASET                                        │  │  ┌─ INFO ASET ────────────────────┐ │ │  │
│  │  │  ─────────────────────────────────────────────       │  │  │ Kategori:      Lifting          │ │ │  │
│  │  │  Nomor Aset:    AST-0001                           │  │  │ Serial Number: CRN-50T-001     │ │ │  │
│  │  │  Nama:           Crane 50 Ton Kapasitas             │  │  │ Model:         XCMC-QY50K       │ │ │  │
│  │  │  Status:         🟢 Aktif                           │  │  │ Manufacturer:  XCMG             │ │ │  │
│  │  │  Safety Critical: ⚠ YES                             │  │  │ Tgl Beli:      15/01/2025       │ │ │  │
│  │  │                                                     │  │  │ Tgl Instalasi: 20/02/2025       │ │ │  │
│  │  │  LOKASI                                             │  │  │ Masa Garansi:  15/01/2027       │ │ │  │
│  │  │  ─────────────────────────────────────────────       │  │  └─────────────────────────────────┘ │ │  │
│  │  │  Site:       Plant A                                │  │                                     │ │  │
│  │  │  Area:       Produksi L1                            │  │  ┌─ CAPA TERKAIT ─────────────────┐ │ │  │
│  │  │  Department: Produksi                               │  │  │ ACT-2026-0003                   │ │ │  │
│  │  │                                                     │  │  │ Status: In Progress             │ │ │  │
│  │  └─────────────────────────────────────────────────────┘  │  │ [🔗 Lihat CAPA]                 │ │ │  │
│  │  └──────────────────────────────────────────────────────────┘  └─────────────────────────────────┘ │ │  │
│  └──────────────────────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
└───────────────────────────────────────────────────────────────────────────────────────┘
```

### Tab: Sertifikat (Certificates)

```
┌───────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                       │
│  ┌─ Certificates Header ────────────────────────────────────────────────────────────┐  │
│  │  SERTIFIKAT ASET (3)                                         [+ Tambah Sertifikat] │  │
│  │  ─────────────────────────────────────────────────────────────────────────────── │  │
│  │  Ringkasan: 🟢 Valid: 1  🟡 <30 Hari: 1  🔴 EXPIRED: 1                          │  │
│  └──────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Certificate Card 1 ─────────────────────────────────────────────────────────────┐  │
│  │  Sertifikat Kalibrasi — SK-KAL-2025-001                  [🔴 EXPIRED]          │  │
│  │  ──────────────────────────────────────────────────────────────────────────    │  │
│  │  Tipe: Sertifikat Kalibrasi                                                     │  │
│  │  Nomor: SK-KAL-2025-001                                                         │  │
│  │  Diterbitkan: 15/01/2025   Kedaluwarsa: 15/01/2026   🔴 KEDALUWARSA            │  │
│  │  Issuing Body: Sucofindo                                                        │  │
│  │  File: [📄 sertifikat_kalibrasi.pdf]  [⬇ Download]                              │  │
│  │                                                                                 │  │
│  │  ┌─ Actions ──────────────────────────────────────────────────────────────┐     │  │
│  │  │  [✏ Edit]                                                             │     │  │
│  │  └─────────────────────────────────────────────────────────────────────────┘     │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Certificate Card 2 ─────────────────────────────────────────────────────────────┐  │
│  │  Surat Kelayakan Operasi — SKO-2025-045                  [🟡 <30 Hari]         │  │
│  │  ──────────────────────────────────────────────────────────────────────────    │  │
│  │  Tipe: Surat Kelayakan Operasi                                                  │  │
│  │  Nomor: SKO-2025-045                                                             │  │
│  │  Diterbitkan: 10/03/2025   Kedaluwarsa: 10/08/2026   🟡 Akan kedaluwarsa (25 hari) │  │
│  │  Issuing Body: Disnaker                                                          │  │
│  │  File: [📄 surat_kelayakan.pdf]  [⬇ Download]                                    │  │
│  │                                                                                 │  │
│  │  ┌─ Actions ──────────────────────────────────────────────────────────────┐     │  │
│  │  │  [✏ Edit]                                                             │     │  │
│  │  └─────────────────────────────────────────────────────────────────────────┘     │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Certificate Card 3 ─────────────────────────────────────────────────────────────┐  │
│  │  Sertifikat K3 Operator — SIO-2025-012                   [🟢 Valid]             │  │
│  │  ──────────────────────────────────────────────────────────────────────────    │  │
│  │  Tipe: Sertifikat K3 Operator Crane                                              │  │
│  │  Nomor: SIO-2025-012                                                             │  │
│  │  Diterbitkan: 05/06/2025   Kedaluwarsa: 05/06/2027   🟢 Valid                    │  │
│  │  Issuing Body: Kemnaker RI                                                       │  │
│  │  File: [📄 sertifikat_k3.pdf]  [⬇ Download]                                     │  │
│  │                                                                                 │  │
│  │  ┌─ Actions ──────────────────────────────────────────────────────────────┐     │  │
│  │  │  [✏ Edit]                                                             │     │  │
│  │  └─────────────────────────────────────────────────────────────────────────┘     │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
└───────────────────────────────────────────────────────────────────────────────────────┘
```

### Tab: Inspeksi (Inspections)

```
┌───────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                       │
│  ┌─ Inspections Header ──────────────────────────────────────────────────────────────┐  │
│  │  INSPEKSI ASET (5)                                         [+ Tambah Inspeksi]   │  │
│  │  ─────────────────────────────────────────────────────────────────────────────── │  │
│  │  Ringkasan: ✅ Pass: 3  ❌ Fail: 1  ⚠ Maintenance: 1                             │  │
│  │  Inspeksi Berikutnya: 15/08/2026                                                │  │
│  └──────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Inspection Card 1 ─────────────────────────────────────────────────────────────┐  │
│  │  Inspeksi 15/02/2026                                    [❌ Fail] [🔗 CAPA]    │  │
│  │  ──────────────────────────────────────────────────────────────────────────    │  │
│  │  Tanggal: 15/02/2026                                                             │  │
│  │  Inspector: Budi Santoso                                                         │  │
│  │  Hasil: ❌ Fail                                                                  │  │
│  │  Catatan: Ditemukan crack pada hook crane. Crane tidak boleh dioperasikan.       │  │
│  │  Inspeksi Berikutnya: 15/05/2026                                                 │  │
│  │  CAPA: [ACT-2026-0003 — In Progress]  [🔗 Lihat CAPA]                            │  │
│  │                                                                                 │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Inspection Card 2 ─────────────────────────────────────────────────────────────┐  │
│  │  Inspeksi 10/01/2026                              [⚠ Maintenance Required]     │  │
│  │  ──────────────────────────────────────────────────────────────────────────    │  │
│  │  Tanggal: 10/01/2026                                                             │  │
│  │  Inspector: Sari Wulandari                                                       │  │
│  │  Hasil: ⚠ Maintenance Required                                                   │  │
│  │  Catatan: Wire rope mulai menipis, perlu penggantian dalam 30 hari.             │  │
│  │  Inspeksi Berikutnya: 10/04/2026                                                 │  │
│  │                                                                                 │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Inspection Card 3 ─────────────────────────────────────────────────────────────┐  │
│  │  Inspeksi 15/10/2025                                     [✅ Pass]              │  │
│  │  ──────────────────────────────────────────────────────────────────────────    │  │
│  │  Tanggal: 15/10/2025                                                             │  │
│  │  Inspector: Budi Santoso                                                         │  │
│  │  Hasil: ✅ Pass                                                                  │  │
│  │  Catatan: Semua komponen dalam kondisi baik.                                     │  │
│  │  Inspeksi Berikutnya: 15/01/2026                                                 │  │
│  │                                                                                 │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Inspection Card 4, 5 ... (collapsed) ──────────────────────────────────────────┐  │
│  │  ...                                                                            │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
└───────────────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Tab

#### Tab: Overview

Menampilkan informasi lengkap aset dalam layout 2 kolom:
- **Left Column (2/3)**: Detail aset (nomor, nama, status, safety_critical), lokasi (site, area, department)
- **Right Column (1/3)**: Info aset (kategori, serial, model, manufacturer, tanggal), CAPA terkait (jika ada)

#### Tab: Sertifikat (Certificates)

Menampilkan daftar sertifikat dalam format card. Setiap card menampilkan:
- Certificate type, number, status badge
- Issued date, expiry date, issuing body
- File download link (jika ada file)
- Edit button (permission: `asset.certificates.update`)

Certificate card mendapat border warna sesuai status:
- Expired: `border-red-500 bg-red-50`
- Expiring Critical: `border-red-400 bg-red-50`
- Expiring Soon: `border-yellow-400 bg-yellow-50`
- Valid: `border-green-300`

#### Tab: Inspeksi (Inspections)

Menampilkan daftar inspeksi dalam format card, diurutkan dari terbaru. Setiap card menampilkan:
- Inspection date, result badge
- Inspector name
- Notes
- Next inspection date
- CAPA link (jika result = fail dan CAPA sudah linked)

Inspection card mendapat border warna sesuai result:
- Fail: `border-red-500 bg-red-50`
- Maintenance Required: `border-yellow-400 bg-yellow-50`
- Pass: `border-green-300`

### Inertia Props (Show Page)

```typescript
interface ShowProps {
    asset: Asset & {
        site: Site;
        area: Area | null;
        department: Department | null;
        certificates: (AssetCertificate & {
            certificate_file: ManagedFile | null;
        })[];
        inspections: (AssetInspection & {
            inspector: User;
            capa_action: CapaAction | null;
        })[];
        comments: Comment[];
        activities: ActivityLog[];
    };
    can: {
        update: boolean;
        create_certificate: boolean;
        update_certificate: boolean;
        create_inspection: boolean;
    };
}
```

---

## 6. Certificate Form (Inline di Asset Show)

### Route: POST `/assets/{asset}/certificates` (`assets.certificates.store`)

### Permission: `asset.certificates.create`

### Wireframe — Modal/Inline Form

```
┌──────────────────────────────────────────────────────────────────────────┐
│  Tambah Sertifikat                                                  [✕]  │
├──────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Tipe Sertifikat *    [Sertifikat Kalibrasi           ]                  │
│                       Contoh: Sertifikat Kalibrasi, Surat Kelayakan,     │
│                       Sertifikat K3, SIO                                │
│                                                                          │
│  Nomor Sertifikat *   [SK-KAL-2025-001                ]                  │
│                                                                          │
│  Tanggal Terbit *     [__/__/____]                                      │
│                                                                          │
│  Tanggal Kedaluwarsa  [__/__/____]   (opsional, kosong = permanen)      │
│                                                                          │
│  Issuing Body *       [Sucofindo                       ]                  │
│                                                                          │
│  File Sertifikat      [📄 Pilih file...]   (opsional, max 10MB)         │
│                       PDF, JPG, PNG, DOC                                │
│                                                                          │
│  [← Batal]                                    [Simpan Sertifikat]       │
│                                                                          │
└──────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Field

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Tipe Sertifikat | Text input | Yes | `required\|string\|max:100` | Free-text |
| Nomor Sertifikat | Text input | Yes | `required\|string\|max:100` | |
| Tanggal Terbit | Date picker | Yes | `required\|date` | |
| Tanggal Kedaluwarsa | Date picker | No | `nullable\|date\|after_or_equal:issued_date` | Null = permanent |
| Issuing Body | Text input | Yes | `required\|string\|max:255` | |
| File Sertifikat | File upload | No | `nullable\|file\|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx\|max:10240` | Max 10MB |

---

## 7. Inspection Form (Inline di Asset Show)

### Route: POST `/assets/{asset}/inspections` (`assets.inspections.store`)

### Permission: `asset.inspections.create`

### Wireframe — Modal/Inline Form

```
┌──────────────────────────────────────────────────────────────────────────┐
│  Tambah Inspeksi                                                    [✕]  │
├──────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Tanggal Inspeksi *   [__/__/____]                                      │
│                                                                          │
│  Inspector *          [— Pilih Inspector —    ▾]                        │
│                       Pilih user yang melakukan inspeksi                 │
│                                                                          │
│  Hasil Inspeksi *     [— Pilih Hasil —    ▾]                            │
│                       ○ Pass   ○ Fail   ○ Maintenance Required          │
│                                                                          │
│  Catatan              ┌──────────────────────────────────────────────┐   │
│                       │ Masukkan catatan inspeksi...                  │   │
│                       │                                              │   │
│                       └──────────────────────────────────────────────┘   │
│                                                                          │
│  Inspeksi Berikutnya  [__/__/____]   (opsional)                         │
│                       Jadwalkan inspeksi berikutnya                     │
│                                                                          │
│  [← Batal]                                    [Simpan Inspeksi]         │
│                                                                          │
└──────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Field

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Tanggal Inspeksi | Date picker | Yes | `required\|date` | |
| Inspector | Select (search) | Yes | `required\|exists:users,id` | Cari user berdasarkan nama |
| Hasil Inspeksi | Select/Radio | Yes | `required\|in:pass,fail,maintenance_required` | 3 pilihan |
| Catatan | Textarea | No | `nullable\|string` | |
| Inspeksi Berikutnya | Date picker | No | `nullable\|date\|after_or_equal:inspection_date` | |

### Post-Save Behavior (Fail Result)

Jika hasil inspeksi = `fail`, setelah simpan:
1. Tampilkan warning banner: "Inspeksi gagal. Aset tidak boleh dioperasikan hingga ditindaklanjuti."
2. Tombol "Create CAPA" muncul pada inspection card.
3. Aset ditandai dengan warning di list page.

---

## 8. Mobile Responsive

### Breakpoints

- Desktop: Full table, 2-column layout pada Show page
- Tablet (md): Table scroll horizontal, Show page tetap 2 kolom
- Mobile (sm): Card list代替 table, Show page 1 kolom

### Mobile Card List (mengganti Table)

```
┌──────────────────────────────────────┐
│  ┌────────────────────────────────┐  │
│  │ ⚠ AST-0001                     │  │
│  │ Crane 50 Ton Kapasitas          │  │
│  │ [🟠 Lifting] [🟢 Aktif] [⚠ SC] │  │
│  │ 🔴 Sertifikat: EXPIRED          │  │
│  │ 📍 Produksi L1   📅 15/01/25   │  │
│  │              [👁 Lihat] [✏ Edit] │  │
│  └────────────────────────────────┘  │
│  ┌────────────────────────────────┐  │
│  │ AST-0002                       │  │
│  │ APAR CO2 #12                    │  │
│  │ [🔴 Fire Eq.] [🟢 Aktif] [⚠ SC]│  │
│  │ 🔴 Sertifikat: EXPIRED          │  │
│  │ 📍 Area Fire    📅 20/03/24    │  │
│  │              [👁 Lihat] [✏ Edit] │  │
│  └────────────────────────────────┘  │
└──────────────────────────────────────┘
```

### Component List

| Component | File | Description |
|---|---|---|
| `AssetIndex` | `Pages/Modules/Asset/Index.tsx` | List page dengan filter, table, pagination |
| `AssetForm` | `Pages/Modules/Asset/Form.tsx` | Create/edit form |
| `AssetShow` | `Pages/Modules/Asset/Show.tsx` | Detail page dengan tabs |
| `AssetOverviewTab` | `Pages/Modules/Asset/Tabs/Overview.tsx` | Tab overview |
| `AssetCertificatesTab` | `Pages/Modules/Asset/Tabs/Certificates.tsx` | Tab sertifikat |
| `AssetInspectionsTab` | `Pages/Modules/Asset/Tabs/Inspections.tsx` | Tab inspeksi |
| `CertificateForm` | `Components/Asset/CertificateForm.tsx` | Modal form sertifikat |
| `InspectionForm` | `Components/Asset/InspectionForm.tsx` | Modal form inspeksi |
| `CertificateCard` | `Components/Asset/CertificateCard.tsx` | Card untuk sertifikat |
| `InspectionCard` | `Components/Asset/InspectionCard.tsx` | Card untuk inspeksi |
| `SafetyCriticalBadge` | `Components/Asset/SafetyCriticalBadge.tsx` | Badge safety-critical |
| `CertificateStatusBadge` | `Components/Asset/CertificateStatusBadge.tsx` | Badge status sertifikat |
| `InspectionResultBadge` | `Components/Asset/InspectionResultBadge.tsx` | Badge hasil inspeksi |
