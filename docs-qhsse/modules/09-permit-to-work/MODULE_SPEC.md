# Module Spec — Permit to Work

> **Module ID:** `09-permit-to-work`  
> **Module Code (numbering):** `permit`  
> **Number Prefix:** `PTW`  
> **Workflow Code:** `PERMIT_WORKFLOW`  
> **Phase:** Phase 9 — Permit to Work  
> **Status:** Ready for coding

---

## 1. Tujuan Modul

Modul Permit to Work (PTW) menyediakan sistem pengelolaan izin kerja untuk aktivitas berisiko tinggi. Modul ini mengakomodasi tujuh jenis izin kerja: Hot Work, Working at Height, Confined Space, Electrical, Excavation, Lifting, dan Other.

Tujuan utama:

- Memastikan setiap pekerjaan berisiko tinggi memiliki **izin tertulis** dengan nomor unik (PTW-YYYY-NNNN) yang di-generate otomatis pada saat create.
- Memvalidasi **periode berlaku** izin (start_datetime + end_datetime) sehingga pekerjaan tidak boleh dilakukan di luar jendela waktu yang disetujui.
- Mensyaratkan **checklist keselamatan** yang harus ditandatangani (signed) sebelum izin dapat diaktifkan (activate).
- Memastikan **approval** dilakukan oleh QHSSE Officer/Manager atau Supervisor yang berwenang sebelum izin berlaku.
- Menampilkan **validity countdown** di halaman Index: Active, Expired, Expiring Soon (≤ 24 jam sebelum end_datetime).
- Mengelola **workflow status** yang jelas: Draft → Submitted → Under Review → Approved → Active → Closed (dengan jalur Reject).
- Mengirim **notifikasi** ke approver saat izin di-submit, ke requester saat di-approve/reject, dan ke stakeholder saat di-close atau akan kedaluwarsa.
- Menyediakan **audit trail** lengkap untuk semua perubahan kritikal (create, submit, review, approve, activate, close, reject, checklist signing).
- Menghubungkan ke modul **Risk/JSA** via `jsa_reference`, **Contractor** via `contractor_id`, dan **Asset** via `work_location`.
- Menyediakan **dashboard metrics** dan **export CSV** untuk pelaporan manajemen.

---

## 2. Dependency

### Core Foundation (Phase 0 — COMPLETE)

| Dependency | Usage in This Module |
|---|---|
| **Auth** | User login, session, role identification |
| **PermissionService** (Spatie) | 8 permission keys `permit.work.*` |
| **NumberingService** | Generate `PTW-YYYY-NNNN` on create (include_site_code=true) |
| **WorkflowService** | Status transitions per `PERMIT_WORKFLOW` definition |
| **FileService** | Upload/download evidence files via `managed_files` table |
| **NotificationService** | In-app + email notifications via `core_notifications` table |
| **AuditTrailService** | Audit log via `audit_logs` table |
| **CommentService** | Comments via `comments` table (`module_name='permit'`) |
| **ActivityLogService** | Activity timeline via `activity_logs` table |
| **ExportService** | CSV export via `permit.work.export` permission |
| **MasterData** | Sites, Areas, Departments, Companies, Users |

### Cross-Module (future/current phases)

| Module | Relationship |
|---|---|
| `13-risk-management` | Permit dapat mereferensikan JSA/Risk assessment via `jsa_reference` |
| `16-contractor-management` | Permit dapat dikaitkan ke contractor via `contractor_id` → `companies` |
| `17-asset-equipment-safety` | Permit dapat mereferensikan lokasi kerja/aset via `work_location` |
| `10-training` | Verifikasi training kompetensi pekerja (future integration) |
| `06-notification` | Notifikasi submit, approve, reject, close, expiring soon |

### Tech Stack

- Laravel 12 (backend: Form Request, Policy, Service, Eloquent)
- Inertia.js React + TypeScript (frontend)
- Tailwind CSS (styling, UI in Indonesian)
- PostgreSQL (data layer)
- Spatie Laravel Permission (RBAC)

---

## 3. User Roles

10 roles terlibat dalam modul ini (sesuai `RolesAndPermissionsSeeder`):

