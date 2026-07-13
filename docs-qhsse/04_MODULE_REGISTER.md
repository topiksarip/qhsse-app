# Module Register

| Modul | Prioritas | Status | Dependency | Owner |
|---|---:|---|---|---|
| Core Foundation | 0 | Released | - | QHSSE/Admin |
| Dashboard & KPI | 1 | In Test | Core Foundation, semua modul sumber data | QHSSE/Admin |
| Incident / Accident / Near Miss Reporting | 2 | In Test | Core Foundation, Master Data, File Upload, Notification, Workflow | QHSSE/Admin |
| Investigation & RCA | 3 | Planned | Core Foundation, Incident Reporting, CAPA | QHSSE/Admin |
| CAPA / Corrective & Preventive Action | 4 | Planned | Core Foundation, Incident, Inspection, Audit, Risk, Legal | QHSSE/Admin |
| Inspection Checklist | 5 | Planned | Core Foundation, CAPA, Asset, Contractor | QHSSE/Admin |
| Audit Management | 6 | Planned | Core Foundation, CAPA, Document, Legal | QHSSE/Admin |
| Document Control | 7 | Planned | Core Foundation, Notification, Workflow, Audit Trail | QHSSE/Admin |
| Training & Competency | 8 | Planned | Core Foundation, Employee, Contractor, Notification | QHSSE/Admin |
| Permit to Work | 9 | Planned | Core Foundation, Risk/JSA, Contractor, Training, Asset, Notification | QHSSE/Admin |
| Environmental Management | 10 | Planned | Core Foundation, CAPA, Legal, Document | QHSSE/Admin |
| Security Management | 11 | In Test | Core Foundation, Incident, Inspection, Contractor | QHSSE/Admin |
| Quality Management | 12 | In Test | Core Foundation, CAPA, Audit, Document, Asset | QHSSE/Admin |
| Risk Management / HIRADC / JSA | 13 | Planned | Core Foundation, Document, CAPA, Incident, PTW | QHSSE/Admin |
| Legal & Compliance Register | 14 | Planned | Core Foundation, Document, Audit, CAPA, Notification | QHSSE/Admin |
| Emergency Preparedness | 15 | Planned | Core Foundation, Training, Asset, Communication | QHSSE/Admin |
| Contractor Management | 16 | Planned | Core Foundation, Training, PTW, Audit, Incident | QHSSE/Admin |
| Asset & Equipment Safety | 17 | Planned | Core Foundation, Inspection, Document, CAPA | QHSSE/Admin |
| Communication & Campaign | 18 | Planned | Core Foundation, Notification, Document, Training | QHSSE/Admin |
| Reporting & Export Advanced | 19 | Planned | Core Foundation, Dashboard, semua modul | QHSSE/Admin |
| Admin & Master Data | 20 | In Test | Core Foundation, semua modul | QHSSE/Admin |

## Status Value

- Planned
- In Design
- In Development
- In Test
- UAT
- Released
- On Hold

## Rule

Tidak ada modul masuk development sebelum `MODULE_SPEC.md`, `DATA_MODEL.md`, `UI_PAGES.md`, dan `TEST_CASES.md` tersedia.

> Status `In Test` pada 2026-07-13 berarti source, focused tests, full regression, build, dan fresh seed lokal sudah lulus; production deployment/UAT masih menjadi release gate sebelum status `Released`.
