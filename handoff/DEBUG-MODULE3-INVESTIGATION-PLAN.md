# DEBUG-MODULE3-INVESTIGATION-PLAN.md — Debug Mendalam Modul 3 (Investigation & RCA)

**Tanggal:** 2026-07-15
**Modul:** `03-investigation-rca` (Phase 2 — modul setelah Incident/CAPA)
**Metode:** systematic-debugging (Iron Law: TIDAK ada fix tanpa root-cause evidence)
**Status:** 🟡 **Suite PASS, tapi LOGIC GAPS NYATA ditemukan dari baca kode** (bukan 403 seperti CAPA).

---

## 0. Konteks & Bukti Segar

- Full suite terbagi: **Investigation = PASS** (semua test `InvestigationTest` hijau).
- **Kenapa tidak 403 seperti CAPA?** `InvestigationController` **TIDAK punya `Access` trait**
  (tidak ada `abort_unless(canAccess)` di `show/edit/update/start/complete/cancel`). Hanya bergantung
  pada `permission:` middleware route. Admin punya `investigation.reports.*` → lolos.
- **Tapi baca kode menemukan 4 logic gap** yang test tidak tangkap (test memakai data minimal &
  tidak mengecek spec secara ketat):

| # | Gap | Bukti kode | Spec/Expected |
|---|-----|-----------|---------------|
| G1 | `start()` tidak validasi RCA | `start()` L210-219 → `doStart()` langsung, TANPA cek `five_whys`/`fishbone` | WORKFLOW.md §5.1/§8: `five_whys` min 1, `fishbone` min 1 cause wajib |
| G2 | `complete` reason tanpa `min:10` | `complete()` L245 `validate(['reason'=>required|string|max:1000])` | WORKFLOW.md §5.2/§8: `reason` `min:10` |
| G3 | `index()` tanpa scope filter | `index()` L43 `Investigation::query()` (no scope) | BR-07 (MODULE_SPEC incident) & pola Incident/CAPA: harus scope by site/dept |
| G4 | `getQhsseUsers()` hardcode role | L318-319 `Role::where('name','QHSSE Officer'/'QHSSE Manager')` | Fragile; jika nama seeder beda → notif silent gagal (sama antipattern CAPA/Incident) |

---

## 1. Inventori Target

| Komponen | Path |
|----------|------|
| Controller | `app/Http/Controllers/Modules/Investigation/InvestigationController.php` |
| Model | `app/Models/Modules/Investigation/Investigation.php` (+ `teamMembers()` relasi) |
| Routes | `routes/modules.php` L83-114 (prefix `investigations`, `permission:investigation.reports.*`) |
| Form Requests | `app/Http/Requests/Modules/Investigation/{Store,Update}InvestigationRequest.php` |
| Frontend | `resources/js/Pages/Modules/Investigation/{Index,Show,Form}.tsx` |
| Tests | `tests/Feature/Modules/Investigation/InvestigationTest.php` |
| Spec | `docs-qhsse/modules/03-investigation-rca/{MODULE_SPEC,WORKFLOW,DATA_MODEL,TEST_CASES,API_CONTRACT,UI_PAGES}.md` |
| Seeder | `database/seeders/InvestigationSeeder.php`, `WorkflowSeeder.php` |

---

## 2. Workstream

### WS-1: `start()` validasi RCA (G1)  🔴
- **Bug:** controller `start()` tidak validasi `five_whys` (min 1) & `fishbone` (min 1 cause) sebelum
  transition `draft→in_progress`. Investigator bisa "mulai" tanpa analisis RCA → laporan tidak valid.
- **Fix:** tambahkan validasi di `doStart()` / `start()` sesuai WORKFLOW.md §5.1:
  `if (blank($investigation->five_whys) || count(...) < 1) return back()->withErrors(['five_whys'=>...])`
  dan cek `fishbone` causes tidak kosong.
- **Verifikasi:** buat investigation draft tanpa RCA → POST `start` → expect `withErrors(['five_whys']`
  atau `['fishbone'])` + status tetap `draft`.
- **DoD:** `start` gagal kalau RCA belum lengkap; test cover.

