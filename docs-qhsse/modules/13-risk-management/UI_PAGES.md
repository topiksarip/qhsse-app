# UI Pages — Risk Management (HIRADC/JSA)

Spesifikasi wireframe halaman UI untuk modul Risk Management.

Semua label menggunakan Bahasa Indonesia. Tech stack: Laravel 12 + Inertia React + TypeScript + Tailwind CSS.

---

## Daftar Isi

1. [Navigasi](#1-navigasi)
2. [Color Coding](#2-color-coding)
3. [Halaman Index — Daftar Risk Register](#3-halaman-index--daftar-risk-register)
4. [Halaman Form — Buat/Edit Risk Register](#4-halaman-form--buatedit-risk-register)
5. [Halaman Show — Detail Risk Register](#5-halaman-show--detail-risk-register)
6. [Mobile Responsive](#6-mobile-responsive)

---

## 1. Navigasi

### Penempatan Menu

Tambahkan item `Risk Register` pada group `Modul QHSSE` di `AuthenticatedLayout.tsx`.

```typescript
const menuGroups: { label: string; items: MenuItem[] }[] = [
    {
        label: 'Core',
        items: [
            { label: 'Dashboard', routeName: 'dashboard', active: 'dashboard' },
            // ... existing Core items
        ],
    },
    {
        label: 'Modul QHSSE',
        items: [
            { label: 'Laporan Insiden', routeName: 'incident.reports.index', active: 'incident.reports.*', permission: 'incident.reports.view' },
            { label: 'Risk Register', routeName: 'risk.registers.index', active: 'risk.registers.*', permission: 'risk.registers.view' },  // ← NEW
        ],
    },
    {
        label: 'Masters',
        // ... existing Masters items
    },
    {
        label: 'Admin',
        // ... existing Admin items
    },
];
```

### Wireframe Navigasi (Desktop)

```
┌──────────────────────────────────────────────────────────────────────┐
│  [Logo] QHSSE   Core ▾   Modul QHSSE ▾   Masters ▾   Admin ▾  [User]│
│                        ┌──────────────────┐                         │
│                        │ Laporan Insiden  │                         │
│                        │ Risk Register    │                         │
│                        └──────────────────┘                         │
└──────────────────────────────────────────────────────────────────────┘
```

### Wireframe Navigasi (Mobile — Hamburger)

```
┌──────────────────────┐
│  [Logo] QHSSE   [☰]  │
├──────────────────────┤
│  CORE                │
│   Dashboard          │
│   Sites              │
│                      │
│  MODUL QHSSE         │
│   Laporan Insiden    │
│   Risk Register      │
│                      │
│  MASTERS             │
│   Severities         │
│   ...                │
│                      │
│  ADMIN               │
│   ...                │
├──────────────────────┤
│  John Doe            │
│  john@example.com    │
│  Profile   Log Out   │
└──────────────────────┘
```

### Permission Filtering

Menu hanya tampil jika user memiliki permission `risk.registers.view`. Filtering dilakukan via `auth.permissions`.

---

## 2. Color Coding

### Risk Level Badge

| Risk Level | Tailwind Class | Preview |
|---|---|---|
| RED (Tinggi) | `bg-red-100 text-red-800 border-red-300` | ⬤ **RED** |
| ORANGE (Sedang-Tinggi) | `bg-orange-100 text-orange-800 border-orange-300` | ⬤ **ORANGE** |
| YELLOW (Sedang) | `bg-yellow-100 text-yellow-800 border-yellow-300` | ⬤ **YELLOW** |
| GREEN (Rendah) | `bg-green-100 text-green-800 border-green-300` | ⬤ **GREEN** |
| Belum Dinilai | `bg-gray-100 text-gray-500 border-gray-300` | ⬤ **—** |

### Status Badge

| Status | Tailwind Class | Label (Indonesian) |
|---|---|---|
| `identified` | `bg-blue-100 text-blue-800` | Teridentifikasi |
| `assessed` | `bg-indigo-100 text-indigo-800` | Dinilai |
| `controls_needed` | `bg-orange-100 text-orange-800` | Perlu Kontrol |
| `controls_in_place` | `bg-cyan-100 text-cyan-800` | Kontrol Terpasang |
| `monitored` | `bg-green-100 text-green-800` | Dipantau |
| `obsolete` | `bg-gray-300 text-gray-700` | Tidak Berlaku |

### Type Badge

| Type | Tailwind Class | Label |
|---|---|---|
| `hazard_identification` | `bg-purple-100 text-purple-800` | Hazard ID |
| `jsa` | `bg-teal-100 text-teal-800` | JSA |
| `hiradc` | `bg-blue-100 text-blue-800` | HIRADC |
| `risk_assessment` | `bg-amber-100 text-amber-800` | Risk Assessment |

### Risk Matrix Grid Color Coding

```
         P1        P2        P3        P4        P5
       (Jarang)  (Tidak    (Mungkin) (Kemung-  (Hampir
                 Mungkin)            kalian    Pasti)
                                  Besar)
S4     ┌───────┐┌───────┐┌───────┐┌───────┐┌───────┐
(Critical)│ ORANGE ││  RED  ││  RED  ││  RED  ││  RED  │
       └───────┘└───────┘└───────┘└───────┘└───────┘
S3     ┌───────┐┌───────┐┌───────┐┌───────┐┌───────┐
(High) │YELLOW ││ORANGE ││ORANGE ││  RED  ││  RED  │
       └───────┘└───────┘└───────┘└───────┘└───────┘
S2     ┌───────┐┌───────┐┌───────┐┌───────┐┌───────┐
(Medium)│ GREEN ││YELLOW ││ORANGE ││ORANGE ││  RED  │
       └───────┘└───────┘└───────┘└───────┘└───────┘
S1     ┌───────┐┌───────┐┌───────┐┌───────┐┌───────┐
(Low)  │ GREEN ││ GREEN ││YELLOW ││YELLOW ││ORANGE │
       └───────┘└───────┘└───────┘└───────┘└───────┘

Cell colors:
  GREEN  = bg-green-200 hover:bg-green-300 border-green-400
  YELLOW = bg-yellow-200 hover:bg-yellow-300 border-yellow-400
  ORANGE = bg-orange-200 hover:bg-orange-300 border-orange-400
  RED    = bg-red-200 hover:bg-red-300 border-red-400

Selected cell:
  ring-2 ring-offset-2 ring-blue-500 + scale-105
```

---

## 3. Halaman Index — Daftar Risk Register

### Wireframe (Desktop)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  Risk Register                                          [+ Buat Risk Register] │
│  Daftar risiko teridentifikasi dan terdaftar                                  │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─ Search ────────────────────────┐  ┌─ Filters ──────────────────────┐    │
│  │ 🔍 Cari nomor, judul, aktivitas │  │ Site: [All Sites ▾]             │    │
│  └──────────────────────────────────┘  │ Type: [All Types ▾]            │    │
│                                        │ Status: [All Status ▾]         │    │
│                                        │ Risk Level: [All Levels ▾]    │    │
│                                        │ [Terapkan Filter] [Reset]      │    │
│                                        └────────────────────────────────┘    │
│                                                                              │
│  ┌──────────────────────────────────────────────────────────────────────────┐│
│  │ ● ● ● ●                    Risk Register               [Export CSV]   ││
│  ├──────────────────────────────────────────────────────────────────────────┤│
│  │ Nomor          │ Judul              │ Tipe    │ Risk Level │ Status    ││
│  ├────────────────┼────────────────────┼─────────┼────────────┼───────────┤│
│  │ RSK-2026-0001  │ Risiko Jatuh dari  │ HIRADC  │ ⬤ RED      │ Dinilai   ││
│  │                │ Ketinggian         │         │            │           ││
│  ├────────────────┼────────────────────┼─────────┼────────────┼───────────┤│
│  │ RSK-2026-0002  │ Hazard Area        │ Hazard  │ ⬤ ORANGE   │ Perlu     ││
│  │                │ Kimia              │ ID      │            │ Kontrol   ││
│  ├────────────────┼────────────────────┼─────────┼────────────┼───────────┤│
│  │ RSK-2026-0003  │ JSA Pekerjaan      │ JSA     │ ⬤ YELLOW   │ Dipantau  ││
│  │                │ Pengelasan         │         │            │           ││
│  ├────────────────┼────────────────────┼─────────┼────────────┼───────────┤│
│  │ RSK-2026-0004  │ Risiko Tersengat   │ Risk    │ ⬤ GREEN    │ Kontrol   ││
│  │                │ Listrik            │ Assess  │            │ Terpasang ││
│  ├────────────────┼────────────────────┼─────────┼────────────┼───────────┤│
│  │ RSK-2026-0005  │ Hazard Panas       │ HIRADC  │ ⬤ —        │ Teriden-  ││
│  │                │ Pipa Steam         │         │            │ tifikasi  ││
│  └────────────────┴────────────────────┴─────────┴────────────┴───────────┘│
│                                                                              │
│  Showing 1 to 5 of 42 results                    [← Prev] [1] [2] [3] [→]  │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Table Columns

| # | Column | Field | Width | Sortable | Notes |
|---|---|---|---|---|---|
| 1 | Nomor | `register_number` | 140px | ✅ | Click to view detail |
| 2 | Judul | `title` | flex | ✅ | Truncated to 1 line |
| 3 | Tipe | `type` | 120px | ✅ | Type badge |
| 4 | Risk Level | `riskLevel.risk_level` | 120px | ✅ | Color-coded badge |
| 5 | Status | `status` | 140px | ✅ | Status badge |
| 6 | Actions | — | 80px | ❌ | View icon button |

### Components

- `RiskLevelBadge` — renders colored badge based on `risk_level` (RED/ORANGE/YELLOW/GREEN/—)
- `StatusBadge` — renders status badge with Indonesian label
- `TypeBadge` — renders type badge
- `SearchInput` — debounced search input
- `FilterSelect` — dropdown filter for site, type, status, risk level
- `Pagination` — standard Laravel pagination
- `ExportButton` — triggers CSV export download

### Empty State

```
┌──────────────────────────────────────────────────────────────────────────┐
│                                                                          │
│                          📋                                             │
│                                                                          │
│                    Belum ada risk register                              │
│                                                                          │
│            Belum ada risiko yang teridentifikasi.                        │
│            Mulai dengan membuat risk register baru.                     │
│                                                                          │
│                    [+ Buat Risk Register]                               │
│                                                                          │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Halaman Form — Buat/Edit Risk Register

### Wireframe (Desktop)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  ← Kembali    Buat Risk Register                                              │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─ Informasi Umum ───────────────────────────────────────────────────────┐ │
│  │                                                                        │ │
│  │  Judul *            [                                            ]     │ │
│  │                                                                        │ │
│  │  Tipe *             [○ Hazard ID] [○ JSA] [● HIRADC] [○ Risk Assess]   │ │
│  │                                                                        │ │
│  │  Site *             [Pilih Site                              ▾]        │ │
│  │  Area               [Pilih Area                              ▾]        │ │
│  │  Department         [Pilih Department                        ▾]        │ │
│  │                                                                        │ │
│  │  Aktivitas *        [                                            ]     │ │
│  │                                                                        │ │
│  │  Hazard *           ┌──────────────────────────────────────────┐      │ │
│  │                     │                                          │      │ │
│  │                     │ Jatuh dari ketinggian saat bekerja di     │      │ │
│  │                     │ atas scaffolding tanpa harness            │      │ │
│  │                     │                                          │      │ │
│  │                     └──────────────────────────────────────────┘      │ │
│  │                                                                        │ │
│  │  Existing Controls  ┌──────────────────────────────────────────┐      │ │
│  │                     │ Guard rail di scaffolding, safety harness│      │ │
│  │                     │ tersedia tetapi tidak selalu digunakan    │      │ │
│  │                     └──────────────────────────────────────────┘      │ │
│  │                                                                        │ │
│  │  Owner *            [Pilih Owner                             ▾]        │ │
│  │  Review Date        [                          ] [📅]                  │ │
│  └────────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  ┌─ Initial Risk Assessment (Sebelum Kontrol) ───────────────────────────┐ │
│  │                                                                        │ │
│  │  Severity *         [Pilih Severity                            ▾]        │ │
│  │                     (LOW / MEDIUM / HIGH / CRITICAL)                  │ │
│  │                                                                        │ │
│  │  Probability *      [Pilih Probability                          ▾]        │ │
│  │                     (Jarang / Tidak Mungkin / Mungkin /               │ │
│  │                      Kemungkinan Besar / Hampir Pasti)                │ │
│  │                                                                        │ │
│  │  ┌─ Risk Matrix Grid ─────────────────────────────────────────────┐   │ │
│  │  │                                                                │   │ │
│  │  │           P1      P2      P3      P4      P5                  │   │ │
│  │  │         (Jarang) (Tidak   (Mungkin)(Kemung- (Hampir           │   │ │
│  │  │                 Mungkin)          kalian   Pasti)             │   │ │
│  │  │                                  Besar)                       │   │ │
│  │  │  S4    ┌─────┐┌─────┐┌─────┐┌─────┐┌─────┐                  │   │ │
│  │  │ (Critical)│ORG  ││ RED ││ RED ││ RED ││ RED │                  │   │ │
│  │  │        └─────┘└─────┘└─────┘└─────┘└─────┘                  │   │ │
│  │  │  S3    ┌─────┐┌─────┐┌─────┐┌─────┐┌─────┐                  │   │ │
│  │  │ (High) │YEL  ││ORG  ││ORG  ││[RED]││ RED │  ← SELECTED      │   │ │
│  │  │        └─────┘└─────┘└─────┘└─────┘└─────┘    (S3 × P4)     │   │ │
│  │  │  S2    ┌─────┐┌─────┐┌─────┐┌─────┐┌─────┐                  │   │ │
│  │  │ (Med)  │GRN  ││YEL  ││ORG  ││ORG  ││ RED │                  │   │ │
│  │  │        └─────┘└─────┘└─────┘└─────┘└─────┘                  │   │ │
│  │  │  S1    ┌─────┐┌─────┐┌─────┐┌─────┐┌─────┐                  │   │ │
│  │  │ (Low)  │GRN  ││GRN  ││YEL  ││YEL  ││ORG  │                  │   │ │
│  │  │        └─────┘└─────┘└─────┘└─────┘└─────┘                  │   │ │
│  │  │                                                                │   │ │
│  │  │  Klik sel untuk memilih kombinasi severity × probability     │   │ │
│  │  └────────────────────────────────────────────────────────────────┘   │ │
│  │                                                                        │ │
│  │  Initial Risk Level:  ⬤ RED  (Tinggi)                                 │ │
│  │                                                                        │ │
│  └────────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  ┌─ Residual Risk Assessment (Setelah Kontrol Tambahan) ─────────────────┐ │
│  │                                                                        │ │
│  │  Additional Controls ┌──────────────────────────────────────────┐     │ │
│  │                      │ 1. Wajib pakai full body harness         │     │ │
│  │                      │ 2. Inspection scaffolding sebelum digunakan│    │ │
│  │                      │ 3. Training height safety                │     │ │
│  │                      └──────────────────────────────────────────┘     │ │
│  │                                                                        │ │
│  │  Residual Severity  [Pilih Severity                            ▾]       │ │
│  │  Residual Probability [Pilih Probability                        ▾]      │ │
│  │                                                                        │ │
│  │  ┌─ Residual Risk Matrix Grid ────────────────────────────────────┐   │ │
│  │  │  (Same grid as above, clicking selects residual combination)  │   │ │
│  │  └────────────────────────────────────────────────────────────────┘   │ │
│  │                                                                        │ │
│  │  Residual Risk Level:  ⬤ YELLOW  (Sedang)                             │ │
│  │                                                                        │ │
│  │  ┌─ Before / After Comparison ────────────────────────────────────┐   │ │
│  │  │                                                                  │   │ │
│  │  │  Initial:   ⬤ RED      (S4 × P4)                                │   │ │
│  │  │  Residual:  ⬤ YELLOW  (S3 × P3)  ↓ Improvement                  │   │ │
│  │  │                                                                  │   │ │
│  │  └──────────────────────────────────────────────────────────────────┘   │ │
│  └────────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  [Batal]                                          [Simpan Risk Register]     │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Form Fields

| # | Field | Type | Required | Validation | Notes |
|---|---|---|---|---|---|
| 1 | `title` | text input | ✅ | `required|string|max:255` | |
| 2 | `type` | radio buttons | ✅ | `required|in:hazard_identification,jsa,hiradc,risk_assessment` | 4 options |
| 3 | `site_id` | select | ✅ | `required|exists:sites,id` | Populated from sites |
| 4 | `area_id` | select | ❌ | `nullable|exists:areas,id` | Filtered by selected site |
| 5 | `department_id` | select | ❌ | `nullable|exists:departments,id` | Filtered by selected site |
| 6 | `activity` | text input | ✅ | `required|string|max:500` | Work activity |
| 7 | `hazard` | textarea | ✅ | `required|string` | Identified hazard(s) |
| 8 | `existing_controls` | textarea | ❌ | `nullable|string` | |
| 9 | `severity_id` | select | ✅ (for assess) | `nullable|exists:severities,id` | LOW/MEDIUM/HIGH/CRITICAL |
| 10 | `probability_id` | select | ✅ (for assess) | `nullable|integer|min:1|max:5` | 1-5 |
| 11 | `risk_level_id` | hidden | auto | Auto-calculated from severity × probability | Set by controller via `RiskMatrixLevel` lookup |
| 12 | `additional_controls` | textarea | ❌ | `nullable|string` | Required for `implement_controls` |
| 13 | `residual_severity_id` | select | ❌ | `nullable|exists:severities,id` | |
| 14 | `residual_probability_id` | select | ❌ | `nullable|integer|min:1|max:5` | |
| 15 | `residual_risk_level_id` | hidden | auto | Auto-calculated | |
| 16 | `owner_id` | select | ✅ | `required|exists:users,id` | Risk owner |
| 17 | `review_date` | date picker | ❌ | `nullable|date` | Next review date |

### Risk Matrix Grid Component

```typescript
interface RiskMatrixGridProps {
  severities: Severity[];          // [{id:1, code:'LOW', level:1, color:'green'}, ...]
  probabilities: ProbabilityOption[]; // [{level:1, label:'Jarang'}, ...]
  matrixLevels: RiskMatrixLevel[];  // [{id:1, severity_level:4, probability_level:1, risk_level:'ORANGE'}, ...]
  selectedSeverityId: number | null;
  selectedProbabilityId: number | null;
  onSelect: (severityId: number, probabilityId: number, riskLevelId: number) => void;
  disabled?: boolean;
}
```

The grid renders a table where:
- Rows = severities (ordered descending by level: CRITICAL first, LOW last)
- Columns = probabilities (ordered ascending: P1 left, P5 right)
- Each cell is colored by `risk_level` (GREEN/YELLOW/ORANGE/RED)
- Clicking a cell calls `onSelect(severityId, probabilityId, riskLevelId)`
- Selected cell has `ring-2 ring-blue-500` and `scale-105`

### Form Behavior

- **Create mode**: All fields empty. `register_number` auto-generated on save.
- **Edit mode**: Pre-populated. `register_number` read-only.
- **Risk level auto-calculation**: When user selects severity + probability (via dropdown or grid click), the frontend queries the matrix levels to find the matching `risk_level_id` and displays it. The `risk_level_id` is sent to the backend.
- **Residual section**: Only visible/expanded after `additional_controls` is filled or when editing an existing record with residual data.

---

## 5. Halaman Show — Detail Risk Register

### Wireframe (Desktop)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  ← Kembali    RSK-2026-0001                                                   │
│               Risiko Jatuh dari Ketinggian                                    │
│               [HIRADC]  [⬤ RED - Dinilai]                                    │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─ Informasi Umum ───────────────────────────────────────────────────────┐ │
│  │                                                                        │ │
│  │  Nomor              RSK-2026-0001                                      │ │
│  │  Tipe               HIRADC                                             │ │
│  │  Site               Plant A                                            │ │
│  │  Area               Area Produksi                                      │ │
│  │  Department         HSE Department                                      │ │
│  │  Aktivitas          Bekerja di atas scaffolding                        │ │
│  │  Hazard             Jatuh dari ketinggian saat bekerja di atas         │ │
│  │                     scaffolding tanpa harness                           │ │
│  │  Existing Controls  Guard rail di scaffolding, safety harness          │ │
│  │                     tersedia tetapi tidak selalu digunakan              │ │
│  │  Owner              John Doe (QHSSE Officer)                           │ │
│  │  Review Date        2026-10-01                                         │ │
│  │  Created At         2026-07-11 14:30                                   │ │
│  │                                                                        │ │
│  └────────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  ┌─ Before / After Risk Comparison ──────────────────────────────────────┐ │
│  │                                                                        │ │
│  │  ┌─────────────────────────┐    ┌─────────────────────────┐           │ │
│  │  │  INITIAL RISK           │    │  RESIDUAL RISK           │           │ │
│  │  │  (Sebelum Kontrol)      │    │  (Setelah Kontrol)       │           │ │
│  │  │                         │    │                         │           │ │
│  │  │  ┌───────────────────┐  │    │  ┌───────────────────┐  │           │ │
│  │  │  │                   │  │    │  │                   │  │           │ │
│  │  │  │      ⬤ RED        │  │    │  │    ⬤ YELLOW      │  │           │ │
│  │  │  │    (TINGGI)       │  │    │  │   (SEDANG)        │  │           │ │
│  │  │  │                   │  │    │  │                   │  │           │ │
│  │  │  └───────────────────┘  │    │  └───────────────────┘  │           │ │
│  │  │                         │    │                         │           │ │
│  │  │  Severity:   CRITICAL    │    │  Severity:   HIGH        │           │ │
│  │  │  Probability: Likely (4) │    │  Probability: Possible (3)│          │ │
│  │  │  S4 × P4 = RED          │    │  S3 × P3 = YELLOW        │           │ │
│  │  │                         │    │                         │           │ │
│  │  └─────────────────────────┘    └─────────────────────────┘           │ │
│  │                                                                        │ │
│  │  Additional Controls:                                                  │ │
│  │  1. Wajib pakai full body harness                                     │ │
│  │  2. Inspection scaffolding sebelum digunakan                           │ │
│  │  3. Training height safety                                            │ │
│  │                                                                        │ │
│  │  ↓ Risk Improvement: RED → YELLOW                                    │ │
│  │                                                                        │ │
│  └────────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  ┌─ Risk Matrix Visualization ────────────────────────────────────────────┐ │
│  │                                                                        │ │
│  │         P1      P2      P3      P4      P5                            │ │
│  │  S4   ┌─────┐┌─────┐┌─────┐┌─────┐┌─────┐                            │ │
│  │       │ORG  ││RED  ││RED  ││[RED]││RED  │  ← Initial (S4 × P4)      │ │
│  │       └─────┘└─────┘└─────┘└─────┘└─────┘                            │ │
│  │  S3   ┌─────┐┌─────┐┌─────┐┌─────┐┌─────┐                            │ │
│  │       │YEL  ││ORG  ││[YEL]││ORG  ││RED  │  ← Residual (S3 × P3)     │ │
│  │       └─────┘└─────┘└─────┘└─────┘└─────┘                            │ │
│  │  S2   ┌─────┐┌─────┐┌─────┐┌─────┐┌─────┐                            │ │
│  │       │GRN  ││YEL  ││ORG  ││ORG  ││RED  │                            │ │
│  │       └─────┘└─────┘└─────┘└─────┘└─────┘                            │ │
│  │  S1   ┌─────┐┌─────┐┌─────┐┌─────┐┌─────┐                            │ │
│  │       │GRN  ││GRN  ││YEL  ││YEL  ││ORG  │                            │ │
│  │       └─────┘└─────┘└─────┘└─────┘└─────┘                            │ │
│  │                                                                        │ │
│  │  ● Initial  ● Residual                                                │ │
│  └────────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  ┌─ Status & Actions ────────────────────────────────────────────────────┐  │
│  │                                                                       │  │
│  │  Status: [ Dinilai ]                                                  │  │
│  │                                                                       │  │
│  │  Available Actions:                                                  │  │
│  │  [Needs Controls]  [Edit]  [Obsolete]                               │  │
│  │                                                                       │  │
│  └───────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Lampiran ────────────────────────────────────────────────────────────┐  │
│  │                                                                       │  │
│  │  📎 jsa_scaffolding.pdf    250 KB    [Download] [Delete]            │  │
│  │  📷 hazard_photo.jpg       1.2 MB    [Download] [Delete]            │  │
│  │                                                                       │  │
│  │  [+ Upload File]                                                     │  │
│  └───────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Komentar ────────────────────────────────────────────────────────────┐  │
│  │                                                                       │  │
│  │  John Doe — 2026-07-11 15:00                                         │  │
│  │  Risk level RED perlu segera ditindak lanjuti.                       │  │
│  │                                                                       │  │
│  │  Jane Smith — 2026-07-11 16:00                                       │  │
│  │  Setuju, additional controls sudah disiapkan.                        │  │
│  │                                                                       │  │
│  │  [Tambah Komentar]                                                   │  │
│  └───────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─ Activity Log ────────────────────────────────────────────────────────┐  │
│  │                                                                       │  │
│  │  ● 2026-07-11 14:30  Risk register dibuat (John Doe)                 │  │
│  │  ● 2026-07-11 14:35  Risk assessment dilakukan (John Doe)            │  │
│  │  ● 2026-07-11 15:00  Komentar ditambahkan (John Doe)                 │  │
│  │  ● 2026-07-11 16:00  File diupload: hazard_photo.jpg (Jane Smith)    │  │
│  │  ● 2026-07-11 16:30  Status: identified → assessed (John Doe)        │  │
│  │                                                                       │  │
│  └───────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Show Page Sections

| # | Section | Content |
|---|---|---|
| 1 | Header | Register number, title, type badge, risk level badge, status badge |
| 2 | Informasi Umum | All general fields (site, area, department, activity, hazard, existing controls, owner, review date, created at) |
| 3 | Before/After Risk Comparison | Side-by-side initial vs residual risk level with severity × probability breakdown |
| 4 | Risk Matrix Visualization | Full matrix grid with both initial and residual cells highlighted |
| 5 | Status & Actions | Current status + available action buttons |
| 6 | Lampiran | File attachments with upload/download/delete |
| 7 | Komentar | Threaded comments |
| 8 | Activity Log | Chronological activity timeline |

### Available Actions by Status

| Status | Actions Available |
|---|---|
| `identified` | Edit, Assess |
| `assessed` | Edit, Needs Controls, Obsolete |
| `controls_needed` | Edit, Implement Controls, Obsolete |
| `controls_in_place` | Edit, Monitor, Obsolete |
| `monitored` | Edit, Obsolete |
| `obsolete` | (none — terminal) |

### Action Button Visibility

| Action | Permission Required | Status Required |
|---|---|---|
| Edit | `risk.registers.update` | not `obsolete` |
| Assess | `risk.registers.assess` | `identified` |
| Needs Controls | `risk.registers.assess` | `assessed` |
| Implement Controls | `risk.registers.assess` | `controls_needed` |
| Monitor | `risk.registers.assess` | `controls_in_place` |
| Obsolete | `risk.registers.assess` | not `obsolete` |

---

## 6. Mobile Responsive

### Index Page (Mobile)

```
┌──────────────────────┐
│  Risk Register       │
│  [+ Buat]  [Export]  │
├──────────────────────┤
│  🔍 Cari...          │
│  [Filter ▾]          │
├──────────────────────┤
│  ┌──────────────────┐│
│  │ RSK-2026-0001    ││
│  │ Risiko Jatuh...   ││
│  │ [HIRADC] ⬤ RED   ││
│  │ Dinilai           ││
│  └──────────────────┘│
│  ┌──────────────────┐│
│  │ RSK-2026-0002    ││
│  │ Hazard Area...   ││
│  │ [Hazard] ⬤ ORANGE││
│  │ Perlu Kontrol    ││
│  └──────────────────┘│
├──────────────────────┤
│  [←] [1/3] [→]       │
└──────────────────────┘
```

### Form Page (Mobile)

- All fields stack vertically
- Risk matrix grid scrolls horizontally if needed (min-width 320px)
- Radio buttons for type become a select dropdown
- Textareas are full-width with auto-grow

### Show Page (Mobile)

- Before/After comparison stacks vertically (initial on top, residual below)
- Risk matrix grid scrolls horizontally
- Tabs for: Detail, Matrix, Attachments, Comments, Activity

```
┌──────────────────────┐
│  ← RSK-2026-0001     │
│  Risiko Jatuh...     │
│  [HIRADC] ⬤ RED     │
├──────────────────────┤
│ [Detail][Matrix][Lampiran][Komentar]
├──────────────────────┤
│  INITIAL: ⬤ RED      │
│  S4 × P4              │
│                      │
│  RESIDUAL: ⬤ YELLOW │
│  S3 × P3              │
│  ↓ Improvement       │
├──────────────────────┤
│  Status: Dinilai     │
│  [Needs Controls]    │
│  [Edit]  [Obsolete]  │
└──────────────────────┘
```

### Component List

| Component | File | Description |
|---|---|---|
| `RiskRegisterIndex` | `Pages/Modules/RiskManagement/Index.tsx` | List page with table, search, filters |
| `RiskRegisterForm` | `Pages/Modules/RiskManagement/Form.tsx` | Create/edit form with risk matrix grid |
| `RiskRegisterShow` | `Pages/Modules/RiskManagement/Show.tsx` | Detail page with before/after comparison |
| `RiskMatrixGrid` | `components/RiskMatrixGrid.tsx` | Interactive severity × probability grid selector |
| `RiskLevelBadge` | `components/RiskLevelBadge.tsx` | Colored badge for risk level |
| `StatusBadge` | `components/StatusBadge.tsx` | Status badge (reusable, already exists) |
| `TypeBadge` | `components/TypeBadge.tsx` | Risk register type badge |
| `BeforeAfterComparison` | `components/BeforeAfterComparison.tsx` | Side-by-side initial vs residual display |
