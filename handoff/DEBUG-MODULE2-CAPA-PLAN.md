# DEBUG-MODULE2-CAPA-PLAN.md — Debug Mendalam Modul 2 (CAPA / Action Tracking)

**Tanggal:** 2026-07-15
**Modul:** `04-capa-action-tracking` (Phase setelah Incident)
**Metode:** systematic-debugging (Iron Law: TIDAK ada fix tanpa root-cause evidence)
**Status:** 🔴 **BUG PRODUKSI NYATA DITEMUKAN** — CAPA 403 (bukan artifact sqlite).

---

## 0. Konteks & Bukti Segar (ROOT CAUSE SUDAH TERIDENTIFIKASI)

- Full suite terbagi: **Capa = FAIL (6 failed)** di `CapaActionTest`. Ke-6 = POST ke endpoint workflow:
  `start` (L83), `submit_verification` (L93), `verify_close` (L103), `reject` (L114),
  `cannot start non-open` (L217), `verify_close without reason` (L226).
- **ROOT CAUSE (terbukti dari baca kode):** `CapaAccess::canAccess()` di `app/Modules/Capa/CapaAccess.php`
  - Line 58-62: `$employee = $user->employee()->where('is_active', true)->first()` → **jika null, return false (403)**.
    Admin test (`User::factory()` + `assignRole('Admin')`) **TIDAK punya employee** → langsung 403.
  - Line 78 & 99: hardcode role **`'System Admin'`** untuk bypass scope (all sites/departments).
    Seeder pakai `'Super Admin'` / `'Admin'` → nama tidak match → bypass gagal.
  - Akibat: semua controller method (`start`, `submitVerification`, `verifyClose`, `reject`, `restart`,
    `show`, `edit`, `update`) panggil `abort_unless($this->capaAccess->canAccess(...), 403)` → 403.
- **Kontras dengan Incident (PASS):** `IncidentAccess::visibleQuery` pakai permission `core.scope.all`,
  BUKAN hardcode role + wajib employee. Makanya admin Incident lolos, CAPA 403.
- **Dampak produksi:** Admin/Super Admin tanpa employee record **TIDAK BISA memproses CAPA sama sekali**
  (tidak bisa start/verify/close/reject). Ini menghalangi operasional QHSSE.

---

## 1. Inventori Target

| Komponen | Path |
|----------|------|
| Controller | `app/Http/Controllers/Modules/Capa/CapaActionController.php` |
| Access/Scope trait | `app/Modules/Capa/CapaAccess.php` ⚠️ ROOT CAUSE |
| Model | `app/Models/Modules/Capa/CapaAction.php` (+ `is_overdue` accessor) |
| Routes | `routes/modules.php` L117-134 (prefix `capa-actions`, `permission:capa.actions.*`) |
| Form Requests | `app/Http/Requests/Modules/Capa/{Store,Update}CapaActionRequest.php` |
| Frontend | `resources/js/Pages/Modules/Capa/{Index,Show,Form}.tsx` |
| Tests | `tests/Feature/Modules/Capa/CapaActionTest.php` |
| Spec | `docs-qhsse/modules/04-capa-action-tracking/{MODULE_SPEC,WORKFLOW,TEST_CASES,...}.md` |
| Seeder | `database/seeders/CapaSeeder.php`, `RolesAndPermissionsSeeder.php` |

---

## 2. Workstream

### WS-1: CapaAccess / Scope — ROOT CAUSE 403  🔴🔴 PALING KRITIS
- **Bug:** (a) wajib employee aktif → Admin tanpa employee = 403; (b) hardcode `'System Admin'`
  tidak match seeder (`Super Admin`/`Admin`).
- **Fix proposal (root-cause, bukan symptom):**
  - Gunakan permission `core.scope.all` seperti Incident, BUKAN hardcode nama role.
  - Atau: izinkan `Super Admin`/`Admin`/`QHSSE Manager` bypass tanpa wajib employee.
  - Jika user TIDAK punya employee, fallback ke `core.scope.all` ATAU `core.scope.company`
    (sesuai role), jangan langsung deny.
  - Selaraskan nama role dengan `CorePermissions::roleMap()` (sumber tunggal kebenaran).
- **Verifikasi:** standalone script: `User::find(1)->can('core.scope.all')`; buat Admin TANPA employee →
  POST `capa.actions.start` → expect 200/redirect (bukan 403).
- **DoD:** Admin/Super Admin/QHSSE Manager bisa akses & transisi CAPA tanpa employee record;
  scope tetap aman untuk role terbatas (Supervisor/Dept Head/Employee).

### WS-2: Workflow Transitions (start/submit_verification/verify_close/reject/restart)
- Endpoint: `capa.actions.{start,submit_verification,verify_close,reject,restart}` → middleware
  `permission:capa.actions.{update,submit,close,reject}`.
- **Debug:** setelah WS-1 beres, pastikan transition jalan: `open→in_progress→waiting_verification→closed`,
  plus `reject`, `restart`. `Restart` (L262) tidak ada di test → perlu tes.
- **Verifikasi:** `php artisan test tests/Feature/Modules/Capa` → 6 failure jadi PASS.
- **DoD:** semua transition sesuai `WORKFLOW.md`; reason wajib di verify_close/reject.

### WS-3: CRUD & Numbering
- `store` pakai `TEMP-` lalu `NumberingService::generate('capa')` → `ACT-YYYY-NNNN`. Tes lolos.
- **Debug:** `StoreCapaActionRequest` validasi `assigned_to` exists? `due_date`? `priority_id` required?
  Cek `update` hanya boleh di status tertentu (spec: tidak bisa edit saat `closed`/`rejected`?).
