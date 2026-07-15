# DEBUG-MODULE12-QUALITY-PLAN.md — Debug Mendalam Modul 12 (Quality Management)

**Tanggal:** 2026-07-15
**Modul:** `12-quality-management` (Phase 12 — 2 resource: NCR + Customer Complaint)
**Metode:** systematic-debugging (Iron Law: TIDAK ada fix tanpa root-cause evidence)
**Status:** 🔴 **Campuran EKSTRIM: CustomerComplaint BENAR, NCR ROBEK (no transitions, bypass close tanpa RCA, scope manipulatif).**

---

## 0. Konteks, Koreksi Pemetaan, & Bukti Segar

> **KOREKSI JUJUR:** Di rencana sebelumnya saya menyebut "Modul 11 = Communication" sebagai modul
> terakhir. Itu **SALAH** karena saya memakai penomoran urutan folder `docs-qhsse/modules/` yang
> sebenarnya tidak linier. Pemindaian penuh menunjukkan **20 folder docs** dan **12 routes aktif**.
> Fakta sebenarnya:
> - `18-communication-campaign` = Communication (bukan `12-communication-reporting`) → itu Modul 11 saya.
> - **`12-quality-management` = Quality = INI Modul 12 yang benar** (folder docs `12-`, controller
>   `Quality`, route `quality.php` L223).
> Penomoran "Modul 1..12" saya pakai = urutan rilis di SOUL.md (Incident, Investigation, CAPA,
> Inspection, Audit, Document, Training, Permit, Environment, Security, Communication, **Quality**).
> Jadi: **Modul 12 = Quality Management** (folder `12-quality-management`). Plan ini benar untuk
> Quality; Communication sudah saya tulis sebagai Modul 11 (file `DEBUG-MODULE11-COMMUNICATION-PLAN.md`).

Modul 12 Quality punya **2 resource** dgn kualitas ekstrim tidak merata:

| Resource | Kualitas | Bukti |
|----------|---------|------|
| **CustomerComplaint** | ✅ BENAR | `ComplaintAccess` scope `core.scope.*`; `close` `lockForUpdate` + `resolution` + `isOpen()`; audit+activity; export jalan; test `CustomerComplaintTest` |
| **Ncr** | 🔴 ROBEK | **TIDAK ADA method `submit`/`review`/`close`/`reject`/`reopen`**; tidak pakai `WorkflowService`; scope `?scope=all` manipulatif; `update()` bisa set `closed` tanpa RCA |

**NCR adalah Modul 9-level gap**: records dibuat tapi **stuck di `open`** (tidak bisa diproses),
dan malah bisa di-set `closed` via `update()` biasa **tanpa RCA** (langgar WORKFLOW.md §2).

| # | Gap | Bukti | WORKFLOW.md mensyaratkan |
|---|-----|------|--------------------------|
| G1 | 🔴 **NCR TIDAK ADA method transisi** | `NcrController` L1-65 hanya CRUD/export; `grep submit/review/close` empty | §1: `open→under_review→in_progress→closed` via WorkflowService |
| G2 | 🔴 **NCR `update()` bypass close tanpa RCA** | L48-56: `if status==='closed' set closed_at` lalu `$ncr->update($validated)` — TIDAK cek `root_cause`/`corrective_action`/`preventive_action` | §2: `close` wajib RCA + CA + PA terisi |
| G3 | 🔴 **NCR scope `?scope=all` manipulatif** | `index` L18-19 / `export` L59-60 pakai `$request->input('scope','all')` TANPA cek `core.scope.all` | User tanpa `core.scope.all` lihat lintas site |
| G4 | 🔴 **NCR tidak pakai WorkflowService** | ctor L13 tidak inject; tidak `start`/`transition` | §1/§4: semua transisi via `WorkflowService` + `workflow_histories` |
| G5 | 🟡 **NCR tidak inject NotificationService** | ctor L13 tidak inject | §4: notif `quality.ncr.submitted`/`closed` |
| G6 | 🟡 **NCR `show()` tidak `availableTransitions`** | L41-44 tidak kirim transitions/abilities | UI tidak render tombol aksi |
| G7 | 🟡 **NCR `update()` tidak cek status editable** | L48-56 langsung update; `closed` bisa di-reopen via PUT | §5: closed terminal |

---

## 1. Inventori Target