### WS-2: `complete` reason min:10 (G2)  🟡
- **Bug:** `validate(['reason'=>required|string|max:1000])` — tidak `min:10` (spec §5.2 bilang `min:10`).
- **Fix:** `'reason' => ['required','string','min:10','max:1000']` (samakan dengan WORKFLOW.md §8).
- **Verifikasi:** POST `complete` dengan reason <10 char → `assertSessionHasErrors(['reason'])`.
- **DoD:** reason wajib min:10; test cover.

### WS-3: Scope / Visibility (G3)  🔴
- **Bug:** `index()` & `show()`/`edit()` **TIDAK scope** (Investigation::query() mentah). Berbeda dengan
  Incident (`IncidentAccess::visibleQuery`) & CAPA (`CapaAccess::scope`). Semua user dgn
  `investigation.reports.view` lihat semua investigasi lintas site.
- **Keputusan:** apakah investigasi memang cross-site (QHSSE saja yang lihat) atau harus di-scope?
  Cek MODULE_SPEC §7 (scope). Jika harus di-scope → buat `InvestigationAccess` mirip Incident
  (permission `core.scope.*`), jangan hardcode role seperti CAPA.
- **Verifikasi:** user QHSSE Officer site A → lihat investigasi site B? (harusnya tidak kalau scoped).
- **DoD:** konsisten dgn modul lain; tidak ada kebocoran lintas scope (atau dicatat di Decision Log
  bahwa investigasi memang global untuk QHSSE).

### WS-4: Notifikasi role name (G4)  🟡
- **Bug:** `getQhsseUsers()` hardcode `'QHSSE Officer'`/`'QHSSE Manager'`. Sama antipattern CAPA/Incident.
- **Fix:** gunakan `CorePermissions::roleMap()` atau konstanta role, bukan string literal.
  Cross-check `RolesAndPermissionsSeeder` nama persis.
- **Verifikasi:** ubah nama role di seeder → notif tetap jalan (atau gunakan constant).
- **DoD:** notif `investigation.started/completed/cancelled` ke recipient benar; tidak fragile ke nama role.

### WS-5: Workflow Seeding  🟢
- WORKFLOW.md L5 bilang investigation workflow "not yet seeded in WorkflowSeeder" — tapi test seed
  `InvestigationSeeder`. Pastikan `InvestigationSeeder` seed definition + transition
  `start/complete/cancel` dgn `required_permission` benar (`submit`/`close`/`update`).
- **Verifikasi:** `WorkflowDefinition::where('module_name','investigation')->exists()`;
  `WorkflowTransition::where(...)->count() == 4`.
- **DoD:** workflow investigation idempoten (`updateOrCreate`); transition sesuai WORKFLOW.md §3.

### WS-6: CRUD & Numbering  🟡
- `store` pakai `TEMP-` lalu `NumberingService::generate('investigation')` → `INV-YYYY-NNNN`. Test lolos.
- **Debug:** `StoreInvestigationRequest` validasi `incident_id` exists, `title` required, `team_members`
  structure. `update` (L192) `->update($validated)` mentah — kalau `validated` mengandung `action`/
  `_token` bisa error? Cek UpdateInvestigationRequest hanya izinkan field aman.
- **DoD:** create/update patuh BR; nomor unik; team_members sync benar.

### WS-7: Evidence File  🟡
- `show` baca `managed_files` `module_name='investigation'`. Upload/delete via `core.files.*`.
- **Debug:** pastikan `module_name='investigation'` konsisten; delete dicekal saat `completed`/`cancelled`
  (terminal, read-only per WORKFLOW.md §6).
- **DoD:** evidence private + scope + audit; lock saat terminal.

### WS-8: Notifications Events  🟡
- `investigation.started` (notifyMany QHSSE), `investigation.completed` (ke investigator),
  `investigation.cancelled`. Test hanya cek `started`. Perlu tes `completed`/`cancelled`.
- **DoD:** ke-3 event terkirim ke recipient benar.

### WS-9: Audit & Activity  🟢
- `auditService->created/updated`, `activityService->log('investigation.*')`. Test creation/activity LULUS.
- **DoD:** audit lengkap per WORKFLOW.md §7; old/new values saat update.

### WS-10: Frontend  🟡
- **Sudah:** TS `sla_days` fix (build green).
- **Debug:** prop `investigation` di Show match controller; tombol transition (`availableTransitions`)
  render; RCA tools (5-why, fishbone) UI; error `workflow`/`five_whys`/`fishbone`/`reason` handling.
