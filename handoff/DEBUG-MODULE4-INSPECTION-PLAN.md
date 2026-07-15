# DEBUG-MODULE4-INSPECTION-PLAN.md — Debug Mendalam Modul 4 (Inspection Checklist)

**Tanggal:** 2026-07-15
**Modul:** `05-inspection-checklist` (Phase 4 — setelah Incident/CAPA/Investigation)
**Metode:** systematic-debugging (Iron Law: TIDAK ada fix tanpa root-cause evidence)
**Status:** 🟡 **Suite PASS, tapi LOGIC GAPS SERIUS ditemukan dari baca kode vs WORKFLOW.md**.

---

## 0. Konteks & Bukti Segar

- Full suite terbagi: **Inspection = PASS** (`InspectionTest` hijau).
- **Kenapa tidak 403 seperti CAPA?** `InspectionController` **TIDAK punya `Access` trait** (hanya
  `permission:` middleware). Admin punya `inspection.checklists.*` → lolos.
- **Tapi baca kode vs `docs-qhsse/modules/05-inspection-checklist/WORKFLOW.md` menemukan 7 gap**
  yang test longgar tidak tangkap:

| # | Gap | Bukti kode | WORKFLOW.md mensyaratkan |
|---|-----|-----------|--------------------------|
| G1 | `complete()` tak validasi required items | `complete()` L252-270 → `transition('complete')` langsung, TANPA cek unanswered required | §4.2 / §8: "Validate all required items have answers" sebelum complete |
| G2 | `complete()` tak kirim notif | Controller TIDAK inject `NotificationService` (ctor L30-35); `complete()` tak panggil notif | §4.2 / §8: `notifyMany('inspection.completed')` + `inspection.unsafe_found` (jika fail) |
| G3 | `start`/`complete` tak audit | `start()` L237 & `complete()` L252 hanya `ActivityService::log`, TIDAK `AuditService` | §5: audit_logs event `started`/`completed` wajib |
| G4 | `store()` tak buat empty results | `store()` L168-196 tak buat `InspectionResult` per item | §8 langkah 1: "Empty InspectionResult records created for each template item" |
| G5 | `is_unsafe` tak auto-calc | `update()` L214-225 simpan `$result['is_unsafe']` mentah | §8: "Auto-calculate is_unsafe based on item type + answer" |
| G6 | `index()`/`templateIndex()` tanpa scope | `index()` L145 & `templateIndex()` L39 `::query()` mentah | BR-07 (konsisten dgn Incident/CAPA): harus scope site/dept |
| G7 | Hardcode role fragilitas | WORKFLOW.md pakai `getQhsseManagersForSite()` (controller nyata tak punya, krn notif di-skip) | Sama antipattern CAPA/Incident/Investigation |

---

## 1. Inventori Target

| Komponen | Path |
|----------|------|
| Controller | `app/Http/Controllers/Modules/Inspection/InspectionController.php` |
| Models | `Inspection, InspectionItem, InspectionResult, InspectionTemplate` (`app/Models/Modules/Inspection/`) |
| Routes | `routes/modules.php` L138-162 (2 grup: `inspection-templates`, `inspections`) |
| Form Requests | `StoreInspectionTemplateRequest, StoreInspectionRequest, UpdateInspectionRequest` |
| Frontend | `resources/js/Pages/Modules/Inspection/{Index,Show,Form}.tsx` + `Templates/{Index,Show,Form}.tsx` |
| Tests | `tests/Feature/Modules/Inspection/InspectionTest.php` |
| Spec | `docs-qhsse/modules/05-inspection-checklist/{MODULE_SPEC,WORKFLOW,DATA_MODEL,TEST_CASES,API_CONTRACT,UI_PAGES}.md` |
| Seeder | `database/seeders/InspectionSeeder.php`, `WorkflowSeeder.php` |

---

## 2. Workstream

### WS-1: `complete()` validasi required items (G1)  🔴
- **Bug:** inspeksi bisa `completed` padahal item `is_required=true` belum dijawab → laporan tidak valid.
- **Fix:** di `complete()`, sebelum transition:
  `$unanswered = $inspection->results()->whereHas('item', fn($q)=>$q->where('is_required',true))->whereNull('answer')->count();`
  `if ($unanswered>0) return back()->withErrors(['complete'=>"Masih ada {$unanswered} item wajib belum dijawab"]);`
- **Verifikasi:** buat inspection in_progress DENGAN item required tapi result kosong → POST `complete`
  → `assertSessionHasErrors(['complete'])` + status tetap `in_progress`.
- **DoD:** complete gagal kalau required items kosong; test cover.

### WS-2: Notifikasi saat complete (G2)  🔴
- **Bug:** controller TIDAK inject `NotificationService` → `complete()` tak kirim `inspection.completed`
  / `inspection.unsafe_found`. Notif sunyi.