| Komponen | Path |
|----------|------|
| Controllers | `NcrController` (🔴), `CustomerComplaintController` (✅) |
| Models | `Ncr`, `CustomerComplaint` |
| Access | `app/Modules/Quality/ComplaintAccess.php` (✅ pattern BENAR) — NCR butuh `NcrAccess` serupa |
| Policies | `NcrPolicy`?, `CustomerComplaintPolicy`? |
| Requests | `StoreNcrRequest`, `UpdateNcrRequest`, `CloseCustomerComplaintRequest`, dll |
| Routes | `routes/modules/quality.php` (require di `routes/modules.php` L223) |
| Tests | `tests/Feature/Modules/Quality/CustomerComplaintTest.php` (✅); **NCR TIDAK ada test** |
| Docs | `docs-qhsse/modules/12-quality-management/{MODULE_SPEC,WORKFLOW,DATA_MODEL,TEST_CASES,API_CONTRACT,UI_PAGES}.md` (✅ lengkap) |

---

## 2. Workstream

### WS-1: Implementasi transisi NCR via WorkflowService (G1/G4)  🔴🔴 CRITICAL
- **Bug:** NCR tidak bisa diproses; tidak pakai WorkflowService (WORKFLOW.md §1 butuh seeder).
- **Fix:**
  1. Buat `QualityWorkflowSeeder` (atau tambah ke `WorkflowSeeder`) untuk `QUALITY_NCR_WORKFLOW`
     (open→under_review→in_progress→closed, +reject/reopen) sesuai WORKFLOW.md §1.
  2. Inject `WorkflowService` ke `NcrController`; tambah method `submit`/`review`/`close`/`reject`/`reopen`
     pakai `$this->workflowService->transition('quality', $ncr->id, $action, $actor)` (sesuai
     WORKFLOW.md §6). `store` panggil `workflowService->start('quality', $ncr->id, $actor)`.
  3. `close` validasi RCA+CA+PA terisi (G2) sebelum transition.
  4. Tambah route di `routes/modules/quality.php`.
- **Verifikasi:** `open`→submit→under_review→review→in_progress→close (dgn RCA); reject→reopen.
- **DoD:** lifecycle jalan via WorkflowService; test cover.

### WS-2: `update()` NCR cek status + hapus bypass close (G2/G7)  🔴
- **Bug:** `update()` bisa set `closed` tanpa RCA & reopen closed.
- **Fix:** `abort_if($ncr->status==='closed', 422)` (defense-in-depth); HAPUS set `status='closed'`
  dari `update()` — gunakan method `close` (WS-1) yang validasi RCA.
- **DoD:** update blocked saat closed; close wajib RCA; test cover.

### WS-3: Scope NCR `core.scope.*` (G3)  🔴
- **Fix:** ganti `?scope=all` ke `visibleQuery` berbasis `core.scope.*` (seperti ComplaintAccess).
  Buat `NcrAccess` (mirror `ComplaintAccess`) untuk konsistensi.
- **DoD:** scope aman; test cover.

### WS-4: Inject + notif NCR (G5)  🟡
- **Fix:** inject `NotificationService`; notif `quality.ncr.submitted` (QHSSE team), `quality.ncr.closed` (reporter).
- **DoD:** notif jalan; test cover.

### WS-5: `show()` NCR `availableTransitions` (G6)  🟡
- **Fix:** kirim `availableTransitions` (dari `workflowService->getAvailableTransitions`) + `abilities`.
- **DoD:** UI render tombol aksi; test props.

### WS-6: Tests NCR (G1-G7)  🔴
- **Bug:** NCR tidak ada test (Complaint ada).
- **Fix:** `tests/Feature/Modules/Quality/NcrTest.php`:
  - CRUD + permission + audit
  - lifecycle transisi via WorkflowService (WS-1)
  - close wajib RCA (WS-2)
  - scope enforcement (WS-3)
  - closed terminal (no edit/reopen)
- **DoD:** minimal 12 test; suite PASS.

### WS-7: Frontend  🟡
- **Sudah:** TS `sla_days` fix (build green); Complaint UI benar.
- **Debug:** NCR Show render `availableTransitions` + action buttons; Index scope filter.
- **DoD:** `npm run build` green; UI aksi benar.

---

## 3. Cross-Link ke Plan Lain

- **DEBUG-MODULE9-ENVIRONMENT-PLAN.md G1:** NCR SAMA — tidak ada transisi. Tapi di Quality,
  **CustomerComplaint SUDAH BENAR** (`ComplaintAccess` + `close` proper), jadi NCR tinggal ikuti
  pola yang SUDAH ADA di modul yang sama (sama seperti Modul 10 SecurityIncident vs VisitorLog/Patrol).