- **DoD:** `npm run build` green; tombol + error benar.

### WS-11: Tests & Regresi  🔴
- Tambah test: (a) `start` gagal tanpa five_whys/fishbone (WS-1); (b) `complete` reason <10 → error (WS-2);
  (c) scope positif (WS-3); (d) notif `completed`/`cancelled`.
- **DoD:** suite tetap 100% PASS + cover gap G1-G4.

---

## 3. Cross-Link ke Plan Lain

- **DEBUG-CORE-MASTER-PLAN.md WS-6:** notif role name (G4) sama antipattern → perbaiki di satu tempat
  (mis. `NotificationService` resolve recipient by permission/constant, bukan hardcode).
- **DEBUG-MODULE1-INCIDENT-PLAN.md WS-6:** `IncidentLifecycle::sendNotification` juga hardcode role →
  konsolidasikan ke satu helper.
- **DEBUG-MODULE2-CAPA-PLAN.md WS-1:** jangan ulangi antipattern `CapaAccess` (hardcode role + wajib
  employee) di Investigation — gunakan permission `core.scope.*` jika di-scope (WS-3).
- **Decision Log:** "Recipient resolution notif pakai constant/permission, bukan hardcode role name".

---

## 4. Urutan Eksekusi

1. **WS-1** (start RCA validation) — integritas RCA, kritis.
2. **WS-3** (scope) — keamanan data; butuh keputusan desain.
3. **WS-2** (reason min:10) — konsistensi validasi.
4. **WS-4** (notif role) — konsolidasi dgn Core.
5. **WS-5/6/7/8/9/10/11** — seeding, CRUD, evidence, notif event, audit, frontend, tests.

---

## 5. Commands Verifikasi

```bash
# Suite Investigation (saat ini PASS)
php artisan test tests/Feature/Modules/Investigation

# Repro G1: start tanpa RCA -> harus gagal (saat ini lolos = bug)
php artisan tinker --execute="
\$u=App\Models\User::factory()->create(); \$u->assignRole('Admin');
\$inv=App\Models\Modules\Investigation\Investigation::factory()->create(['status'=>'draft']);
app(App\Core\Workflow\WorkflowService::class)->start('investigation',\$inv->id,\$u);
\$resp=app()->make(App\Http\Controllers\Modules\Investigation\InvestigationController::class)->start(\$inv, request()->merge([]));
"

# Repro G3: index tanpa scope
grep -n "Investigation::query()" app/Http/Controllers/Modules/Investigation/InvestigationController.php

# Frontend
npm run build
```

---

## 6. Definition of Done (Modul 3 Total)

- [ ] WS-1: `start` wajib validasi five_whys + fishbone; test cover.
- [ ] WS-2: `complete` reason `min:10`; test cover.
- [ ] WS-3: scope investigation konsisten (atau dicatat global di Decision Log); no leak.
- [ ] WS-4: notif resolve recipient tanpa hardcode role name.
- [ ] WS-5: workflow investigation seeded idempoten; 4 transition benar.
- [ ] WS-6: CRUD + numbering patuh BR; update aman.
- [ ] WS-7: evidence private + scope + lock saat terminal.
- [ ] WS-8: notif started/completed/cancelled ke recipient benar.
- [ ] WS-9: audit/activity lengkap.
- [ ] WS-10: `npm run build` green; tombol + error benar.
- [ ] WS-11: test regresi G1-G4; suite 100% PASS.
- [ ] Cross-link Core/Master WS-6 & Incident WS-6 tertutup; Decision Log + Handoff diperbarui.

---

## 7. Catatan Jujur

- Investigation **tidak** punya bug 403 seperti CAPA (tidak pakai Access trait). Tapi punya **logic gap**
  yang lebih halus (G1-G4) yang luput dari test longgar.
- G1 (start tanpa RCA) adalah **integritas data serius**: investigasi "jalan" tanpa analisis root cause.
- Jangan asal tambah `Access` trait ala CAPA (hardcode role) — jika di-scope, pakai `core.scope.*`.
- Plan ini BUKAN eksekusi; tidak ada kode diubah sampai per WS dilakukan dengan root-cause evidence.