- **Fix:** inject `NotificationService`; di `complete()` panggil `notifyMany` ke QHSSE Manager/ Officer
  site terkait (resolusi recipient pakai constant/permission, BUKAN hardcode — lihat WS-7).
- **Verifikasi:** inspection fail (ada unsafe) → complete → `CoreNotification::where('type','inspection.unsafe_found')` exists; pass → `inspection.completed` exists.
- **DoD:** ke-2 notif terkirim ke recipient benar; test cover.

### WS-3: Audit pada transition (G3)  🟡
- **Bug:** `start`/`complete` hanya `ActivityService`, tidak `AuditService` (template CRUD pakai audit).
- **Fix:** tambahkan `auditService->workflow(...)` atau `->log(...)` di `start`/`complete` sesuai WORKFLOW.md §5.
- **Verifikasi:** `AuditLog::where('module_name','inspection')->where('event','workflow.transitioned'/'started'/'completed')` exists.
- **DoD:** audit lengkap per spec; test cover.

### WS-4: Pre-create InspectionResult (G4)  🟡
- **Bug:** `store()` tak buat empty `InspectionResult` per template item → inspector harus save manual.
- **Fix:** setelah create inspection, loop `$template->items` → `InspectionResult::create([inspection_id, inspection_item_id, answer:null])`.
  (Atau biarkan lazy di `update` — tapi selaraskan dgn spec §8.)
- **Verifikasi:** create inspection dari template 3 item → `InspectionResult::where('inspection_id',...)->count()==3`.
- **DoD:** results pre-created (atau dokumentasikan lazy sebagai disengaja di Decision Log).

### WS-5: `is_unsafe` auto-calculate (G5)  🔴
- **Bug:** `update()` simpan `is_unsafe` dari input mentah. Inspector bisa isi `false` padahal `answer='unsafe'`
  → `overall_result` salah (pass padahal ada unsafe).
- **Fix:** di `update()`, hitung `is_unsafe` dari `item->type` + `answer` (mis. type `safe_unsafe` &
  answer `unsafe` → true; `yes_no` & answer `no` → ? sesuaikan spec). Override input.
- **Verifikasi:** result `answer='unsafe'` type `safe_unsafe` → `is_unsafe` jadi `true` walau input `false`.
- **DoD:** `is_unsafe` konsisten dgn answer; `overall_result` benar; test cover.

### WS-6: Scope / Visibility (G6)  🔴
- **Bug:** `index()` (L145) & `templateIndex()` (L39) `::query()` mentah — tanpa scope. Sama G3 Investigation.
- **Keputusan:** inspection harus di-scope (site/dept) atau global QHSSE? Cek MODULE_SPEC §7. Jika scoped →
  buat `InspectionAccess` pakai `core.scope.*` (JANGAN hardcode role ala CAPA).
- **Verifikasi:** QHSSE Officer site A tidak lihat inspection site B (jika scoped).
- **DoD:** konsisten; no leak (atau global dicatat di Decision Log).

### WS-7: Notif recipient resolution (G7)  🟡
- **Bug:** hardcode role name fragilitas (sama CAPA/Incident/Investigation).
- **Fix:** satu helper di `NotificationService` / `CorePermissions` resolve QHSSE Manager/Officer by
  site tanpa string literal. Cross-link Core/Master WS-6 & Incident WS-6.
- **DoD:** recipient resolve tanpa hardcode; test cover.

### WS-8: Template CRUD  🟢
- `templateStore`/`templateUpdate`/`templateDestroy` (soft disable `is_active=false`). Test lolos.
- **Debug:** `templateUpdate` (L117) `items()->delete()` lalu recreate — kalau concurrent, race; cek
  `withCount('items')` di index. Logika aman untuk Phase 4.
- **DoD:** template CRUD patuh BR; audit `template.created/updated`.

### WS-9: Cross-module CAPA link  🟡
- WORKFLOW.md §8 langkah 5: inspection fail → tombol "Buat CAPA" (source_module='inspection').
- **Debug:** pastikan `InspectionController`/frontend link ke `capa.actions.create?source_module=inspection&source_reference_id=...`.
  (Ini lebih ke frontend/integration; pastikan tidak crash.)
- **DoD:** link CAPA dari inspection fail jalan.

### WS-10: Evidence File  🟡
- WORKFLOW.md §5 audit `inspection.file.uploaded/deleted`. Controller saat ini TIDAK tampilkan/manage
  evidence di `show()` (L198 cuma load template/site/area/inspector/results). Perlu cek apakah upload
  evidence didukung (via `core.files.*` dengan `module_name='inspection'`).
- **DoD:** jika didukung, show evidence + audit; jika tidak, dokumentasikan di Decision Log.

