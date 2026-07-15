# DEBUG-MODULE5-AUDIT-PLAN.md — Debug Mendalam Modul 5 (Audit Management)

**Tanggal:** 2026-07-15
**Modul:** `06-audit-management` (Phase 6 — paling matang dari Modul 1-4)
**Metode:** systematic-debugging (Iron Law: TIDAK ada fix tanpa root-cause evidence)
**Status:** 🟡 **Suite PASS, tapi GAP INTEGRITAS & AUTHZ ditemukan dari baca kode vs WORKFLOW.md**.

---

## 0. Konteks & Bukti Segar

- Full suite terbagi: **Audit = PASS** (`AuditTest` 30+ test hijau, termasuk scope QHSSE Officer/Manager/Supervisor/Employee).
- **Modul 5 PALING MATANG:** punya `visibleQuery()` scope benar (`core.scope.*`), `AuditPolicy`
  (`view`/`update`), dan **sudah inject `NotificationService`** (ctor L51). Tidak ada antipattern
  `CapaAccess` (hardcode role + wajib employee).
- **Tapi baca kode vs `docs-qhsse/modules/06-audit-management/WORKFLOW.md` menemukan 6 gap:**

| # | Gap | Bukti kode | WORKFLOW.md mensyaratkan |
|---|-----|-----------|--------------------------|
| G1 | 3 transition tak kirim notif | `startAudit` L254 / `generateReport` L275 / `closeAudit` L298 HANYA `ActivityService::log`, TIDAK `notifyMany` (padahal `$this->notifications` di-inject) | §5.1/5.2/5.3: `audit.started`/`audit.report_ready`/`audit.closed` wajib |
| G2 | `closeFinding` tak cek major→CAPA | `closeFinding` L369-381 cek `status==='closed'` & `audit_id` saja | §6: "If classification=major, capa_action_id must not be null" |
| G3 | `updateFinding` tak cek finding `open` | `updateFinding` L348-367 cek permission + audit status, TIDAK `status==='open'` | §6 CRUD: "Update Finding ... finding must be `open`" |
| G4 | 3 transition tak audit trail | `startAudit`/`generateReport`/`closeAudit` tak panggil `$this->audit->log` (spt `store`/`update`/`uploadEvidence`/`export` yang pakai) | §7: `audit_logs` event `audit.started`/`audit.report_generated`/`audit.closed` |
| G5 | Transition TAK di-scope | `startAudit` L257 `abort_unless($this->canExecute($audit),403)` — `canExecute` (L538) cek permission+status SAJA, TIDAK `ensureVisible`/`authorize` | `show` L150 `authorize('view')`, `comment`/`uploadEvidence` L386/398 `ensureVisible` → transition BYPASS scope |
| G6 | Test gap + doc mismatch | Tak ada test: closeAudit dgn open findings, generateReport summary<20, closeFinding major tanpa CAPA, updateFinding pada closed | §5.3 code sample pakai `'major'`, kode/model/test pakai `'major_nc'` |

---

## 1. Inventori Target

| Komponen | Path |
|----------|------|
| Controller | `app/Http/Controllers/Modules/Audit/AuditController.php` (592 baris) |
| Policy | `app/Policies/Modules/Audit/AuditPolicy.php` (`view`, `update` — TIDAK `execute`/`close`) |
| Model | `Audit` (punya `allFindingsClosed()` L83, `majorFindingsHaveCapa()` L88 — cek `major_nc`), `AuditFinding` |
| Requests | `GenerateAuditReportRequest` (summary `min:20` ✓), `StoreAuditRequest`, `UpdateAuditRequest`, `StoreAuditFindingRequest`, `UpdateAuditFindingRequest` |
| Routes | `routes/modules.php` L189-207 |
| Frontend | `resources/js/Pages/Modules/Audit/{Index,Show,Form}.tsx` |
| Tests | `tests/Feature/Modules/Audit/AuditTest.php` (585 baris) |
| Spec | `docs-qhsse/modules/06-audit-management/{MODULE_SPEC,WORKFLOW,DATA_MODEL,TEST_CASES,API_CONTRACT,UI_PAGES}.md` |
| Seeder | `WorkflowSeeder` (audit workflow), `NumberingFormatSeeder` |

---

## 2. Workstream

### WS-1: Notifikasi pada 3 transition (G1)  🔴
- **Bug:** `NotificationService` di-inject tapi TIDAK dipanggil di `startAudit`/`generateReport`/`closeAudit`.
  Notif sunyi → auditee/QHSSE Manager tidak tahu audit berjalan/selesai.
