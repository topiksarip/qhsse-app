# API Authentication

## Mekanisme
- **Laravel Sanctum** (token guard `sanctum`), bukan session cookie.
- Frontend Flutter mengirim `Authorization: Bearer <token>` di tiap request.

## Endpoint
### POST `/api/v1/auth/login`
Request:
```json
{ "email": "user@company.com", "password": "secret", "device_name": "Field-Tablet-01" }
```
Response (200):
```json
{ "data": { "token": "1|abcdef...", "user": { "id": 1, "name": "...", "roles": ["Operator"], "permissions": ["incident.reports.create", "..."] } }, "message": "Login berhasil" }
```

### POST `/api/v1/auth/logout`
Header `Authorization: Bearer <token>`. Response 204.

### POST `/api/v1/auth/refresh` (opsional)
Sanctum token tidak expired otomatis; untuk rotasi, frontend simpan `device_name` & re-login saat 401.

### GET `/api/v1/auth/me`
Mengembalikan user + roles + permission list (dipakai Flutter untuk gating UI).

## Penyimpanan Token di Device
- **JANGAN** simpan di `SharedPreferences` polos.
- Gunakan `flutter_secure_storage` (Keychain/Keystore).
- Tambahkan **biometric unlock** (local_auth) untuk membuka token di tablet lapangan.

## Handling 401
- Interceptor Dio: jika 401, coba refresh/re-login; jika gagal, arahkan ke login.

## Keamanan Tambahan
- `App\Core\Auth` sudah memblokir *inactive user* → pastikan guard Sanctum juga mengecek `users.is_active`.
- Rate-limit login (Laravel throttle) wajib untuk cegah brute force.
