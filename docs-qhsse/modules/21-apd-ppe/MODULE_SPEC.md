# Module Spec — APD / PPE Management

> **Module ID:** `21-apd-ppe`  \
> **Module Code (numbering):** `apd`  \
> **Number Prefix:** `PPE`  \
> **Phase:** Phase 21 — APD / PPE Management  \
> **Status:** Spec ready (approved via interview)

---

## 1. Tujuan Modul

Modul APD (Alat Pelindung Diri / Personal Protective Equipment) mengelola siklus hidup alat pelindung diri secara terstruktur: mulai dari katalog/jenis APD, inventori stok, penugasan (issuance) ke pemegang, inspeksi berkala & insidental, hingga pengembalian/penggantian/penghapusan. Modul ini **terpisah** dari modul Asset & Equipment Safety (Asset = alat/equipment bersama; APD = alat personal yang di-issue ke orang/lokasi).

Tujuan utama:

- Mengelola **katalog jenis APD** (helm, sepatu safety, sarung tangan, kacamata, respirator, harness, earplug, rompi, dll) dengan kategori tubuh (kepala, mata, tangan, kaki, pernapasan, tubuh) dan standar (SNI/EN/ANSI).
- Melacak stok dengan **mixed tracking**: `serial` (item mahal/teridentifikasi, mis. helm, harness) per-unit, dan `batch` (item murah, mis. sarung tangan) per-kuantitas.
- Mengeluarkan (issue) APD ke **pemegang polimorfik**: Employee, Contractor, atau Location/Pos (sebagai pemegang sementara).
- Menjalankan **inspeksi** APD: terjadwal (per-serial) + insidental + kondisi manual, dengan foto evidence via File Service.
- Menjalankan siklus **request → approve → issue → return/dispose** via Workflow Core, dengan permission terpisah.
- Terintegrasi ke Risk Register (hazard → APD wajib), Incident (APD gagal/tidak dipakai), Inspection (temuan "tidak pakai APD" → CAPA), dan Training (fit-test respirator).
- Menyediakan widget dashboard APD (stok rendah, overdue inspeksi/kadaluarsa, compliance per site, top hazard) di dashboard shell yang sudah ada.

---

## 2. Dependency

### Core Foundation (Phase 0 — COMPLETE)

| Dependency | Usage in This Module |
|---|---|
| **Auth** | User login, session, role identification |
| **PermissionService** (Spatie) | Permission keys `apd.*` (view/create/update/delete/issue/inspect/approve/request/export) |
| **NumberingService** | Generate `PPE-{YYYY}-{0001}` untuk katalog & `PPE-ISSUE-{YYYY}-{0001}` untuk issuance |
| **FileService** | Upload/download foto inspeksi & dokumen via `managed_files` |
| **NotificationService** | Notifikasi: request baru, approve, overdue inspeksi, kadaluarsa, stok rendah |
| **AuditTrailService** | Audit log (`module_name='apd'`) untuk create/update/issue/inspect/dispose |
| **CommentService** | Comments (`module_name='apd'`) |
| **ActivityLogService** | Timeline aktivitas |
| **Workflow Core** | `request → approve → issue → return/dispose` |
| **ListQuery** | Paginated, searchable, sortable list |
| **MasterData** | Sites, Areas, Departments, Employees, Contractors, Companies |

### Cross-Module Dependencies

| Module | Relationship |
|---|---|
| `17-asset-equipment-safety` | Sibling; APD terpisah. Tidak ada FK silang. Batas: Asset = alat bersama, APD = alat personal. |
| Risk Register | Hazard/activity (RiskRegister) → APD wajib via tabel pivot `risk_apd_requirements`. |
| `01-incident-reporting` | Incident memiliki field `ppe_involved` (boolean) + `ppe_id` (FK → apd_items, nullable) + `ppe_failure` (boolean). Menunjukkan APD terlibat/gagal. |
| `02-inspection` | Temuan inspeksi `type='ppe_not_used'` → dapat di-escalate ke CAPA (`capa.actions`). |
| `08-training-competency` | TrainingRecord `type='ppe_fit_test'` (respirator) → link ke `apd_items` (serial respirator). |
| Search | Tambah 2 entry di `SearchController::modules()`: katalog APD + issuance. |

