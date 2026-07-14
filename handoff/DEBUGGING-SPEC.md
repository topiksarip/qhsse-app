# SPEC DEBUGGING — QHSSE App (Deploy Ubuntu-5 / 18.192.98.211)

**Tanggal:** 2026-07-15
**Metodologi:** systematic-debugging (4 fase) + qhsse-app-development pitfall map
**Status Phase 1 (Root Cause):** SELESAI — tidak ada lagi tebakan, semua temuan berbasis bukti server nyata.

---

## 1. Tujuan Debugging

Mendebug **menyeluruh** aplikasi QHSSE yang sudah dideploy di `18.192.98.211`, menemukan
defect laten (bukan hanya error 500 yang sudah diperbaiki saat deploy), dan membawa aplikasi
ke kondisi **production-ready** (aman, auditable, dapat diaudit, berjalan end-to-end).

## 2. Ruang Lingkup

| Layer | Komponen | Cara Cek |
|-------|----------|----------|
| Bootstrap | Laravel 12.63, PHP 8.3.6, autoload | `php artisan --version`, autoload probe |
| Web | Nginx 1.24, PHP-FPM 8.3 | `systemctl`, `curl` per route |
| DB | PostgreSQL 15, 80 tabel | `migrate:status`, row counts, FK check |
| Queue/Scheduler | `QUEUE_CONNECTION=database` | `pgrep queue:work`, crontab |
| Auth | session driver database, Spatie RBAC | login flow, permission matrix |
| Frontend | Inertia React TS, Vite build | manifest, pitfall scan |
| Security | .env exposure, public storage, CVE | grep, composer audit |

**Di LUAR scope:** perubahan fitur bisnis, penambahan modul baru, refactor arsitektur.

---

## 3. Bukti Baseline (3 Pass Diagnostic — FAKTA, bukan asumsi)

| # | Temuan | Bukti | Severity |
|---|--------|-------|----------|
| H1 | **APP_ENV=local, APP_DEBUG=true di production** | grep `.env`: `APP_ENV=local`, `APP_DEBUG=true` | 🔴 HIGH |
| H2 | **Queue worker TIDAK jalan** → `GenerateReportJob` (Saved Reports) tidak pernah dieksekusi | `pgrep -af queue:work` → none; `jobs`=0 tapi `SavedReportController` `GenerateReportJob::dispatch` | 🔴 HIGH |
| H3 | **Scheduler cron HILANG** → `CheckAssetCertificates`, `CheckDocumentExpiry`, `CheckAssetInspections` tidak pernah berjalan | crontab kosong; command ada di `app/Console/Commands/*` | 🔴 HIGH |
| M1 | **User seed tidak punya employee** (orphan) → risiko 500 pada logika scope `auth()->user()->employee` | `users=1`, `employees=0`; AuditSeeder skip "no active departments or users" | 🟠 MEDIUM |
| M2 | **TS type `priorities` salah** ekspektasi `level` padahal backend kirim `sla_days` | `Capa/Form.tsx:25`, `Incident/Form.tsx:36` tipe `{level:number}`; controller kirim `sla_days` | 🟡 LOW |
| L1 | Data seed minimal (incidents/capa/inspections=0) | row counts = 0 (fresh seed) | ⚪ INFO |
| L2 | Curl login 419 = CSRF aktif (bukan bug) — harness saya salah ekstrak token | 419 = token mismatch, bukan app defect | ⚪ INFO |
| L3 | `composer audit` bersih, tidak ada symlink `public/storage`, priorities backend benar `sla_days` | audit no advisories; no public/storage; grep controller `sla_days` | ✅ GOOD |

**Yang SUDAH BENAR (tidak perlu diubah):**
- ✅ employees pakai `department_id`/`position_id` (legacy sudah di-rename → tidak ada column shadowing)
- ✅ Tidak ada `public/storage` symlink → file evidence tetap private
- ✅ Tidak ada `priorities` query pakai kolom `level` di PHP (semua `sla_days`)
- ✅ `composer audit` → 0 vulnerability
- ✅ 227 routes terdaftar, semua route utama 200/302 (302 = redirect login, wajar)

---

## 4. Hipotesis Akar Masalah (ranked)

1. **H1/H2/H3 = miskonfigurasi deployment**, bukan bug kode. Akar: proses deploy hanya menyalin file + migrate, tanpa mengaktifkan service production (env production, queue worker, scheduler).
2. **M1 = seeding gap**: `DatabaseSeeder` tidak membuat `departments` + `employees` untuk user test, sehingga fitur scope-based rentan null.
3. **M2 = ketidakcocokan kontrak TS**, rendah risiko runtime (Form kemungkinan render `name`, bukan `level`).

## 5. Definisi Selesai (Debugging DoD)

- [ ] APP_ENV=production, APP_DEBUG=false, config cached
- [ ] Queue worker aktif sebagai systemd service, restart otomatis
- [ ] Scheduler cron aktif (`schedule:run` tiap menit)
- [ ] User admin memiliki employee + department + site (scope aman)
- [ ] TS type `priorities` dikoreksi ke `sla_days`
- [ ] Verifikasi: login browser → dashboard 200, buat Saved Report → job selesai
- [ ] Tidak ada error baru di `laravel.log` pasca perbaikan
