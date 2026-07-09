# Product Requirement Document — QHSSE Web Application

## 1. Product Vision

Aplikasi web QHSSE modular untuk mengelola semua proses Quality, Health, Safety, Security, dan Environment dalam satu platform yang scalable, auditable, dan mudah digunakan.

## 2. Product Strategy

- Phase 0 membangun fondasi umum.
- Phase berikutnya membangun modul secara berurutan.
- Setiap modul harus memiliki workflow, permission, data model, API, UI, notification, report, dan test case.
- Dashboard lintas modul dibuat setelah data utama tersedia.

## 3. Target User

- Employee/Reporter: melapor dan menyelesaikan action.
- Supervisor: review laporan, assign, monitor tim.
- QHSSE Officer: investigasi, verifikasi, audit, inspeksi.
- QHSSE Manager: approve, monitor KPI, export report.
- Department Head: monitor departemen.
- Contractor: akses terbatas.
- Auditor: akses baca/report.
- Admin: master data dan user.

## 4. High-Level Requirement

- Authentication dan authorization.
- Modular QHSSE workflows.
- Evidence/file attachment.
- Notification dan reminder.
- Audit trail.
- Numbering otomatis.
- Dashboard KPI.
- Export PDF/Excel/CSV.
- Mobile-friendly reporting.
- Role/site/department-based visibility.

## 5. Module Scope

Semua modul pada Module Register termasuk scope produk, tetapi urutan implementasi mengikuti roadmap.

## 6. Out of Scope Awal

- Workflow builder visual.
- Offline-first full synchronization.
- Field-level permission kompleks.
- AI automation.
- Native mobile app.
- IoT integration.

## 7. Acceptance Produk Minimum

Produk minimum dianggap layak ketika Core Foundation, Incident Reporting, CAPA, Inspection, dan Dashboard dasar berjalan end-to-end.