| # | Role | Deskripsi Peran dalam Permit to Work |
|---|---|---|
| 1 | **Super Admin** | Akses penuh ke semua data dan fungsi. Bypass scope. |
| 2 | **Admin** | Akses sistem penuh termasuk konfigurasi, numbering, master data. |
| 3 | **QHSSE Manager** | Review, approve, reject, activate, close permit. Scope: all sites. |
| 4 | **QHSSE Officer** | Review, approve, reject, activate, close permit. Scope: assigned site(s). |
| 5 | **Supervisor** | Create, update, submit permit untuk department-nya. Dapat approve permit. Scope: department. |
| 6 | **Department Head** | Lihat permit di department-nya. Scope: department. |
| 7 | **Employee/Reporter** | Create draft, submit, update own draft permit. Scope: own. |
| 8 | **Contractor** | Create draft, submit permit untuk pekerjaan contractor-nya. Scope: company. |
| 9 | **Auditor** | View-only semua data dalam scope audit. Export. Tidak create/edit. |
| 10 | **Top Management** | View dashboard & report, export. Scope: all. Tidak create/edit. |

---

## 4. Fitur Lengkap

### 4.1 Permit CRUD

- **Create** — Form pembuatan izin kerja. Nomor `PTW-YYYY-NNNN` di-generate otomatis oleh `NumberingService` pada saat record dibuat. Status awal: `draft`. Pemilihan jenis izin (type) memicu render checklist dinamis.
- **List** — Halaman list dengan search (nomor, judul, lokasi kerja), filter (site, type, status, validity status, contractor, date range), pagination (default 15 per page), dan tombol Export CSV. Menampilkan validity countdown badge.
- **Detail** — Halaman detail menampilkan: nomor, jenis, judul, deskripsi pekerjaan, lokasi (site/area/department), contractor, periode berlaku (start/end datetime + countdown), risk level, JSA reference, status, workflow timeline, checklist dengan signing, attachments, comments, activity log, audit trail.
- **Update** — Edit record. Hanya bisa edit jika status `draft`. Setelah submit, record tidak bisa di-edit kecuali di-reject.
- **Delete** — Soft delete. Hanya Super Admin / Admin. Tidak bisa delete record yang sudah `active` atau `closed`.

### 4.2 Workflow Actions

- **Save Draft** — Simpan tanpa validasi mandatory fields. Status tetap `draft`. Checklist items belum wajib diisi.
- **Submit** — Validasi mandatory fields. Status: `draft` → `submitted`. Trigger notifikasi ke QHSSE Officer/Manager dan Supervisor terkait.
- **Start Review** — QHSSE Officer/Manager/Supervisor memulai review. Status: `submitted` → `under_review`.
- **Approve** — Approve permit. Memerlukan permission `permit.work.approve`. Status: `under_review` → `approved`. Set `approved_by` dan `approved_at`. Tidak mengaktifkan izin secara otomatis.
- **Activate** — Mengaktifkan izin. **Syarat: semua checklist items harus sudah di-sign (is_checked=true, checked_by, checked_at).** Status: `approved` → `active`. Izin mulai berlaku berdasarkan `start_datetime`.
- **Close** — Menutup izin setelah pekerjaan selesai. Wajib isi reason. Status: `active` → `closed`. Set `closed_by` dan `closed_at`. Trigger notifikasi ke requester dan stakeholder.
- **Reject** — Tolak permit. Wajib isi reason (min: 10 karakter). Status: `submitted` atau `under_review` → `rejected`. Trigger notifikasi ke requester.

### 4.3 Checklist Management

- Setiap jenis izin (type) memiliki checklist items yang berbeda, di-seed saat pembuatan permit.
- Checklist items disimpan di tabel `permit_checklists` dengan relasi `permit_id`.
- Setiap item memiliki: `item_text`, `is_checked` (boolean), `checked_by` (FK→users), `checked_at` (timestamp).
- **Signing** checklist dilakukan di halaman Show oleh user yang berwenang (permission `permit.work.update` atau `permit.work.approve`).
- **Semua checklist items harus di-sign sebelum izin dapat di-activate.**
- Jika checklist diubah setelah aktivasi, izin otomatis kembali ke `approved` (deactivated).

### 4.4 Validity Period Management

