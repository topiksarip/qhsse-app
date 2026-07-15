# DEBUG-MODULE11-COMMUNICATION-PLAN.md — Debug Mendalam Modul 11 (Communication & Reporting)

**Tanggal:** 2026-07-15
**Modul:** `12-communication-reporting` (Phase 12 — modul terakhir, hanya 1 resource: Campaign)
**Metode:** systematic-debugging (Iron Law: TIDAK ada fix tanpa root-cause evidence)
**Status:** 🟡 **CRUD+ack matang, tapi 2 TODO kritis (publish no-notif, export stub) + 2 logic bug + no docs/test.**

---

## 0. Konteks & Bukti Segar

Modul 11 HANYA punya `CampaignController` + `Campaign` + `CampaignAcknowledgment` (satu resource).
Tidak ada docs (`docs-qhsse/modules/12-communication-reporting/` kosong) dan tidak ada test.

Model `Campaign` matang: punya `active`/`expired`/`forSite`/`forDepartment`/`forRole` scopes,
`canBeEditedBy`/`canBePublished` helper, `acknowledgments` relasi. Controller CRUD + `publish`
+ `acknowledge` + `export`.

| # | Gap | Bukti | Dampak |
|---|-----|------|--------|
| G1 | 🔴 **`publish()` TIDAK kirim notif** | L213-214 `// TODO: Send notification blast` | Fitur inti Communication gagal: publish tidak sampai ke target audience |
| G2 | 🔴 **`export()` stub** | L262-263 `return back()->with('info','Export coming soon')` | Export tidak jalan (fitur reporting gagal) |
| G3 | 🟡 **`show()` authorize() salah** | L113 `$canViewAcknowledgments = $this->authorize(...)` — `authorize()` return void, `if()` selalu true | Acknowledgments di-load tanpa cek permission sebenarnya |
| G4 | 🟡 **`update()` tak cek status `draft`** | L149-152 langsung update; lawan `canBeEditedBy` (L208: hanya draft) | Campaign `published` bisa diedit |
| G5 | 🔴 **TIDAK ada test** | `tests/Feature/Modules/Communication/` tidak ada | Zero coverage |
| G6 | 🔴 **TIDAK ada docs** | `docs-qhsse/modules/12-communication-reporting/` kosong | No MODULE_SPEC/WORKFLOW/DATA_MODEL/TEST_CASES/API_CONTRACT/UI_PAGES |
| G7 | 🟡 **No AuditService** | ctor L25-28 hanya `ActivityService`+`NumberingService` | Transisi `publish` tidak ada `audit_logs` (hanya activity) |

---

## 1. Inventori Target

| Komponen | Path |
|----------|------|
| Controller | `CampaignController` (`app/Http/Controllers/Modules/Communication/`) |
| Models | `Campaign`, `CampaignAcknowledgment` (`app/Models/Modules/Communication/`) |
| Requests | `StoreCampaignRequest`, `UpdateCampaignRequest`, `AcknowledgeCampaignRequest` |
| Routes | `routes/modules/communication.php` (require di `routes/modules.php` L239) |
| Policy | `CampaignPolicy` (harus cek `viewAcknowledgments`/`acknowledge`/`publish`) |
| Tests | `tests/Feature/Modules/Communication/` → **TIDAK ADA** |
| Docs | `docs-qhsse/modules/12-communication-reporting/` → **KOSONG** |
| Frontend | `resources/js/Pages/Modules/Communication/Campaign/{Index,Show,CreateOrEdit}.tsx` |

---

## 2. Workstream

### WS-1: Implementasi notif publish (G1)  🔴
- **Bug:** `publish()` tidak kirim notif ke target audience.
- **Fix:** inject `NotificationService`; resolve recipients dari `target_audience`
  (`all`/`specific_site`/`specific_department`/`specific_role`) via query `User` (bukan hardcode role);
  `notifyMany($recipients, 'communication.campaign.published', $context, $actor, 'communication', $campaign->id, route('campaigns.show', $campaign))`.
- **Verifikasi:** `core_notifications` terisi sesuai target audience; test cover.
- **DoD:** publish mengirim notif; test cover berbagai `target_audience`.

### WS-2: Implementasi export CSV (G2)  🔴
- **Bug:** `export()` stub.
- **Fix:** pakai `CsvExporter` (seperti Modul 4/5/10) untuk export campaigns + acknowledgment summary.
- **Verifikasi:** file CSV ter-download; test cover.
- **DoD:** export jalan; test cover.

### WS-3: Perbaiki `show()` authorize bug (G3)  🟡
- **Bug:** `authorize()` return void; `if($canViewAcknowledgments)` selalu true.
- **Fix:** `$canViewAcks = $request->user()->can('viewAcknowledgments', Campaign::class);`
  (atau `$this->authorize()` throw 403 kalau tidak boleh — tapi kita mau conditional load).
- **DoD:** acknowledgments hanya load kalau user punya permission; test cover.