- **DEBUG-MODULE10-SECURITY-PLAN.md:** pola identik — 1 resource robek dalam modul yg 2/3 benar.
- **DEBUG-MODULE7-TRAINING-PLAN.md G1 + DEBUG-MODULE8-PERMIT-PLAN.md G6:** hardcode role / scope
  manual — NCR G3 sama; Complaint sudah `core.scope.*`.
- **Decision Log:** "Dalam satu modul, gunakan pattern yang SAMA (ComplaintAccess sudah ada di
  Quality — NCR harus ikut: buat `NcrAccess` + pakai WorkflowService); BANNED hardcode role /
  `?scope=` manipulatif; semua transisi via `WorkflowService`."

---

## 4. Urutan Eksekusi

1. **WS-1** (NCR transisi via WorkflowService — CRITICAL) — ikuti pola Complaint + WORKFLOW.md §6.
2. **WS-2** (update guard + RCA) — integrity.
3. **WS-3** (scope) — authz.
4. **WS-6** (tests) — coverage.
5. **WS-4/5/7** — notif, show props, frontend.

---

## 5. Commands Verifikasi

```bash
# Cek method transisi NCR ada?
grep -rn "function submit\|function review\|function close\|function reject\|function reopen" app/Http/Controllers/Modules/Quality/NcrController.php
# (empty = CRITICAL GAP)

# Cek WorkflowService di NcrController?
grep -n "WorkflowService" app/Http/Controllers/Modules/Quality/NcrController.php
# (empty = TIDAK pakai workflow)

# Repro G2: update set closed tanpa RCA (saat ini lolos = bug)
php artisan tinker --execute="
\$u=App\Models\User::factory()->create(); \$u->assignRole('Admin');
\$n=App\Models\Modules\Quality\Ncr::factory()->create(['status'=>'open','root_cause'=>null,'corrective_action'=>null,'preventive_action'=>null,'created_by'=>\$u->id]);
\$c=app(App\Http\Controllers\Modules\Quality\NcrController::class);
\$req=request(); \$req->merge(['status'=>'closed']);
\$c->update(\$req, \$n);
echo \$n->fresh()->status.' closed_at='.\$n->fresh()->closed_at; // 'closed' TANPA RCA (BUG)
"

# Repro G3: scope manipulasi
curl -s 'http://localhost/quality/ncrs?scope=all'  # tanpa core.scope.all lihat semua?

# Frontend
npm run build
```

---

## 6. Definition of Done (Modul 12 Total)

- [ ] WS-1: `QualityWorkflowSeeder` + NCR `submit`/`review`/`close`/`reject`/`reopen` via WorkflowService; full lifecycle; test cover.
- [ ] WS-2: `update()` blocked saat closed; close wajib RCA; test cover.
- [ ] WS-3: NCR scope `core.scope.*` (`NcrAccess`); test cover.
- [ ] WS-6: `NcrTest` minimal 12; suite PASS.
- [ ] WS-4: NCR notif via `NotificationService`; test cover.
- [ ] WS-5: `show()` `availableTransitions` + abilities; test props.
- [ ] WS-7: `npm run build` green; UI aksi benar.
- [ ] Cross-link Modul 9/10, Core/Master WS-6, Modul 7/8 tertutup; Decision Log + Handoff.

---

## 7. Catatan Jujur

- Modul 12 Quality adalah **kasus ekstrim**: CustomerComplaint production-ready (scope benar, close
  proper, lock, test), tapi **NCR robek total** — tidak ada transisi, tidak pakai WorkflowService,
  `update()` bisa bypass close tanpa RCA. Ini **Modul 9-level gap** pada 1 dari 2 resource.
- G1/G2 = NCR tidak bisa diproses dengan benar + close tanpa RCA = kehilangan tujuan QMS (root cause
  analysis wajib untuk NCR).
- **KEUNTUNGAN**: pattern BENAR SUDAH ADA di modul yang sama (`ComplaintAccess`, `close` proper).
  NCR tinggal ikuti + pakai `WorkflowService` sesuai WORKFLOW.md §1/§6 (yang sudah lengkap & akurat).
- Docs Quality LENGKAP (MODULE_SPEC/WORKFLOW/DATA_MODEL/TEST_CASES/API_CONTRACT/UI_PAGES) — berbeda
  dgn Modul 11 (Communication) yang tidak punya docs. Jadi plan ini punya spec referensi yang kuat.
- **KOREKSI**: ini Modul 12 yang benar (Quality), bukan Communication. Communication = Modul 11
  (sudah ditulis `DEBUG-MODULE11-COMMUNICATION-PLAN.md`, meski folder docs-nya `18-communication-campaign`).
- Plan ini BUKAN eksekusi; tidak ada kode diubah sampai per WS dilakukan dengan root-cause evidence.
