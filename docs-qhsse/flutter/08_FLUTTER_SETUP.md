# Flutter Project Setup

## Stack Yang Disarankan
- Flutter 3.x (stable), Dart 3.x.
- State: `riverpod` atau `bloc` (pilih satu, konsisten).
- HTTP: `dio` + `retrofit` (codegen) untuk binding endpoint.
- Storage aman: `flutter_secure_storage`.
- Lokal DB offline: `drift` (SQLite) untuk cache/queue.
- Env: `flutter_dotenv` (`API_BASE_URL`, `FCM_SENDER_ID`).

## Struktur
```
lib/
  core/        # dio client, interceptors (auth/error), secure storage, env
  data/        # models (freezed), remote datasources (retrofit), local (drift)
  domain/      # entities, repository interfaces
  features/    # per-modul (incident, inspection, apd, ...)
  routes/      # go_router
  shared/      # widgets (app_bar, form fields, status_badge, photo_picker)
```

## Env
```
API_BASE_URL=https://18.192.98.211/api/v1
USE_HTTP_CERT_PINNING=true
```

## Model Generation
- Ambil skema dari `08_DATA_MODEL_ERD.md` / `docs-qhsse/modules/*.md`.
- Pakai `freezed` + `json_serializable` untuk DTO dari response JSON.

## Auth Flow
1. Login → simpan token di `flutter_secure_storage`.
2. Dio interceptor sisipkan `Authorization: Bearer`.
3. 401 → refresh/re-login → ulangi request (queue).

## Convention UI (sejalan web)
- Warna primary `#2563eb`, navbar `#fdb913`.
- Font: title 16, group 15, sub 13.
- Status badge, severity, priority menonjol; overdue merah.