### WS-4: `update()` cek status draft (G4)  🟡
- **Bug:** campaign `published` bisa diedit.
- **Fix:** `abort_if($campaign->status !== 'draft', 422)` di `update()` (defense-in-depth, sama
  `canBeEditedBy`).
- **DoD:** update blocked saat published; test cover.

### WS-5: Feature tests (G5)  🔴
- **Bug:** zero coverage.
- **Fix:** `tests/Feature/Modules/Communication/CampaignTest.php`:
  - CRUD + permission + soft-delete
  - publish → notif ke target audience (WS-1)
  - acknowledge idempotent + only once
  - update blocked saat published (WS-4)
  - export CSV (WS-2)
  - show authorize acknowledgments (WS-3)
- **DoD:** minimal 12 test; suite PASS.

### WS-6: Tulis docs (G6)  🔴
- **Bug:** tidak ada MODULE_SPEC/WORKFLOW/DATA_MODEL/TEST_CASES/API_CONTRACT/UI_PAGES.
- **Fix:** tulis 6 file docs berdasar kode aktual (bukan spek imajiner).
- **DoD:** 6 file docs ada & akurat.

### WS-7: AuditService di publish (G7)  🟡
- **Fix:** inject `AuditService`; `audit->updated` di `publish` (status change draft→published).
- **DoD:** audit_logs terisi; test cover.

### WS-8: Frontend  🟡
- **Debug:** Show render acknowledgments hanya kalau `canViewAcknowledgments`; Index export button
  (WS-2); Publish confirm. `npm run build` green.
- **DoD:** UI sesuai; `npm run build` green.

---

## 3. Cross-Link ke Plan Lain

- **DEBUG-MODULE4-INSPECTION-PLAN.md G2/G3:** notif silent + tidak audit transition — Campaign
  `publish` SAMA (G1/G7) tapi lebih parah (TODO, bukan lupa inject).
- **DEBUG-MODULE7-TRAINING-PLAN.md G6:** `NotificationService->notify` signature — Campaign harus
  pakai `notifyMany(recipients, type, context, actor, module, refId, url)` (sudah dipakai Patrol).
- **DEBUG-CORE-MASTER-PLAN.md WS-6:** notif via permission/target query, bukan hardcode role.
- **Decision Log:** "TODO di controller = bug; BANNED stub return 'coming soon'; semua export pakai
  `CsvExporter`; `authorize()` return void (jangan assign ke var bool)."

---

## 4. Urutan Eksekusi

1. **WS-1** (notif publish — CRITICAL, fitur inti gagal) → 2. **WS-2** (export) → 3. **WS-5** (tests)
   → 4. **WS-6** (docs) → 5. WS-3/4/7/8 (bug show/update/audit/frontend).

---

## 5. Commands Verifikasi

```bash
# Cek TODO kritis
grep -n "TODO" app/Http/Controllers/Modules/Communication/CampaignController.php
# L213 publish notif, L262 export — kedua-duanya TODO/stub

# Repro G3: authorize() return void
php artisan tinker --execute="
\$c=app(App\Http\Controllers\Modules\Communication\CampaignController::class);
\$m=new ReflectionMethod(\$c,'show');
echo 'show body uses \$this->authorize(...) as bool — BUG';
"

# Repro G4: update published
# (test WS-5)

# Frontend
npm run build
```

---

## 6. Definition of Done (Modul 11 Total)

- [ ] WS-1: `publish()` kirim notif ke target audience via `NotificationService`; test cover.
- [ ] WS-2: `export()` CSV via `CsvExporter`; test cover.
- [ ] WS-5: `CampaignTest` minimal 12; suite PASS.
- [ ] WS-6: 6 docs files (`MODULE_SPEC`/`WORKFLOW`/`DATA_MODEL`/`TEST_CASES`/`API_CONTRACT`/`UI_PAGES`).
- [ ] WS-3: `show()` `canViewAcknowledgments` benar; test cover.
- [ ] WS-4: `update()` blocked saat published; test cover.
- [ ] WS-7: `publish()` audit via `AuditService`; test cover.
- [ ] WS-8: `npm run build` green; UI benar.
- [ ] Cross-link Modul 4/7/Core tertutup; Decision Log + Handoff.

---

## 7. Catatan Jujur

- Modul 11 adalah **modul terkecil** (1 resource) tapi **paling tidak didokumentasi** (no docs) dan
  punya **2 TODO kritis** yang membuat fitur inti gagal:
  - `publish()` TIDAK mengirim notif → kampanye safety alert tidak sampai ke target (compliance!).
  - `export()` stub → reporting tidak jalan.
- G3 (authorize void) adalah logic bug nyata: acknowledgments di-load tanpa cek permission.
- G4: `update()` lawan `canBeEditedBy` (hanya draft) — inkonsisten.
- Berbeda dgn Modul 9 (non-functional core), Modul 11 CRUD jalan tapi **fitur communication (notif)
  dan reporting (export) gagal**.
- Plan ini BUKAN eksekusi; tidak ada kode diubah sampai per WS dilakukan dengan root-cause evidence.
