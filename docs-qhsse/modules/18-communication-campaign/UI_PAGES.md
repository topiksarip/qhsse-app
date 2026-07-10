# UI Pages — Communication & Campaign

Spesifikasi wireframe halaman UI untuk modul Communication & Campaign.

Semua label menggunakan Bahasa Indonesia. Tech stack: Laravel 12 + Inertia React + TypeScript + Tailwind CSS.

---

## Daftar Isi

1. [Navigasi](#1-navigasi)
2. [Color Coding](#2-color-coding)
3. [Halaman Index — Daftar Kampanye](#3-halaman-index)
4. [Halaman Form — Buat/Edit Kampanye](#4-halaman-form)
5. [Halaman Show — Detail Kampanye](#5-halaman-show)
6. [Mobile Responsive](#6-mobile-responsive)

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
            // ... existing module items (Incident, CAPA, Training, etc.)
            { label: 'Kampanye Komunikasi', routeName: 'communication.campaigns.index', active: 'communication.campaigns.*', permission: 'communication.campaigns.view' },
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
│                        │ Record Pelatihan          │                 │
│                        │ Kampanye Komunikasi  ◄──  │                 │
│                        │ ...                       │                 │
│                        └──────────────────────────┘                 │
└──────────────────────────────────────────────────────────────────────┘
```

### Permission Filtering

Menu hanya tampil jika user memiliki permission `communication.campaigns.view`. Filtering dilakukan via `auth.permissions` pada layout.

---

## 2. Color Coding

### Status Badge

| Status | Tailwind Class | Preview |
|---|---|---|
| Draft | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `🟡 Draft` |
| Published | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `🟢 Published` |

### Type Badge

| Type | Tailwind Class | Preview |
|---|---|---|
| Safety Alert | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `🔴 Safety Alert` |
| Lesson Learned | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | `🔵 Lesson Learned` |
| Campaign | `bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200` | `🟣 Kampanye` |
| Announcement | `bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200` | `🔵 Pengumuman` |
| Newsletter | `bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200` | `🟢 Newsletter` |

### Target Audience Badge

| Target Audience | Tailwind Class | Preview |
|---|---|---|
| Semua Karyawan | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | `👥 Semua` |
| Site Tertentu | `bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200` | `🏢 Site` |
| Departemen Tertentu | `bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200` | `📁 Departemen` |
| Role Tertentu | `bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200` | `👤 Role` |

### Expiry Indicator

| Condition | Tailwind Class (row) | Preview |
|---|---|---|
| Expired (`expires_at < now()`) | `bg-red-50 dark:bg-red-900/20` + `border-l-4 border-red-500` | Row with left red border |
| Expiring ≤ 7 days | `bg-orange-50 dark:bg-orange-900/20` + `border-l-4 border-orange-500` | Row with left orange border |

### Acknowledgment Status (per user)

| Status | Tailwind Class | Preview |
|---|---|---|
| Acknowledged | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `✅ Dikonfirmasi` |
| Pending (safety_alert) | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `⚠ Belum Dikonfirmasi` |
| N/A (newsletter) | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | `— N/A` |

### Komponen Badge (Reusable)

```tsx
// Komponen: components/Badge.tsx
type BadgeColor = 'gray' | 'blue' | 'yellow' | 'green' | 'red' | 'orange' | 'purple' | 'indigo' | 'teal' | 'cyan' | 'amber' | 'pink';

function Badge({ label, color }: { label: string; color: BadgeColor }) {
    const colors: Record<BadgeColor, string> = {
        gray:    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        blue:    'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        yellow:  'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        green:   'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        red:     'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        orange:  'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        purple:  'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        indigo:  'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
        teal:    'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200',
        cyan:    'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200',
        amber:   'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
        pink:    'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200',
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
// utils/campaignBadgeColors.ts

const statusColors: Record<string, BadgeColor> = {
    draft:     'yellow',
    published: 'green',
};

const typeColors: Record<string, BadgeColor> = {
    safety_alert:    'red',
    lesson_learned:  'blue',
    campaign:        'purple',
    announcement:    'indigo',
    newsletter:      'teal',
};

const typeLabels: Record<string, string> = {
    safety_alert:    'Safety Alert',
    lesson_learned:  'Lesson Learned',
    campaign:        'Kampanye',
    announcement:     'Pengumuman',
    newsletter:      'Newsletter',
};

const targetAudienceColors: Record<string, BadgeColor> = {
    all:                  'gray',
    specific_site:        'cyan',
    specific_department:  'amber',
    specific_role:        'pink',
};

const targetAudienceLabels: Record<string, string> = {
    all:                  'Semua Karyawan',
    specific_site:        'Site Tertentu',
    specific_department:  'Departemen Tertentu',
    specific_role:        'Role Tertentu',
};
```

---

## 3. Halaman Index

### Route: `GET /campaigns` (`communication.campaigns.index`)

### Permission: `communication.campaigns.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Kampanye Komunikasi                                  [+ Buat Kampanye]         │
│  Kelola safety alert, pengumuman, dan kampanye QHSSE                            │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────────┐ │
│  │ [🔍 Cari nomor, judul kampanye...             ]                             │ │
│  │                                                                              │ │
│  │ Tipe: [Semua ▾]   Status: [Semua ▾]   [Reset]                               │ │
│  └──────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                  │
│  ┌─ Toolbar ───────────────────────────────────────────────────────────────────┐ │
│  │ Menampilkan 1–15 dari 42 kampanye                       [⬇ Export CSV]      │ │
│  └──────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                  │
│  ┌─ Table ──────────────────────────────────────────────────────────────────────┐│
│  │ Nomor          Judul                    Tipe           Status    Views  Ack ││
│  ├──────────────────────────────────────────────────────────────────────────────┤│
│  │ COM-2026-0001  Safety Alert: Kebakaran   🔴Safety Alert 🟡Draft    0    —   ││
│  │ COM-2026-0002  Lesson Learned: Slip      🔵Lesson Learn🟢Published 45   32  ││
│  │ COM-2026-0003  Zero Accident Month       🟣Kampanye     🟢Published 128  —  ││
│  │ COM-2026-0004  Perubahan Prosedur         🔵Pengumuman   🟢Published 67   —  ││
│  │ COM-2026-0005  Buletin QHSSE Q2           🟢Newsletter  🟢Published 89   —  ││
│  └──────────────────────────────────────────────────────────────────────────────┘│
│  │                                                                      Aksi    ││
│  │                                                   [👁 Lihat] [✏ Edit]      ││
│  │                                                   [👁 Lihat] [📢 Publish]  ││
│  │                                                   [👁 Lihat]                ││
│  │                                                   [👁 Lihat]                ││
│  │                                                   [👁 Lihat]                ││
│  └──────────────────────────────────────────────────────────────────────────────┘│
│                                                                                  │
│  ┌─ Pagination ───────────────────────────────────────────────────────────────┐ │
│  │                              ‹ Sebelumnya   1  2  3   Berikutnya ›          │ │
│  └──────────────────────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Wireframe — Empty State

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│                                                                                  │
│  ┌──────────────────────────────────────────────────────────────────────────┐    │
│  │                                                                          │    │
│  │                              📢                                         │    │
│  │                                                                          │    │
│  │                   Belum ada kampanye komunikasi                          │    │
│  │                                                                          │    │
│  │           Belum ada kampanye yang dibuat. Klik tombol di bawah           │    │
│  │             untuk membuat kampanye pertama.                              │    │
│  │                                                                          │    │
│  │                      [+ Buat Kampanye Pertama]                          │    │
│  │                                                                          │    │
│  └──────────────────────────────────────────────────────────────────────────┘    │
│                                                                                  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Element

#### Header

| Element | Type | Detail |
|---|---|---|
| Title | `<h1>` | "Kampanye Komunikasi" |
| Subtitle | `<p>` | "Kelola safety alert, pengumuman, dan kampanye QHSSE" |
| Button "Buat Kampanye" | `<Link>` | Route: `communication.campaigns.create`, permission: `communication.campaigns.create` |
| Button Style | Tailwind | `bg-blue-600 text-white hover:bg-blue-700` |

#### Filter Dropdowns

| Filter | Label | Options | Param |
|---|---|---|---|
| Search | "Cari nomor, judul kampanye..." | Free text | `?search=` |
| Tipe | "Tipe" | Semua, Safety Alert, Lesson Learned, Kampanye, Pengumuman, Newsletter | `?type=` |
| Status | "Status" | Semua, Draft, Published | `?status=` |

#### Table Columns

| # | Column | Key | Width | Align | Badge? | Detail |
|---|---|---|---|---|---|---|
| 1 | Nomor | `campaign_number` | 130px | left | No | Link ke show page, monospace |
| 2 | Judul | `title` | flex | left | No | Truncate dengan `max-w-xs truncate` |
| 3 | Tipe | `type` | 130px | center | Yes | See Type Badge |
| 4 | Status | `status` | 110px | center | Yes | See Status Badge |
| 5 | Views | `view_count` | 80px | center | No | Integer |
| 6 | Ack | `acknowledgments_count` | 80px | center | No | Count or `—` if no acknowledgment |
| 7 | Aksi | — | 150px | center | No | See below |

#### Aksi Column (per row)

| Action | Icon | Permission | Condition |
|---|---|---|---|
| Lihat | 👁 | `communication.campaigns.view` | Selalu tampil |
| Edit | ✏ | `communication.campaigns.update` | Hanya jika status = `draft` |
| Publish | 📢 | `communication.campaigns.publish` | Hanya jika status = `draft` |

---

## 4. Halaman Form

### Route

- Create: `GET /campaigns/create` (`communication.campaigns.create`)
- Edit: `GET /campaigns/{campaign}/edit` (`communication.campaigns.edit`)

### Permission

- Create: `communication.campaigns.create`
- Edit: `communication.campaigns.update`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                               │
│  Buat Kampanye Komunikasi                                                            │
│  Buat safety alert, pengumuman, atau kampanye QHSSE baru                            │
├──────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                      │
│  ┌─ Section: Informasi Kampanye ──────────────────────────────────────────────────┐  │
│  │  INFORMASI KAMPANYE                                                             │  │
│  │  ─────────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                                 │  │
│  │  Nomor Kampanye       [Auto-generated — COM-2026-0006        ]  ⓘ              │  │
│  │                        Nomor akan dibuat otomatis saat simpan                   │  │
│  │                                                                                 │  │
│  │  Judul *              [Safety Alert: Kebocoran Pipa            ]                │  │
│  │                        Judul kampanye (max 255 karakter)                        │  │
│  │                                                                                 │  │
│  │  Tipe *               [— Pilih Tipe —                ▾]                       │  │
│  │                        ○ Safety Alert  ○ Lesson Learned  ○ Kampanye          │  │
│  │                        ○ Pengumuman     ○ Newsletter                           │  │
│  │                                                                                 │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                      │
│  ┌─ Section: Konten ──────────────────────────────────────────────────────────────┐  │
│  │  KONTEN                                                                         │  │
│  │  ─────────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                                 │  │
│  │  ┌──────────────────────────────────────────────────────────────────────────┐  │  │
│  │  │ [B] [I] [U] [H1] [H2] [List] [Link] [Image] [Table] [Code]              │  │  │
│  │  ├──────────────────────────────────────────────────────────────────────────┤  │  │
│  │  │                                                                          │  │  │
│  │  │  Tulis konten kampanye di sini...                                       │  │  │
│  │  │                                                                          │  │  │
│  │  │                                                                          │  │  │
│  │  │  Deskripsikan safety alert, lesson learned, atau pengumuman dengan      │  │  │
│  │  │  detail yang jelas. Sertakan:                                           │  │  │
│  │  │  - Kronologi kejadian                                                    │  │  │
│  │  │  - Dampak/risk                                                          │  │  │
│  │  │  - Tindakan pencegahan                                                  │  │  │
│  │  │  - Kontak untuk pertanyaan                                              │  │  │
│  │  │                                                                          │  │  │
│  │  └──────────────────────────────────────────────────────────────────────────┘  │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                      │
│  ┌─ Section: Target Audiens ───────────────────────────────────────────────────────┐  │
│  │  TARGET AUDIENS                                                                 │  │
│  │  ─────────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                                 │  │
│  │  Target Audiens *     [— Pilih Target Audiens —    ▾]                          │  │
│  │                        ○ Semua Karyawan                                        │  │
│  │                        ○ Site Tertentu                                         │  │
│  │                        ○ Departemen Tertentu                                   │  │
│  │                        ○ Role Tertentu                                         │  │
│  │                                                                                 │  │
│  │  ── Conditional Fields (muncul berdasarkan pilihan target_audience) ──        │  │
│  │                                                                                 │  │
│  │  [Jika specific_site:]                                                          │  │
│  │  Site *               [— Pilih Site —              ▾]                         │  │
│  │                        Site target kampanye                                     │  │
│  │                                                                                 │  │
│  │  [Jika specific_department:]                                                    │  │
│  │  Departemen *         [— Pilih Departemen —        ▾]                         │  │
│  │                        Departemen target kampanye                               │  │
│  │                                                                                 │  │
│  │  [Jika specific_role:]                                                          │  │
│  │  Role *               [— Pilih Role —              ▾]                         │  │
│  │                        ○ QHSSE Manager ○ QHSSE Officer ○ Supervisor          │  │
│  │                        ○ Department Head ○ Employee / Reporter                │  │
│  │                        ○ Contractor ○ Auditor ○ Top Management                │  │
│  │                                                                                 │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                      │
│  ┌─ Section: Lampiran & Jadwal ────────────────────────────────────────────────────┐  │
│  │  LAMPIRAN & JADWAL                                                              │  │
│  │  ─────────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                                 │  │
│  │  Lampiran             [📁 Drag & drop atau klik untuk upload]                  │  │
│  │                        PDF, JPG, PNG, DOC, DOCX, XLS, XLSX, PPT, PPTX            │  │
│  │                        Max 10 MB per file, max 5 file                          │  │
│  │                                                                                 │  │
│  │  Kedaluwarsa Pada     [__/__/____ __:__]  (opsional)                           │  │
│  │                        Tanggal kedaluwarsa kampanye. Kosongkan jika tidak ada.  │  │
│  │                                                                                 │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                      │
│  ┌─ Action Bar (sticky bottom) ──────────────────────────────────────────────────┐  │
│  │                                                                               │  │
│  │  [← Batal]                                              [Simpan Draft]       │  │
│  │                                                          (primary)             │  │
│  └───────────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Form Fields

| # | Field | Type | Validation | Label (ID) | Notes |
|---|---|---|---|---|---|
| 1 | `campaign_number` | readonly text | — | "Nomor Kampanye" | Auto-generated, display only on create |
| 2 | `title` | text input | required, max:255 | "Judul" | |
| 3 | `type` | select dropdown | required, in:list | "Tipe" | 5 options |
| 4 | `content` | rich text editor | required, string | "Konten" | TipTap or similar |
| 5 | `target_audience` | radio/select | required, in:list | "Target Audiens" | 4 options, drives conditional fields |
| 6 | `site_id` | select dropdown | required_if target_audience=specific_site, exists:sites,id | "Site" | Only shown if target_audience = specific_site |
| 7 | `department_id` | select dropdown | required_if target_audience=specific_department, exists:departments,id | "Departemen" | Only shown if target_audience = specific_department |
| 8 | `target_role` | select dropdown | required_if target_audience=specific_role, in:roles | "Role" | Only shown if target_audience = specific_role |
| 9 | `attachments[]` | file upload | nullable, array, max:5 files, each mimes + max:10240 | "Lampiran" | Multi-file upload |
| 10 | `expires_at` | datetime picker | nullable, date, after:now | "Kedaluwarsa Pada" | Optional |

### Conditional Field Logic

```typescript
// React component logic
const [targetAudience, setTargetAudience] = useState<string>('all');

// Show site_id field only when target_audience === 'specific_site'
{targetAudience === 'specific_site' && (
    <SelectField label="Site *" name="site_id" options={sites} />
)}

// Show department_id field only when target_audience === 'specific_department'
{targetAudience === 'specific_department' && (
    <SelectField label="Departemen *" name="department_id" options={departments} />
)}

// Show target_role field only when target_audience === 'specific_role'
{targetAudience === 'specific_role' && (
    <SelectField label="Role *" name="target_role" options={roles} />
)}
```

---

## 5. Halaman Show

### Route: `GET /campaigns/{campaign}` (`communication.campaigns.show`)

### Permission: `communication.campaigns.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                                       │
│  ← Kembali ke Daftar                                                                        │
│                                                                                              │
│  COM-2026-0002                                                                              │
│  Safety Alert: Kebocoran Pipa Gas di Area Produksi                                          │
│                                                                                              │
│  🔴 Safety Alert     🟢 Published     👥 Semua Karyawan     👁 145 views                   │
│                                                                                              │
│  Dibuat oleh: Ahmad QHSSE Officer  |  Published: 11 Jul 2026 14:30  |  Expires: 11 Aug 2026│
├──────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                              │
│  ┌─ Section: Konten Kampanye ─────────────────────────────────────────────────────────────┐  │
│  │  KONTEN KAMPANYE                                                                       │  │
│  │  ─────────────────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                                        │  │
│  │  <h2>Kronologi Kejadian</h2>                                                           │  │
│  │  <p>Pada tanggal 10 Juli 2026 pukul 13:45 WIB, terdeteksi kebocoran pipa gas           │  │
│  │  di area produksi unit B. Sistem deteksi gas terpicu dan alarm berbunyi. Tim          │  │
│  │  emergency response segera melakukan isolasi area...</p>                                │  │
│  │                                                                                        │  │
│  │  <h2>Dampak & Risiko</h2>                                                              │  │
│  │  <ul>                                                                                  │  │
│  │    <li>Potensi ledakan jika gas terakumulasi</li>                                      │  │
│  │    <li>Pencemaran lingkungan</li>                                                     │  │
│  │    <li>Gangguan operasional produksi</li>                                             │  │
│  │  </ul>                                                                                 │  │
│  │                                                                                        │  │
│  │  <h2>Tindakan Pencegahan</h2>                                                          │  │
│  │  <ol>                                                                                  │  │
│  │    <li>Selalu gunakan detektir gas pribadi di area berisiko</li>                       │  │
│  │    <li>Periksa koneksi pipa secara berkala</li>                                        │  │
│  │    <li>Laporkan immediately jika mencium bau gas</li>                                  │  │
│  │  </ol>                                                                                 │  │
│  │                                                                                        │  │
│  │  <h2>Kontak</h2>                                                                       │  │
│  │  <p>Hubungi QHSSE Department: ext. 1234 atau email: qhsse@company.com</p>             │  │
│  │                                                                                        │  │
│  └────────────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                              │
│  ┌─ Section: Lampiran ───────────────────────────────────────────────────────────────────┐  │
│  │  LAMPIRAN                                                                              │  │
│  │  ─────────────────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                                        │  │
│  │  📎 investigation_report.pdf  (2.3 MB)                    [⬇ Download]                 │  │
│  │  📎 safety_procedure.pdf      (1.1 MB)                    [⬇ Download]                 │  │
│  │                                                                                        │  │
│  └────────────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                              │
│  ┌─ Section: Konfirmasi (Acknowledgment) ───────────────────────────────────────────────┐  │
│  │  KONFIRMASI BACA                                                                       │  │
│  │  ─────────────────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                                        │  │
│  │  ┌────────────────────────────────────────────────────────────────────────────────┐    │  │
│  │  │  ⚠ Safety Alert ini wajib Anda konfirmasi (acknowledge).                      │    │  │
│  │  │                                                                                │    │  │
│  │  │  Dengan mengklik tombol di bawah, Anda menyatakan telah membaca dan memahami  │    │  │
│  │  │  isi safety alert ini.                                                        │    │  │
│  │  │                                                                                │    │  │
│  │  │                          [✅ Saya Sudah Membaca]                               │    │  │
│  │  └────────────────────────────────────────────────────────────────────────────────┘    │  │
│  │                                                                                        │  │
│  │  ATAU                                                                                   │  │
│  │                                                                                        │  │
│  │  ┌────────────────────────────────────────────────────────────────────────────────┐    │  │
│  │  │  ✅ Anda telah mengkonfirmasi safety alert ini.                                │    │  │
│  │  │  Dikonfirmasi pada: 11 Jul 2026 15:20 WIB                                     │    │  │
│  │  │  IP Address: 192.168.1.100                                                      │    │  │
│  │  └────────────────────────────────────────────────────────────────────────────────┘    │  │
│  └────────────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                              │
│  ┌─ Section: Daftar Konfirmasi (Acknowledgment List) ─────────────────────────────────────┐  │
│  │  DAFTAR KONFIRMASI                                               32 dari 50          │  │
│  │  ─────────────────────────────────────────────────────────────────────────────────────  │  │
│  │  (Hanya untuk user dengan permission: communication.acknowledgments.view)              │  │
│  │                                                                                        │  │
│  │  Acknowledgment Rate: ████████████████░░░░░░░░  64%                                   │  │
│  │                                                                                        │  │
│  │  ┌─ Table ──────────────────────────────────────────────────────────────────────────┐  │  │
│  │  │ User              Tanggal Konfirmasi         IP Address          Status          │  │  │
│  │  ├──────────────────────────────────────────────────────────────────────────────────┤  │  │
│  │  │ Budi Santoso     11 Jul 2026 14:35 WIB      192.168.1.101      ✅ Dikonfirmasi   │  │  │
│  │  │ Sari Wulandari   11 Jul 2026 14:42 WIB      192.168.1.102      ✅ Dikonfirmasi   │  │  │
│  │  │ Andi Pratama     11 Jul 2026 15:01 WIB      10.0.0.50          ✅ Dikonfirmasi   │  │  │
│  │  │ ...                                                                              │  │  │
│  │  └──────────────────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                                        │  │
│  │  [Muat Lebih Banyak]                                                                   │  │
│  └────────────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                              │
│  ┌─ Section: Activity Log ───────────────────────────────────────────────────────────────┐  │
│  │  ACTIVITY LOG                                                                          │  │
│  │  ─────────────────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                                        │  │
│  │  🟢 11 Jul 2026 14:30  Campaign published by Ahmad QHSSE Officer                      │  │
│  │  🔵 11 Jul 2026 14:00  Campaign updated by Ahmad QHSSE Officer                         │  │
│  │  ⚪ 11 Jul 2026 13:45  Campaign created by Ahmad QHSSE Officer                         │  │
│  │                                                                                        │  │
│  └────────────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                              │
│  ┌─ Action Bar ──────────────────────────────────────────────────────────────────────────┐  │
│  │  [← Kembali]     [✏ Edit]  [📢 Publish]     [⬇ Export]                               │  │
│  └────────────────────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Element

#### Header

| Element | Type | Detail |
|---|---|---|
| Back Link | `<Link>` | Route: `communication.campaigns.index` |
| Campaign Number | `<span>` | Monospace, `text-gray-500` |
| Title | `<h1>` | "Safety Alert: Kebocoran Pipa Gas di Area Produksi" |
| Type Badge | Badge | 🔴 Safety Alert |
| Status Badge | Badge | 🟢 Published |
| Target Audience Badge | Badge | 👥 Semua Karyawan |
| View Count | `<span>` | "145 views" with 👁 icon |
| Meta Info | `<p>` | Author, published_at, expires_at |

#### Content Section

| Element | Type | Detail |
|---|---|---|
| Content | `<div dangerouslySetInnerHTML>` | Render rich text HTML (sanitized) |

#### Attachment Section

| Element | Type | Detail |
|---|---|---|
| File List | List | Each item: icon, filename, size, download button |
| Download Button | `<a>` | Route: `core.files.download`, permission: `communication.campaigns.view` |
| Empty State | `<p>` | "Tidak ada lampiran" if no files |

#### Acknowledgment Section (Conditional)

**If user hasn't acknowledged AND campaign type requires acknowledgment:**

| Element | Type | Detail |
|---|---|---|
| Warning Box | `<div>` | Yellow/red highlight with safety alert warning |
| Acknowledge Button | `<button>` | POST to `communication.campaigns.acknowledge`, confirm dialog |

**If user has already acknowledged:**

| Element | Type | Detail |
|---|---|---|
| Confirmation Box | `<div>` | Green highlight showing acknowledgment timestamp + IP |

**If acknowledgment not applicable (newsletter):**

Section hidden entirely.

#### Acknowledgment List Section

| Element | Type | Detail |
|---|---|---|
| Permission Gate | Conditional | Only render if `can.viewAcknowledgments` = true |
| Progress Bar | `<div>` | Acknowledgment rate: acknowledged/total target audience |
| Table | `<table>` | Columns: User, Tanggal Konfirmasi, IP Address, Status |
| Pagination | "Muat Lebih Banyak" | Load more via AJAX/Inertia partial reload |

#### Activity Log Section

| Element | Type | Detail |
|---|---|---|
| Timeline | List | Latest 10 activities, each: icon, timestamp, description |

#### Action Bar

| Action | Icon | Permission | Condition |
|---|---|---|---|
| Kembali | ← | — | Selalu tampil |
| Edit | ✏ | `communication.campaigns.update` | Hanya jika status = `draft` |
| Publish | 📢 | `communication.campaigns.publish` | Hanya jika status = `draft` |
| Export | ⬇ | `communication.campaigns.export` | Selalu tampil |

---

## 6. Mobile Responsive

### Breakpoints

- Desktop: Full layout as described above
- Tablet (md): Table columns collapse, filters stack vertically
- Mobile (sm): Card-based layout, filters in collapsible panel

### Mobile Index — Card Layout

```
┌──────────────────────────────────────┐
│  Kampanye Komunikasi                 │
│  [+ Buat]  [⬇ Export]               │
│                                      │
│  [🔍 Cari...]                        │
│  Tipe: [Semua ▾]  Status: [Semua ▾] │
├──────────────────────────────────────┤
│                                      │
│  ┌────────────────────────────────┐  │
│  │ COM-2026-0002                  │  │
│  │ Safety Alert: Kebocoran Pipa   │  │
│  │ 🔴 Safety Alert  🟢 Published │  │
│  │ 👁 145 views  ✅ 32 ack        │  │
│  │ Published: 11 Jul 2026         │  │
│  │              [👁 Lihat]        │  │
│  └────────────────────────────────┘  │
│                                      │
│  ┌────────────────────────────────┐  │
│  │ COM-2026-0003                  │  │
│  │ Zero Accident Month             │  │
│  │ 🟣 Kampanye     🟢 Published   │  │
│  │ 👁 128 views                    │  │
│  │ Published: 01 Jul 2026          │  │
│  │              [👁 Lihat]        │  │
│  └────────────────────────────────┘  │
│                                      │
│  ‹ 1  2  3 ›                        │
└──────────────────────────────────────┘
```

### Mobile Form

- Form sections stack vertically (always stacked on mobile)
- Rich text editor switches to simpler toolbar
- File upload uses native file picker
- Target audience conditional fields appear inline

### Mobile Show

- Header badges wrap
- Content section full width
- Acknowledgment button full width
- Acknowledgment list table becomes card list
- Activity log condensed to single column

### Component List

| Component | File | Used In |
|---|---|---|
| `CampaignIndex` | `Pages/Modules/Communication/Campaign/Index.tsx` | Index page |
| `CampaignForm` | `Pages/Modules/Communication/Campaign/Form.tsx` | Create/Edit page |
| `CampaignShow` | `Pages/Modules/Communication/Campaign/Show.tsx` | Show page |
| `CampaignBadge` | `components/CampaignBadge.tsx` | Reusable badge component |
| `TargetAudienceSelector` | `components/TargetAudienceSelector.tsx` | Form: conditional audience fields |
| `AcknowledgmentList` | `components/AcknowledgmentList.tsx` | Show: acknowledgment table |
| `AcknowledgmentButton` | `components/AcknowledgmentButton.tsx` | Show: acknowledge action |
| `ViewCountBadge` | `components/ViewCountBadge.tsx` | Show/List: view count display |
| `RichTextEditor` | `components/RichTextEditor.tsx` | Form: TipTap-based editor |
| `CampaignActivityLog` | `components/CampaignActivityLog.tsx` | Show: activity timeline |