- Setiap permit memiliki `start_datetime` dan `end_datetime` yang menentukan periode berlaku.
- `validity_hours` dihitung otomatis sebagai selisih jam antara start dan end datetime.
- Validity status di halaman Index:
  - **Active** — `status = 'active'` dan `now()` antara start_datetime dan end_datetime. Badge hijau.
  - **Expired** — `now()` > `end_datetime` dan status masih `active`. Badge merah.
  - **Expiring Soon** — `now()` ≤ 24 jam sebelum `end_datetime` dan status `active`. Badge kuning.
  - **Not Started** — `status` di `approved`/`draft`/`submitted`/`under_review`. Badge abu-abu.

### 4.5 Evidence Management

- Upload file bukti (foto, JSA document, risk assessment) melalui File Service core.
- Collection: `evidence`.
- Multiple files per permit.
- Download melalui authorized endpoint (permission check).
- Tidak bisa hapus file setelah status `closed`.

### 4.6 Comments & Activity Timeline

- Comment dapat ditambahkan oleh user yang punya akses ke record.
- Activity log otomatis mencatat: create, submit, review, approve, activate, close, reject, checklist signing, field changes.
- Timeline ditampilkan di halaman detail.

### 4.7 Notification

- 5 event notifikasi: `permit.submitted`, `permit.reviewing`, `permit.approved`, `permit.rejected`, `permit.closed`, `permit.expiring_soon`.
- In-app notification via `core_notifications` table.
- Email notification jika configured.

### 4.8 Dashboard & Reporting

- Dashboard widget: total permit, breakdown by type/status/site, active permits, expiring soon, expired.
- Export CSV dengan kolom yang dispesifikasikan di Section 12.

---

## 5. Jenis Izin Kerja (Permit Types)

Tujuh jenis izin kerja, masing-masing dengan checklist yang berbeda:

| # | Code | Nama | Deskripsi |
|---|---|---|---|
| 1 | `hot_work` | **Hot Work** | Pekerjaan yang menghasilkan panas, api, atau percikan — pengelasan, pemotongan, grinding, soldering. Wajib fire watch, APD tahan api, area bebas bahan mudah terbakar. |
| 2 | `working_at_height` | **Working at Height** | Pekerjaan pada ketinggian ≥ 1.8 meter — scaffolding, rooftop, ladder work, rope access. Wajib harness, anchor point inspection, fall protection system. |
| 3 | `confined_space` | **Confined Space** | Pekerjaan di ruang terbatas — tank, vessel, pit, duct, tunnel. Wajib gas test, ventilasi, entry permit, standby person, rescue plan. |
| 4 | `electrical` | **Electrical** | Pekerjaan pada sistem kelistrikan — LOTO, panel, switchgear, cable work. Wajib LOTO procedure, voltage verification, PPE electrical rated. |
| 5 | `excavation` | **Excavation** | Pekerjaan penggalian tanah — trenching, boring, piling. Wajib utility scan (underground), shoring/sloping, safe access/egress. |
| 6 | `lifting` | **Lifting** | Pekerjaan angkat angkut dengan crane/hoist — heavy lift, rigging. Wajib lift plan, load calculation, equipment certification, rigger/signalman. |
| 7 | `other` | **Other** | Jenis pekerjaan berisiko lain yang tidak termasuk kategori di atas. Checklist generic: risk assessment, APD, emergency procedure. |

### Checklist Template per Jenis Izin

#### hot_work
1. APD tahan api tersedia dan dipakai (goggles, gloves, apron)
2. Fire extinguisher tersedia di area kerja (min. 2 unit)
3. Area 10 meter bebas bahan mudah terbakar
4. Fire watch ditunjuk dan siap
5. Hot work permit area di-barricade
6. Sistem ventilasi memadai
7. Emergency response plan diketahui semua pekerja

#### working_at_height
1. Full body harness dipakai dan di-inspect
2. Anchor point terverifikasi (min. 22 kN)
3. Scaffolding di-inspect oleh competent person
4. Edge protection / guard rail terpasang
5. Fall protection system aktif
6. Tidak ada pekerjaan di bawah area tanpa proteksi
7. Emergency rescue plan siap

#### confined_space
1. Gas test dilakukan (O2, LEL, H2S, CO)
2. Ventilasi mekanis aktif
3. Entry permit ditandatangani
4. Standby person ditunjuk di entrance
5. Rescue equipment siap (tripod, winch, SCBA)
6. Komunikasi antara entrant dan attendant
7. Lockout/Tagout semua sumber energi
8. Continuous gas monitoring aktif

