# Roadmap & Phasing Plan

## Prinsip Phase

Satu phase harus selesai, diuji, dan diterima sebelum phase berikutnya dimulai. Modul boleh dirancang lebih awal, tetapi development hanya fokus pada phase aktif.

## Roadmap

| Phase | Modul | Status | Dependency |
|---:|---|---|---|
| 0 | Core Foundation | Planned | - |
| 1 | Dashboard & KPI | Planned | Core Foundation, semua modul sumber data |
| 2 | Incident / Accident / Near Miss Reporting | Planned | Core Foundation, Master Data, File Upload, Notification, Workflow |
| 3 | Investigation & RCA | Planned | Core Foundation, Incident Reporting, CAPA |
| 4 | CAPA / Corrective & Preventive Action | Planned | Core Foundation, Incident, Inspection, Audit, Risk, Legal |
| 5 | Inspection Checklist | Planned | Core Foundation, CAPA, Asset, Contractor |
| 6 | Audit Management | Planned | Core Foundation, CAPA, Document, Legal |
| 7 | Document Control | Planned | Core Foundation, Notification, Workflow, Audit Trail |
| 8 | Training & Competency | Planned | Core Foundation, Employee, Contractor, Notification |
| 9 | Permit to Work | Planned | Core Foundation, Risk/JSA, Contractor, Training, Asset, Notification |
| 10 | Environmental Management | Planned | Core Foundation, CAPA, Legal, Document |
| 11 | Security Management | Planned | Core Foundation, Incident, Inspection, Contractor |
| 12 | Quality Management | Planned | Core Foundation, CAPA, Audit, Document, Asset |
| 13 | Risk Management / HIRADC / JSA | Planned | Core Foundation, Document, CAPA, Incident, PTW |
| 14 | Legal & Compliance Register | Planned | Core Foundation, Document, Audit, CAPA, Notification |
| 15 | Emergency Preparedness | Planned | Core Foundation, Training, Asset, Communication |
| 16 | Contractor Management | Planned | Core Foundation, Training, PTW, Audit, Incident |
| 17 | Asset & Equipment Safety | Planned | Core Foundation, Inspection, Document, CAPA |
| 18 | Communication & Campaign | Planned | Core Foundation, Notification, Document, Training |
| 19 | Reporting & Export Advanced | Planned | Core Foundation, Dashboard, semua modul |
| 20 | Admin & Master Data | Planned | Core Foundation, semua modul |

## Gate Per Phase

Setiap phase harus melewati:

1. Module spec disetujui.
2. Data model disetujui.
3. UI pages disetujui.
4. API contract disetujui.
5. Development selesai.
6. Test case lulus.
7. UAT diterima.
8. Decision log diperbarui.
9. Changelog diperbarui.

## Rekomendasi Urutan Awal

1. Core Foundation
2. Incident Reporting
3. CAPA
4. Inspection
5. Dashboard
6. Document Control
7. Audit Management
8. Training
9. Permit to Work
