# DEBUG-MODULE6-DOCUMENT-PLAN.md — Debug Mendalam Modul 6 (Document Control)

**Tanggal:** 2026-07-15
**Modul:** `07-document-control` (Phase 7 — modul paling kompleks & paling mature)
**Metode:** systematic-debugging (Iron Law: TIDAK ada fix tanpa root-cause evidence)
**Status:** 🟡 **Suite PASS, tapi LOGIC GAP & ANTIPATTERN ditemukan dari baca kode vs WORKFLOW.md**.

---

## 0. Konteks & Bukti Segar

- Full suite terbagi: **DocumentControl = PASS** (`DocumentControlTest` hijau).
- **Modul 6 PALING MATANG:** punya `visibleQuery()` scope benar (`core.scope.*`), `ensureVisible`/
  `ensureMutable`, version tracking `document_reviews`, audit + activity + notif per transition.
  Workflow `document` SUDAH seeded (§1). Tidak ada antipattern `CapaAccess`.
- **Tapi baca kode vs `docs-qhsse/modules/07-document-control/WORKFLOW.md` menemukan 3 gap + antipattern:**

| # | Gap | Bukti kode | WORKFLOW.md mensyaratkan |
|---|-----|-----------|--------------------------|
| G1 | Hardcode role di notif/confidential | `usersWithRole('QHSSE Manager')` L464, `usersWithRole('QHSSE Officer')` L484, `hasAnyRole(['Super Admin','Admin','QHSSE Manager'])` L663 | Sama antipattern CAPA/Incident/Investigation — fragile ke nama seeder |
| G2 | `revise()` tak cek status `rejected` | `revise()` L323-341 panggil `ensureMutable` (scope) TAPI TIDAK `abort_unless(status==='rejected')` (beda dgn `edit`/`update` L175/183 yang cek `draft`/`rejected`) | §2/§3: `revise` hanya dari `rejected` → `draft`. Doc bisa `approved`/`effective` di-revise langsung |
| G3 | `makeEffective`/`approve`/`reject` tak explicit guard status | `makeEffective` L263 / `approve` L235 / `reject` L296 cek `ensureMutable` TAPI TIDAK `abort_unless(status===...)` (beda dgn `submitReview` L209 `abort_unless draft`) | §3: transition hanya dari status tertentu; saat ini aman via `RuntimeException` workflow tapi tidak explicit |
| G4 | Test gap | Tak ada test: `revise` dari non-rejected (G2), `obsolete` cross-site, `makeEffective` non-approved, confidential download scope | — |

---

## 1. Inventori Target

| Komponen | Path |
|----------|------|
| Controller | `app/Http/Controllers/Modules/DocumentControl/DocumentControlController.php` (696 baris) |
| Model | `ControlledDocument` (punya `reviews()`, `owner()`, `approver()`, `is_confidential`), `DocumentReview` |
| Policy | — (pakai `ensureVisible`/`ensureMutable`, bukan Policy) |
| Requests | `StoreDocumentRequest`, `UpdateDocumentRequest` |
| Routes | `routes/modules.php` L167-184 (prefix `documents`, name `document.control.*`) |
| Frontend | `resources/js/Pages/Modules/DocumentControl/{Index,Show,Form}.tsx` |
| Tests | `tests/Feature/Modules/DocumentControl/DocumentControlTest.php` |
| Spec | `docs-qhsse/modules/07-document-control/{MODULE_SPEC,WORKFLOW,DATA_MODEL,TEST_CASES,API_CONTRACT,UI_PAGES}.md` |
| Seeder | `WorkflowSeeder` (document workflow), `NumberingFormatSeeder` |

---

## 2. Workstream

### WS-1: Hardcode role → constant/permission (G1)  🔴
- **Bug:** `usersWithRole('QHSSE Manager')` (L464/L537), `usersWithRole('QHSSE Officer')` (L484),
  `hasAnyRole(['Super Admin','Admin','QHSSE Manager'])` (L663) — fragile. Jika seeder ganti nama
  role → notif confidential silent gagal.