#### electrical
1. LOTO procedure dijalankan dan diverifikasi
2. Voltage test dilakukan (verify zero energy)
3. PPE electrical rated dipakai (gloves, mats)
4. Grounding temporary terpasang
5. Barricade dan warning sign terpasang
6. Competent person melakukan pekerjaan
7. Emergency procedure untuk electrical shock diketahui

#### excavation
1. Underground utility scan dilakukan dan didokumentasikan
2. Shoring/sloping sesuai depth (≥ 1.2m wajib shoring)
3. Safe access/egress (ladder setiap 7.5m)
4. Spoil pile ≥ 0.6m dari edge
5. Gas test untuk confined space trench
6. Barricade dan warning sign terpasang
7. Daily inspection oleh competent person

#### lifting
1. Lift plan disiapkan dan di-approve
2. Load calculation dilakukan
3. Crane/hoist certification valid
4. Rigger dan signalman certified
5. Sling dan rigging gear di-inspect
6. Area lifting di-barricade
7. Weather condition sesuai (wind speed < limit)
8. Communication radio tersedia

#### other
1. Risk assessment / JSA dilakukan
2. APD sesuai pekerjaan dipakai
3. Emergency procedure diketahui
4. Pekerja competent dan tersertifikasi
5. Area kerja di-barricade

---

## 6. Business Rules

### BR-01: Numbering on Create

- Nomor permit di-generate **saat record dibuat** (POST create), bukan saat submit.
- Format: `PTW-YYYY-NNNN` (contoh: `PTW-2026-0001`).
- `include_site_code=true` → jika site memiliki code, nomor menjadi `PTW-{SITE_CODE}-YYYY-NNNN` (contoh: `PTW-JKT-2026-0001`).
- Sumber: `NumberingService::generate('permit', $actor, $siteCode)`.
- Konfigurasi numbering (sudah di-seed di `numbering_formats`):
  - `module_name`: `permit`
  - `prefix`: `PTW`
  - `padding`: `4`
  - `separator`: `-`
  - `reset_frequency`: `yearly`
  - `include_year`: `true`
  - `include_site_code`: `true`
  - `sample`: `PTW-2026-0001`
- Nomor bersifat **unique**. Jika terjadi race condition, database unique constraint mencegah duplikat; service melakukan retry dengan increment.
- Nomor tidak dapat diubah setelah di-generate.

### BR-02: Draft Save Without Mandatory Fields

- Saat status `draft`, record dapat disimpan tanpa mengisi mandatory fields.
- Field wajib hanya divalidasi saat **submit**.
- Checklist items auto-generated berdasarkan type saat create, tetapi belum wajib di-sign.

### BR-03: Submit Validates Mandatory Fields

Saat user melakukan **submit** (transition `draft` → `submitted`), sistem memvalidasi field mandatory berikut:

| Field | Validation Rule |
|---|---|
| `permit_number` | auto-generated, unique |
| `type` | required, in: hot_work, working_at_height, confined_space, electrical, excavation, lifting, other |
| `title` | required, string, max:255 |
| `description` | required, text |
| `site_id` | required, exists in sites |
| `work_location` | required, string, max:255 |
| `work_description` | required, text |
| `start_datetime` | required, datetime, after_or_equal: now |
| `end_datetime` | required, datetime, after: start_datetime |
| `validity_hours` | auto-calculated (end - start in hours, min: 1) |
| `risk_level` | nullable, in: low, medium, high, critical |
| `jsa_reference` | nullable, string, max:255 |
| `contractor_id` | nullable, exists in companies |
| `area_id` | nullable, exists in areas |
| `department_id` | nullable, exists in departments |

Jika validasi gagal, submit ditolak dan record tetap berstatus `draft`.

### BR-04: Checklist Must Be Signed Before Activation

- Sebelum izin dapat di-activate (`approved` → `active`), **semua checklist items** harus sudah di-sign:
  - `is_checked = true`
  - `checked_by` tidak NULL (FK→users)
  - `checked_at` tidak NULL (timestamp)
- Jika ada satu item yang belum di-sign, activation ditolak dengan error: "Semua checklist items harus di-sign sebelum izin dapat diaktifkan."
- Checklist signing dilakukan oleh user dengan permission `permit.work.update` atau `permit.work.approve`.

### BR-05: Approval Requires QHSSE/Supervisor

