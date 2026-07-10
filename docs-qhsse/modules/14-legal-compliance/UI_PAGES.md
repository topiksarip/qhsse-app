# UI Pages — Legal & Compliance Register

Spesifikasi wireframe halaman UI untuk modul Legal & Compliance Register.

Semua label menggunakan Bahasa Indonesia. Tech stack: Laravel 12 + Inertia React + TypeScript + Tailwind CSS.

---

## Daftar Isi

1. [Navigasi](#1-navigasi)
2. [Color Coding](#2-color-coding)
3. [Halaman Index — Daftar Register](#3-halaman-index--daftar-register)
4. [Halaman Form — Buat/Edit Register](#4-halaman-form--buatedit-register)
5. [Halaman Show — Detail Register (dengan Obligations Tab)](#5-halaman-show--detail-register)
6. [Obligation Form (Inline di Register Show)](#6-obligation-form--inline-di-register-show)
7. [Mobile Responsive](#7-mobile-responsive)

---

## 1. Navigasi

### Penempatan Menu

Tambahkan item menu `Legal & Compliance` pada group `Modul QHSSE` di array `menuGroups` pada `AuthenticatedLayout.tsx`.

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
            { label: 'Audit Management', routeName: 'audits.index', active: 'audits.*', permission: 'audit.management.view' },
            { label: 'Legal & Compliance', routeName: 'legal-register.index', active: 'legal-register.*', permission: 'legal.register.view' },  // ← NEW
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
│                        │ Legal & Compliance│                         │
│                        └──────────────────┘                         │
└──────────────────────────────────────────────────────────────────────┘
```

### Permission Filtering

Menu hanya tampil jika user memiliki permission `legal.register.view`. Filtering dilakukan via `auth.permissions` pada layout.

---

## 2. Color Coding

### Compliance Status Badge

| Status | Tailwind Class | Preview |
|---|---|---|
| Compliant | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Compliant` |
| Non-Compliant | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Non-Compliant` |
| In Progress | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 In Progress` |
| Not Applicable | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | `⚪ Not Applicable` |

### Category Badge

| Category | Tailwind Class | Preview |
|---|---|---|
| National | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | `🔵 National` |
| Regional | `bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200` | `🟣 Regional` |
| Industry | `bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200` | `🔵 Industry` |
| Internal | `bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200` | `🟢 Internal` |

### Obligation Status Badge

| Status | Tailwind Class | Preview |
|---|---|---|
| Pending | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 Pending` |
| Completed | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Completed` |
| Overdue | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Overdue` |
| Due Soon | `bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200` | `🟠 Due Soon` |

### Register Record Status Badge

| Status | Tailwind Class | Preview |
|---|---|---|
| Active | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Active` |
| Inactive | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | `⚪ Inactive` |

### Pemetaan Helper

```typescript
// utils/badgeColors.ts

const complianceStatusColors: Record<string, BadgeColor> = {
    compliant:       'green',
    non_compliant:    'red',
    in_progress:      'yellow',
    not_applicable:   'gray',
};

const categoryColors: Record<string, BadgeColor> = {
    national:  'blue',
    regional:  'purple',
    industry:  'indigo',
    internal:  'teal',
};

const obligationStatusColors: Record<string, BadgeColor> = {
    pending:    'yellow',
    completed:  'green',
    overdue:    'red',
    due_soon:   'orange',
};

const registerStatusColors: Record<string, BadgeColor> = {
    active:    'green',
    inactive:  'gray',
};
```

### Compliance Status Helper Functions

```typescript
// utils/complianceStatus.ts

export function getObligationStatus(obligation: LegalObligation): string {
    if (obligation.status === 'completed') return 'completed';
    if (obligation.next_due && new Date(obligation.next_due) < new Date()) return 'overdue';
    if (obligation.next_due && new Date(obligation.next_due) <= new Date(Date.now() + 7 * 24 * 60 * 60 * 1000)) return 'due_soon';
    return 'pending';
}

export function getDaysOverdue(obligation: LegalObligation): number | null {
    if (obligation.status !== 'pending' || !obligation.next_due) return null;
    const diff = Math.floor((Date.now() - new Date(obligation.next_due).getTime()) / (1000 * 60 * 60 * 24));
    return diff > 0 ? diff : null;
}
```

---

## 3. Halaman Index — Daftar Register

### Route: `GET /legal-register` (`legal-register.index`)

### Permission: `legal.register.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Legal & Compliance Register                              [+ Buat Register]   │
│  Kelola register peraturan dan regulasi kepatuhan                                │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ KPI Summary Bar ──────────────────────────────────────────────────────────┐   │
│  │ Total: 32   🟢 Compliant: 18   🔴 Non-Compliant: 3   🟡 In Progress: 8   │   │
│  │ ⚪ N/A: 3   🔴 Overdue Obligations: 5   🟠 Due Soon: 2                    │   │
│  └────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari nomor, judul, nama regulasi...          ]                          │  │
│  │                                                                              │  │
│  │ Status: [Semua ▾]  Kategori: [Semua ▾]  Site: [Semua ▾]                   │  │
│  │ Department: [Semua ▾]  Owner: [Semua ▾]  [Reset]                           │  │
│  └──────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Toolbar ─────────────────────────────────────────────────────────────────┐   │
│  │ Menampilkan 1–15 dari 32 register                     [⬇ Export CSV]      │   │
│  └────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Table ────────────────────────────────────────────────────────────────────┐  │
│  │ Nomor        Judul              Kategori   Status         Owner    Review │  │
│  ├────────────────────────────────────────────────────────────────────────────┤  │
│  │ LEG-0001    UU No.1/1970       🔵National 🟡In Progress  Budi S.  15/08  │  │
│  │ LEG-0002    Pergub JKT K3      🟣Regional 🔴Non-Complnt   Sari W.  —      │  │
│  │ LEG-0003    SNI ISO 45001      🔵Industry 🟢Compliant     Andi P.  01/09  │  │
│  │ LEG-0004    SOP Internal K3    🟢Internal ⚪N/A            Budi S.  —      │  │
│  │ LEG-0005    Permenaker 5/2018  🔵National 🟢Compliant     Dewi A.  20/10  │  │
│  │ ...                                                                         │  │
│  └────────────────────────────────────────────────────────────────────────────┘  │
│  ┌─ Table (cont.) ────────────────────────────────────────────────────────────┐  │
│  │ ... Review  Obligations  Aksi                                               │  │
│  │ ... 15/08   3 (1🔴, 1🟡)   [👁 Lihat]  [✏ Edit]                              │  │
│  │ ... —       2 (0🔴, 0🟡)   [👁 Lihat]  [✏ Edit]                              │  │
│  │ ... 01/09   5 (0🔴, 1🟡)   [👁 Lihat]  [✏ Edit]                              │  │
│  │ ... —       0              [👁 Lihat]  [✏ Edit]                              │  │
│  │ ... 20/10   1 (0🔴, 0🟡)   [👁 Lihat]  [✏ Edit]                              │  │
│  └────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Pagination ──────────────────────────────────────────────────────────────┐   │
│  │                        ‹ Sebelumnya   1  2  3   Berikutnya ›              │   │
│  └────────────────────────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Wireframe — Empty State

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│                                                                                  │
│  ┌──────────────────────────────────────────────────────────────────────────┐   │
│  │                              📋                                          │   │
│  │                                                                          │   │
│  │                     Belum ada register                                  │   │
│  │                                                                          │   │
│  │           Belum ada register peraturan yang dibuat. Klik tombol          │   │
│  │           di bawah untuk membuat register pertama Anda.                  │   │
│  │                                                                          │   │
│  │                      [+ Buat Register Pertama]                          │   │
│  │                                                                          │   │
│  └──────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Element

#### Header

| Element | Type | Detail |
|---|---|---|
| Title | `<h1>` | "Legal & Compliance Register" |
| Subtitle | `<p>` | "Kelola register peraturan dan regulasi kepatuhan" |
| Button "Buat Register" | `<Link>` | Route: `legal-register.create`, permission: `legal.register.create` |
| Button Style | Tailwind | `bg-blue-600 text-white hover:bg-blue-700` |

#### KPI Summary Bar

| KPI | Query | Color |
|---|---|---|
| Total | Count all registers in scope | Default text color |
| Compliant | Count where compliance_status = `compliant` | `text-green-600` |
| Non-Compliant | Count where compliance_status = `non_compliant` | `text-red-600` |
| In Progress | Count where compliance_status = `in_progress` | `text-yellow-600` |
| Not Applicable | Count where compliance_status = `not_applicable` | `text-gray-600` |
| Overdue Obligations | Count obligations overdue | `text-red-600` with badge |
| Due Soon | Count obligations due in 7 days | `text-orange-600` |

#### Search Box

| Element | Type | Detail |
|---|---|---|
| Placeholder | `<input>` | "Cari nomor, judul, nama regulasi..." |
| Behavior | debounce | 300ms, Inertia visit |
| Param | query | `?search=keyword` |

#### Filter Dropdowns

| Filter | Label | Options | Param |
|---|---|---|---|
| Compliance Status | "Status Kepatuhan" | Semua, Compliant, Non-Compliant, In Progress, Not Applicable | `?compliance_status=` |
| Category | "Kategori" | Semua, National, Regional, Industry, Internal | `?category=` |
| Site | "Site" | Semua + dari master Sites | `?site_id=` |
| Department | "Department" | Semua + dari master Departments | `?department_id=` |
| Owner | "Owner" | Semua + dari Users (filtered by role QHSSE) | `?owner_id=` |
| Reset | Button | "Reset" — clear all filters | — |

#### Table Columns

| # | Column | Key | Width | Align | Badge? | Detail |
|---|---|---|---|---|---|---|
| 1 | Nomor | `register_number` | 120px | left | No | Link ke show page, monospace |
| 2 | Judul | `title` | flex | left | No | Truncate `max-w-xs` |
| 3 | Kategori | `category` | 100px | center | Yes | Lihat Color Coding |
| 4 | Status Kepatuhan | `compliance_status` | 130px | center | Yes | Lihat Color Coding |
| 5 | Owner | `owner.name` | 140px | left | No | Nama user |
| 6 | Next Review | `next_review_date` | 90px | center | No | Format: `dd/mm/yy`, "—" jika null |
| 7 | Obligations | count | 130px | center | No | "3 (1🔴, 1🟡)" = total (overdue, due_soon) |
| 8 | Aksi | — | 120px | center | No | Lihat di bawah |

#### Aksi Column (per row)

| Action | Icon | Permission | Condition |
|---|---|---|---|
| Lihat | 👁 | `legal.register.view` | Selalu tampil |
| Edit | ✏ | `legal.register.update` | status = active |

### Inertia Props

```typescript
interface IndexProps {
    registers: {
        data: LegalRegister[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
    filters: {
        search?: string;
        compliance_status?: string;
        category?: string;
        site_id?: number;
        department_id?: number;
        owner_id?: number;
    };
    sites: Site[];
    departments: Department[];
    owners: User[];
    kpiSummary: {
        total: number;
        compliant: number;
        non_compliant: number;
        in_progress: number;
        not_applicable: number;
        overdue_obligations: number;
        due_soon_obligations: number;
    };
    can: {
        create: boolean;
        export: boolean;
    };
}
```

---

## 4. Halaman Form — Buat/Edit Register

### Route

- Create: `GET /legal-register/create` (`legal-register.create`)
- Edit: `GET /legal-register/{register}/edit` (`legal-register.edit`)

### Permission

- Create: `legal.register.create`
- Edit: `legal.register.update` (hanya jika status = active)

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                               │
│  Buat Register                                                                       │
│  Isi data register peraturan dengan lengkap                                          │
├──────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                      │
│  ┌─ Section: Informasi Regulasi ──────────────────────────────────────────────────┐   │
│  │  INFORMASI REGULASI                                                             │   │
│  │  ─────────────────────────────────────────────────────────────────────────────  │   │
│  │                                                                                 │   │
│  │  Nomor Register        [Auto-generated — LEG-0001                ]  ⓘ          │   │
│  │                         Nomor akan dibuat otomatis saat simpan                   │   │
│  │                                                                                 │   │
│  │  Judul *               [Masukkan judul register...                ]              │   │
│  │                                                                                 │   │
│  │  Nama Regulasi *       [Masukkan nama regulasi...                 ]              │   │
│  │                         Contoh: Undang-Undang Keselamatan Kerja                   │   │
│  │                                                                                 │   │
│  │  Nomor Regulasi *      [Masukkan nomor regulasi...                ]              │   │
│  │                         Contoh: UU No. 1 Tahun 1970                               │   │
│  │                                                                                 │   │
│  │  Issuing Body *        [Masukkan instansi penerbit...             ]              │   │
│  │                         Contoh: Pemerintah RI, Kemenaker, Pemda                    │   │
│  │                                                                                 │   │
│  │  Kategori *            [— Pilih Kategori —    ▾]                                 │   │
│  │                         ○ National   ○ Regional                                 │   │
│  │                         ○ Industry   ○ Internal                                 │   │
│  │                                                                                 │   │
│  │  Status Kepatuhan *    [— Pilih Status —    ▾]                                   │   │
│  │                         ○ 🟢 Compliant                                          │   │
│  │                         ○ 🔴 Non-Compliant                                      │   │
│  │                         ○ 🟡 In Progress  (default)                             │   │
│  │                         ○ ⚪ Not Applicable                                      │   │
│  │                                                                                 │   │
│  └─────────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                      │
│  ┌─ Section: Lokasi & Penanggung Jawab ───────────────────────────────────────────┐   │
│  │  LOKASI & PENANGGUNG JAWAB                                                      │   │
│  │  ─────────────────────────────────────────────────────────────────────────────  │   │
│  │                                                                                 │   │
│  │  Site                  [— Pilih Site —    ▾]  (opsional, nullable)              │   │
│  │                         Kosongkan jika berlaku company-wide                      │   │
│  │                                                                                 │   │
│  │  Department            [— Pilih Department —    ▾]  (opsional, nullable)        │   │
│  │                                                                                 │   │
│  │  Owner *               [— Pilih Owner —    ▾]                                  │   │
│  │                         Pilih user penanggung jawab kepatuhan                     │   │
│  │                                                                                 │   │
│  │  Next Review Date      [__/__/____]  (opsional)                                  │   │
│  │                         Tanggal review kepatuhan berikutnya                       │   │
│  │                                                                                 │   │
│  └─────────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                      │
│  ┌─ Section: Dokumen & Catatan ───────────────────────────────────────────────────┐   │
│  │  DOKUMEN & CATATAN                                                              │   │
│  │  ─────────────────────────────────────────────────────────────────────────────  │   │
│  │                                                                                 │   │
│  │  Dokumen Terhubung     [— Pilih Dokumen —    ▾]  (opsional)                     │   │
│  │                         Link ke dokumen terkendali (modul Document Control)      │   │
│  │                                                                                 │   │
│  │  Catatan               ┌──────────────────────────────────────────────┐         │   │
│  │                         │ Catatan tambahan tentang regulasi ini...     │         │   │
│  │                         │                                              │         │   │
│  │                         └──────────────────────────────────────────────┘         │   │
│  │                         (opsional)                                                │   │
│  │                                                                                 │   │
│  └─────────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                      │
│  ┌─ Action Bar (sticky bottom) ──────────────────────────────────────────────────┐   │
│  │                                                                                │   │
│  │  [← Batal]                                            [Simpan Register]      │   │
│  │                                                       (primary)                │   │
│  └────────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                      │
└──────────────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Section

#### Section: Informasi Regulasi

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Nomor Register | Text (readonly) | No | — | Auto-generated saat create. Placeholder "Auto-generated" |
| Judul | Text input | Yes | `required, min:5, max:255` | Placeholder: "Masukkan judul register..." |
| Nama Regulasi | Text input | Yes | `required, string, max:255` | Contoh: Undang-Undang Keselamatan Kerja |
| Nomor Regulasi | Text input | Yes | `required, string, max:255` | Contoh: UU No. 1 Tahun 1970 |
| Issuing Body | Text input | Yes | `required, string, max:255` | Contoh: Pemerintah RI, Kemenaker |
| Kategori | Radio/Select | Yes | `required, in:national,regional,industry,internal` | National / Regional / Industry / Internal |
| Status Kepatuhan | Radio/Select | Yes | `required, in:compliant,non_compliant,in_progress,not_applicable` | Default: in_progress |

#### Section: Lokasi & Penanggung Jawab

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Site | Select dropdown | No | `nullable, exists:sites,id` | Dari master Sites, nullable untuk company-wide |
| Department | Select dropdown | No | `nullable, exists:departments,id` | Filtered by site_id |
| Owner | Select (search) | Yes | `required, exists:users,id` | Cari user berdasarkan nama |
| Next Review Date | Date picker | No | `nullable, date` | Format: `dd/mm/yyyy` |

#### Section: Dokumen & Catatan

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Dokumen Terhubung | Select dropdown | No | `nullable, exists:documents,id` | Dari modul Document Control |
| Catatan | Textarea | No | `nullable, string` | Catatan tambahan |

### Action Buttons

| Button | Type | Style | Behavior |
|---|---|---|---|
| Batal | Link | `text-slate-600 hover:text-slate-900` | Redirect ke index page |
| Simpan Register | Submit | `bg-blue-600 text-white hover:bg-blue-700` | POST atau PUT, redirect ke show page |

### Edit Mode Notes

- Saat edit (status = active), nomor register tampil sebagai readonly
- Jika status `inactive`, redirect ke show page (tidak bisa edit)
- Field sama dengan create mode

### Inertia Props

```typescript
interface FormProps {
    register: LegalRegister | null;  // null untuk create, filled untuk edit
    sites: Site[];
    departments: Department[];
    owners: User[];                  // users yang bisa menjadi owner
    documents: Document[];           // available controlled documents
    can: {
        update: boolean;
    };
}
```

---

## 5. Halaman Show — Detail Register

### Route: `GET /legal-register/{register}` (`legal-register.show`)

### Permission: `legal.register.view`

### Wireframe — Desktop

```
┌───────────────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                                    │
│  ← Kembali ke Daftar                                                                      │
├───────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                           │
│  ┌─ Summary Card ──────────────────────────────────────────────────────────────────────┐   │
│  │                                                                                      │   │
│  │  LEG-0001                                 [🔵 National] [🟡 In Progress] [🟢 Active]│   │
│  │  UU No. 1 Tahun 1970 tentang Keselamatan Kerja                                       │   │
│  │                                                                                      │   │
│  │  📋 Regulasi: Undang-Undang Keselamatan Kerja                                        │   │
│  │  🔢 Nomor: UU No. 1 Tahun 1970                                                       │   │
│  │  🏛️ Issuing Body: Pemerintah RI                                                      │   │
│  │  🏭 Site: Plant A   🏢 Dept: Produksi                                                 │   │
│  │  👤 Owner: Budi Santoso                                                              │   │
│  │  📅 Next Review: 15/08/2026                                                           │   │
│  │  📄 Dokumen: [DOC-2026-0003] Lihat Dokumen                                           │   │
│  │                                                                                      │   │
│  │  ┌─ Action Buttons (permission-gated) ──────────────────────────────────────────┐    │   │
│  │  │  [✏ Edit]  [📁 Upload Evidence]                                             │    │   │
│  │  └────────────────────────────────────────────────────────────────────────────────┘    │   │
│  └──────────────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                           │
│  ┌─ Tab Bar ────────────────────────────────────────────────────────────────────────────┐   │
│  │  [📋 Detail]  [📝 Obligations (3)]  [📁 Evidence]  [💬 Comments (2)]  [📊 Activity] │   │
│  └──────────────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                           │
│  ┌─ Tab: Detail ───────────────────────────────────────────────────────────────────────┐   │
│  │                                                                                       │   │
│  │  ┌─ Left Column (2/3) ─────────────────────────────────┐  ┌─ Right Column (1/3) ──────────────────┐ │   │
│  │  │                                                     │  │                                     │ │   │
│  │  │  CATATAN                                           │  │  ┌─ INFO REGISTER ───────────────┐ │ │   │
│  │  │  ─────────────────────────────────────────────      │  │  │ Kategori:    National        │ │ │   │
│  │  │  Regulasi ini mencakup ketentuan keselamatan        │  │  │ Status:       In Progress     │ │ │   │
│  │  │  kerja untuk semua tempat kerja. Wajib dilaporkan   │  │  │ Site:         Plant A         │ │ │   │
│  │  │  kepatuhan secara berkala.                           │  │  │ Department:   Produksi        │ │ │   │
│  │  │                                                     │  │  └──────────────────────────────┘ │ │   │
│  │  │  OBLIGATIONS SUMMARY                                │  │                                     │ │   │
│  │  │  ─────────────────────────────────────────────      │  │  ┌─ OWNER ──────────────────────┐ │ │   │
│  │  │  Total: 3   Pending: 2   Completed: 1               │  │  │ Nama:  Budi Santoso           │ │ │   │
│  │  │  🔴 Overdue: 1   🟡 Due Soon: 0                     │  │  │ Email: budi.s@company.com      │ │ │   │
│  │  │                                                     │  │  └──────────────────────────────┘ │ │   │
│  │  └─────────────────────────────────────────────────────┘  │                                     │ │   │
│  │  └──────────────────────────────────────────────────────────┘                                     │ │   │
│  └──────────────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                           │
└───────────────────────────────────────────────────────────────────────────────────────────┘
```

### Tab: Obligations

```
┌───────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                           │
│  ┌─ Obligations Header ────────────────────────────────────────────────────────────────┐  │
│  │  KEWAJIBAN (3)                                          [+ Tambah Kewajiban]       │  │
│  │  ───────────────────────────────────────────────────────────────────────────────    │  │
│  │  Ringkasan: 🔴 Overdue: 1  🟡 Pending: 1  🟢 Completed: 1                         │  │
│  └──────────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                           │
│  ┌─ Obligation Card 1 ───────────────────────────────────────────────────────────────┐   │
│  │  Kewajiban #1                          [🔴 Overdue — 5 hari]  [🟡 Pending]        │   │
│  │  ─────────────────────────────────────────────────────────────────────────────    │   │
│  │  Deskripsi:                                                                        │   │
│  │  Lapor kepatuhan K3 bulanan ke Disnaker setiap akhir bulan.                        │   │
│  │                                                                                     │   │
│  │  Frekuensi: 📅 Monthly                                                              │   │
│  │  Terakhir Dilaksanakan: 01/06/2026                                                  │   │
│  │  Jatuh Tempo Berikutnya: 01/07/2026  (5 hari overdue)                              │   │
│  │  Evidence: [Belum ada]  [📁 Upload Evidence]                                        │   │
│  │                                                                                     │   │
│  │  ┌─ Actions ──────────────────────────────────────────────────────────────────┐    │   │
│  │  │  [✏ Edit]  [✓ Tandai Selesai]                                              │    │   │
│  │  └─────────────────────────────────────────────────────────────────────────────┘    │   │
│  └──────────────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                           │
│  ┌─ Obligation Card 2 ───────────────────────────────────────────────────────────────┐   │
│  │  Kewajiban #2                          [🟡 Pending]                                 │   │
│  │  ─────────────────────────────────────────────────────────────────────────────    │   │
│  │  Deskripsi:                                                                        │   │
│  │  Inspeksi alarm kebakaran secara triwulanan.                                       │   │
│  │                                                                                     │   │
│  │  Frekuensi: 📅 Quarterly                                                            │   │
│  │  Terakhir Dilaksanakan: 01/04/2026                                                  │   │
│  │  Jatuh Tempo Berikutnya: 01/07/2026                                                  │   │
│  │  Evidence: [Belum ada]  [📁 Upload Evidence]                                        │   │
│  │                                                                                     │   │
│  │  ┌─ Actions ──────────────────────────────────────────────────────────────────┐    │   │
│  │  │  [✏ Edit]  [✓ Tandai Selesai]                                              │    │   │
│  │  └─────────────────────────────────────────────────────────────────────────────┘    │   │
│  └──────────────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                           │
│  ┌─ Obligation Card 3 ───────────────────────────────────────────────────────────────┐   │
│  │  Kewajiban #3                          [🟢 Completed]                               │   │
│  │  ─────────────────────────────────────────────────────────────────────────────    │   │
│  │  Deskripsi:                                                                        │   │
│  │  Audit internal kepatuhan SMK3 secara tahunan.                                     │   │
│  │                                                                                     │   │
│  │  Frekuensi: 📅 Annual                                                               │   │
│  │  Terakhir Dilaksanakan: 15/01/2026                                                  │   │
│  │  Jatuh Tempo Berikutnya: 15/01/2027                                                  │   │
│  │  Evidence: [evidence_2026-01-15.pdf] ✓ Terunggah   [👁 Lihat]                      │   │
│  │                                                                                     │   │
│  └──────────────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                           │
└───────────────────────────────────────────────────────────────────────────────────────────┘
```

### Action Buttons (Permission-Gated)

| Button | Permission | Condition (status) | Route |
|---|---|---|---|
| Edit | `legal.register.update` | status = active | `legal-register.edit` |
| Upload Evidence | `legal.register.update` | status = active | `POST /core/files` |

### Inertia Props

```typescript
interface ShowProps {
    register: LegalRegister & {
        site: Site | null;
        department: Department | null;
        owner: User;
        document: Document | null;
        obligations: (LegalObligation & {
            evidence_file: ManagedFile | null;
            is_overdue: boolean;
            is_due_soon: boolean;
            days_overdue: number | null;
        })[];
        evidence_files: ManagedFile[];
        comments: Comment[];
        activities: ActivityLog[];
    };
    can: {
        update: boolean;
        export: boolean;
        create_obligation: boolean;
        update_obligation: boolean;
    };
}
```

---

## 6. Obligation Form (Inline di Register Show)

Obligation form ditampilkan sebagai **modal dialog** di tab Obligations pada halaman Show register.

### Wireframe — Modal Create Obligation

```
┌──────────────────────────────────────────────────────────────────────────────────────┐
│  ┌─ Modal Overlay ──────────────────────────────────────────────────────────────────┐│
│  │                                                                                   ││
│  │  ┌─ Modal Dialog ──────────────────────────────────────────────────────────────┐││
│  │  │                                                                              │││
│  │  │  Tambah Kewajiban                                    [✕]                     │││
│  │  │  ──────────────────────────────────────────────────────────────────────────  │││
│  │  │                                                                              │││
│  │  │  Deskripsi Kewajiban *  ┌──────────────────────────────────────────────┐    │││
│  │  │                           │ Jelaskan kewajiban yang harus dilaksanakan... │    │││
│  │  │                           │                                              │    │││
│  │  │                           │                                              │    │││
│  │  │                           └──────────────────────────────────────────────┘    │││
│  │  │                           Minimal 10 karakter                                │││
│  │  │                                                                              │││
│  │  │  Frekuensi *            [— Pilih Frekuensi —    ▾]                           │││
│  │  │                         ○ 📅 Bulanan (Monthly)                              │││
│  │  │                         ○ 📅 Triwulanan (Quarterly)                        │││
│  │  │                         ○ 📅 Tahunan (Annual)                               │││
│  │  │                                                                              │││
│  │  │  Terakhir Dilaksanakan [__/__/____]  (opsional)                              │││
│  │  │                         Jika diisi, next_due akan dihitung otomatis           │││
│  │  │                                                                              │││
│  │  │  Jatuh Tempo Berikutnya [Auto-calculated — ]  ⓘ                             │││
│  │  │                         Dihitung dari last_completed + frequency              │││
│  │  │                                                                              │││
│  │  │  ┌─ Action Bar ─────────────────────────────────────────────────────────┐    │││
│  │  │  │  [✕ Batal]                                  [Simpan Kewajiban]     │    │││
│  │  │  └────────────────────────────────────────────────────────────────────────┘    │││
│  │  │                                                                              │││
│  │  └──────────────────────────────────────────────────────────────────────────────┘││
│  │                                                                                   ││
│  └───────────────────────────────────────────────────────────────────────────────────┘│
└──────────────────────────────────────────────────────────────────────────────────────┘
```

### Modal Complete Obligation

```
┌──────────────────────────────────────────────────────────────────────────────────────┐
│  ┌─ Modal Dialog ──────────────────────────────────────────────────────────────────┐ │
│  │                                                                                │ │
│  │  Tandai Kewajiban Selesai                              [✕]                     │ │
│  │  ─────────────────────────────────────────────────────────────────────────     │ │
│  │                                                                                │ │
│  │  Kewajiban: Lapor kepatuhan K3 bulanan ke Disnaker...                          │ │
│  │  Frekuensi: Monthly                                                            │ │
│  │                                                                                │ │
│  │  Tanggal Pelaksanaan *  [__/__/____]                                          │ │
│  │                          Tanggal kewajiban dilaksanakan                          │ │
│  │                                                                                │ │
│  │  Upload Evidence *       [📁 Pilih File]                                       │ │
│  │                          Upload bukti pelaksanaan (wajib)                        │ │
│  │                          Format: PDF, JPG, PNG, DOCX, XLSX (max 25MB)          │ │
│  │                                                                                │ │
│  │  Next Due (Auto):       [01/08/2026]  ⓘ                                       │ │
│  │                         Dihitung otomatis: tanggal pelaksanaan + 1 bulan         │ │
│  │                                                                                │ │
│  │  ┌─ Action Bar ───────────────────────────────────────────────────────────┐    │ │
│  │  │  [✕ Batal]                              [✓ Tandai Selesai]            │    │ │
│  │  └────────────────────────────────────────────────────────────────────────┘    │ │
│  │                                                                                │ │
│  └────────────────────────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────────────────────┘
```

### Obligation Action Buttons

Setiap obligation card memiliki action buttons berikut:

| Button | Icon | Permission | Condition | Behavior |
|---|---|---|---|---|
| Edit | ✏ | `legal.obligations.update` | register status = active | Buka modal edit obligation |
| Tandai Selesai | ✓ | `legal.obligations.update` | obligation status = pending | Buka modal complete obligation |
| Upload Evidence | 📁 | `legal.obligations.update` | obligation status = pending | Trigger file upload untuk evidence |
| Lihat Evidence | 👁 | `legal.obligations.view` | evidence_file_id != null | Download/view evidence file |

### Complete Obligation Flow

Saat user klik "Tandai Selesai" pada obligation:

1. Modal terbuka dengan form: tanggal pelaksanaan + upload evidence
2. User mengisi tanggal pelaksanaan dan upload evidence file
3. Saat submit:
   a. File diupload via `POST /core/files` dengan `module_name='legal'`, `reference_id=obligation.id`, `collection='obligation_evidence'`
   b. `obligation.last_completed` di-update dengan tanggal pelaksanaan
   c. `obligation.next_due` di-recalculate berdasarkan frequency
   d. `obligation.status` berubah ke `completed`
   e. `obligation.evidence_file_id` di-set dengan ID file yang diupload
4. Activity log: `legal.obligation.completed`
5. Audit trail: `legal.obligation.completed`

---

## 7. Mobile Responsive

### Index Page — Mobile

```
┌──────────────────────────┐
│  Legal & Compliance      │
│           [+ Buat]      │
├──────────────────────────┤
│ Total: 32  🟢18 🔴3    │
│ 🟡8 ⚪3  Overdue: 5     │
├──────────────────────────┤
│ [🔍 Cari...]             │
│ [Status ▾] [Kategori ▾] │
│ [Site ▾]  [Reset]       │
├──────────────────────────┤
│ 1–15 dari 32  [⬇ CSV]  │
├──────────────────────────┤
│ ┌──────────────────────┐│
│ │ LEG-0001            ││
│ │ UU No.1/1970         ││
│ │ [🔵Nat] [🟡InProg]  ││
│ │ Owner: Budi S.       ││
│ │ Oblig: 3 (1🔴)       ││
│ │ Review: 15/08        ││
│ │              [👁]    ││
│ └──────────────────────┘│
│ ┌──────────────────────┐│
│ │ LEG-0002            ││
│ │ Pergub JKT K3        ││
│ │ [🟣Reg] [🔴NonComp]  ││
│ │ Owner: Sari W.       ││
│ │ Oblig: 2 (0🔴)       ││
│ │              [👁]    ││
│ └──────────────────────┘│
├──────────────────────────┤
│      ‹  1  2  3  ›      │
└──────────────────────────┘
```

### Form Page — Mobile

```
┌──────────────────────────┐
│  ← Buat Register         │
├──────────────────────────┤
│                          │
│  INFORMASI REGULASI      │
│  ─────────────────       │
│  Nomor: Auto-generated   │
│  Judul *                 │
│  [........................]│
│  Nama Regulasi *         │
│  [........................]│
│  Nomor Regulasi *        │
│  [........................]│
│  Issuing Body *          │
│  [........................]│
│  Kategori * [Pilih ▾]   │
│  Status Kepatuhan *      │
│  [Pilih ▾]              │
│                          │
│  LOKASI & PJ             │
│  ─────────────────       │
│  Site [Pilih ▾]        │
│  Dept [Pilih ▾]        │
│  Owner * [Pilih ▾]     │
│  Next Review [__/__/__]  │
│                          │
│  DOKUMEN & CATATAN       │
│  ─────────────────       │
│  Dokumen [Pilih ▾]     │
│  Catatan                 │
│  ┌──────────────────┐    │
│  │                  │    │
│  └──────────────────┘    │
│                          │
├──────────────────────────┤
│ [Batal]  [Simpan Register]│ ← sticky bottom
└──────────────────────────┘
```

### Show Page — Mobile

```
┌──────────────────────────┐
│  ← Kembali               │
├──────────────────────────┤
│                          │
│  LEG-0001                │
│  [🔵Nat] [🟡InProg]     │
│  [🟢Active]              │
│  UU No.1/1970            │
│                          │
│  📋 UU Keselamatan Kerja │
│  🔢 UU No.1/1970        │
│  🏛️ Pemerintah RI        │
│  🏭 Plant A              │
│  🏢 Produksi             │
│  👤 Budi Santoso         │
│  📅 15/08/2026           │
│  📄 [DOC-0003] Lihat     │
│                          │
│  [✏ Edit]                │
│                          │
├──────────────────────────┤
│ [Detail] [Obligations(3)]│
│ [Evidence] [Comments]   │
│ [Activity]              │
├──────────────────────────┤
│                          │
│  KEWAJIBAN (3)           │
│  🔴Overdue:1 🟡Pending:1│
│  🟢Completed:1           │
│  [+ Tambah Kewajiban]    │
│                          │
│ ┌──────────────────────┐ │
│ │ Kewajiban #1        │ │
│ │ [🔴Overdue 5 hari]  │ │
│ │ Lapor kepatuhan K3  │ │
│ │ bulanan ke Disnaker │ │
│ │ 📅Monthly  Due:01/07│ │
│ │ [✓ Tandai Selesai]  │ │
│ └──────────────────────┘ │
│ ┌──────────────────────┐ │
│ │ Kewajiban #2        │ │
│ │ [🟡Pending]          │ │
│ │ Inspeksi alarm      │ │
│ │ kebakaran triwulanan│ │
│ │ 📅Quarterly Due:01/07│ │
│ │ [✓ Tandai Selesai]  │ │
│ └──────────────────────┘ │
│                          │
└──────────────────────────┘
```

### Mobile Notes

- **Table → Card list**: Setiap row berubah menjadi card vertikal
- **KPI bar**: Wrap ke baris berikutnya di mobile
- **Filter**: Dropdown wrap ke baris berikutnya
- **Obligations tab**: Obligations tampil sebagai card stack, tidak ada table
- **Obligation modal**: Full screen modal di mobile
- **Action bar**: Sticky bottom dengan tombol primary
