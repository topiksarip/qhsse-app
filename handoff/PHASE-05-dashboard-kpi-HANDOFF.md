# Handoff — Phase 5 Dashboard & KPI

## 1. Status
- Phase: 5 — Dashboard & KPI
- Status: Completed
- Date: 2026-07-11

## 2. Scope Dikerjakan
- DashboardController: 8 KPI cards dari 4 modul (Incident, Investigation, CAPA, Inspection) + org data
- 4 chart widgets with real data:
  - Tren Insiden (6 bulan) — monthly incident count
  - Status CAPA — open/in_progress/pending_verify/closed breakdown
  - Hasil Inspeksi — pass/fail/pending
  - Insiden per Kategori — by category in date range
- Dashboard filters: date range (from/to), site, department
- KpiCard component: added `sub` text + `red` tone
- ChartPlaceholder: added `labels` support, removed "Shell" badge
- Quick links: role-aware, updated to include all 4 operational modules
- Dashboard.tsx: rewritten from "Phase 0 shell" to "Live KPI" branding
- Tests: 10 new DashboardTest + updated DashboardShellTest (3 tests)

## 3. KPI Cards (8)
| Label | Source | Sub-text |
|---|---|---|
| Insiden | IncidentReport count (date range) | Open count |
| Investigasi | Investigation open count | Completed count |
| CAPA Open | CapaAction open count | Overdue count (red if >0) |
| Inspeksi | Inspection pending count | Completed + fail count |
| Site Aktif | Site active count | — |
| Karyawan | Employee active count | — |
| User Aktif | User active count | — |
| Notifikasi | Unread notification count | — |

## 4. Test Results
- Tests: **160 passed** (572 assertions) — 79 P0 + 19 P1 + 19 P2 + 19 P3 + 14 P4 + 10 P5
- Build: **pass** (4.69s)

## 5. Next Prompt
```text
Lanjutkan Phase 6 — Document Control.
```
