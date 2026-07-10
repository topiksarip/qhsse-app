# UI Pages — Environmental Management

Spesifikasi wireframe halaman UI untuk modul Environmental Management.

Semua label menggunakan Bahasa Indonesia. Tech stack: Laravel 12 + Inertia React + TypeScript + Tailwind CSS.

---

## Daftar Isi

1. [Navigasi](#1-navigasi)
2. [Color Coding](#2-color-coding)
3. [Halaman Index — Daftar Catatan Lingkungan](#3-halaman-index--daftar-catatan-lingkungan)
4. [Halaman Form — Buat/Edit Catatan](#4-halaman-form--buatedit-catatan)
5. [Halaman Show — Detail Catatan](#5-halaman-show--detail-catatan)
6. [Mobile Responsive](#6-mobile-responsive)

---

## 1. Navigasi

### Penempatan Menu

Tambahkan item menu pada group `Modul QHSSE` di `AuthenticatedLayout.tsx`, setelah item modul lain yang sudah ada.

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
            { label: 'Catatan Lingkungan', routeName: 'environmental-records.index', active: 'environmental-records.*', permission: 'environment.records.view' },
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
│                        │ Catatan Lingkungan     │                     │
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
│   Catatan Lingkungan │
│   ...                │
├──────────────────────┤
│  John Doe            │
│  john@example.com    │
└──────────────────────┘
```

### Permission Filtering

Menu hanya tampil jika user memiliki permission `environment.records.view`. Filtering dilakukan via `auth.permissions` pada layout.

---

## 2. Color Coding

### Type Badge

| Type             | Tailwind Class                                                      | Preview          |
|------------------|---------------------------------------------------------------------|------------------|
| Limbah (waste)   | `bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200` | `🟡 Limbah`      |
| Tumpahan (spill) | `bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200` | `🟠 Tumpahan`    |
| Emisi (emission) | `bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200` | `🟣 Emisi`       |
| Kebisingan (noise) | `bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200` | `🔵 Kebisingan`  |
| Monitoring Air   | `bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200`    | `🔵 Monitoring Air` |
| Lainnya (other)  | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200`     | `⚪ Lainnya`     |

### Status Badge

| Status         | Tailwind Class                                                      | Preview          |
|----------------|---------------------------------------------------------------------|------------------|
| Tercatat       | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200`    | `⚪ Tercatat`     |
| Diinvestigasi  | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 Diinvestigasi` |
| Aksi Dibuka    | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200`     | `🔵 Aksi Dibuka` |
| Ditutup        | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Ditutup`     |

### Exceedance Badge

| Condition | Tailwind Class                                                      | Preview          |
|-----------|---------------------------------------------------------------------|------------------|
| Exceedance | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 border border-red-300` | `🔴 Exceedance`  |
| Normal    | (no badge)                                                          | —                |

### Row Highlight for Exceedance

Record dengan `is_exceedance = true` disorot dengan warna MERAH di tabel Index:

```tsx
<tr className={record.is_exceedance
    ? 'bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500'
    : ''
}>
```

### Komponen Badge (Reusable)

```tsx
type BadgeColor = 'gray' | 'blue' | 'yellow' | 'green' | 'red' | 'orange' | 'purple' | 'indigo' | 'cyan' | 'amber';

function Badge({ label, color }: { label: string; color: BadgeColor }) {
    const colors: Record<BadgeColor, string> = {
        gray:   'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        blue:   'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        green:  'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        red:    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        orange: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        purple: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        indigo: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
        cyan:   'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200',
        amber:  'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
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
const typeColors: Record<string, BadgeColor> = {
    waste:            'amber',
    spill:            'orange',
    emission:         'purple',
    noise:            'indigo',
    water_monitoring: 'cyan',
    other:            'gray',
};

const typeLabels: Record<string, string> = {
    waste:            'Limbah',
    spill:            'Tumpahan',
    emission:         'Emisi',
    noise:            'Kebisingan',
    water_monitoring: 'Monitoring Air',
    other:            'Lainnya',
};

const statusColors: Record<string, BadgeColor> = {
    recorded:     'gray',
    investigated: 'yellow',
    action_open:  'blue',
    closed:       'green',
};

const statusLabels: Record<string, string> = {
    recorded:     'Tercatat',
    investigated: 'Diinvestigasi',
    action_open:  'Aksi Dibuka',
    closed:       'Ditutup',
};
```

---

## 3. Halaman Index — Daftar Catatan Lingkungan

### Route: `GET /environmental-records` (`environmental-records.index`)

### Permission: `environment.records.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Catatan Lingkungan                                    [+ Buat Catatan]        │
│  Kelola catatan lingkungan: limbah, tumpahan, emisi, kebisingan, air            │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari nomor, judul...          ]                                        │  │
│  │                                                                            │  │
│  │ Tipe:  [Semua ▾]   Status: [Semua ▾]   Exceedance: [☐ Hanya Exceedance]  │  │
│  │ Site:  [Semua ▾]   Dari: [__/__/____]  Sampai: [__/__/____]  [Reset]     │  │
│  └────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Toolbar ───────────────────────────────────────────────────────────────────┐ │
│  │ Menampilkan 1–10 dari 32 catatan                      [⬇ Export CSV]        │ │
│  └──────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                  │
│  ┌─ Table ─────────────────────────────────────────────────────────────────────┐ │
│  │ Nomor       Judul              Tipe        Nilai    Batas    Exceed  Status  │ │
│  ├─────────────────────────────────────────────────────────────────────────────┤ │
│  │ ENV-0001   Emisi Stack #1    🟣 Emisi    450 mg/m³  300 mg/m³ 🔴 Exc  ⚪Ter  │ │
│  │ ─────────  (ROW HIGHLIGHTED RED: bg-red-50 border-l-4 border-red-500) ────  │ │
│  │ ENV-0002   Spill Solar        🟠 Tumpah  50 L       —        —       ⚪Ter  │ │
│  │ ENV-0003   pH Air Limbah     🔵 Air     7.2 pH     6-9 pH   —       🟡Inv  │ │
│  │ ENV-0004   Limbah B3         🟡 Limbah   —        —        —       🔵Aksi  │ │
│  │ ENV-0005   Kebisingan Genset  🔵 Kebis   85 dB      75 dB    🔴 Exc  ⚪Ter  │ │
│  │ ─────────  (ROW HIGHLIGHTED RED: bg-red-50 border-l-4 border-red-500) ────  │ │
│  │ ENV-0006   Tumpahan Minyak   🟠 Tumpah  20 L       —        —       🟢Tut  │ │
│  │ ...                                                                         │ │
│  └─────────────────────────────────────────────────────────────────────────────┘ │
│  ┌─ Table (cont.) ────────────────────────────────────────────────────────────┐ │
│  │ ... Status    Tanggal    Site       Reporter    Aksi                       │ │
│  │ ... ⚪Ter     11/07/26   Plant A   Budi S.    [👁 Lihat]                    │ │
│  │ ... ⚪Ter     11/07/26   Plant A   Andi P.    [👁 Lihat] [✏ Edit]          │ │
│  │ ... 🟡Inv     10/07/26   Plant B   Sari W.    [👁 Lihat]                  │ │
│  │ ... 🔵Aksi    10/07/26   Plant A   Joni K.    [👁 Lihat]                  │ │
│  │ ... ⚪Ter     09/07/26   Plant A   Budi S.    [👁 Lihat] [✏ Edit]          │ │
│  │ ... 🟢Tut    08/07/26   Plant B   Andi P.    [👁 Lihat]                  │ │
│  └─────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                  │
│  ┌─ Pagination ───────────────────────────────────────────────────────────────┐ │
│  │                              ‹ Sebelumnya   1  2  3  4   Berikutnya ›       │ │
│  └──────────────────────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Wireframe — Empty State

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Catatan Lingkungan                                    [+ Buat Catatan]        │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌──────────────────────────────────────────────────────────────────────────┐   │
│  │                                                                          │   │
│  │                              🌿                                          │   │
│  │                                                                          │   │
│  │                   Belum ada catatan lingkungan                          │   │
│  │                                                                          │   │
│  │           Belum ada catatan yang dibuat. Klik tombol di bawah            │   │
│  │           untuk membuat catatan lingkungan pertama Anda.                 │   │
│  │                                                                          │   │
│  │                      [+ Buat Catatan Pertama]                           │   │
│  │                                                                          │   │
│  └──────────────────────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Element

#### Header

| Element | Type | Detail |
|---|---|---|
| Title | `<h1>` | "Catatan Lingkungan" |
| Subtitle | `<p>` | "Kelola catatan lingkungan: limbah, tumpahan, emisi, kebisingan, air" |
| Button "Buat Catatan" | `<Link>` | Route: `environmental-records.create`, permission: `environment.records.create` |
| Button Style | Tailwind | `bg-green-600 text-white hover:bg-green-700` |

#### Filter Bar

| Filter | Label | Options | Param |
|---|---|---|---|
| Search | "Cari nomor, judul..." | Text input with debounce | `?search=` |
| Tipe | "Tipe" | Semua, Limbah, Tumpahan, Emisi, Kebisingan, Monitoring Air, Lainnya | `?type=` |
| Status | "Status" | Semua, Tercatat, Diinvestigasi, Aksi Dibuka, Ditutup | `?status=` |
| Exceedance | "Hanya Exceedance" | Checkbox | `?exceedance_only=1` |
| Site | "Site" | Semua + from master Sites | `?site_id=` |
| Date Range | "Dari" / "Sampai" | Date pickers | `?from=` `?to=` |
| Reset | "Reset" | Clear all filters | — |

#### Table Columns

| # | Column | Key | Width | Align | Badge? | Detail |
|---|---|---|---|---|---|---|
| 1 | Nomor | `record_number` | 120px | left | No | Link ke Show page, monospace |
| 2 | Judul | `title` | flex | left | No | Truncate `max-w-xs truncate` |
| 3 | Tipe | `type` | 120px | left | Yes | Type badge with label |
| 4 | Nilai | `measured_value` + `unit` | 120px | right | No | Format: "450 mg/m³" or "—" |
| 5 | Batas | `limit_value` + `unit` | 120px | right | No | Format: "300 mg/m³" or "—" |
| 6 | Exceedance | `is_exceedance` | 90px | center | Yes | 🔴 Exceedance or blank |
| 7 | Status | `status` | 130px | center | Yes | Status badge |
| 8 | Tanggal | `occurred_at` | 100px | center | No | Format: `dd/mm/yy` |
| 9 | Site | `site.name` | 100px | left | No | |
| 10 | Reporter | `reporter.name` | 120px | left | No | |
| 11 | Aksi | — | 120px | center | No | See below |

#### Row Exceedance Highlight

```tsx
{records.data.map((record) => (
    <tr
        key={record.id}
        className={record.is_exceedance
            ? 'bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500'
            : 'hover:bg-gray-50 dark:hover:bg-gray-800'
        }
    >
```

#### Aksi Column (per row)

| Action | Icon | Permission | Condition |
|---|---|---|---|
| Lihat | 👁 | `environment.records.view` | Selalu tampil |
| Edit | ✏ | `environment.records.update` | Status = `recorded` atau `investigated` |

#### Pagination

```
Menampilkan 1–10 dari 32 catatan

‹ Sebelumnya   1  2  3  4   Berikutnya ›
```

- 15 item per halaman (configurable: 10/25/50).

#### Export CSV

| Element | Detail |
|---|---|
| Button | `[⬇ Export CSV]` |
| Permission | `environment.records.export` |
| Endpoint | `GET /environmental-records/export?type=...&status=...&...` |

### Inertia Props

```typescript
interface IndexProps {
    records: {
        data: EnvironmentalRecord[];
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
        exceedance_only?: boolean;
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

## 4. Halaman Form — Buat/Edit Catatan

### Route

- Create: `GET /environmental-records/create` (`environmental-records.create`)
- Edit: `GET /environmental-records/{record}/edit` (`environmental-records.edit`)

### Permission

- Create: `environment.records.create`
- Edit: `environment.records.update` (hanya jika status `recorded` atau `investigated`)

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Buat Catatan Lingkungan                                                         │
│  Isi data catatan lingkungan dengan lengkap                                     │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Umum ──────────────────────────────────────────────────┐  │
│  │  INFORMASI UMUM                                                             │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Nomor Catatan       [Auto-generated — ENV-0001              ]  ⓘ          │  │
│  │                       Nomor akan dibuat otomatis saat simpan                 │  │
│  │                                                                             │  │
│  │  Tipe Catatan *      [— Pilih Tipe —           ▾]                           │  │
│  │                        ○ Limbah   ○ Tumpahan   ○ Emisi                     │  │
│  │                        ○ Kebisingan  ○ Monitoring Air  ○ Lainnya          │  │
│  │                                                                             │  │
│  │  Judul *             [Masukkan judul catatan...               ]             │  │
│  │                                                                             │  │
│  │  Deskripsi *         ┌──────────────────────────────────────────────┐      │  │
│  │                       │ Jelaskan detail pengamatan lingkungan...     │      │  │
│  │                       │                                              │      │  │
│  │                       └──────────────────────────────────────────────┘      │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Lokasi ─────────────────────────────────────────────────────────┐  │
│  │  LOKASI                                                                     │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Site *                [— Pilih Site —    ▾]                                │  │
│  │                                                                             │  │
│  │  Area                  [— Pilih Area —    ▾]    (filtered by site)          │  │
│  │                                                                             │  │
│  │  Tanggal Kejadian     [__/__/____] [🕐]  (opsional)                        │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Detail Pengukuran (dinamis berdasarkan tipe) ───────────────────┐  │
│  │  DETAIL PENGUKURAN                                                         │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  ╔══ IF type = waste (Limbah) ═════════════════════════════════════════╗   │  │
│  │  ║  Jenis Limbah *     [Limbah B3 / Non-B3 / Medis...       ]         ║   │  │
│  │  ║  Jumlah *           [______]  Satuan: [kg / liter / m³ ▾]         ║   │  │
│  │  ║  Metode Pembuangan *[Incinerasi / TPA / Pihak Ketiga... ]         ║   │  │
│  │  ╚════════════════════════════════════════════════════════════════════╝   │  │
│  │                                                                             │  │
│  │  ╔══ IF type = spill (Tumpahan) ═══════════════════════════════════════╗   │  │
│  │  ║  Material *         [Minyak / Kimia / Solar...          ]          ║   │  │
│  │  ║  Volume *           [______]  Satuan: [liter / m³ ▾]               ║   │  │
│  │  ║  Penahanan *        [Boom oil / Absorbent / Dike...      ]         ║   │  │
│  │  ╚════════════════════════════════════════════════════════════════════╝   │  │
│  │                                                                             │  │
│  │  ╔══ IF type = emission (Emisi) ══════════════════════════════════════╗   │  │
│  │  ║  Parameter *        [SOx / NOx / CO / PM10...           ]          ║   │  │
│  │  ║  Nilai Terukur *    [______]  Satuan: [mg/m³ ▾]                   ║   │  │
│  │  ║  Batas *            [______]  (Nilai batas regulasi)              ║   │  │
│  │  ║  ⓘ Exceedance akan otomatis terdeteksi jika nilai > batas          ║   │  │
│  │  ╚════════════════════════════════════════════════════════════════════╝   │  │
│  │                                                                             │  │
│  │  ╔══ IF type = noise (Kebisingan) ════════════════════════════════════╗   │  │
│  │  ║  Tingkat Kebisingan * [______]  dB   (measured_value + unit=dB)  ║   │  │
│  │  ║  Lokasi Pengukuran * [Stack area / Genset room / ...   ]          ║   │  │
│  │  ║  Waktu Pengukuran *  [__/__/____] [__:__]  (occurred_at)          ║   │  │
│  │  ║  Batas *             [______]  dB   (limit_value)                ║   │  │
│  │  ║  ⓘ Exceedance akan otomatis terdeteksi jika nilai > batas          ║   │  │
│  │  ╚════════════════════════════════════════════════════════════════════╝   │  │
│  │                                                                             │  │
│  │  ╔══ IF type = water_monitoring (Monitoring Air) ═════════════════════╗   │  │
│  │  ║  Parameter *        [pH / TSS / BOD / COD / Logam Berat...]       ║   │  │
│  │  ║  Nilai Terukur *    [______]  Satuan: [mg/L / pH ▾]              ║   │  │
│  │  ║  Batas *            [______]  (Nilai batas regulasi)              ║   │  │
│  │  ║  ⓘ Exceedance akan otomatis terdeteksi jika nilai > batas          ║   │  │
│  │  ╚════════════════════════════════════════════════════════════════════╝   │  │
│  │                                                                             │  │
│  │  ╔══ IF type = other (Lainnya) ═══════════════════════════════════════╗   │  │
│  │  ║  Nilai Terukur      [______]  (opsional)                          ║   │  │
│  │  ║  Satuan             [______]  (opsional)                          ║   │  │
│  │  ║  Batas              [______]  (opsional)                           ║   │  │
│  │  ╚════════════════════════════════════════════════════════════════════╝   │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Evidence ───────────────────────────────────────────────────────┐  │
│  │  EVIDENCE                                                                  │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │  ┌─────────────────────────────────────────────────────────────────────┐   │  │
│  │  │                                                                     │   │  │
│  │  │              📁  Drag & drop file di sini                           │   │  │
│  │  │                  atau [Pilih File]                                  │   │  │
│  │  │                                                                     │   │  │
│  │  │              Maks 25MB per file. Format: jpg, png, pdf, docx        │   │  │
│  │  │                                                                     │   │  │
│  │  └─────────────────────────────────────────────────────────────────────┘   │  │
│  │  File terunggah:                                                           │  │
│  │  ┌──────────────────────────────────────────────────────────────────┐     │  │
│  │  │ 📎 foto_spill_area.jpg                          2.3 MB   [🗑]    │     │  │
│  │  └──────────────────────────────────────────────────────────────────┘     │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Action Bar (sticky bottom) ──────────────────────────────────────────────┐  │
│  │                                                                           │  │
│  │  [← Batal]                                                [Simpan]       │  │
│  │                                                           (primary)       │  │
│  └───────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Type-Specific Field Visibility Logic

```tsx
{watch('type') === 'waste' && (
    <>
        <Input label="Jenis Limbah *" name="waste_type" required />
        <Input label="Jumlah *" name="quantity" type="number" required />
        <Select label="Satuan" name="unit" options={['kg', 'liter', 'm³']} />
        <Input label="Metode Pembuangan *" name="disposal_method" required />
    </>
)}

{watch('type') === 'spill' && (
    <>
        <Input label="Material *" name="material" required />
        <Input label="Volume *" name="volume" type="number" required />
        <Select label="Satuan" name="unit" options={['liter', 'm³', ' barrel']} />
        <Input label="Penahanan *" name="containment" required />
    </>
)}

{watch('type') === 'emission' && (
    <>
        <Input label="Parameter *" name="parameter" required placeholder="SOx, NOx, CO, PM10" />
        <Input label="Nilai Terukur *" name="measured_value" type="number" step="0.0001" required />
        <Select label="Satuan" name="unit" options={['mg/m³', 'ppm', 'µg/m³']} />
        <Input label="Batas *" name="limit_value" type="number" step="0.0001" required />
        <p className="text-xs text-gray-500">
            ⓘ Exceedance akan otomatis terdeteksi jika nilai terukur melebihi batas
        </p>
    </>
)}

{watch('type') === 'noise' && (
    <>
        <Input label="Tingkat Kebisingan *" name="measured_value" type="number" required suffix="dB" />
        <input type="hidden" name="unit" value="dB" />
        <Input label="Lokasi Pengukuran *" name="location" required />
        <DateTimePicker label="Waktu Pengukuran *" name="occurred_at" required />
        <Input label="Batas *" name="limit_value" type="number" suffix="dB" required />
    </>
)}

{watch('type') === 'water_monitoring' && (
    <>
        <Input label="Parameter *" name="parameter" required placeholder="pH, TSS, BOD, COD" />
        <Input label="Nilai Terukur *" name="measured_value" type="number" step="0.0001" required />
        <Select label="Satuan" name="unit" options={['mg/L', 'pH', 'µS/cm']} />
        <Input label="Batas *" name="limit_value" type="number" step="0.0001" required />
    </>
)}

{watch('type') === 'other' && (
    <>
        <Input label="Nilai Terukur" name="measured_value" type="number" step="0.0001" />
        <Input label="Satuan" name="unit" />
        <Input label="Batas" name="limit_value" type="number" step="0.0001" />
    </>
)}
```

### Inertia Props

```typescript
interface FormProps {
    record: EnvironmentalRecord | null; // null for create
    sites: Site[];
    areas: Area[];
    can: {
        create: boolean;
        update: boolean;
    };
}
```

---

## 5. Halaman Show — Detail Catatan

### Route: `GET /environmental-records/{record}` (`environmental-records.show`)

### Permission: `environment.records.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  ← Kembali    ENV-2026-0001 — Emisi Stack #1    [✏ Edit] [⬇ Export]            │
│  📋 Catatan Lingkungan                                                           │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Umum ──────────────────────────────────────────────────┐  │
│  │  INFORMASI UMUM                                                             │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │  Nomor           : ENV-2026-0001                                            │  │
│  │  Tipe            : 🟣 Emisi                                                 │  │
│  │  Judul           : Emisi Stack #1                                           │  │
│  │  Status          : ⚪ Tercatat                                               │  │
│  │  Exceedance      : 🔴 EXCEEDANCE                                            │  │
│  │  Tanggal Kejadian: 11 Juli 2026, 14:30                                      │  │
│  │  Dibuat Oleh     : Budi Santoso                                             │  │
│  │  Dibuat Pada     : 11 Juli 2026, 15:00                                      │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Pengukuran ─────────────────────────────────────────────────────┐  │
│  │  PENGUKURAN                                                                │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │  Parameter       : SOx                                                     │  │
│  │  Nilai Terukur   : 450 mg/m³                                               │  │
│  │  Batas           : 300 mg/m³                                               │  │
│  │  Exceedance      : 🔴 YA — Nilai melebihi batas sebesar 150 mg/m³         │  │
│  │                                                                             │  │
│  │  ┌─ Exceedance Alert Box ───────────────────────────────────────────────┐ │  │
│  │  │ ⚠ EXCEEDANCE TERDETEKSI                                               │ │  │
│  │  │ Nilai terukur (450 mg/m³) melebihi batas regulasi (300 mg/m³).       │ │  │
│  │  │ Selisih: 150 mg/m³ (50% di atas batas)                                │ │  │
│  │  │ Disarankan untuk membuka CAPA dan melakukan investigasi.              │ │  │
│  │  └────────────────────────────────────────────────────────────────────┘ │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Lokasi ─────────────────────────────────────────────────────────┐  │
│  │  LOKASI                                                                     │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │  Site  : Plant A                                                            │  │
│  │  Area  : Area Produksi                                                     │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Deskripsi ──────────────────────────────────────────────────────┐  │
│  │  DESKRIPSI                                                                  │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │  Pengukuran emisi SOx dari stack #1 dilakukan pada 11 Juli 2026.          │  │
│  │  Hasil pengukuran menunjukkan nilai 450 mg/m³, melebihi batas             │  │
│  │  regulasi 300 mg/m³.                                                       │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: CAPA Terkait ──────────────────────────────────────────────────┐  │
│  │  CAPA TERKAIT                                                               │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │  ┌─────────────────────────────────────────────────────────────────────┐   │  │
│  │  │ 🔵 ACT-2026-0012    Status: In Progress                              │   │  │
│  │  │ Investigasi emisi SOx — instalasi scrubber                           │   │  │
│  │  │                              [👁 Lihat CAPA →]                        │   │  │
│  │  └─────────────────────────────────────────────────────────────────────┘   │  │
│  │  — atau —                                                                  │  │
│  │  Belum ada CAPA terkait.                                                   │  │
│  │  [+ Buka CAPA]  (permission: environment.records.investigate)              │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Evidence ───────────────────────────────────────────────────────┐  │
│  │  EVIDENCE                                                                  │  │
│  │  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐        │  │
│  │  │  📷              │  │  📄              │  │  📷              │        │  │
│  │  │  stack_photo.jpg │  │  lab_report.pdf  │  │  monitoring.jpg  │        │  │
│  │  │  2.3 MB          │  │  850 KB          │  │  1.1 MB          │        │  │
│  │  │  [⬇ Download]    │  │  [⬇ Download]    │  │  [⬇ Download]    │        │  │
│  │  └──────────────────┘  └──────────────────┘  └──────────────────┘        │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Aktivitas & Komentar ──────────────────────────────────────────┐  │
│  │  AKTIVITAS & KOMENTAR                                                      │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │  [Tab: Timeline]  [Tab: Komentar]                                         │  │
│  │                                                                             │  │
│  │  ◉ 11 Jul 15:00 — Budi Santoso membuat catatan lingkungan                 │  │
│  │  ◉ 11 Jul 15:01 — Exceedance terdeteksi: nilai 450 > batas 300            │  │
│  │  ◉ 11 Jul 15:05 — Budi Santoso mengupload file: stack_photo.jpg          │  │
│  │                                                                             │  │
│  │  ┌─ Add Comment ─────────────────────────────────────────────────────┐     │  │
│  │  │ [Tulis komentar...                                        ]      │     │  │
│  │  │                                                    [Kirim]      │     │  │
│  │  └────────────────────────────────────────────────────────────────────┘     │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Action Bar (bottom, contextual) ────────────────────────────────────────┐  │
│  │  Status: ⚪ Tercatat                                                        │  │
│  │  Available actions:                                                        │  │
│  │  [Mulai Investigasi]  (permission: environment.records.investigate)        │  │
│  │  [Tutup Langsung]     (permission: environment.records.close)              │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Exceedance Alert Box (shown only if `is_exceedance = true`)

```tsx
{record.is_exceedance && (
    <div className="rounded-lg border-2 border-red-300 bg-red-50 dark:bg-red-900/20 p-4">
        <div className="flex items-start">
            <span className="text-2xl mr-3">⚠</span>
            <div>
                <h3 className="text-lg font-bold text-red-800 dark:text-red-200">
                    EXCEEDANCE TERDETEKSI
                </h3>
                <p className="text-sm text-red-700 dark:text-red-300">
                    Nilai terukur ({record.measured_value} {record.unit}) melebihi batas
                    regulasi ({record.limit_value} {record.unit}).
                </p>
                <p className="text-sm text-red-700 dark:text-red-300 mt-1">
                    Selisih: {(record.measured_value - record.limit_value)} {record.unit}
                    ({(((record.measured_value - record.limit_value) / record.limit_value) * 100).toFixed(1)}% di atas batas)
                </p>
                <p className="text-xs text-red-600 dark:text-red-400 mt-2">
                    Disarankan untuk membuka CAPA dan melakukan investigasi.
                </p>
            </div>
        </div>
    </div>
)}
```

### CAPA Section

```tsx
{record.capa_action ? (
    <div className="border rounded-lg p-4 flex items-center justify-between">
        <div>
            <Badge label={record.capa_action.status} color={capaStatusColors[record.capa_action.status]} />
            <span className="ml-2 font-mono text-sm">{record.capa_action.number}</span>
            <p className="mt-1 text-sm text-gray-600">{record.capa_action.title}</p>
        </div>
        <Link href={route('capa.actions.show', record.capa_action.id)}>
            👁 Lihat CAPA →
        </Link>
    </div>
) : (
    <div>
        <p className="text-sm text-gray-500">Belum ada CAPA terkait.</p>
        {can.investigate && record.status === 'investigated' && (
            <button className="mt-2 px-4 py-2 bg-blue-600 text-white rounded">
                + Buka CAPA
            </button>
        )}
    </div>
)}
```

### Inertia Props

```typescript
interface ShowProps {
    record: EnvironmentalRecord & {
        site: Site;
        area: Area | null;
        reporter: User;
        capaAction: CapaAction | null;
    };
    evidence: ManagedFile[];
    comments: Comment[];
    activities: ActivityLog[];
    can: {
        update: boolean;
        investigate: boolean;
        close: boolean;
        export: boolean;
    };
    availableTransitions: {
        action: string;
        label: string;
        permission: string;
        requires_reason: boolean;
    }[];
}
```

---

## 6. Mobile Responsive

### Breakpoints

- **Desktop**: ≥1024px — full layout as described above
- **Tablet**: 768px–1023px — table scroll horizontal, 2-column form
- **Mobile**: <768px — single column, card-based list

### Mobile Index (Card-based)

```
┌──────────────────────────────────┐
│  Catatan Lingkungan              │
│  [+ Buat]   [🔍]   [⚙ Filter]   │
├──────────────────────────────────┤
│  ┌──────────────────────────────┐│
│  │ ENV-0001                    ││
│  │ 🟣 Emisi   🔴 Exceedance    ││
│  │ Emisi Stack #1              ││
│  │ 450 mg/m³ > 300 mg/m³       ││
│  │ Plant A   |  11 Jul 2026    ││
│  │ ⚪ Tercatat    [👁 Lihat]   ││
│  └──────────────────────────────┘│
│  (red left border if exceedance) │
│  ┌──────────────────────────────┐│
│  │ ENV-0002                    ││
│  │ 🟠 Tumpahan                 ││
│  │ Spill Solar                  ││
│  │ 50 L                         ││
│  │ Plant A   |  11 Jul 2026    ││
│  │ ⚪ Tercatat    [👁 Lihat]   ││
│  └──────────────────────────────┘│
│  ...                             │
│  ‹ 1  2  3  ›                    │
└──────────────────────────────────┘
```

### Mobile Form

- Single column layout
- Type-specific fields show/hide with animation
- Evidence upload area full width
- Action bar sticky bottom

### Mobile Show

- All sections stacked vertically
- Exceedance alert box full width
- Evidence grid → 2 columns
- Action buttons full width

### Component List

| Component | File | Description |
|---|---|---|
| Index | `Pages/Modules/Environmental/Index.tsx` | List page with filters |
| Form | `Pages/Modules/Environmental/Form.tsx` | Create/Edit form with dynamic fields |
| Show | `Pages/Modules/Environmental/Show.tsx` | Detail page with CAPA link |
| TypeFields | `Components/Environmental/TypeFields.tsx` | Dynamic type-specific field renderer |
| ExceedanceBadge | `Components/Environmental/ExceedanceBadge.tsx` | Exceedance badge component |
| ExceedanceAlert | `Components/Environmental/ExceedanceAlert.tsx` | Alert box for exceedance on Show page |
| RecordCard | `Components/Environmental/RecordCard.tsx` | Card for mobile list view |