- **DoD:** create/update patuh BR; nomor unik; audit `capa.created/updated`.

### WS-4: Evidence File (CAPA)
- `show` baca `managed_files` `module_name='capa'`. Upload/delete via `core.files.*` (sama engine Incident WS-4).
- **Debug:** pastikan CAPA evidence pakai `module_name='capa'` konsisten; delete dicekal saat closed.
- **DoD:** upload/download/delete patuh aturan private storage + scope.

### WS-5: Notifications
- `notify` ke PIC (`capa.assigned`), ke assignedTo saat `verify_close`/`reject` (`capa.closed`/`capa.rejected`).
- **Debug:** cek `NotificationTemplateSeeder` punya template `capa.assigned/closed/rejected`;
  recipient resolution (PIC = `assigned_to`, bukan hardcode role).
- **DoD:** ke-3 event notif terkirim; template resolve.

### WS-6: Audit & Activity
- `auditService->created/updated`, `activityService->log('capa.*')`. Tes creation/activity LULUS.
- **DoD:** audit lengkap per modul; old/new values saat update.

### WS-7: Frontend (Index/Show/Form)
- **Sudah diperbaiki:** TS `priorities.sla_days` (build green).
- **Debug:** prop `action` di Show match controller; tombol transition (`availableTransitions`)
  render sesuai status; `is_overdue` highlight; error `workflow` handling.
- **DoD:** `npm run build` green; tombol benar; overdue menonjol.

### WS-8: Tests & Suite
- 6 failure harus jadi PASS setelah WS-1.
- **Tambah test:** (a) Admin TANPA employee bisa akses CAPA (regresi root cause); (b) `restart`
  transition; (c) scope positif (Supervisor hanya lihat dept-nya); (d) Employee tanpa employee record
  (edge) tidak crash.
- **DoD:** `php artisan test tests/Feature/Modules/Capa` 100% PASS (vs sqlite & PG).

---

## 3. Cross-Link ke Plan Lain

- **DEBUG-CORE-MASTER-PLAN.md WS-1/WS-2:** CAPA 403 juga relevan di sana (permission `capa.actions.*`
  vs `incident.reports.*`; `WorkflowService` netral). Eksekusi WS-1 di sini SEKALIGUS menutup WS-1/WS-2 Core.
- **DEBUG-MODULE1-INCIDENT-PLAN.md §3:** pola CAPA vs Incident — jadikan `CapaAccess` mengikuti
  pola `IncidentAccess` (permission-based, bukan hardcode role + wajib employee) agar konsisten.
- **Decision Log:** tambahkan entry "CAPA access model diselaraskan dengan Incident (permission-based)".

---

## 4. Urutan Eksekusi

1. **WS-1** (CapaAccess root cause) — BLOKER operasional; selesaikan dulu.
2. **WS-2** (workflow) — verifikasi 6 failure jadi PASS.
3. **WS-8** (tests) — tambah regresi Admin-tanpa-employee + restart + scope.
4. **WS-3/4/5/6/7** — CRUD, evidence, notif, audit, frontend (sebagian besar sudah hijau).

---

## 5. Commands Verifikasi

```bash
# Jalankan suite CAPA (6 fail saat ini)
php artisan test tests/Feature/Modules/Capa

# Repro root cause: Admin tanpa employee -> 403?
php artisan tinker --execute="
\$u = App\Models\User::factory()->create(); \$u->assignRole('Admin');
\$a = App\Models\Modules\Capa\CapaAction::factory()->create(['status'=>'open']);
\$acc = new App\Modules\Capa\CapaAccess();
var_dump(\$acc->canAccess(\$a, \$u));
"

# Bandingkan role name seeder vs hardcode
grep -n "System Admin\|Super Admin" database/seeders/RolesAndPermissionsSeeder.php app/Modules/Capa/CapaAccess.php

# Frontend
npm run build
```

---

## 6. Definition of Done (Modul 2 Total)

- [x] WS-1: `CapaAccess` tidak hardcode `'System Admin'`; pakai `core.scope.*` seperti Incident;
  Admin/Super Admin/QHSSE Manager bisa akses CAPA tanpa employee record; scope aman untuk role terbatas. (✅ 2026-07-15)
- [ ] WS-2: 6 CAPA failure → PASS; semua transition (`start/submit_verification/verify_close/reject/restart`) jalan.
- [ ] WS-3: CRUD + numbering patuh BR; update dibatasi status.
- [ ] WS-4: evidence CAPA private + scope + audit.
- [ ] WS-5: notif `capa.assigned/closed/rejected` ke recipient benar.
- [ ] WS-6: audit/activity lengkap.
- [ ] WS-7: `npm run build` green; tombol transition + overdue benar.
- [ ] WS-8: test regresi Admin-tanpa-employee + restart + scope positif; suite 100% PASS.
- [ ] Cross-link: Core/Master WS-1/WS-2 & Incident §3 tertutup; Decision Log + Handoff diperbarui.

---

## 7. Catatan Jujur

- CAPA 403 adalah **bug nyata**, bukan artifact sqlite (berbeda dengan Core 6 failure).
- Perbaikan HARUS mengikuti pola `IncidentAccess` (permission-based) agar tidak mengulang antipattern.
- Jangan "fix" dengan memberi employee ke admin test — itu menyembunyikan bug, bukan memperbaiki.
  Fix di `CapaAccess` itu sendiri.
- Plan ini BUKAN eksekusi; tidak ada kode diubah sampai WS-1 dilakukan dengan root-cause evidence.