### Tech Stack

- Laravel 12 (Form Request, Policy, Service, Eloquent)
- Inertia.js React + TypeScript (frontend)
- Tailwind CSS (UI Indonesian)
- PostgreSQL (data layer)
- Spatie Laravel Permission (RBAC)

---

## 3. User Roles

| # | Role | Akses |
|---|---|---|
| 1 | **Super Admin** | Penuh, bypass scope |
| 2 | **Admin** | Penuh termasuk config |
| 3 | **QHSSE Manager** | Full lifecycle, scope all sites |
| 4 | **QHSSE Officer** | CRUD + issue + inspect, scope assigned site(s) |
| 5 | **Supervisor** | Request APD untuk tim, view |
| 6 | **Department Head** | Approve request di department, view |
| 7 | **Employee/Reporter** | Request APD sendiri, view milik sendiri |
| 8 | **Contractor** | View APD yang dipegang |
| 9 | **Auditor** | View-only + export |
| 10 | **Top Management** | View dashboard + export |

---

## 4. Fitur Lengkap

### 4.1 Katalog Jenis APD (Master)
- Create/edit/list kategori APD: nama, body part (kepala/mata/tangan/kaki/pernapasan/tubuh), standar (SNI/EN/ANSI), masa pakai default (bulan), track_type default (`serial`/`batch`), satuan, foto contoh.
- Nomor `PPE-{YYYY}-{0001}` auto-generate.

### 4.2 Inventori Stok
- Item stok per katalog: `apd_items` dengan `track_type` (`serial`/`batch`).
- Serial: tiap unit 1 row (`serial_number` unik, `status` per-unit: available/in_use/under_inspection/damaged/expired/disposed).
- Batch: 1 row mewakili N unit (`quantity`, `available_quantity`).
- Field bersama: `site_id`, `area_id`, `location_note`, `lot_number`, `received_date`, `expiry_date` (untuk disposable/expiry), `condition`, `next_inspection_date` (serial), `manufacturer`, `model`.
- Stok minimum per katalog/lokasi → alert.
- Receive barang (menambah stok) via form, masuk audit trail.

### 4.3 Issuance (Penugasan)
- Issue APD ke pemegang polimorfik (`holder_type` + `holder_id`): Employee, Contractor, atau Location.
- `apd_issuances`: `issue_number`, `apd_item_id`, `quantity` (untuk batch), `issued_to_type`, `issued_to_id`, `issued_by`, `issue_date`, `expected_return_date`/`expiry_date`, `status` (draft/requested/approved/issued/returned/disposed/rejected), `condition_out`, `notes`.
- Untuk serial: status item berubah `available` → `in_use`, `current_holder` di-set.
- Untuk batch: `available_quantity` berkurang.
- Workflow: `request → approve (Dept Head/Supervisor) → issue (QHSSE Officer)`.

### 4.4 Return / Replace / Dispose
- Return: status `issued` → `returned`, serial kembali `available` (atau `damaged`/`expired`), batch `available_quantity` naik.
- Replace: issue baru menggantikan yang rusak/habis.
- Dispose: serial/batch dihapus dari stok (status `disposed`), masuk audit + alasan.

### 4.5 Inspeksi APD
- `apd_inspections`: `apd_item_id` (serial), `inspection_type` (`scheduled`/`incidental`/`manual`), `inspected_by`, `inspection_date`, `result` (layak/tidak_layak), `condition`, `notes`, `next_inspection_date`, foto via `managed_files` collection `inspection`.
- Jadwal: `apd_items.next_inspection_date` (dihitung dari katalog `default_lifespan_months` atau fixed date). Overdue → flag + notifikasi.
- Kondisi manual: update `condition`/`status` tanpa jadwal.

