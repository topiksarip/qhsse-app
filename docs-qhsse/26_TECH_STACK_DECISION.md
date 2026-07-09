# Tech Stack Decision — QHSSE Web Application

## 1. Decision

Default implementation stack untuk project QHSSE adalah:

```text
Backend/Application: Laravel 12
Frontend: Inertia React + TypeScript
Database: PostgreSQL
Cache/Queue: Redis
CSS/UI: Tailwind CSS
Auth: Laravel session auth + optional Sanctum for API/mobile later
RBAC: spatie/laravel-permission
File Storage: Laravel Storage private disk, S3-compatible later
PDF/Excel: Laravel Excel + PDF renderer selected during implementation
Testing: PHPUnit/Pest for backend, frontend tests as needed, browser testing for critical flows
Deployment: Docker-ready local/dev, VPS-ready production
Architecture: Modular Monolith
```

## 2. Assumptions

Karena belum ada jawaban final untuk semua keputusan teknis, readiness ini memakai default enterprise-ready berikut:

1. Aplikasi web utama, bukan native mobile.
2. UI harus responsive dan nyaman untuk mobile browser.
3. Login awal email/password.
4. SSO disiapkan sebagai roadmap, bukan Phase 0.
5. Local development memakai Docker-ready setup.
6. Production target awal VPS/cloud, bukan shared hosting.
7. Database utama PostgreSQL.
8. Redis dipakai untuk cache, queue, notification job, dan scheduler support.
9. Modular monolith dipilih; microservice dilarang di awal.
10. Semua modul harus memakai Core Foundation yang sama.

Jika user mengubah salah satu asumsi ini, update Decision Log sebelum coding.

## 3. Rationale

### Laravel 12

Cocok untuk QHSSE enterprise karena:

- Auth, validation, queue, scheduler, storage, mail, migration sudah matang.
- Mudah membangun admin panel dan workflow bisnis.
- Ekosistem RBAC dan audit kuat.
- Cocok untuk modular monolith.

### Inertia React + TypeScript

Cocok karena:

- Memberi pengalaman SPA tanpa memisah total backend/frontend.
- Lebih cepat untuk dashboard dan form enterprise.
- Mengurangi kompleksitas API authentication di awal.
- React tetap fleksibel untuk UI kompleks.

### PostgreSQL

Cocok karena:

- Relational integrity kuat.
- Reporting dan filter kompleks lebih aman.
- JSONB tersedia untuk metadata bila dibutuhkan.
- Cocok untuk audit trail dan workflow history.

### Redis

Dipakai untuk:

- Queue notification.
- Cache dashboard.
- Rate limit.
- Background job.

### Modular Monolith

Dipilih karena:

- Modul banyak tetapi masih satu domain perusahaan.
- Lebih mudah maintain daripada microservice.
- Lebih cepat untuk generating bertahap.
- Shared core seperti user, permission, file, audit, notification tetap konsisten.

## 4. Rejected Alternatives

### Next.js Full Stack

Ditolak untuk default karena:

- Workflow enterprise, scheduler, queue, PDF/export, RBAC lebih banyak custom work.
- Laravel lebih cocok untuk aplikasi internal operasional yang heavily form/workflow based.

### Laravel API + Separate React SPA

Ditunda karena:

- Auth dan deployment lebih kompleks.
- Inertia sudah cukup untuk web app utama.
- API bisa dibuka kemudian untuk mobile/integrasi.

### Microservices

Ditolak untuk tahap awal karena:

- Menambah operational complexity.
- Belum ada kebutuhan scaling terpisah.
- Core data QHSSE sangat saling terkait.

### Shared Hosting

Tidak direkomendasikan karena:

- Queue, scheduler, Redis, dan worker sering terbatas.
- Sulit untuk production-grade QHSSE.

## 5. Project Structure Target

```text
app/
  Core/
    Auth/
    Users/
    Permissions/
    MasterData/
    Files/
    Notifications/
    Numbering/
    Workflow/
    AuditTrail/
    Comments/
    Exports/
    Dashboard/
  Modules/
    Dashboard/
    Incident/
    Investigation/
    Capa/
    Inspection/
    Documents/
    Audit/
    Training/
    Permit/
    Risk/
    Environment/
    Security/
    Quality/
    Legal/
    Emergency/
    Contractor/
    Asset/
    Communication/
    Reporting/

resources/js/
  Layouts/
  Pages/
    Core/
    Modules/
  Components/
  lib/

routes/
  web.php
  auth.php
  core.php
  modules.php

database/
  migrations/
  seeders/
  factories/

docs-qhsse/
  ...documentation...

tasks/
  plan.md
  todo.md

handoff/
  PHASE-xx-...-HANDOFF.md
```

## 6. Command Targets

Final command dapat berubah sesuai bootstrap, tetapi target standar:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan serve
php artisan test
npm run build
```

Jika Docker dipakai:

```bash
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan migrate --seed
docker compose exec node npm install
docker compose exec node npm run dev
```

## 7. Mandatory Packages Candidate

Backend:

- `spatie/laravel-permission`
- `maatwebsite/excel`
- PDF package to decide during implementation
- audit implementation can be custom first; package optional

Frontend:

- React
- TypeScript
- Inertia React
- Tailwind CSS
- Chart library selected during Dashboard phase

Rule: jangan menambah dependency tanpa alasan di Decision Log.

## 8. Security Baseline

- Server-side permission on every protected action.
- UI permission only for convenience, not security.
- Private file storage.
- Authorized file download endpoint.
- Audit trail for critical event.
- Soft delete for referenced data.
- Input validation through Form Request or equivalent.
- No secrets committed.

## 9. Source-Driven Development Notes

Saat mulai coding, agent harus mengecek dokumentasi resmi/current docs untuk:

- Laravel 12 installation and auth starter kit.
- Inertia React setup.
- spatie/laravel-permission setup.
- PostgreSQL config.
- Redis queue/scheduler config.
- Laravel file storage private download pattern.

## 10. Decision Status

Status: Proposed default and ready for implementation unless user overrides.

Jika user tidak memberi koreksi, Phase 0 menggunakan stack ini.
