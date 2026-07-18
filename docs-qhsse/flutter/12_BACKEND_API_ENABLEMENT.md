# Backend API Enablement (Prasyarat Flutter)

> **Peringatan kritis:** Backend saat ini **belum** punya lapisan JSON API. Semua route adalah Inertia.
> Tanpa enablement ini, Flutter **tidak bisa** berkomunikasi sama sekali.

## Langkah Implementasi (Laravel)

1. **Install Sanctum** (sudah ada di Laravel 12 via `laravel/sanctum`).
   - `php artisan vendor:publish --provider="Laravel\\Sanctum\\SanctumServiceProvider"`.
   - Pastikan `users` punya tokenable (trait `HasApiTokens`).

2. **Buat route `/api`** di `routes/api.php` dengan middleware `auth:sanctum` + `throttle`.
   - Padanankan tiap resource Inertia (`routes/web.php`) ke JSON controller/resource.

3. **API Resources / ResourceCollections** (`app/Http/Resources/`) untuk bentuk response konsisten (envelope `data`/`meta`).

4. **Auth controller** (`/api/v1/auth/login|logout|me`) → `Auth::attempt` + `createToken`.
   - Cek `users.is_active` (sudah diblokir di `App\\Core\\Auth` untuk web).

5. **Reuse layanan inti**: `NumberingService`, `ManagedFileService`, `WorkflowService`, `ScopeService`, `CommentService`, `NotificationService` — panggil dari API controller, jangan duplikasi.

6. **Otorisasi**: terapkan `permission:{module}.{action}` + scope (`ScopeService`) di API middleware/policy. Jangan percaya header dari client.

7. **File endpoint JSON**: `GET /api/v1/files/{id}/download` menggunakan `ParentAuthorizationRegistry` (sudah fail-closed).

8. **Validasi**: pakai Request class yang sama dengan web (server-side).

9. **CORS**: izinkan origin app (atau pakai cert pinning + HTTPS; CORS tidak relevan untuk non-browser, tapi tetap set untuk web preview).

10. **Tests**: tambah `tests/Feature/Api/*` (Pest) — happy/permission/edge, sama seperti web. Jalankan `make test`.

## Mapping Cepat (Inertia → JSON)
- `incidents` (web) → `/api/v1/incidents` (JSON, butuh controller API)
- `investigations` (web) → `/api/v1/investigations` (JSON, butuh controller API)
- `capa-actions` (web) → `/api/v1/capa-actions` (JSON, butuh controller API)
- `inspections` (web) → `/api/v1/inspections` (JSON, butuh controller API)
- `documents` (web) → `/api/v1/documents` (JSON, butuh controller API)
- `audits` (web) → `/api/v1/audits` (JSON, butuh controller API)
- `training` (web) → `/api/v1/training` (JSON, butuh controller API)
- `risk` (web) → `/api/v1/risk` (JSON, butuh controller API)
- `legal` (web) → `/api/v1/legal` (JSON, butuh controller API)
- `contractors` (web) → `/api/v1/contractors` (JSON, butuh controller API)
- `assets` (web) → `/api/v1/assets` (JSON, butuh controller API)
- `apd` (web) → `/api/v1/apd` (JSON, butuh controller API)
- `campaigns` (web) → `/api/v1/campaigns` (JSON, butuh controller API)
- `reports` (web) → `/api/v1/reports` (JSON, butuh controller API)
- `emergency` (web) → `/api/v1/emergency` (JSON, butuh controller API)
- `permits` (web) → `/api/v1/permits` (JSON, butuh controller API)
- `environment` (web) → `/api/v1/environment` (JSON, butuh controller API)
- `security` (web) → `/api/v1/security` (JSON, butuh controller API)
- `quality` (web) → `/api/v1/quality` (JSON, butuh controller API)
- `core` (web) → `/api/v1/core` (JSON, butuh controller API)

## Checklist Sebelum Flutter Dapat Dites
- [ ] Sanctum aktif + `HasApiTokens` di User
- [ ] `routes/api.php` dengan prefix `v1` + `auth:sanctum`
- [ ] Login mengembalikan token + permissions
- [ ] Minimal 1 resource (incident) full CRUD + aksi state di JSON
- [ ] File upload/download terotorisasi di JSON
- [ ] Test API hijau (`make test`)
- [ ] Dokumentasi `03_API_ENDPOINTS.md` cocok dengan implementasi