### 4.6 Integrasi
- **Risk Register**: hazard → APD wajib (`risk_apd_requirements`). Tampil di Show Risk & saat buat issuance (rekomendasi APD).
- **Incident**: field `ppe_involved`, `ppe_id`, `ppe_failure`. Jika `ppe_failure=true` → bisa buat CAPA.
- **Inspection**: temuan `ppe_not_used` → escalate CAPA.
- **Training**: TrainingRecord `type='ppe_fit_test'` link `apd_item_id`.

### 4.7 Search
Tambah entry di `SearchController::modules()`:
- `apd_items` (label "APD / Inventori", route `apd.items.index`, permission `apd.view`) — cari nomor serial, katalog, pemegang, lokasi.
- `apd_issuances` (label "APD / Penugasan", route `apd.issuances.index`, permission `apd.view`) — cari nomor issue, pemegang.

### 4.8 Dashboard Widgets (di dashboard shell)
1. **Stok Rendah** — katalog/lokasi di bawah `min_stock`.
2. **Overdue Inspeksi / Kadaluarsa** — `apd_items` dengan `next_inspection_date < now()` atau `expiry_date < now()`.
3. **Compliance per Site** — % pemegang Employee yang memegang APD wajib (dari Risk Register) vs terpenuhi.
4. **Top Hazard** — hazard Risk Register dengan kebutuhan APD terbanyak.

---

## 5. Business Rules

### BR-01: Numbering
- Katalog: `PPE-{YYYY}-{0001}` via `NumberingService::generate('apd', ...)`.
- Issuance: `PPE-ISSUE-{YYYY}-{0001}` via `NumberingService::generate('apd_issue', ...)`.
- Unique constraint; immutable setelah generate.

### BR-02: Mixed Tracking
- `apd_items.track_type` menentukan perilaku:
  - `serial`: 1 row = 1 unit fisik, `serial_number` wajib & unik, `status` per-unit.
  - `batch`: 1 row = N unit, `quantity` & `available_quantity` wajib, `serial_number` null.
- Issuance serial: consume 1 unit (status → in_use). Issuance batch: kurangi `available_quantity` sebesar `quantity`.

### BR-03: Holder Polymorphic
- `issued_to_type` ∈ {`employee`, `contractor`, `location`}.
- `issued_to_id` → `employees.id` / `contractors.company_id` (atau `companies.id` untuk contractor) / `areas.id` (pos/lokasi).
- Validasi: holder ada & aktif.

### BR-04: Workflow States
Issuance status: `draft → requested → approved → issued → returned|disposed|rejected`.
- `requested` dibuat oleh Supervisor/Employee/Contractor.
- `approved` oleh Department Head/Supervisor (perm `apd.approve`).
- `issued` oleh QHSSE Officer (perm `apd.issue`).
- Transisi dicek di backend (Policy/Service), tidak hanya UI.

### BR-05: Inspection
- `scheduled`: di-generate otomatis saat serial di-issue (set `next_inspection_date`).
- `result='tidak_layak'` → item status `damaged`, wajib return/dispose.
- Foto evidence wajib untuk `incidental` & `scheduled` yang `tidak_layak`.

### BR-06: Expiry
- `expiry_date` (disposable) < now → status `expired`, notifikasi.
- `next_inspection_date` < now → overdue, notifikasi mingguan.

### BR-07: Audit Trail
`module_name='apd'`, event: `catalog.created/updated`, `item.received`, `item.disposed`, `issuance.requested/approved/issued/returned/rejected`, `inspection.done`.

### BR-08: Scope
- Site-scoped: QHSSE Officer lihat site terkait. Manager/Admin semua site. Employee/Contractor hanya milik sendiri.

---

## 6. Permission Keys

| # | Permission Key | Description |
|---|---|---|
| 1 | `apd.view` | Lihat katalog, stok, issuance, inspeksi |
| 2 | `apd.create` | Buat katalog & terima stok |
| 3 | `apd.update` | Edit katalog & item |
| 4 | `apd.delete` | Soft delete katalog/item (Admin) |
| 5 | `apd.issue` | Issue & return/dispose (QHSSE Officer) |
| 6 | `apd.inspect` | Buat inspeksi |
| 7 | `apd.approve` | Approve request issuance |
| 8 | `apd.request` | Ajukan request issuance (Supervisor/Employee/Contractor) |
| 9 | `apd.export` | Export CSV |

