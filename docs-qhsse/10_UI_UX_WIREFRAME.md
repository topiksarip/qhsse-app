# UI/UX & Wireframe

## Layout
- Navbar atas warna #fdb913 (orange). Sidebar kiri per modul (filter by role).
- Dashboard: KPI cards per modul, overdue highlight, quick-report.

## Halaman Umum (setiap modul)
- Index (search/filter/pagination, export CSV).
- Form (create/edit) dengan validasi server.
- Show (detail + tab: Comments, Activity, Workflow History, Files, Audit).

## Komponen Shared
- `SearchableMultiSelect` (inline SVG, no heroicons dep).
- Badge: Severity / Priority / Status.
- Table dengan sort + pagination.

## Inspeksi Multi-Unit
- Form: pilih Daftar Unit dari master Asset (searchable multi-select).
- Show: per-unit page, checkbox ✓ / cancel, complete terkunci sampai semua unit selesai/dibatalkan.