- **Fix:** di tiap transition, panggil `$this->notifications->notifyMany(...)` sesuai WORKFLOW.md §5:
  - `start`: recipient = Department Head + Supervisor audited dept + lead auditor; type `audit.started`
  - `generateReport`: QHSSE Manager + lead auditor + dept head; type `audit.report_ready`
  - `close`: lead auditor + dept head + QHSSE Manager + user CAPA-linked; type `audit.closed`
  - Resolve recipient via `NotificationService` (BUKAN hardcode role — lihat WS-7 Core link).
- **Verifikasi:** `CoreNotification::where('type','audit.started'/'audit.report_ready'/'audit.closed')` exists setelah tiap transition.
- **DoD:** ke-3 notif terkirim; test cover (lihat WS-6).

### WS-2: `closeFinding` wajib CAPA untuk major (G2)  🔴
- **Bug:** major finding bisa ditutup tanpa CAPA → melanggar BR + `closeAudit` guard `majorFindingsHaveCapa()`.
- **Fix:** di `closeFinding` (sebelum update), tambah:
  `if ($finding->classification === 'major_nc' && $finding->capa_action_id === null) return back()->withErrors(['finding'=> 'Temuan Major wajib terhubung ke CAPA.']);`
  (pastikan pakai `major_nc` sesuai model, bukan `major` dari doc lama — WS-7).
- **Verifikasi:** major finding tanpa CAPA → POST close → `assertSessionHasErrors(['finding'])` + status tetap `open`.
- **DoD:** major finding terkunci tanpa CAPA; test cover.

### WS-3: `updateFinding` hanya untuk finding `open` (G3)  🔴
- **Bug:** finding `closed` bisa di-update → langgar terminal rule (§6).
- **Fix:** di `updateFinding` tambah `abort_if($finding->status !== 'open', 422, 'Temuan sudah ditutup.')`
  (sama pattern dgn `closeFinding` L374).
- **Verifikasi:** closed finding → PUT update → `assertForbidden()`/`assertSessionHasErrors`.
- **DoD:** closed finding read-only; test cover.

### WS-4: Audit trail pada 3 transition (G4)  🟡
- **Bug:** `startAudit`/`generateReport`/`closeAudit` hanya `ActivityService`, tidak `AuditService`
  (berbeda dgn `store`/`update`/`uploadEvidence`/`export` yang audit).
- **Fix:** tambah `$this->audit->log('audit.started'/'audit.report_generated'/'audit.closed', $audit, oldValues, newValues, $actor, 'audit', $audit->id)` sesuai §7.
- **Verifikasi:** `AuditLog::where('module_name','audit')->where('event','audit.started')` exists.
- **DoD:** audit transition lengkap; test cover.

### WS-5: Scope pada transition (G5)  🔴
- **Bug:** `startAudit`/`generateReport`/`closeAudit` pakai `canExecute`/`canClose` (permission+status)
  tapi TIDAK `ensureVisible`/`authorize`. User lintas site dgn permission `execute`/`close` bisa
  eksekusi transition audit site lain — padahal tidak bisa lihat di index (scope).
- **Fix:** di ketiga method, tambah `$this->ensureVisible($actor, $audit)` SEBELUM transition
  (sama spt `comment` L386 / `uploadEvidence` L398). Atau tambah `execute`/`close` ke `AuditPolicy`
  dan `authorize(...)`.
- **Verifikasi:** QHSSE Officer site A → POST `startAudit`/`generateReport`/`closeAudit` audit site B
  → `assertForbidden()` (422/403).
- **DoD:** transition di-scope konsisten dgn view; no cross-site exec; test cover.

### WS-6: Tests & Regresi (G6)  🟡
- Tambah test: (a) notif 3 transition (WS-1); (b) closeFinding major tanpa CAPA gagal (WS-2);
  (c) updateFinding closed gagal (WS-3); (d) audit trail transition (WS-4);
  (e) transition cross-site diblokir (WS-5); (f) closeAudit dgn open findings gagal;
  (g) generateReport summary<20 gagal.
- **DoD:** suite tetap 100% PASS + cover G1-G6.

### WS-7: Doc/Code mismatch `major` vs `major_nc`  🟡
- WORKFLOW.md §5.3 code sample pakai `where('classification','major')`; kode/model/test pakai `major_nc`.
- **Fix:** update `WORKFLOW.md` §5.3 + §6 ke `major_nc` (atau standardisasi ke satu nilai di Decision Log).
- **DoD:** doc selaras dgn kode.

### WS-8: Frontend  🟡
- **Sudah:** TS `sla_days` fix (build green).
- **Debug:** Show page render `can` flags (start/generate_report/close/create_finding/close_finding)
  & `availableTransitions`; finding close button disabled kalau major tanpa CAPA; error handling
  `workflow`/`finding`/`close` di blade/Inertia.
- **DoD:** `npm run build` green; UI sesuai spec.

