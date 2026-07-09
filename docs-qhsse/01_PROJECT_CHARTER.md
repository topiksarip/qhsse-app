# Project Charter — QHSSE Web Application

## 1. Background

Perusahaan membutuhkan platform QHSSE terintegrasi untuk mengelola proses Quality, Health, Safety, Security, dan Environment secara digital. Saat ini proses QHSSE umumnya tersebar di Excel, email, form manual, chat, dan dokumen lokal sehingga sulit dipantau, sulit diaudit, dan lambat ditindaklanjuti.

## 2. Tujuan

- Menyediakan satu sistem web untuk seluruh proses QHSSE.
- Memastikan semua laporan, temuan, action, dokumen, training, permit, risk, audit, legal, environmental, security, quality, contractor, asset, dan reporting dapat dikelola bertahap dalam satu platform.
- Mengurangi action overdue.
- Meningkatkan visibility KPI QHSSE.
- Mempercepat pelaporan dan investigasi.
- Membuat data siap audit.

## 3. Scope Besar

Scope mencakup core foundation dan 20 kelompok modul QHSSE. Semua modul dirancang sejak awal, namun development dilakukan bertahap.

## 4. Stakeholder

- Top Management
- QHSSE Manager
- QHSSE Officer
- Department Head
- Supervisor
- Employee/Reporter
- Contractor/Vendor
- Auditor
- Admin System

## 5. Success Criteria

- Core foundation dapat dipakai semua modul.
- Modul pertama berjalan end-to-end sebelum modul berikutnya dibuat.
- Semua data penting memiliki audit trail.
- Semua action dapat dipantau sampai closed.
- Dashboard menampilkan KPI lintas modul.
- Report dapat diexport sesuai kebutuhan audit/manajemen.

## 6. Risiko Utama

- Scope terlalu besar jika semua modul dikerjakan sekaligus.
- Workflow approval tiap site berbeda.
- Data master tidak rapi.
- User adoption rendah jika UI rumit.
- Notifikasi terlalu banyak dan menjadi noise.

## 7. Strategi Mitigasi

- Build bertahap berdasarkan phase.
- Core dibuat minimal tetapi mencakup semua kebutuhan modul.
- Gunakan module spec sebelum coding.
- Gunakan UAT per modul.
- Hindari custom workflow builder sampai benar-benar dibutuhkan.
