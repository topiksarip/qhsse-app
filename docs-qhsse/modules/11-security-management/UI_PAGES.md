# UI Pages — Security Management

Spesifikasi wireframe halaman UI untuk modul Security Management.

Semua label menggunakan Bahasa Indonesia. Tech stack: Laravel 12 + Inertia React + TypeScript + Tailwind CSS.

---

## Daftar Isi

1. [Navigasi](#1-navigasi)
2. [Color Coding](#2-color-coding)
3. [Security Incident — Index](#3-security-incident--index)
4. [Security Incident — Form](#4-security-incident--form)
5. [Security Incident — Show](#5-security-incident--show)
6. [Visitor Log — Index](#6-visitor-log--index)
7. [Visitor Log — Form (Check-In)](#7-visitor-log--form-check-in)
8. [Patrol Checklist — Index](#8-patrol-checklist--index)
9. [Patrol Checklist — Form](#9-patrol-checklist--form)
10. [Patrol Checklist — Show (Execute)](#10-patrol-checklist--show-execute)
11. [Mobile Responsive](#11-mobile-responsive)

---

## 1. Navigasi

### Penempatan Menu

Tambahkan item baru pada group `Modul QHSSE` di `AuthenticatedLayout.tsx`:

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
            // ... other modules ...
            { label: 'Insiden Keamanan', routeName: 'security.incidents.index', active: 'security.incidents.*', permission: 'security.incidents.view' },
            { label: 'Log Pengunjung', routeName: 'security.visitors.index', active: 'security.visitors.*', permission: 'security.visitors.view' },
            { label: 'Patroli Keamanan', routeName: 'security.patrols.index', active: 'security.patrols.*', permission: 'security.patrols.view' },
        ],
    },
    // ...
];
```

### Wireframe Navigasi (Desktop)

```
┌──────────────────────────────────────────────────────────────────────┐
│  [Logo] QHSSE   Core ▾   Modul QHSSE ▾   Masters ▾   Admin ▾  [User]│
│                        ┌────────────────────────┐                     │
│                        │ Laporan Insiden        │                     │
│                        │ Insiden Keamanan       │                     │
│                        │ Log Pengunjung         │                     │
│                        │ Patroli Keamanan       │                     │
│                        │ ...                    │                     │
│                        └────────────────────────┘                     │
└──────────────────────────────────────────────────────────────────────┘
```

### Permission Filtering

Menu hanya tampil jika user memiliki permission yang sesuai. Filtering via `auth.permissions`.

---

## 2. Color Coding

### Severity Badge

| Severity | Tailwind Class | Preview |
|---|---|---|
| Critical | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | 🔴 Critical |
| High | `bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200` | 🟠 High |
| Medium | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | 🟡 Medium |
| Low | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | 🔵 Low |

### Incident Status Badge

| Status | Tailwind Class | Preview |
|---|---|---|
| Reported | `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | 🔵 Reported |
| Under Investigation | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | 🟡 Investigasi |
| Closed | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | 🟢 Closed |

### Patrol Status Badge

| Status | Tailwind Class | Preview |
|---|---|---|
| Scheduled | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | ⚪ Terjadwal |
| In Progress | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | 🟡 Berlangsung |
| Completed | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | 🟢 Selesai |

### Patrol Result Status Badge

| Status | Tailwind Class | Preview |
|---|---|---|
| OK | `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | ✅ OK |
| Issue | `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | ❌ Issue |
| N/A | `bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200` | ➖ N/A |

### Incident Type Badge

| Type | Tailwind Class | Label |
|---|---|---|
| unauthorized_access | `bg-purple-100 text-purple-800` | Akses Tidak Sah |
| theft | `bg-red-100 text-red-800` | Pencurian |
| vandalism | `bg-orange-100 text-orange-800` | Vandalisme |
| trespass | `bg-pink-100 text-pink-800` | Penyusupan |
| suspicious_activity | `bg-yellow-100 text-yellow-800` | Aktivitas Mencurigakan |
| other | `bg-gray-100 text-gray-800` | Lainnya |

---

## 3. Security Incident — Index

### Route: `GET /security-incidents` (`security.incidents.index`)
### Permission: `security.incidents.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                       │
│  Insiden Keamanan                                    [+ Laporkan Insiden]   │
│  Kelola insiden keamanan: akses tidak sah, pencurian, vandalisme            │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari nomor, judul...                  ]                            │  │
│  │                                                                        │  │
│  │ Status: [Semua ▾]  Tipe: [Semua ▾]  Severity: [Semua ▾]               │  │
│  │ Site:   [Semua ▾]  Dari: [__/__/____]  Sampai: [__/__/____]  [Reset]  │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Toolbar ─────────────────────────────────────────────────────────────┐  │
│  │ Menampilkan 1–15 dari 32 insiden                    [⬇ Export CSV]   │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Table ─────────────────────────────────────────────────────────────────┐ │
│  │ Nomor       Judul                Tipe          Severity  Status     Tgl  │ │
│  ├─────────────────────────────────────────────────────────────────────────┤ │
│  │ SEC-0001   Akses Tidak Sah       Akses Tidak  🔴Critical 🔵Reported 11/07│ │
│  │ SEC-0002   Pencurian di Gudang   Pencurian     🟠High     🟡Investig  10/07│ │
│  │ SEC-0003   Vandalisme Pagar      Vandalisme   🟡Medium   🟢Closed    09/07│ │
│  │ SEC-0004   Orang Asing           Penyusupan   🟡Medium   🟢Closed    08/07│ │
│  │ SEC-0005   Aktivitas Mencurigakan Aktivitas  🔵Low      🔵Reported  07/07│ │
│  │ ...                                                                     │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│  ┌─ Table (cont.) ────────────────────────────────────────────────────────┐ │
│  │ ... Tgl    Pelapor       Aksi                                           │ │
│  │ ... 11/07  Budi S.       [👁 Lihat]                                     │ │
│  │ ... 10/07  Sari W.       [👁 Lihat]                                     │ │
│  │ ... 09/07  Andi P.       [👁 Lihat]                                     │ │
│  │ ... 08/07  Joni K.       [👁 Lihat]                                     │ │
│  │ ... 07/07  Rina M.       [👁 Lihat] [✏ Edit]                           │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  ┌─ Pagination ───────────────────────────────────────────────────────────┐  │
│  │                          ‹ Sebelumnya   1  2  3   Berikutnya ›         │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Table Columns

| # | Column | Key | Width | Badge? | Detail |
|---|---|---|---|---|---|
| 1 | Nomor | `security_number` | 120px | No | Link ke show page, monospace |
| 2 | Judul | `title` | flex | No | Truncate `max-w-xs truncate` |
| 3 | Tipe | `type` | 140px | Yes | Type badge |
| 4 | Severity | `severity.name` | 110px | Yes | Severity badge |
| 5 | Status | `status` | 130px | Yes | Status badge |
| 6 | Tanggal | `occurred_at` | 100px | No | Format `dd/mm/yy` |
| 7 | Pelapor | `reported_by.name` | 130px | No | |
| 8 | Aksi | — | 120px | No | Buttons |

### Aksi Column

| Action | Icon | Permission | Condition |
|---|---|---|---|
| Lihat | 👁 | `security.incidents.view` | Selalu |
| Edit | ✏ | `security.incidents.update` | Status = Reported atau Under Investigation |

### Inertia Props

```typescript
interface IncidentIndexProps {
    incidents: {
        data: SecurityIncident[];
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
        type?: string;
        severity_id?: number;
        site_id?: number;
        from?: string;
        to?: string;
    };
    sites: Site[];
    severities: Severity[];
    can: {
        create: boolean;
        export: boolean;
    };
}
```

---

## 4. Security Incident — Form

### Route
- Create: `GET /security-incidents/create` (`security.incidents.create`)
- Edit: `GET /security-incidents/{id}/edit` (`security.incidents.edit`)

### Permission
- Create: `security.incidents.create`
- Edit: `security.incidents.update` (hanya jika status `reported` atau `under_investigation`)

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Laporkan Insiden Keamanan                                                       │
│  Isi data insiden keamanan dengan lengkap                                        │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Insiden ───────────────────────────────────────────────┐  │
│  │  INFORMASI INSIDEN                                                          │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Nomor Insiden       [Auto-generated — SEC-2026-0001          ]  ⓘ        │  │
│  │                       Nomor dibuat otomatis saat simpan                       │  │
│  │                                                                             │  │
│  │  Tipe Insiden *      [— Pilih Tipe —                 ▾]                    │  │
│  │                       ○ Akses Tidak Sah  ○ Pencurian  ○ Vandalisme          │  │
│  │                       ○ Penyusupan       ○ Aktivitas Mencurigakan          │  │
│  │                       ○ Lainnya                                             │  │
│  │                                                                             │  │
│  │  Judul *             [Masukkan judul insiden...               ]            │  │
│  │                                                                             │  │
│  │  Waktu Kejadian *    [__/__/____ __:__] [🕐]                               │  │
│  │                       Tanggal dan waktu kejadian                             │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Lokasi ─────────────────────────────────────────────────────────┐  │
│  │  LOKASI                                                                     │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Site *              [— Pilih Site —    ▾]                                  │  │
│  │                                                                             │  │
│  │  Area                [— Pilih Area —    ▾]    (filtered by site)            │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Klasifikasi ─────────────────────────────────────────────────────┐  │
│  │  KLASIFIKASI                                                                │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Severity *          [— Pilih Severity —    ▾]                             │  │
│  │                       ○ Critical  ○ High  ○ Medium  ○ Low                  │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Deskripsi ──────────────────────────────────────────────────────┐  │
│  │  DESKRIPSI                                                                  │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Deskripsi Insiden * ┌──────────────────────────────────────────────┐     │  │
│  │                       │                                              │     │  │
│  │                       │ Jelaskan kronologi insiden keamanan...       │     │  │
│  │                       │                                              │     │  │
│  │                       └──────────────────────────────────────────────┘     │  │
│  │                       Minimal 20 karakter                                  │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Evidence ───────────────────────────────────────────────────────┐  │
│  │  EVIDENCE                                                                  │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  ┌─────────────────────────────────────────────────────────────────────┐   │  │
│  │  │                                                                     │   │  │
│  │  │              📁  Drag & drop file di sini                           │   │  │
│  │  │                  atau [Pilih File]                                  │   │  │
│  │  │                                                                     │   │  │
│  │  │              Maks 25MB per file. Format: jpg, png, pdf, mp4        │   │  │
│  │  │                                                                     │   │  │
│  │  └─────────────────────────────────────────────────────────────────────┘   │  │
│  │                                                                             │  │
│  │  File terunggah:                                                           │  │
│  │  ┌──────────────────────────────────────────────────────────────────┐     │  │
│  │  │ 📷 cctv_footage.mp4                              12.5 MB  [🗑]  │     │  │
│  │  │ 📷 foto_lokasi.jpg                                2.1 MB  [🗑]  │     │  │
│  │  └──────────────────────────────────────────────────────────────────┘     │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Action Bar (sticky bottom) ──────────────────────────────────────────────┐  │
│  │                                                                           │  │
│  │  [← Batal]                                          [Simpan]              │  │
│  │                                                     (primary)             │  │
│  └───────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Form Fields

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Nomor Insiden | Text (readonly) | No | — | Auto-generated. Placeholder "Auto-generated" |
| Tipe Insiden | Select dropdown | Yes | `required, in:unauthorized_access,theft,vandalism,trespass,suspicious_activity,other` | 6 options |
| Judul | Text input | Yes | `required, min:5, max:255` | |
| Waktu Kejadian | DateTime picker | Yes | `required, date, before_or_equal:now` | Format `dd/mm/yyyy HH:mm` |
| Site | Select dropdown | Yes | `required, exists:sites` | |
| Area | Select dropdown | No | `nullable, exists:areas` | Filtered by site_id |
| Severity | Select dropdown | Yes | `required, exists:severities` | Critical/High/Medium/Low |
| Deskripsi | Textarea | Yes | `required, min:20` | Minimal 20 karakter |

### Action Buttons

| Button | Type | Style | Behavior |
|---|---|---|---|
| Batal | Link | `text-slate-600 hover:text-slate-900` | Redirect ke index |
| Simpan | Submit | `bg-blue-600 text-white hover:bg-blue-700` | POST/PUT, save record |

### Inertia Props

```typescript
interface IncidentFormProps {
    incident: SecurityIncident | null;  // null for create
    sites: Site[];
    areas: Area[];
    severities: Severity[];
}
```

---

## 5. Security Incident — Show

### Route: `GET /security-incidents/{id}` (`security.incidents.show`)
### Permission: `security.incidents.view`

### Wireframe — Desktop

```
┌───────────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                                │
│  ← Kembali ke Daftar                                                                  │
├───────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                       │
│  ┌─ Summary Card ──────────────────────────────────────────────────────────────────┐  │
│  │                                                                                 │  │
│  │  SEC-0001                          [🔴 Critical] [🔵 Reported]                  │  │
│  │  Akses Tidak Sah ke Server Room                                                   │  │
│  │  [🟣 Akses Tidak Sah]                                                            │  │
│  │                                                                                 │  │
│  │  📅 Waktu Kejadian: 11/07/2026 14:30                                             │  │
│  │  🏭 Site: Plant A   📍 Area: Server Room                                        │  │
│  │  👤 Pelapor: Budi Santoso (budi.s@company.com)                                  │  │
│  │  📁 Severity: Critical                                                           │  │
│  │                                                                                 │  │
│  │  ┌─ Action Buttons ──────────────────────────────────────────────────────────┐   │  │
│  │  │  [✏ Edit]  [🔍 Mulai Investigasi]  [✓ Tutup Insiden]                    │   │  │
│  │  └───────────────────────────────────────────────────────────────────────────┘   │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Detail Layout: 2 columns ──────────────────────────────────────────────────────┐  │
│  │                                                                                  │  │
│  │  ┌─ Left Column (2/3) ─────────────────────────────────┐  ┌─ Right Column (1/3) ┐│  │
│  │  │                                                     │  │                    ││  │
│  │  │  DESKRIPSI INSIDEN                                  │  │ ┌─ INFO LOKASI ──┐ ││  │
│  │  │  ─────────────────────────────────────────────      │  │ │ Site: Plant A  │ ││  │
│  │  │  Pada tanggal 11 Juli 2026 pukul 14:30 WIB,         │  │ │ Area: Server   │ ││  │
│  │  │  terdeteksi akses tidak sah ke Server Room.         │  │ │    Room        │ ││  │
│  │  │  CCTV menunjukkan orang tidak dikenal memasuki     │  │ └────────────────┘ ││  │
│  │  │  area pada jam di luar operasional...               │  │                    ││  │
│  │  │                                                     │  │ ┌─ PELAPOR ──────┐ ││  │
│  │  │  ┌─ RESOLUSI ────────────────────────────────────┐  │  │ │ Nama: Budi S.  │ ││  │
│  │  │  │ (Hanya tampil jika status = Closed)           │  │  │ │ Email: budi..  │ ││  │
│  │  │  │ Investigasi menunjukkan pintu darurat tidak   │  │  │ └────────────────┘ ││  │
│  │  │  │ terkunci. Sistem akses telah diperbaiki.       │  │  │                    ││  │
│  │  │  │ Ditutup: 12/07/2026 10:00                     │  │  │ ┌─ EVIDENCE ─────┐ ││  │
│  │  │  └───────────────────────────────────────────────┘  │  │ │ 📷 cctv.mp4   │ ││  │
│  │  │                                                     │  │ │    12.5 MB ⬇  │ ││  │
│  │  └─────────────────────────────────────────────────────┘  │ │ 📷 foto.jpg   │ ││  │
│  │                                                            │ │     2.1 MB ⬇  │ ││  │
│  │                                                            │ └────────────────┘ ││  │
│  └──────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Activity Log ───────────────────────────────────────────────────────────────────┐  │
│  │  LOG AKTIVITAS                                                                    │  │
│  │  ─────────────────────────────────────────────────────────────────────────────    │  │
│  │                                                                                   │  │
│  │  📝 11/07/2026 14:35  Budi Santoso  Membuat laporan insiden keamanan              │  │
│  │  🔍 11/07/2026 15:00  Sari W.       Memulai investigasi                          │  │
│  │  ✅ 12/07/2026 10:00  Sari W.       Menutup insiden dengan resolusi              │  │
│  │                                                                                   │  │
│  └───────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Comments Section ───────────────────────────────────────────────────────────────┐  │
│  │  KOMENTAR (2)                                                                     │  │
│  │  ─────────────────────────────────────────────────────────────────────────────    │  │
│  │                                                                                   │  │
│  │  ┌─ Comment 1 ───────────────────────────────────────────────────────────────┐   │  │
│  │  │ 👤 Sari W. (QHSSE Officer)                              11/07 15:05     │   │  │
│  │  │ Mohon coba rekam CCTV jam 13:00-15:00 untuk identifikasi.                 │   │  │
│  │  └───────────────────────────────────────────────────────────────────────────┘   │  │
│  │                                                                                   │  │
│  │  ┌─ Add Comment ─────────────────────────────────────────────────────────────┐   │  │
│  │  │ [Tulis komentar...                                          ]  [Kirim]    │   │  │
│  │  └───────────────────────────────────────────────────────────────────────────┘   │  │
│  │                                                                                   │  │
│  └───────────────────────────────────────────────────────────────────────────────────┘  │
└───────────────────────────────────────────────────────────────────────────────────────┘
```

### Action Buttons (Permission-Gated)

| Button | Permission | Condition (status) | Route |
|---|---|---|---|
| Edit | `security.incidents.update` | status = Reported / Under Investigation | `security.incidents.edit` |
| Mulai Investigasi | `security.incidents.update` | status = Reported | `POST security.incidents/{id}/investigate` |
| Tutup Insiden | `security.incidents.close` | status = Reported / Under Investigation | `POST security.incidents/{id}/close` |

### Inertia Props

```typescript
interface IncidentShowProps {
    incident: SecurityIncident & {
        site: Site;
        area: Area | null;
        reported_by: User;
        severity: Severity;
    };
    evidence: ManagedFile[];
    comments: Comment[];
    activities: ActivityLog[];
    can: {
        update: boolean;
        close: boolean;
    };
}
```

---

## 6. Visitor Log — Index

### Route: `GET /visitor-logs` (`security.visitors.index`)
### Permission: `security.visitors.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                       │
│  Log Pengunjung                                    [+ Check-In Pengunjung]  │
│  Catatan kedatangan dan keberangkatan pengunjung                            │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari nama, perusahaan, host...        ]                            │  │
│  │                                                                        │  │
│  │ Status: [Semua ▾]  Site: [Semua ▾]                                    │  │
│  │ Dari: [__/__/____]  Sampai: [__/__/____]  [Reset]                    │  │
│  │                                                                        │  │
│  │ Status: ○ Semua  ○ Check-In Saja  ○ Masih di Lokasi  ○ Check-Out      │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Toolbar ─────────────────────────────────────────────────────────────┐  │
│  │ Menampilkan 1–15 dari 89 pengunjung               [⬇ Export CSV]    │  │
│  │ Pengunjung On-Site: 3                                                  │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Table ─────────────────────────────────────────────────────────────────┐ │
│  │ Nama            Perusahaan     Tujuan       Host        Check-In  Status│ │
│  ├─────────────────────────────────────────────────────────────────────────┤ │
│  │ Andi Pratama   PT Maju Jaya   Meeting   Sari W.   11/07 09:00 🟢On-Site│ │
│  │ Budi Santoso   CV Sukses      Delivery  Joni K.   11/07 08:30 🟢On-Site│ │
│  │ Cindy Lestari  —              Interview Rina M.   11/07 10:00 🔵Out   │ │
│  │ Doni Hartono   PT Teknologi   Service   Andi P.   10/07 14:00 🔵Out   │ │
│  │ ...                                                                     │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│  ┌─ Table (cont.) ────────────────────────────────────────────────────────┐ │
│  │ ... Status    Check-Out   Plat          Aksi                           │ │
│  │ ... 🟢On-Site —           B 1234 ABC   [⬆ Check-Out] [✏ Edit]          │ │
│  │ ... 🟢On-Site —           —            [⬆ Check-Out] [✏ Edit]          │ │
│  │ ... 🔵Out    10/07 11:00  B 5678 DEF  [👁 Lihat]                      │ │
│  │ ... 🔵Out    10/07 16:00  —            [👁 Lihat]                      │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  ┌─ Pagination ───────────────────────────────────────────────────────────┐  │
│  │                          ‹ Sebelumnya   1  2  3  4  5  6   Berikutnya ›│  │
│  └────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Table Columns

| # | Column | Key | Width | Badge? | Detail |
|---|---|---|---|---|---|
| 1 | Nama | `visitor_name` | 150px | No | |
| 2 | Perusahaan | `visitor_company` | 140px | No | "—" if null |
| 3 | Tujuan | `purpose` | flex | No | Truncate |
| 4 | Host | `host.name` | 120px | No | |
| 5 | Check-In | `check_in_at` | 130px | No | Format `dd/mm/yy HH:mm` |
| 6 | Status | — | 100px | Yes | 🟢 On-Site / 🔵 Checked-Out |
| 7 | Check-Out | `check_out_at` | 130px | No | "—" if null |
| 8 | Plat | `vehicle_plate` | 110px | No | "—" if null |
| 9 | Aksi | — | 160px | No | Buttons |

### Aksi Column

| Action | Icon | Permission | Condition |
|---|---|---|---|
| Lihat | 👁 | `security.visitors.view` | Selalu |
| Edit | ✏ | `security.visitors.update` | check_out_at IS NULL |
| Check-Out | ⬆ | `security.visitors.update` | check_out_at IS NULL |

---

## 7. Visitor Log — Form (Check-In)

### Route: `GET /visitor-logs/create` (`security.visitors.create`)
### Permission: `security.visitors.create`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Check-In Pengunjung                                                            │
│  Daftarkan pengunjung yang masuk ke lokasi                                      │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Data Pengunjung ────────────────────────────────────────────────┐  │
│  │  DATA PENGUNJUNG                                                           │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Nama Pengunjung *   [Masukkan nama lengkap...               ]            │  │
│  │                                                                             │  │
│  │  Perusahaan           [Nama perusahaan/organisasi...         ]            │  │
│  │                        Opsional jika pengunjung individu                     │  │
│  │                                                                             │  │
│  │  Tujuan Kunjungan *  ┌──────────────────────────────────────────────┐     │  │
│  │                       │ Jelaskan tujuan kunjungan...                 │     │  │
│  │                       └──────────────────────────────────────────────┘     │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Identitas ──────────────────────────────────────────────────────┐  │
│  │  IDENTITAS                                                                  │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Jenis ID *          [— Pilih Jenis ID —    ▾]                             │  │
│  │                       ○ KTP  ○ SIM  ○ Passport  ○ Lainnya                   │  │
│  │                                                                             │  │
│  │  Nomor ID *          [Masukkan nomor ID...                    ]            │  │
│  │                                                                             │  │
│  │  Plat Kendaraan      [B 1234 ABC           ]    Opsional                    │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Tujuan & Host ─────────────────────────────────────────────────┐  │
│  │  TUJUAN & HOST                                                             │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Site *              [— Pilih Site —    ▾]                                  │  │
│  │                                                                             │  │
│  │  Host *              [— Cari host/pegawai —    ▾]                           │  │
│  │                       User/employee yang dikunjungi                          │  │
│  │                                                                             │  │
│  │  Check-In At         [11/07/2026 09:00]    Auto (now), bisa edit            │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Action Bar (sticky bottom) ──────────────────────────────────────────────┐  │
│  │                                                                           │  │
│  │  [← Batal]                                          [Check-In]           │  │
│  │                                                     (primary)             │  │
│  └───────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Form Fields

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Nama Pengunjung | Text input | Yes | `required, max:255` | |
| Perusahaan | Text input | No | `nullable, max:255` | |
| Tujuan Kunjungan | Textarea | Yes | `required, min:5` | |
| Jenis ID | Select | Yes | `required, in:KTP,SIM,Passport,Lainnya` | |
| Nomor ID | Text input | Yes | `required, max:100` | |
| Plat Kendaraan | Text input | No | `nullable, max:20` | Uppercase |
| Site | Select | Yes | `required, exists:sites` | |
| Host | Select (search) | Yes | `required, exists:users` | |
| Check-In At | DateTime | Yes | `required, date` | Default: now() |

---

## 8. Patrol Checklist — Index

### Route: `GET /patrol-checklists` (`security.patrols.index`)
### Permission: `security.patrols.view`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                       │
│  Patroli Keamanan                                   [+ Buat Jadwal Patroli]  │
│  Kelola jadwal dan hasil patroli keamanan                                    │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─ Filter Bar ───────────────────────────────────────────────────────────┐  │
│  │ [🔍 Cari nomor, rute, officer...        ]                              │  │
│  │                                                                        │  │
│  │ Status: [Semua ▾]  Site: [Semua ▾]                                    │  │
│  │ Dari: [__/__/____]  Sampai: [__/__/____]  [Reset]                    │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Toolbar ─────────────────────────────────────────────────────────────┐  │
│  │ Menampilkan 1–15 dari 24 patroli                  [⬇ Export CSV]      │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Table ─────────────────────────────────────────────────────────────────┐ │
│  │ Nomor       Rute              Officer      Site      Jadwal   Status   │ │
│  ├─────────────────────────────────────────────────────────────────────────┤ │
│  │ SPL-0001   Rute Malam         Andi P.     Plant A   11/07 22:00 ⚪Terj  │ │
│  │ SPL-0002   Rute Pagi          Budi S.     Plant A   11/07 06:00 🟡Berl  │ │
│  │ SPL-0003   Rute Siang          Sari W.    Plant B   10/07 14:00 🟢Seles │ │
│  │ SPL-0004   Rute Akhir Pekan    Joni K.    Plant B   09/07 22:00 🟢Seles │ │
│  │ ...                                                                     │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│  ┌─ Table (cont.) ────────────────────────────────────────────────────────┐ │
│  │ ... Status    Eksekusi     Issue  Aksi                                  │ │
│  │ ... ⚪Terj    —            —     [▶ Eksekusi] [✏ Edit]                  │ │
│  │ ... 🟡Berl   11/07 06:05  2     [👁 Lihat] [✓ Selesaikan]              │ │
│  │ ... 🟢Seles  10/07 14:02  0     [👁 Lihat]                            │ │
│  │ ... 🟢Seles  09/07 22:03  1     [👁 Lihat]                            │ │
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
| 1 | Nomor | `patrol_number` | 120px | No | Monospace |
| 2 | Rute | `patrol_route` | flex | No | Truncate |
| 3 | Officer | `officer.name` | 120px | No | |
| 4 | Site | `site.name` | 100px | No | |
| 5 | Jadwal | `scheduled_at` | 130px | No | Format `dd/mm/yy HH:mm` |
| 6 | Status | `status` | 100px | Yes | Patrol status badge |
| 7 | Eksekusi | `executed_at` | 130px | No | "—" if null |
| 8 | Issue | count of issues | 70px | No | Integer, red if > 0 |
| 9 | Aksi | — | 180px | No | Buttons |

### Aksi Column

| Action | Icon | Permission | Condition |
|---|---|---|---|
| Lihat | 👁 | `security.patrols.view` | Selalu |
| Edit | ✏ | `security.patrols.create` | Status = Scheduled |
| Eksekusi | ▶ | `security.patrols.execute` | Status = Scheduled |
| Selesaikan | ✓ | `security.patrols.execute` | Status = In Progress |

---

## 9. Patrol Checklist — Form

### Route: `GET /patrol-checklists/create` (`security.patrols.create`)
### Permission: `security.patrols.create`

### Wireframe — Desktop

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                           │
│  Buat Jadwal Patroli                                                             │
│  Jadwalkan patroli keamanan dengan checkpoint                                    │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─ Section: Informasi Patroli ───────────────────────────────────────────────┐  │
│  │  INFORMASI PATROLI                                                          │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  Nomor Patroli      [Auto-generated — SPL-2026-0001          ]  ⓘ        │  │
│  │                                                                             │  │
│  │  Site *             [— Pilih Site —    ▾]                                   │  │
│  │                                                                             │  │
│  │  Rute Patroli *     [Masukkan nama/deskripsi rute...         ]            │  │
│  │                       Contoh: "Rute Malam — Gerbang Utama ke Gudang"         │  │
│  │                                                                             │  │
│  │  Officer *          [— Pilih Officer —    ▾]                                │  │
│  │                                                                             │  │
│  │  Jadwal Patroli *   [__/__/____ __:__] [🕐]                                │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Checkpoints ────────────────────────────────────────────────────┐  │
│  │  CHECKPOINTS                                                                │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │                                                                             │  │
│  │  ┌─ Repeater Item 1 ────────────────────────────────────────────────────┐  │  │
│  │  │ Checkpoint *  [Masukkan nama checkpoint...      ]                   │  │  │
│  │  │                Contoh: "Gerbang Utama"                               │  │  │
│  │  │                                                          [🗑 Hapus] │  │  │
│  │  └──────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  ┌─ Repeater Item 2 ────────────────────────────────────────────────────┐  │  │
│  │  │ Checkpoint *  [Masukkan nama checkpoint...      ]                   │  │  │
│  │  │                                                          [🗑 Hapus] │  │  │
│  │  └──────────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                             │  │
│  │  [+ Tambah Checkpoint]                                                      │  │
│  │                                                                             │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Section: Catatan ─────────────────────────────────────────────────────────┐  │
│  │  CATATAN                                                                    │  │
│  │  ─────────────────────────────────────────────────────────────────────────  │  │
│  │  Catatan       ┌──────────────────────────────────────────────────────┐   │  │
│  │                │ Catatan tambahan tentang patroli ini...              │   │  │
│  │                └──────────────────────────────────────────────────────┘   │  │
│  └─────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
│  ┌─ Action Bar (sticky bottom) ──────────────────────────────────────────────┐  │
│  │                                                                           │  │
│  │  [← Batal]                                          [Simpan]              │  │
│  └───────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Form Fields

| Field | Type | Required | Validation | Detail |
|---|---|---|---|---|
| Nomor Patroli | Text (readonly) | No | — | Auto-generated |
| Site | Select | Yes | `required, exists:sites` | |
| Rute Patroli | Text input | Yes | `required, max:255` | |
| Officer | Select | Yes | `required, exists:users` | Security officers |
| Jadwal Patroli | DateTime picker | Yes | `required, date` | |
| Checkpoints | Repeater | Yes | `required, array, min:1` | Min 1 checkpoint |
| Checkpoints.*.checkpoint | Text input | Yes | `required, max:255` | |
| Catatan | Textarea | No | `nullable, text` | |

---

## 10. Patrol Checklist — Show (Execute)

### Route: `GET /patrol-checklists/{id}` (`security.patrols.show`)
### Permission: `security.patrols.view`

### Wireframe — Desktop

```
┌───────────────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                                │
│  ← Kembali ke Daftar                                                                  │
├───────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                       │
│  ┌─ Summary Card ──────────────────────────────────────────────────────────────────┐  │
│  │                                                                                 │  │
│  │  SPL-0001                                    [⚪ Terjadwal]                      │  │
│  │  Rute Malam — Gerbang Utama ke Gudang                                            │  │
│  │                                                                                 │  │
│  │  📅 Jadwal: 11/07/2026 22:00   ⏱ Eksekusi: —                                    │  │
│  │  🏭 Site: Plant A                                                                │  │
│  │  👤 Officer: Andi Pratama                                                        │  │
│  │                                                                                 │  │
│  │  ┌─ Action Buttons ──────────────────────────────────────────────────────────┐   │  │
│  │  │  [▶ Mulai Eksekusi]  atau  [✏ Edit]  [✓ Selesaikan]                     │   │  │
│  │  └───────────────────────────────────────────────────────────────────────────┘   │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Checkpoint Results ────────────────────────────────────────────────────────────┐  │
│  │  HASIL CHECKPOINT                                                               │  │
│  │  ─────────────────────────────────────────────────────────────────────────────    │  │
│  │                                                                                   │  │
│  │  ┌─ Checkpoint 1 ────────────────────────────────────────────────────────────┐   │  │
│  │  │ Gerbang Utama                                                              │   │  │
│  │  │                                                                             │   │  │
│  │  │ Status:  ○ ✅ OK    ○ ❌ Issue    ○ ➖ N/A                                 │   │  │
│  │  │                                                                             │   │  │
│  │  │ Remark: [Catatan untuk checkpoint ini...              ]                   │   │  │
│  │  │          (Wajib jika status = Issue)                                      │   │  │
│  │  └─────────────────────────────────────────────────────────────────────────────┘   │  │
│  │                                                                                   │  │
│  │  ┌─ Checkpoint 2 ────────────────────────────────────────────────────────────┐   │  │
│  │  │ Area Parkir                                                               │   │  │
│  │  │                                                                             │   │  │
│  │  │ Status:  ○ ✅ OK    ● ❌ Issue    ○ ➖ N/A                                 │   │  │
│  │  │                                                                             │   │  │
│  │  │ Remark: [Pintu belakang tidak terkunci. Perlu perbaikan.]              │   │  │
│  │  │          (Wajib diisi)                                                    │   │  │
│  │  └─────────────────────────────────────────────────────────────────────────────┘   │  │
│  │                                                                                   │  │
│  │  ┌─ Checkpoint 3 ────────────────────────────────────────────────────────────┐   │  │
│  │  │ Gudang Bahan Baku                                                         │   │  │
│  │  │                                                                             │   │  │
│  │  │ Status:  ● ✅ OK    ○ ❌ Issue    ○ ➖ N/A                                 │   │  │
│  │  │                                                                             │   │  │
│  │  │ Remark: [Semua aman]                                                  ]   │   │  │
│  │  └─────────────────────────────────────────────────────────────────────────────┘   │  │
│  │                                                                                   │  │
│  └───────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Notes ─────────────────────────────────────────────────────────────────────────┐  │
│  │  CATATAN PATROLI                                                                 │  │
│  │  ─────────────────────────────────────────────────────────────────────────────    │  │
│  │  Patroli berjalan dengan baik kecuali temuan issue di Area Parkir.               │  │
│  └───────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                       │
│  ┌─ Activity Log ───────────────────────────────────────────────────────────────────┐  │
│  │  LOG AKTIVITAS                                                                    │  │
│  │  ─────────────────────────────────────────────────────────────────────────────    │  │
│  │  📝 11/07/2026 09:00  Admin         Membuat jadwal patroli                        │  │
│  │  ▶  11/07/2026 22:05  Andi P.      Memulai eksekusi patroli                      │  │
│  │  ✅ 11/07/2026 22:45  Andi P.      Menyelesaikan patroli (1 issue)              │  │
│  │                                                                                   │  │
│  └───────────────────────────────────────────────────────────────────────────────────┘  │
└───────────────────────────────────────────────────────────────────────────────────────┘
```

### Inertia Props

```typescript
interface PatrolShowProps {
    patrol: PatrolChecklist & {
        site: Site;
        officer: User;
        results: PatrolResult[];
    };
    activities: ActivityLog[];
    can: {
        execute: boolean;
        update: boolean;
    };
}
```

---

## 11. Mobile Responsive

### Breakpoints

| Prefix | Min-width | Deskripsi |
|---|---|---|
| `sm` | 640px | Small phones (landscape) |
| `md` | 768px | Tablets |
| `lg` | 1024px | Desktop |

### Mobile Patterns

- **Table → Card list**: Setiap row berubah menjadi card vertikal.
- **Filter**: Dropdown wrap ke baris berikutnya. Setiap filter mengambil lebar penuh.
- **Search**: Full width, di atas filter.
- **Action bar**: Sticky di bawah layar.
- **Form**: Single column, semua section ditumpuk vertikal.
- **Date picker**: Native mobile date picker.
- **Select dropdown**: Native mobile select.

### Security Incident Index — Mobile

```
┌──────────────────────────┐
│  Insiden Keamanan        │
│           [+ Laporkan]   │
├──────────────────────────┤
│ [🔍 Cari...]             │
│ [Status ▾] [Tipe ▾]     │
│ [Severity ▾] [Site ▾]   │
│ [Dari] [Sampai] [Reset] │
├──────────────────────────┤
│ 1–15 dari 32  [⬇ CSV]   │
├──────────────────────────┤
│ ┌──────────────────────┐ │
│ │ SEC-0001            │ │
│ │ Akses Tidak Sah      │ │
│ │ [🟣Akses] [🔴Critical]│ │
│ │ [🔵Reported]         │ │
│ │ 11/07  Budi S.       │ │
│ │              [👁]    │ │
│ └──────────────────────┘ │
│ ┌──────────────────────┐ │
│ │ SEC-0002            │ │
│ │ Pencurian di Gudang  │ │
│ │ [🔴Pencurian] [🟠High]│ │
│ │ [🟡Investigasi]      │ │
│ │ 10/07  Sari W.       │ │
│ │              [👁]    │ │
│ └──────────────────────┘ │
├──────────────────────────┤
│      ‹  1  2  3  ›      │
└──────────────────────────┘
```

### Visitor Log Index — Mobile

```
┌──────────────────────────┐
│  Log Pengunjung          │
│           [+ Check-In]   │
├──────────────────────────┤
│ [🔍 Cari...]             │
│ [Status ▾] [Site ▾]     │
│ [Dari] [Sampai] [Reset] │
├──────────────────────────┤
│ On-Site: 3               │
├──────────────────────────┤
│ ┌──────────────────────┐ │
│ │ Andi Pratama         │ │
│ │ PT Maju Jaya         │ │
│ │ Meeting — Sari W.    │ │
│ │ 🟢 On-Site           │ │
│ │ Check-In: 11/07 09:00│ │
│ │ Plat: B 1234 ABC     │ │
│ │ [⬆ Check-Out] [✏]  │ │
│ └──────────────────────┘ │
│ ┌──────────────────────┐ │
│ │ Budi Santoso         │ │
│ │ CV Sukses            │ │
│ │ Delivery — Joni K.    │ │
│ │ 🟢 On-Site           │ │
│ │ Check-In: 11/07 08:30│ │
│ │ [⬆ Check-Out] [✏]  │ │
│ └──────────────────────┘ │
├──────────────────────────┤
│      ‹  1  2  3  ›      │
└──────────────────────────┘
```

### Patrol Execute — Mobile

```
┌──────────────────────────┐
│  ← SPL-0001              │
├──────────────────────────┤
│  Rute Malam              │
│  [⚪ Terjadwal]          │
│  📅 11/07 22:00          │
│  🏭 Plant A              │
│  👤 Andi Pratama         │
│                          │
│  [▶ Mulai Eksekusi]      │
├──────────────────────────┤
│  CHECKPOINTS             │
│  ─────────────            │
│  ┌──────────────────────┐│
│  │ 1. Gerbang Utama     ││
│  │ ○ ✅OK ○ ❌Issue ○ ➖ ││
│  │ Remark: [..........] ││
│  └──────────────────────┘│
│  ┌──────────────────────┐│
│  │ 2. Area Parkir       ││
│  │ ○ ✅OK ● ❌Issue ○ ➖ ││
│  │ Remark: [Wajib diisi]││
│  └──────────────────────┘│
│  ┌──────────────────────┐│
│  │ 3. Gudang            ││
│  │ ● ✅OK ○ ❌Issue ○ ➖ ││
│  │ Remark: [Aman]       ││
│  └──────────────────────┘│
├──────────────────────────┤
│ [✓ Selesaikan Patroli]   │
└──────────────────────────┘
```
