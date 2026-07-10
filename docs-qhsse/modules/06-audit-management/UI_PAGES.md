# UI Pages — Audit Management

Spesifikasi wireframe halaman UI untuk modul Audit Management.

Semua label menggunakan Bahasa Indonesia. Tech stack: Laravel 12 + Inertia React + TypeScript + Tailwind CSS.

---

## Daftar Isi

1. [Navigasi](#1-navigasi)
2. [Color Coding](#2-color-coding)
3. [Halaman Index — Daftar Audit](#3-halaman-index--daftar-audit)
4. [Halaman Form — Buat/Edit Audit](#4-halaman-form--buatedit-audit)
5. [Halaman Show — Detail Audit (dengan Findings Tab)](#5-halaman-show--detail-audit)
6. [Finding Form (Inline di Audit Show)](#6-finding-form--inline-di-audit-show)
7. [Mobile Responsive](#7-mobile-responsive)

---

## 1. Navigasi

### Penempatan Menu

Tambahkan item menu `Audit Management` pada group `Modul QHSSE` di array `menuGroups` pada `AuthenticatedLayout.tsx`.

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
            { label: 'Audit Management', routeName: 'audits.index', active: 'audits.*', permission: 'audit.management.view' },  // ← NEW
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
│                        │ Audit Management  │                         │
│                        └──────────────────┘                         │
└──────────────────────────────────────────────────────────────────────┘
```

### Permission Filtering

Menu hanya tampil jika user memiliki permission `audit.management.view`. Filtering dilakukan via `auth.permissions` pada layout.

---

## 2. Color Coding

### Audit Status Badge

| Status | Tailwind Class | Preview |
|---|---|---|
| Planned | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | `⚪ Planned` |
| In Progress | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 In Progress` |
| Report Ready | `bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200` | `🟠 Report Ready` |
| Closed | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Closed` |

### Audit Type Badge

| Type | Tailwind Class | Preview |
|---|---|---|
| Internal | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | `🔵 Internal` |
| External | `bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200` | `🟣 External` |
| Supplier | `bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200` | `🔵 Supplier` |

### Finding Classification Badge

| Classification | Tailwind Class | Preview |
|---|---|---|
| Major | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Major` |
| Minor | `bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200` | `🟠 Minor` |
| Observation | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 Observation` |
| OFI | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | `🔵 OFI` |

### Finding Status Badge

| Status | Tailwind Class | Preview |
|---|---|---|
| Open | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Open` |
| Closed | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Closed` |

### Pemetaan Helper

```typescript
// utils/badgeColors.ts

const auditStatusColors: Record<string, BadgeColor> = {
    planned:      'gray',
    in_progress:   'yellow',
    report_ready:  'orange',
    closed:        'green',
};

const auditTypeColors: Record<string, BadgeColor> = {
    internal: 'blue',
    external: 'purple',
    supplier: 'indigo',
};

const findingClassificationColors: Record<string, BadgeColor> = {
    major:        'red',
    minor:        'orange',
    observation:  'yellow',
    ofi:          'blue',
};

const findingStatusColors: Record<string, BadgeColor> = {
    open:    'red',
    closed:  'green',
};
```

---

## 3. Halaman Index — Daftar Audit

### Route: `GET /audits` (`audits.index`)

### Permission: `audit.management.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                       │
│  Audit Management                                       [+ Buat Audit]     │
│  Kelola audit internal, eksternal, dan supplier                              │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari nomor, judul, lead auditor...       ]                          │  │
│  │                                                                        │  │
│  │ Status: [Semua ▾]  Tipe: [Semua ▾]  Site: [Semua ▾]                   │  │
│  │ Dari: [__/__/____]  Sampai: [__/__/____]  [Reset]                      │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Toolbar ─────────────────────────────────────────────────────────────┐  │
│  │ Menampilkan 1–15 dari 32 audit                    [⬇ Export CSV]      │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Table ─────────────────────────────────────────────────────────────────┐ │
│  │ Nomor         Judul            Tipe      Standar      Status     Mulai  │ │
│  ├─────────────────────────────────────────────────────────────────────────┤ │
│  │ AUD-0001    Audit Internal 1  🔵Internal ISO 45001   ⚪Planned  15/07  │ │
│  │ AUD-0002    Audit Supplier X  🔵Supplier SMK3       🟡In Prog  10/07  │ │
│  │ AUD-0003    Audit External S1 🟣External ISO 9001   🟠Report   05/07  │ │
│  │ AUD-0004    Audit Internal 2  🔵Internal ISO 14001  🟢Closed   01/07  │ │
│  │ ...                                                                     │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│  ┌─ Table (cont.) ────────────────────────────────────────────────────────┐ │
│  │ ... Mulai  Lead Auditor      Findings  Aksi                            │ │
│  │ ... 15/07  Budi Santoso      0         [👁 Lihat]                      │ │
│  │ ... 10/07  Sari Wulandari    3 (1🔴)   [👁 Lihat]                      │ │
│  │ ... 05/07  Andi Pratama      5 (2🔴)   [👁 Lihat]                      │ │
│  │ ... 01/07  Budi Santoso      2 (0🔴)   [👁 Lihat]                      │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  ┌─ Pagination ───────────────────────────────────────────────────────────┐  │
│  │                         ‹ Sebelumnya   1  2  3   Berikutnya ›          │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Wireframe — Empty State

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                                                                              │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │                          📋                                          │   │
│  │                                                                      │   │
│  │                   Belum ada audit                                    │   │
│  │                                                                      │   │
│  │           Belum ada audit yang dibuat. Klik tombol di bawah            │   │
│  │           untuk membuat audit pertama Anda.                          │   │
│  │                                                                      │   │
│  │                      [+ Buat Audit Pertama]                          │   │
│  │                                                                      │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Element

#### Header

| Element | Type | Detail |
|---|---|---|
| Title | `<h1>` | "Audit Management" |
| Subtitle | `<p>` | "Kelola audit internal, eksternal, dan supplier" |
| Button "Buat Audit" | `<Link>` | Route: `audits.create`, permission: `audit.management.create` |
| Button Style | Tailwind | `bg-blue-600 text-white hover:bg-blue-700` |

#### Search Box

| Element | Type | Detail |
|---|---|---|
| Placeholder | `<input>` | "Cari nomor, judul, lead auditor..." |
| Behavior | debounce | 300ms, Inertia visit |
| Param | query | `?search=keyword` |

#### Filter Dropdowns

| Filter | Label | Options | Param |
|---|---|---|---|
| Status | "Status" | Semua, Planned, In Progress, Report Ready, Closed | `?status=` |
| Tipe | "Tipe" | Semua, Internal, External, Supplier | `?type=` |
| Site | "Site" | Semua + dari master Sites | `?site_id=` |
| Date Range | "Dari" / "Sampai" | Date picker | `?from=` `?to=` |
| Reset | Button | "Reset" — clear all filters | — |

#### Table Columns

| # | Column | Key | Width | Align | Badge? | Detail |
|---|---|---|---|---|---|---|
| 1 | Nomor | `audit_number` | 120px | left | No | Link ke show page, monospace |
| 2 | Judul | `title` | flex | left | No | Truncate `max-w-xs` |
| 3 | Tipe | `type` | 100px | center | Yes | Lihat Color Coding |
| 4 | Standar | `standard` | 120px | left | No | Nullable, tampil "—" jika null |
| 5 | Status | `status` | 130px | center | Yes | Lihat Color Coding |
| 6 | Mulai | `start_date` | 90px | center | No | Format: `dd/mm/yy` |
| 7 | Lead Auditor | `lead_auditor.name` | 140px | left | No | Nama user |
| 8 | Findings | count | 100px | center | No | "3 (1🔴)" = total (major count) |
| 9 | Aksi | — | 100px | center | No | Lihat di bawah |

#### Aksi Column (per row)

| Action | Icon | Permission | Condition |
|---|---|---|---|
| Lihat | 👁 | `audit.management.view` | Selalu tampil |
| Edit | ✏ | `audit.management.update` | Status = Planned |

### Inertia Props

```typescript
interface IndexProps {
    audits: {
        data: Audit[];
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
        type?: string;
        site_id?: number;
        from?: string;
        to?: string;
    };
    sites: Site[];
    can: {
        create: boolean;
        export: boolean;
    };
}
```

---

## 4. Halaman Form — Buat/Edit Audit

### Route

- Create: `GET /audits/create` (`audits.create`)
- Edit: `GET /audits/{audit}/edit` (`audits.edit`)

### Permission

- Create: `audit.management.create`
- Edit: `audit.management.update` (hanya jika status = Planned)

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Buat Audit                                                                      │
│  Isi data audit dengan lengkap                                                   │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Audit ──────────────────────────────────────────────────┐  │
│  │  INFORMASI AUDIT                                                             │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Nomor Audit          [Auto-generated — AUD-0001              ]  ⓘ        │  │
│  │                       Nomor akan dibuat otomatis saat simpan                 │  │
│  │                                                                             │  │
│  │  Judul Audit *        [Masukkan judul audit...                ]              │  │
│  │                                                                             │  │
│  │  Tipe Audit *         [— Pilih Tipe —    ▾]                                 │  │
│  │                        ○ Internal   ○ External   ○ Supplier                  │  │
│  │                                                                             │  │
│  │  Standar              [ISO 45001:2018                        ]              │  │
│  │                       (opsional) Contoh: ISO 9001, ISO 14001, SMK3          │  │
│  │                                                                             │  │
│  │  Scope *              ┌──────────────────────────────────────────────┐       │  │
│  │  (Ruang Lingkup)      │ Jelaskan ruang lingkup audit...               │       │  │
│  │                        │                                              │       │  │
│  │                        │                                              │       │  │
│  │                        └──────────────────────────────────────────────┘       │  │
│  │                        Minimal 10 karakter                                   │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Lokasi & Auditor ─────────────────────────────────────────────────┐  │
│  │  LOKASI & AUDITOR                                                           │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Site *                [— Pilih Site —    ▾]                                │  │
│  │                                                                             │  │
│  │  Department            [— Pilih Department —    ▾]  (opsional, nullable)    │  │
│  │                                                                             │  │
│  │  Lead Auditor *        [— Pilih Lead Auditor —    ▾]                       │  │
│  │                        Pilih user yang akan memimpin audit                   │  │
│  │                                                                             │  │
│  │  Tanggal Mulai *      [__/__/____]                                          │  │
│  │                                                                             │  │
│  │  Tanggal Selesai      [__/__/____]   (opsional)                             │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Action Bar (sticky bottom) ──────────────────────────────────────────────────┐  │
│  │                                                                           │  │
│  │  [← Batal]                                          [Simpan Audit]      │  │
│  │                                                     (primary)             │  │
│  └───────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Section

#### Section: Informasi Audit

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Nomor Audit | Text (readonly) | No | — | Auto-generated saat create. Placeholder "Auto-generated" |
| Judul Audit | Text input | Yes | `required, min:5, max:255` | Placeholder: "Masukkan judul audit..." |
| Tipe Audit | Radio/Select | Yes | `required, in:internal,external,supplier` | Internal / External / Supplier |
| Standar | Text input | No | `nullable, max:100` | Contoh: ISO 45001:2018 |
| Scope | Textarea | Yes | `required, min:10` | Ruang lingkup audit |

#### Section: Lokasi & Auditor

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Site | Select dropdown | Yes | `required, exists:sites,id` | Dari master Sites |
| Department | Select dropdown | No | `nullable, exists:departments,id` | Filtered by site_id |
| Lead Auditor | Select (search) | Yes | `required, exists:users,id` | Cari user berdasarkan nama |
| Tanggal Mulai | Date picker | Yes | `required, date` | Format: `dd/mm/yyyy` |
| Tanggal Selesai | Date picker | No | `nullable, date, after_or_equal:start_date` | Opsional |

### Action Buttons

| Button | Type | Style | Behavior |
|---|---|---|---|
| Batal | Link | `text-slate-600 hover:text-slate-900` | Redirect ke index page |
| Simpan Audit | Submit | `bg-blue-600 text-white hover:bg-blue-700` | POST atau PUT, redirect ke show page |

### Edit Mode Notes

- Saat edit (status = Planned), nomor audit tampil sebagai readonly
- Jika status bukan Planned, redirect ke show page (tidak bisa edit)
- Field sama dengan create mode

### Inertia Props

```typescript
interface FormProps {
    audit: Audit | null;        // null untuk create, filled untuk edit
    sites: Site[];
    departments: Department[];  // pre-filtered by site jika edit
    auditors: User[];           // users yang bisa menjadi lead auditor
    can: {
        update: boolean;
    };
}
```

---

## 5. Halaman Show — Detail Audit

### Route: `GET /audits/{audit}` (`audits.show`)

### Permission: `audit.management.view`

### Wireframe — Desktop

```
┌───────────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                                │
│  ← Kembali ke Daftar                                                                  │
├───────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                       │
│  ┌─ Summary Card ──────────────────────────────────────────────────────────────────┐  │
│  │                                                                                 │  │
│  │  AUD-0001                                    [🔵 Internal] [⚪ Planned]        │  │
│  │  Audit Internal QHSSE Q3 2026                                                   │  │
│  │                                                                                 │  │
│  │  📅 Tanggal: 15/07/2026 — 17/07/2026                                            │  │
│  │  🏭 Site: Plant A   🏢 Dept: Produksi                                           │  │
│  │  👤 Lead Auditor: Budi Santoso                                                   │  │
│  │  📋 Standar: ISO 45001:2018                                                     │  │
│  │                                                                                 │  │
│  │  ┌─ Action Buttons (permission-gated) ──────────────────────────────────────┐   │  │
│  │  │  [✏ Edit]  [▶ Mulai Audit]  [📄 Generate Report]  [✓ Close Audit]      │   │  │
│  │  └─────────────────────────────────────────────────────────────────────────┘   │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Tab Bar ────────────────────────────────────────────────────────────────────────┐  │
│  │  [📋 Detail]  [🔍 Findings (3)]  [📁 Evidence]  [💬 Comments (2)]  [📝 Activity] │  │
│  └───────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Tab: Detail ────────────────────────────────────────────────────────────────────┐  │
│  │                                                                                  │  │
│  │  ┌─ Left Column (2/3) ─────────────────────────────────┐  ┌─ Right Column (1/3) ──────────────────┐ │  │
│  │  │                                                     │  │                                     │ │  │
│  │  │  SCOPE AUDIT                                       │  │  ┌─ INFO AUDIT ────────────────────┐ │ │  │
│  │  │  ─────────────────────────────────────────────      │  │  │ Tipe:       Internal           │ │ │  │
│  │  │  Audit mencakup sistem manajemen K3 di area         │  │  │ Standar:    ISO 45001:2018      │ │ │  │
│  │  │  produksi Plant A, termasuk review prosedur         │  │  │ Site:       Plant A             │ │ │  │
│  │  │  kerja aman, APD, dan emergency response.            │  │  │ Department: Produksi            │ │ │  │
│  │  │                                                     │  │  └─────────────────────────────────┘ │ │  │
│  │  │  SUMMARY                                           │  │                                     │ │  │
│  │  │  ─────────────────────────────────────────────      │  │  ┌─ LEAD AUDITOR ─────────────────┐ │ │  │
│  │  │  (Kosong sampai report di-generate)                  │  │  │ Nama:  Budi Santoso            │ │ │  │
│  │  │                                                     │  │  │ Email: budi.s@company.com       │ │ │  │
│  │  └─────────────────────────────────────────────────────┘  │  └─────────────────────────────────┘ │ │  │
│  │  └──────────────────────────────────────────────────────────┘                                     │ │  │
│  └──────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
└───────────────────────────────────────────────────────────────────────────────────────┘
```

### Tab: Findings

```
┌───────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                       │
│  ┌─ Findings Header ──────────────────────────────────────────────────────────────┐  │
│  │  TEMUAN AUDIT (3)                                          [+ Tambah Finding]  │  │
│  │  ───────────────────────────────────────────────────────────────────────────    │  │
│  │  Ringkasan: 🔴 Major: 1  🟠 Minor: 1  🟡 Observation: 0  🔵 OFI: 1            │  │
│  └────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Finding Card 1 ───────────────────────────────────────────────────────────────┐  │
│  │  AUD-0001-F01                              [🔴 Major] [🔴 Open]                │  │
│  │  ──────────────────────────────────────────────────────────────────────────    │  │
│  │  Deskripsi:                                                                    │  │
│  │  Prosedur lockout/tagout tidak diimplementasikan di area mesin produksi.       │  │
│  │  Ditemukan 3 mesin tanpa prosedur LOTO yang aktif.                             │  │
│  │                                                                                 │  │
│  │  Area: Produksi — Mesin CNC Line 2                                             │  │
│  │  Rekomendasi: Implementasi prosedur LOTO untuk semua mesin produksi.           │  │
│  │                                                                                 │  │
│  │  CAPA: [Belum terhubung]  [⚡ Create CAPA]  [🔗 Link CAPA]                    │  │
│  │                                                                                 │  │
│  │  ┌─ Actions ──────────────────────────────────────────────────────────────┐    │  │
│  │  │  [✏ Edit]  [✓ Close Finding]                                           │    │  │
│  │  └─────────────────────────────────────────────────────────────────────────┘    │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Finding Card 2 ───────────────────────────────────────────────────────────────┐  │
│  │  AUD-0001-F02                              [🟠 Minor] [🔴 Open]               │  │
│  │  ──────────────────────────────────────────────────────────────────────────    │  │
│  │  Deskripsi:                                                                    │  │
│  │  Beberapa karyawan tidak menggunakan APD lengkap (safety glasses) di area      │  │
│  │  produksi.                                                                     │  │
│  │                                                                                 │  │
│  │  Area: Produksi — Assembly Line                                                │  │
│  │  Rekomendasi: Briefing ulang kewajiban APD dan pengawasan supervisor.           │  │
│  │                                                                                 │  │
│  │  CAPA: [ACT-2026-0005] ✓ Terhubung   [👁 Lihat CAPA]                           │  │
│  │                                                                                 │  │
│  │  ┌─ Actions ──────────────────────────────────────────────────────────────┐    │  │
│  │  │  [✏ Edit]  [✓ Close Finding]                                           │    │  │
│  │  └─────────────────────────────────────────────────────────────────────────┘    │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Finding Card 3 ───────────────────────────────────────────────────────────────┐  │
│  │  AUD-0001-F03                              [🔵 OFI] [🟢 Closed]                │  │
│  │  ──────────────────────────────────────────────────────────────────────────    │  │
│  │  Deskripsi:                                                                    │  │
│  │  Pertimbangkan digitalisasi checklist inspeksi harian untuk efisiensi.         │  │
│  │                                                                                 │  │
│  │  Area: Produksi — All Lines                                                    │  │
│  │  Rekomendasi: Evaluasi sistem digital checklist.                                │  │
│  │                                                                                 │  │
│  │  CAPA: Tidak diperlukan (OFI)                                                  │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
└───────────────────────────────────────────────────────────────────────────────────────┘
```

### Action Buttons (Permission-Gated)

| Button | Permission | Condition (status) | Route |
|---|---|---|---|
| Edit | `audit.management.update` | status = Planned | `audits.edit` |
| Mulai Audit | `audit.management.execute` | status = Planned | `POST audits/{id}/start` |
| Generate Report | `audit.management.execute` | status = In Progress | `POST audits/{id}/generate-report` |
| Close Audit | `audit.management.close` | status = Report Ready, all findings closed | `POST audits/{id}/close` |

### Inertia Props

```typescript
interface ShowProps {
    audit: Audit & {
        site: Site;
        department: Department | null;
        lead_auditor: User;
        findings: (AuditFinding & {
            capa_action: CapaAction | null;
        })[];
        evidence_files: ManagedFile[];
        comments: Comment[];
        activities: ActivityLog[];
        workflow_history: WorkflowHistory[];
    };
    can: {
        update: boolean;
        execute: boolean;
        close: boolean;
        export: boolean;
        create_finding: boolean;
        update_finding: boolean;
        close_finding: boolean;
    };
    availableTransitions: {
        action_key: string;
        action_label: string;
        requires_reason: boolean;
        requires_summary: boolean;
    }[];
}
```

---

## 6. Finding Form (Inline di Audit Show)

Finding form ditampilkan sebagai **modal dialog** atau **inline expandable section** di tab Findings pada halaman Show audit.

### Wireframe — Modal Create Finding

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│  ┌─ Modal Overlay ──────────────────────────────────────────────────────────────┐ │
│  │                                                                              │ │
│  │  ┌─ Modal Dialog ─────────────────────────────────────────────────────────┐  │ │
│  │  │                                                                         │  │ │
│  │  │  Tambah Finding                              [✕]                       │  │ │
│  │  │  ─────────────────────────────────────────────────────────────────────  │  │ │
│  │  │                                                                         │  │ │
│  │  │  Nomor Finding     [Auto: AUD-0001-F04                ]  ⓘ            │  │ │
│  │  │                                                                         │  │ │
│  │  │  Klasifikasi *      [— Pilih Klasifikasi —    ▾]                      │  │ │
│  │  │                      ○ 🔴 Major  ○ 🟠 Minor                            │  │ │
│  │  │                      ○ 🟡 Observation  ○ 🔵 OFI                       │  │ │
│  │  │                                                                         │  │ │
│  │  │  Deskripsi *        ┌──────────────────────────────────────────────┐   │  │ │
│  │  │                      │ Jelaskan temuan audit secara detail...        │   │  │ │
│  │  │                      │                                              │   │  │ │
│  │  │                      │                                              │   │  │ │
│  │  │                      └──────────────────────────────────────────────┘   │  │ │
│  │  │                      Minimal 10 karakter                                │  │ │
│  │  │                                                                         │  │ │
│  │  │  Area / Proses      [Area produksi, mesin, atau proses... ]            │  │ │
│  │  │                      (opsional)                                          │  │ │
│  │  │                                                                         │  │ │
│  │  │  Rekomendasi        ┌──────────────────────────────────────────────┐   │  │ │
│  │  │                      │ Rekomendasi tindakan perbaikan...             │   │  │ │
│  │  │                      │                                              │   │  │ │
│  │  │                      └──────────────────────────────────────────────┘   │  │ │
│  │  │                      (opsional)                                          │  │ │
│  │  │                                                                         │  │ │
│  │  │  ┌─ Action Bar ─────────────────────────────────────────────────────┐   │  │ │
│  │  │  │  [✕ Batal]                                  [Simpan Finding]    │   │  │ │
│  │  │  └─────────────────────────────────────────────────────────────────────┘   │  │ │
│  │  │                                                                         │  │ │
│  │  └─────────────────────────────────────────────────────────────────────────┘  │ │
│  │                                                                              │ │
│  └──────────────────────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Modal Generate Report

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│  ┌─ Modal Dialog ──────────────────────────────────────────────────────────────┐ │
│  │                                                                             │ │
│  │  Generate Audit Report                          [✕]                        │ │
│  │  ─────────────────────────────────────────────────────────────────────      │ │
│  │                                                                             │ │
│  │  Status akan berubah menjadi "Report Ready".                               │ │
│  │  Isi ringkasan audit di bawah ini:                                         │ │
│  │                                                                             │ │
│  │  Ringkasan Audit *   ┌──────────────────────────────────────────────┐      │ │
│  │                       │ Tulis ringkasan hasil audit...               │      │ │
│  │                       │                                              │      │ │
│  │                       │                                              │      │ │
│  │                       │                                              │      │ │
│  │                       └──────────────────────────────────────────────┘      │ │
│  │                       Minimal 20 karakter                                    │ │
│  │                                                                             │ │
│  │  ┌─ Action Bar ─────────────────────────────────────────────────────┐      │ │
│  │  │  [✕ Batal]                                [Generate Report]     │      │ │
│  │  └────────────────────────────────────────────────────────────────────┘      │ │
│  │                                                                             │ │
│  └─────────────────────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Finding Action Buttons

Setiap finding card memiliki action buttons berikut:

| Button | Icon | Permission | Condition | Behavior |
|---|---|---|---|---|
| Edit | ✏ | `audit.findings.update` | Audit status = in_progress/report_ready, finding status = open | Buka modal edit finding |
| Close Finding | ✓ | `audit.findings.close` | finding status = open | POST `audits/{audit}/findings/{finding}/close` |
| Create CAPA | ⚡ | `audit.findings.update` | capa_action_id = null | Redirect ke CAPA create form dengan pre-filled source |
| Link CAPA | 🔗 | `audit.findings.update` | capa_action_id = null | Buka modal untuk pilih existing CAPA |
| Lihat CAPA | 👁 | `audit.findings.view` | capa_action_id != null | Redirect ke CAPA show page |
| Unlink CAPA | ✕ | `audit.findings.update` | capa_action_id != null | Unlink CAPA dari finding |

### Create CAPA Flow

Saat user klik "Create CAPA" pada finding:

1. Frontend redirect ke `GET /capa-actions/create?source_module=audit&source_reference_id={finding.id}&title={finding.description (truncated)}`
2. CAPA form pre-fills `source_module` dan `source_reference_id`
3. Saat CAPA disimpan, CAPA controller membuat CAPA record dan mengupdate `audit_findings.capa_action_id` dengan ID CAPA baru
4. User redirect kembali ke audit show page, finding card menampilkan badge "CAPA: [ACT-XXXX-XXXX] ✓ Terhubung"

---

## 7. Mobile Responsive

### Index Page — Mobile

```
┌──────────────────────────┐
│  Audit Management         │
│           [+ Buat]       │
├──────────────────────────┤
│ [🔍 Cari...]             │
│ [Status ▾] [Tipe ▾]      │
│ [Site ▾]                 │
│ [Dari] [Sampai] [Reset]  │
├──────────────────────────┤
│ 1–15 dari 32  [⬇ CSV]   │
├──────────────────────────┤
│ ┌──────────────────────┐ │
│ │ AUD-0001            │ │
│ │ Audit Internal Q3    │ │
│ │ [🔵Internal][⚪Plan] │ │
│ │ 15/07  Budi S.       │ │
│ │ Findings: 0          │ │
│ │              [👁]    │ │
│ └──────────────────────┘ │
│ ┌──────────────────────┐ │
│ │ AUD-0002            │ │
│ │ Audit Supplier X     │ │
│ │ [🔵Supplier][🟡Prog] │ │
│ │ 10/07  Sari W.       │ │
│ │ Findings: 3 (1🔴)    │ │
│ │              [👁]    │ │
│ └──────────────────────┘ │
├──────────────────────────┤
│      ‹  1  2  3  ›      │
└──────────────────────────┘
```

### Form Page — Mobile

```
┌──────────────────────────┐
│  ← Buat Audit            │
├──────────────────────────┤
│                          │
│  INFORMASI AUDIT         │
│  ─────────────────       │
│  Nomor: Auto-generated   │
│  Judul *                 │
│  [........................]│
│  Tipe *                  │
│  [Pilih ▾]               │
│  Standar                 │
│  [........................]│
│  Scope *                 │
│  ┌──────────────────┐    │
│  │                  │    │
│  │                  │    │
│  └──────────────────┘    │
│                          │
│  LOKASI & AUDITOR        │
│  ─────────────────       │
│  Site * [Pilih ▾]       │
│  Dept   [Pilih ▾]       │
│  Auditor * [Pilih ▾]    │
│  Mulai * [__/__/____]    │
│  Selesai [__/__/____]    │
│                          │
├──────────────────────────┤
│ [Batal]    [Simpan Audit]│ ← sticky bottom
└──────────────────────────┘
```

### Show Page — Mobile

```
┌──────────────────────────┐
│  ← Kembali               │
├──────────────────────────┤
│                          │
│  AUD-0001                │
│  [🔵Internal] [⚪Planned]│
│  Audit Internal Q3 2026  │
│                          │
│  📅 15/07 — 17/07        │
│  🏭 Plant A              │
│  🏢 Produksi             │
│  👤 Budi Santoso         │
│  📋 ISO 45001:2018       │
│                          │
│  [▶ Mulai Audit]         │
│  [✏ Edit]                │
│                          │
├──────────────────────────┤
│ [Detail] [Findings(3)]   │
│ [Evidence] [Comments]    │
│ [Activity]               │
├──────────────────────────┤
│                          │
│  TEMUAN AUDIT (3)        │
│  🔴Major:1 🟠Minor:1    │
│  🟡Obs:0 🔵OFI:1        │
│  [+ Tambah Finding]      │
│                          │
│ ┌──────────────────────┐ │
│ │ AUD-0001-F01        │ │
│ │ [🔴Major] [🔴Open]   │ │
│ │ Prosedur LOTO tidak  │ │
│ │ diimplementasikan... │ │
│ │ [⚡ Create CAPA]     │ │
│ └──────────────────────┘ │
│ ┌──────────────────────┐ │
│ │ AUD-0001-F02        │ │
│ │ [🟠Minor] [🔴Open]   │ │
│ │ APD tidak lengkap... │ │
│ │ [ACT-0005] ✓ Linked │ │
│ └──────────────────────┘ │
│                          │
└──────────────────────────┘
```

### Mobile Notes

- **Table → Card list**: Setiap row berubah menjadi card vertikal
- **Filter**: Dropdown wrap ke baris berikutnya
- **Findings tab**: Findings tampil sebagai card stack, tidak ada table
- **Finding modal**: Full screen modal di mobile
- **Action bar**: Sticky bottom dengan tombol primary