- Transition `under_review` → `approved` (action `approve`) memerlukan permission `permit.work.approve`.
- Hanya role berikut yang dapat approve: QHSSE Manager, QHSSE Officer, Supervisor.
- Setelah approve: `approved_by` dan `approved_at` di-set.
- Approver tidak boleh sama dengan requester (pembuat permit) untuk menghindari conflict of interest.

### BR-06: Reject Requires Reason

- Transition `submitted` → `rejected` dan `under_review` → `rejected` memerlukan field `reason` (wajib, text, min:10 karakter).
- Reason disimpan di `workflow_histories.reason` dan `permits.cancellation_reason`.
- Notifikasi dikirim ke requester.

### BR-07: Close Requires Reason

- Transition `active` → `closed` memerlukan field `reason` (wajib, text, min:10 karakter).
- Reason disimpan di `workflow_histories.reason`.
- Setelah close, record menjadi read-only. Tidak bisa edit, tidak bisa hapus file evidence.
- `closed_by` dan `closed_at` di-set.

### BR-08: Validity Period Enforcement

- `start_datetime` harus ≥ now() saat submit.
- `end_datetime` harus > `start_datetime`.
- `validity_hours` dihitung otomatis: `round((end_datetime - start_datetime) / 3600)`.
- Minimum validity: 1 jam.
- Jika `now()` > `end_datetime` dan status masih `active`, sistem menandai izin sebagai **Expired** (badge merah) tetapi tidak otomatis close — close harus dilakukan manual.
- Sistem mengirim notifikasi `permit.expiring_soon` 24 jam sebelum `end_datetime` jika izin masih `active`.

### BR-09: Audit Trail on Critical Changes

Audit trail dicatat untuk event berikut (via `audit_logs` table, `module_name='permit'`):

| Event | Auditable | Old/New Values |
|---|---|---|
| `permit.created` | Permit record | new_values: all fields |
| `permit.updated` | Permit record | changed fields only |
| `permit.submitted` | Permit record | status change |
| `permit.reviewing` | Permit record | status change |
| `permit.approved` | Permit record | status change + approved_by + approved_at |
| `permit.activated` | Permit record | status change |
| `permit.closed` | Permit record | status change + reason + closed_by + closed_at |
| `permit.rejected` | Permit record | status change + reason |
| `permit.checklist.signed` | PermitChecklist | is_checked, checked_by, checked_at |
| `permit.file.uploaded` | ManagedFile | new_values |
| `permit.file.downloaded` | ManagedFile | metadata: user, ip |

### BR-10: Data Visibility by Scope

Data visibility mengikuti role scope (sesuai `CorePermissions::roleMap()`):

| Scope | Who | What They See |
|---|---|---|
| `own` | Employee/Reporter, Contractor | Only permits they created |
| `department` | Supervisor, Department Head | Permits in their department |
| `site` | QHSSE Officer | Permits in their assigned site(s) |
| `all` | QHSSE Manager, Top Management, Auditor, Super Admin, Admin | All permits |

- Scope check dilakukan **server-side** di Controller/Policy, bukan di frontend.
- Contractor hanya melihat permit milik company/contractor-nya.
- Query scope: filter berdasarkan `created_by` (own), `department_id` (department), `site_id` (site), atau no filter (all).

---

## 7. Permission Keys

8 permission keys untuk modul Permit to Work:

| # | Permission Key | Description |
|---|---|---|
| 1 | `permit.work.view` | View permit list and detail. Scope-filtered. |
| 2 | `permit.work.create` | Create new permit record. Generates PTW number. |
| 3 | `permit.work.update` | Update permit record. Only draft status. Own or scope-based. Checklist signing. |
| 4 | `permit.work.submit` | Submit permit (draft → submitted). Validates mandatory fields. |
| 5 | `permit.work.review` | Review/reject permit. QHSSE roles + Supervisor. |
| 6 | `permit.work.approve` | Approve permit (under_review → approved). QHSSE/Supervisor only. |
| 7 | `permit.work.close` | Close permit (active → closed). Requires reason. |
| 8 | `permit.work.export` | Export permit list to CSV. Scope-filtered. |

### Implementation Notes

