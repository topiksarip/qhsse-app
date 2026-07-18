# Reporting & Export Specification

- Ekspor: `App\Core\Export\CsvExporter` (stream CSV, DB-agnostic `LOWER(col) LIKE LOWER(?)`).
- Modul dengan export: incidents, capa, inspections (+ `export-units`), audits, assets, apd, training, risk, legal, contractor, emergency, permit, environment, security, quality, communication, sites, departments.
- Reporting module: `report_templates` + `saved_reports`; generate & download (permission `reporting.reports.*`).
- Dashboard KPI di `DashboardController`.
