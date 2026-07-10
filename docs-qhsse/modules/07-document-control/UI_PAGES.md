# UI Pages — Document Control

Spesifikasi wireframe halaman UI untuk modul Document Control.

Semua label menggunakan Bahasa Indonesia. Tech stack: Laravel 12 + Inertia React + TypeScript + Tailwind CSS.

---

## Daftar Isi

1. [Navigasi](#1-navigasi)
2. [Color Coding](#2-color-coding)
3. [Halaman Index — Daftar Dokumen](#3-halaman-index--daftar-dokumen)
4. [Halaman Form — Buat/Edit Dokumen](#4-halaman-form--buatedit-dokumen)
5. [Halaman Show — Detail Dokumen](#5-halaman-show--detail-dokumen)
6. [Mobile Responsive](#6-mobile-responsive)

---

## 1. Navigasi

### Penempatan Menu

Tambahkan item baru pada group `Modul QHSSE` di array `menuGroups` pada `AuthenticatedLayout.tsx`.

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
            { label: 'Dokumen Terkontrol', routeName: 'document.control.index', active: 'document.control.*', permission: 'document.control.view' },  // ← NEW
        ],
    },
    {
        label: 'Masters',
        items: [
            // ... existing Masters items
        ],
    },
];
```

### Wireframe Navigasi (Desktop)

```
┌──────────────────────────────────────────────────────────────────────────┐
│  [Logo] QHSSE   Core ▾   Modul QHSSE ▾   Masters ▾   Admin ▾    [User]  │
│                              ┌────────────────────────┐                   │
│                              │ Laporan Insiden       │                   │
│                              │ Dokumen Terkontrol    │                   │
│                              └────────────────────────┘                   │
└──────────────────────────────────────────────────────────────────────────┘
```

### Wireframe Navigasi (Mobile — Hamburger)

```
┌──────────────────────┐
│  [Logo] QHSSE   [☰]  │
├──────────────────────┤
│  CORE                 │
│   Dashboard           │
│                       │
│  MODUL QHSSE          │
│   Laporan Insiden     │
│   Dokumen Terkontrol  │
│                       │
│  MASTERS              │
│   Departments         │
│   ...                 │
│                       │
│  ADMIN                │
│   ...                 │
├──────────────────────┤
│  John Doe             │
│  Profile    Log Out   │
└──────────────────────┘
```

### Permission Filtering

Menu hanya tampil jika user memiliki permission `document.control.view`. Filtering dilakukan via `auth.permissions` pada layout.

---

## 2. Color Coding

### Type Badge

| Type    | Tailwind Class                              | Preview          |
|---------|---------------------------------------------|------------------|
| SOP     | `bg-indigo-100 text-indigo-800`             | `📋 SOP`         |
| WI      | `bg-cyan-100 text-cyan-800`                 | `📝 WI`          |
| JSA     | `bg-amber-100 text-amber-800`               | `⚠️ JSA`         |
| HIRADC  | `bg-purple-100 text-purple-800`             | `🔍 HIRADC`      |
| MSDS    | `bg-teal-100 text-teal-800`                 | `🧪 MSDS`        |
| Policy  | `bg-blue-100 text-blue-800`                 | `📜 Policy`      |
| Form    | `bg-slate-100 text-slate-800`               | `📄 Form`        |
| Manual  | `bg-orange-100 text-orange-800`             | `📖 Manual`      |
| Other   | `bg-gray-100 text-gray-800`                 | `📎 Other`       |

### Status Badge

| Status    | Tailwind Class                              | Preview          |
|-----------|---------------------------------------------|------------------|
| Draft     | `bg-gray-100 text-gray-800`                 | `⚪ Draft`       |
| Review    | `bg-blue-100 text-blue-800`                 | `🔵 Review`      |
| Approved  | `bg-yellow-100 text-yellow-800`             | `🟡 Approved`    |
| Effective | `bg-green-100 text-green-800`               | `🟢 Effective`   |
| Obsolete  | `bg-red-100 text-red-800`                   | `🔴 Obsolete`    |
| Rejected  | `bg-red-100 text-red-800`                   | `🔴 Rejected`    |

### Confidential Badge

| Status | Tailwind Class | Preview |
|---|---|---|
| Rahasia | `bg-purple-100 text-purple-800` | `🔒 Rahasia` |

### Komponen Badge (Reusable)

```tsx
// Komponen: components/Badge.tsx
type BadgeColor = 'gray' | 'blue' | 'yellow' | 'green' | 'red' | 'orange' | 'indigo' | 'cyan' | 'amber' | 'purple' | 'teal' | 'slate';

function Badge({ label, color }: { label: string; color: BadgeColor }) {
    const colors: Record<BadgeColor, string> = {
        gray:   'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        blue:   'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        green:  'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        red:    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        orange: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        indigo: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
        cyan:   'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200',
        amber:  'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
        purple: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        teal:   'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200',
        slate:  'bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-200',
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

const typeColors: Record<string, BadgeColor> = {
    sop:     'indigo',
    wi:      'cyan',
    jsa:     'amber',
    hiradc:  'purple',
    msds:    'teal',
    policy:  'blue',
    form:    'slate',
    manual:  'orange',
    other:   'gray',
};

const statusColors: Record<string, BadgeColor> = {
    draft:     'gray',
    review:    'blue',
    approved:  'yellow',
    effective: 'green',
    obsolete:  'red',
    rejected:  'red',
};
```

---

## 3. Halaman Index — Daftar Dokumen

### Route: `GET /documents` (`document.control.index`)

### Permission: `document.control.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                       │
│  Dokumen Terkontrol                                    [+ Buat Dokumen]     │
│  Kelola dokumen terkontrol (SOP, WI, JSA, HIRADC, MSDS, dll)                │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari nomor, judul...           ]                                   │  │
│  │                                                                        │  │
│  │ Tipe: [Semua ▾]  Status: [Semua ▾]  Department: [Semua ▾]            │  │
│  │                                                  [Reset]              │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Toolbar ─────────────────────────────────────────────────────────────┐  │
│  │ Menampilkan 1–15 dari 32 dokumen                      [⬇ Export CSV]  │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Table ─────────────────────────────────────────────────────────────────┐ │
│  │ Nomor         Judul                Tipe    Versi  Status     Tgl Berlaku │ │
│  ├─────────────────────────────────────────────────────────────────────────┤ │
│  │ DOC-2026-0001 SOP Penggunaan APD    📋SOP  1.0   🟢Effective 01/08/26  │ │
│  │ DOC-2026-0002 WI Pemeliharaan Mesin 📝WI   2.1   🟡Approved  —          │ │
│  │ DOC-2026-0003 JSA Pengerjaan Tinggi ⚠️JSA  1.0   🔵Review    —          │ │
│  │ DOC-2026-0004 HIRADC Area Produksi  🔍HIRA 1.2   🟢Effective 15/06/26  │ │
│  │ DOC-2026-0005 MSDS Asam Sulfat      🧪MSDS 1.0   🟢Effective 01/01/26  │ │
│  │ DOC-2026-0006 Policy K3             📜Poli 3.0   🔴Obsolete  01/01/25  │ │
│  │ DOC-2026-0007 Form Laporan Insiden  📄Form 1.0   ⚪Draft     —          │ │
│  │ ...                                                                     │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│  ┌─ Table (cont.) ────────────────────────────────────────────────────────┐ │
│  │ ... Tgl Berlaku  Owner         Aksi                                     │ │
│  │ ... 01/08/26    Budi S.        [👁 Lihat] [✏ Edit]                    │ │
│  │ ... —           Sari W.        [👁 Lihat] [✓ Effective]               │ │
│  │ ... —           Andi P.        [👁 Lihat] [✓ Approve] [✗ Reject]      │ │
│  │ ... 15/06/26    Joni K.       [👁 Lihat]                              │ │
│  │ ... 01/01/26    Maya R.       [👁 Lihat]                              │ │
│  │ ... 01/01/25    Budi S.       [👁 Lihat]                              │ │
│  │ ... —           Sari W.       [👁 Lihat] [✏ Edit] [📤 Submit]        │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  ┌─ Pagination ───────────────────────────────────────────────────────────┐  │
│  │                            ‹ Sebelumnya   1  2  3   Berikutnya ›       │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Wireframe — Empty State

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                       │
│  Dokumen Terkontrol                                    [+ Buat Dokumen]     │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari nomor, judul...           ]                                   │  │
│  │ Tipe: [Semua ▾]  Status: [Semua ▾]  Department: [Semua ▾]            │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │                                                                      │   │
│  │                          📄                                          │   │
│  │                                                                      │   │
│  │                   Belum ada dokumen terkontrol                      │   │
│  │                                                                      │   │
│  │         Belum ada dokumen yang dibuat. Klik tombol di bawah          │   │
│  │           untuk membuat dokumen terkontrol pertama.                  │   │
│  │                                                                      │   │
│  │                   [+ Buat Dokumen Pertama]                          │   │
│  │                                                                      │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Element

#### Header

| Element | Type | Detail |
|---|---|---|
| Title | `<h1>` | "Dokumen Terkontrol" |
| Subtitle | `<p>` | "Kelola dokumen terkontrol (SOP, WI, JSA, HIRADC, MSDS, dll)" |
| Button "Buat Dokumen" | `<Link>` | Route: `document.control.create`, permission: `document.control.create` |
| Button Style | Tailwind | `bg-blue-600 text-white hover:bg-blue-700` |

#### Search Box

| Element | Type | Detail |
|---|---|---|
| Placeholder | `<input>` | "Cari nomor, judul..." |
| Icon | SVG | Magnifying glass icon di kiri input |
| Behavior | debounce | 300ms debounce, kirim ke server via Inertia visit |
| Param | query | `?search=keyword` |

#### Filter Dropdowns

| Filter | Label | Options | Param |
|---|---|---|---|
| Tipe | "Tipe" | Semua, SOP, WI, JSA, HIRADC, MSDS, Policy, Form, Manual, Other | `?type=` |
| Status | "Status" | Semua, Draft, Review, Approved, Effective, Obsolete, Rejected | `?status=` |
| Department | "Department" | Semua + dari master Departments | `?department_id=` |
| Reset Button | Button | "Reset" — clear all filters | — |

#### Table Columns

| # | Column | Key | Width | Align | Badge? | Detail |
|---|---|---|---|---|---|---|
| 1 | Nomor | `document_number` | 130px | left | No | Link ke show page, monospace font |
| 2 | Judul | `title` | flex | left | No | Truncate dengan `max-w-xs truncate` |
| 3 | Tipe | `type` | 100px | center | Yes | Lihat [Color Coding](#2-color-coding) |
| 4 | Versi | `version` | 80px | center | No | |
| 5 | Status | `status` | 120px | center | Yes | Lihat [Color Coding](#2-color-coding) |
| 6 | Tgl Berlaku | `effective_date` | 110px | center | No | Format: `dd/mm/yy` |
| 7 | Owner | `owner.name` | 130px | left | No | Nama user |
| 8 | Aksi | — | 180px | center | No | Lihat di bawah |

#### Aksi Column (per row)

| Action | Icon | Permission | Condition |
|---|---|---|---|
| Lihat | 👁 | `document.control.view` | Selalu tampil |
| Edit | ✏ | `document.control.update` | Status = Draft atau Rejected |
| Submit | 📤 | `document.control.submit_review` | Status = Draft |
| Approve | ✓ | `document.control.approve` | Status = Review |
| Reject | ✗ | `document.control.approve` | Status = Review |
| Effective | ✓ | `document.control.make_effective` | Status = Approved |
| Obsolete | 🗑 | `document.control.obsolete` | Status = Effective |

#### Pagination

```
Menampilkan 1–15 dari 32 dokumen

‹ Sebelumnya   1  2  3   Berikutnya ›
```

- Menggunakan komponen Tailwind pagination standar.
- 15 item per halaman (dapat di-configurable: 15/25/50).

#### Export CSV

| Element | Detail |
|---|---|
| Button | `[⬇ Export CSV]` |
| Permission | `document.control.export` |
| Behavior | Export data sesuai filter aktif saat ini |
| Endpoint | `GET /documents/export?type=...&status=...&...` |

### Inertia Props

```typescript
interface IndexProps {
    items: {
        data: ControlledDocument[];
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
        department_id?: number;
    };
    departments: Department[];
    can: {
        create: boolean;
        export: boolean;
    };
}
```

---

## 4. Halaman Form — Buat/Edit Dokumen

### Route

- Create: `GET /documents/create` (`document.control.create`)
- Edit: `GET /documents/{id}/edit` (`document.control.edit`)

### Permission

- Create: `document.control.create`
- Edit: `document.control.update` (hanya jika status = Draft atau Rejected)

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Buat Dokumen Terkontrol                                                         │
│  Isi data dokumen terkontrol dengan lengkap                                     │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Dokumen ──────────────────────────────────────────────┐  │
│  │  INFORMASI DOKUMEN                                                         │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Nomor Dokumen        [Auto-generated — DOC-2026-0008        ]  ⓘ         │  │
│  │                       Nomor akan dibuat otomatis saat simpan                 │  │
│  │                                                                             │  │
│  │  Judul *              [Masukkan judul dokumen...              ]              │  │
│  │                                                                             │  │
│  │  Tipe *               [— Pilih Tipe —           ▾]                         │  │
│  │                        ○ SOP   ○ WI    ○ JSA   ○ HIRADC                   │  │
│  │                        ○ MSDS  ○ Policy ○ Form ○ Manual                    │  │
│  │                        ○ Other                                             │  │
│  │                                                                             │  │
│  │  Versi *              [1.0          ]                                        │  │
│  │                        Format bebas, contoh: 1.0, 1.1, 2.0                   │  │
│  │                                                                             │  │
│  │  Catatan Revisi       ┌──────────────────────────────────────────────┐     │  │
│  │  (Revision Notes)     │ Catatan perubahan pada versi ini...           │     │  │
│  │                        │                                              │     │  │
│  │                        └──────────────────────────────────────────────┘     │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Tanggal Penting ───────────────────────────────────────────────┐  │
│  │  TANGGAL PENTING                                                           │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Tanggal Berlaku      [__/__/____] [📅]                                    │  │
│  │  (Effective Date)      Tanggal dokumen berlaku efektif                       │  │
│  │                                                                             │  │
│  │  Tanggal Review       [__/__/____] [📅]                                    │  │
│  │  (Review Date)         Tanggal review berikutnya (untuk pengingat)           │  │
│  │                                                                             │  │
│  │  Tanggal Kadaluarsa   [__/__/____] [📅]                                    │  │
│  │  (Expiry Date)         Tanggal dokumen kadaluarsa                            │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Penanggung Jawab ─────────────────────────────────────────────┐  │
│  │  PENANGGUNG JAWAB                                                          │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Department           [— Pilih Department —    ▾]                          │  │
│  │                                                                             │  │
│  │  Owner *              [— Cari user... —    ▾]                               │  │
│  │                        Pemilik dokumen (default: user yang login)            │  │
│  │                                                                             │  │
│  │  ☐ Dokumen Rahasia    (is_confidential)                                    │  │
│  │                        Centang jika dokumen rahasia — download dibatasi      │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Upload File ───────────────────────────────────────────────────┐  │
│  │  UPLOAD FILE DOKUMEN                                                       │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  ┌─────────────────────────────────────────────────────────────────────┐   │  │
│  │  │                                                                     │   │  │
│  │  │              📁  Drag & drop file di sini                           │   │  │
│  │  │                  atau [Pilih File]                                  │   │  │
│  │  │                                                                     │   │  │
│  │  │              Maks 50MB per file. Format: pdf, docx, xlsx            │   │  │
│  │  │                                                                     │   │  │
│  │  └─────────────────────────────────────────────────────────────────────┘   │  │
│  │                                                                             │  │
│  │  File terunggah:                                                           │  │
│  │  ┌──────────────────────────────────────────────────────────────────┐     │  │
│  │  │ 📎 SOP_Penggunaan_APD_v1.0.pdf                      1.2 MB   [🗑] │     │  │
│  │  └──────────────────────────────────────────────────────────────────┘     │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Action Bar (sticky bottom) ──────────────────────────────────────────────┐  │
│  │                                                                           │  │
│  │  [← Batal]                              [Simpan Draft]  [Submit Review]  │  │
│  │                                                         (primary)       │  │
│  └───────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Section

#### Section: Informasi Dokumen

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Nomor Dokumen | Text (readonly) | No | — | Auto-generated saat create. Placeholder "Auto-generated" |
| Judul | Text input | Yes | `required, min:5, max:255` | Placeholder: "Masukkan judul dokumen..." |
| Tipe | Select dropdown | Yes | `required, in:sop,wi,jsa,hiradc,msds,policy,form,manual,other` | 9 options |
| Versi | Text input | Yes | `required, max:20` | Format bebas, contoh: 1.0, 1.1, 2.0 |
| Catatan Revisi | Textarea | No | `nullable, max:2000` | Catatan perubahan pada versi ini |

#### Section: Tanggal Penting

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Tanggal Berlaku | Date picker | No | `nullable, date` | Tanggal dokumen berlaku efektif |
| Tanggal Review | Date picker | No | `nullable, date, after_or_equal:today` | Tanggal review berikutnya |
| Tanggal Kadaluarsa | Date picker | No | `nullable, date, after:review_date` | Tanggal kadaluarsa |

#### Section: Penanggung Jawab

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Department | Select dropdown | No | `nullable, exists:departments,id` | Dari master Departments |
| Owner | Select (search) | Yes | `required, exists:users,id` | Default: user yang login |
| Dokumen Rahasia | Checkbox | No | `boolean` | Jika centang, download dibatasi |

#### Section: Upload File

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| File | File upload | No (draft) / Yes (submit_review) | `nullable, max:51200kb, mimes:pdf,doc,docx,xls,xlsx,ppt,pptx` | Drag & drop atau klik |

Accepted formats: `pdf, doc, docx, xls, xlsx, ppt, pptx`
Maksimal: 10 file, 50MB per file
Uploaded files tampil dalam list dengan: icon, filename, size, delete button

### Action Buttons

| Button | Type | Style | Behavior |
|---|---|---|---|
| Batal | Link | `text-slate-600 hover:text-slate-900` | Redirect ke index page |
| Simpan Draft | Submit | `bg-gray-200 text-gray-800 hover:bg-gray-300` | `POST`/`PUT` dengan `action=draft`. Tidak validasi field mandatory |
| Submit Review | Submit | `bg-blue-600 text-white hover:bg-blue-700` | `POST`/`PUT` dengan `action=submit_review`. Validasi semua field mandatory |

### Edit Mode Notes

- Saat edit (status = Draft atau Rejected), nomor dokumen tampil sebagai readonly
- Section dan field sama dengan create mode
- Jika status = Rejected, tampilkan alert dengan alasan reject dari review terakhir

### Inertia Props

```typescript
interface FormProps {
    item: ControlledDocument | null;   // null untuk create, filled untuk edit
    departments: Department[];
    users: User[];
    documentTypes: { value: string; label: string }[];
    can: {
        submit_review: boolean;
    };
}
```

---

## 5. Halaman Show — Detail Dokumen

### Route: `GET /documents/{id}` (`document.control.show`)

### Permission: `document.control.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  ← Kembali ke Daftar                                                             │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Summary Card ──────────────────────────────────────────────────────────┐    │
│  │                                                                         │    │
│  │  DOC-2026-0001                  [📋 SOP] [🟢 Effective] [v1.0]       │    │
│  │  SOP Penggunaan APD di Area Produksi                                   │    │
│  │                                                                         │    │
│  │  📅 Tanggal Berlaku: 01/08/2026                                        │    │
│  │  📅 Tanggal Review: 01/08/2027    📅 Kadaluarsa: —                      │    │
│  │  🏢 Department: Produksi              👤 Owner: Budi Santoso           │    │
│  │  ✓ Approver: Sari W. (QHSSE Manager)   🔒 Rahasia: Tidak               │    │
│  │                                                                         │    │
│  │  ┌─ Action Buttons (permission-gated) ──────────────────────────────┐ │    │
│  │  │  [✏ Edit]  [📤 Submit Review]  [✓ Approve]  [✗ Reject]          │ │    │
│  │  │  [✓ Make Effective]  [🗑 Obsolete]  [🔄 Revise]                  │ │    │
│  │  └───────────────────────────────────────────────────────────────────┘ │    │
│  └─────────────────────────────────────────────────────────────────────────┘    │
│                                                                                  │
│  ┌─ Detail Layout: 2 columns ──────────────────────────────────────────────┐   │
│  │                                                                         │   │
│  │  ┌─ Left Column (2/3) ─────────────────────────────┐  ┌─ Right Column (1/3) ──────────────┐ │   │
│  │  │                                                 │  │                                   │ │   │
│  │  │  CATATAN REVISI                                 │  │  ┌─ INFO DOKUMEN ──────────────┐ │ │   │
│  │  │  ─────────────────────────────────────────      │  │  │ Nomor:    DOC-2026-0001    │ │ │   │
│  │  │  Pembuatan SOP baru untuk area produksi.         │  │  │ Tipe:     SOP              │ │ │   │
│  │  │  Mencakup prosedur penggunaan APD lengkap        │  │  │ Versi:    1.0              │ │ │   │
│  │  │  (helm, sepatu safety, sarung tangan, dll).      │  │  │ Status:   Effective        │ │ │   │
│  │  │                                                 │  │  └─────────────────────────────┘ │ │   │
│  │  │  ┌─ FILE DOKUMEN ─────────────────────────────┐  │  │                                   │ │   │
│  │  │  │                                              │  │  ┌─ TANGGAL ───────────────────┐ │ │   │
│  │  │  │  📎 SOP_Penggunaan_APD_v1.0.pdf              │  │  │ Berlaku:  01/08/2026        │ │ │   │
│  │  │  │     1.2 MB   [⬇ Download]                   │  │  │ Review:   01/08/2027        │ │ │   │
│  │  │  │                                              │  │  │ Kadaluarsa: —               │ │ │   │
│  │  │  └──────────────────────────────────────────────┘  │  └─────────────────────────────┘ │ │   │
│  │  │                                                 │  │                                   │ │   │
│  │  └─────────────────────────────────────────────────┘  └───────────────────────────────────┘ │   │
│  │                                                                         │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Version History (document_reviews) ────────────────────────────────────┐   │
│  │  RIWAYAT VERSI & REVIEW                                                  │   │
│  │  ─────────────────────────────────────────────────────────────────────    │   │
│  │                                                                           │   │
│  │  ●━━━ v1.0 — Submit Review         11/07/2026 10:00   Budi Santoso       │   │
│  │  │     Catatan: Dokumen siap untuk review. Mohon ditinjau.               │   │
│  │  │     Decision: Pending → Approved                                       │   │
│  │  │                                                                        │   │
│  │  ●━━━ v1.0 — Approved              11/07/2026 14:30   Sari W. (Manager)  │   │
│  │  │     Catatan: Dokumen sudah memenuhi standar. Disetujui.                │   │
│  │  │     Decision: Approve                                                  │   │
│  │  │                                                                        │   │
│  │  ●━━━ v1.0 — Made Effective        11/07/2026 15:00   Sari W. (Manager)  │   │
│  │        Effective Date: 01/08/2026                                         │   │
│  │                                                                           │   │
│  └───────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Workflow Timeline ─────────────────────────────────────────────────────┐   │
│  │  RIWAYAT WORKFLOW                                                        │   │
│  │  ─────────────────────────────────────────────────────────────────────    │   │
│  │                                                                           │   │
│  │  ●━━━ Draft                     11/07/2026 09:00   Budi Santoso        │   │
│  │  │     Dokumen dibuat sebagai draft                                       │   │
│  │  │                                                                        │   │
│  │  ●━━━ Review                    11/07/2026 10:00   Budi Santoso        │   │
│  │  │     Dokumen di-submit untuk review                                     │   │
│  │  │                                                                        │   │
│  │  ●━━━ Approved                  11/07/2026 14:30   Sari W.             │   │
│  │  │     Dokumen disetujui                                                  │   │
│  │  │                                                                        │   │
│  │  ●━━━ Effective                 11/07/2026 15:00   Sari W.             │   │
│  │  │     Dokumen berlaku efektif sejak 01/08/2026                           │   │
│  │  │                                                                        │   │
│  │  ○━━━ (Menunggu) Obsolete                                                 │   │
│  │                                                                           │   │
│  └───────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Comments Section ──────────────────────────────────────────────────────┐   │
│  │  KOMENTAR (2)                                                            │   │
│  │  ─────────────────────────────────────────────────────────────────────    │   │
│  │                                                                           │   │
│  │  ┌─ Comment 1 ───────────────────────────────────────────────────────┐    │   │
│  │  │ 👤 Sari W. (QHSSE Manager)                           11/07 14:00  │    │   │
│  │  │ Mohon tambahkan prosedur untuk area warehouse.                     │    │   │
│  │  └────────────────────────────────────────────────────────────────────┘    │   │
│  │                                                                           │   │
│  │  ┌─ Comment 2 ───────────────────────────────────────────────────────┐    │   │
│  │  │ 👤 Budi S. (Owner)                                   11/07 14:15   │    │   │
│  │  │ Sudah ditambahkan, mohon dicek.                                     │    │   │
│  │  └────────────────────────────────────────────────────────────────────┘    │   │
│  │                                                                           │   │
│  │  ┌─ Add Comment ─────────────────────────────────────────────────────┐    │   │
│  │  │ [Tulis komentar...                                    ]  [Kirim]  │    │   │
│  │  └────────────────────────────────────────────────────────────────────┘    │   │
│  │                                                                           │   │
│  └───────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
│  ┌─ Activity Log ─────────────────────────────────────────────────────────┐   │
│  │  LOG AKTIVITAS                                                          │   │
│  │  ─────────────────────────────────────────────────────────────────────    │   │
│  │                                                                           │   │
│  │  📝 11/07/2026 09:00  Budi Santoso  Membuat dokumen (draft)              │   │
│  │  📤 11/07/2026 10:00  Budi Santoso  Submit dokumen untuk review          │   │
│  │  ✓  11/07/2026 14:30  Sari W.        Menyetujui dokumen                  │   │
│  │  ✓  11/07/2026 15:00  Sari W.        Menerapkan dokumen efektif           │   │
│  │  📎 11/07/2026 09:30  Budi Santoso  Upload file: SOP_Penggunaan_APD.pdf  │   │
│  │                                                                           │   │
│  └───────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Section

#### Summary Card

| Element | Type | Detail |
|---|---|---|
| Document Number | `<span>` | Monospace font, bold |
| Title | `<h2>` | Document title |
| Type Badge | Badge | Sesuai type (lihat Color Coding) |
| Status Badge | Badge | Sesuai status (lihat Color Coding) |
| Version | `<span>` | "v{version}" |
| Effective Date | `<p>` | "📅 Tanggal Berlaku: {date}" |
| Review Date | `<p>` | "📅 Tanggal Review: {date}" |
| Expiry Date | `<p>` | "📅 Kadaluarsa: {date}" |
| Department | `<p>` | "🏢 Department: {name}" |
| Owner | `<p>` | "👤 Owner: {name}" |
| Approver | `<p>` | "✓ Approver: {name}" (nullable) |
| Confidential | `<p>` | "🔒 Rahasia: Ya/Tidak" |

#### Action Buttons (permission-gated)

| Action | Permission | Condition | Style |
|---|---|---|---|
| Edit | `document.control.update` | Status = Draft/Rejected | `bg-gray-200` |
| Submit Review | `document.control.submit_review` | Status = Draft | `bg-blue-600` |
| Approve | `document.control.approve` | Status = Review | `bg-green-600` |
| Reject | `document.control.approve` | Status = Review | `bg-red-600` |
| Make Effective | `document.control.make_effective` | Status = Approved | `bg-green-600` |
| Obsolete | `document.control.obsolete` | Status = Effective | `bg-red-600` |
| Revise | `document.control.update` | Status = Rejected | `bg-yellow-600` |

#### Reject/Obsolete Modal

Saat user klik Reject atau Obsolete, tampilkan modal:

```
┌──────────────────────────────────────────────────┐
│  Tolak Dokumen                                   │
├──────────────────────────────────────────────────┤
│                                                  │
│  Alasan *                                        │
│  ┌────────────────────────────────────────────┐  │
│  │ Jelaskan alasan penolakan...               │  │
│  │                                            │  │
│  │                                            │  │
│  └────────────────────────────────────────────┘  │
│  Minimal 10 karakter                            │
│                                                  │
│                    [Batal]  [Tolak Dokumen]     │
│                                                  │
└──────────────────────────────────────────────────┘
```

#### Version History Section

Menampilkan semua `document_reviews` record untuk dokumen ini:

| Column | Detail |
|---|---|
| Version + Action | "v{version} — {Action}" |
| Timestamp | Format: `dd/mm/yyyy HH:MM` |
| Actor | Nama user |
| Notes | `review_notes` |
| Decision | Badge: Pending (gray), Approved (green), Rejected (red), Revised (yellow) |

#### File Download

| Element | Detail |
|---|---|
| File icon | Sesuai extension (📄 for pdf, 📊 for xlsx, etc.) |
| Filename | `original_name` |
| Size | Formatted (KB/MB) |
| Download button | `[⬇ Download]` — hanya tampil jika `can.download_file = true` |
| Download endpoint | `GET /core/files/{managedFile}/download` |

#### Workflow Timeline

Menampilkan `workflow_histories` untuk dokumen ini:

```
●━━━ Draft                     11/07/2026 09:00   Budi Santoso
│     Dokumen dibuat sebagai draft
│
●━━━ Review                    11/07/2026 10:00   Budi Santoso
│     Dokumen di-submit untuk review
│
●━━━ Approved                  11/07/2026 14:30   Sari W.
│     Dokumen disetujui
│
●━━━ Effective                 11/07/2026 15:00   Sari W.
│     Dokumen berlaku efektif sejak 01/08/2026
│
○━━━ (Menunggu) Obsolete
```

### Inertia Props

```typescript
interface ShowProps {
    document: ControlledDocument & {
        department: Department | null;
        owner: User;
        approver: User | null;
    };
    files: ManagedFile[];
    reviews: (DocumentReview & {
        reviewer: User | null;
    })[];
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
        submit_review: boolean;
        approve: boolean;
        make_effective: boolean;
        obsolete: boolean;
        revise: boolean;
        download_file: boolean;
    };
}
```

---

## 6. Mobile Responsive

### Breakpoints

- Desktop: full layout (2-3 columns)
- Tablet (md): collapsed sections, single column for forms
- Mobile (sm): stacked layout, simplified table

### Mobile Index

```
┌──────────────────────┐
│  Dokumen Terkontrol  │
│  [+ Buat]  [⬇ Export]│
├──────────────────────┤
│ [🔍 Cari...        ] │
│ Tipe: [Semua ▾]      │
│ Status: [Semua ▾]    │
├──────────────────────┤
│ ┌──────────────────┐ │
│ │ DOC-2026-0001    │ │
│ │ SOP Penggunaan   │ │
│ │ [📋SOP] [🟢Eff]  │ │
│ │ v1.0  01/08/26   │ │
│ │ Budi S.  [👁]    │ │
│ └──────────────────┘ │
│ ┌──────────────────┐ │
│ │ DOC-2026-0002    │ │
│ │ WI Pemeliharaan  │ │
│ │ [📝WI] [🟡Appr]  │ │
│ │ v2.1  —          │ │
│ │ Sari W.  [👁]    │ │
│ └──────────────────┘ │
├──────────────────────┤
│ ‹ 1  2  3  ›        │
└──────────────────────┘
```

### Mobile Form

- Semua section di-stack vertikal
- File upload area full width
- Action bar fixed di bottom

### Mobile Show

- Summary card di atas
- Action buttons dalam horizontal scroll atau grid 2 kolom
- Tabs untuk: Detail, Version History, Workflow, Comments, Activity

### Component List

| Component | File | Description |
|---|---|---|
| `DocumentIndex` | `Pages/Modules/Document/Index.tsx` | List page dengan filter, search, table, pagination |
| `DocumentForm` | `Pages/Modules/Document/Form.tsx` | Create/Edit form dengan file upload |
| `DocumentShow` | `Pages/Modules/Document/Show.tsx` | Detail page dengan version history, workflow, comments |
| `DocumentTypeBadge` | `components/Document/TypeBadge.tsx` | Badge untuk type dokumen |
| `DocumentStatusBadge` | `components/Document/StatusBadge.tsx` | Badge untuk status dokumen |
| `VersionHistory` | `components/Document/VersionHistory.tsx` | Timeline version history dari document_reviews |
| `RejectModal` | `components/Document/RejectModal.tsx` | Modal untuk input reject reason |
| `ObsoleteModal` | `components/Document/ObsoleteModal.tsx` | Modal untuk input obsolete reason |
| `FileUploader` | `components/File/FileUploader.tsx` | Drag & drop file upload (reusable) |
