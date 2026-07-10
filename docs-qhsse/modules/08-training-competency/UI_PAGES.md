# UI Pages — Training & Competency

Spesifikasi wireframe halaman UI untuk modul Training & Competency.

Semua label menggunakan Bahasa Indonesia. Tech stack: Laravel 12 + Inertia React + TypeScript + Tailwind CSS.

---

## Daftar Isi

1. [Navigasi](#1-navigasi)
2. [Color Coding](#2-color-coding)
3. [Halaman Program Index — Daftar Program Pelatihan](#3-halaman-program-index)
4. [Halaman Program Form — Buat/Edit Program](#4-halaman-program-form)
5. [Halaman Record Index — Daftar Record Pelatihan](#5-halaman-record-index)
6. [Halaman Record Form — Buat/Edit Record](#6-halaman-record-form)
7. [Halaman Record Show — Detail Record](#7-halaman-record-show)
8. [Halaman Training Matrix](#8-halaman-training-matrix)
9. [Mobile Responsive](#9-mobile-responsive)

---

## 1. Navigasi

### Penempatan Menu

Tambahkan item menu pada group `Modul QHSSE` di `AuthenticatedLayout.tsx`:

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
            // ... existing module items (Incident, CAPA, etc.)
            { label: 'Program Pelatihan', routeName: 'training.programs.index', active: 'training.programs.*', permission: 'training.programs.view' },
            { label: 'Record Pelatihan', routeName: 'training.records.index', active: 'training.records.*', permission: 'training.records.view' },
            { label: 'Matriks Kompetensi', routeName: 'training.matrix.index', active: 'training.matrix.*', permission: 'training.records.view' },
        ],
    },
    {
        label: 'Masters',
        // ... existing Masters items
    },
];
```

### Wireframe Navigasi (Desktop)

```
┌──────────────────────────────────────────────────────────────────────┐
│  [Logo] QHSSE   Core ▾   Modul QHSSE ▾   Masters ▾   Admin ▾  [User]│
│                        ┌──────────────────────────┐                 │
│                        │ Laporan Insiden           │                 │
│                        │ Program Pelatihan    ◄──  │                 │
│                        │ Record Pelatihan     ◄──  │                 │
│                        │ Matriks Kompetensi   ◄──  │                 │
│                        └──────────────────────────┘                 │
└──────────────────────────────────────────────────────────────────────┘
```

### Permission Filtering

Menu hanya tampil jika user memiliki permission yang sesuai. Filtering dilakukan via `auth.permissions` pada layout.

---

## 2. Color Coding

### Status Badge (Training Records)

| Status | Tailwind Class | Preview |
|---|---|---|
| Scheduled | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | `🔵 Scheduled` |
| In Progress | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 In Progress` |
| Completed | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Completed` |
| Expired | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Expired` |
| Cancelled | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | `⚪ Cancelled` |

### Result Badge

| Result | Tailwind Class | Preview |
|---|---|---|
| Pass | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `✅ Pass` |
| Fail | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `❌ Fail` |
| Pending | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `⏳ Pending` |

### Expiry Row Highlight (CRITICAL)

Record dengan `expiry_date < now()` dan `status = 'expired'` disorot dengan warna **MERAH** di tabel:

| Condition | Tailwind Class (row) | Preview |
|---|---|---|
| Expired | `bg-red-50 dark:bg-red-900/20` + `border-l-4 border-red-500` | Row with left red border |
| Expiring ≤ 30 days | `bg-orange-50 dark:bg-orange-900/20` + `border-l-4 border-orange-500` | Row with left orange border |

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
// utils/trainingBadgeColors.ts

const statusColors: Record<string, BadgeColor> = {
    scheduled:   'blue',
    in_progress: 'yellow',
    completed:   'green',
    expired:     'red',
    cancelled:   'gray',
};

const resultColors: Record<string, BadgeColor> = {
    pass:    'green',
    fail:    'red',
    pending: 'yellow',
};
```

---

## 3. Halaman Program Index

### Route: `GET /training-programs` (`training.programs.index`)

### Permission: `training.programs.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                       │
│  Program Pelatihan                                     [+ Buat Program]    │
│  Kelola program pelatihan dan sertifikasi QHSSE                             │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari kode, nama program...         ]                               │  │
│  │                                                                        │  │
│  │ Kategori: [Semua ▾]   Status: [Semua ▾]   [Reset]                      │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Toolbar ─────────────────────────────────────────────────────────────┐  │
│  │ Menampilkan 1–10 dari 23 program                                        │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Table ─────────────────────────────────────────────────────────────────┐ │
│  │ Kode       Nama Program                Kategori    Durasi  Sert.  Aktif │ │
│  ├─────────────────────────────────────────────────────────────────────────┤ │
│  │ HSE-IND   HSE Induction                 Safety       8 jam   Ya    ✅  │ │
│  │ FIRE-01   Fire Safety Training          Safety      16 jam   Ya    ✅  │ │
│  │ FORK-01   Forklift Operation            Technical   24 jam   Ya    ✅  │ │
│  │ ISO-01    ISO 9001 Awareness             Compliance  4 jam   Tidak  ✅  │ │
│  │ CPR-01    CPR & First Aid               First Aid  16 jam   Ya    ❌  │ │
│  │ ...                                                                     │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│  │ ... Aktif   Aksi                                                        │ │
│  │ ... ✅     [👁 Lihat] [✏ Edit]                                         │ │
│  │ ... ✅     [👁 Lihat] [✏ Edit]                                         │ │
│  │ ... ✅     [👁 Lihat] [✏ Edit]                                         │ │
│  │ ... ✅     [👁 Lihat] [✏ Edit]                                         │ │
│  │ ... ❌     [👁 Lihat] [✏ Edit] [↻ Aktifkan]                            │ │
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
│                                                                              │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │                                                                      │   │
│  │                          📚                                          │   │
│  │                                                                      │   │
│  │                   Belum ada program pelatihan                        │   │
│  │                                                                      │   │
│  │           Belum ada program yang dibuat. Klik tombol di bawah         │   │
│  │             untuk membuat program pelatihan pertama.                  │   │
│  │                                                                      │   │
│  │                      [+ Buat Program Pertama]                        │   │
│  │                                                                      │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Element

#### Header

| Element | Type | Detail |
|---|---|---|
| Title | `<h1>` | "Program Pelatihan" |
| Subtitle | `<p>` | "Kelola program pelatihan dan sertifikasi QHSSE" |
| Button "Buat Program" | `<Link>` | Route: `training.programs.create`, permission: `training.programs.create` |
| Button Style | Tailwind | `bg-blue-600 text-white hover:bg-blue-700` |

#### Filter Dropdowns

| Filter | Label | Options | Param |
|---|---|---|---|
| Search | "Cari kode, nama program..." | Free text | `?search=` |
| Kategori | "Kategori" | Semua, Safety, Technical, Compliance, Soft Skill, Environment, Security, Quality, First Aid | `?category=` |
| Status | "Status" | Semua, Aktif, Non-aktif | `?is_active=` |

#### Table Columns

| # | Column | Key | Width | Align | Badge? | Detail |
|---|---|---|---|---|---|---|
| 1 | Kode | `code` | 100px | left | No | Link ke show page, monospace |
| 2 | Nama Program | `name` | flex | left | No | Truncate dengan `max-w-xs truncate` |
| 3 | Kategori | `category` | 130px | left | Yes | `bg-indigo-100 text-indigo-800` |
| 4 | Durasi | `duration_hours` | 90px | center | No | Format: `{n} jam` |
| 5 | Sertifikasi | `is_certification` | 80px | center | Yes | `bg-green-100 text-green-800` if true |
| 6 | Masa Berlaku | `validity_months` | 100px | center | No | Format: `{n} bulan` or `—` |
| 7 | Aktif | `is_active` | 70px | center | Yes | ✅ / ❌ |
| 8 | Aksi | — | 130px | center | No | See below |

#### Aksi Column (per row)

| Action | Icon | Permission | Condition |
|---|---|---|---|
| Lihat | 👁 | `training.programs.view` | Selalu tampil |
| Edit | ✏ | `training.programs.update` | Selalu tampil |
| Aktifkan/Nonaktifkan | ↻ | `training.programs.update` | Toggle `is_active` |

---

## 4. Halaman Program Form

### Route

- Create: `GET /training-programs/create` (`training.programs.create`)
- Edit: `GET /training-programs/{program}/edit` (`training.programs.edit`)

### Permission

- Create: `training.programs.create`
- Edit: `training.programs.update`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Buat Program Pelatihan                                                          │
│  Tambahkan program pelatihan baru ke sistem                                     │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Program ──────────────────────────────────────────────┐  │
│  │  INFORMASI PROGRAM                                                         │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Kode Program *       [HSE-IND                              ]                │  │
│  │                        Kode unik program (max 50 karakter)                   │  │
│  │                                                                             │  │
│  │  Nama Program *       [HSE Induction                        ]              │  │
│  │                        Nama program pelatihan                               │  │
│  │                                                                             │  │
│  │  Kategori *           [— Pilih Kategori —           ▾]                    │  │
│  │                        ○ Safety ○ Technical ○ Compliance ○ Soft Skill     │  │
│  │                        ○ Environment ○ Security ○ Quality ○ First Aid    │  │
│  │                                                                             │  │
│  │  Durasi (jam) *       [8      ]                                             │  │
│  │                        Jumlah jam pelatihan                                  │  │
│  │                                                                             │  │
│  │  Deskripsi            ┌──────────────────────────────────────────────┐     │  │
│  │                        │ Deskripsi program pelatihan...               │     │  │
│  │                        │                                              │     │  │
│  │                        └──────────────────────────────────────────────┘     │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Sertifikasi ─────────────────────────────────────────────────────┐  │
│  │  SERTIFIKASI                                                               │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Program Sertifikasi   [☐] Ya, program ini menerbitkan sertifikat          │  │
│  │                                                                             │  │
│  │  Masa Berlaku (bulan)  [12     ]  (hanya jika sertifikasi = true)          │  │
│  │                        Jumlah bulan masa berlaku sertifikat. Kosongkan      │  │
│  │                        jika tidak ada masa berlaku.                         │  │
│  │                                                                             │  │
│  │  Status Aktif          [☑] Program aktif                                  │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Action Bar (sticky bottom) ──────────────────────────────────────────────┐  │
│  │                                                                           │  │
│  │  [← Batal]                                           [Simpan Program]    │  │
│  │                                                      (primary)            │  │
│  └───────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Form Fields

| # | Field | Type | Validation | Label (ID) | Notes |
|---|---|---|---|---|---|
| 1 | `code` | text input | required, max:50, unique | "Kode Program" | Monospace font |
| 2 | `name` | text input | required, max:255 | "Nama Program" | |
| 3 | `category` | select dropdown | required, in:list | "Kategori" | 8 options |
| 4 | `duration_hours` | number input | required, integer, min:1 | "Durasi (jam)" | |
| 5 | `description` | textarea | nullable, text | "Deskripsi" | |
| 6 | `is_certification` | checkbox | boolean | "Program Sertifikasi" | Toggles validity field |
| 7 | `validity_months` | number input | nullable, integer, min:1 | "Masa Berlaku (bulan)" | Only shown if `is_certification` checked |
| 8 | `is_active` | checkbox | boolean, default true | "Status Aktif" | |

---

## 5. Halaman Record Index

### Route: `GET /training-records` (`training.records.index`)

### Permission: `training.records.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                               │
│  Record Pelatihan                                          [+ Buat Record]          │
│  Daftar pelatihan karyawan dengan pelacakan kedaluwarsa sertifikat                  │
├──────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                      │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari nomor, nama karyawan...           ]                                   │  │
│  │                                                                                │  │
│  │ Program: [Semua ▾]   Karyawan: [Semua ▾]   Status: [Semua ▾]                  │  │
│  │ Site:    [Semua ▾]   Dept:     [Semua ▾]   [Reset]                            │  │
│  └────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                      │
│  ┌─ Toolbar ─────────────────────────────────────────────────────────────────────┐  │
│  │ Menampilkan 1–15 dari 82 record                          [⬇ Export CSV]      │  │
│  └────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                      │
│  ┌─ Table ─────────────────────────────────────────────────────────────────────────┐ │
│  │ Nomor         Karyawan       Program          Status      Hasil   Kedaluwarsa  │ │
│  ├─────────────────────────────────────────────────────────────────────────────────┤ │
│  │ TRN-2026-0001 Budi Santoso   HSE Induction    🟢Completed  ✅Pass  12/07/2026  │ │
│  ├─────────────────────────────────────────────────────────────────────────────────┤ │
│  │ TRN-2026-0002 Sari Wulandari  Fire Safety      🔴Expired    ⚠ —    01/07/2026  │ │ ← RED HIGHLIGHT
│  ├─────────────────────────────────────────────────────────────────────────────────┤ │
│  │ TRN-2026-0003 Andi Pratama    Forklift Op.     🟡In Progress ⏳Pending  —      │ │
│  ├─────────────────────────────────────────────────────────────────────────────────┤ │
│  │ TRN-2026-0004 Joni Kurniawan  ISO Awareness    🔵Scheduled   —       —        │ │
│  ├─────────────────────────────────────────────────────────────────────────────────┤ │
│  │ TRN-2026-0005 Dewi Lestari    CPR & First Aid  🟢Completed  ✅Pass  05/08/2026  │ │ ← ORANGE (expiring soon)
│  └─────────────────────────────────────────────────────────────────────────────────┘ │
│  │ ... Kedaluwarsa   Aksi                                                          │ │
│  │ ... 12/07/2026   [👁 Lihat] [✏ Edit]                                           │ │
│  │ ... 01/07/2026   [👁 Lihat] [✏ Edit]                                           │ │
│  │ ... —            [👁 Lihat] [✏ Edit]                                           │ │
│  │ ... —            [👁 Lihat] [✏ Edit]                                           │ │
│  │ ... 05/08/2026   [👁 Lihat] [✏ Edit]                                           │ │
│  └─────────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                      │
│  ┌─ Pagination ───────────────────────────────────────────────────────────────────┐  │
│  │                              ‹ Sebelumnya   1  2  3  4  5  6   Berikutnya ›  │  │
│  └────────────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────────┘
```

### Expiry Row Highlight Detail

```
┌────────────────────────────────────────────────────────────────────────────────┐
│  Row dengan expiry_date < now() (status = expired):                            │
│  ┌──────────────────────────────────────────────────────────────────────────┐  │
│  │▌TRN-2026-0002 Sari Wulandari  Fire Safety  🔴Expired  ⚠ —   01/07/2026  │  │
│  └──────────────────────────────────────────────────────────────────────────┘  │
│  ▌ = border-l-4 border-red-500, bg-red-50 dark:bg-red-900/20                  │
│                                                                                │
│  Row dengan expiry_date ≤ 30 hari ke depan (expiring soon):                   │
│  ┌──────────────────────────────────────────────────────────────────────────┐  │
│  │▌TRN-2026-0005 Dewi Lestari    CPR & FA     🟢Completed ✅Pass 05/08/2026 │  │
│  └──────────────────────────────────────────────────────────────────────────┘  │
│  ▌ = border-l-4 border-orange-500, bg-orange-50 dark:bg-orange-900/20          │
└────────────────────────────────────────────────────────────────────────────────┘
```

#### Table Columns

| # | Column | Key | Width | Align | Badge? | Detail |
|---|---|---|---|---|---|---|
| 1 | Nomor | `training_number` | 130px | left | No | Link ke show page, monospace |
| 2 | Karyawan | `employee.name` | 150px | left | No | |
| 3 | Program | `program.name` | flex | left | No | Truncate |
| 4 | Status | `status` | 130px | center | Yes | See Color Coding |
| 5 | Hasil | `result` | 90px | center | Yes | pass/fail/pending |
| 6 | Kedaluwarsa | `expiry_date` | 120px | center | No | Date or `—`. Row highlighted if expired/expiring |

#### Filter Dropdowns

| Filter | Label | Options | Param |
|---|---|---|---|
| Search | "Cari nomor, nama karyawan..." | Free text | `?search=` |
| Program | "Program" | Semua + active programs | `?training_program_id=` |
| Karyawan | "Karyawan" | Semua + employees (scoped) | `?employee_id=` |
| Status | "Status" | Semua, Scheduled, In Progress, Completed, Expired, Cancelled | `?status=` |
| Site | "Site" | Semua + sites | `?site_id=` |
| Department | "Departemen" | Semua + departments | `?department_id=` |

---

## 6. Halaman Record Form

### Route

- Create: `GET /training-records/create` (`training.records.create`)
- Edit: `GET /training-records/{record}/edit` (`training.records.edit`)

### Permission

- Create: `training.records.create`
- Edit: `training.records.update`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Buat Record Pelatihan                                                           │
│  Jadwalkan pelatihan untuk karyawan                                              │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Pelatihan ────────────────────────────────────────────┐  │
│  │  INFORMASI PELATIHAN                                                       │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Nomor Record         [Auto-generated — TRN-2026-0006        ]  ⓘ          │  │
│  │                        Nomor akan dibuat otomatis saat simpan               │  │
│  │                                                                             │  │
│  │  Karyawan *           [— Pilih Karyawan —           ▾]                   │  │
│  │                        Cari berdasarkan nama atau NIK                       │  │
│  │                                                                             │  │
│  │  Program Pelatihan *  [— Pilih Program —             ▾]                   │  │
│  │                        Hanya program aktif yang ditampilkan                 │  │
│  │                                                                             │  │
│  │  Provider              [Nama lembaga/institusi pelatihan    ]             │  │
│  │                        Kosongkan jika internal                             │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Jadwal & Status ─────────────────────────────────────────────────┐  │
│  │  JADWAL & STATUS                                                           │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Tanggal Mulai *      [__/__/____]                                         │  │
│  │                                                                             │  │
│  │  Tanggal Selesai       [__/__/____]  (kosongkan jika masih berjalan)      │  │
│  │                                                                             │  │
│  │  Status *             [— Pilih Status —           ▾]                    │  │
│  │                        ○ Scheduled ○ In Progress ○ Completed              │  │
│  │                        ○ Expired ○ Cancelled                              │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Hasil & Sertifikasi ─────────────────────────────────────────────┐  │
│  │  HASIL & SERTIFIKASI                                                       │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Skor                  [85.50    ]  (0-100)                                │  │
│  │                                                                             │  │
│  │  Hasil                [— Pilih Hasil —           ▾]                     │  │
│  │                        ○ Pass ○ Fail ○ Pending                             │  │
│  │                                                                             │  │
│  │  Nomor Sertifikat      [CERT-2026-00123      ]                            │  │
│  │                        Nomor sertifikat dari lembaga                        │  │
│  │                                                                             │  │
│  │  File Sertifikat       [📁 Drag & drop atau klik untuk upload]             │  │
│  │                        Format: PDF, JPG, PNG. Maks 10MB.                  │  │
│  │                        📎 sertifikat_budi.pdf  2.1 MB  [🗑 Hapus]          │  │
│  │                                                                             │  │
│  │  Tanggal Kedaluwarsa   [__/__/____]  (auto dari program validity)         │  │
│  │                        Dihitung otomatis: end_date + validity_months       │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Catatan ─────────────────────────────────────────────────────────┐  │
│  │  CATATAN                                                                    │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Catatan              ┌──────────────────────────────────────────────┐     │  │
│  │                        │ Catatan tambahan terkait pelatihan...        │     │  │
│  │                        │                                              │     │  │
│  │                        └──────────────────────────────────────────────┘     │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Action Bar (sticky bottom) ──────────────────────────────────────────────┐  │
│  │                                                                           │  │
│  │  [← Batal]                                         [Simpan Record]      │  │
│  │                                                    (primary)              │  │
│  └───────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Form Fields

| # | Field | Type | Validation | Label (ID) | Notes |
|---|---|---|---|---|---|
| 1 | `training_number` | read-only | — | "Nomor Record" | Auto-generated on create. Shown as placeholder. |
| 2 | `employee_id` | select dropdown | required, exists:employees,id | "Karyawan" | Scoped by user permissions |
| 3 | `training_program_id` | select dropdown | required, exists:training_programs,id,is_active=true | "Program Pelatihan" | Only active programs |
| 4 | `provider` | text input | nullable, max:255 | "Provider" | |
| 5 | `start_date` | date picker | required, date | "Tanggal Mulai" | |
| 6 | `end_date` | date picker | nullable, date, after_or_equal:start_date | "Tanggal Selesai" | |
| 7 | `status` | select dropdown | required, in:list | "Status" | 5 options |
| 8 | `score` | number input | nullable, numeric, between:0,100 | "Skor" | |
| 9 | `result` | select dropdown | nullable, in:pass,fail,pending | "Hasil" | |
| 10 | `certificate_number` | text input | nullable, max:255 | "Nomor Sertifikat" | |
| 11 | `certificate_file` | file upload | nullable, file, mimes:pdf,jpg,jpeg,png, max:10240 | "File Sertifikat" | ManagedFileService |
| 12 | `expiry_date` | date picker | nullable, date | "Tanggal Kedaluwarsa" | Auto-calculated if program has validity_months |
| 13 | `notes` | textarea | nullable, text | "Catatan" | |

---

## 7. Halaman Record Show

### Route: `GET /training-records/{record}` (`training.records.show`)

### Permission: `training.records.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  TRN-2026-0001                                              [✏ Edit]            │
│  HSE Induction — Budi Santoso                                                    │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Detail Pelatihan ────────────────────────────────────────────────┐  │
│  │  DETAIL PELATIHAN                                                          │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Nomor Record          TRN-2026-0001                                        │  │
│  │  Karyawan              Budi Santoso (EMP-001)                              │  │
│  │  Department            Produksi                                             │  │
│  │  Program               HSE Induction (HSE-IND)                              │  │
│  │  Kategori              Safety                                               │  │
│  │  Provider              PT Safety First Indonesia                            │  │
│  │  Durasi Program        8 jam                                                │  │
│  │                                                                             │  │
│  │  Tanggal Mulai         01 Juli 2026                                         │  │
│  │  Tanggal Selesai       02 Juli 2026                                         │  │
│  │  Status                🟢 Completed                                          │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Hasil & Sertifikasi ─────────────────────────────────────────────┐  │
│  │  HASIL & SERTIFIKASI                                                       │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Skor                  85.50 / 100                                          │  │
│  │  Hasil                 ✅ Pass                                              │  │
│  │  Nomor Sertifikat      CERT-2026-00123                                      │  │
│  │  Tanggal Kedaluwarsa   02 Juli 2027                                        │  │
│  │  Status Kedaluwarsa    🟢 Masih Berlaku (363 hari lagi)                    │  │
│  │                                                                             │  │
│  │  File Sertifikat:                                                           │  │
│  │  ┌──────────────────────────────────────────────────────────────────┐     │  │
│  │  │ 📄 sertifikat_budi.pdf                          2.1 MB   [⬇ Download]│     │  │
│  │  └──────────────────────────────────────────────────────────────────┘     │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Catatan ─────────────────────────────────────────────────────────┐  │
│  │  CATATAN                                                                    │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │  Pelatihan berjalan dengan baik. Peserta aktif mengikuti seluruh sesi.    │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Activity Timeline ───────────────────────────────────────────────┐  │
│  │  ACTIVITY TIMELINE                                                          │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  ● 11 Jul 2026 09:00  Record created by Admin                               │  │
│  │  ● 01 Jul 2026 14:00  Status changed: scheduled → in_progress               │  │
│  │  ● 02 Jul 2026 16:00  Status changed: in_progress → completed               │  │
│  │  ● 02 Jul 2026 16:05  Certificate uploaded                                   │  │
│  │  ● 02 Jul 2026 16:10  Score updated: 85.50                                  │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Action Bar ──────────────────────────────────────────────────────────────┐  │
│  │  [← Kembali ke Daftar]                              [✏ Edit Record]      │  │
│  └───────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Expired Record — Show Page Variant

When `status = 'expired'`, show a prominent warning banner:

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│  ┌─ WARNING BANNER ────────────────────────────────────────────────────────────┐  │
│  │  ⚠ SERTIFIKAT KEDALUWARSA                                                  │  │
│  │  Sertifikat ini telah kedaluwarsa pada 01 Juli 2026 (12 hari yang lalu).   │  │
│  │  Mohon jadwalkan ulang pelatihan untuk memperbarui sertifikat.              │  │
│  │  [📅 Jadwalkan Ulang]                                                       │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

---

## 8. Halaman Training Matrix

### Route: `GET /training/matrix` (`training.matrix.index`)

### Permission: `training.records.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                                   │
│  Matriks Kompetensi                                                                      │
│  Grid status kompetensi karyawan per program pelatihan                                   │
├──────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                          │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────────────────┐  │
│  │ Site: [Semua ▾]   Departemen: [Semua ▾]   [Reset]                                  │  │
│  └────────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                          │
│  ┌─ Legend ────────────────────────────────────────────────────────────────────────────┐  │
│  │ 🟢 Completed   🔴 Expired   🟡 In Progress   🔵 Scheduled   ⚪ Not Started          │  │
│  └────────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                          │
│  ┌─ Matrix Grid ──────────────────────────────────────────────────────────────────────┐  │
│  │                                                                                     │  │
│  │  Karyawan          HSE Ind.  Fire Sft  Forklift  ISO 9001  CPR  Env. Spill  Sec.  │  │
│  │  ──────────────── ───────── ───────── ──────── ───────── ───── ─────────── ─────  │  │
│  │  Budi Santoso      🟢        🟢        🟢       ⚪         🟢    🟢          ⚪    │  │
│  │  Sari Wulandari    🟢        🔴        ⚪       🟢         ⚪    ⚪          ⚪    │  │
│  │  Andi Pratama      🟢        🟡        ⚪       ⚪         ⚪    ⚪          ⚪    │  │
│  │  Joni Kurniawan    🔵        ⚪        ⚪       ⚪         ⚪    ⚪          ⚪    │  │
│  │  Dewi Lestari      🟢        🟢        🟢       🟢         🟢    🟢          🟢    │  │
│  │  Eka Saputra       🟢        ⚪        🔴       ⚪         ⚪    ⚪          ⚪    │  │
│  │  ...                                                                                │  │
│  └─────────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                          │
│  ┌─ Pagination ───────────────────────────────────────────────────────────────────────┐  │
│  │  Menampilkan 1–20 dari 50 karyawan               ‹ Sebelumnya   1  2  3   ›       │  │
│  └────────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                          │
│  ┌─ Toolbar ──────────────────────────────────────────────────────────────────────┐    │
│  │                                                           [⬇ Export CSV]     │    │
│  └────────────────────────────────────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────────────────────────────────────┘
```

### Matrix Cell Behavior

| Cell Status | Color | Tooltip | Click Action |
|---|---|---|---|
| Completed (valid) | `bg-green-100 dark:bg-green-900/30` | "Completed on {end_date}. Expires {expiry_date}." | Link to record detail |
| Completed (expired) | `bg-red-100 dark:bg-red-900/30` | "Expired on {expiry_date}. Please re-schedule." | Link to record detail |
| In Progress | `bg-yellow-100 dark:bg-yellow-900/30` | "Started {start_date}. In progress." | Link to record detail |
| Scheduled | `bg-blue-100 dark:bg-blue-900/30` | "Scheduled for {start_date}." | Link to record detail |
| Not Started | `bg-gray-100 dark:bg-gray-700/30` | "No training record." | No action |

### Matrix Query Logic

```php
// Controller: TrainingMatrixController@index
$employees = Employee::query()
    ->with(['trainingRecords' => function ($q) {
        $q->whereIn('status', ['completed', 'in_progress', 'scheduled', 'expired'])
          ->orderBy('created_at', 'desc');
    }, 'trainingRecords.program'])
    ->where('is_active', true)
    ->when($siteId, fn($q) => $q->where('site_id', $siteId))
    ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
    ->paginate(20);

$programs = TrainingProgram::where('is_active', true)->orderBy('category')->orderBy('name')->get();
```

### Inertia Props

```typescript
interface MatrixProps {
    employees: {
        data: (Employee & {
            trainingRecords: TrainingRecord[];
        })[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    programs: TrainingProgram[];
    filters: {
        site_id?: number;
        department_id?: number;
    };
    sites: Site[];
    departments: Department[];
    can: {
        export: boolean;
    };
}
```

---

## 9. Mobile Responsive

### Breakpoints

- **Desktop** (≥1024px): Full table with all columns, matrix grid fully visible.
- **Tablet** (768px–1023px): Table columns reduced (hide provider, score), matrix grid horizontal scroll.
- **Mobile** (<768px): Card-based layout for records, matrix hidden behind tab toggle.

### Mobile Record Index — Card Layout

```
┌──────────────────────────────┐
│  📋 Record Pelatihan         │
│  [+ Buat]    [⬇ Export]     │
├──────────────────────────────┤
│  [🔍 Cari nomor, karyawan..]│
│  Status: [Semua ▾]          │
├──────────────────────────────┤
│  ┌────────────────────────┐ │
│  │▌TRN-2026-0001         │ │
│  │  Budi Santoso          │ │
│  │  HSE Induction         │ │
│  │  🟢 Completed  ✅Pass │ │
│  │  Expired: 12/07/2026   │ │
│  │           [👁 Lihat]   │ │
│  └────────────────────────┘ │
│  ┌────────────────────────┐ │
│  │▌TRN-2026-0002         │ │ ← RED HIGHLIGHT
│  │  Sari Wulandari        │ │
│  │  Fire Safety           │ │
│  │  🔴 Expired     ⚠ —  │ │
│  │  Expired: 01/07/2026   │ │
│  │           [👁 Lihat]   │ │
│  └────────────────────────┘ │
│                              │
│  ‹ 1  2  3  ›               │
└──────────────────────────────┘
```

### Mobile Form — Stacked Sections

Form sections stack vertically. File upload uses native file picker. Date inputs use native date pickers.

### Mobile Matrix — Simplified

Matrix grid replaced with tab toggle:
- Tab 1: Employee list with competency summary (count of completed/expired per employee)
- Tab 2: Program list with employee completion count
- Tap employee → shows their training records in a list

---

## 10. Component List

| # | Component | File | Description |
|---|---|---|---|
| 1 | `TrainingProgramIndex` | `Pages/Modules/Training/Program/Index.tsx` | Program list page |
| 2 | `TrainingProgramForm` | `Pages/Modules/Training/Program/Form.tsx` | Program create/edit form |
| 3 | `TrainingRecordIndex` | `Pages/Modules/Training/Record/Index.tsx` | Record list with expiry highlight |
| 4 | `TrainingRecordForm` | `Pages/Modules/Training/Record/Form.tsx` | Record create/edit form |
| 5 | `TrainingRecordShow` | `Pages/Modules/Training/Record/Show.tsx` | Record detail page |
| 6 | `TrainingMatrix` | `Pages/Modules/Training/Matrix/Index.tsx` | Matrix grid view |
| 7 | `StatusBadge` | `components/training/StatusBadge.tsx` | Reusable status badge |
| 8 | `ResultBadge` | `components/training/ResultBadge.tsx` | Reusable result badge |
| 9 | `ExpiryIndicator` | `components/training/ExpiryIndicator.tsx` | Shows expiry status with color |
| 10 | `ProgramSelect` | `components/training/ProgramSelect.tsx` | Dropdown for active programs |
| 11 | `EmployeeSelect` | `components/training/EmployeeSelect.tsx` | Dropdown for employees (scoped) |
| 12 | `CertificateUpload` | `components/training/CertificateUpload.tsx` | File upload for certificate |
| 13 | `MatrixCell` | `components/training/MatrixCell.tsx` | Single cell in matrix grid |
