# DEBUG-MODULE8-PERMIT-PLAN.md — Debug Mendalam Modul 8 (Permit to Work)

**Tanggal:** 2026-07-15
**Modul:** `09-permit-to-work` (Phase 9 — pakai WorkflowService, tapi transition generik LEMAH)
**Metode:** systematic-debugging (Iron Law: TIDAK ada fix tanpa root-cause evidence)
**Status:** 🔴 **TIDAK ADA feature test + transition generik LEMAH (no reason/COI/checklist/notif) + scope manipulatif**.

---

## 0. Konteks & Bukti Segar

- Modul 8 SUDAH pakai `WorkflowService` + `PermitWorkflowSeeder` (ada, `PERMIT_WORKFLOW` ter-seed).
  Lebih baik dari Modul 7 (yang hardcode).
- Tapi `transition()` (L362-424) adalah method **GENERIK** yang menangani semua action (submit/
  review/approve/activate/close/reject) dengan satu validation `reason => nullable`. **TIDAK menerapkan
  business rule spesifik** dari WORKFLOW.md §4.
- **TIDAK ADA feature test** (`tests/Feature/Modules/Permit/` kosong). Hanya regression test di
  `TrainingAssetReportingRegressionTest` (test reporting).

| # | Gap | Bukti kode | WORKFLOW.md mensyaratkan |
|---|-----|-----------|--------------------------|
| G1 | `transition()` tak cek `reason` required | L362-367 `reason => 'nullable|string'` | §3: `close`/`reject` wajib reason min:10 |
| G2 | `transition()` tak cek conflict of interest | L362-424 TIDAK cek `$actor->id !== $permit->created_by` | §4.3: `approve` dilarang self-approve |
| G3 | `transition()` tak cek checklist all-signed | L362-424 TIDAK cek `PermitChecklist` unsigned | §4.4: `activate` wajib semua checklist signed |
| G4 | `transition()` tak kirim notif | ctor L29-34 TIDAK inject `NotificationService`; `transition()` tidak `notify` | §4: notif `permit.submitted`/`approved`/`closed`/`rejected`/etc |
| G5 | `update()` tak cek status `draft` | L303-330 LANGSUNG `$permit->update($data)` | §7: update hanya `draft` |
| G6 | Scope manipulatif via `?scope=all` | `index` L44-54 pakai `$request->input('scope','all')` TANPA cek `core.scope.all` | User tanpa `core.scope.all` bisa `?scope=all` lihat lintas site |
| G7 | Tidak ada feature test | `tests/Feature/Modules/Permit/` kosong | Tidak ada bukti authz/logic jalan |

---

## 1. Inventori Target

| Komponen | Path |
|----------|------|
| Controller | `PermitController` (`app/Http/Controllers/Modules/Permit/`) |
| Models | `Permit`, `PermitChecklist` (`app/Models/Modules/Permit/`) |
| Policy | — (pakai `authorizeResource(Permit::class, 'permit')` L35) |
| Requests | `StorePermitRequest`, `UpdatePermitRequest`, `SignChecklistRequest` |
| Routes | `routes/modules/permit.php` (require di `routes/modules.php` L214) |
| Seeder | `database/seeders/PermitWorkflowSeeder.php` (ada, `PERMIT_WORKFLOW`) |
| WorkflowService | `app/Core/Workflow/WorkflowService.php` |
| Frontend | `resources/js/Pages/Modules/Permit/{Index,Show,Form}.tsx` |
| Tests | `tests/Feature/Modules/Permit/` → **KOSONG** |
| Spec | `docs-qhsse/modules/09-permit-to-work/{MODULE_SPEC,WORKFLOW,DATA_MODEL,TEST_CASES,API_CONTRACT,UI_PAGES}.md` |

---

## 2. Workstream

