# UI Pages — Incident / Accident / Near Miss Reporting

Spesifikasi wireframe halaman UI untuk modul Incident Reporting.

Semua label menggunakan Bahasa Indonesia. Tech stack: Laravel 12 + Inertia React + TypeScript + Tailwind CSS.

---

## Daftar Isi

1. [Navigasi](#1-navigasi)
2. [Color Coding](#2-color-coding)
3. [Halaman Index — Daftar Laporan](#3-halaman-index--daftar-laporan)
4. [Halaman Form — Buat/Edit Laporan](#4-halaman-form--buatedit-laporan)
5. [Halaman Show — Detail Laporan](#5-halaman-show--detail-laporan)
6. [Mobile Responsive](#6-mobile-responsive)

---

## 1. Navigasi

### Penempatan Menu

Tambahkan group baru `Modul QHSSE` pada array `menuGroups` di `AuthenticatedLayout.tsx`, setelah group `Core` dan sebelum `Masters`.

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
        label: 'Modul QHSSE',                              // ← NEW GROUP
        items: [
            { label: 'Laporan Insiden', routeName: 'incident-reporting.index', active: 'incident-reporting.*', permission: 'incident-reporting.view' },
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
│                        ┌──────────────────┐                         │
│                        │ Laporan Insiden  │                         │
│                        └──────────────────┘                         │
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

Menu hanya tampil jika user memiliki permission `incident-reporting.view`. Filtering dilakukan via `auth.permissions` pada layout (sudah ada mekanisme `permissions.has(item.permission)`).

---

## 2. Color Coding

### Severity Badge

| Severity   | Tailwind Class                              | Preview          |
|------------|---------------------------------------------|------------------|
| Critical   | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200`    | `🔴 Critical`    |
| High       | `bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200` | `🟠 High`        |
| Medium     | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 Medium`      |
| Low        | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | `🔵 Low`         |

### Status Badge

| Status         | Tailwind Class                              | Preview          |
|----------------|---------------------------------------------|------------------|
| Draft          | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | `⚪ Draft`       |
| Submitted      | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | `🔵 Submitted`   |
| Under Review   | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 Under Review` |
| Closed         | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Closed`      |
| Rejected       | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Rejected`    |

### Komponen Badge (Reusable)

```tsx
// Komponen: components/Badge.tsx
type BadgeColor = 'gray' | 'blue' | 'yellow' | 'green' | 'red' | 'orange';

function Badge({ label, color }: { label: string; color: BadgeColor }) {
    const colors: Record<BadgeColor, string> = {
        gray:   'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        blue:   'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        green:  'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        red:    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        orange: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
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

const severityColors: Record<string, BadgeColor> = {
    critical: 'red',
    high:     'orange',
    medium:   'yellow',
    low:      'blue',
};

const statusColors: Record<string, BadgeColor> = {
    draft:         'gray',
    submitted:     'blue',
    under_review:  'yellow',
    closed:        'green',
    rejected:      'red',
};
```

---

## 3. Halaman Index — Daftar Laporan

### Route: `GET /incident-reporting` (`incident-reporting.index`)

### Permission: `incident-reporting.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                       │
│  Laporan Insiden                                        [+ Buat Laporan]    │
│  Kelola laporan insiden, kecelakaan, dan near miss                          │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari nomor, judul, reporter...           ]                          │  │
│  │                                                                        │  │
│  │ Status: [Semua ▾]  Kategori: [Semua ▾]  Severity: [Semua ▾]           │  │
│  │ Site:   [Semua ▾]  Dari: [__/__/____]  Sampai: [__/__/____]  [Reset]  │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Toolbar ─────────────────────────────────────────────────────────────┐  │
│  │ Menampilkan 1–10 dari 47 laporan                  [⬇ Export CSV]      │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Table ─────────────────────────────────────────────────────────────────┐ │
│  │ Nomor        Judul            Kategori    Severity   Status      Tanggal │ │
│  ├─────────────────────────────────────────────────────────────────────────┤ │
│  │ INC-0001    Kecelakaan Kerja  Kecelakaan  🔴Critical 🟡Under Rev  11/07  │ │
│  │ INC-0002    Near Miss Lift    Near Miss   🟠High     🔵Submitted  10/07  │ │
│  │ INC-0003    Tumpahan Kimia    Lingkungan  🟡Medium   🟢Closed     09/07  │ │
│  │ INC-0004    Pintu Darurat     Safety      🔵Low       ⚪Draft      08/07  │ │
│  │ ...                                                                     │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│  ┌─ Table (cont.) ────────────────────────────────────────────────────────┐ │
│  │ ... Tanggal   Reporter     Aksi                                          │ │
│  │ ... 11/07/26 Budi S.      [👁 Lihat]                                    │ │
│  │ ... 10/07/26 Sari W.      [👁 Lihat]                                    │ │
│  │ ... 09/07/26 Andi P.      [👁 Lihat]                                    │ │
│  │ ... 08/07/26 Joni K.      [👁 Lihat] [✏ Edit]                          │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  ┌─ Pagination ───────────────────────────────────────────────────────────┐  │
│  │                              ‹ Sebelumnya   1  2  3  4  5   Berikutnya ›│  │
│  └────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Wireframe — Empty State

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                       │
│  Laporan Insiden                                        [+ Buat Laporan]    │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari nomor, judul, reporter...           ]                          │  │
│  │ Status: [Semua ▾]  Kategori: [Semua ▾]  Severity: [Semua ▾]           │  │
│  │ Site: [Semua ▾]   Dari: [__/__/____]  Sampai: [__/__/____]  [Reset]    │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │                                                                      │   │
│  │                          📋                                          │   │
│  │                                                                      │   │
│  │                   Belum ada laporan insiden                          │   │
│  │                                                                      │   │
│  │           Belum ada laporan yang dibuat. Klik tombol di bawah         │   │
│  │             untuk membuat laporan insiden pertama Anda.               │   │
│  │                                                                      │   │
│  │                      [+ Buat Laporan Pertama]                        │   │
│  │                                                                      │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Wireframe — Empty State (dengan filter aktif, tidak ada hasil)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                                                                              │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │                          🔍                                          │   │
│  │                                                                      │   │
│  │                   Tidak ada hasil ditemukan                         │   │
│  │                                                                      │   │
│  │            Tidak ada laporan yang cocok dengan filter yang            │   │
│  │            Anda pilih. Coba ubah atau reset filter.                   │   │
│  │                                                                      │   │
│  │                        [↻ Reset Filter]                             │   │
│  │                                                                      │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Element

#### Header

| Element           | Type        | Detail                                                             |
|-------------------|-------------|-------------------------------------------------------------------|
| Title             | `<h1>`     | "Laporan Insiden"                                                 |
| Subtitle          | `<p>`      | "Kelola laporan insiden, kecelakaan, dan near miss"               |
| Button "Buat Laporan" | `<Link>` | Route: `incident-reporting.create`, permission: `incident-reporting.create` |
| Button Style      | Tailwind   | `bg-blue-600 text-white hover:bg-blue-700`                         |

#### Search Box

| Element    | Type     | Detail                                              |
|------------|----------|-----------------------------------------------------|
| Placeholder| `<input>`| "Cari nomor, judul, reporter..."                    |
| Icon       | SVG      | Magnifying glass icon di kiri input                 |
| Behavior   | debounce | 300ms debounce, kirim ke server via Inertia visit   |
| Param      | query    | `?search=keyword`                                   |

#### Filter Dropdowns

| Filter       | Label         | Options                              | Param          |
|--------------|---------------|--------------------------------------|----------------|
| Status       | "Status"      | Semua, Draft, Submitted, Under Review, Closed, Rejected | `?status=`     |
| Kategori     | "Kategori"    | Semua + dari master Categories (type=incident) | `?category_id=`|
| Severity     | "Severity"    | Semua, Critical, High, Medium, Low   | `?severity=`   |
| Site         | "Site"        | Semua + dari master Sites            | `?site_id=`    |
| Date Range   | "Dari" / "Sampai" | Date picker untuk rentang tanggal | `?from=` `?to=`|
| Reset Button | Button        | "Reset" — clear all filters          | —              |

#### Table Columns

| #  | Column    | Key           | Width     | Align  | Badge? | Detail                                    |
|----|-----------|---------------|-----------|--------|--------|-------------------------------------------|
| 1  | Nomor     | `number`      | 120px     | left   | No     | Link ke show page, monospace font         |
| 2  | Judul     | `title`       | flex      | left   | No     | Truncate dengan `max-w-xs truncate`       |
| 3  | Kategori  | `category`    | 140px     | left   | Yes    | `bg-indigo-100 text-indigo-800`           |
| 4  | Severity  | `severity`    | 110px     | center | Yes    | Lihat [Color Coding](#2-color-coding)     |
| 5  | Status    | `status`      | 130px     | center | Yes    | Lihat [Color Coding](#2-color-coding)     |
| 6  | Tanggal   | `occurred_at` | 100px     | center | No     | Format: `dd/mm/yy`                         |
| 7  | Reporter  | `reporter`    | 130px     | left   | No     | Nama user                                 |
| 8  | Aksi      | —             | 120px     | center | No     | Lihat di bawah                             |

#### Aksi Column (per row)

| Action   | Icon | Permission                              | Condition            |
|----------|------|-----------------------------------------|----------------------|
| Lihat    | 👁   | `incident-reporting.view`               | Selalu tampil        |
| Edit     | ✏   | `incident-reporting.update`             | Status = Draft       |
| Hapus    | 🗑   | `incident-reporting.update`             | Status = Draft, soft-delete |

#### Pagination

```
Menampilkan 1–10 dari 47 laporan

‹ Sebelumnya   1  2  3  4  5   Berikutnya ›
```

- Menggunakan komponen Tailwind pagination standar.
- Tampilkan "Menampilkan X–Y dari Z laporan".
- 10 item per halaman (dapat di-configurable: 10/25/50).

#### Export CSV

| Element          | Detail                                                                  |
|------------------|-------------------------------------------------------------------------|
| Button           | `[⬇ Export CSV]`                                                        |
| Permission       | `incident-reporting.export`                                             |
| Behavior         | Export data sesuai filter aktif saat ini                                 |
| Endpoint         | `GET /incident-reporting/export?status=...&category_id=...&...`          |
| Response         | CSV download dengan kolom: Nomor, Judul, Kategori, Severity, Status, Tanggal, Reporter |

### Inertia Props

```typescript
interface IndexProps {
    incidents: {
        data: Incident[];
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
        category_id?: number;
        severity?: string;
        site_id?: number;
        from?: string;
        to?: string;
    };
    categories: Category[];
    sites: Site[];
    can: {
        create: boolean;
        export: boolean;
    };
}
```

---

## 4. Halaman Form — Buat/Edit Laporan

### Route

- Create: `GET /incident-reporting/create` (`incident-reporting.create`)
- Edit: `GET /incident-reporting/{id}/edit` (`incident-reporting.edit`)

### Permission

- Create: `incident-reporting.create`
- Edit: `incident-reporting.update` (hanya jika status = Draft)

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Buat Laporan Insiden                                                            │
│  Isi data laporan insiden dengan lengkap                                         │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Umum ──────────────────────────────────────────────────┐  │
│  │  INFORMASI UMUM                                                             │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Nomor Laporan        [Auto-generated — INC-0005              ]  ⓘ          │  │
│  │                       Nomor akan dibuat otomatis saat submit                  │  │
│  │                                                                             │  │
│  │  Judul Insiden *      [Masukkan judul insiden...              ]              │  │
│  │                                                                             │  │
│  │  Kategori *           [— Pilih Kategori —           ▾]                     │  │
│  │                        ○ Kecelakaan  ○ Near Miss  ○ Lingkungan             │  │
│  │                        ○ Safety      ○ Security   ○ Quality NCR            │  │
│  │                                                                             │  │
│  │  Tanggal Kejadian *   [__/__/____] [🕐]                                    │  │
│  │                        Tanggal dan waktu kejadian                            │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Lokasi ─────────────────────────────────────────────────────────┐  │
│  │  LOKASI                                                                     │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Site *                [— Pilih Site —    ▾]                                │  │
│  │                                                                             │  │
│  │  Area *                [— Pilih Area —    ▾]    (filtered by site)          │  │
│  │                                                                             │  │
│  │  Department *          [— Pilih Department —    ▾]  (filtered by site)     │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Klasifikasi ─────────────────────────────────────────────────────┐  │
│  │  KLASIFIKASI                                                                │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Severity *            [— Pilih Severity —    ▾]                           │  │
│  │                        ○ Critical  ○ High  ○ Medium  ○ Low                  │  │
│  │                                                                             │  │
│  │  Priority              [— Pilih Priority —    ▾]                           │  │
│  │                        ○ Urgent  ○ High  ○ Normal  ○ Low                    │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Deskripsi ──────────────────────────────────────────────────────┐  │
│  │  DESKRIPSI                                                                  │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Deskripsi Insiden *  ┌──────────────────────────────────────────────┐     │  │
│  │                        │                                              │     │  │
│  │                        │ Jelaskan kronologi kejadian secara detail... │     │  │
│  │                        │                                              │     │  │
│  │                        │                                              │     │  │
│  │                        └──────────────────────────────────────────────┘     │  │
│  │                        Minimal 20 karakter                                │  │
│  │                                                                             │  │
│  │  Tindakan Segera *    ┌──────────────────────────────────────────────┐     │  │
│  │  (Immediate Action)   │                                              │     │  │
│  │                        │ Tindakan yang dilakukan saat kejadian...     │     │  │
│  │                        │                                              │     │  │
│  │                        └──────────────────────────────────────────────┘     │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Orang Terlibat ─────────────────────────────────────────────────┐  │
│  │  ORANG TERLIBAT                                                            │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  ┌─ Repeater Item 1 ────────────────────────────────────────────────────┐  │  │
│  │  │ Karyawan *   [— Cari karyawan... —    ▾]                              │  │  │
│  │  │ Peran        [— Pilih Peran —    ▾]  ○ Korban  ○ Saksi  ○ Pelaku    │  │  │
│  │  │ Catatan      [Catatan tambahan...                 ]                   │  │  │
│  │  │                                                              [🗑 Hapus]│  │  │
│  │  └──────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  ┌─ Repeater Item 2 ────────────────────────────────────────────────────┐  │  │
│  │  │ Karyawan *   [— Cari karyawan... —    ▾]                              │  │  │
│  │  │ Peran        [— Pilih Peran —    ▾]                                  │  │  │
│  │  │ Catatan      [Catatan tambahan...                 ]                   │  │  │
│  │  │                                                              [🗑 Hapus]│  │  │
│  │  └──────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  [+ Tambah Orang Terlibat]                                                 │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Evidence ───────────────────────────────────────────────────────┐  │
│  │  EVIDENCE                                                                  │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  ┌─────────────────────────────────────────────────────────────────────┐   │  │
│  │  │                                                                     │   │  │
│  │  │              📁  Drag & drop file di sini                           │   │  │
│  │  │                  atau [Pilih File]                                  │   │  │
│  │  │                                                                     │   │  │
│  │  │              Maks 10MB per file. Format: jpg, png, pdf, docx        │   │  │
│  │  │                                                                     │   │  │
│  │  └─────────────────────────────────────────────────────────────────────┘   │  │
│  │                                                                             │  │
│  │  File terunggah:                                                           │  │
│  │  ┌──────────────────────────────────────────────────────────────────┐     │  │
│  │  │ 📎 foto_kejadian_1.jpg                          2.3 MB   [🗑]    │     │  │
│  │  │ 📎 laporan_polisi.pdf                            850 KB   [🗑]    │     │  │
│  │  └──────────────────────────────────────────────────────────────────┘     │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Action Bar (sticky bottom) ──────────────────────────────────────────────┐  │
│  │                                                                           │  │
│  │  [← Batal]                                    [Simpan Draft]  [Submit]  │  │
│  │                                                           (primary)       │  │
│  └───────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Section

#### Section: Informasi Umum

| Field              | Type            | Required | Validation                      | Detail                                     |
|--------------------|-----------------|----------|---------------------------------|--------------------------------------------|
| Nomor Laporan      | Text (readonly) | No       | —                               | Auto-generated saat submit. Placeholder "Auto-generated" |
| Judul Insiden      | Text input      | Yes      | `required, min:5, max:255`      | Placeholder: "Masukkan judul insiden..."   |
| Kategori           | Select dropdown | Yes      | `required, exists:categories`   | Options dari master Categories (type=incident) |
| Tanggal Kejadian   | DateTime picker | Yes      | `required, date, before_or_equal:now` | Format: `dd/mm/yyyy HH:mm`                |

#### Section: Lokasi

| Field      | Type            | Required | Validation                    | Detail                          |
|------------|-----------------|----------|-------------------------------|---------------------------------|
| Site       | Select dropdown | Yes      | `required, exists:sites`     | Dari master Sites               |
| Area       | Select dropdown | Yes      | `required, exists:areas`      | Filtered by site_id             |
| Department | Select dropdown | Yes      | `required, exists:departments`| Filtered by site_id             |

#### Section: Klasifikasi

| Field    | Type            | Required | Validation                  | Detail                             |
|----------|-----------------|----------|-----------------------------|------------------------------------|
| Severity | Select dropdown | Yes      | `required, in:critical,high,medium,low` | Critical / High / Medium / Low    |
| Priority | Select dropdown | No       | `nullable, in:urgent,high,normal,low`  | Urgent / High / Normal / Low      |

#### Section: Deskripsi

| Field             | Type         | Required | Validation               | Detail                                        |
|-------------------|--------------|----------|--------------------------|-----------------------------------------------|
| Deskripsi Insiden | Textarea     | Yes      | `required, min:20`      | Minimum 20 karakter. Rich text optional.       |
| Tindakan Segera   | Textarea     | Yes      | `required, min:10`      | Immediate action yang dilakukan saat kejadian  |

#### Section: Orang Terlibat (Repeater)

| Field     | Type            | Required | Validation                | Detail                              |
|-----------|-----------------|----------|---------------------------|-------------------------------------|
| Karyawan  | Select (search) | Yes      | `required, exists:employees` | Cari karyawan berdasarkan nama/NPK |
| Peran     | Select dropdown | Yes      | `required, in:korban,saksi,pelaku,lainnya` | Korban / Saksi / Pelaku / Lainnya  |
| Catatan   | Text input      | No       | `nullable, max:500`      | Catatan tambahan tentang keterlibatan |

Repeater behavior:
- Tambah item: klik `[+ Tambah Orang Terlibat]`
- Hapus item: klik tombol `[🗑 Hapus]` per item
- Minimum 0 items (opsional), tidak ada maksimum
- Setiap item dalam card terpisah dengan border

#### Section: Evidence (File Upload)

| Field | Type          | Required | Validation                            | Detail                           |
|-------|---------------|----------|---------------------------------------|----------------------------------|
| Files | File upload   | No       | `nullable, max:10 files, max:10240kb` | Drag & drop atau klik untuk pilih |

Accepted formats: `jpg, jpeg, png, pdf, docx, xlsx`
Maksimal: 10 file, 10MB per file
Uploaded files tampil dalam list dengan: icon, filename, size, delete button

### Action Buttons

| Button       | Type    | Style                                       | Behavior                                                                 |
|--------------|---------|---------------------------------------------|--------------------------------------------------------------------------|
| Batal        | Link    | `text-slate-600 hover:text-slate-900`       | Redirect ke index page                                                   |
| Simpan Draft | Submit  | `bg-gray-200 text-gray-800 hover:bg-gray-300` | `POST` atau `PUT` dengan `action=draft`. Tidak validasi field mandatory |
| Submit       | Submit  | `bg-blue-600 text-white hover:bg-blue-700`  | `POST` atau `PUT` dengan `action=submit`. Validasi semua field mandatory |

### Edit Mode Notes

- Saat edit (status = Draft), nomor laporan tetap "Auto-generated" jika belum ada
- Jika sudah ada nomor (submitted record yang di-reject → edit), nomor tampil sebagai readonly
- Section dan field sama dengan create mode

### Inertia Props

```typescript
interface FormProps {
    incident: Incident | null;        // null untuk create, filled untuk edit
    categories: Category[];
    sites: Site[];
    areas: Area[];                    // jika edit, pre-filtered by site
    departments: Department[];
    employees: Employee[];            // untuk search di repeater
    severities: Severity[];
    priorities: Priority[];
    can: {
        submit: boolean;
    };
}
```

---

## 5. Halaman Show — Detail Laporan

### Route: `GET /incident-reporting/{id}` (`incident-reporting.show`)

### Permission: `incident-reporting.view`

### Wireframe — Desktop

```
┌───────────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                                │
│  ← Kembali ke Daftar                                                                  │
├───────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                       │
│  ┌─ Summary Card ──────────────────────────────────────────────────────────────────┐  │
│  │                                                                                 │  │
│  │  INC-0001                                    [🔴 Critical] [🟡 Under Review]  │  │
│  │  Kecelakaan Kerja di Area Produksi                                              │  │
│  │                                                                                 │  │
│  │  📅 Tanggal Kejadian: 11/07/2026 14:30                                          │  │
│  │  🏭 Site: Plant A   📍 Area: Produksi   🏢 Dept: Produksi                      │  │
│  │  👤 Reporter: Budi Santoso   (budi.s@company.com)                               │  │
│  │  📁 Kategori: Kecelakaan   ⚡ Priority: Urgent                                 │  │
│  │                                                                                 │  │
│  │  ┌─ Action Buttons (permission-gated) ──────────────────────────────────────┐  │  │
│  │  │  [✏ Edit]  [📤 Submit]  [🔍 Review]  [✓ Close]  [🖨 Export PDF]         │  │  │
│  │  └─────────────────────────────────────────────────────────────────────────┘  │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Detail Layout: 2 columns ──────────────────────────────────────────────────────┐  │
│  │                                                                                  │  │
│  │  ┌─ Left Column (2/3) ─────────────────────────────────┐  ┌─ Right Column (1/3) ──────────────────┐ │  │
│  │  │                                                     │  │                                     │ │  │
│  │  │  DESKRIPSI INSIDEN                                  │  │  ┌─ INFO LOKASI ──────────────────┐ │ │  │
│  │  │  ─────────────────────────────────────────────      │  │  │ Site:       Plant A            │ │ │  │
│  │  │  Pada tanggal 11 Juli 2026 pukul 14:30 WIB,         │  │  │ Area:       Produksi           │ │ │  │
│  │  │  terjadi kecelakaan kerja di area produksi          │  │  │ Department: Produksi           │ │ │  │
│  │  │  Plant A. Karyawan Budi Santoso terluka saat        │  │  │                                 │ │ │  │
│  │  │  mengoperasikan mesin...                             │  │  └─────────────────────────────────┘ │ │  │
│  │  │                                                     │  │                                     │ │  │
│  │  │  TINDAKAN SEGERA                                    │  │  ┌─ REPORTER ─────────────────────┐ │ │  │
│  │  │  ─────────────────────────────────────────────      │  │  │ Nama:  Budi Santoso            │ │ │  │
│  │  │  Pertolongan pertama diberikan oleh First           │  │  │ Email: budi.s@company.com      │ │ │  │
│  │  │  Responder, korban dievakuasi ke klinik...          │  │  │ Role:  Operator                │ │ │  │
│  │  │                                                     │  │  │ Dept:  Produksi                │ │ │  │
│  │  │  ┌─ ORANG TERLIBAT ──────────────────────────────┐  │  │  └─────────────────────────────────┘ │ │  │
│  │  │  │                                                │  │  │                                     │ │  │
│  │  │  │  👤 Budi Santoso  — Korban                    │  │  │  ┌─ EVIDENCE ─────────────────────┐ │ │  │
│  │  │  │     Catatan: Korban mengalami luka di tangan  │  │  │  │                                 │ │ │  │
│  │  │  │                                                │  │  │  │  📎 foto_kejadian_1.jpg         │ │ │  │
│  │  │  │  👤 Andi Pratama  — Saksi                     │  │  │  │     2.3 MB   [⬇ Download]      │ │ │  │
│  │  │  │     Catatan: Melihat kejadian dari dekat       │  │  │  │                                 │ │ │  │
│  │  │  │                                                │  │  │  │  📎 laporan_polisi.pdf          │ │ │  │
│  │  │  └────────────────────────────────────────────────┘  │  │     850 KB   [⬇ Download]      │ │ │  │
│  │  │                                                     │  │  │                                 │ │ │  │
│  │  └─────────────────────────────────────────────────────┘  └───────────────────────────────────┘ │  │
│  │                                                                                  │  │
│  └──────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Workflow Timeline ──────────────────────────────────────────────────────────────┐  │
│  │  RIWAYAT WORKFLOW                                                                 │  │
│  │  ─────────────────────────────────────────────────────────────────────────────    │  │
│  │                                                                                   │  │
│  │  ●━━━ Draft                     11/07/2026 09:00   Budi Santoso                  │  │
│  │  │     Laporan dibuat sebagai draft                                               │  │
│  │  │                                                                                │  │
│  │  ●━━━ Submitted                 11/07/2026 10:15   Budi Santoso                  │  │
│  │  │     Laporan disubmit untuk review                                              │  │
│  │  │                                                                                │  │
│  │  ●━━━ Under Review              11/07/2026 11:30   QHSSE Officer (Sari W.)       │  │
│  │  │     Laporan sedang direview                                                    │  │
│  │  │                                                                                │  │
│  │  ○━━━ (Menunggu) Approved / In Progress                                           │  │
│  │  │                                                                                │  │
│  │  ○━━━ (Menunggu) Waiting Verification                                             │  │
│  │  │                                                                                │  │
│  │  ○━━━ Closed                                                                      │  │
│  │                                                                                   │  │
│  └───────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Comments Section ───────────────────────────────────────────────────────────────┐  │
│  │  KOMENTAR (3)                                                                     │  │
│  │  ─────────────────────────────────────────────────────────────────────────────    │  │
│  │                                                                                   │  │
│  │  ┌─ Comment 1 ───────────────────────────────────────────────────────────────┐   │  │
│  │  │ 👤 Sari W. (QHSSE Officer)                              11/07 11:35     │   │  │
│  │  │ Mohon lengkapi foto evidence dari area yang lebih dekat.                   │   │  │
│  │  └───────────────────────────────────────────────────────────────────────────┘   │  │
│  │                                                                                   │  │
│  │  ┌─ Comment 2 ───────────────────────────────────────────────────────────────┐   │  │
│  │  │ 👤 Budi S. (Reporter)                                  11/07 12:00     │   │  │
│  │  │ Sudah ditambahkan, mohon dicek.                                           │   │  │
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
│  │  📝 11/07/2026 09:00  Budi Santoso  Membuat laporan (draft)                       │  │
│  │  📤 11/07/2026 10:15  Budi Santoso  Submit laporan                                │  │
│  │  🔍 11/07/2026 11:30  Sari W.       Mulai review                                  │  │
│  │  💬 11/07/2026 11:35  Sari W.       Menambah komentar                             │  │
│  │  💬 11/07/2026 12:00  Budi Santoso  Menambah komentar                             │  │
│  │  📎 11/07/2026 12:05  Budi Santoso  Mengunggah file: foto_kejadian_2.jpg          │  │
│  │                                                                                   │  │
│  └───────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
└───────────────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Section

#### Summary Card

| Element        | Type      | Detail                                                          |
|----------------|-----------|-----------------------------------------------------------------|
| Nomor Laporan  | `<h2>`   | Monospace font, contoh: `INC-0001`                              |
| Judul          | `<h1>`   | Judul insiden                                                    |
| Severity Badge | Badge     | Lihat [Color Coding](#2-color-coding)                           |
| Status Badge   | Badge     | Lihat [Color Coding](#2-color-coding)                           |
| Tanggal        | Text      | "Tanggal Kejadian: dd/mm/yyyy HH:mm"                            |
| Location       | Text      | Site, Area, Department                                           |
| Reporter       | Text      | Nama + email                                                     |
| Kategori       | Text      | Dari master Categories                                           |
| Priority       | Text      | Dari master Priorities                                           |

#### Action Buttons (Permission-Gated)

Action buttons hanya tampil berdasarkan permission user dan status record saat ini:

| Button        | Permission                        | Condition (status)                          | Route                                          |
|---------------|-----------------------------------|---------------------------------------------|------------------------------------------------|
| Edit          | `incident-reporting.update`       | status = Draft atau Rejected                | `incident-reporting.edit`                      |
| Submit        | `incident-reporting.submit`       | status = Draft                              | `POST incident-reporting/{id}/submit`          |
| Review        | `incident-reporting.review`       | status = Submitted                          | `POST incident-reporting/{id}/review`         |
| Approve       | `incident-reporting.approve`      | status = Under Review                       | `POST incident-reporting/{id}/approve`         |
| Reject        | `incident-reporting.reject`       | status = Under Review, Waiting Verification | `POST incident-reporting/{id}/reject`          |
| Verify        | `incident-reporting.verify`       | status = Waiting Verification               | `POST incident-reporting/{id}/verify`          |
| Close         | `incident-reporting.close`        | status = Approved / Waiting Verification    | `POST incident-reporting/{id}/close`           |
| Reopen        | `incident-reporting.reopen`       | status = Closed                              | `POST incident-reporting/{id}/reopen`          |
| Export PDF    | `incident-reporting.export`       | Selalu tersedia                             | `GET incident-reporting/{id}/export`           |
| Cancel        | `incident-reporting.update`       | status != Closed, != Cancelled               | `POST incident-reporting/{id}/cancel`          |

Button styling:
- Primary action: `bg-blue-600 text-white hover:bg-blue-700`
- Secondary action: `bg-gray-200 text-gray-800 hover:bg-gray-300`
- Destructive (Reject, Cancel): `bg-red-600 text-white hover:bg-red-700`

Untuk Reject dan Cancel, tampilkan modal dialog untuk input alasan sebelum konfirmasi.

#### Info Lokasi (Right Column)

| Field      | Detail                |
|------------|-----------------------|
| Site       | Nama site             |
| Area       | Nama area             |
| Department | Nama department       |

#### Reporter (Right Column)

| Field | Detail                |
|-------|-----------------------|
| Nama  | User name             |
| Email | User email            |
| Role  | User role             |
| Dept  | User department       |

#### Evidence Files (Right Column)

Setiap file menampilkan:

| Element    | Detail                                    |
|------------|-------------------------------------------|
| Icon       | File type icon (📎 untuk semua, atau icon spesifik per tipe) |
| Filename   | Original filename                         |
| Size       | Formatted file size (KB/MB)              |
| Download   | `[⬇ Download]` button, permission: `incident-reporting.view` |
| Download URL | Private route: `GET /incident-reporting/{id}/files/{fileId}/download` |

#### Workflow Timeline

Menampilkan riwayat status record secara vertikal dengan timeline visual:

```
●━━━ Draft                11/07 09:00  Budi Santoso
│     Laporan dibuat sebagai draft
│
●━━━ Submitted            11/07 10:15  Budi Santoso
│     Laporan disubmit untuk review
│
●━━━ Under Review         11/07 11:30  Sari W.
│     Laporan sedang direview
│
○━━━ (Menunggu) Approved
│
○━━━ (Menunggu) Closed
```

- `●` = completed step (filled circle, `text-green-600`)
- `○` = pending step (hollow circle, `text-gray-400`)
- Current step disorot dengan ring biru
- Setiap step menampilkan: status label, timestamp, actor name, note/description
- Data dari `workflow_histories` table (module_name = 'incident-reporting', reference_id = incident.id)

#### Comments Section

| Element        | Detail                                                              |
|----------------|---------------------------------------------------------------------|
| Header         | "KOMENTAR (N)" dengan count                                         |
| Comment Card   | Avatar/initial, nama, role, timestamp, body text                    |
| Add Comment    | Textarea + "Kirim" button                                           |
| Permission     | User dengan `incident-reporting.view` dapat melihat, `incident-reporting.update` dapat menambah komentar |
| Endpoint       | `POST /incident-reporting/{id}/comments`                            |
| Data source    | `comments` table (module_name = 'incident-reporting', reference_id = incident.id) |

#### Activity Log

| Element    | Detail                                                                |
|------------|-----------------------------------------------------------------------|
| Header     | "LOG AKTIVITAS"                                                       |
| Entry      | Icon + timestamp + actor + description                               |
| Icons      | 📝 create, 📤 submit, 🔍 review, ✅ approve, ❌ reject, 💬 comment, 📎 file, ✏ update |
| Data source| `activity_logs` table (module_name = 'incident-reporting', reference_id = incident.id) |
| Permission | `incident-reporting.view` (read-only)                                |

### Inertia Props

```typescript
interface ShowProps {
    incident: Incident & {
        site: Site;
        area: Area;
        department: Department;
        category: Category;
        severity: Severity;
        priority: Priority;
        reporter: User;
        involved_persons: InvolvedPerson[];
        evidence_files: File[];
        workflow_histories: WorkflowHistory[];
        comments: Comment[];
        activity_logs: ActivityLog[];
    };
    can: {
        update: boolean;
        submit: boolean;
        review: boolean;
        approve: boolean;
        reject: boolean;
        verify: boolean;
        close: boolean;
        reopen: boolean;
        export: boolean;
    };
}
```

---

## 6. Mobile Responsive

### Breakpoints

Tailwind default breakpoints digunakan:

| Prefix | Min-width | Deskripsi         |
|--------|-----------|-------------------|
| `sm`   | 640px     | Small phones (landscape) |
| `md`   | 768px     | Tablets           |
| `lg`   | 1024px    | Desktop           |
| `xl`   | 1280px    | Large desktop     |

### Index Page — Mobile

```
┌──────────────────────────┐
│  Laporan Insiden         │
│           [+ Buat]       │
├──────────────────────────┤
│ [🔍 Cari...]             │
│ [Status ▾] [Kategori ▾]  │
│ [Severity ▾] [Site ▾]   │
│ [Dari] [Sampai] [Reset]  │
├──────────────────────────┤
│ 1–10 dari 47  [⬇ CSV]   │
├──────────────────────────┤
│ ┌──────────────────────┐ │
│ │ INC-0001            │ │
│ │ Kecelakaan Kerja     │ │
│ │ [🔴Critical][🟡Rev]  │ │
│ │ 11/07  Budi S.       │ │
│ │              [👁]    │ │
│ └──────────────────────┘ │
│ ┌──────────────────────┐ │
│ │ INC-0002            │ │
│ │ Near Miss Lift       │ │
│ │ [🟠High][🔵Subm]     │ │
│ │ 10/07  Sari W.       │ │
│ │              [👁]    │ │
│ └──────────────────────┘ │
├──────────────────────────┤
│      ‹  1  2  3  4  ›   │
└──────────────────────────┘
```

Perubahan pada mobile:
- **Table → Card list**: Setiap row berubah menjadi card vertikal. Nomor dan judul di atas, badges di tengah, metadata dan tombol aksi di bawah.
- **Filter**: Dropdown wrap ke baris berikutnya. Setiap filter mengambil lebar penuh atau setengah.
- **Search**: Full width, di atas filter.
- **Pagination**: Tombol lebih kecil, hanya menampilkan nomor halaman tanpa label "Sebelumnya"/"Berikutnya".
- **Button "Buat Laporan"**: Label disingkat menjadi "Buat" atau icon-only `+`.

### Form Page — Mobile

```
┌──────────────────────────┐
│  ← Buat Laporan Insiden  │
├──────────────────────────┤
│                          │
│  INFORMASI UMUM          │
│  ─────────────────       │
│  Nomor: Auto-generated   │
│  Judul *                 │
│  [........................]│
│  Kategori *              │
│  [Pilih ▾]               │
│  Tanggal Kejadian *      │
│  [__/__/____] [🕐]       │
│                          │
│  LOKASI                  │
│  ─────────────────       │
│  Site * [Pilih ▾]       │
│  Area * [Pilih ▾]       │
│  Dept * [Pilih ▾]       │
│                          │
│  KLASIFIKASI             │
│  ─────────────────       │
│  Severity * [Pilih ▾]   │
│  Priority  [Pilih ▾]    │
│                          │
│  DESKRIPSI               │
│  ─────────────────       │
│  Deskripsi *             │
│  ┌──────────────────┐    │
│  │                  │    │
│  │                  │    │
│  └──────────────────┘    │
│  Tindakan Segera *       │
│  ┌──────────────────┐    │
│  │                  │    │
│  └──────────────────┘    │
│                          │
│  ORANG TERLIBAT          │
│  ─────────────────       │
│  ┌──────────────────┐    │
│  │ Karyawan [▾]     │    │
│  │ Peran    [▾]     │    │
│  │ Catatan [...]    │    │
│  │           [🗑]   │    │
│  └──────────────────┘    │
│  [+ Tambah]              │
│                          │
│  EVIDENCE               │
│  ─────────────────       │
│  ┌──────────────────┐    │
│  │    📁 Drop here   │    │
│  │   [Pilih File]    │    │
│  └──────────────────┘    │
│  📎 foto_1.jpg [🗑]      │
│                          │
├──────────────────────────┤
│ [Batal] [Draft] [Submit] │ ← sticky bottom
└──────────────────────────┘
```

Perubahan pada mobile:
- **Layout**: Single column. Semua section ditumpuk vertikal.
- **Section headers**: Lebih ringkas, tidak ada divider line yang lebar.
- **Form fields**: Full width, label di atas input (bukan di samping).
- **Repeater**: Setiap item card full width, tombol hapus lebih kecil (icon-only).
- **File upload**: Drag & drop area lebih kecil. Fokus pada tombol "Pilih File".
- **Action bar**: Sticky di bawah layar. Tombol "Simpan Draft" dan "Submit" berdampingan. "Batal" bisa icon-only `←`.
- **Date picker**: Gunakan native mobile date picker.
- **Select dropdown**: Gunakan native mobile select untuk performa terbaik.

### Show Page — Mobile

```
┌──────────────────────────┐
│  ← Kembali               │
├──────────────────────────┤
│                          │
│  INC-0001                │
│  [🔴Critical] [🟡Rev]    │
│  Kecelakaan Kerja di      │
│  Area Produksi           │
│                          │
│  📅 11/07/2026 14:30     │
│  🏭 Plant A              │
│  📍 Area Produksi        │
│  🏢 Dept Produksi        │
│  👤 Budi Santoso         │
│  📁 Kecelakaan           │
│  ⚡ Urgent               │
│                          │
│  [✏ Edit] [📤 Submit]   │
│  [🔍 Review] [✓ Close]  │
│  [🖨 PDF]                │
│                          │
├──────────────────────────┤
│  DESKRIPSI INSIDEN       │
│  Pada tanggal 11 Juli... │
│                          │
│  TINDAKAN SEGERA         │
│  Pertolongan pertama...  │
│                          │
├──────────────────────────┤
│  ORANG TERLIBAT          │
│  👤 Budi S. — Korban    │
│  👤 Andi P. — Saksi     │
│                          │
├──────────────────────────┤
│  EVIDENCE               │
│  📎 foto_1.jpg [⬇]      │
│  📎 laporan.pdf   [⬇]   │
│                          │
├──────────────────────────┤
│  RIWAYAT WORKFLOW        │
│  ● Draft    11/07 09:00 │
│  ● Submit   11/07 10:15 │
│  ● Review   11/07 11:30 │
│  ○ Approved  (pending)   │
│  ○ Closed    (pending)   │
│                          │
├──────────────────────────┤
│  KOMENTAR (3)            │
│  👤 Sari W.  11:35       │
│  "Mohon lengkapi..."     │
│  👤 Budi S.  12:00       │
│  "Sudah ditambahkan..."  │
│  [Tulis komentar...]     │
│  [Kirim]                 │
│                          │
├──────────────────────────┤
│  LOG AKTIVITAS           │
│  📝 09:00 Membuat draft  │
│  📤 10:15 Submit laporan │
│  🔍 11:30 Mulai review   │
│  💬 11:35 Menambah komen  │
│  💬 12:00 Menambah komen  │
│  📎 12:05 Upload file    │
│                          │
└──────────────────────────┘
```

Perubahan pada mobile:
- **Layout**: Single column. 2-column layout desktop berubah menjadi 1 column stacked.
- **Summary card**: Semua info ditumpuk vertikal. Badges di baris terpisah.
- **Action buttons**: Wrap ke baris berikutnya, mungkin 2-3 per baris. Bisa di-scroll horizontal jika banyak.
- **Evidence files**: Full width, download button lebih kecil (icon-only `⬇`).
- **Workflow timeline**: Lebih ringkas, hanya status + timestamp. Note disembunyikan, tap untuk expand.
- **Comments**: Full width, input di bawah daftar komentar.
- **Activity log**: Ringkas, timestamp + description. Bisa di-scroll vertikal.
- **Tab navigation** (optional): Pada mobile, bisa gunakan tab bar horizontal untuk switch antar: "Detail" / "Workflow" / "Komentar" / "Log" untuk mengurangi scroll panjang.

### Optional Mobile Tab Layout

```
┌──────────────────────────┐
│  INC-0001                │
│  [🔴Critical] [🟡Rev]    │
│  Kecelakaan Kerja         │
│  [✏] [📤] [🔍] [✓] [🖨]  │
├──────────────────────────┤
│ [Detail] [Workflow] [💬] [Log] │ ← horizontal tabs
├──────────────────────────┤
│                          │
│  (content per tab)       │
│                          │
└──────────────────────────┘
```

### Responsive Tailwind Patterns

Gunakan pola berikut untuk responsive design:

```tsx
// Container
<div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

// Grid: 1 col mobile → 2 cols tablet → 3 cols desktop
<div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">

// Table → Card on mobile: hide table, show card list
<div className="hidden lg:block"> {/* table */} </div>
<div className="lg:hidden"> {/* card list */} </div>

// Buttons
<button className="w-full sm:w-auto px-4 py-2 ...">

// Form inputs
<input className="w-full text-sm sm:text-base ..." />

// Action bar sticky
<div className="sticky bottom-0 flex gap-2 border-t bg-white p-4 dark:bg-gray-900">
```

---

## Lampiran: Route Summary

| Method | Route                                        | Name                              | Permission                   |
|--------|----------------------------------------------|-----------------------------------|------------------------------|
| GET    | `/incident-reporting`                        | `incident-reporting.index`        | `.view`                      |
| GET    | `/incident-reporting/create`                 | `incident-reporting.create`       | `.create`                    |
| POST   | `/incident-reporting`                        | `incident-reporting.store`        | `.create`                    |
| GET    | `/incident-reporting/{id}`                   | `incident-reporting.show`         | `.view`                      |
| GET    | `/incident-reporting/{id}/edit`              | `incident-reporting.edit`        | `.update`                    |
| PUT    | `/incident-reporting/{id}`                   | `incident-reporting.update`      | `.update`                    |
| POST   | `/incident-reporting/{id}/submit`            | `incident-reporting.submit`       | `.submit`                    |
| POST   | `/incident-reporting/{id}/review`            | `incident-reporting.review`       | `.review`                    |
| POST   | `/incident-reporting/{id}/approve`          | `incident-reporting.approve`      | `.approve`                   |
| POST   | `/incident-reporting/{id}/reject`            | `incident-reporting.reject`       | `.reject`                    |
| POST   | `/incident-reporting/{id}/verify`            | `incident-reporting.verify`       | `.verify`                    |
| POST   | `/incident-reporting/{id}/close`             | `incident-reporting.close`        | `.close`                     |
| POST   | `/incident-reporting/{id}/reopen`            | `incident-reporting.reopen`       | `.reopen`                    |
| POST   | `/incident-reporting/{id}/cancel`            | `incident-reporting.cancel`       | `.update`                    |
| GET    | `/incident-reporting/export`                 | `incident-reporting.export`       | `.export`                    |
| GET    | `/incident-reporting/{id}/export`            | `incident-reporting.export-pdf`   | `.export`                    |
| POST   | `/incident-reporting/{id}/comments`          | `incident-reporting.comments.store` | `.update`                  |
| GET    | `/incident-reporting/{id}/files/{fileId}/download` | `incident-reporting.files.download` | `.view`             |
