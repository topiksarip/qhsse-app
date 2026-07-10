# UI Pages — Reporting & Export

Spesifikasi wireframe halaman UI untuk modul Reporting & Export.

Semua label menggunakan Bahasa Indonesia. Tech stack: Laravel 12 + Inertia React + TypeScript + Tailwind CSS.

---

## Daftar Isi

1. [Navigasi](#1-navigasi)
2. [Color Coding](#2-color-coding)
3. [Halaman Template Index — Daftar Template](#3-halaman-template-index--daftar-template)
4. [Halaman Configure — Konfigurasi Laporan](#4-halaman-configure--konfigurasi-laporan)
5. [Halaman Saved Reports — Daftar Laporan Tersimpan](#5-halaman-saved-reports--daftar-laporan-tersimpan)
6. [Halaman Template Form — Buat/Edit Custom Template](#6-halaman-template-form--buatedit-custom-template)
7. [Mobile Responsive](#7-mobile-responsive)
8. [Komponen Reusable](#8-komponen-reusable)

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
            // ... modul lainnya ...
            { label: 'Reporting', routeName: 'reporting.templates.index', active: 'reporting.*', permission: 'reporting.templates.view' },
            { label: 'Laporan Tersimpan', routeName: 'reporting.reports.index', active: 'reporting.reports.*', permission: 'reporting.reports.view' },
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
│                        │ Tindakan (CAPA)      │                      │
│                        │ ...                  │                      │
│                        │ Reporting            │                      │
│                        │ Laporan Tersimpan    │                      │
│                        └──────────────────────┘                      │
└──────────────────────────────────────────────────────────────────────┘
```

### Permission Filtering

- Menu "Reporting" hanya tampil jika user memiliki permission `reporting.templates.view`.
- Menu "Laporan Tersimpan" hanya tampil jika user memiliki permission `reporting.reports.view`.

---

## 2. Color Coding

### Status Badge (Saved Reports)

| Status | Tailwind Class | Preview |
|---|---|---|
| Pending | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | `⚪ Menunggu` |
| Processing | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 Sedang Diproses` |
| Completed | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Selesai` |
| Failed | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Gagal` |

### Report Type Badge

| Type | Tailwind Class | Preview |
|---|---|---|
| incident_summary | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🟠 Ringkasan Insiden` |
| capa_summary | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | `🔵 Ringkasan CAPA` |
| inspection_summary | `bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200` | `🟣 Ringkasan Inspection` |
| audit_summary | `bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200` | `🟢 Ringkasan Audit` |
| training_compliance | `bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200` | `🟣 Kepatuhan Training` |
| monthly_qhsse | `bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200` | `🟠 Laporan Bulanan` |
| annual_qhsse | `bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200` | `🟣 Laporan Tahunan` |
| custom | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | `⚪ Custom` |

### Format Badge

| Format | Tailwind Class | Preview |
|---|---|---|
| CSV | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `📄 CSV` |
| PDF | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `📕 PDF` |
| Excel | `bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200` | `📊 Excel` |

### Template Active Badge

| Status | Tailwind Class | Preview |
|---|---|---|
| Active | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `✅ Aktif` |
| Inactive | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | `⏸ Nonaktif` |

### Pemetaan Helper

```typescript
// utils/reportBadgeColors.ts

const statusColors: Record<string, BadgeColor> = {
    pending:    'gray',
    processing: 'yellow',
    completed:  'green',
    failed:     'red',
};

const statusLabels: Record<string, string> = {
    pending:    'Menunggu',
    processing: 'Sedang Diproses',
    completed:  'Selesai',
    failed:     'Gagal',
};

const typeColors: Record<string, BadgeColor> = {
    incident_summary:    'red',
    capa_summary:        'blue',
    inspection_summary:  'indigo',
    audit_summary:       'teal',
    training_compliance: 'purple',
    monthly_qhsse:       'orange',
    annual_qhsse:        'pink',
    custom:              'gray',
};

const typeLabels: Record<string, string> = {
    incident_summary:    'Ringkasan Insiden',
    capa_summary:        'Ringkasan CAPA',
    inspection_summary:  'Ringkasan Inspection',
    audit_summary:       'Ringkasan Audit',
    training_compliance: 'Kepatuhan Training',
    monthly_qhsse:       'Laporan Bulanan QHSSE',
    annual_qhsse:        'Laporan Tahunan QHSSE',
    custom:              'Laporan Custom',
};

const formatColors: Record<string, BadgeColor> = {
    csv:   'green',
    pdf:   'red',
    excel: 'emerald',
};

const formatLabels: Record<string, string> = {
    csv:   'CSV',
    pdf:   'PDF',
    excel: 'Excel',
};
```

---

## 3. Halaman Template Index — Daftar Template

### Route: `GET /reports/templates` (`reporting.templates.index`)

### Permission: `reporting.templates.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                       │
│  Template Laporan                                                            │
│  Pilih template laporan untuk di-generate                                    │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari nama template...                              ]               │  │
│  │                                                                        │  │
│  │ Tipe: [Semua ▾]  Status: [Semua ▾]         [Reset]                   │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Toolbar ─────────────────────────────────────────────────────────────┐  │
│  │ Menampilkan 1–8 dari 8 template              [+ Buat Template Custom] │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Template Cards (Grid) ────────────────────────────────────────────────┐  │
│  │                                                                        │  │
│  │  ┌──────────────────────────────┐  ┌──────────────────────────────┐    │  │
│  │  │ 🟠 Ringkasan Insiden          │  │ 🔵 Ringkasan CAPA             │    │  │
│  │  │ incident_summary              │  │ capa_summary                  │    │  │
│  │  │ ✅ Aktif                       │  │ ✅ Aktif                       │    │  │
│  │  │ Laporan ringkasan insiden     │  │ Laporan status CAPA: open,   │    │  │
│  │  │ per periode, site, dept.      │  │ overdue, closure rate.       │    │  │
│  │  │                                │  │                                │    │  │
│  │  │ [⚙ Konfigurasi & Generate]   │  │ [⚙ Konfigurasi & Generate]   │    │  │
│  │  └──────────────────────────────┘  └──────────────────────────────┘    │  │
│  │                                                                        │  │
│  │  ┌──────────────────────────────┐  ┌──────────────────────────────┐    │  │
│  │  │ 🟣 Ringkasan Inspection        │  │ 🟢 Ringkasan Audit            │    │  │
│  │  │ inspection_summary            │  │ audit_summary                 │    │  │
│  │  │ ✅ Aktif                       │  │ ✅ Aktif                       │    │  │
│  │  │ Laporan hasil inspection dan  │  │ Laporan audit findings dan   │    │  │
│  │  │ compliance rate.              │  │ status.                       │    │  │
│  │  │                                │  │                                │    │  │
│  │  │ [⚙ Konfigurasi & Generate]   │  │ [⚙ Konfigurasi & Generate]   │    │  │
│  │  └──────────────────────────────┘  └──────────────────────────────┘    │  │
│  │                                                                        │  │
│  │  ┌──────────────────────────────┐  ┌──────────────────────────────┐    │  │
│  │  │ 🟣 Kepatuhan Training         │  │ 🟠 Laporan Bulanan QHSSE     │    │  │
│  │  │ training_compliance           │  │ monthly_qhsse                 │    │  │
│  │  │ ✅ Aktif                       │  │ ✅ Aktif                       │    │  │
│  │  │ Status kelengkapan training   │  │ Laporan komprehensif bulanan:│    │  │
│  │  │ per karyawan/departemen.      │  │ insiden + CAPA + inspection.  │    │  │
│  │  │                                │  │                                │    │  │
│  │  │ [⚙ Konfigurasi & Generate]   │  │ [⚙ Konfigurasi & Generate]   │    │  │
│  │  └──────────────────────────────┘  └──────────────────────────────┘    │  │
│  │                                                                        │  │
│  │  ┌──────────────────────────────┐  ┌──────────────────────────────┐    │  │
│  │  │ 🟣 Laporan Tahunan QHSSE      │  │ ⚪ Laporan Custom              │    │  │
│  │  │ annual_qhsse                  │  │ custom (dibuat oleh Manager)  │    │  │
│  │  │ ✅ Aktif                       │  │ ✅ Aktif                       │    │  │
│  │  │ Laporan komprehensif tahunan  │  │ Template custom yang dibuat   │    │  │
│  │  │ dengan tren 12 bulan.         │  │ oleh QHSSE Manager/Admin.     │    │  │
│  │  │                                │  │                                │    │  │
│  │  │ [⚙ Konfigurasi & Generate]   │  │ [⚙ Konfigurasi] [✏ Edit]     │    │  │
│  │  └──────────────────────────────┘  └──────────────────────────────┘    │  │
│  │                                                                        │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Wireframe — Empty State

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                                                                              │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │                                                                      │   │
│  │                          📊                                          │   │
│  │                                                                      │   │
│  │                   Belum ada template laporan                          │   │
│  │                                                                      │   │
│  │           Belum ada template yang tersedia.                          │   │
│  │           Hubungi administrator untuk konfigurasi.                   │   │
│  │                                                                      │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Element

#### Header

| Element | Type | Detail |
|---|---|---|
| Title | `<h1>` | "Template Laporan" |
| Subtitle | `<p>` | "Pilih template laporan untuk di-generate" |
| Button "Buat Template Custom" | `<Link>` | Route: `reporting.templates.create`, permission: `reporting.templates.create` |
| Button Style | Tailwind | `bg-blue-600 text-white hover:bg-blue-700` |

#### Filter Bar

| Filter | Label | Options | Param |
|---|---|---|---|
| Search | "Cari nama template..." | Text input | `?search=keyword` |
| Tipe | "Tipe" | Semua + 8 tipe laporan | `?type=` |
| Status | "Status" | Semua, Aktif, Nonaktif | `?is_active=` |

#### Template Card

Setiap template ditampilkan sebagai card dengan informasi:

| Element | Detail |
|---|---|
| Icon + Type Badge | Warna sesuai [Color Coding](#2-color-coding) |
| Template Name | Bold, `text-lg` |
| Type Code | `text-xs text-gray-500`, monospace |
| Active Badge | ✅ Aktif / ⏸ Nonaktif |
| Description | `text-sm text-gray-600`, max 3 lines |
| Button "Konfigurasi & Generate" | Link ke `reporting.reports.create?template_id={id}`, permission: `reporting.reports.generate` |
| Button "Edit" (custom only) | Link ke `reporting.templates.edit`, permission: `reporting.templates.update` |

#### Inactive Template

Template dengan `is_active = false` ditampilkan dengan opacity 50% dan badge "Nonaktif". Tombol "Konfigurasi & Generate" tidak tampil.

### Inertia Props

```typescript
interface TemplateIndexProps {
    templates: {
        data: ReportTemplateListItem[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        type?: string;
        is_active?: boolean;
    };
    can: {
        create: boolean;
    };
}

interface ReportTemplateListItem {
    id: number;
    name: string;
    type: string;
    type_label: string;
    description: string | null;
    is_active: boolean;
    is_predefined: boolean;
    created_by: { id: number; name: string };
    created_at: string;
}
```

---

## 4. Halaman Configure — Konfigurasi Laporan

### Route: `GET /reports/create` (`reporting.reports.create`)

### Permission: `reporting.reports.generate`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Konfigurasi Laporan                                                             │
│  Generate laporan dari template yang dipilih                                      │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Template Terpilih ──────────────────────────────────────────────┐  │
│  │  TEMPLATE TERPILIH                                                         │  │
│  │  ─────────────────────────────────────────────────────────────────────    │  │
│  │                                                                            │  │
│  │  🟠 Laporan Bulanan QHSSE                                                 │  │
│  │  Tipe: monthly_qhsse                                                       │  │
│  │  Laporan komprehensif bulanan: insiden, CAPA, inspection, audit, training. │  │
│  │                                                                            │  │
│  │  [← Ganti Template]                                                        │  │
│  └────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Parameter Laporan ──────────────────────────────────────────────┐  │
│  │  PARAMETER LAPORAN                                                         │  │
│  │  ─────────────────────────────────────────────────────────────────────    │  │
│  │                                                                            │  │
│  │  Nama Laporan *   [Laporan Bulanan QHSSE - Januari 2026       ]           │  │
│  │                                                                            │  │
│  │  ┌─ Periode ───────────────────────────────────────────────────────────┐  │  │
│  │  │                                                                      │  │  │
│  │  │  Rentang Tanggal *                                                  │  │  │
│  │  │                                                                      │  │  │
│  │  │  ○ Bulan Ini    ○ Bulan Lalu    ● Custom                            │  │  │
│  │  │                                                                      │  │  │
│  │  │  Dari *  [01/01/2026] [📅]     Sampai *  [31/01/2026] [📅]         │  │  │
│  │  │                                                                      │  │  │
│  │  │  Maksimal rentang: 2 tahun (730 hari)                                │  │  │
│  │  └──────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                            │  │
│  │  ┌─ Filter ───────────────────────────────────────────────────────────┐  │  │
│  │  │                                                                      │  │  │
│  │  │  Site         [— Semua Site —    ▾]                                 │  │  │
│  │  │               (Kosongkan untuk semua site dalam scope Anda)        │  │  │
│  │  │                                                                      │  │  │
│  │  │  Departemen  [— Semua Departemen —    ▾]                            │  │  │
│  │  │               (Filtered by site jika site dipilih)                 │  │  │
│  │  │                                                                      │  │  │
│  │  └──────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                            │  │
│  │  ┌─ Format Output ────────────────────────────────────────────────────┐  │  │
│  │  │                                                                      │  │  │
│  │  │  Format *                                                            │  │  │
│  │  │  ○ 📄 CSV    ● 📕 PDF    ○ 📊 Excel                                │  │  │
│  │  │                                                                      │  │  │
│  │  │  ☑ Sertakan grafik (hanya PDF/Excel)                                 │  │  │
│  │  │                                                                      │  │  │
│  │  └──────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                            │  │
│  └────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Preview Sections ───────────────────────────────────────────────┐  │
│  │  SEKSI LAPORAN                                                            │  │
│  │  ─────────────────────────────────────────────────────────────────────    │  │
│  │                                                                            │  │
│  │  Berikut adalah seksi yang akan disertakan dalam laporan:                 │  │
│  │                                                                            │  │
│  │  ┌────────────────────────────────────────────────────────────────────┐   │  │
│  │  │ ☑ Ringkasan Eksekutif                                               │   │  │
│  │  │ ☑ Statistik Insiden (data_source: incident)                        │   │  │
│  │  │ ☑ Status CAPA (data_source: capa)                                   │   │  │
│  │  │ ☑ Hasil Inspection (data_source: inspection)                        │   │  │
│  │  │ ☑ Audit Findings (data_source: audit)                              │   │  │
│  │  │ ☑ Training Compliance (data_source: training)                      │   │  │
│  │  │ ☑ Permit to Work (data_source: permit)                             │   │  │
│  │  │ ☑ Environmental (data_source: environment)                          │   │  │
│  │  │ ☑ Security (data_source: security)                                 │   │  │
│  │  └────────────────────────────────────────────────────────────────────┘   │  │
│  │                                                                            │  │
│  │  Catatan: Seksi tidak dapat diubah untuk template predefined.             │  │
│  │  Custom template dapat dikonfigurasi di halaman edit template.            │  │
│  └────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Action Bar (sticky bottom) ──────────────────────────────────────────────┐  │
│  │                                                                            │  │
│  │  [← Batal]                                          [Generate Laporan]   │  │
│  │                                                      (primary)            │  │
│  └────────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Wireframe — Quick Date Range Selector (Detail)

```
┌─ Rentang Tanggal ───────────────────────────────────┐
│                                                      │
│  Pilih cepat:                                        │
│  [Hari Ini]  [Bulan Ini]  [Bulan Lalu]  [Tahun Ini] │
│  [Tahun Lalu]  [Custom]                              │
│                                                      │
│  Dari *  [01/01/2026] [📅]                           │
│  Sampai * [31/01/2026] [📅]                          │
│                                                      │
└──────────────────────────────────────────────────────┘
```

### Spesifikasi Element

#### Nama Laporan

| Element | Type | Detail |
|---|---|---|
| Label | "Nama Laporan" | Required |
| Input | `<input type="text">` | Max 255 chars |
| Default | Auto-fill: "{template.name} - {month_name} {year}" | Bisa diubah user |

#### Date Range

| Element | Type | Detail |
|---|---|---|
| Quick Select | Radio buttons | Bulan Ini, Bulan Lalu, Tahun Ini, Tahun Lalu, Custom |
| Dari | `<input type="date">` | Required, max = today |
| Sampai | `<input type="date">` | Required, >= Dari, max rentang 730 hari |

#### Site Filter

| Element | Type | Detail |
|---|---|---|
| Label | "Site" | Opsional |
| Select | Dropdown | "Semua Site" + daftar sites dalam scope user |
| Scope | Filtered by user's data scope | QHSSE Officer only sees their site(s) |

#### Departemen Filter

| Element | Type | Detail |
|---|---|---|
| Label | "Departemen" | Opsional |
| Select | Dropdown | "Semua Departemen" + daftar departments, filtered by site if site dipilih |

#### Format Output

| Element | Type | Detail |
|---|---|---|
| Label | "Format" | Required |
| Radio | 3 options | CSV, PDF, Excel |
| Include Charts | Checkbox | Hanya enabled untuk PDF/Excel, disabled untuk CSV |

#### Sections Preview

| Element | Type | Detail |
|---|---|---|
| Display | Checkbox list (read-only untuk predefined) | Menampilkan sections dari template config |
| Note | "Seksi tidak dapat diubah untuk template predefined" | Hanya untuk predefined templates |

#### Action Bar

| Element | Detail |
|---|---|
| Button "Batal" | Redirect ke template index |
| Button "Generate Laporan" | Submit POST ke `reporting.reports.store`, permission: `reporting.reports.generate` |
| Loading State | Button disabled + spinner saat submit, text: "Memulai..." |

### Inertia Props

```typescript
interface ConfigureReportProps {
    template: ReportTemplate & {
        created_by: { id: number; name: string };
        config: {
            sections: {
                key: string;
                label: string;
                enabled: boolean;
                data_source?: string;
            }[];
            default_parameters: {
                date_range?: string;
                site_id?: number | null;
                department_id?: number | null;
                format?: string;
                include_charts?: boolean;
            };
        };
    };
    sites: Site[];
    departments: Department[];
    can: {
        generate: boolean;
    };
}
```

---

## 5. Halaman Saved Reports — Daftar Laporan Tersimpan

### Route: `GET /reports/saved` (`reporting.reports.index`)

### Permission: `reporting.reports.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                       │
│  Laporan Tersimpan                                                           │
│  Daftar laporan yang sudah di-generate                                      │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─ KPI Cards ─────────────────────────────────────────────────────────────┐  │
│  │                                                                        │  │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐ │  │
│  │  │ 📊 Total  │  │ ⏳ Pending│  │ 🔄 Proses│  │ ✅ Selesai│  │ ❌ Gagal  │ │  │
│  │  │   42      │  │    3      │  │    1      │  │   35      │  │    3      │ │  │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────┘  └──────────┘ │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari nama laporan...                              ]                │  │
│  │                                                                        │  │
│  │ Tipe: [Semua ▾]  Status: [Semua ▾]  Format: [Semua ▾]                  │  │
│  │ Dari: [__/__/____]  Sampai: [__/__/____]  [Reset]                      │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Toolbar ─────────────────────────────────────────────────────────────┐  │
│  │ Menampilkan 1–15 dari 42 laporan                                        │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Table ─────────────────────────────────────────────────────────────────┐ │
│  │ Nama           Tipe              Format  Status     Generated    Aksi    │ │
│  ├──────────────────────────────────────────────────────────────────────────┤ │
│  │ Laporan Bln-  🟠Laporan Bulanan  📕PDF  🟢Selesai  10/07/26    [⬇] [🔄] │ │
│  │ QHSSE Jan 2026                                                          │ │
│  ├──────────────────────────────────────────────────────────────────────────┤ │
│  │ CAPA Summary  🔵Ringkasan CAPA    📊Exl  🟡Sedang    10/07/26    [⏳]   │ │
│  │ Q1 2026                        Diproses                                 │ │
│  ├──────────────────────────────────────────────────────────────────────────┤ │
│  │ Audit Q2      🟢Ringkasan Audit   📕PDF  ⚪Menunggu  10/07/26    [⏳]   │ │
│  ├──────────────────────────────────────────────────────────────────────────┤ │
│  │ Training Dec  🟣Kepatuhan Trn    📊Exl  🔴Gagal     09/07/26    [🔄]   │ │
│  │ 2025                                                                   │ │
│  ├──────────────────────────────────────────────────────────────────────────┤ │
│  │ Incident Nov  🟠Ringkasan Insiden 📄CSV  🟢Selesai  08/07/26    [⬇] [🔄]│ │
│  └──────────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  ┌─ Table (cont.) ────────────────────────────────────────────────────────┐ │
│  │ ... Generated By  Generated At                                         │ │
│  │ ... Budi S.      10/07/26 14:30                                        │ │
│  │ ... Sari W.      10/07/26 09:15                                        │ │
│  │ ... Andi P.      10/07/26 08:00                                        │ │
│  │ ... Joni K.      09/07/26 16:45                                        │ │
│  │ ... Doni A.      08/07/26 11:20                                        │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  ┌─ Pagination ───────────────────────────────────────────────────────────┐  │
│  │                              ‹ Sebelumnya   1  2  3   Berikutnya ›     │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Wireframe — Pending/Processing Row (Detail)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  ┌─ Processing Row (bg-yellow-50 dark:bg-yellow-900/20) ──────────────────┐ │
│  │ CAPA Summary   🔵Ringkasan CAPA   📊Excel  🟡Sedang Diproses         │ │
│  │ Q1 2026                                                              │ │
│  │ Started: 10/07/26 09:15   By: Sari W.    [⏳ Sedang Diproses...]     │ │
│  └────────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  ┌─ Pending Row (bg-gray-50 dark:bg-gray-800/50) ────────────────────────┐ │
│  │ Audit Q2       🟢Ringkasan Audit    📕PDF   ⚪Menunggu               │ │
│  │ Queued: 10/07/26 08:00   By: Andi P.     [⏳ Menunggu Antrian...]     │ │
│  └────────────────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Wireframe — Failed Row (Detail)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  ┌─ Failed Row (bg-red-50 dark:bg-red-900/20) ────────────────────────────┐│
│  │ Training Dec   🟣Kepatuhan Training  📊Excel  🔴Gagal                 ││
│  │ 2025                                                                    ││
│  │ Error: Connection timeout saat meng-query data training.               ││
│  │ Generated: 09/07/26 16:45   By: Joni K.    [🔄 Generate Ulang]         ││
│  └────────────────────────────────────────────────────────────────────────┘│
└──────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Element

#### KPI Cards

| Card | Query | Color |
|---|---|---|
| Total | Count all in scope | `bg-blue-50 text-blue-800` |
| Pending | Count status = pending | `bg-gray-50 text-gray-800` |
| Processing | Count status = processing | `bg-yellow-50 text-yellow-800` |
| Completed | Count status = completed | `bg-green-50 text-green-800` |
| Failed | Count status = failed | `bg-red-50 text-red-800` |

#### Filter Bar

| Filter | Label | Options | Param |
|---|---|---|---|
| Search | "Cari nama laporan..." | Text input | `?search=keyword` |
| Tipe | "Tipe" | Semua + 8 tipe laporan | `?type=` |
| Status | "Status" | Semua, Menunggu, Sedang Diproses, Selesai, Gagal | `?status=` |
| Format | "Format" | Semua, CSV, PDF, Excel | `?format=` |
| Date Range | "Dari" / "Sampai" | Date picker | `?from=` `?to=` |

#### Table Columns

| # | Column | Key | Width | Align | Badge? | Detail |
|---|---|---|---|---|---|---|
| 1 | Nama | `name` | flex | left | No | Truncate dengan `max-w-xs truncate` |
| 2 | Tipe | `report_template.type` | 140px | center | Yes | Lihat [Color Coding](#2-color-coding) |
| 3 | Format | `format` | 80px | center | Yes | Lihat [Color Coding](#2-color-coding) |
| 4 | Status | `status` | 120px | center | Yes | Lihat [Color Coding](#2-color-coding) |
| 5 | Generated By | `generated_by.name` | 120px | left | No | Nama user |
| 6 | Generated At | `generated_at` | 130px | center | No | Formatted `d/m/Y H:i` |
| 7 | Aksi | — | 100px | center | No | Lihat di bawah |

#### Aksi Column (per row)

| Action | Icon | Permission | Condition |
|---|---|---|---|
| Download | ⬇ | `reporting.reports.download` | Status = completed |
| Re-generate | 🔄 | `reporting.reports.generate` | Status = completed atau failed |
| View Detail | 👁 | `reporting.reports.view` | Selalu tampil |

#### Status Row Highlight

```tsx
<tr
    key={report.id}
    className={
        report.status === 'processing'
            ? 'bg-yellow-50 dark:bg-yellow-900/20'
            : report.status === 'failed'
            ? 'bg-red-50 dark:bg-red-900/20'
            : report.status === 'pending'
            ? 'bg-gray-50 dark:bg-gray-800/50'
            : 'hover:bg-gray-50 dark:hover:bg-gray-800'
    }
>
    {/* ... cells ... */}
</tr>
```

#### Auto-Refresh

- Halaman Saved Reports melakukan **auto-refresh** setiap 10 detik jika ada report dengan status `pending` atau `processing`.
- Menggunakan Inertia's `reload()` atau polling mechanism.

```typescript
useEffect(() => {
    const hasActiveReports = reports.data.some(
        (r) => r.status === 'pending' || r.status === 'processing'
    );

    if (!hasActiveReports) return;

    const interval = setInterval(() => {
        router.reload({ only: ['reports'] });
    }, 10000);

    return () => clearInterval(interval);
}, [reports.data]);
```

### Inertia Props

```typescript
interface SavedReportsIndexProps {
    reports: {
        data: SavedReportListItem[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        type?: string;
        status?: string;
        format?: string;
        from?: string;
        to?: string;
    };
    stats: {
        total: number;
        pending: number;
        processing: number;
        completed: number;
        failed: number;
    };
    can: {
        generate: boolean;
        download: boolean;
    };
}

interface SavedReportListItem {
    id: number;
    name: string;
    format: string;
    status: string;
    status_label: string;
    generated_at: string;
    parameters: {
        date_from: string;
        date_to: string;
        site_id: number | null;
        department_id: number | null;
        error?: string | null;
    };
    report_template: {
        id: number;
        name: string;
        type: string;
        type_label: string;
    };
    generated_by: {
        id: number;
        name: string;
    };
}
```

---

## 6. Halaman Template Form — Buat/Edit Custom Template

### Route

- Create: `GET /reports/templates/create` (`reporting.templates.create`)
- Edit: `GET /reports/templates/{template}/edit` (`reporting.templates.edit`)

### Permission

- Create: `reporting.templates.create`
- Edit: `reporting.templates.update`

### Wireframe — Desktop (Create)

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Buat Template Custom                                                            │
│  Buat template laporan custom dengan konfigurasi sendiri                        │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Template ────────────────────────────────────────────┐   │
│  │  INFORMASI TEMPLATE                                                      │   │
│  │  ─────────────────────────────────────────────────────────────────────    │   │
│  │                                                                          │   │
│  │  Nama *           [Nama template laporan...                  ]           │   │
│  │                                                                          │   │
│  │  Tipe             [Custom (read-only)]                                 │   │
│  │                   Tipe custom tidak dapat diubah setelah disimpan.        │   │
│  │                                                                          │   │
│  │  Deskripsi        ┌──────────────────────────────────────────────┐     │   │
│  │                   │ Jelaskan tujuan template ini...              │     │   │
│  │                   │                                              │     │   │
│  │                   └──────────────────────────────────────────────┘     │   │
│  │                                                                          │   │
│  │  Status           [☑ Aktif]                                             │   │
│  │                                                                          │   │
│  └──────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Section: Konfigurasi Sections ─────────────────────────────────────────┐   │
│  │  KONFIGURASI SEKSI                                                       │   │
│  │  ─────────────────────────────────────────────────────────────────────    │   │
│  │                                                                          │   │
│  │  Pilih data source dan seksi yang akan disertakan:                      │   │
│  │                                                                          │   │
│  │  ┌────────────────────────────────────────────────────────────────────┐ │   │
│  │  │ ☑ Ringkasan Eksekutif (summary)                                    │ │   │
│  │  │                                                                      │ │   │
│  │  │ ☑ Statistik Insiden (data_source: incident)                        │ │   │
│  │  │   Group by: [☑ Severity] [☑ Site] [☑ Month] [☐ Type]              │ │   │
│  │  │                                                                      │ │   │
│  │  │ ☑ Status CAPA (data_source: capa)                                  │ │   │
│  │  │   Group by: [☑ Status] [☑ Priority] [☐ Source Module]             │ │   │
│  │  │                                                                      │ │   │
│  │  │ ☐ Hasil Inspection (data_source: inspection)                      │ │   │
│  │  │                                                                      │ │   │
│  │  │ ☑ Audit Findings (data_source: audit)                              │ │   │
│  │  │   Group by: [☑ Severity] [☑ Status]                               │ │   │
│  │  │                                                                      │ │   │
│  │  │ ☐ Training Compliance (data_source: training)                     │ │   │
│  │  │                                                                      │ │   │
│  │  │ [+ Tambah Seksi Custom]                                             │ │   │
│  │  └────────────────────────────────────────────────────────────────────┘ │   │
│  │                                                                          │   │
│  └──────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Section: Default Parameters ───────────────────────────────────────────┐   │
│  │  PARAMETER DEFAULT                                                       │   │
│  │  ─────────────────────────────────────────────────────────────────────    │   │
│  │                                                                          │   │
│  │  Format Default    [— Pilih —    ▾]                                     │   │
│  │                     ○ CSV  ○ PDF  ● Excel                              │   │
│  │                                                                          │   │
│  │  Include Charts     [☑ Ya]                                              │   │
│  │                                                                          │   │
│  └──────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Action Bar (sticky bottom) ────────────────────────────────────────────┐  │
│  │                                                                          │   │
│  │  [← Batal]                                          [Simpan Template]   │   │
│  │                                                      (primary)          │   │
│  └──────────────────────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Wireframe — Edit Predefined Template (Restricted)

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Edit Template: Ringkasan Insiden                                                │
│  Pre-defined template — edit terbatas                                            │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Template ────────────────────────────────────────────┐   │
│  │                                                                          │   │
│  │  Nama             [Ringkasan Insiden (read-only)            ] 🔒        │   │
│  │                                                                          │   │
│  │  Tipe             [incident_summary (read-only)             ] 🔒        │   │
│  │                                                                          │   │
│  │  Deskripsi        ┌──────────────────────────────────────────────┐      │   │
│  │                   │ Laporan ringkasan insiden per periode.       │      │   │
│  │                   └──────────────────────────────────────────────┘      │   │
│  │                   (Dapat diubah)                                         │   │
│  │                                                                          │   │
│  │  Status           [☑ Aktif]  (Dapat diubah)                             │   │
│  │                                                                          │   │
│  └──────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Section: Konfigurasi Sections (read-only) ─────────────────────────────┐   │
│  │                                                                          │   │
│  │  ┌────────────────────────────────────────────────────────────────────┐ │   │
│  │  │ ☑ Ringkasan (read-only)                                           │ │   │
│  │  │ ☑ By Severity (read-only)                                          │ │   │
│  │  │ ☑ By Site (read-only)                                               │ │   │
│  │  │ ☑ Trend Bulanan (read-only)                                         │ │   │
│  │  │ ☑ Distribusi Status (read-only)                                     │ │   │
│  │  └────────────────────────────────────────────────────────────────────┘ │   │
│  │                                                                          │   │
│  │  ⚠ Pre-defined template: konfigurasi sections tidak dapat diubah.      │   │
│  │                                                                          │   │
│  └──────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Action Bar ─────────────────────────────────────────────────────────────┐  │
│  │  [← Batal]                                          [Simpan Perubahan] │   │
│  └──────────────────────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Inertia Props

```typescript
interface TemplateFormProps {
    template: ReportTemplate | null; // null on create
    can: {
        update: boolean;
    };
}
```

---

## 7. Mobile Responsive

### Breakpoints

- **Desktop** (≥1024px): Grid 2 kolom untuk template cards, table full width.
- **Tablet** (768px–1023px): Grid 1 kolom untuk template cards, table scrollable.
- **Mobile** (<768px): Stack vertical, card layout untuk saved reports.

### Mobile Wireframe — Saved Reports List

```
┌──────────────────────────────────────┐
│ ☰  Laporan Tersimpan          [User] │
├──────────────────────────────────────┤
│                                      │
│ ┌─ KPI (horizontal scroll) ────────┐ │
│ │ [📊42] [⏳3] [🔄1] [✅35] [❌3]  │ │
│ └──────────────────────────────────┘ │
│                                      │
│ [🔍 Cari laporan...]                │
│ [Tipe ▾] [Status ▾]                 │
│                                      │
│ ┌──────────────────────────────────┐ │
│ │ Laporan Bln-QHSSE Jan 2026       │ │
│ │ 🟠Laporan Bulanan  📕PDF         │ │
│ │ 🟢Selesai                        │ │
│ │ 10/07/26 14:30  By: Budi S.      │ │
│ │ [⬇ Download]  [🔄 Generate Ulang]│ │
│ └──────────────────────────────────┘ │
│                                      │
│ ┌──────────────────────────────────┐ │
│ │ CAPA Summary Q1 2026             │ │
│ │ 🔵Ringkasan CAPA  📊Excel        │ │
│ │ 🟡Sedang Diproses                │ │
│ │ 10/07/26 09:15  By: Sari W.      │ │
│ │ [⏳ Sedang Diproses...]          │ │
│ └──────────────────────────────────┘ │
│                                      │
│ ┌──────────────────────────────────┐ │
│ │ Audit Q2                         │ │
│ │ 🟢Ringkasan Audit  📕PDF         │ │
│ │ ⚪Menunggu                        │ │
│ │ 10/07/26 08:00  By: Andi P.      │ │
│ │ [⏳ Menunggu Antrian...]         │ │
│ └──────────────────────────────────┘ │
│                                      │
│         ‹ 1  2  3 ›                  │
└──────────────────────────────────────┘
```

### Mobile Notes

- KPI cards menjadi horizontal scroll.
- Table berubah menjadi card list.
- Filter bar collapse menjadi dropdown.
- Template index grid 1 kolom.
- Configure page: semua sections stack vertical.

---

## 8. Komponen Reusable

### 8.1 StatusBadge

```tsx
interface StatusBadgeProps {
    status: 'pending' | 'processing' | 'completed' | 'failed';
}

const StatusBadge: React.FC<StatusBadgeProps> = ({ status }) => {
    const config = {
        pending:    { label: 'Menunggu',        classes: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' },
        processing: { label: 'Sedang Diproses',  classes: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' },
        completed:  { label: 'Selesai',          classes: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' },
        failed:     { label: 'Gagal',            classes: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
    };
    const { label, classes } = config[status];
    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${classes}`}>
            {label}
        </span>
    );
};
```

### 8.2 TypeBadge

```tsx
interface TypeBadgeProps {
    type: string;
    label?: string;
}

const TypeBadge: React.FC<TypeBadgeProps> = ({ type, label }) => {
    const config: Record<string, { classes: string }> = {
        incident_summary:    { classes: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
        capa_summary:        { classes: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' },
        inspection_summary:  { classes: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200' },
        audit_summary:       { classes: 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200' },
        training_compliance: { classes: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' },
        monthly_qhsse:       { classes: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' },
        annual_qhsse:        { classes: 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200' },
        custom:              { classes: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' },
    };
    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${config[type]?.classes ?? config.custom.classes}`}>
            {label ?? type}
        </span>
    );
};
```

### 8.3 FormatBadge

```tsx
interface FormatBadgeProps {
    format: 'csv' | 'pdf' | 'excel';
}

const FormatBadge: React.FC<FormatBadgeProps> = ({ format }) => {
    const config = {
        csv:   { label: '📄 CSV',   classes: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' },
        pdf:   { label: '📕 PDF',   classes: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
        excel: { label: '📊 Excel', classes: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' },
    };
    const { label, classes } = config[format];
    return (
        <span className={`inline-flex items-center rounded px-2 py-0.5 text-xs font-medium ${classes}`}>
            {label}
        </span>
    );
};
```

### 8.4 DateRangeSelector

```tsx
interface DateRangeSelectorProps {
    dateFrom: string;
    dateTo: string;
    onChange: (from: string, to: string) => void;
}

const quickRanges = [
    { label: 'Hari Ini',    getRange: () => [today, today] },
    { label: 'Bulan Ini',   getRange: () => [firstOfMonth, lastOfMonth] },
    { label: 'Bulan Lalu',  getRange: () => [firstOfLastMonth, lastOfLastMonth] },
    { label: 'Tahun Ini',   getRange: () => [firstOfYear, lastOfYear] },
    { label: 'Tahun Lalu',  getRange: () => [firstOfLastYear, lastOfLastYear] },
    { label: 'Custom',      getRange: () => null },
];
```

### 8.5 TemplateCard

```tsx
interface TemplateCardProps {
    template: ReportTemplateListItem;
    canGenerate: boolean;
    canUpdate: boolean;
}

const TemplateCard: React.FC<TemplateCardProps> = ({ template, canGenerate, canUpdate }) => (
    <div className={`rounded-lg border p-4 ${template.is_active ? 'border-gray-200 dark:border-gray-700' : 'border-gray-200 opacity-50 dark:border-gray-700'}`}>
        <div className="flex items-center justify-between">
            <TypeBadge type={template.type} label={template.type_label} />
            <span className={`text-xs ${template.is_active ? 'text-green-600' : 'text-gray-400'}`}>
                {template.is_active ? '✅ Aktif' : '⏸ Nonaktif'}
            </span>
        </div>
        <h3 className="mt-2 text-lg font-semibold">{template.name}</h3>
        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400 line-clamp-3">{template.description}</p>
        <div className="mt-4 flex gap-2">
            {canGenerate && template.is_active && (
                <Link
                    href={route('reporting.reports.create', { template_id: template.id })}
                    className="rounded bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-700"
                >
                    ⚙ Konfigurasi & Generate
                </Link>
            )}
            {canUpdate && !template.is_predefined && (
                <Link
                    href={route('reporting.templates.edit', template.id)}
                    className="rounded border border-gray-300 px-3 py-1.5 text-sm font-medium hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-800"
                >
                    ✏ Edit
                </Link>
            )}
        </div>
    </div>
);
```
