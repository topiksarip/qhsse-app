# UI Pages — Emergency Preparedness

Spesifikasi wireframe halaman UI untuk modul Emergency Preparedness.

Semua label menggunakan Bahasa Indonesia. Tech stack: Laravel 12 + Inertia React + TypeScript + Tailwind CSS.

---

## Daftar Isi

1. [Navigasi](#1-navigasi)
2. [Color Coding](#2-color-coding)
3. [Halaman Index — Daftar Rencana Darurat](#3-halaman-index--daftar-rencana-darurat)
4. [Halaman Form — Buat/Edit Rencana Darurat](#4-halaman-form--buatedit-rencana-darurat)
5. [Halaman Show — Detail Rencana Darurat](#5-halaman-show--detail-rencana-darurat)
6. [Halaman Index — Daftar Latihan Darurat](#6-halaman-index--daftar-latihan-darurat)
7. [Halaman Form — Buat/Edit Latihan Darurat](#7-halaman-form--buatedit-latihan-darurat)
8. [Halaman Show — Detail Latihan Darurat](#8-halaman-show--detail-latihan-darurat)
9. [Halaman Index — Daftar Kontak Darurat](#9-halaman-index--daftar-kontak-darurat)
10. [Mobile Responsive](#10-mobile-responsive)

---

## 1. Navigasi

### Penempatan Menu

Tambahkan tiga item menu pada group `Modul QHSSE` di `AuthenticatedLayout.tsx`:

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
            // ... existing module items
            { label: 'Rencana Darurat', routeName: 'emergency-plans.index', active: 'emergency-plans.*', permission: 'emergency.plans.view' },
            { label: 'Latihan Darurat', routeName: 'emergency-drills.index', active: 'emergency-drills.*', permission: 'emergency.drills.view' },
            { label: 'Kontak Darurat', routeName: 'emergency-contacts.index', active: 'emergency-contacts.*', permission: 'emergency.contacts.view' },
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
│                        ┌────────────────────────┐                     │
│                        │ ...                    │                     │
│                        │ Rencana Darurat        │                     │
│                        │ Latihan Darurat        │                     │
│                        │ Kontak Darurat         │                     │
│                        │ ...                    │                     │
│                        └────────────────────────┘                     │
└──────────────────────────────────────────────────────────────────────┘
```

### Wireframe Navigasi (Mobile — Hamburger)

```
┌──────────────────────┐
│  [Logo] QHSSE   [☰]  │
├──────────────────────┤
│  MODUL QHSSE         │
│   ...                │
│   Rencana Darurat    │
│   Latihan Darurat    │
│   Kontak Darurat     │
│   ...                │
├──────────────────────┤
│  John Doe            │
│  john@example.com    │
└──────────────────────┘
```

### Permission Filtering

Menu hanya tampil jika user memiliki permission masing-masing: `emergency.plans.view`, `emergency.drills.view`, `emergency.contacts.view`. Filtering dilakukan via `auth.permissions` pada layout.

---

## 2. Color Coding

### Plan Type Badge

| Type | Tailwind Class | Preview |
|---|---|---|
| Kebakaran (fire) | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Kebakaran` |
| Medis (medical) | `bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200` | `🩺 Medis` |
| Tumpahan (spill) | `bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200` | `🟠 Tumpahan` |
| Evakuasi (evacuation) | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | `🔵 Evakuasi` |
| Bencana Alam (natural_disaster) | `bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200` | `🟡 Bencana Alam` |
| Keamanan (security) | `bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200` | `🔵 Keamanan` |
| Lainnya (other) | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | `⚪ Lainnya` |

### Drill Status Badge

| Status | Tailwind Class | Preview |
|---|---|---|
| Terjadwal (scheduled) | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 Terjadwal` |
| Selesai (executed) | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Selesai` |

### Drill Result Badge

| Result | Tailwind Class | Preview |
|---|---|---|
| Lulus (pass) | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Lulus` |
| Gagal (fail) | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Gagal` |
| Perlu Perbaikan (needs_improvement) | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 Perlu Perbaikan` |

### Contact Active Badge

| Status | Tailwind Class | Preview |
|---|---|---|
| Aktif | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Aktif` |
| Nonaktif | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | `⚪ Nonaktif` |

### Pemetaan Helper

```typescript
const planTypeColors: Record<string, BadgeColor> = {
    fire:             'red',
    medical:          'pink',
    spill:            'orange',
    evacuation:       'blue',
    natural_disaster: 'amber',
    security:         'indigo',
    other:            'gray',
};

const planTypeLabels: Record<string, string> = {
    fire:             'Kebakaran',
    medical:          'Medis',
    spill:            'Tumpahan',
    evacuation:       'Evakuasi',
    natural_disaster: 'Bencana Alam',
    security:         'Keamanan',
    other:            'Lainnya',
};

const drillStatusColors: Record<string, BadgeColor> = {
    scheduled: 'yellow',
    executed:  'green',
};

const drillStatusLabels: Record<string, string> = {
    scheduled: 'Terjadwal',
    executed:  'Selesai',
};

const drillResultColors: Record<string, BadgeColor> = {
    pass:               'green',
    fail:               'red',
    needs_improvement:  'yellow',
};

const drillResultLabels: Record<string, string> = {
    pass:               'Lulus',
    fail:               'Gagal',
    needs_improvement:  'Perlu Perbaikan',
};
```

---

## 3. Halaman Index — Daftar Rencana Darurat

### Route: `GET /emergency-plans` (`emergency-plans.index`)

### Permission: `emergency.plans.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Rencana Darurat                                      [+ Buat Rencana]          │
│  Kelola rencana kesiapsiagaan darurat: kebakaran, medis, tumpahan, evakuasi      │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari nomor, nama...          ]                                        │  │
│  │                                                                            │  │
│  │ Tipe: [Semua ▾]   Site: [Semua ▾]                     [Reset]              │  │
│  └────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Toolbar ───────────────────────────────────────────────────────────────────┐ │
│  │ Menampilkan 1–10 dari 24 rencana                      [⬇ Export CSV]        │ │
│  └──────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                  │
│  ┌─ Table ─────────────────────────────────────────────────────────────────────┐ │
│  │ Nomor        Nama                  Tipe        Site        Kontak    Aksi   │ │
│  ├─────────────────────────────────────────────────────────────────────────────┤ │
│  │ EMG-0001    Rencana Kebakaran    🔴 Kebakaran Plant A   Budi S.   [👁] [✏]  │ │
│  │ EMG-0002    Rencana Evakuasi     🔵 Evakuasi  Plant A   Andi P.   [👁] [✏]  │ │
│  │ EMG-0003    Spill Response       🟠 Tumpahan  Plant B   Sari W.   [👁] [✏]  │ │
│  │ EMG-0004    Gempa Bumi           🟡 Bencana   Plant B   Joni K.   [👁]      │ │
│  │ EMG-0005    Medical Emergency    🩺 Medis     Plant A   Budi S.   [👁] [✏]  │ │
│  │ ...                                                                         │ │
│  └─────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                  │
│  ┌─ Pagination ───────────────────────────────────────────────────────────────┐ │
│  │                              ‹ Sebelumnya   1  2   Berikutnya ›             │ │
│  └──────────────────────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Wireframe — Empty State

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Rencana Darurat                                      [+ Buat Rencana]          │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌──────────────────────────────────────────────────────────────────────────┐   │
│  │                                                                          │   │
│  │                              🚨                                         │   │
│  │                                                                          │   │
│  │                   Belum ada rencana darurat                             │   │
│  │                                                                          │   │
│  │           Belum ada rencana yang dibuat. Klik tombol di bawah            │   │
│  │           untuk membuat rencana darurat pertama Anda.                    │   │
│  │                                                                          │   │
│  │                      [+ Buat Rencana Pertama]                           │   │
│  │                                                                          │   │
│  └──────────────────────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Element

#### Header

| Element | Type | Detail |
|---|---|---|
| Title | `<h1>` | "Rencana Darurat" |
| Subtitle | `<p>` | "Kelola rencana kesiapsiagaan darurat: kebakaran, medis, tumpahan, evakuasi" |
| Button "Buat Rencana" | `<Link>` | Route: `emergency-plans.create`, permission: `emergency.plans.create` |
| Button Style | Tailwind | `bg-red-600 text-white hover:bg-red-700` |

#### Filter Bar

| Filter | Label | Options | Param |
|---|---|---|---|
| Search | "Cari nomor, nama..." | Text input with debounce | `?search=` |
| Tipe | "Tipe" | Semua, Kebakaran, Medis, Tumpahan, Evakuasi, Bencana Alam, Keamanan, Lainnya | `?type=` |
| Site | "Site" | Semua + from master Sites | `?site_id=` |
| Reset | "Reset" | Clear all filters | — |

#### Table Columns

| # | Column | Key | Width | Align | Badge? | Detail |
|---|---|---|---|---|---|---|
| 1 | Nomor | `plan_number` | 120px | left | No | Link ke Show page, monospace |
| 2 | Nama | `name` | flex | left | No | Truncate `max-w-xs truncate` |
| 3 | Tipe | `type` | 120px | left | Yes | Type badge with label |
| 4 | Site | `site.name` | 100px | left | No | |
| 5 | Kontak | `contactPerson.name` | 120px | left | No | |
| 6 | Aksi | — | 100px | center | No | See below |

#### Aksi Column (per row)

| Action | Icon | Permission | Condition |
|---|---|---|---|
| Lihat | 👁 | `emergency.plans.view` | Selalu tampil |
| Edit | ✏ | `emergency.plans.update` | Selalu tampil |

#### Pagination

```
Menampilkan 1–10 dari 24 rencana

‹ Sebelumnya   1  2   Berikutnya ›
```

- 15 item per halaman (configurable: 10/25/50).

### Inertia Props

```typescript
interface PlanIndexProps {
    plans: {
        data: EmergencyPlan[];
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
        site_id?: number;
    };
    sites: Site[];
    can: {
        create: boolean;
        export: boolean;
    };
}
```

---

## 4. Halaman Form — Buat/Edit Rencana Darurat

### Route

- Create: `GET /emergency-plans/create` (`emergency-plans.create`)
- Edit: `GET /emergency-plans/{plan}/edit` (`emergency-plans.edit`)

### Permission

- Create: `emergency.plans.create`
- Edit: `emergency.plans.update`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Buat Rencana Darurat                                                            │
│  Isi data rencana darurat dengan lengkap                                         │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Umum ──────────────────────────────────────────────────┐  │
│  │  INFORMASI UMUM                                                             │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Nomor Rencana      [Auto-generated — EMG-0001              ]  ⓘ          │  │
│  │                       Nomor akan dibuat otomatis saat simpan                │  │
│  │                                                                             │  │
│  │  Nama *             [Masukkan nama rencana darurat...        ]              │  │
│  │                                                                             │  │
│  │  Tipe *             [— Pilih Tipe —           ▾]                           │  │
│  │                       ○ Kebakaran  ○ Medis  ○ Tumpahan                    │  │
│  │                       ○ Evakuasi  ○ Bencana Alam  ○ Keamanan              │  │
│  │                       ○ Lainnya                                            │  │
│  │                                                                             │  │
│  │  Site *             [— Pilih Site —    ▾]                                  │  │
│  │                                                                             │  │
│  │  Kontak Person *    [— Pilih User —   ▾]                                   │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Deskripsi & Prosedur ────────────────────────────────────────────┐  │
│  │  DESKRIPSI & PROSEDUR                                                       │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Deskripsi *        ┌──────────────────────────────────────────────┐       │  │
│  │                       │ Jelaskan rencana darurat secara detail...   │       │  │
│  │                       │                                              │       │  │
│  │                       └──────────────────────────────────────────────┘       │  │
│  │                                                                             │  │
│  │  Prosedur Respons * ┌──────────────────────────────────────────────┐       │  │
│  │                       │ Langkah-langkah respons darurat:          │       │  │
│  │                       │ 1. ...                                     │       │  │
│  │                       │ 2. ...                                     │       │  │
│  │                       └──────────────────────────────────────────────┘       │  │
│  │                                                                             │  │
│  │  Prosedur Eskalasi * ┌──────────────────────────────────────────────┐       │  │
│  │                       │ Prosedur eskalasi:                         │       │  │
│  │                       │ 1. Hubungi...                              │       │  │
│  │                       │ 2. Eskalasi ke...                         │       │  │
│  │                       └──────────────────────────────────────────────┘       │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Kontak Darurat Tambahan ────────────────────────────────────────┐  │
│  │  KONTAK DARURAT TAMBAHAN                                                   │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  [+ Tambah Kontak]                                                         │  │
│  │  ┌─────────────────────────────────────────────────────────────────────┐   │  │
│  │  │ Nama: [________________]  Peran: [______________]                  │   │  │
│  │  │ Telepon: [____________]  [🗑 Hapus]                                 │   │  │
│  │  └─────────────────────────────────────────────────────────────────────┘   │  │
│  │  (repeatable — JSON array stored in emergency_contacts field)              │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Peralatan ───────────────────────────────────────────────────────┐  │
│  │  PERALATAN                                                                  │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Peralatan yang Dibutuhkan                                                          │  │
│  │  ┌────────────────────────────────────────────────────────────────────┐    │  │
│  │  │ APAR, Hydrant, Eye Wash, Spill Kit, Stretcher, Radio Komunikasi... │    │  │
│  │  │                                                                      │    │  │
│  │  └────────────────────────────────────────────────────────────────────┘    │  │
│  │  (opsional)                                                                 │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Action Bar (sticky bottom) ──────────────────────────────────────────────┐  │
│  │                                                                           │  │
│  │  [← Batal]                                                [Simpan]       │  │
│  │                                                           (primary)       │  │
│  └───────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Dynamic Emergency Contacts (JSON Array)

```tsx
{fields.map((field, index) => (
    <div key={field.id} className="border rounded-lg p-4 flex gap-4 items-end">
        <Input label="Nama" name={`emergency_contacts.${index}.name`} />
        <Input label="Peran" name={`emergency_contacts.${index}.role`} />
        <Input label="Telepon" name={`emergency_contacts.${index}.phone`} />
        <button type="button" onClick={() => remove(index)}>🗑 Hapus</button>
    </div>
))}
<button type="button" onClick={() => append({ name: '', role: '', phone: '' })}>
    + Tambah Kontak
</button>
```

### Inertia Props

```typescript
interface PlanFormProps {
    plan: EmergencyPlan | null; // null for create
    sites: Site[];
    users: User[]; // for contact_person_id select
    can: {
        create: boolean;
        update: boolean;
    };
}
```

---

## 5. Halaman Show — Detail Rencana Darurat

### Route: `GET /emergency-plans/{plan}` (`emergency-plans.show`)

### Permission: `emergency.plans.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  ← Kembali    EMG-2026-0001 — Rencana Kebakaran    [✏ Edit] [⬇ Export]         │
│  📋 Rencana Darurat                                                              │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Umum ──────────────────────────────────────────────────┐  │
│  │  INFORMASI UMUM                                                             │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │  Nomor            : EMG-2026-0001                                           │  │
│  │  Nama             : Rencana Kebakaran                                        │  │
│  │  Tipe             : 🔴 Kebakaran                                             │  │
│  │  Site             : Plant A                                                 │  │
│  │  Kontak Person    : Budi Santoso                                            │  │
│  │  Dibuat Pada      : 11 Juli 2026, 10:00                                     │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Deskripsi ──────────────────────────────────────────────────────┐  │
│  │  DESKRIPSI                                                                  │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │  Rencana respons untuk kebakaran di area produksi Plant A.                │  │
│  │  Mencakup prosedur evakuasi, penggunaan APAR, dan koordinasi               │  │
│  │  dengan pemadam kebakaran eksternal.                                        │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Prosedur Respons ───────────────────────────────────────────────┐  │
│  │  PROSEDUR RESPONS                                                           │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │  1. Aktifkan alarm kebakaran                                                │  │
│  │  2. Hubungi pemadam kebakaran (119)                                         │  │
│  │  3. Evakuasi karyawan ke titik kumpul (assembly point)                    │  │
│  │  4. Lakukan headcount di assembly point                                     │  │
│  │  5. Gunakan APAR untuk api kecil                                           │  │
│  │  6. Jangan kembali ke gedung sampai all-clear diberikan                    │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Prosedur Eskalasi ──────────────────────────────────────────────┐  │
│  │  PROSEDUR ESKALASI                                                          │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │  1. Laporkan ke Supervisor langsung                                         │  │
│  │  2. Eskalasi ke QHSSE Officer dalam 5 menit                                 │  │
│  │  3. Eskalasi ke QHSSE Manager dalam 15 menit                                │  │
│  │  4. Eskalasi ke Top Management untuk kebakaran besar                        │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Kontak Darurat Tambahan ────────────────────────────────────────┐  │
│  │  KONTAK DARURAT TAMBAHAN                                                   │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │  ┌──────────────────────────────────────────────────────────────────┐      │  │
│  │  │ Nama: Budi Santoso    Peran: Fire Warden    Telp: +62-812-...   │      │  │
│  │  ├──────────────────────────────────────────────────────────────────┤      │  │
│  │  │ Nama: Sari Wijaya     Peran: First Aider     Telp: +62-813-...   │      │  │
│  │  └──────────────────────────────────────────────────────────────────┘      │  │
│  │  — atau —                                                                  │  │
│  │  Tidak ada kontak tambahan.                                                │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Peralatan ───────────────────────────────────────────────────────┐  │
│  │  PERALATAN YANG DIBUTUHKAN                                                 │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │  APAR (4 unit), Hydrant (2 unit), Smoke Detector, Eye Wash Station          │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Latihan Darurat Terkait ────────────────────────────────────────┐  │
│  │  LATIHAN DARURAT TERKAIT                                                   │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │  ┌──────────────────────────────────────────────────────────────────┐      │  │
│  │  │ EMG-0005  🟡 Terjadwal   15 Jul 2026   50 peserta   [👁 Lihat]   │      │  │
│  │  ├──────────────────────────────────────────────────────────────────┤      │  │
│  │  │ EMG-0003  🟢 Selesai 🟢 Lulus  10 Mar 2026   45 peserta  [👁]    │      │  │
│  │  ├──────────────────────────────────────────────────────────────────┤      │  │
│  │  │ EMG-0002  🟢 Selesai 🔴 Gagal  10 Sep 2025   30 peserta  [👁]    │      │  │
│  │  └──────────────────────────────────────────────────────────────────┘      │  │
│  │  [+ Jadwalkan Latihan]  (permission: emergency.drills.create)              │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Evidence ───────────────────────────────────────────────────────┐  │
│  │  EVIDENCE                                                                  │  │
│  │  ┌──────────────────┐  ┌──────────────────┐                               │  │
│  │  │  📄              │  │  📷              │                               │  │
│  │  │  prosedur.pdf    │  │  layout.jpg     │                               │  │
│  │  │  850 KB          │  │  1.1 MB          │                               │  │
│  │  │  [⬇ Download]    │  │  [⬇ Download]    │                               │  │
│  │  └──────────────────┘  └──────────────────┘                               │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Aktivitas & Komentar ──────────────────────────────────────────┐  │
│  │  AKTIVITAS & KOMENTAR                                                      │  │
│  │  [Tab: Timeline]  [Tab: Komentar]                                          │  │
│  │                                                                             │  │
│  │  ◉ 11 Jul 10:00 — Budi Santoso membuat rencana darurat                    │  │
│  │  ◉ 11 Jul 10:05 — Budi Santoso mengupload file: prosedur.pdf              │  │
│  │  ◉ 12 Jul 09:00 — Sari Wijaya menjadwalkan latihan: EMG-0005              │  │
│  │                                                                             │  │
│  │  ┌─ Add Comment ─────────────────────────────────────────────────────┐     │  │
│  │  │ [Tulis komentar...                                        ]      │     │  │
│  │  │                                                    [Kirim]      │     │  │
│  │  └────────────────────────────────────────────────────────────────────┘     │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Linked Drills Section

```tsx
{plan.drills.map((drill) => (
    <div key={drill.id} className="border rounded-lg p-4 flex items-center justify-between">
        <div className="flex items-center gap-4">
            <Badge label={drillStatusLabels[drill.status]} color={drillStatusColors[drill.status]} />
            {drill.result && (
                <Badge label={drillResultLabels[drill.result]} color={drillResultColors[drill.result]} />
            )}
            <span className="font-mono text-sm">{drill.drill_number}</span>
            <span className="text-sm text-gray-600">
                {drill.executed_date ? drill.executed_date.format('d M Y') : drill.scheduled_date.format('d M Y')}
            </span>
            {drill.participants_count && (
                <span className="text-sm text-gray-500">{drill.participants_count} peserta</span>
            )}
        </div>
        <Link href={route('emergency-drills.show', drill.id)}>👁 Lihat</Link>
    </div>
))}
```

### Inertia Props

```typescript
interface PlanShowProps {
    plan: EmergencyPlan & {
        site: Site;
        contactPerson: User;
        drills: EmergencyDrill[];
    };
    evidence: ManagedFile[];
    comments: Comment[];
    activities: ActivityLog[];
    can: {
        update: boolean;
        export: boolean;
        createDrill: boolean;
    };
}
```

---

## 6. Halaman Index — Daftar Latihan Darurat

### Route: `GET /emergency-drills` (`emergency-drills.index`)

### Permission: `emergency.drills.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Latihan Darurat                                      [+ Jadwalkan Latihan]     │
│  Kelola latihan darurat: penjadwalan dan pelacakan eksekusi                     │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari nomor...             ]                                            │  │
│  │                                                                            │  │
│  │ Status: [Semua ▾]   Hasil: [Semua ▾]   Site: [Semua ▾]   [Reset]         │  │
│  │ Dari: [__/__/____]  Sampai: [__/__/____]                                  │  │
│  └────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Toolbar ───────────────────────────────────────────────────────────────────┐ │
│  │ Menampilkan 1–10 dari 18 latihan                     [⬇ Export CSV]        │ │
│  └──────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                  │
│  ┌─ Table ─────────────────────────────────────────────────────────────────────┐ │
│  │ Nomor       Rencana           Terjadwal   Eksekusi    Hasil     Status  Aksi │ │
│  ├─────────────────────────────────────────────────────────────────────────────┤ │
│  │ EMG-0005   Kebakaran         15 Jul 2026  —          —        🟡Terj  [👁] │ │
│  │ EMG-0003   Kebakaran         10 Mar 2026  10 Mar 2026 🟢Lulus  🟢Sele  [👁]│ │
│  │ EMG-0002   Evakuasi          10 Sep 2025  10 Sep 2025 🔴Gagal  🟢Sele  [👁]│ │
│  │ EMG-0004   Spill Response    05 Aug 2025  05 Aug 2025 🟡Perlu  🟢Sele  [👁]│ │
│  │ ...                                                                         │ │
│  └─────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                  │
│  ┌─ Pagination ───────────────────────────────────────────────────────────────┐ │
│  │                              ‹ Sebelumnya   1  2   Berikutnya ›             │ │
│  └──────────────────────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Table Columns

| # | Column | Key | Width | Align | Badge? | Detail |
|---|---|---|---|---|---|---|
| 1 | Nomor | `drill_number` | 110px | left | No | Link ke Show page, monospace |
| 2 | Rencana | `emergencyPlan.name` | flex | left | No | Truncate |
| 3 | Terjadwal | `scheduled_date` | 110px | center | No | Format: `dd/mm/yy` |
| 4 | Eksekusi | `executed_date` | 110px | center | No | Format: `dd/mm/yy` or "—" |
| 5 | Hasil | `result` | 120px | center | Yes | Result badge |
| 6 | Status | `status` | 100px | center | Yes | Status badge |
| 7 | Aksi | — | 100px | center | No | See below |

#### Aksi Column (per row)

| Action | Icon | Permission | Condition |
|---|---|---|---|
| Lihat | 👁 | `emergency.drills.view` | Selalu tampil |
| Edit | ✏ | `emergency.drills.update` | Status = `scheduled` |
| Eksekusi | ▶ | `emergency.drills.execute` | Status = `scheduled` |

### Inertia Props

```typescript
interface DrillIndexProps {
    drills: {
        data: EmergencyDrill[];
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
        result?: string;
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

## 7. Halaman Form — Buat/Edit Latihan Darurat

### Route

- Create: `GET /emergency-drills/create` (`emergency-drills.create`)
- Edit: `GET /emergency-drills/{drill}/edit` (`emergency-drills.edit`)

### Permission

- Create: `emergency.drills.create`
- Edit: `emergency.drills.update` (hanya jika status = `scheduled`)

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Jadwalkan Latihan Darurat                                                       │
│  Isi data latihan darurat dengan lengkap                                         │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Latihan ──────────────────────────────────────────────┐  │
│  │  INFORMASI LATIHAN                                                         │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Nomor Latihan     [Auto-generated — EMG-0005              ]  ⓘ          │  │
│  │                                                                             │  │
│  │  Rencana Darurat *  [— Pilih Rencana —     ▾]                              │  │
│  │                       (filtered by site)                                    │  │
│  │                                                                             │  │
│  │  Site *             [— Pilih Site —    ▾]                                  │  │
│  │                                                                             │  │
│  │  Tanggal Terjadwal * [__/__/____] [📅]                                     │  │
│  │                                                                             │  │
│  │  Observer *         [— Pilih User —   ▾]                                   │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Action Bar (sticky bottom) ──────────────────────────────────────────────┐  │
│  │                                                                           │  │
│  │  [← Batal]                                                [Simpan]       │  │
│  │                                                           (primary)       │  │
│  └───────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Inertia Props

```typescript
interface DrillFormProps {
    drill: EmergencyDrill | null; // null for create
    plans: EmergencyPlan[]; // for emergency_plan_id select
    sites: Site[];
    users: User[]; // for observer_id select
    can: {
        create: boolean;
        update: boolean;
    };
}
```

---

## 8. Halaman Show — Detail Latihan Darurat

### Route: `GET /emergency-drills/{drill}` (`emergency-drills.show`)

### Permission: `emergency.drills.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  ← Kembali    EMG-2026-0005 — Latihan Kebakaran    [✏ Edit] [⬇ Export]          │
│  📋 Latihan Darurat                                                              │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Latihan ──────────────────────────────────────────────┐  │
│  │  INFORMASI LATIHAN                                                         │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │  Nomor            : EMG-2026-0005                                           │  │
│  │  Rencana Darurat  : EMG-2026-0001 — Rencana Kebakaran   [👁 Lihat Rencana]  │  │
│  │  Site             : Plant A                                                 │  │
│  │  Tanggal Terjadwal: 15 Juli 2026                                           │  │
│  │  Tanggal Eksekusi : — (belum dieksekusi)                                   │  │
│  │  Observer         : Andi Pratama                                            │  │
│  │  Status           : 🟡 Terjadwal                                            │  │
│  │  Hasil            : — (belum ada)                                          │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Hasil Eksekusi ─────────────────────────────────────────────────┐  │
│  │  HASIL EKSEKUSI                                                            │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  (Jika status = scheduled:)                                                │  │
│  │  ┌─ Execute Form ───────────────────────────────────────────────────────┐   │  │
│  │  │ Tanggal Eksekusi *  [__/__/____] [📅]                              │   │  │
│  │  │ Jumlah Peserta *    [____]                                          │   │  │
│  │  │ Hasil *              [— Pilih Hasil — ▾]                            │   │  │
│  │  │                       ○ Lulus  ○ Gagal  ○ Perlu Perbaikan          │   │  │
│  │  │ Temuan               ┌──────────────────────────────────────┐      │   │  │
│  │  │                       │ Temuan dari latihan...               │      │   │  │
│  │  │                       └──────────────────────────────────────┘      │   │  │
│  │  │ Rekomendasi          ┌──────────────────────────────────────┐      │   │  │
│  │  │                       │ Rekomendasi perbaikan...             │      │   │  │
│  │  │                       └──────────────────────────────────────┘      │   │  │
│  │  │                                                     [Eksekusi]     │   │  │
│  │  └──────────────────────────────────────────────────────────────────────┘   │  │
│  │                                                                             │  │
│  │  (Jika status = executed:)                                                │  │
│  │  Tanggal Eksekusi  : 10 Maret 2026                                         │  │
│  │  Jumlah Peserta    : 45                                                    │  │
│  │  Hasil             : 🟢 Lulus                                              │  │
│  │  Temuan            : Semua peserta berhasil evakuasi dalam 3 menit.       │  │
│  │  Rekomendasi       : Tambah APAR di area warehouse.                       │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Aktivitas ──────────────────────────────────────────────────────┐  │
│  │  AKTIVITAS                                                                 │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │  ◉ 11 Jul 10:00 — Sari Wijaya menjadwalkan latihan darurat                │  │
│  │  ◉ 10 Mar 14:00 — Andi Pratama mengeksekusi latihan (Hasil: Lulus)        │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Execute Form (shown only if status = `scheduled` and user has `emergency.drills.execute`)

```tsx
{drill.status === 'scheduled' && can.execute && (
    <div className="border-2 border-blue-300 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
        <h3 className="text-lg font-bold text-blue-800 dark:text-blue-200 mb-4">
            Form Eksekusi Latihan
        </h3>
        <Input label="Tanggal Eksekusi *" name="executed_date" type="date" required />
        <Input label="Jumlah Peserta *" name="participants_count" type="number" min="0" required />
        <Select label="Hasil *" name="result" options={[
            { value: 'pass', label: 'Lulus' },
            { value: 'fail', label: 'Gagal' },
            { value: 'needs_improvement', label: 'Perlu Perbaikan' },
        ]} required />
        <Textarea label="Temuan" name="findings" />
        <Textarea label="Rekomendasi" name="recommendations" />
        <button type="submit" className="bg-blue-600 text-white px-4 py-2 rounded">
            Eksekusi
        </button>
    </div>
)}
```

### Inertia Props

```typescript
interface DrillShowProps {
    drill: EmergencyDrill & {
        emergencyPlan: EmergencyPlan;
        site: Site;
        observer: User;
    };
    activities: ActivityLog[];
    can: {
        update: boolean;
        execute: boolean;
        export: boolean;
    };
}
```

---

## 9. Halaman Index — Daftar Kontak Darurat

### Route: `GET /emergency-contacts` (`emergency-contacts.index`)

### Permission: `emergency.contacts.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Kontak Darurat                                       [+ Buat Kontak]            │
│  Kelola direktori kontak darurat per site                                         │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari nama, telepon...     ]                                            │  │
│  │                                                                            │  │
│  │ Site: [Semua ▾]   Status: [Semua ▾]   [Reset]                             │  │
│  └────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Table ─────────────────────────────────────────────────────────────────────┐ │
│  │ Nama              Peran              Telepon         Email        Status  Aksi│ │
│  ├─────────────────────────────────────────────────────────────────────────────┤ │
│  │ Budi Santoso     Fire Warden        +62-812-3456   budi@...     🟢Aktif [✏]│ │
│  │ Sari Wijaya      First Aider        +62-813-9876   sari@...     🟢Aktif [✏]│ │
│  │ Joni Kurnia      Site Security      +62-814-1111   —            ⚪Nonak [✏]│ │
│  │ Andi Pratama     Medical Officer    +62-815-2222   andi@...     🟢Aktif [✏]│ │
│  │ ...                                                                         │ │
│  └─────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                  │
│  ┌─ Pagination ───────────────────────────────────────────────────────────────┐ │
│  │                              ‹ Sebelumnya   1  2   Berikutnya ›             │ │
│  └──────────────────────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Table Columns

| # | Column | Key | Width | Align | Badge? | Detail |
|---|---|---|---|---|---|---|
| 1 | Nama | `name` | flex | left | No | |
| 2 | Peran | `role` | 150px | left | No | |
| 3 | Telepon | `phone` | 150px | left | No | |
| 4 | Email | `email` | 200px | left | No | "—" if null |
| 5 | Site | `site.name` | 100px | left | No | |
| 6 | Status | `is_active` | 90px | center | Yes | Active/Inactive badge |
| 7 | Aksi | — | 80px | center | No | Edit only |

### Inertia Props

```typescript
interface ContactIndexProps {
    contacts: {
        data: EmergencyContact[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
    filters: {
        search?: string;
        site_id?: number;
        is_active?: boolean;
    };
    sites: Site[];
    can: {
        create: boolean;
        update: boolean;
    };
}
```

### Contact Form (Modal or Inline)

```
┌─ Form Kontak Darurat ──────────────────────────────┐
│                                                    │
│  Nama *        [____________________________]      │
│  Peran *       [____________________________]      │
│  Telepon *     [____________________________]      │
│  Email         [____________________________]      │
│  Site *        [— Pilih Site —    ▾]               │
│  Aktif         [☑]                                 │
│                                                    │
│  [← Batal]                          [Simpan]       │
└────────────────────────────────────────────────────┘
```

---

## 10. Mobile Responsive

### Breakpoints

- **Desktop**: ≥1024px — full layout as described above
- **Tablet**: 768px–1023px — table scroll horizontal, 2-column form
- **Mobile**: <768px — single column, card-based list

### Mobile Plan Index (Card-based)

```
┌──────────────────────────────────┐
│  Rencana Darurat                 │
│  [+ Buat]   [🔍]   [⚙ Filter]   │
├──────────────────────────────────┤
│  ┌──────────────────────────────┐│
│  │ EMG-0001                     ││
│  │ 🔴 Kebakaran                 ││
│  │ Rencana Kebakaran            ││
│  │ Plant A   |  11 Jul 2026     ││
│  │ [👁 Lihat] [✏ Edit]         ││
│  └──────────────────────────────┘│
│  ┌──────────────────────────────┐│
│  │ EMG-0002                     ││
│  │ 🔵 Evakuasi                  ││
│  │ Rencana Evakuasi             ││
│  │ Plant A   |  11 Jul 2026     ││
│  │ [👁 Lihat] [✏ Edit]         ││
│  └──────────────────────────────┘│
│  ...                              │
│  ‹ 1  2  ›                        │
└──────────────────────────────────┘
```

### Mobile Drill Index (Card-based)

```
┌──────────────────────────────────┐
│  Latihan Darurat                 │
│  [+ Jadwalkan]   [🔍]   [⚙]     │
├──────────────────────────────────┤
│  ┌──────────────────────────────┐│
│  │ EMG-0005                     ││
│  │ 🟡 Terjadwal                 ││
│  │ Rencana Kebakaran            ││
│  │ Terjadwal: 15 Jul 2026       ││
│  │ [👁 Lihat]                   ││
│  └──────────────────────────────┘│
│  ┌──────────────────────────────┐│
│  │ EMG-0003                     ││
│  │ 🟢 Selesai   🟢 Lulus        ││
│  │ Rencana Kebakaran            ││
│  │ Dieksekusi: 10 Mar 2026      ││
│  │ [👁 Lihat]                   ││
│  └──────────────────────────────┘│
│  ...                              │
│  ‹ 1  2  ›                        │
└──────────────────────────────────┘
```

### Component List

| Component | File | Description |
|---|---|---|
| PlanIndex | `Pages/Modules/Emergency/Plans/Index.tsx` | Plan list page with filters |
| PlanForm | `Pages/Modules/Emergency/Plans/Form.tsx` | Plan create/edit form |
| PlanShow | `Pages/Modules/Emergency/Plans/Show.tsx` | Plan detail with contacts + linked drills |
| DrillIndex | `Pages/Modules/Emergency/Drills/Index.tsx` | Drill list page with filters |
| DrillForm | `Pages/Modules/Emergency/Drills/Form.tsx` | Drill create/edit form |
| DrillShow | `Pages/Modules/Emergency/Drills/Show.tsx` | Drill detail with execute form |
| ContactIndex | `Pages/Modules/Emergency/Contacts/Index.tsx` | Contact list with inline form |
| ContactForm | `Components/Emergency/ContactForm.tsx` | Contact create/edit modal or inline |
| EmergencyContactsEditor | `Components/Emergency/EmergencyContactsEditor.tsx` | JSON contacts editor on plan form |
| DrillCard | `Components/Emergency/DrillCard.tsx` | Card for mobile drill list view |
| PlanCard | `Components/Emergency/PlanCard.tsx` | Card for mobile plan list view |