### WS-11: Frontend  🟡
- **Sudah:** TS `sla_days` fix (build green).
- **Debug:** Show page render results per item, highlight unsafe (red), tombol CAPA saat fail;
  error `complete` (required items) handling; template Form dynamic items.
- **DoD:** `npm run build` green; UI sesuai spec.

### WS-12: Tests & Regresi  🔴
- Tambah test: (a) complete gagal kalau required kosong (WS-1); (b) notif completed/unsafe_found (WS-2);
  (c) audit transition (WS-3); (d) is_unsafe auto-calc (WS-5); (e) scope positif (WS-6).
- **DoD:** suite tetap 100% PASS + cover G1-G7.

---

## 3. Cross-Link ke Plan Lain

- **DEBUG-CORE-MASTER-PLAN.md WS-6:** notif hardcode role (G2/G7) → satu helper resolve recipient.
- **DEBUG-MODULE1-INCIDENT-PLAN.md WS-6:** `IncidentLifecycle` hardcode role → samakan perbaikan.
- **DEBUG-MODULE2-CAPA-PLAN.md WS-1:** jangan ulangi `CapaAccess` (hardcode + wajib employee) di scope (WS-6).
- **DEBUG-MODULE3-INVESTIGATION-PLAN.md G3:** scope Investigation & Inspection sama pola → selesaikan bersama.
- **Decision Log:** "Notif recipient resolve by permission/constant; inspection & investigation scope keputusan".

---

## 4. Urutan Eksekusi

1. **WS-1** (complete validasi required) — integritas.
2. **WS-5** (is_unsafe auto-calc) — integritas hasil.
3. **WS-2 + WS-7** (notif + recipient) — reliability.
4. **WS-3** (audit transition) — compliance.
5. **WS-6** (scope) — keamanan.
6. **WS-4/8/9/10/11/12** — pre-create results, template, CAPA link, evidence, frontend, tests.

---

## 5. Commands Verifikasi

```bash
# Suite Inspection (saat ini PASS)
php artisan test tests/Feature/Modules/Inspection

# Repro G1: complete tanpa jawab required -> harus gagal (saat ini lolos = bug)
php artisan tinker --execute="
\$u=App\Models\User::factory()->create(); \$u->assignRole('Admin');
\$t=App\Models\Modules\Inspection\InspectionTemplate::factory()->create();
\$item=App\Models\Modules\Inspection\InspectionItem::create(['inspection_template_id'=>\$t->id,'question'=>'Q?','type'=>'yes_no','is_required'=>true,'order'=>0]);
\$ins=App\Models\Modules\Inspection\Inspection::factory()->create(['status'=>'in_progress','inspection_template_id'=>\$t->id]);
app(App\Core\Workflow\WorkflowService::class)->start('inspection',\$ins->id,\$u);
\$c=app(App\Http\Controllers\Modules\Inspection\InspectionController::class);
\$c->complete(\$ins, request()->merge([]));
echo \$ins->fresh()->status; // saat ini 'completed' (BUG), seharusnya 'in_progress'
"

# Repro G3: scope
grep -n "Inspection::query()" app/Http/Controllers/Modules/Inspection/InspectionController.php

# Frontend
npm run build
```

---

## 6. Definition of Done (Modul 4 Total)

- [ ] WS-1: `complete` gagal kalau required items kosong; test cover.
- [ ] WS-5: `is_unsafe` auto-calc dari answer; `overall_result` benar; test cover.
- [ ] WS-2: notif `inspection.completed` + `inspection.unsafe_found` terkirim; test cover.
- [ ] WS-7: recipient resolve tanpa hardcode role.
- [ ] WS-3: audit `started`/`completed` pada transition; test cover.
- [ ] WS-6: scope inspection konsisten (atau global tercatat); no leak.
- [ ] WS-4: results pre-created (atau lazy tercatat).
- [ ] WS-8: template CRUD aman; audit.
- [ ] WS-9: CAPA link dari inspection fail jalan.
- [ ] WS-10: evidence (jika didukung) + audit.
- [ ] WS-11: `npm run build` green; UI sesuai spec.
- [ ] WS-12: test regresi G1-G7; suite 100% PASS.
- [ ] Cross-link Core/Master WS-6, Incident WS-6, Investigation G3 tertutup; Decision Log + Handoff.

---

## 7. Catatan Jujur

- Inspection **tidak** 403 seperti CAPA (no Access trait). Tapi punya **logic gap integritas** (G1, G5)
  yang lebih berbahaya: laporan inspeksi bisa "selesai" padahal tidak valid.
- G2 (notif silent) berarti manajemen QHSSE **tidak tahu** inspeksi selesai / ada item unsafe → blind spot.
- Jangan asal tambah `Access` trait ala CAPA; jika di-scope pakai `core.scope.*` (WS-6).
- Plan ini BUKAN eksekusi; tidak ada kode diubah sampai per WS dilakukan dengan root-cause evidence.
