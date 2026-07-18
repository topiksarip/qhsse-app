# Product Requirements Document — QHSSE App

## Tujuan
Sistem kerja QHSSE terpadu yang memenuhi standar ISO 9001/14001/45001/27001 secara operasional.

## User Stories Inti
1. **Operator** dapat melaporkan insiden/near-miss dari lapangan dengan bukti foto.
2. **Supervisor** meninjau, mengassign CAPA, dan mengeksekusi inspeksi multi-unit.
3. **QHSSE Officer** menjalankan audit, menutup temuan, dan memantau KPI.
4. **Admin** mengelola master data, RBAC, dan dokumen terkendali.
5. **Auditor** meninjau & mengekspor seluruh modul secara read-only.

## Fitur Per Modul
Lihat `04_MODULE_REGISTER.md` dan `09_API_SPEC.md`.

## Acceptance Criteria Global
- Setiap aksi kritis tercatat di audit trail.
- Otorisasi selalu dicek di backend (tidak hanya UI).
- File bukti privat via endpoint terotorisasi (fail-closed).
- Nomor dokumen unik via Numbering Service.
