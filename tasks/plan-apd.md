# Plan: Modul APD / PPE (21-apd-ppe)

> Spec: `docs-qhsse/modules/21-apd-ppe/` (MODULE_SPEC, DATA_MODEL, WORKFLOW)
> Status: SPEC APPROVED (interview 2026-07-16). Siap dibangun bertahap.
> Stack: Laravel 12 + Inertia React + TS + PostgreSQL (prod) / SQLite (test).
> Reuse: NumberingService, FileService, AuditTrail, Comment, ActivityLog, Workflow Core, ListQuery, SearchController.

## Scope (dari interview)
- MVP penuh: master katalog + inventori stok + issuance + inspeksi.
- Mixed tracking: serial (per-unit) + batch (kuantitas).
- Pemegang polimorfik: employee / contractor / location.
- Inspeksi: scheduled + incidental + manual, foto evidence.
- Workflow: request → approve → issue → return/dispose.
- Integrasi: Risk Register, Incident, Inspection→CAPA, Training (fit-test), Search.
- Dashboard: 3-4 widget di shell existing.

## Konvensi File (ikuti modul 17 Asset)
- Models: `app/Models/Modules/Apd/{ApdCatalog,ApdItem,ApdIssuance,ApdInspection,RiskApdRequirement}.php`
- Controllers: `app/Http/Controllers/Modules/Apd/...`
- Requests: `app/Http/Requests/Modules/Apd/...`
- Policies: `app/Policies/Modules/Apd/...`
- Pages: `resources/js/Pages/Modules/Apd/{Catalog,Items,Issuances,Inspections}/...`
- Tests: `tests/Feature/Modules/Apd/...`

## Phases

### Phase A — Master + Stok (foundation APD)
- [ ] Migration `apd_catalogs`, `apd_items` (+ indexes, checks)
- [ ] Models + relations + `Auditable` + SoftDeletes (katalog is_active)
- [ ] Permission `apd.view/create/update/delete/export` di `CorePermissions`
- [ ] Policy `ApdCatalogPolicy`, `ApdItemPolicy`
- [ ] Numbering format `apd` (PPE-YYYY-NNNN)
- [ ] CatalogController (CRUD) + ItemController (receive stok, list, show)
- [ ] Requests Store/Update/Receive
- [ ] Pages: Catalog/{Index,Form,Show}, Items/{Index,Show}
- [ ] Seeder `ApdSeeder` (sample katalog + items)
- [ ] Tests: CatalogTest, ItemTest (CRUD + permission gate + numbering)
- [ ] `npm run build` + `make test` hijau

### Phase B — Issuance + Workflow
- [ ] Migration `apd_issuances`
- [ ] Model `ApdIssuance` + relation ke item/holder polimorfik
- [ ] Permission `apd.issue/approve/request`
- [ ] Numbering `apd_issue` (PPE-ISSUE-YYYY-NNNN)
- [ ] IssuanceController: create/request/approve/issue/return/dispose
- [ ] Workflow Core transition (histori + activity)
- [ ] Update stok saat issue/return/dispose (serial status, batch qty)
- [ ] Policy `ApdIssuancePolicy` (gated per transisi)
- [ ] Pages: Issuances/{Index,Form,Show}
- [ ] Tests: IssuanceTest (workflow + stock effect + permission)
- [ ] Build + test hijau

### Phase C — Inspeksi
- [ ] Migration `apd_inspections`
- [ ] Model `ApdInspection` + FileService collection `inspection`
- [ ] Permission `apd.inspect`
- [ ] InspectionsController (store) + scheduled flag helper
- [ ] `tidak_layak` → item status `damaged`
- [ ] Pages: Inspections/{Index,Form}
- [ ] Tests: InspectionsTest
- [ ] Build + test hijau

### Phase D — Integrasi + Dashboard + Search
- [ ] Migration `risk_apd_requirements` + model + permission gated
- [ ] Risk Register Show: panel APD wajib (edit relation)
- [ ] Incident: tambah field `ppe_involved/ppe_id/ppe_failure` (migrasi additive) + Show link + CAPA button
- [ ] Inspection: temuan `ppe_not_used` → escalate CAPA
- [ ] Training: `ppe_fit_test` type + `apd_item_id` link
- [ ] SearchController: 2 entry (apd_items, apd_issuances)
- [ ] Dashboard widgets (4): stok rendah, overdue/expired, compliance/site, top hazard
- [ ] Tests integrasi dasar + SearchTest entries
- [ ] Build + test hijau
- [ ] Deploy Ubuntu-5 + smoke

## HARD Rules
- Otorisasi SELALU di backend (Policy/Service), bukan UI-only.
- Numbering immutable; generate di create.
- File private via ManagedFileService.
- Tidak ubah modul Asset (batas terpisah).
- Setiap phase: migration + model + request + controller + policy + page + test + build hijau.

## Verifikasi per phase
- `php artisan migrate --force` (prod) / SQLite in-memory (test)
- `make test` (Phase tests) hijau
- `npm run build` hijau
- Deploy Ubuntu-5 + smoke (login, buka menu APD, issue 1 item)
