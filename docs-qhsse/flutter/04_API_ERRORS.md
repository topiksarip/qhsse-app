# API Errors & Validation

## HTTP Status
- `200` OK (GET/PUT/PATCH/DELETE sukses)
- `201` Created (POST sukses)
- `204` No Content (logout)
- `400` Bad Request (format salah)
- `401` Unauthenticated (token invalid/expired)
- `403` Forbidden (tidak punya permission/scope)
- `404` Not Found (resource tidak ada / modul tak terdaftar)
- `422` Unprocessable Entity (validasi gagal)
- `429` Too Many Requests (rate limit)
- `500` Server Error

## Envelope Error
```json
{
  "message": "Validation failed",
  "errors": {
    "title": ["The title field is required."],
    "site_id": ["The selected site id is invalid."]
  }
}
```

## Aturan
- `422` selalu mengembalikan `errors` keyed by field (Flutter tinggal map ke TextFormField).
- `403` artinya **server menolak** — jangan di-handle sebagai bug UI.
- `404` untuk modul yang tidak terdaftar di `ParentAuthorizationRegistry` (fail-closed) — konsisten dengan `ManagedFileService`.

## Client Handling (Flutter)
- Gunakan `DioInterceptor` yang memetakan `errors` → `Map<String,String>` per field.
- Tampilkan `errors[field]` di bawah input terkait.
- Untuk `401` → trigger re-auth flow.
