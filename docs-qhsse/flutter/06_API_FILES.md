# File Upload & Download

## Upload (bukti foto / dokumen)
`POST /api/v1/{resource}/{id}/evidence` — `multipart/form-data`
- Field: `file` (binary), `module_name` (e.g. `incident`), `reference_id`, `collection` (e.g. `incident_evidence`).
- Backend menyimpan via `App\Core\Files\ManagedFileService` ke disk `local` (private).
- Response: `{ "data": { "id": 99, "url": "/api/v1/files/99/download" } }`

## Download
`GET /api/v1/files/{id}/download` (header `Authorization: Bearer <token>`)
- Backend mengecek otorisasi via `ParentAuthorizationRegistry` (fail-closed). Modul tak terdaftar → `403`/`404`.
- Stream file dari disk private (tidak pernah expose public path).

## Catatan
- Saat ini modul terdaftar di registry: `incident`, `capa`. Modul lain (asset, document, audit, dll) punya endpoint dedikasi — semua harus diberi padanan JSON.
- Flutter: gunakan `dio` `FormData` untuk upload; untuk download simpan ke app cache lalu tampilkan via `Image.file` / `open_file`.

## Ukuran & Tipe
- Validasi di server (`mimes`, `max` via Request). Client pra-validasi ukuran sebelum upload untuk UX.