### WS-9: Numbering / Evidence / Comment (verify only)  🟢
- `store` numbering `AUD-YYYY-NNNNN` ✓ (test L102). `uploadEvidence`/`downloadEvidence` private file ✓.
  `comment` via `CommentService` ✓. `export` CSV ✓. `closeAudit` guard `allFindingsClosed` +
  `majorFindingsHaveCapa` ✓ (L302-303). **Tidak ada gap di WS-9** — verifikasi saja.

---

## 3. Cross-Link ke Plan Lain

- **DEBUG-MODULE4-INSPECTION-PLAN.md G2/G3:** notif silent & audit transition SAMA persis →
  Modul 5 sudah inject `NotificationService` & `AuditService`, jadi fix LEBIH MUDAH (tinggal panggil).
  Gunakan satu pola `NotificationService` helper (Core/Master WS-6) agar konsisten.
- **DEBUG-MODULE3-INVESTIGATION-PLAN.md G3 + DEBUG-MODULE4-INSPECTION-PLAN.md G6:** scope.
  Modul 5 SUDAH scope di view (`visibleQuery`) tapi LEWAT di transition (G5) → tutup celah ini.
- **DEBUG-MODULE2-CAPA-PLAN.md WS-1:** Modul 5 TIDAK pakai antipattern `CapaAccess` (hardcode+employee)
  — justru contoh BENAR (Policy + `core.scope.*`). Jangan mundur ke pattern CAPA.
- **Decision Log:** "Notif recipient resolve by permission/constant; classification standard = `major_nc`".

---

## 4. Urutan Eksekusi

1. **WS-5** (scope transition) — celah lintas site, keamanan.
2. **WS-2 + WS-3** (finding integrity) — major→CAPA & open check.
3. **WS-1** (notif) — reliability.
4. **WS-4** (audit transition) — compliance.
5. **WS-6/7/8/9** — tests, doc, frontend, verify.

---

## 5. Commands Verifikasi

```bash
# Suite Audit (saat ini PASS)
php artisan test tests/Feature/Modules/Audit

# Repro G1: notif silent setelah start
php artisan tinker --execute="
\$u=App\Models\User::factory()->create(); \$u->assignRole('Admin'); \$u->update(['employee_id'=>App\Models\Core\Users\Employee::factory()->create()->id]);
\$a=App\Models\Modules\Audit\Audit::factory()->create(['status'=>'planned']);
app(App\Core\Workflow\WorkflowService::class)->start('audit',\$a->id,\$u);
app(App\Http\Controllers\Modules\Audit\AuditController::class)->startAudit(request(),\$a);
echo App\Models\Core\Notifications\CoreNotification::where('type','audit.started')->count(); // saat ini 0 (BUG), seharusnya >0
"

# Repro G5: transition cross-site (harus 403, saat ini lolos)
grep -n "canExecute(\$audit)" app/Http/Controllers/Modules/Audit/AuditController.php

# Frontend
npm run build
```

---

## 6. Definition of Done (Modul 5 Total)

- [x] WS-5: transition di-scope (`ensureVisible`/`authorize`); no cross-site exec; test cover. (✅ 2026-07-15)
- [ ] WS-2: major finding tak bisa close tanpa CAPA; test cover.
- [ ] WS-3: closed finding tak bisa update; test cover.
- [ ] WS-1: notif `audit.started`/`audit.report_ready`/`audit.closed` terkirim; test cover.
- [ ] WS-4: audit trail `started`/`report_generated`/`closed` ada; test cover.
- [ ] WS-6: test regresi G1-G6; suite 100% PASS.
- [ ] WS-7: WORKFLOW.md selaras `major_nc`.
- [ ] WS-8: `npm run build` green; UI `can` flags + error benar.
- [ ] WS-9: numbering/evidence/comment/export terverifikasi (no gap).
- [ ] Cross-link Modul 4 G2/G3 & Core/Master WS-6 tertutup; Decision Log + Handoff.

---

## 7. Catatan Jujur

- Modul 5 **paling dekat ke production-ready** dari Modul 1-4: scope benar, Policy ada, notif service
  sudah di-inject. Gap lebih ke **ketidakkonsistenan** (transition lupa panggil notif/audit/scope)
  daripada arsitektur salah.
- G5 (transition bypass scope) adalah **celah keamanan nyata**: user lintas site bisa gerakkan audit
  orang lain. Prioritas tertinggi.
- G1/G2/G3/G4 = integrity/compliance, sama kelas dgn Modul 4 tapi di Modul 5 lebih mudah diperbaiki
  karena infrastruktur (NotificationService/AuditService/scope) SUDAH ada.
- Plan ini BUKAN eksekusi; tidak ada kode diubah sampai per WS dilakukan dengan root-cause evidence.
