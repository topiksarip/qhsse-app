# Master Data Specification

## Core Master Data (`app/Models/Core/MasterData`)
- **Site** — lokasi/site perusahaan.
- **Area** — area dalam site.
- **Department** — departemen.
- **Position** — jabatan.
- **Company** — perusahaan (termasuk kontraktor).
- **Employee** — karyawan (terikat user, site, department, position).
- **Severity** — tingkat keparahan.
- **Priority** — prioritas.
- **Status** — status generik (workflow states).
- **Category** — kategori (insiden, dll).
- **RiskMatrixLevel** — level matriks risiko (likelihood × consequence).

## Konvensi
- Semua master data punya CRUD + deactivate (soft).
- Permission `core.*.view/create/update/deactivate/delete`.
- Digunakan lintas modul (FK site_id, area_id, department_id, severity_id, priority_id).
