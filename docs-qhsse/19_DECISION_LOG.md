# Decision Log

| # | Keputusan | Alasan |
|---|-----------|--------|
| D1 | Modular monolith (bukan microservices) | Operasional sederhana, reuse core. |
| D2 | Laravel 12 + Inertia React + TS | Stack modern, type-safe. |
| D3 | PostgreSQL prod / SQLite test | Sesuai env; test cepat. |
| D4 | Spatie laravel-permission | RBAC standar. |
| D5 | Private file via ManagedFileService + ParentAuthorizationRegistry (fail-closed) | Keamanan file. |
| D6 | Numbering Service terpusat | Nomor unik & audit-friendly. |
| D7 | InspectionUnit.asset_id dari master Asset (searchable multi-select) | Daftar Unit konsisten dengan aset nyata. |
| D8 | scopeLowStock pakai `where` bukan `whereColumn(0)` | Bug Postgres `column "0"`. |
| D9 | `incident` didaftarkan di ParentAuthorizationRegistry | Komentar/file incident konsisten test. |
| D10 | Queue via systemd (bukan supervisor) | Sesuai server ubuntu-5. |