### WS-1: `transition()` terapkan business rule (G1/G2/G3)  🔴
- **Bug:** method generik tidak cek reason/COI/checklist.
- **Fix:** di `transition()` (L362) sebelum `WorkflowService::transition`:
  - `close`/`reject`: validasi `reason` required|min:10.
  - `approve`: `abort_if($permit->created_by === $user->id, 422, 'Tidak dapat approve permit sendiri')`.
  - `activate`: `abort_if(PermitChecklist::where('permit_id',$permit->id)->where('is_checked',false)->exists(), 422, "{$n} item belum di-sign")`.
- **Verifikasi:** `close` tanpa reason → 422; `approve` self → 422; `activate` dgn checklist unsigned → 422.
- **DoD:** semua rule §4 diterapkan; test cover.

### WS-2: Inject + kirim notif di `transition()` (G4)  🔴
- **Bug:** ctor tidak inject `NotificationService`; `transition()` silent (sama Modul 4 G2).
- **Fix:** inject `NotificationService`; di `transition()` setelah sukses, `notifyMany` recipient
  sesuai §4 (submit→QHSSE+Manager+Supervisor; review→requester; approve→requester; close→requester+
  supervisor+contractor; reject→requester). Resolve recipient via `core.scope.*` (BUKAN hardcode role).
- **Verifikasi:** `core_notifications` terisi per transition.
- **DoD:** notif jalan; test cover.

### WS-3: `update()` cek status `draft` (G5)  🔴
- **Bug:** permit `approved`/`active` bisa diedit.
- **Fix:** `abort_unless($permit->status === 'draft', 422, '...')` di `update()` (L303).
- **Verifikasi:** `approved` permit → PUT `update` → 422.
- **DoD:** update hanya draft; test cover.

### WS-4: Scope pakai `core.scope.*` bukan param manipulatif (G6)  🔴
- **Bug:** `index`/`export` (L44-54/L436-442) pakai `?scope=all` TANPA cek permission. User tanpa
  `core.scope.all` bisa lihat lintas site dengan manipulasi URL.
- **Fix:** ganti ke `visibleQuery` berbasis `core.scope.*` (seperti Modul 5/6). Hapus `scope=all`
  manual; jika tidak `core.scope.all`, force scope ke site/department/own.
- **Verifikasi:** user `core.scope.site` dgn `?scope=all` → tetap hanya site sendiri.
- **DoD:** scope aman; test cover.

### WS-5: Feature tests (G7)  🔴
- **Bug:** zero coverage Permit.
- **Fix:** `tests/Feature/Modules/Permit/PermitTest.php`:
  - CRUD + permission + audit
  - transition: submit→...→closed; reject reason; approve COI; activate checklist
  - `update` draft-only
  - scope enforcement
  - notif assertions
- **DoD:** suite cover G1-G6; minimal 12 test.

### WS-6: `signChecklist` verify (verify)  🟢
- **Cek:** `signChecklist` (L332-360) sudah cek `permit_id` match + status `submitted`/`under_review`/
  `approved` (L363) + audit + activity. Sesuai §5. **Tidak ada gap** — verifikasi via test.
- **DoD:** test sign/unsign + permission.

### WS-7: Expiry command (verify/future)  🟡
- WORKFLOW.md §9: `CheckPermitExpiry` hourly → notif `permit.expiring_soon`. Cek file ada & scheduled.
- **DoD:** command ada + scheduled, atau tercatat future.

### WS-8: Frontend  🟡
- **Sudah:** TS `sla_days` fix (build green).
- **Debug:** Show render `availableActions` (gated by permission), `checklistProgress`, `workflow`;
  Form validity; error `reason`/`workflow` handling.
- **DoD:** `npm run build` green; UI sesuai spec.

---

## 3. Cross-Link ke Plan Lain

- **DEBUG-MODULE4-INSPECTION-PLAN.md G2/G3:** Modul 8 SAMA — notif silent + tidak audit transition
  (G4). Modul 8 sudah inject `AuditService` tapi `transition()` tidak log `workflow.transitioned`
  via `AuditService::workflow()` — perlu tambah).
- **DEBUG-MODULE5-AUDIT-PLAN.md G5:** Modul 8 `transition()` tidak di-scope secara ketat — tapi
  pakai `authorizeResource` (Policy). Periksa Policy apakah transition di-scope (seperti Modul 5 G5).