Semua di-enforce server-side via Policy.

---

## 7. Role-Permission Matrix

| Role | view | create | update | delete | issue | inspect | approve | request | export |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Super Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Manager | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Officer | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Supervisor | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ✅ |
| Department Head | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ✅ |
| Employee/Reporter | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| Contractor | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| Auditor | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Top Management | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |

---

## 8. Notification Events

| Event | Trigger | Recipients |
|---|---|---|
| `apd.requested` | Issuance di-request | Dept Head/Supervisor (approver) |
| `apd.approved` | Di-approve | Requester + QHSSE Officer |
| `apd.issued` | Di-issue | Pemegang |
| `apd.inspection_overdue` | `next_inspection_date` < now (scheduler) | QHSSE Officer site |
| `apd.expired` | `expiry_date` < now (scheduler) | QHSSE Officer site |
| `apd.low_stock` | `available_quantity` < `min_stock` | QHSSE Officer |

---

## 9. File Attachment Rules
- `module_name='apd'`, collections: `catalog_photo`, `inspection`.
- Max 10MB, format PDF/JPG/PNG.
- Download via authorized endpoint.

---

## 10. File Structure

```
app/Models/Modules/Apd/
    ApdCatalog.php
    ApdItem.php
    ApdIssuance.php
    ApdInspection.php
    RiskApdRequirement.php
app/Http/Controllers/Modules/Apd/
    ApdCatalogController.php
    ApdItemController.php
    ApdIssuanceController.php
    ApdInspectionController.php
app/Http/Requests/Modules/Apd/
    StoreApdCatalogRequest.php
    UpdateApdCatalogRequest.php
    ReceiveApdItemRequest.php
    StoreApdIssuanceRequest.php
    UpdateApdIssuanceStatusRequest.php
    StoreApdInspectionRequest.php
app/Policies/Modules/Apd/
    ApdCatalogPolicy.php
    ApdItemPolicy.php
    ApdIssuancePolicy.php
    ApdInspectionPolicy.php
database/migrations/
    2026_07_16_000001_create_apd_catalogs_table.php
    2026_07_16_000002_create_apd_items_table.php
    2026_07_16_000003_create_apd_issuances_table.php
    2026_07_16_000004_create_apd_inspections_table.php
    2026_07_16_000005_create_risk_apd_requirements_table.php
database/factories/Modules/Apd/
    ApdCatalogFactory.php
    ApdItemFactory.php
    ApdIssuanceFactory.php
    ApdInspectionFactory.php
database/seeders/
    ApdSeeder.php
resources/js/Pages/Modules/Apd/
    Catalog/{Index,Form,Show}.tsx
    Items/{Index,Show}.tsx
    Issuances/{Index,Form,Show}.tsx
    Inspections/{Index,Form}.tsx
tests/Feature/Modules/Apd/
    ApdCatalogTest.php
    ApdItemTest.php
    ApdIssuanceTest.php
    ApdInspectionTest.php
docs-qhsse/modules/21-apd-ppe/
    MODULE_SPEC.md
    DATA_MODEL.md
    WORKFLOW.md
```

---

## 11. Dashboard Metrics (widget di dashboard shell)

| Widget | Query |
|---|---|
| Stok Rendah | `ApdItem::where('available_quantity','<',DB::raw('min_stock'))` (batch) + katalog di bawah ambang |
| Overdue / Kadaluarsa | `ApdItem::where('next_inspection_date','<',now())->orWhere('expiry_date','<',now())` (serial) |
| Compliance per Site | pemegang Employee dengan APD wajib (Risk) vs issued |
| Top Hazard | `RiskApdRequirement` group by `risk_register_id` count |

---

## 12. Report / Export
- Export CSV katalog, item, issuance (filter mengikuti list).
- CSV columns mengikuti DATA_MODEL.
