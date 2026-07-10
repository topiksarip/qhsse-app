# UI Pages — Inspection Checklist

Spesifikasi wireframe halaman UI untuk modul Inspection Checklist.

Semua label menggunakan Bahasa Indonesia. Tech stack: Laravel 12 + Inertia React + TypeScript + Tailwind CSS.

---

## Daftar Isi

1. [Navigasi](#1-navigasi)
2. [Color Coding](#2-color-coding)
3. [Halaman Template Index — Daftar Template](#3-halaman-template-index--daftar-template)
4. [Halaman Template Form — Buat/Edit Template](#4-halaman-template-form--buatedit-template)
5. [Halaman Inspection Index — Daftar Inspeksi](#5-halaman-inspection-index--daftar-inspeksi)
6. [Halaman Inspection Form — Eksekusi Inspeksi](#6-halaman-inspection-form--eksekusi-inspeksi)
7. [Halaman Inspection Show — Hasil Inspeksi](#7-halaman-inspection-show--hasil-inspeksi)
8. [Mobile Responsive](#8-mobile-responsive)

---

## 1. Navigasi

### Penempatan Menu

Tambahkan items baru pada group `Modul QHSSE` di `AuthenticatedLayout.tsx`:

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
            { label: 'Template Inspeksi', routeName: 'inspection.templates.index', active: 'inspection.templates.*', permission: 'inspection.checklists.view' },
            { label: 'Inspeksi', routeName: 'inspection.inspections.index', active: 'inspection.inspections.*', permission: 'inspection.results.view' },
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
│                        ┌──────────────────────┐                      │
│                        │ Laporan Insiden      │                      │
│                        │ Template Inspeksi    │                      │
│                        │ Inspeksi             │                      │
│                        └──────────────────────┘                      │
└──────────────────────────────────────────────────────────────────────┘
```

### Permission Filtering

- **Template Inspeksi** menu tampil jika user punya `inspection.checklists.view`
- **Inspeksi** menu tampil jika user punya `inspection.results.view`

---

## 2. Color Coding

### Status Badge

| Status | Tailwind Class | Preview |
|---|---|---|
| Pending | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | `⚪ Pending` |
| In Progress | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | `🔵 In Progress` |
| Completed | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Completed` |

### Overall Result Badge

| Result | Tailwind Class | Preview |
|---|---|---|
| Pass | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Pass` |
| Fail | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Fail` |
| Pending | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 Pending` |

### Answer Indicators

| Answer | Tailwind Class | Preview |
|---|---|---|
| Yes / Safe | `bg-green-100 text-green-800` | `✅ Ya` / `✅ Aman` |
| No / Unsafe | `bg-red-100 text-red-800` | `❌ Tidak` / `⚠ Tidak Aman` |
| NA | `bg-gray-100 text-gray-800` | `➖ N/A` |
| Scale (1-2) | `bg-red-100 text-red-800` | `🔴 1` / `🔴 2` |
| Scale (3) | `bg-yellow-100 text-yellow-800` | `🟡 3` |
| Scale (4-5) | `bg-green-100 text-green-800` | `🟢 4` / `🟢 5` |

### Template Active Badge

| Status | Tailwind Class | Preview |
|---|---|---|
| Aktif | `bg-green-100 text-green-800` | `🟢 Aktif` |
| Nonaktif | `bg-gray-100 text-gray-800` | `⚪ Nonaktif` |

### Unsafe Item Highlight

Item dengan `is_unsafe=true` ditampilkan dengan:
- Border kiri merah: `border-l-4 border-red-500`
- Background merah muda: `bg-red-50 dark:bg-red-900/20`
- Badge `⚠ Tidak Aman` di sebelah jawaban

---

## 3. Halaman Template Index — Daftar Template

### Route: `GET /inspection-templates` (`inspection.templates.index`)

### Permission: `inspection.checklists.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                       │
│  Template Inspeksi                                    [+ Buat Template]      │
│  Kelola template checklist inspeksi QHSSE                                   │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari kode, nama template...         ]                              │  │
│  │                                                                        │  │
│  │ Kategori: [Semua ▾]  Status: [Semua ▾]                 [Reset]        │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Toolbar ─────────────────────────────────────────────────────────────┐  │
│  │ Menampilkan 1–10 dari 23 template                  [⬇ Export CSV]     │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Table ─────────────────────────────────────────────────────────────────┐ │
│  │ Kode       Nama Template              Kategori      Item  Status  Aksi  │ │
│  ├─────────────────────────────────────────────────────────────────────────┤ │
│  │ SAF-001    Inspeksi Safety Harian    Safety        12    🟢Aktif [👁]  │ │
│  │ ENV-001    Inspeksi Lingkungan        Environment   8     🟢Aktif [👁]  │ │
│  │ EQP-001    Inspeksi Peralatan         Equipment     15    🟢Aktif [👁]  │ │
│  │ FIR-001    Inspeksi Fire Safety       Fire          10    🟢Aktif [👁]  │ │
│  │ HSK-001    Inspeksi Housekeeping 5S   Housekeeping   6    ⚪Nonakt [👁] │ │
│  │ ...                                                                     │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  ┌─ Pagination ───────────────────────────────────────────────────────────┐  │
│  │                              ‹ Sebelumnya   1  2  3   Berikutnya ›     │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Wireframe — Empty State

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │                                                                      │   │
│  │                          📋                                          │   │
│  │                                                                      │   │
│  │                   Belum ada template inspeksi                        │   │
│  │                                                                      │   │
│  │         Belum ada template yang dibuat. Klik tombol di bawah         │   │
│  │           untuk membuat template inspeksi pertama Anda.              │   │
│  │                                                                      │   │
│  │                      [+ Buat Template Pertama]                      │   │
│  │                                                                      │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Element

#### Header

| Element | Type | Detail |
|---|---|---|
| Title | `<h1>` | "Template Inspeksi" |
| Subtitle | `<p>` | "Kelola template checklist inspeksi QHSSE" |
| Button "Buat Template" | `<Link>` | Route: `inspection.templates.create`, permission: `inspection.checklists.create` |
| Button Style | Tailwind | `bg-blue-600 text-white hover:bg-blue-700` |

#### Filter Dropdowns

| Filter | Label | Options | Param |
|---|---|---|---|
| Kategori | "Kategori" | Semua, Safety, Environment, Equipment, Fire, Housekeeping, Security, Quality, Compliance | `?category=` |
| Status | "Status" | Semua, Aktif, Nonaktif | `?is_active=` |
| Search | Search box | Cari kode, nama | `?search=` |
| Reset | Button | Clear all filters | — |

#### Table Columns

| # | Column | Key | Width | Align | Badge? | Detail |
|---|---|---|---|---|---|---|
| 1 | Kode | `code` | 100px | left | No | Monospace font, link ke show |
| 2 | Nama Template | `name` | flex | left | No | Truncate |
| 3 | Kategori | `category` | 130px | left | Yes | `bg-indigo-100 text-indigo-800` |
| 4 | Item | `items_count` | 80px | center | No | Jumlah items |
| 5 | Status | `is_active` | 100px | center | Yes | Aktif/Nonaktif |
| 6 | Aksi | — | 150px | center | No | Lihat, Edit, Hapus |

#### Aksi Column (per row)

| Action | Icon | Permission | Condition |
|---|---|---|---|
| Lihat | 👁 | `inspection.checklists.view` | Selalu tampil |
| Edit | ✏ | `inspection.checklists.update` | Selalu tampil |
| Hapus | 🗑 | `inspection.checklists.delete` | Tidak ada inspection terkait |

### Inertia Props

```typescript
interface TemplateIndexProps {
    templates: {
        data: InspectionTemplate[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
    filters: {
        search?: string;
        category?: string;
        is_active?: string;
    };
    can: {
        create: boolean;
        update: boolean;
        delete: boolean;
        export: boolean;
    };
}
```

---

## 4. Halaman Template Form — Buat/Edit Template

### Route

- Create: `GET /inspection-templates/create` (`inspection.templates.create`)
- Edit: `GET /inspection-templates/{template}/edit` (`inspection.templates.edit`)

### Permission

- Create: `inspection.checklists.create`
- Edit: `inspection.checklists.update`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Buat Template Inspeksi                                                         │
│  Definisikan checklist inspeksi dengan item-item pertanyaan                      │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Template ──────────────────────────────────────────────┐  │
│  │  INFORMASI TEMPLATE                                                         │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Kode *              [SAF-001                              ]                 │  │
│  │                       Kode unik template (max 50 karakter)                   │  │
│  │                                                                             │  │
│  │  Nama *              [Inspeksi Safety Harian                ]               │  │
│  │                                                                             │  │
│  │  Kategori *          [— Pilih Kategori —           ▾]                      │  │
│  │                       ○ Safety  ○ Environment  ○ Equipment                 │  │
│  │                       ○ Fire     ○ Housekeeping  ○ Security               │  │
│  │                       ○ Quality  ○ Compliance                             │  │
│  │                                                                             │  │
│  │  Deskripsi           ┌──────────────────────────────────────────────┐      │  │
│  │                       │ Deskripsi template inspeksi...               │      │  │
│  │                       │                                              │      │  │
│  │                       └──────────────────────────────────────────────┘      │  │
│  │                                                                             │  │
│  │  Status Aktif        [✓] Aktif                                              │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Item Inspeksi ───────────────────────────────────────────────────┐  │
│  │  ITEM INSPEKSI                                                              │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  ┌─ Item 1 ──────────────────────────────────────────────────────────────┐  │  │
│  │  │ Pertanyaan *  [Apakah semua pekerja memakai APD?         ]           │  │  │
│  │  │ Tipe *        [Yes/No ▾]                                             │  │  │
│  │  │               ○ Yes/No  ○ Safe/Unsafe  ○ N/A  ○ Scale  ○ Text       │  │  │
│  │  │ Kategori     [PPE                    ]  (opsional, untuk grouping)   │  │  │
│  │  │ Wajib        [✓] Item wajib dijawab                                   │  │  │
│  │  │ Urutan       [1]                                                     │  │  │
│  │  │                                                            [🗑 Hapus] │  │  │
│  │  └──────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  ┌─ Item 2 ──────────────────────────────────────────────────────────────┐  │  │
│  │  │ Pertanyaan *  [Apakah fire extinguisher dalam kondisi baik? ]          │  │  │
│  │  │ Tipe *        [Safe/Unsafe ▾]                                         │  │  │
│  │  │ Kategori     [Fire Safety           ]                                 │  │  │
│  │  │ Wajib        [✓] Item wajib dijawab                                   │  │  │
│  │  │ Urutan       [2]                                                     │  │  │
│  │  │                                                            [🗑 Hapus] │  │  │
│  │  └──────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  ┌─ Item 3 ──────────────────────────────────────────────────────────────┐  │  │
│  │  │ Pertanyaan *  [Rate kebersihan area kerja (1-5)            ]           │  │  │
│  │  │ Tipe *        [Scale ▾]                                               │  │  │
│  │  │ Kategori     [Housekeeping           ]                                 │  │  │
│  │  │ Wajib        [ ] Opsional                                            │  │  │
│  │  │ Urutan       [3]                                                     │  │  │
│  │  │                                                            [🗑 Hapus] │  │  │
│  │  └──────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  [+ Tambah Item]                                                           │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Action Bar (sticky bottom) ──────────────────────────────────────────────┐  │
│  │                                                                           │  │
│  │  [← Batal]                                              [Simpan Template]  │  │
│  │                                                           (primary)       │  │
│  └───────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Item Type Options

| Type | Label | Answer Options |
|---|---|---|
| `yes_no` | Yes/No | `yes`, `no` |
| `safe_unsafe` | Safe/Unsafe | `safe`, `unsafe` |
| `na` | N/A | `na` |
| `scale` | Scale (1-5) | `1`, `2`, `3`, `4`, `5` |
| `text` | Text | Free text |

### Inertia Props

```typescript
interface TemplateFormProps {
    template: InspectionTemplate | null; // null for create
    items: InspectionItem[];
    categories: string[]; // available categories
}
```

---

## 5. Halaman Inspection Index — Daftar Inspeksi

### Route: `GET /inspections` (`inspection.inspections.index`)

### Permission: `inspection.results.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                       │
│  Inspeksi                                              [+ Buat Inspeksi]    │
│  Daftar inspeksi QHSSE                                                       │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari nomor inspeksi, template...      ]                            │  │
│  │                                                                        │  │
│  │ Status: [Semua ▾]  Template: [Semua ▾]  Site: [Semua ▾]               │  │
│  │ Hasil:  [Semua ▾]  Dari: [__/__/____]  Sampai: [__/__/____]  [Reset]  │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Toolbar ─────────────────────────────────────────────────────────────┐  │
│  │ Menampilkan 1–10 dari 34 inspeksi                  [⬇ Export CSV]     │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Table ─────────────────────────────────────────────────────────────────┐ │
│  │ Nomor       Template            Site      Inspector  Status   Hasil   │ │
│  ├─────────────────────────────────────────────────────────────────────────┤ │
│  │ INS-2026-0001 Safety Harian     Plant A   Budi S.   🟢Done   🟢Pass   │ │
│  │ INS-2026-0002 Lingkungan        Plant B   Sari W.   🔵In Pro 🟡Pending │ │
│  │ INS-2026-0003 Peralatan         Plant A   Andi P.   ⚪Pending 🟡Pending │ │
│  │ INS-2026-0004 Fire Safety       Plant C   Joni K.   🟢Done   🔴Fail   │ │
│  │ ...                                                                     │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│  ┌─ Table (cont.) ────────────────────────────────────────────────────────┐ │
│  │ ... Jadwal    Unsafe  Aksi                                             │ │
│  │ ... 11/07/26  0       [👁 Lihat] [▶ Mulai]                             │ │
│  │ ... 10/07/26  0       [👁 Lihat] [▶ Lanjutkan]                        │ │
│  │ ... 12/07/26  -       [👁 Lihat] [▶ Mulai]                             │ │
│  │ ... 09/07/26  3       [👁 Lihat] [⚠ Buat CAPA]                         │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  ┌─ Pagination ───────────────────────────────────────────────────────────┐  │
│  │                              ‹ Sebelumnya   1  2  3  4   Berikutnya ›  │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Filter Dropdowns

| Filter | Label | Options | Param |
|---|---|---|---|
| Search | Search box | Cari nomor, nama template | `?search=` |
| Status | "Status" | Semua, Pending, In Progress, Completed | `?status=` |
| Template | "Template" | Semua + dari master templates | `?template_id=` |
| Site | "Site" | Semua + dari master sites | `?site_id=` |
| Hasil | "Hasil" | Semua, Pass, Fail, Pending | `?overall_result=` |
| Date Range | "Dari" / "Sampai" | Date picker | `?from=` `?to=` |
| Reset | Button | Clear all filters | — |

### Table Columns

| # | Column | Key | Width | Align | Badge? | Detail |
|---|---|---|---|---|---|---|
| 1 | Nomor | `inspection_number` | 140px | left | No | Monospace, link ke show |
| 2 | Template | `template.name` | flex | left | No | Truncate |
| 3 | Site | `site.name` | 120px | left | No | |
| 4 | Inspector | `inspector.name` | 120px | left | No | |
| 5 | Status | `status` | 120px | center | Yes | Lihat Color Coding |
| 6 | Hasil | `overall_result` | 100px | center | Yes | Lihat Color Coding |
| 7 | Jadwal | `scheduled_at` | 100px | center | No | Format `dd/mm/yy` |
| 8 | Unsafe | `unsafe_count` | 80px | center | No | Count of unsafe items |
| 9 | Aksi | — | 160px | center | No | Lihat di bawah |

### Aksi Column (per row)

| Action | Icon | Permission | Condition |
|---|---|---|---|
| Lihat | 👁 | `inspection.results.view` | Selalu tampil |
| Mulai | ▶ | `inspection.checklists.execute` | Status = Pending |
| Lanjutkan | ▶ | `inspection.checklists.execute` | Status = In Progress |
| Buat CAPA | ⚠ | `capa.actions.create` | Status = Completed AND unsafe_count > 0 |

### Inertia Props

```typescript
interface InspectionIndexProps {
    inspections: {
        data: Inspection[];
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
        template_id?: number;
        site_id?: number;
        overall_result?: string;
        from?: string;
        to?: string;
    };
    templates: InspectionTemplate[]; // for filter dropdown
    sites: Site[]; // for filter dropdown
    can: {
        create: boolean;
        execute: boolean;
        export: boolean;
        createCapa: boolean;
    };
}
```

---

## 6. Halaman Inspection Form — Eksekusi Inspeksi

### Route

- Create: `GET /inspections/create` (`inspection.inspections.create`)
- Execute: `GET /inspections/{inspection}/execute` (`inspection.inspections.execute`)

### Permission

- Create: `inspection.checklists.execute`
- Execute: `inspection.checklists.execute`

### Wireframe — Create Form (Desktop)

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Buat Inspeksi                                                                  │
│  Pilih template dan jadwalkan inspeksi                                          │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Inspeksi ─────────────────────────────────────────────┐  │
│  │  INFORMASI INSPEKSI                                                        │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Nomor Inspeksi     [Auto-generated — INS-2026-0005          ]  ⓘ         │  │
│  │                       Nomor akan dibuat otomatis saat disimpan               │  │
│  │                                                                             │  │
│  │  Template *          [— Pilih Template —           ▾]                      │  │
│  │                       Hanya template aktif yang ditampilkan                  │  │
│  │                                                                             │  │
│  │  Site *              [— Pilih Site —              ▾]                      │  │
│  │                                                                             │  │
│  │  Area                [— Pilih Area —              ▾]  (opsional)          │  │
│  │                       (filtered by site)                                    │  │
│  │                                                                             │  │
│  │  Inspector *         [— Pilih Inspector —          ▾]                    │  │
│  │                       User yang akan melaksanakan inspeksi                  │  │
│  │                                                                             │  │
│  │  Jadwal *            [__/__/____] [🕐]                                    │  │
│  │                       Tanggal inspeksi dijadwalkan                          │  │
│  │                                                                             │  │
│  │  Catatan             ┌──────────────────────────────────────────────┐     │  │
│  │                       │ Catatan tambahan...                          │     │  │
│  │                       └──────────────────────────────────────────────┘     │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Action Bar (sticky bottom) ──────────────────────────────────────────────┐  │
│  │                                                                           │  │
│  │  [← Batal]                                          [Simpan Inspeksi]     │  │
│  └───────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Wireframe — Execute Form (Desktop)

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Eksekusi Inspeksi: INS-2026-0005                                               │
│  Inspeksi Safety Harian — Plant A                                               │
│  Status: 🔵 In Progress    Inspector: Budi Santoso    Jadwal: 11/07/2026       │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Item Inspeksi ───────────────────────────────────────────────────┐  │
│  │  ITEM INSPEKSI                                                              │  │
│  │  Jawab setiap item di bawah ini. Item bertanda * wajib dijawab.             │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  ┌─ Item 1: PPE ────────────────────────────────────────────────────────┐  │  │
│  │  │ Apakah semua pekerja memakai APD? *                  (Yes/No)         │  │  │
│  │  │                                                                       │  │  │
│  │  │  [✅ Ya]  [❌ Tidak]                                                  │  │  │
│  │  │                                                                       │  │  │
│  │  │  Catatan:  ┌────────────────────────────────────────────┐            │  │  │
│  │  │            │                                            │            │  │  │
│  │  │            └────────────────────────────────────────────┘            │  │  │
│  │  │                                                                       │  │  │
│  │  │  [📷 Upload Foto]                                                    │  │  │
│  │  └───────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  ┌─ Item 2: Fire Safety ───────────────────────────────────────────────┐  │  │
│  │  │ Apakah fire extinguisher dalam kondisi baik? *    (Safe/Unsafe)      │  │  │
│  │  │                                                                       │  │  │
│  │  │  [✅ Aman]  [⚠ Tidak Aman]                                          │  │  │
│  │  │                                                                       │  │  │
│  │  │  Catatan:  ┌────────────────────────────────────────────┐            │  │  │
│  │  │            │                                              │            │  │  │
│  │  │            └────────────────────────────────────────────┘            │  │  │
│  │  │                                                                       │  │  │
│  │  │  [📷 Upload Foto]                                                    │  │  │
│  │  └───────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  ┌─ Item 3: Housekeeping ──────────────────────────────────────────────┐  │  │
│  │  │ Rate kebersihan area kerja (1-5)                              (Scale)│  │  │
│  │  │                                                                       │  │  │
│  │  │  [🔴 1] [🔴 2] [🟡 3] [🟢 4] [🟢 5]                                │  │  │
│  │  │                                                                       │  │  │
│  │  │  Catatan:  ┌────────────────────────────────────────────┐            │  │  │
│  │  │            │                                            │            │  │  │
│  │  │            └────────────────────────────────────────────┘            │  │  │
│  │  └───────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  ┌─ Item 4 ────────────────────────────────────────────────────────────┐   │  │
│  │  │ Catatan tambahan untuk area ini.                            (Text)   │   │  │
│  │  │                                                                       │   │  │
│  │  │  ┌──────────────────────────────────────────────────────────┐       │   │  │
│  │  │  │ Tulis observasi tambahan...                               │       │   │  │
│  │  │  │                                                          │       │   │  │
│  │  │  └──────────────────────────────────────────────────────────┘       │   │  │
│  │  └───────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Catatan Umum ────────────────────────────────────────────────────┐  │
│  │  CATATAN UMUM                                                              │  │
│  │  ┌──────────────────────────────────────────────────────────────────┐      │  │
│  │  │ Catatan keseluruhan inspeksi...                                   │      │  │
│  │  │                                                                    │      │  │
│  │  └──────────────────────────────────────────────────────────────────┘      │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Action Bar (sticky bottom) ──────────────────────────────────────────────┐  │
│  │                                                                           │  │
│  │  [← Batal]                                          [✓ Selesaikan Inspeksi]│  │
│  └───────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Answer UI Components by Type

| Type | Component | Behavior |
|---|---|---|
| `yes_no` | Toggle button group: [✅ Ya] [❌ Tidak] | Single select, radio behavior |
| `safe_unsafe` | Toggle button group: [✅ Aman] [⚠ Tidak Aman] | Single select, radio behavior |
| `na` | Toggle button: [➖ N/A] | Single toggle, radio behavior |
| `scale` | Button group: [1] [2] [3] [4] [5] | Single select, colored buttons |
| `text` | Textarea | Free text input |

### Inertia Props

```typescript
interface InspectionExecuteProps {
    inspection: Inspection & {
        template: InspectionTemplate & {
            items: InspectionItem[];
        };
        site: Site;
        area: Area | null;
        inspector: User;
        results: InspectionResult[]; // keyed by inspection_item_id
    };
    can: {
        execute: boolean;
        complete: boolean;
    };
}
```

---

## 7. Halaman Inspection Show — Hasil Inspeksi

### Route: `GET /inspections/{inspection}` (`inspection.inspections.show`)

### Permission: `inspection.results.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Detail Inspeksi: INS-2026-0005                                                  │
│  Inspeksi Safety Harian — Plant A                                               │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Inspeksi ─────────────────────────────────────────────┐  │
│  │  INFORMASI INSPEKSI                                                        │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Nomor            INS-2026-0005                                             │  │
│  │  Template         Inspeksi Safety Harian (SAF-001)                          │  │
│  │  Site             Plant A                                                   │  │
│  │  Area             Area Produksi                                             │  │
│  │  Inspector        Budi Santoso                                              │  │
│  │  Jadwal           11/07/2026                                                │  │
│  │  Dieksekusi       11/07/2026 08:30                                          │  │
│  │  Status           🟢 Completed                                              │  │
│  │  Hasil            🔴 Fail                                                   │  │
│  │  Unsafe Items     2                                                         │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Warning: Unsafe Items Found ─────────────────────────────────────────────┐  │
│  │  ⚠ PERHATIAN                                                              │  │
│  │                                                                           │  │
│  │  Ditemukan 2 item tidak aman pada inspeksi ini.                           │  │
│  │  Disarankan untuk membuat CAPA (Corrective and Preventive Action).        │  │
│  │                                                                           │  │
│  │  [⚠ Buat CAPA dari Item Unsafe]                                          │  │
│  └───────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Hasil Inspeksi ──────────────────────────────────────────────────┐  │
│  │  HASIL INSPEKSI                                                            │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  ┌─ Item 1: PPE ────────────────────────────────────────────────────────┐  │  │
│  │  │ Apakah semua pekerja memakai APD?                                    │  │  │
│  │  │ Jawaban: ✅ Ya                                                        │  │  │
│  │  │ Catatan: Semua pekerja memakai APD lengkap.                          │  │  │
│  │  └───────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  ┌─ Item 2: Fire Safety (⚠ UNSAFE) ────────────────────────────────┐      │  │
│  │  │ ┃ Apakah fire extinguisher dalam kondisi baik?                     │      │  │
│  │  │ ┃ Jawaban: ⚠ Tidak Aman                                            │      │  │
│  │  │ ┃ Catatan: APAR di area produksi sudah kedaluwarsa (exp: 06/2026).│      │  │
│  │  │ ┃                                                                   │      │  │
│  │  │ ┃ [⚠ Buat CAPA untuk item ini]                                     │      │  │
│  │  └───────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  ┌─ Item 3: Housekeeping ──────────────────────────────────────────────┐  │  │
│  │  │ Rate kebersihan area kerja (1-5)                                     │  │  │
│  │  │ Jawaban: 🟢 4                                                        │  │  │
│  │  │ Catatan: Area cukup bersih, perlu perbaikan di zona B.               │  │  │
│  │  └───────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  ┌─ Item 4 (⚠ UNSAFE) ────────────────────────────────────────────────┐   │  │
│  │  │ ┃ Apakah jalur evakuasi bebas hambatan?                             │   │  │
│  │  │ ┃ Jawaban: ❌ Tidak                                                 │   │  │
│  │  │ ┃ Catatan: Jalur evakuasi tersumbat oleh barang di zona gudang.    │   │  │
│  │  │ ┃                                                                   │   │  │
│  │  │ ┃ [⚠ Buat CAPA untuk item ini]                                      │   │  │
│  │  └───────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Catatan Umum ────────────────────────────────────────────────────┐  │
│  │  CATATAN UMUM                                                              │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │  Inspeksi umum berjalan baik. Dua item perlu tindak lanjut:               │  │
│  │  APAR kedaluwarsa dan jalur evakuasi tersumbat.                           │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Evidence ────────────────────────────────────────────────────────┐  │
│  │  EVIDENCE                                                                  │  │
│  │  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐         │  │
│  │  │ 📷 foto_apar.jpg  │  │ 📷 foto_evakuasi │  │ 📷 foto_area.jpg │         │  │
│  │  │ 1.2 MB            │  │ 850 KB           │  │ 2.1 MB           │         │  │
│  │  └──────────────────┘  └──────────────────┘  └──────────────────┘         │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Activity Timeline ──────────────────────────────────────────────┐  │
│  │  ACTIVITY TIMELINE                                                         │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │  ● 11/07/26 08:30  Inspection started by Budi Santoso                     │  │
│  │  ● 11/07/26 09:15  Result saved: Item 2 marked unsafe                    │  │
│  │  ● 11/07/26 09:20  Result saved: Item 4 marked unsafe                    │  │
│  │  ● 11/07/26 09:30  Inspection completed by Budi Santoso                   │  │
│  │  ● 11/07/26 09:30  Overall result: FAIL                                   │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Action Bar ───────────────────────────────────────────────────────────────┐  │
│  │  [← Kembali]                     [⬇ Export PDF]                           │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Unsafe Item Highlighting

Item dengan `is_unsafe=true`:
- Border kiri merah tebal: `border-l-4 border-red-500`
- Background: `bg-red-50 dark:bg-red-900/20`
- Label `⚠ UNSAFE` atau `⚠ TIDAK AMAN` dengan badge merah
- Tombol "Buat CAPA untuk item ini" dengan link ke `/capa/create?source_module=inspection&source_reference_id={inspection.id}&item_id={item.id}`

### CAPA Link Behavior

Klik "Buat CAPA" → redirect ke form CAPA dengan pre-fill:
- `source_module`: `inspection`
- `source_reference_id`: `{inspection.id}`
- `description`: Auto-filled dengan detail item unsafe (question, answer, remark)

### Inertia Props

```typescript
interface InspectionShowProps {
    inspection: Inspection & {
        template: InspectionTemplate;
        site: Site;
        area: Area | null;
        inspector: User;
        results: (InspectionResult & {
            item: InspectionItem;
        })[];
    };
    evidence: ManagedFile[];
    activities: ActivityLog[];
    workflowHistory: WorkflowHistory[];
    unsafeCount: number;
    capaLinks: {
        createFromInspection: boolean;
        createFromItem: boolean;
    };
    can: {
        execute: boolean;
        createCapa: boolean;
        export: boolean;
    };
}
```

---

## 8. Mobile Responsive

### Breakpoints

- `sm` (640px): Stack all columns, horizontal scroll for tables
- `md` (768px): Two-column layout for forms
- `lg` (1024px): Full desktop layout

### Mobile Notes

- **Template Index**: Table berubah jadi card list di mobile. Setiap card menampilkan kode, nama, kategori, dan status.
- **Template Form**: Item editor stack vertikal. Tombol "Tambah Item" di bawah.
- **Inspection Execute**: Setiap item tampil sebagai card dengan jawaban toggle di bawah pertanyaan. Swipe gesture untuk navigasi antar item (opsional).
- **Inspection Show**: Hasil item tampil sebagai list card. Unsafe items di-highlight dengan border merah.
- **Answer Toggle**: Tombol jawaban (Ya/Tidak, Aman/Tidak Aman) tampil full-width di mobile.
- **Sticky Action Bar**: Tetap di bawah layar saat scroll di mobile.

### Component List

| Component | File | Used In |
|---|---|---|
| `Badge` | `components/Badge.tsx` | All pages |
| `InspectionStatusBadge` | `components/Inspection/StatusBadge.tsx` | Index, Show |
| `ResultBadge` | `components/Inspection/ResultBadge.tsx` | Index, Show |
| `AnswerToggle` | `components/Inspection/AnswerToggle.tsx` | Execute Form |
| `UnsafeItemCard` | `components/Inspection/UnsafeItemCard.tsx` | Show |
| `ItemRepeater` | `components/Inspection/ItemRepeater.tsx` | Template Form |
| `EvidenceUploader` | `components/Inspection/EvidenceUploader.tsx` | Execute Form, Show |
| `CapaLinkButton` | `components/Inspection/CapaLinkButton.tsx` | Show |
| `InspectionForm` | `Pages/Modules/Inspection/Inspection/Form.tsx` | Create, Execute |
| `TemplateForm` | `Pages/Modules/Inspection/Template/Form.tsx` | Template Create/Edit |