- Permission keys mengikuti format `{module}.{entity}.{action}` → `permit.work.*`.
- Keys harus di-register di `CorePermissions::all()`.
- Keys di-assign ke roles via `CorePermissions::roleMap()`.
- File upload/download menggunakan permission core: `core.files.upload`, `core.files.download`.
- Comment menggunakan permission core: `core.comments.view`, `core.comments.create`.
- Workflow transition menggunakan permission core: `core.workflow.transition`.

---

## 8. Role-Permission Matrix

| Role | `view` | `create` | `update` | `submit` | `review` | `approve` | `close` | `export` |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Super Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Manager | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| QHSSE Officer | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Supervisor | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| Department Head | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Employee/Reporter | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Contractor | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Auditor | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Top Management | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |

### Notes

- **Supervisor** dapat `approve` (sebagai approver untuk pekerjaan di department-nya) tetapi tidak dapat `close`. Close adalah otoritas QHSSE Officer/Manager.
- **QHSSE Officer** dan **QHSSE Manager** dapat melakukan full lifecycle: review, approve, activate (via checklist signing + activate transition), close.
- **Employee/Reporter** dan **Contractor** dapat create/update/submit permit miliknya sendiri (scope: own/company).
- **Auditor** dan **Top Management** hanya view + export (read-only).
- Semua permission di-enforce **server-side** via Laravel Policy atau Gate.

---

## 9. Notification Events

6 event notifikasi untuk modul Permit to Work:

### 9.1 `permit.submitted`