- **DEBUG-MODULE7-TRAINING-PLAN.md G1:** Modul 8 scope manual (`?scope=`) lebih aman dari hardcode
  role, tapi TETAP manipulatif. Gunakan `core.scope.*` konsisten.
- **DEBUG-CORE-MASTER-PLAN.md WS-6:** notif recipient resolve by permission, bukan hardcode.
- **Decision Log:** "Transition tidak boleh generik tanpa business rule; semua notif via
  `NotificationService`; scope via `core.scope.*`."

---

## 4. Urutan Eksekusi

1. **WS-1** (transition rules) — logic + compliance.
2. **WS-2** (notif) — silent gap.
3. **WS-3** (update draft-only) — integrity.
4. **WS-4** (scope) — authz.
5. **WS-5** (tests) — coverage.
6. **WS-6/7/8** — verify signChecklist, expiry command, frontend.

---

## 5. Commands Verifikasi

```bash
# Cek feature test ada?
ls tests/Feature/Modules/Permit/   # saat ini KOSONG

# Repro G1: close tanpa reason (saat ini lolos = bug)
php artisan tinker --execute="
\$u=App\Models\User::factory()->create(); \$u->assignRole('Admin');
\$p=App\Models\Modules\Permit\Permit::factory()->create(['status'=>'active','created_by'=>\$u->id]);
\$c=app(App\Http\Controllers\Modules\Permit\PermitController::class);
\$req=request(); \$req->merge(['action'=>'close','reason'=>null]);
\$c->transition(\$req, \$p);
echo \$p->fresh()->status; // saat ini 'closed' (BUG), seharusnya 422
"

# Repro G2: self-approve
php artisan tinker --execute="
\$u=App\Models\User::factory()->create(); \$u->assignRole('Admin');
\$p=App\Models\Modules\Permit\Permit::factory()->create(['status'=>'under_review','created_by'=>\$u->id]);
\$c=app(App\Http\Controllers\Modules\Permit\PermitController::class);
\$req=request(); \$req->merge(['action'=>'approve']);
\$c->transition(\$req, \$p);
echo \$p->fresh()->status.' approved_by='.\$p->fresh()->approved_by; // saat ini 'approved' self (BUG)
"

# Repro G6: scope manipulasi
curl -s 'http://localhost/permit?scope=all'  # user tanpa core.scope.all lihat semua?

# Frontend
npm run build
```

---

## 6. Definition of Done (Modul 8 Total)

- [ ] WS-1: `transition()` terapkan reason/COI/checklist; test cover.
- [ ] WS-2: notif per transition via `NotificationService`; test cover.
- [ ] WS-3: `update()` draft-only; test cover.
- [ ] WS-4: scope `core.scope.*` (bukan `?scope=` manipulatif); test cover.
- [ ] WS-5: feature tests Permit minimal 12; suite PASS.
- [ ] WS-6: `signChecklist` verify (sudah benar); test cover.
- [ ] WS-7: expiry command ada + scheduled (atau tercatat).
- [ ] WS-8: `npm run build` green; UI `availableActions`/`checklistProgress` benar.
- [ ] Cross-link Core/Master WS-6, Modul 4/5/7 tertutup; Decision Log + Handoff.

---

## 7. Catatan Jujur

- Modul 8 LEBIH MATANG dari Modul 7 (pakai WorkflowService + seeder) tapi `transition()` GENERIK
  adalah anti-pattern: menangani semua action tanpa business rule spesifik.
- G1/G2/G3 = compliance HSE kritis: permit bisa close tanpa reason, self-approve, active tanpa
  checklist — semua melanggar WORKFLOW.md §4.
- G4 = notif silent (sama Modul 4 G2) — recipient tidak tahu status berubah.
- G6 = celah authz: `?scope=all` bisa dilewati tanpa `core.scope.all`.
- Modul 8 butuh pengetatan `transition()` + scope, bukan rewrite besar.
- Plan ini BUKAN eksekusi; tidak ada kode diubah sampai per WS dilakukan dengan root-cause evidence.