- **Fix:** gunakan `CorePermissions` constant atau resolver berbasis permission
  (`User::whereHas('roles.permissions', fn => $permission->name==='document.control.approve')`) —
  BUKAN string literal. Cross-link Core/Master WS-6 & Incident WS-6.
- **Verifikasi:** ubah nama role di seeder → notif & confidential download tetap jalan (atau pakai constant).
- **DoD:** recipient resolve tanpa hardcode; test cover.

### WS-2: `revise()` wajib status `rejected` (G2)  🔴
- **Bug:** dokumen `approved`/`effective` bisa di-revise langsung (langgar workflow order §3).
  `revise()` L323 hanya `ensureMutable`, tidak cek status.
- **Fix:** di `revise()` tambah `abort_unless($controlledDocument->status === 'rejected', 422, '...')`
  (sama pattern dgn `edit`/`update` L175/183).
- **Verifikasi:** `approved` doc → POST `revise` → `assertForbidden()`/`assertSessionHasErrors`;
  `rejected` doc → lolos.
- **DoD:** revise hanya dari rejected; test cover.

### WS-3: Explicit status guard pada transition (G3)  🟡
- **Bug:** `makeEffective`/`approve`/`reject` tidak explicit guard status (hanya via RuntimeException
  workflow). `submitReview` L209 sudah `abort_unless(draft)` — inkonsisten.
- **Fix:** tambah `abort_unless(status===...)` di `approve` (review), `makeEffective` (approved),
  `reject` (review), `obsolete` (effective) sebelum transition — konsisten dgn `submitReview`.
- **Verifikasi:** wrong-status transition → 422 (bukan 500 dari RuntimeException).
- **DoD:** semua transition explicit guard; test cover.

### WS-4: Scope pada `obsolete` (verify)  🟢
- **Cek:** `obsolete()` L289 → `reasonedTransition` L413 → `ensureMutable` (scope) ✓.
  Jadi `obsolete` SUDAH di-scope (tidak seperti Modul 5 G5). **Tidak ada gap** — verifikasi lewat test.
- **DoD:** test obsolete cross-site → 403.

### WS-5: Version tracking `document_reviews` (verify)  🟢
- **Cek:** `submitForReview` (L404) create pending; `approve` (L244) / `reject` (L305) update latest
  pending; `revise` (L331) `decision='revise'`; re-submit buat record BARU (L404) ✓.
  Sesuai §5. **Tidak ada gap** — verifikasi test history.
- **DoD:** test sequence draft→review→approve→effective→obsolete + reject→revise→submit.

### WS-6: Audit/Activity/Notif per transition (verify)  🟢
- **Cek:** tiap transition panggil `auditTransition` + `activity->log` + `notify*` ✓ (L252/276/312/333/409/422).
  Berbeda dgn Modul 4 G2/G3 yang LUPA. Modul 6 BENAR. **Tidak ada gap**.
- **DoD:** test audit_log per transition exists.

### WS-7: Tests & Regresi (G4)  🔴
- Tambah test: (a) revise dari non-rejected gagal (WS-2); (b) obsolete/approve/makeEffective/reject
  cross-site → 403 (WS-1/WS-4); (c) wrong-status transition → 422 (WS-3);
  (d) confidential download hanya owner/approver/QHSSE (WS-1); (e) document_reviews history (WS-5).
- **DoD:** suite tetap 100% PASS + cover G1-G4.

### WS-8: Frontend  🟡
- **Sudah:** TS `sla_days` fix (build green).
- **Debug:** Show render `can` (submit_review/approve/make_effective/obsolete/revise) & `availableTransitions`;
  error `workflow`/`file`/`reason` handling; confidential badge + download gate.
- **DoD:** `npm run build` green; UI sesuai spec.

### WS-9: Expiry Reminder Command (verify/future)  🟡
- WORKFLOW.md §10: scheduled `documents:check-expiry` daily → notif `document.expiry_reminder`.
- **Cek:** apakah command ada di `app/Console/Commands`? Jika tidak → WS ini future (bukan blocker Phase 7).
  Pastikan tidak crash kalau tidak ada.
