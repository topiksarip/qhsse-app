# UI Pages — Contractor Management

Spesifikasi wireframe halaman UI untuk modul Contractor Management (CSMS).

Semua label menggunakan Bahasa Indonesia. Tech stack: Laravel 12 + Inertia React + TypeScript + Tailwind CSS.

---

## Daftar Isi

1. [Navigasi](#1-navigasi)
2. [Color Coding](#2-color-coding)
3. [Halaman Index — Daftar Kontraktor](#3-halaman-index--daftar-kontraktor)
4. [Halaman Form — Buat/Edit Kontraktor](#4-halaman-form--buatedit-kontraktor)
5. [Halaman Show — Detail Kontraktor](#5-halaman-show--detail-kontraktor)
6. [Form Evaluasi (Inline di Contractor Show)](#6-form-evaluasi--inline-di-contractor-show)
7. [Mobile Responsive](#7-mobile-responsive)
8. [Component List](#8-component-list)

---

## 1. Navigasi

### Penempatan Menu

Tambahkan item menu `Manajemen Kontraktor` pada group `Modul QHSSE` di array `menuGroups` pada `AuthenticatedLayout.tsx`.

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
            { label: 'Permit to Work', routeName: 'permits.index', active: 'permits.*', permission: 'permit.work.view' },
            { label: 'Manajemen Kontraktor', routeName: 'contractors.index', active: 'contractors.*', permission: 'contractor.management.view' },  // ← NEW
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
┌──────────────────────────────────────────────────────────────────────────┐
│  [Logo] QHSSE   Core ▾   Modul QHSSE ▾   Masters ▾   Admin ▾    [User]  │
│                          ┌──────────────────────────┐                    │
│                          │ Laporan Insiden         │                    │
│                          │ Audit Management        │                    │
│                          │ Permit to Work          │                    │
│                          │ Manajemen Kontraktor    │                    │
│                          └──────────────────────────┘                    │
└──────────────────────────────────────────────────────────────────────────┘
```

### Permission Filtering

Menu hanya tampil jika user memiliki permission `contractor.management.view`. Filtering dilakukan via `auth.permissions` pada layout.

---

## 2. Color Coding

### Prequalification Badge

| Status | Tailwind Class | Preview |
|---|---|---|
| Prequalified | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Prequalified` |
| Expiring Soon | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 Akan Kedaluwarsa` |
| Expired | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Kedaluwarsa` |
| Not Prequalified | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | `⚪ Belum Prequalified` |

### Safety Rating Badge

| Rating | Tailwind Class | Preview |
|---|---|---|
| Excellent | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Excellent` |
| Good | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | `🔵 Good` |
| Fair | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 Fair` |
| Poor | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Poor` |

### Contractor Status Badge

| Status | Tailwind Class | Preview |
|---|---|---|
| Active | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Active` |
| Inactive | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | `⚪ Inactive` |
| Blacklisted | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Blacklisted` |

### Evaluation Result Badge

| Result | Tailwind Class | Preview |
|---|---|---|
| Pass | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Pass` |
| Conditional | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 Conditional` |
| Fail | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Fail` |

### Pemetaan Helper

```typescript
// utils/badgeColors.ts

const prequalificationStatusColors: Record<string, BadgeColor> = {
    prequalified:     'green',
    expiring_soon:    'yellow',
    expired:          'red',
    not_prequalified: 'gray',
};

const safetyRatingColors: Record<string, BadgeColor> = {
    excellent: 'green',
    good:      'blue',
    fair:      'yellow',
    poor:      'red',
};

const contractorStatusColors: Record<string, BadgeColor> = {
    active:      'green',
    inactive:    'gray',
    blacklisted:  'red',
};

const evaluationResultColors: Record<string, BadgeColor> = {
    pass:        'green',
    conditional: 'yellow',
    fail:        'red',
};
```

### Prequalification Status Helper

```typescript
function getPrequalificationStatus(contractor: Contractor): string {
    if (!contractor.is_prequalified) return 'not_prequalified';
    if (!contractor.prequalified_until) return 'not_prequalified';
    const now = new Date();
    const expiry = new Date(contractor.prequalified_until);
    const thirtyDaysFromNow = new Date();
    thirtyDaysFromNow.setDate(thirtyDaysFromNow.getDate() + 30);

    if (expiry < now) return 'expired';
    if (expiry <= thirtyDaysFromNow) return 'expiring_soon';
    return 'prequalified';
}
```

---

## 3. Halaman Index — Daftar Kontraktor

### Route: `GET /contractors` (`contractors.index`)

### Permission: `contractor.management.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Manajemen Kontraktor                                     [+ Daftar Kontraktor] │
│  Kelola kontraktor, prequalification, dan evaluasi kinerja keselamatan           │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Summary Cards ─────────────────────────────────────────────────────────────┐ │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐     │ │
│  │  │ Total    │  │ Prequal- │  │ Akan     │  │ Kedaluar-│  │ Belum    │     │ │
│  │  │ 25       │  │ ified    │  │ Kadaluw. │  │ warsa    │  │ Prequal. │     │ │
│  │  │          │  │ 12       │  │ 3        │  │ 2        │  │ 8        │     │ │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────┘  └──────────┘     │ │
│  └──────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                  │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari nomor, perusahaan, contact person...     ]                         │  │
│  │                                                                            │  │
│  │ Status: [Semua ▾]  Prequalification: [Semua ▾]  Jenis Layanan: [Semua ▾]  │  │
│  │ Safety Rating: [Semua ▾]                              [Reset]              │  │
│  └────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Toolbar ────────────────────────────────────────────────────────────────┐  │
│  │ Menampilkan 1–15 dari 25 kontraktor                    [⬇ Export CSV]     │  │
│  └────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Table ───────────────────────────────────────────────────────────────────┐  │
│  │ Nomor       Perusahaan          Contact Person  Jenis Layanan  Prequal.  │  │
│  ├───────────────────────────────────────────────────────────────────────────┤  │
│  │ CTR-0001  PT Karya Konstruksi  Budi Santoso    Konstruksi Sipil 🟢Prequal │  │
│  │ CTR-0002  PT Mega Mechanical   Sari Wulandari  Mechanical    🟡Akan Kad. │  │
│  │ CTR-0003  CV Sinar Listrik      Andi Pratama    Electrical     🔴Kadaluw. │  │
│  │ CTR-0004  PT Bersih Sejahtera  Dewi Lestari    Cleaning Svc  ⚪Belum     │  │
│  │ ...                                                                       │  │
│  └───────────────────────────────────────────────────────────────────────────┘  │
│  ┌─ Table (cont.) ──────────────────────────────────────────────────────────┐  │
│  │ ... Safety Rating  Status     Aksi                                         │  │
│  │ ... 🟢Excellent    🟢Active   [👁 Lihat] [✏ Edit]                         │  │
│  │ ... 🟡Fair         🟢Active   [👁 Lihat] [✏ Edit]                         │  │
│  │ ... 🔴Poor         🟢Active   [👁 Lihat] [✏ Edit]                         │  │
│  │ ... —              🟢Active   [👁 Lihat] [✏ Edit]                         │  │
│  └───────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Pagination ───────────────────────────────────────────────────────────────┐ │
│  │                         ‹ Sebelumnya   1  2   Berikutnya ›                │ │
│  └────────────────────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Wireframe — Empty State

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│                                                                                  │
│  ┌──────────────────────────────────────────────────────────────────────────┐   │
│  │                          🏗️                                               │   │
│  │                                                                          │   │
│  │                   Belum ada kontraktor                                   │   │
│  │                                                                          │   │
│  │           Belum ada kontraktor yang terdaftar. Klik tombol di bawah      │   │
│  │           untuk mendaftarkan kontraktor pertama Anda.                    │   │
│  │                                                                          │   │
│  │                      [+ Daftar Kontraktor Pertama]                       │   │
│  │                                                                          │   │
│  └──────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Element

#### Header

| Element | Type | Detail |
|---|---|---|
| Title | `<h1>` | "Manajemen Kontraktor" |
| Subtitle | `<p>` | "Kelola kontraktor, prequalification, dan evaluasi kinerja keselamatan" |
| Button "Daftar Kontraktor" | `<Link>` | Route: `contractors.create`, permission: `contractor.management.create` |
| Button Style | Tailwind | `bg-blue-600 text-white hover:bg-blue-700` |

#### Summary Cards

| Card | Query | Color |
|---|---|---|
| Total | `Contractor::count()` | `bg-blue-50 text-blue-700` |
| Prequalified | `is_prequalified=true AND prequalified_until > now+30d` | `bg-green-50 text-green-700` |
| Akan Kadaluwarsa | `is_prequalified=true AND prequalified_until BETWEEN now AND now+30d` | `bg-yellow-50 text-yellow-700` |
| Kedaluwarsa | `is_prequalified=true AND prequalified_until < now` | `bg-red-50 text-red-700` |
| Belum Prequalified | `is_prequalified=false` | `bg-gray-50 text-gray-700` |

#### Search Box

| Element | Type | Detail |
|---|---|---|
| Placeholder | `<input>` | "Cari nomor, perusahaan, contact person..." |
| Behavior | debounce | 300ms, Inertia visit |
| Param | query | `?search=keyword` |

#### Filter Dropdowns

| Filter | Label | Options | Param |
|---|---|---|---|
| Status | "Status" | Semua, Active, Inactive, Blacklisted | `?status=` |
| Prequalification | "Prequalification" | Semua, Prequalified, Akan Kadaluwarsa, Kedaluwarsa, Belum Prequalified | `?prequalification=` |
| Jenis Layanan | "Jenis Layanan" | Semua + unique service_type values | `?service_type=` |
| Safety Rating | "Safety Rating" | Semua, Excellent, Good, Fair, Poor | `?safety_rating=` |
| Reset | Button | "Reset" — clear all filters | — |

#### Table Columns

| # | Column | Key | Width | Align | Badge? | Detail |
|---|---|---|---|---|---|---|
| 1 | Nomor | `contractor_number` | 120px | left | No | Link ke show page, monospace |
| 2 | Perusahaan | `company.name` | flex | left | No | Truncate `max-w-xs` |
| 3 | Contact Person | `contact_person` | 150px | left | No | Nama contact person |
| 4 | Jenis Layanan | `service_type` | 150px | left | No | Free-text |
| 5 | Prequalification | badge | 140px | center | Yes | Lihat Color Coding |
| 6 | Safety Rating | `safety_rating` | 120px | center | Yes | Lihat Color Coding, "—" jika null |
| 7 | Status | `status` | 100px | center | Yes | Lihat Color Coding |
| 8 | Aksi | — | 120px | center | No | Lihat di bawah |

#### Aksi Column (per row)

| Action | Icon | Permission | Condition |
|---|---|---|---|
| Lihat | 👁 | `contractor.management.view` | Selalu tampil |
| Edit | ✏ | `contractor.management.update` | Selalu tampil (edit info, bukan nomor) |

### Inertia Props

```typescript
interface IndexProps {
    contractors: {
        data: Contractor[];
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
        prequalification?: string;
        service_type?: string;
        safety_rating?: string;
    };
    serviceTypes: string[];  // unique service_type values for filter dropdown
    summary: {
        total: number;
        prequalified: number;
        expiring_soon: number;
        expired: number;
        not_prequalified: number;
    };
    can: {
        create: boolean;
        export: boolean;
    };
}
```

---

## 4. Halaman Form — Buat/Edit Kontraktor

### Route

- Create: `GET /contractors/create` (`contractors.create`)
- Edit: `GET /contractors/{contractor}/edit` (`contractors.edit`)

### Permission

- Create: `contractor.management.create`
- Edit: `contractor.management.update`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Daftar Kontraktor                                                               │
│  Isi data kontraktor dengan lengkap                                             │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Kontraktor ─────────────────────────────────────────────┐ │
│  │  INFORMASI KONTRAKTOR                                                        │ │
│  │  ─────────────────────────────────────────────────────────────────────────   │ │
│  │                                                                              │ │
│  │  Nomor Kontraktor     [Auto-generated — CTR-2026-0001          ]  ⓘ        │ │
│  │                       Nomor akan dibuat otomatis saat simpan                 │ │
│  │                                                                              │ │
│  │  Perusahaan *         [— Pilih Perusahaan —    ▾]                            │ │
│  │                       Pilih perusahaan dari master data                       │ │
│  │                                                                              │ │
│  │  Jenis Layanan *      [Masukkan jenis layanan...              ]               │ │
│  │                       Contoh: Konstruksi Sipil, Mechanical & Piping            │ │
│  │                                                                              │
│  └──────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                  │
│  ┌─ Section: Contact Person ────────────────────────────────────────────────────┐ │
│  │  CONTACT PERSON                                                              │ │
│  │  ─────────────────────────────────────────────────────────────────────────   │ │
│  │                                                                              │ │
│  │  Nama Contact *       [Masukkan nama contact person...       ]               │ │
│  │                                                                              │ │
│  │  Telepon *            [08xx-xxxx-xxxx                        ]               │ │
│  │                                                                              │ │
│  │  Email                 [email@perusahaan.com                 ]   (opsional)  │ │
│  │                                                                              │ │
│  └──────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                  │
│  ┌─ Section: Prequalification ──────────────────────────────────────────────────┐ │
│  │  PREQUALIFICATION                                                            │ │
│  │  ─────────────────────────────────────────────────────────────────────────   │ │
│  │                                                                              │ │
│  │  Status Prequal.     [ ] Belum Prequalified (default)                       │ │
│  │                       [ ] Prequalified                                       │ │
│  │                                                                              │ │
│  │  Berlaku Sampai       [__/__/____]   (wajib jika Prequalified)              │ │
│  │                                                                              │ │
│  │  Catatan              Prequalification dapat diaktifkan setelah              │ │
│  │                       verifikasi dokumen dan evaluasi.                       │ │
│  │                                                                              │ │
│  └──────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                  │
│  ┌─ Action Bar (sticky bottom) ──────────────────────────────────────────────────┐ │
│  │                                                                           │ │
│  │  [← Batal]                                          [Simpan Kontraktor]  │ │
│  │                                                     (primary)             │ │
│  └───────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Section

#### Section: Informasi Kontraktor

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Nomor Kontraktor | Text (readonly) | No | — | Auto-generated saat create. Placeholder "Auto-generated" |
| Perusahaan | Select (search) | Yes | `required, exists:companies,id` | Pilih dari master Companies. Hanya company dengan type contractor/vendor |
| Jenis Layanan | Text input | Yes | `required, string, max:255` | Free-text, contoh: "Konstruksi Sipil" |

#### Section: Contact Person

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Nama Contact | Text input | Yes | `required, string, max:255` | Nama orang yang bisa dihubungi |
| Telepon | Text input | Yes | `required, string, max:50` | Nomor telepon/HP |
| Email | Text input | No | `nullable, email, max:255` | Email contact person |

#### Section: Prequalification

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Status Prequalification | Radio/Checkbox | No | `boolean, default: false` | Default: belum prequalified |
| Berlaku Sampai | Date picker | Conditional | `required_if:is_prequalified,true, date, after:today` | Wajib jika prequalified |

### Action Buttons

| Button | Type | Style | Behavior |
|---|---|---|---|
| Batal | Link | `text-slate-600 hover:text-slate-900` | Redirect ke index page |
| Simpan Kontraktor | Submit | `bg-blue-600 text-white hover:bg-blue-700` | POST atau PUT, redirect ke show page |

### Edit Mode Notes

- Saat edit, nomor kontraktor tampil sebagai readonly (immutable).
- Field `company_id` dapat diubah jika belum ada PTW terkait.
- Jika status `blacklisted`, tidak bisa edit kecuali oleh Admin.
- Evaluasi tidak dapat ditambahkan dari form ini — dilakukan di halaman Show.

### Inertia Props

```typescript
interface FormProps {
    contractor: Contractor | null;  // null untuk create, filled untuk edit
    companies: Company[];           // companies dengan type contractor/vendor
    can: {
        update: boolean;
    };
}
```

---

## 5. Halaman Show — Detail Kontraktor

### Route: `GET /contractors/{contractor}` (`contractors.show`)

### Permission: `contractor.management.view`

### Wireframe — Desktop

```
┌───────────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                                │
│  ← Kembali ke Daftar                                                                  │
├───────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                       │
│  ┌─ Summary Card ──────────────────────────────────────────────────────────────────┐  │
│  │                                                                                 │  │
│  │  CTR-2026-0001                          [🟢 Prequalified] [🟢 Active]        │  │
│  │  PT Karya Konstruksi Sejahtera                                                   │  │
│  │                                                                                 │  │
│  │  🏭 Jenis Layanan: Konstruksi Sipil                                             │  │
│  │  👤 Contact Person: Budi Santoso    📱 0812-3456-7890    ✉ budi@karya.com      │  │
│  │  ⭐ Safety Rating: 🟢 Excellent                                                  │  │
│  │  📅 Prequalified Until: 31/12/2026                                              │  │
│  │                                                                                 │  │
│  │  ┌─ Action Buttons (permission-gated) ──────────────────────────────────────┐   │  │
│  │  │  [✏ Edit]  [📋 Evaluasi Baru]  [✓ Set Prequalified]  [🚫 Revoke Prequal]│   │  │
│  │  └─────────────────────────────────────────────────────────────────────────┘   │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Tab Bar ────────────────────────────────────────────────────────────────────────┐  │
│  │  [📋 Detail]  [📊 Evaluasi (3)]  [🔗 PTW (5)]  [⚠️ Insiden (2)]  [📁 Dokumen]    │  │
│  │  [💬 Komentar (1)]  [📝 Aktivitas]                                                │  │
│  └───────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Tab: Detail ────────────────────────────────────────────────────────────────────┐  │
│  │                                                                                  │  │
│  │  ┌─ Left Column (2/3) ─────────────────────────────────┐  ┌─ Right Column (1/3) ──────────────────┐ │  │
│  │  │                                                     │  │                                     │ │  │
│  │  │  INFORMASI KONTRAKTOR                               │  │  ┌─ INFO PERUSAHAAN ───────────────┐ │ │  │
│  │  │  ─────────────────────────────────────────────      │  │  │ Kode:  PT-KARYA-001             │ │ │  │
│  │  │  Nomor:       CTR-2026-0001                        │  │  │ Nama:  PT Karya Konstruksi      │ │ │  │
│  │  │  Jenis:       Konstruksi Sipil                     │  │  │ Type:  Contractor               │ │ │  │
│  │  │  Status:      🟢 Active                            │  │  │ Aktif: ✅                       │ │ │  │
│  │  │  Prequalified: ✅ Hingga 31/12/2026                 │  │  └─────────────────────────────────┘ │ │  │
│  │  │                                                     │  │                                     │ │  │
│  │  │  CONTACT PERSON                                    │  │  ┌─ SAFETY SCORE ─────────────────┐ │ │  │
│  │  │  ─────────────────────────────────────────────      │  │  │ Rating: 🟢 Excellent           │ │ │  │
│  │  │  Nama:    Budi Santoso                             │  │  │ Rata-rata: 88.5/100            │ │ │  │
│  │  │  Telepon: 0812-3456-7890                           │  │  │ Evaluasi: 3 total              │ │ │  │
│  │  │  Email:   budi@karya.com                           │  │  │ Terakhir: 15/06/2026           │ │ │  │
│  │  │                                                     │  │  └─────────────────────────────────┘ │ │  │
│  │  └─────────────────────────────────────────────────────┘  └─────────────────────────────────────┘ │  │
│  └──────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
└───────────────────────────────────────────────────────────────────────────────────────┘
```

### Tab: Evaluasi

```
┌───────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                       │
│  ┌─ Evaluasi Header ──────────────────────────────────────────────────────────────┐  │
│  │  RIWAYAT EVALUASI (3)                                              [+ Evaluasi] │  │
│  │  ───────────────────────────────────────────────────────────────────────────    │  │
│  │  Ringkasan: 🟢 Pass: 2  🟡 Conditional: 1  🔴 Fail: 0                          │  │
│  │  Rata-rata Skor: 88.5/100  |  Safety Rating: 🟢 Excellent                      │  │
│  └────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Evaluasi Card 1 ───────────────────────────────────────────────────────────────┐  │
│  │  Tanggal: 15/06/2026                              [🟢 Pass]  Skor: 92/100     │  │
│  │  ──────────────────────────────────────────────────────────────────────────    │  │
│  │  Evaluator: Andi Pratama (QHSSE Officer)                                        │  │
│  │                                                                                 │  │
│  │  Kriteria Penilaian:                                                            │  │
│  │  ┌──────────────────────────────────────────────────────────────────────────┐  │  │
│  │  │ Compliance Dokumen          20/20 ████████████████████████               │  │  │
│  │  │ Rekam Jejak Keselamatan     23/25 ████████████████████░░░                 │  │  │
│  │  │ Kompetensi Personel         18/20 ███████████████████░░░░                │  │  │
│  │  │ Ketersediaan APD             15/15 ████████████████████████              │  │  │
│  │  │ Program K3                   16/20 ████████████████░░░░░░                │  │  │
│  │  └──────────────────────────────────────────────────────────────────────────┘  │  │
│  │  Total: 92/100                                                                  │  │
│  │                                                                                 │  │
│  │  Catatan: Kontraktor menunjukkan komitmen tinggi terhadap keselamatan.          │  │
│  │  Dokumen prequalification lengkap dan up to date.                              │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Evaluasi Card 2 ───────────────────────────────────────────────────────────────┐  │
│  │  Tanggal: 15/01/2026                              [🟡 Conditional]  Skor: 75  │  │
│  │  ──────────────────────────────────────────────────────────────────────────    │  │
│  │  Evaluator: Sari Wulandari (QHSSE Manager)                                      │  │
│  │                                                                                 │  │
│  │  Kriteria Penilaian:                                                            │  │
│  │  ┌──────────────────────────────────────────────────────────────────────────┐  │  │
│  │  │ Compliance Dokumen          18/20 ████████████████████░░                 │  │  │
│  │  │ Rekam Jejak Keselamatan     20/25 ████████████████░░░░░░░                 │  │  │
│  │  │ Kompetensi Personel         15/20 ███████████████░░░░░░░░                │  │  │
│  │  │ Ketersediaan APD             12/15 ████████████████░░░░░                  │  │  │
│  │  │ Program K3                   10/20 ██████████░░░░░░░░░░░░                │  │  │
│  │  └──────────────────────────────────────────────────────────────────────────┘  │  │
│  │  Total: 75/100                                                                  │  │
│  │                                                                                 │  │
│  │  Catatan: Perlu perbaikan pada program K3 dan kompetensi personel.             │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
└───────────────────────────────────────────────────────────────────────────────────────┘
```

### Tab: PTW (Linked Permits)

```
┌───────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                       │
│  ┌─ PTW Header ────────────────────────────────────────────────────────────────────┐  │
│  │  IZIN KERJA TERKAIT (5)                                                         │  │
│  │  ───────────────────────────────────────────────────────────────────────────    │  │
│  │  Ringkasan: 🟢 Active: 2  ⚪ Draft: 1  🔴 Expired: 1  🟢 Closed: 1             │  │
│  └────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Table ───────────────────────────────────────────────────────────────────────┐  │
│  │ Nomor          Judul                    Jenis        Status    Mulai           │  │
│  ├───────────────────────────────────────────────────────────────────────────────┤  │
│  │ PTW-2026-0015  Pengelasan Strip Plate    Hot Work   🟢Active  11/07 14:00     │  │
│  │ PTW-2026-0012  Pekerjaan Ketinggian      Height     ⚪Draft   —               │  │
│  │ PTW-2026-0008  Excavation Trench         Excavation 🔴Expired 05/07 08:00     │  │
│  │ PTW-2026-0005  Lifting Tower Section     Lifting    🟢Closed  01/07 06:00     │  │
│  │ PTW-2026-0001  Confined Space Entry      Confined   🟢Active  10/07 07:00     │  │
│  └───────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  Klik baris untuk navigasi ke halaman detail PTW.                                    │
│                                                                                       │
└───────────────────────────────────────────────────────────────────────────────────────┘
```

### Tab: Insiden (Linked Incidents)

```
┌───────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                       │
│  ┌─ Insiden Header ────────────────────────────────────────────────────────────────┐  │
│  │  INSIDEN TERKAIT (2)                                                            │  │
│  │  ───────────────────────────────────────────────────────────────────────────    │  │
│  │  Ringkasan: 🔴 Critical: 0  🟠 Major: 1  🟡 Minor: 1                            │  │
│  └────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Table ───────────────────────────────────────────────────────────────────────┐  │
│  │ Nomor          Judul                    Severity   Status    Tanggal          │  │
│  ├───────────────────────────────────────────────────────────────────────────────┤  │
│  │ INC-2026-0008  Cedra Jatuh dari Ketinggian  🟠Major   Closed   03/06/2026      │  │
│  │ INC-2026-0003  Hampir Celaka Lifting       🟡Minor   Closed   15/03/2026      │  │
│  └───────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  Klik baris untuk navigasi ke halaman detail insiden.                               │
│                                                                                       │
└───────────────────────────────────────────────────────────────────────────────────────┘
```

### Tab: Dokumen

```
┌───────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                       │
│  ┌─ Dokumen Header ────────────────────────────────────────────────────────────────┐  │
│  │  DOKUMEN KONTRAKTOR                                                              │  │
│  │  ───────────────────────────────────────────────────────────────────────────    │  │
│  │  Collection: [Semua ▾]                                              [↑ Upload]  │  │
│  └────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ File List ─────────────────────────────────────────────────────────────────────┐  │
│  │ Nama File                    Collection         Ukuran    Tanggal    Aksi       │  │
│  ├───────────────────────────────────────────────────────────────────────────────┤  │
│  │ SPPKP_PT_Karya_2026.pdf      Prequalification   2.3 MB    01/01/2026  [⬇] [🗑]│  │
│  │ Sertifikat_SMK3_2025.pdf     Prequalification   1.8 MB    01/01/2026  [⬇] [🗑]│  │
│  │ ISO_45001_Certificate.pdf    Prequalification   3.1 MB    01/01/2026  [⬇] [🗑]│  │
│  │ Evaluasi_Q1_2026.pdf         Evaluation         0.5 MB    15/01/2026  [⬇] [🗑]│  │
│  │ Daftar_Tenaga_Kerja.xlsx     Supporting Docs   0.8 MB    01/01/2026  [⬇] [🗑]│  │
│  └───────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
└───────────────────────────────────────────────────────────────────────────────────────┘
```

### Tab: Komentar

```
┌───────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                       │
│  ┌─ Komentar ──────────────────────────────────────────────────────────────────────┐  │
│  │                                                                                  │  │
│  │  ┌─ Andi Pratama (QHSSE Officer) — 10/06/2026 14:30 ───────────────────────┐   │  │
│  │  │ Kontraktor perlu evaluasi ulang sebelum perpanjangan prequalification.  │   │  │
│  │  │ Mohon jadwalkan evaluasi Q3 2026.                                       │   │  │
│  │  └─────────────────────────────────────────────────────────────────────────┘   │  │
│  │                                                                                  │  │
│  │  ┌─ Sari Wulandari (QHSSE Manager) — 11/06/2026 09:15 ─────────────────────┐   │  │
│  │  │ Setuju, akan dijadwalkan minggu depan.                                   │   │  │
│  │  └─────────────────────────────────────────────────────────────────────────┘   │  │
│  │                                                                                  │  │
│  │  [✏ Tulis Komentar...                                                  ] [Kirim]│  │
│  │  [☑ Internal Only]                                                              │  │
│  │                                                                                  │  │
│  └──────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
└───────────────────────────────────────────────────────────────────────────────────────┘
```

### Tab: Aktivitas

```
┌───────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                       │
│  ┌─ Activity Timeline ─────────────────────────────────────────────────────────────┐  │
│  │                                                                                  │  │
│  │  ●─ 15/06/2026 10:00 — Evaluasi Baru                                            │  │
│  │  │   Andi Pratama membuat evaluasi baru. Skor: 92/100 (Pass)                    │  │
│  │  │   Safety rating diperbarui: good → excellent                                 │  │
│  │  │                                                                               │  │
│  │  ●─ 01/06/2026 14:00 — Prequalification Diaktifkan                               │  │
│  │  │   Sari Wulandari mengaktifkan prequalification hingga 31/12/2026.            │  │
│  │  │                                                                               │  │
│  │  ●─ 15/01/2026 10:00 — Evaluasi Baru                                            │  │
│  │  │   Sari Wulandari membuat evaluasi baru. Skor: 75/100 (Conditional)           │  │
│  │  │                                                                               │  │
│  │  ●─ 05/01/2026 09:00 — Kontraktor Dibuat                                        │  │
│  │  │   Andi Pratama mendaftarkan kontraktor CTR-2026-0001.                        │  │
│  │  │                                                                               │  │
│  └──────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
└───────────────────────────────────────────────────────────────────────────────────────┘
```

### Action Buttons (Summary Card)

| Button | Permission | Condition | Style |
|---|---|---|---|
| Edit | `contractor.management.update` | Selalu | `text-slate-600 hover:text-slate-900 border border-slate-300` |
| Evaluasi Baru | `contractor.management.evaluate` | Selalu | `bg-blue-600 text-white hover:bg-blue-700` |
| Set Prequalified | `contractor.management.update` | `is_prequalified = false` | `bg-green-600 text-white hover:bg-green-700` |
| Revoke Prequalified | `contractor.management.update` | `is_prequalified = true` | `bg-red-600 text-white hover:bg-red-700` |

### Inertia Props

```typescript
interface ShowProps {
    contractor: Contractor & {
        company: Company;
        evaluations: ContractorEvaluation[];
        creator: User;
    };
    linkedPermits: {
        data: Permit[];
        total: number;
        active: number;
        expired: number;
        draft: number;
        closed: number;
    };
    linkedIncidents: {
        data: Incident[];
        total: number;
        critical: number;
        major: number;
        minor: number;
    };
    files: ManagedFile[];
    comments: Comment[];
    activities: ActivityLog[];
    safetyScore: {
        rating: string | null;
        average_score: number | null;
        total_evaluations: number;
        latest_evaluation_date: string | null;
    };
    can: {
        update: boolean;
        evaluate: boolean;
        export: boolean;
    };
}
```

---

## 6. Form Evaluasi (Inline di Contractor Show)

### Trigger: Klik tombol [+ Evaluasi] di Tab Evaluasi

### Permission: `contractor.management.evaluate`

### Wireframe — Modal

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│  ┌─ Modal: Evaluasi Kontraktor ───────────────────────────────────────────────┐  │
│  │                                                                          ┐  │  │
│  │  EVALUASI KONTRAKTOR                                          [✕]       │  │  │
│  │  CTR-2026-0001 — PT Karya Konstruksi Sejahtera                           │  │  │
│  │  ────────────────────────────────────────────────────────────────────    │  │  │
│  │                                                                          │  │  │
│  │  Tanggal Evaluasi *   [__/__/____]                                      │  │  │
│  │                                                                          │  │  │
│  │  Kriteria Penilaian:                                                    │  │  │
│  │  ────────────────────────────────────────────────────────────────────    │  │  │
│  │                                                                          │  │  │
│  │  ┌─ Compliance Dokumen ─────────────────────────────────────────────┐   │  │  │
│  │  │  Skor: [__/20]  (0-20)                                            │   │  │  │
│  │  │  Kelengkapan dokumen prequalification, perizinan, sertifikasi    │   │  │  │
│  │  └──────────────────────────────────────────────────────────────────┘   │  │  │
│  │                                                                          │  │  │
│  │  ┌─ Rekam Jejak Keselamatan ────────────────────────────────────────┐   │  │  │
│  │  │  Skor: [__/25]  (0-25)                                            │   │  │  │
│  │  │  Riwayat insiden, near miss, compliance hukum                    │   │  │  │
│  │  └──────────────────────────────────────────────────────────────────┘   │  │  │
│  │                                                                          │  │  │
│  │  ┌─ Kompetensi Personel ────────────────────────────────────────────┐   │  │  │
│  │  │  Skor: [__/20]  (0-20)                                            │   │  │  │
│  │  │  Sertifikasi pekerja, training K3, kompetensi teknis             │   │  │  │
│  │  └──────────────────────────────────────────────────────────────────┘   │  │  │
│  │                                                                          │  │  │
│  │  ┌─ Ketersediaan APD ───────────────────────────────────────────────┐   │  │  │
│  │  │  Skor: [__/15]  (0-15)                                            │   │  │  │
│  │  │  Ketersediaan dan kondisi APD, maintenance APD                   │   │  │  │
│  │  └──────────────────────────────────────────────────────────────────┘   │  │  │
│  │                                                                          │  │  │
│  │  ┌─ Program K3 ─────────────────────────────────────────────────────┐   │  │  │
│  │  │  Skor: [__/20]  (0-20)                                            │   │  │  │
│  │  │  Safety program, toolbox meeting, safety officer                 │   │  │  │
│  │  └──────────────────────────────────────────────────────────────────┘   │  │  │
│  │                                                                          │  │  │
│  │  Total Skor:  [0/100]  (auto-calculated)                                │  │  │
│  │  Result:      [—]  (auto-derived: ≥80 Pass, 60-79 Conditional, <60 Fail)│  │  │
│  │                                                                          │  │  │
│  │  Catatan              ┌──────────────────────────────────────────────┐  │  │  │
│  │  (opsional)           │                                              │  │  │  │
│  │                       │                                              │  │  │  │
│  │                       └──────────────────────────────────────────────┘  │  │  │
│  │                                                                          │  │  │
│  │  ┌─ Action Bar ───────────────────────────────────────────────────────┐ │  │  │
│  │  │  [← Batal]                                        [Simpan Evaluasi] │ │  │  │
│  │  └────────────────────────────────────────────────────────────────────┘ │  │  │
│  └──────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Field

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Tanggal Evaluasi | Date picker | Yes | `required, date, before_or_equal:today` | Tanggal evaluasi dilakukan |
| Compliance Dokumen | Number input | Yes | `required, integer, min:0, max:20` | Skor 0-20 |
| Rekam Jejak Keselamatan | Number input | Yes | `required, integer, min:0, max:25` | Skor 0-25 |
| Kompetensi Personel | Number input | Yes | `required, integer, min:0, max:20` | Skor 0-20 |
| Ketersediaan APD | Number input | Yes | `required, integer, min:0, max:15` | Skor 0-15 |
| Program K3 | Number input | Yes | `required, integer, min:0, max:20` | Skor 0-20 |
| Total Skor | Text (readonly) | — | Auto-calculated sum | 0-100 |
| Result | Text (readonly) | — | Auto-derived from total_score | pass/conditional/fail |
| Catatan | Textarea | No | `nullable, string` | Catatan evaluator |

### Auto-Calculation Behavior

```typescript
// Total skor auto-update saat input berubah
const totalScore = complianceDokumen + rekamJejak + kompetensiPersonel + ketersediaanApd + programK3;

// Result auto-derived
const result = totalScore >= 80 ? 'pass' : totalScore >= 60 ? 'conditional' : 'fail';
```

---

## 7. Mobile Responsive

### Breakpoints

| Breakpoint | Layout |
|---|---|
| Desktop (≥1024px) | Full layout, 2-column detail |
| Tablet (768-1023px) | Stack columns, table horizontal scroll |
| Mobile (<768px) | Single column, cards replace tables |

### Mobile Adaptations

- **Index**: Table berubah menjadi card list. Filter bar collapse ke accordion.
- **Show**: Tab bar berubah menjadi dropdown select. Summary card full-width.
- **Form**: Semua section stack vertikal. Action bar fixed bottom.
- **Evaluasi Modal**: Full-screen modal di mobile. Criteria input stack vertikal.

### Mobile Index Card

```
┌───────────────────────────────────┐
│  CTR-2026-0001             🟢    │
│  PT Karya Konstruksi Sejahtera    │
│  📞 Budi Santoso                  │
│  Konstruksi Sipil                 │
│  Safety: 🟢 Excellent  | 🟢 Active│
│                          [👁] [✏] │
└───────────────────────────────────┘
```

---

## 8. Component List

### Reusable Components

| Component | Path | Description |
|---|---|---|
| `PrequalificationBadge` | `components/ContractorManagement/PrequalificationBadge.tsx` | Badge untuk prequalification status |
| `SafetyRatingBadge` | `components/ContractorManagement/SafetyRatingBadge.tsx` | Badge untuk safety rating |
| `ContractorStatusBadge` | `components/ContractorManagement/ContractorStatusBadge.tsx` | Badge untuk contractor status |
| `EvaluationResultBadge` | `components/ContractorManagement/EvaluationResultBadge.tsx` | Badge untuk evaluation result |
| `EvaluationForm` | `components/ContractorManagement/EvaluationForm.tsx` | Modal form untuk evaluasi |
| `CriteriaScoreBar` | `components/ContractorManagement/CriteriaScoreBar.tsx` | Progress bar untuk skor criteria |
| `PrequalificationForm` | `components/ContractorManagement/PrequalificationForm.tsx` | Inline form untuk set/unset prequalification |
| `SummaryCards` | `components/ContractorManagement/SummaryCards.tsx` | Summary cards di halaman Index |
| `LinkedPermitsTable` | `components/ContractorManagement/LinkedPermitsTable.tsx` | Tabel PTW terkait |
| `LinkedIncidentsTable` | `components/ContractorManagement/LinkedIncidentsTable.tsx` | Tabel insiden terkait |

### Page Components

| Page | Path | Description |
|---|---|---|
| Index | `Pages/Modules/ContractorManagement/Index.tsx` | Daftar kontraktor |
| Form | `Pages/Modules/ContractorManagement/Form.tsx` | Create/Edit kontraktor |
| Show | `Pages/Modules/ContractorManagement/Show.tsx` | Detail kontraktor dengan tabs |
