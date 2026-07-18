# API Architecture (Flutter ↔ QHSSE Backend)

> Dokumen ini menjelaskan **kontrak JSON API** yang harus diimplementasikan di Laravel agar
> frontend Flutter dapat berkomunikasi. Backend saat ini mengembalikan *Inertia responses*
> (React), bukan JSON murni — lihat `BACKEND_API_ENABLEMENT.md` untuk langkah enablement.

## Prinsip
- **REST JSON** di prefix `/api/v1`.
- **Stateless**: autentikasi via Laravel Sanctum bearer token (bukan session/cookie).
- **Envelope konsisten**: seluruh response dibungkus `{ "data": ..., "meta": {...}, "message": "..." }`.
- **Server-side authorization wajib**: permission & scope (own/department/site/company/all) selalu divalidasi di backend — Flutter hanya memangkas UI, tidak mengamankan.
- **DB-agnostic queries**: backend tetap pakai `LOWER(col) LIKE LOWER(?)` (bukan `ILIKE`) agar kompatibel SQLite test & Postgres prod.

## Response Envelope
```json
{
  "data": [ ... ],
  "meta": { "current_page": 1, "last_page": 5, "per_page": 15, "total": 73 },
  "message": null
}
```

## Versioning
- Base URL: `https://<host>/api/v1`
- Breaking change → naik `v2`.

## Content Types
- Request: `application/json` (kecuali upload file: `multipart/form-data`).
- Response: `application/json; charset=utf-8`.

## Mapping ke kode eksisting
| Konsep web (Inertia) | Bentuk JSON API |
|----------------------|-----------------|
| `route:list` resource `incidents` | `GET /api/v1/incidents` |
| aksi `incident.reports.submit` | `POST /api/v1/incidents/{id}/submit` |
| `ManagedFileService` download | `GET /api/v1/files/{id}/download` (token) |
| `NumberingService` | dihasilkan otomatis di backend saat POST |
