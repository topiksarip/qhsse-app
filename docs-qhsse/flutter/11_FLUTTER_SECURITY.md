# Mobile Security

## Token Storage
- **Wajib** `flutter_secure_storage` (Keychain iOS / Keystore Android).
- Jangan `SharedPreferences` polos untuk token.

## Transport
- HTTPS selalu. Aktifkan **certificate pinning** (`dio_certificate_pinning`) ke host backend (`18.192.98.211`).
- Validasi `Authorization` di server tiap request (Sanctum).

## Device
- `local_auth` (biometric) untuk membuka app / token di tablet lapangan.
- Anti-tamper ringan: cek `flutter_jailbreak_detection` (opsional, bisa ganggu dev).

## Data Lokal
- DB lokal terenkripsi (`sqflite_sqlcipher` / `drift` + secure key).
- Hapus data lokal saat logout.

## Otorisasi
- Flutter **hanya** memangkas UI berdasarkan `permissions` dari `/auth/me`.
- **Semua** pengecekan izin tetap di backend (fail-closed, konsisten web).

## Logging
- Jangan log token / PII ke console di build release.
