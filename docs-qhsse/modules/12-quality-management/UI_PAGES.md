# UI Pages — Quality Management

Spesifikasi wireframe halaman UI untuk modul Quality Management (NCR + Customer Complaints).

Semua label menggunakan Bahasa Indonesia. Tech stack: Laravel 12 + Inertia React + TypeScript + Tailwind CSS.

---

## Daftar Isi

1. [Navigasi](#1-navigasi)
2. [Color Coding](#2-color-coding)
3. [NCR Index — Daftar NCR](#3-ncr-index--daftar-ncr)
4. [NCR Form — Buat/Edit NCR](#4-ncr-form--buatedit-ncr)
5. [NCR Show — Detail NCR](#5-ncr-show--detail-ncr)
6. [Complaint Index — Daftar Keluhan Pelanggan](#6-complaint-index--daftar-keluhan-pelanggan)
7. [Complaint Form — Buat/Edit Keluhan](#7-complaint-form--buatedit-keluhan)
8. [Complaint Show — Detail Keluhan](#8-complaint-show--detail-keluhan)
9. [Mobile Responsive](#9-mobile-responsive)

---

## 1. Navigasi

### Penempatan Menu

Tambahkan item menu pada group `Modul QHSSE` di `AuthenticatedLayout.tsx`.

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
            // ... existing items ...
            { label: 'NCR (Non-Conformance)', routeName: 'quality.ncrs.index', active: 'quality.ncrs.*', permission: 'quality.ncrs.view' },
            { label: 'Keluhan Pelanggan', routeName: 'quality.complaints.index', active: 'quality.complaints.*', permission: 'quality.complaints.view' },
        ],
    },
    {
        label: 'Masters',
        // ...
    },
];
```

### Wireframe Navigasi (Desktop)

```
┌──────────────────────────────────────────────────────────────────────┐
│  [Logo] QHSSE   Core ▾   Modul QHSSE ▾   Masters ▾   Admin ▾  [User]│
│                        ┌────────────────────────────┐               │
│                        │ Laporan Insiden            │               │
│                        │ ...                        │               │
│                        │ NCR (Non-Conformance)      │               │
│                        │ Keluhan Pelanggan          │               │
│                        └────────────────────────────┘               │
└──────────────────────────────────────────────────────────────────────┘
```

### Wireframe Navigasi (Mobile — Hamburger)

```
┌──────────────────────┐
│  [Logo] QHSSE   [☰]  │
├──────────────────────┤
│  MODUL QHSSE         │
│   Laporan Insiden    │
│   ...                │
│   NCR (Non-Conform)  │
│   Keluhan Pelanggan  │
│                      │
│  MASTERS             │
│   ...                │
├──────────────────────┤
│  John Doe            │
│  Profile   Log Out   │
└──────────────────────┘
```

### Permission Filtering

Menu NCR hanya tampil jika user memiliki `quality.ncrs.view`. Menu Keluhan Pelanggan hanya tampil jika user memiliki `quality.complaints.view`.

---

## 2. Color Coding

### Severity Badge

| Severity | Tailwind Class | Preview |
|---|---|---|
| Critical | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Critical` |
| High | `bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200` | `🟠 High` |
| Medium | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 Medium` |
| Low | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | `🔵 Low` |

### Status Badge (NCR)

| Status | Tailwind Class | Preview |
|---|---|---|
| Open | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | `🔵 Open` |
| Under Review | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 Under Review` |
| In Progress | `bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200` | `🟣 In Progress` |
| Closed | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Closed` |
| Rejected | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Rejected` |

### Status Badge (Complaint)

| Status | Tailwind Class | Preview |
|---|---|---|
| Open | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | `🔵 Open` |
| In Progress | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 In Progress` |
| Closed | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Closed` |

### Source Badge (NCR)

| Source | Label (Indonesian) | Badge Color |
|---|---|---|
| `internal` | Internal | `bg-indigo-100 text-indigo-800` |
| `external` | Eksternal | `bg-cyan-100 text-cyan-800` |
| `customer_complaint` | Keluhan Pelanggan | `bg-pink-100 text-pink-800` |
| `audit` | Audit | `bg-teal-100 text-teal-800` |
| `supplier` | Pemasok | `bg-amber-100 text-amber-800` |

---

## 3. NCR Index — Daftar NCR

### Route: `GET /ncrs` (`quality.ncrs.index`)

### Permission: `quality.ncrs.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                       │
│  Non-Conformance Report (NCR)                         [+ Buat NCR]           │
│  Kelola laporan ketidaksesuaian dan tindakan korektif                       │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari nomor, judul, produk/jasa...           ]                      │  │
│  │                                                                        │  │
│  │ Status: [Semua ▾]  Sumber: [Semua ▾]  Severity: [Semua ▾]             │  │
│  │ Site:   [Semua ▾]  Dari: [__/__/____]  Sampai: [__/__/____]  [Reset]  │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Toolbar ─────────────────────────────────────────────────────────────┐  │
│  │ Menampilkan 1–10 dari 32 NCR                      [⬇ Export CSV]       │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Table ─────────────────────────────────────────────────────────────────┐ │
│  │ Nomor          Judul              Sumber    Severity  Status       Tgl   │ │
│  ├─────────────────────────────────────────────────────────────────────────┤ │
│  │ NCR-2026-0001  Produk Cacat        Internal  🔴Crit  🟡UnderRev  11/07  │ │
│  │ NCR-2026-0002  Keluhan Pelanggan   Pelanggan 🟠High  🔵Open      10/07  │ │
│  │ NCR-2026-0003  Temuan Audit        Audit     🟡Med   🟣InProg    09/07  │ │
│  │ NCR-2026-0004  Supplier Non-Conf   Pemasok   🔵Low   🟢Closed    08/07  │ │
│  │ ...                                                                     │ │
│  │ ... Tgl   Site          Aksi                                             │ │
│  │ ... 11/07 Plant A       [👁 Lihat]                                      │ │
│  │ ... 10/07 Plant B       [👁 Lihat] [✏ Edit]                             │ │
│  │ ... 09/07 Plant A       [👁 Lihat]                                      │ │
│  │ ... 08/07 Plant C       [👁 Lihat]                                      │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  ┌─ Pagination ───────────────────────────────────────────────────────────┐  │
│  │                          ‹ Sebelumnya   1  2  3  4   Berikutnya ›      │  │
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
│  │                   Belum ada NCR                                      │   │
│  │                                                                      │   │
│  │           Belum ada laporan ketidaksesuaian yang dibuat.            │   │
│  │           Klik tombol di bawah untuk membuat NCR pertama.            │   │
│  │                                                                      │   │
│  │                      [+ Buat NCR Pertama]                            │   │
│  │                                                                      │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Elemen

#### Header

| Element | Type | Detail |
|---|---|---|
| Title | `<h1>` | "Non-Conformance Report (NCR)" |
| Subtitle | `<p>` | "Kelola laporan ketidaksesuaian dan tindakan korektif" |
| Button "Buat NCR" | `<Link>` | Route: `quality.ncrs.create`, permission: `quality.ncrs.create` |
| Button Style | Tailwind | `bg-blue-600 text-white hover:bg-blue-700` |

#### Filter Dropdowns

| Filter | Label | Options | Param |
|---|---|---|---|
| Status | "Status" | Semua, Open, Under Review, In Progress, Closed, Rejected | `?status=` |
| Sumber | "Sumber" | Semua, Internal, Eksternal, Keluhan Pelanggan, Audit, Pemasok | `?source=` |
| Severity | "Severity" | Semua + dari master Severities | `?severity_id=` |
| Site | "Site" | Semua + dari master Sites | `?site_id=` |
| Date Range | "Dari" / "Sampai" | Date picker | `?from=` `?to=` |
| Reset | Button | "Reset" | — |

#### Table Columns

| # | Column | Key | Width | Badge? | Detail |
|---|---|---|---|---|---|
| 1 | Nomor | `ncr_number` | 140px | No | Link ke show, monospace |
| 2 | Judul | `title` | flex | No | Truncate `max-w-xs truncate` |
| 3 | Sumber | `source` | 130px | Yes | Lihat Source Badge |
| 4 | Severity | `severity` | 110px | Yes | Lihat Color Coding |
| 5 | Status | `status` | 130px | Yes | Lihat Color Coding |
| 6 | Tanggal | `created_at` | 100px | No | Format `dd/mm/yy` |
| 7 | Site | `site.name` | 120px | No | Nama site |
| 8 | Aksi | — | 120px | No | Tombol aksi |

#### Aksi Column (per row)

| Action | Icon | Permission | Condition |
|---|---|---|---|
| Lihat | 👁 | `quality.ncrs.view` | Selalu tampil |
| Edit | ✏ | `quality.ncrs.update` | Status = Open atau Under Review |

#### Export CSV

| Element | Detail |
|---|---|
| Button | `[⬇ Export CSV]` |
| Permission | `quality.ncrs.export` |
| Endpoint | `GET /ncrs/export?status=...&source=...&...` |

### Inertia Props

```typescript
interface NcrIndexProps {
    items: {
        data: Ncr[];
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
        source?: string;
        severity_id?: number;
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

## 4. NCR Form — Buat/Edit NCR

### Route

- Create: `GET /ncrs/create` (`quality.ncrs.create`)
- Edit: `GET /ncrs/{ncr}/edit` (`quality.ncrs.edit`)

### Permission

- Create: `quality.ncrs.create`
- Edit: `quality.ncrs.update` (hanya jika status = `open` atau `under_review`)

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Buat NCR                                                                        │
│  Isi data laporan ketidaksesuaian dengan lengkap                                │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Umum ──────────────────────────────────────────────────┐  │
│  │  INFORMASI UMUM                                                             │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Nomor NCR            [Auto-generated — NCR-2026-0001        ]  ⓘ          │  │
│  │                       Nomor dibuat otomatis saat simpan                      │  │
│  │                                                                             │  │
│  │  Judul *              [Masukkan judul ketidaksesuaian...      ]              │  │
│  │                                                                             │  │
│  │  Sumber *             [— Pilih Sumber —    ▾]                               │  │
│  │                        ○ Internal  ○ Eksternal  ○ Keluhan Pelanggan          │  │
│  │                        ○ Audit      ○ Pemasok                                │  │
│  │                                                                             │  │
│  │  Site *               [— Pilih Site —    ▾]                                 │  │
│  │                                                                             │  │
│  │  Departemen           [— Pilih Departemen —    ▾]  (filtered by site)      │  │
│  │                                                                             │  │
│  │  Severity *           [— Pilih Severity —    ▾]                             │  │
│  │                        ○ Critical  ○ High  ○ Medium  ○ Low                   │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Detail Produk/Jasa ─────────────────────────────────────────────┐  │
│  │  DETAIL PRODUK/JASA                                                         │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Produk/Jasa           [Nama produk atau jasa...             ]             │  │
│  │                                                                             │  │
│  │  Batch/Lot            [Nomor batch/lot...                    ]             │  │
│  │                                                                             │  │
│  │  Nama Pelanggan       [Nama pelanggan (jika relevan)...      ]             │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Deskripsi ──────────────────────────────────────────────────────┐  │
│  │  DESKRIPSI KETIDAKSESUAIAN                                                 │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Deskripsi *          ┌──────────────────────────────────────────────┐     │  │
│  │                        │ Jelaskan ketidaksesuaian secara detail...   │     │  │
│  │                        │                                              │     │  │
│  │                        │                                              │     │  │
│  │                        └──────────────────────────────────────────────┘     │  │
│  │                        Minimal 20 karakter                                  │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Evidence ───────────────────────────────────────────────────────┐  │
│  │  EVIDENCE / LAMPIRAN                                                       │  │
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
│  │  │ 📎 foto_cacat_1.jpg                              2.3 MB   [🗑]    │     │  │
│  │  │ 📎 laporan_inspeksi.pdf                          850 KB   [🗑]    │     │  │
│  │  └──────────────────────────────────────────────────────────────────┘     │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Action Bar (sticky bottom) ──────────────────────────────────────────────┐  │
│  │                                                                           │  │
│  │  [← Batal]                                           [Simpan]  [Submit]  │  │
│  │                                                                (primary)  │  │
│  └───────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Inertia Props

```typescript
interface NcrFormProps {
    item: Ncr | null;  // null for create, populated for edit
    sites: Site[];
    departments: Department[];
    severities: Severity[];
    capaActions: { id: number; capa_number: string; title: string }[]; // for CAPA link dropdown
}
```

---

## 5. NCR Show — Detail NCR

### Route: `GET /ncrs/{ncr}` (`quality.ncrs.show`)

### Permission: `quality.ncrs.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  NCR-2026-0001                                        [⬇ Export]                │
│  Produk Cacat di Lini Produksi A                                                 │
│  [🔴 Critical]  [🟡 Under Review]  Internal  Plant A                            │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Summary Cards ────────────────────────────────────────────────────────────┐  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐      │  │
│  │  │ Severity    │  │ Status      │  │ Sumber      │  │ Dibuat      │      │  │
│  │  │ 🔴 Critical │  │ 🟡 UnderRev │  │ Internal    │  │ 11/07/2026  │      │  │
│  │  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘      │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Tabs ─────────────────────────────────────────────────────────────────────┐  │
│  │  [Detail]  [Root Cause Analysis]  [CAPA Link]  [Lampiran]  [Komentar]     │  │
│  │  [Aktivitas]                                                               │  │
│  └────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Tab: Detail ─────────────────────────────────────────────────────────────┐  │
│  │                                                                             │  │
│  │  Deskripsi:                                                                │  │
│  │  Ditemukan 5 unit produk dengan dimensi tidak sesuai spesifikasi pada      │  │
│  │  lini produksi A saat inspeksi shift pagi.                                 │  │
│  │                                                                             │  │
│  │  Site:           Plant A                                                    │  │
│  │  Departemen:     Produksi                                                   │  │
│  │  Produk/Jasa:    Panel Kontrol X-100                                        │  │
│  │  Batch/Lot:      LOT-2026-0711-A                                           │  │
│  │  Nama Pelanggan: —                                                          │  │
│  │  Severity:       Critical                                                   │  │
│  │                                                                             │  │
│  │  ┌─ Workflow Actions ──────────────────────────────────────────────────┐   │  │
│  │  │  Status saat ini: Under Review                                       │   │  │
│  │  │                                                                       │   │  │
│  │  │  [▶ Start Review (In Progress)]   [✕ Reject]                         │   │  │
│  │  │                                                                       │   │  │
│  │  │  Permission: quality.ncrs.update (review), quality.ncrs.close (close)│   │  │
│  │  └───────────────────────────────────────────────────────────────────────┘  │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Tab: Root Cause Analysis ────────────────────────────────────────────────┐  │
│  │                                                                             │  │
│  │  ┌─ Akar Masalah (Root Cause) ──────────────────────────────────────────┐  │  │
│  │  │  ┌──────────────────────────────────────────────────────────────┐    │  │  │
│  │  │  │ Analisis 5-Why menunjukkan mesin calibration drift          │    │  │  │
│  │  │  │ setelah 6 bulan tanpa recalibration.                         │    │  │
│  │  │  └──────────────────────────────────────────────────────────────┘    │  │  │
│  │  │  [✏ Edit Root Cause]  (permission: quality.ncrs.update)              │  │  │
│  │  └──────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  ┌─ Tindakan Korektif (Corrective Action) ─────────────────────────────┐  │  │
│  │  │  ┌──────────────────────────────────────────────────────────────┐    │  │  │
│  │  │  │ 1. Hentikan produksi di lini A                               │    │  │  │
│  │  │  │ 2. Recall 5 unit affected                                   │    │  │  │
│  │  │  │ 3. Recalibrate mesin                                        │    │  │  │
│  │  │  └──────────────────────────────────────────────────────────────┘    │  │  │
│  │  │  [✏ Edit Corrective Action]  (permission: quality.ncrs.update)      │  │  │
│  │  └──────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  ┌─ Tindakan Preventif (Preventive Action) ────────────────────────────┐  │  │
│  │  │  ┌──────────────────────────────────────────────────────────────┐    │  │  │
│  │  │  │ 1. Jadwalkan recalibration setiap 3 bulan                    │    │  │  │
│  │  │  │ 2. Tambahkan checklist kalibrasi di shift handover           │    │  │  │
│  │  │  └──────────────────────────────────────────────────────────────┘    │  │  │
│  │  │  [✏ Edit Preventive Action]  (permission: quality.ncrs.update)       │  │  │
│  │  └──────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  ┌─ Close NCR ──────────────────────────────────────────────────────────┐  │  │
│  │  │  ⚠ Menutup NCR memerlukan:                                           │  │  │
│  │  │    ✓ Root Cause sudah diisi                                          │  │  │
│  │  │    ✓ Corrective Action sudah diisi                                   │  │  │
│  │  │    ✓ Preventive Action sudah diisi                                   │  │  │
│  │  │                                                                       │  │  │
│  │  │  [🔒 Tutup NCR]  (permission: quality.ncrs.close)                    │  │  │
│  │  └──────────────────────────────────────────────────────────────────────┘  │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Tab: CAPA Link ───────────────────────────────────────────────────────────┐  │
│  │                                                                             │  │
│  │  CAPA Terkait:                                                              │  │
│  │  ┌──────────────────────────────────────────────────────────────────┐      │  │
│  │  │ ACT-2026-0003  Calibrasi Mesin Lini A                            │      │  │
│  │  │ Status: In Progress    [👁 Lihat CAPA]                          │      │  │
│  │  └──────────────────────────────────────────────────────────────────┘      │  │
│  │                                                                             │  │
│  │  — atau —                                                                   │  │
│  │                                                                             │  │
│  │  Belum ada CAPA terkait.                                                   │  │
│  │  [🔗 Link ke CAPA]  (permission: quality.ncrs.update)                      │  │
│  │  [➕ Buat CAPA Baru dari NCR ini]  (permission: capa.actions.create)       │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Tab: Lampiran ───────────────────────────────────────────────────────────┐  │
│  │                                                                             │  │
│  │  ┌──────────────────────────────────────────────────────────────────┐      │  │
│  │  │ 📎 foto_cacat_1.jpg                          2.3 MB   [⬇] [🗑]    │      │  │
│  │  │ 📷 foto_cacat_2.jpg                          1.8 MB   [⬇] [🗑]    │      │  │
│  │  │ 📄 laporan_inspeksi.pdf                      850 KB   [⬇] [🗑]    │      │  │
│  │  └──────────────────────────────────────────────────────────────────┘      │  │
│  │                                                                             │  │
│  │  [📁 Upload File]  (permission: quality.ncrs.update)                       │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Tab: Komentar ────────────────────────────────────────────────────────────┐  │
│  │                                                                             │  │
│  │  ┌─ Comment 1 ───────────────────────────────────────────────────────┐    │  │
│  │  │ 👤 Budi Santoso (QHSSE Officer) — 11/07/2026 09:30                 │    │  │
│  │  │ Perlu segera dilakukan RCA. Mohon input dari tim produksi.         │    │  │
│  │  │                                             [Balas]  [🗑]          │    │  │
│  │  └──────────────────────────────────────────────────────────────────────┘    │  │
│  │                                                                             │  │
│  │  ┌─ Reply ───────────────────────────────────────────────────────────┐    │  │
│  │  │ 👤 Andi Pratama (Supervisor) — 11/07/2026 10:15                    │    │  │
│  │  │ Setuju, kami akan investigasi hari ini.                           │    │  │
│  │  └──────────────────────────────────────────────────────────────────────┘    │  │
│  │                                                                             │  │
│  │  ┌─ Add Comment ─────────────────────────────────────────────────────┐    │  │
│  │  │ [Tulis komentar...                                       ]           │    │  │
│  │  │ ☐ Internal only                                                     │    │  │
│  │  │                                            [Kirim Komentar]          │    │  │
│  │  └──────────────────────────────────────────────────────────────────────┘    │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Tab: Aktivitas ───────────────────────────────────────────────────────────┐  │
│  │                                                                             │  │
│  │  ── 11/07/2026 14:30 — NCR dibuat oleh Budi Santoso                        │  │
│  │  ── 11/07/2026 14:35 — NCR di-submit oleh Budi Santoso                     │  │
│  │  ── 11/07/2026 15:00 — Status berubah: Open → Under Review oleh Sari W.    │  │
│  │  ── 11/07/2026 16:20 — Root Cause diperbarui oleh Andi P.                  │  │
│  │  ── 11/07/2026 16:25 — Corrective Action diperbarui oleh Andi P.           │  │
│  │  ── 11/07/2026 16:30 — Preventive Action diperbarui oleh Andi P.           │  │
│  │  ── 11/07/2026 16:35 — File diunggah: foto_cacat_1.jpg                     │  │
│  │  ── 11/07/2026 16:40 — CAPA terkait: ACT-2026-0003                         │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Inertia Props

```typescript
interface NcrShowProps {
    ncr: Ncr & {
        site: Site;
        department: Department | null;
        severity: Severity;
        capaAction: CapaAction | null;
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
    can: {
        update: boolean;
        close: boolean;
        export: boolean;
    };
}
```

---

## 6. Complaint Index — Daftar Keluhan Pelanggan

### Route: `GET /customer-complaints` (`quality.complaints.index`)

### Permission: `quality.complaints.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                       │
│  Keluhan Pelanggan                                  [+ Buat Keluhan]         │
│  Kelola keluhan pelanggan dan resolusinya                                    │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari nomor, nama pelanggan, deskripsi...    ]                       │  │
│  │                                                                        │  │
│  │ Status: [Semua ▾]  Severity: [Semua ▾]                                 │  │
│  │ Dari:  [__/__/____]  Sampai: [__/__/____]  [Reset]                    │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Toolbar ─────────────────────────────────────────────────────────────┐  │
│  │ Menampilkan 1–10 dari 18 keluhan                 [⬇ Export CSV]      │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Table ─────────────────────────────────────────────────────────────────┐ │
│  │ Nomor           Pelanggan         Tanggal  Severity  Status     NCR     │ │
│  ├─────────────────────────────────────────────────────────────────────────┤ │
│  │ NCR-2026-0005  PT Maju Jaya       10/07   🟠High   🟡InProg   Link    │ │
│  │ NCR-2026-0006  CV Abadi           09/07   🟡Med    🔵Open     —       │ │
│  │ NCR-2026-0007  PT Sentosa         08/07   🔵Low    🟢Closed   Link    │ │
│  │ NCR-2026-0008  PT Damai Sejahtera 07/07   🟠High   🟢Closed   —       │ │
│  │ ...                                                                     │ │
│  │ ... NCR     Aksi                                                        │ │
│  │ ... Link   [👁 Lihat]                                                   │ │
│  │ ... —     [👁 Lihat] [✏ Edit]                                          │ │
│  │ ... Link   [👁 Lihat]                                                   │ │
│  │ ... —     [👁 Lihat]                                                    │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  ┌─ Pagination ───────────────────────────────────────────────────────────┐  │
│  │                          ‹ Sebelumnya   1  2   Berikutnya ›             │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Table Columns

| # | Column | Key | Width | Badge? | Detail |
|---|---|---|---|---|---|
| 1 | Nomor | `complaint_number` | 140px | No | Link ke show, monospace |
| 2 | Pelanggan | `customer_name` | flex | No | Truncate |
| 3 | Tanggal | `complaint_date` | 100px | No | Format `dd/mm/yy` |
| 4 | Severity | `severity` | 110px | Yes | Lihat Color Coding |
| 5 | Status | `status` | 120px | Yes | Lihat Color Coding |
| 6 | NCR | `ncr_id` | 80px | No | "Link" jika ada, "—" jika tidak |
| 7 | Aksi | — | 120px | No | Tombol aksi |

### Inertia Props

```typescript
interface ComplaintIndexProps {
    items: {
        data: CustomerComplaint[];
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
        severity_id?: number;
        from?: string;
        to?: string;
    };
    can: {
        create: boolean;
        export: boolean;
    };
}
```

---

## 7. Complaint Form — Buat/Edit Keluhan

### Route

- Create: `GET /customer-complaints/create` (`quality.complaints.create`)
- Edit: `GET /customer-complaints/{complaint}/edit` (`quality.complaints.edit`)

### Permission

- Create: `quality.complaints.create`
- Edit: `quality.complaints.update` (hanya jika status = `open` atau `in_progress`)

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Buat Keluhan Pelanggan                                                          │
│  Catat keluhan pelanggan dan tautkan ke NCR jika relevan                         │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Pelanggan ─────────────────────────────────────────────┐  │
│  │  INFORMASI PELANGGAN                                                        │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Nomor Keluhan        [Auto-generated — NCR-2026-0005        ]  ⓘ        │  │
│  │                       Nomor dibuat otomatis saat simpan                      │  │
│  │                                                                             │  │
│  │  Nama Pelanggan *     [Nama pelanggan...                      ]             │  │
│  │                                                                             │  │
│  │  Kontak Pelanggan     [Telepon/email pelanggan...            ]             │  │
│  │                                                                             │  │
│  │  Tanggal Complaint *   [__/__/____]  [🕐]                                    │  │
│  │                        Tanggal complaint diterima                            │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Detail Keluhan ──────────────────────────────────────────────────┐  │
│  │  DETAIL KELUHAN                                                             │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Severity *           [— Pilih Severity —    ▾]                             │  │
│  │                        ○ Critical  ○ High  ○ Medium  ○ Low                   │  │
│  │                                                                             │  │
│  │  Deskripsi *          ┌──────────────────────────────────────────────┐     │  │
│  │                        │ Jelaskan keluhan pelanggan secara detail... │     │  │
│  │                        │                                              │     │  │
│  │                        │                                              │     │  │
│  │                        └──────────────────────────────────────────────┘     │  │
│  │                        Minimal 20 karakter                                  │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Link ke NCR (Opsional) ──────────────────────────────────────────┐  │
│  │  LINK KE NCR                                                                │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  NCR Terkait           [— Cari NCR... —    ▾]                               │  │
│  │                        Pilih NCR jika keluhan ini terkait                   │  │
│  │                        ketidaksesuaian yang sudah dicatat                   │  │
│  │                                                                             │  │
│  │  ☐ Buat NCR baru otomatis dari complaint ini                                │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Action Bar (sticky bottom) ──────────────────────────────────────────────┐  │
│  │                                                                           │  │
│  │  [← Batal]                                                      [Simpan] │  │
│  │                                                                (primary)  │  │
│  └───────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Inertia Props

```typescript
interface ComplaintFormProps {
    item: CustomerComplaint | null;  // null for create
    severities: Severity[];
    ncrs: { id: number; ncr_number: string; title: string }[]; // for NCR link dropdown
}
```

---

## 8. Complaint Show — Detail Keluhan

### Route: `GET /customer-complaints/{complaint}` (`quality.complaints.show`)

### Permission: `quality.complaints.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  NCR-2026-0005                                         [⬇ Export]               │
│  Keluhan Produk Cacat — PT Maju Jaya                                             │
│  [🟠 High]  [🟡 In Progress]                                                     │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Summary Cards ────────────────────────────────────────────────────────────┐  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐      │  │
│  │  │ Severity    │  │ Status       │  │ Tanggal     │  │ NCR Terkait │      │  │
│  │  │ 🟠 High     │  │ 🟡 InProg    │  │ 10/07/2026  │  │ NCR-0001    │      │  │
│  │  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘      │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Tabs ─────────────────────────────────────────────────────────────────────┐  │
│  │  [Detail]  [Resolusi]  [NCR Link]  [Lampiran]  [Komentar]  [Aktivitas]   │  │
│  └────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Tab: Detail ─────────────────────────────────────────────────────────────┐  │
│  │                                                                             │  │
│  │  Nama Pelanggan:   PT Maju Jaya                                             │  │
│  │  Kontak:           021-555-1234 (Bapak Andi)                                │  │
│  │  Tanggal:          10/07/2026                                               │  │
│  │  Severity:         High                                                     │  │
│  │  Status:           In Progress                                              │  │
│  │                                                                             │  │
│  │  Deskripsi:                                                                 │  │
│  │  Pelanggan melaporkan 3 unit panel kontrol yang diterima dalam kondisi      │  │
│  │  rusak. Komponen internal ditemukan lepas pada inspeksi penerimaan.        │  │
│  │                                                                             │  │
│  │  ┌─ Workflow Actions ──────────────────────────────────────────────────┐   │  │
│  │  │  [▶ Start Review (In Progress)]   [🔒 Tutup Keluhan]                 │   │  │
│  │  │  Permission: quality.complaints.update / quality.complaints.close    │   │  │
│  │  └───────────────────────────────────────────────────────────────────────┘  │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Tab: Resolusi ────────────────────────────────────────────────────────────┐  │
│  │                                                                             │  │
│  │  Resolusi:                                                                  │  │
│  │  ┌──────────────────────────────────────────────────────────────────┐      │  │
│  │  │ 1. Penggantian 3 unit panel kontrol                              │      │  │
│  │  │ 2. Investigasi penyebab kerusakan packaging                      │      │  │
│  │  │ 3. Perbaikan prosedur packing                                    │      │  │
│  │  └──────────────────────────────────────────────────────────────────┘      │  │
│  │  [✏ Edit Resolusi]  (permission: quality.complaints.update)               │  │
│  │                                                                             │  │
│  │  ┌─ Close Complaint ──────────────────────────────────────────────────┐   │  │
│  │  │  ⚠ Menutup keluhan memerlukan:                                      │   │  │
│  │  │    ✓ Resolusi sudah diisi                                           │   │  │
│  │  │                                                                      │   │  │
│  │  │  [🔒 Tutup Keluhan]  (permission: quality.complaints.close)         │   │  │
│  │  └──────────────────────────────────────────────────────────────────────┘  │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Tab: NCR Link ───────────────────────────────────────────────────────────┐  │
│  │                                                                             │  │
│  │  NCR Terkait:                                                              │  │
│  │  ┌──────────────────────────────────────────────────────────────────┐      │  │
│  │  │ NCR-2026-0001  Produk Cacat di Lini Produksi A                   │      │  │
│  │  │ Status: Under Review   Severity: Critical                        │      │  │
│  │  │ [👁 Lihat NCR]                                                    │      │  │
│  │  └──────────────────────────────────────────────────────────────────┘      │  │
│  │                                                                             │  │
│  │  — atau —                                                                   │  │
│  │                                                                             │  │
│  │  Belum ada NCR terkait.                                                    │  │
│  │  [🔗 Link ke NCR]  (permission: quality.complaints.update)                 │  │
│  │  [➕ Buat NCR dari Keluhan ini]  (permission: quality.ncrs.create)          │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Tab: Lampiran ───────────────────────────────────────────────────────────┐  │
│  │                                                                             │  │
│  │  ┌──────────────────────────────────────────────────────────────────┐      │  │
│  │  │ 📄 surat_keluhan_pt_maju.pdf                   1.2 MB   [⬇] [🗑]    │      │  │
│  │  │ 📷 foto_produk_rusak_1.jpg                    2.5 MB   [⬇] [🗑]    │      │  │
│  │  └──────────────────────────────────────────────────────────────────┘      │  │
│  │                                                                             │  │
│  │  [📁 Upload File]  (permission: quality.complaints.update)                 │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Tab: Komentar ────────────────────────────────────────────────────────────┐  │
│  │  (Same structure as NCR comments tab)                                      │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Tab: Aktivitas ───────────────────────────────────────────────────────────┐  │
│  │  ── 10/07/2026 09:00 — Keluhan dibuat oleh Sari Wulandari                   │  │
│  │  ── 10/07/2026 09:30 — Status berubah: Open → In Progress                   │  │
│  │  ── 10/07/2026 10:00 — NCR terkait ditautkan: NCR-2026-0001                 │  │
│  │  ── 11/07/2026 14:00 — Resolusi diperbarui oleh Budi S.                     │  │
│  │  ── 11/07/2026 14:05 — File diunggah: surat_keluhan_pt_maju.pdf             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Inertia Props

```typescript
interface ComplaintShowProps {
    complaint: CustomerComplaint & {
        severity: Severity;
        ncr: Ncr | null;
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
    can: {
        update: boolean;
        close: boolean;
        export: boolean;
    };
}
```

---

## 9. Mobile Responsive

### Prinsip

- Form NCR dan Complaint usable di mobile (min 375px width).
- Tabel index menjadi card list di mobile.
- Tab bar di show page menjadi scrollable horizontal di mobile.
- Action bar sticky bottom tetap accessible.
- Upload file di mobile menggunakan native file picker.

### Mobile Wireframe — NCR Index

```
┌──────────────────────┐
│  NCR                 │
│  [+ Buat NCR]        │
├──────────────────────┤
│  [🔍 Cari...]        │
│  Status: [Semua ▾]   │
│  Sumber: [Semua ▾]   │
├──────────────────────┤
│  Menampilkan 1-5    │
│  dari 32 NCR         │
├──────────────────────┤
│ ┌──────────────────┐ │
│ │ NCR-2026-0001    │ │
│ │ Produk Cacat     │ │
│ │ 🔴Crit 🟡UnderRev│ │
│ │ 11/07  Plant A   │ │
│ │ [👁] [✏]         │ │
│ └──────────────────┘ │
│ ┌──────────────────┐ │
│ │ NCR-2026-0002    │ │
│ │ Keluhan Pelanggan│ │
│ │ 🟠High 🔵Open    │ │
│ │ 10/07  Plant B   │ │
│ │ [👁] [✏]         │ │
│ └──────────────────┘ │
│                      │
│  ‹ 1  2  3  4  5 ›  │
└──────────────────────┘
```

### Mobile Wireframe — NCR Show (Tabs scrollable)

```
┌──────────────────────┐
│  NCR-2026-0001       │
│  Produk Cacat        │
│  🔴Crit 🟡UnderRev   │
├──────────────────────┤
│ ← Detail  RCA  CAPA →│  (scrollable)
├──────────────────────┤
│  Deskripsi:          │
│  Ditemukan 5 unit...  │
│                      │
│  Site: Plant A       │
│  Produk: Panel X-100 │
│  Batch: LOT-0711-A   │
│                      │
│  [▶ Start Review]    │
│  [✕ Reject]           │
└──────────────────────┘
```

---

## 10. Component List

| Component | Location | Used In |
|---|---|---|
| `Badge` | `components/Badge.tsx` | All pages — severity, status, source badges |
| `NcrForm` | `Pages/Modules/Quality/Ncr/Form.tsx` | NCR Create/Edit |
| `NcrIndex` | `Pages/Modules/Quality/Ncr/Index.tsx` | NCR list |
| `NcrShow` | `Pages/Modules/Quality/Ncr/Show.tsx` | NCR detail |
| `ComplaintForm` | `Pages/Modules/Quality/Complaint/Form.tsx` | Complaint Create/Edit |
| `ComplaintIndex` | `Pages/Modules/Quality/Complaint/Index.tsx` | Complaint list |
| `ComplaintShow` | `Pages/Modules/Quality/Complaint/Show.tsx` | Complaint detail |
| `RcaPanel` | `Pages/Modules/Quality/Ncr/Partials/RcaPanel.tsx` | NCR Show — RCA tab |
| `CapaLinkPanel` | `Pages/Modules/Quality/Ncr/Partials/CapaLinkPanel.tsx` | NCR Show — CAPA tab |
| `FileUpload` | `components/FileUpload.tsx` | NCR & Complaint forms/show |
| `CommentThread` | `components/CommentThread.tsx` | NCR & Complaint show |
| `ActivityTimeline` | `components/ActivityTimeline.tsx` | NCR & Complaint show |
| `WorkflowActions` | `components/WorkflowActions.tsx` | NCR & Complaint show |