| Property | Value |
|---|---|
| **Trigger** | User melakukan submit (transition `draft` → `submitted`) |
| **Recipients** | All users with role `QHSSE Officer` and `QHSSE Manager` in the same site scope. Supervisor of the department. |
| **Type** | `permit.submitted` |
| **Title (template)** | `Izin Kerja Baru: {permit.permit_number}` |
| **Message (template)** | `{requester.name} telah mengajukan izin kerja {permit.permit_number} - {permit.title} ({permit.type}) di {site.name}. Mohon lakukan review.` |
| **Action URL** | `/permits/{permit.id}` |
| **Module/Reference** | `module_name='permit'`, `reference_id={permit.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.2 `permit.reviewing`

| Property | Value |
|---|---|
| **Trigger** | QHSSE Officer/Manager melakukan start review (transition `submitted` → `under_review`) |
| **Recipients** | The original requester (`permits.created_by` — via `users` table) |
| **Type** | `permit.reviewing` |
| **Title (template)** | `Izin Kerja Sedang Direview: {permit.permit_number}` |
| **Message (template)** | `Izin kerja {permit.permit_number} - {permit.title} sedang dalam proses review oleh {reviewer.name} ({reviewer.role}).` |
| **Action URL** | `/permits/{permit.id}` |
| **Module/Reference** | `module_name='permit'`, `reference_id={permit.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.3 `permit.approved`

| Property | Value |
|---|---|
| **Trigger** | QHSSE Officer/Manager/Supervisor melakukan approve (transition `under_review` → `approved`) |
| **Recipients** | The original requester (`permits.created_by`) |
| **Type** | `permit.approved` |
| **Title (template)** | `Izin Kerja Disetujui: {permit.permit_number}` |
| **Message (template)** | `Izin kerja {permit.permit_number} - {permit.title} telah disetujui oleh {approver.name}. Mohon lengkapi checklist dan aktifkan izin sebelum mulai bekerja.` |
| **Action URL** | `/permits/{permit.id}` |
| **Module/Reference** | `module_name='permit'`, `reference_id={permit.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.4 `permit.rejected`

| Property | Value |
|---|---|
| **Trigger** | Approver melakukan reject (transition `submitted`/`under_review` → `rejected`) |
| **Recipients** | The original requester (`permits.created_by`) |
| **Type** | `permit.rejected` |
| **Title (template)** | `Izin Kerja Ditolak: {permit.permit_number}` |
| **Message (template)** | `Izin kerja {permit.permit_number} - {permit.title} ditolak oleh {rejecter.name}. Alasan: {reject_reason}. Silakan perbaiki dan kirim ulang.` |
| **Action URL** | `/permits/{permit.id}` |
| **Module/Reference** | `module_name='permit'`, `reference_id={permit.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.5 `permit.closed`

| Property | Value |
|---|---|
| **Trigger** | QHSSE Officer/Manager melakukan close (transition `active` → `closed`) |
| **Recipients** | Requester (`permits.created_by`), Supervisor of the department, Contractor (if applicable) |
| **Type** | `permit.closed` |
| **Title (template)** | `Izin Kerja Ditutup: {permit.permit_number}` |
| **Message (template)** | `Izin kerja {permit.permit_number} - {permit.title} telah ditutup oleh {closer.name}. Alasan: {close_reason}.` |
| **Action URL** | `/permits/{permit.id}` |
| **Module/Reference** | `module_name='permit'`, `reference_id={permit.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### 9.6 `permit.expiring_soon`

| Property | Value |
|---|---|
| **Trigger** | Scheduled job: 24 jam sebelum `end_datetime` jika izin masih `active` |
| **Recipients** | Requester (`permits.created_by`), QHSSE Officer di site terkait, Supervisor |
| **Type** | `permit.expiring_soon` |
| **Title (template)** | `Izin Kerja Akan Kedaluwarsa: {permit.permit_number}` |
| **Message (template)** | `Izin kerja {permit.permit_number} - {permit.title} akan kedaluwarsa dalam 24 jam pada {end_datetime}. Mohon perpanjang atau tutup izin.` |
| **Action URL** | `/permits/{permit.id}` |
| **Module/Reference** | `module_name='permit'`, `reference_id={permit.id}` |
| **Channel** | In-app (`core_notifications`) + Email (if configured) |

### Implementation Notes

- Notification dikirim setelah DB transaction commit (use Laravel Event/Listener or Observer pattern).
- In-app notification: sync write to `core_notifications`.
- Email notification: dispatch to queue if available, fallback sync.
- Template variables di-resolve di NotificationService atau listener.
- Recipient resolution: query users with target role + matching scope (site/department).
- `permit.expiring_soon` dijalankan via scheduled command (Laravel Scheduler, setiap 1 jam).

---

## 10. File Attachment Rules

### 10.1 Storage Configuration

| Property | Value |
|---|---|
| **Service** | Core FileService (`App\Core\File\FileService`) |
| **Table** | `managed_files` |
| **module_name** | `permit` |
| **reference_id** | `permits.id` |
| **collection** | `evidence` |
| **Disk** | `local` (private, not publicly accessible) |
| **Path pattern** | `permit/{permit_id}/evidence/{stored_name}` |

### 10.2 Validation Rules

| Rule | Value |
|---|---|
| **Allowed extensions** | `jpg`, `jpeg`, `png`, `gif`, `webp`, `pdf`, `doc`, `docx`, `xls`, `xlsx` |
| **Allowed MIME types** | Corresponding to extensions above |
| **Max file size** | 25 MB per file |
| **Max files per permit** | 20 |
| **Filename** | Original filename stored in `original_name`; generated UUID-based name in `stored_name` |

### 10.3 Access Rules

- **Upload**: User must have `permit.work.update` (or be the requester of a draft).
- **Download**: User must have `permit.work.view` and be within data scope of the permit.
- **Delete**: User must have `permit.work.update` AND permit status must NOT be `closed` or `rejected`. Once a permit is `closed`, evidence files cannot be deleted except by Super Admin / Admin.
- Download endpoint streams file from private storage; no direct public URL.
- File access logged in audit trail (`permit.file.downloaded`).

---

## 11. Dashboard Metrics

### 11.1 KPI Cards

| Metric | Query | Display |
|---|---|---|
| **Total Permits** | Count all permits in scope | Number + icon |
| **Active Permits** | Count where status = `active` AND now() between start and end | Number, green |
| **Expiring Soon** | Count where status = `active` AND end_datetime ≤ now() + 24h | Number, yellow badge |
| **Expired** | Count where status = `active` AND now() > end_datetime | Number, red badge |
| **Draft/Pending** | Count where status IN (draft, submitted, under_review) | Number, blue |
| **Closed This Month** | Count closed in current month | Number + trend arrow |

### 11.2 Charts

| Chart | Type | Data |
|---|---|---|
| **Monthly Trend** | Line chart | Permit count by month (last 12 months), split by type |
| **By Type** | Donut | Count by permit type (Hot Work, Height, etc.) |
| **By Status** | Donut | Count by workflow status |
| **By Site** | Horizontal bar | Count by site (top 10) |
| **By Risk Level** | Stacked bar | Count by risk_level |

### 11.3 Table Widgets

| Widget | Columns | Filter |
|---|---|---|
| **Recent Permits** | Number, Title, Type, Site, Status, Validity, Created At | Last 10, scoped |
| **Active Permits** | Number, Title, Type, Site, End Datetime, Time Remaining | Status = active |
| **Expiring Soon** | Number, Title, Type, Site, End Datetime | Active + expiring ≤ 24h |

### 11.4 Filters

Dashboard metrics support:
- Date range filter (default: current year)
- Site filter
- Type filter
- Risk level filter

---

## 12. Export Spec

### 12.1 CSV Export

| Property | Value |
|---|---|
| **Format** | CSV (UTF-8 with BOM for Excel compatibility) |
| **Filename** | `permits_export_{YYYYMMDD_HHmmss}.csv` |
| **Permission** | `permit.work.export` |
| **Scope** | Follows user's data scope (own/department/site/all) |
| **Filter** | Follows current list page filter parameters |

### 12.2 CSV Columns (in order)

| # | Column Header | Field Source | Notes |
|---|---|---|---|
| 1 | `Nomor` | `permit_number` | PTW-YYYY-NNNN |
| 2 | `Judul` | `title` | |
| 3 | `Jenis` | `type` | hot_work, working_at_height, etc. |
| 4 | `Deskripsi Pekerjaan` | `work_description` | Truncated to 500 chars |
| 5 | `Site` | `site.name` | Via `site_id` |
| 6 | `Area` | `area.name` | Via `area_id`, nullable |
| 7 | `Department` | `department.name` | Via `department_id`, nullable |
| 8 | `Contractor` | `contractor.name` | Via `contractor_id`, nullable |
| 9 | `Lokasi Kerja` | `work_location` | |
| 10 | `Mulai` | `start_datetime` | Format: Y-m-d H:i |
| 11 | `Berakhir` | `end_datetime` | Format: Y-m-d H:i |
| 12 | `Durasi (jam)` | `validity_hours` | |
| 13 | `Risk Level` | `risk_level` | low, medium, high, critical |
| 14 | `JSA Reference` | `jsa_reference` | Nullable |
| 15 | `Status` | `status` | draft, submitted, under_review, approved, active, closed, rejected |
| 16 | `Approved By` | `approved_by` → `users.name` | Nullable |
| 17 | `Closed By` | `closed_by` → `users.name` | Nullable |
| 18 | `Created At` | `created_at` | Format: Y-m-d H:i |

---

## 13. Acceptance Criteria

1. User dengan permission `permit.work.create` dapat membuat permit baru dengan nomor PTW auto-generated.
2. User tanpa permission `permit.work.create` ditolak saat membuat permit.
3. Submit memvalidasi semua field mandatory termasuk start_datetime dan end_datetime.
4. Checklist items auto-generated berdasarkan type saat create.
5. Checklist items harus di-sign (is_checked=true, checked_by, checked_at) sebelum izin dapat di-activate.
6. Approval hanya bisa dilakukan oleh QHSSE Officer/Manager atau Supervisor dengan permission `permit.work.approve`.
7. Approver tidak boleh sama dengan requester (conflict of interest).
8. Close memerlukan reason (min 10 karakter).
9. Reject memerlukan reason (min 10 karakter).
10. Validity countdown ditampilkan di halaman Index (active/expired/expiring soon).
11. Notifikasi terkirim ke penerima tepat saat submit, approve, reject, close, dan expiring soon.
12. Audit trail tercatat untuk semua perubahan kritikal.
13. List dapat search/filter/pagination dan export CSV.
14. Data visibility mengikuti role scope (own/department/site/all).
15. File evidence dapat di-upload/download sesuai permission.

---

## 14. Open Questions

1. Apakah izin yang expired otomatis di-close atau tetap manual?
2. Apakah ada fitur perpanjangan (extension) izin? Jika ya, field apa yang ditambahkan?
3. Apakah QR verification diperlukan di Phase 9 atau ditangguhkan?
4. Apakah LOTO (Lockout/Tagout) perlu tracking terpisah atau cukup sebagai checklist item pada type `electrical`?
5. Apakah integrasi dengan training competency check (modul 10) diperlukan di Phase 9?
6. Default `validity_hours` maksimum per permit type (mis. hot_work max 8 jam)?
7. Apakah approver bisa di-set per type (mis. confined space hanya QHSSE Manager)?
8. Template report PDF untuk permit cetakan fisik — apakah diperlukan di Phase 9?