- **DoD:** command ada & jalan, atau dicatat di Decision Log sebagai future enhancement.

---

## 3. Cross-Link ke Plan Lain

- **DEBUG-CORE-MASTER-PLAN.md WS-6 + DEBUG-MODULE1-INCIDENT-PLAN.md WS-6:** hardcode role (G1) SAMA.
  Modul 6 sudah pakai `usersWithRole()` helper — cukup ganti implementasi ke permission-based.
- **DEBUG-MODULE2-CAPA-PLAN.md WS-1 + DEBUG-MODULE5-AUDIT-PLAN.md G5:** Modul 6 SUDAH scope benar
  (`ensureVisible`/`ensureMutable` di semua method termasuk `obsolete`) — contoh BENAR, beda dgn Modul 5 G5.
- **DEBUG-MODULE4-INSPECTION-PLAN.md G2/G3:** Modul 6 SUDAH notif + audit per transition — contoh BENAR.
- **Decision Log:** "Notif recipient resolve by permission/constant; jangan hardcode role name".

---

## 4. Urutan Eksekusi

1. **WS-2** (revise status guard) — logic bug workflow.
2. **WS-1** (hardcode role) — reliability notif/confidential.
3. **WS-3** (explicit status guard) — konsistensi.
4. **WS-7** (tests) — regresi.
5. **WS-4/5/6/8/9** — verify scope/version/audit, frontend, expiry command.

---

## 5. Commands Verifikasi

```bash
# Suite Document (saat ini PASS)
php artisan test tests/Feature/Modules/DocumentControl

# Repro G2: revise dari approved -> harus gagal (saat ini lolos = bug)
php artisan tinker --execute="
\$u=App\Models\User::factory()->create(); \$u->assignRole('Admin'); \$u->update(['employee_id'=>App\Models\Core\Users\Employee::factory()->create()->id]);
\$d=App\Models\Modules\DocumentControl\ControlledDocument::factory()->create(['status'=>'approved','owner_id'=>\$u->id]);
app(App\Http\Controllers\Modules\DocumentControl\DocumentControlController::class)->revise(request(),\$d);
echo \$d->fresh()->status; // saat ini 'draft' (BUG), seharusnya 'approved'
"

# Repro G1: hardcode role
grep -n "usersWithRole\|hasAnyRole" app/Http/Controllers/Modules/DocumentControl/DocumentControlController.php

# Frontend
npm run build
```

---

## 6. Definition of Done (Modul 6 Total)

- [ ] WS-2: `revise` hanya dari `rejected`; test cover.
- [ ] WS-1: notif/confidential resolve tanpa hardcode role; test cover.
- [ ] WS-3: semua transition explicit status guard; test cover.
- [ ] WS-7: test regresi G1-G4; suite 100% PASS.
- [ ] WS-4: `obsolete` cross-site → 403 (verify scope).
- [ ] WS-5: `document_reviews` history benar (verify).
- [ ] WS-6: audit/activity/notif per transition (verify).
- [ ] WS-8: `npm run build` green; UI `can` + error benar.
- [ ] WS-9: expiry command ada/jalan atau tercatat future.
- [ ] Cross-link Core/Master WS-6, Incident WS-6, Modul 4/5 tertutup; Decision Log + Handoff.

---

## 7. Catatan Jujur

- Modul 6 **paling production-ready** dari Modul 1-5: scope benar di SEMUA method (termasuk `obsolete`),
  notif + audit + activity per transition, version tracking `document_reviews` lengkap.
- Satu logic bug nyata: **G2** (revise dari non-rejected). Sisanya antipattern hardcode role (G1) &
  inkonsistensi guard (G3) — bukan arsitektur salah.
- Modul 6 adalah **referensi baik** untuk memperbaiki Modul 4/5 (notif/audit/scope sudah benar di sini).
- Plan ini BUKAN eksekusi; tidak ada kode diubah sampai per WS dilakukan dengan root-cause evidence.
