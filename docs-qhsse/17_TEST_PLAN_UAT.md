# Test Plan & UAT

## Unit/Feature (Pest)
- Core: auth, RBAC, file, comment, numbering, workflow, dashboard.
- Modul: incident (25+ tests), inspection multi-unit, apd (28 tests), dll.
- Total `make test`: 603 passed / 2933 assertions.

## UAT Checklist (Phase 0)
Lihat `28_PHASE_0_UAT_CHECKLIST.md`.

## Kriteria
- Happy path, permission block, edge case (overdue, soft-delete, export).
- Build `npm run build` PASS sebelum klaim selesai.
