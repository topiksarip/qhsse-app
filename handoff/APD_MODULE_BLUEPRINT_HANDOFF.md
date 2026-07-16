# Handoff — Blueprint Modul APD / PPE (21-apd-ppe)

> Tanggal: 2026-07-16
> Status: Blueprint SELESAI & DIVALIDASI. Belum ada kode (baru docs + plan).

## Yang dihasilkan
1. `docs-qhsse/modules/21-apd-ppe/MODULE_SPEC.md` — tujuan, dependensi, roles, fitur, business rules, permission keys, role-permission matrix, notifikasi, file structure, dashboard metrics.
2. `docs-qhsse/modules/21-apd-ppe/DATA_MODEL.md` — 5 tabel (`apd_catalogs`, `apd_items`, `apd_issuances`, `apd_inspections`, `risk_apd_requirements`), DDL + migration reference, ERD ASCII, indexes, shared relations.
3. `docs-qhsse/modules/21-apd-ppe/WORKFLOW.md` — issuance lifecycle (Workflow Core), receive stok, inspeksi (scheduled/incidental/manual), integrasi ke Risk/Incident/Inspection/Training/Search, dashboard widgets, scheduler notifikasi.
4. `docs-qhsse/19_DECISION_LOG.md` — 4 entri keputusan APD (module terpisah dari Asset, mixed tracking, holder polimorfik, workflow).
5. `tasks/plan-apd.md` — rencana build bertahap (Phase A–D) dengan HARD rules & verifikasi.

## Keputusan kunci (dari interview)
- Module ID `21-apd-ppe`; **terpisah** dari Asset (`17-asset-equipment-safety`).
- **Mixed tracking**: serial (per-unit, helm/harness) + batch (kuantitas, sarung tangan).
- **Holder polimorfik**: employee / contractor / location (tanpa FK fisik).
- **Issuance Workflow Core**: `draft → requested → approved → issued → returned/disposed/rejected`.
- **Inspeksi**: scheduled + incidental + manual, foto via FileService.
- **Integrasi penuh**: Risk Register (hazard→APD wajib), Incident (APD gagal/tidak dipakai), Inspection (temuan→CAPA), Training (fit-test), Search.
- **Dashboard**: 4 widget di shell existing.

## Konvensi yang diikuti (konsisten modul 17 Asset)
- Models `app/Models/Modules/Apd/`, Controllers `app/Http/Controllers/Modules/Apd/`, dll.
- `Auditable` concern + SoftDeletes; `site_id/area_id/department_id` master FKs.
- Permission `apd.*` di `CorePermissions::all()` + `roleMap()`.
- `module_name='apd'` untuk files/comments/activity/audit/workflow.

## Langkah berikutnya (bila disetujui)
Eksekusi `tasks/plan-apd.md` mulai **Phase A** (Master + Stok):
1. Migration + Models + Permission + Numbering.
2. Catalog CRUD + Item receive/list/show.
3. Seeder + Tests.
4. Build + test hijau → deploy smoke.

Belum ada perubahan kode yang di-commit untuk modul ini (hanya dokumen & plan).

## Catatan
- Tidak ada soft-delete wajib di `apd_items` untuk serial rusak → status `disposed` (audit retention). Bila regulasi butuh, bisa ditambah nanti.
- Link ke Incident/Training/Inspection bersifat logical (field di tabel modul lain / pivot), bukan FK dari tabel APD — sesuai pola cross-module existing.
