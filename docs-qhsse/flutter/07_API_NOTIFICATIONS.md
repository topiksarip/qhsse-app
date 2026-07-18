# Notifications

## Sumber
Tabel `core_notifications` + `notification_templates` (diisi via `App\Core\Notifications\NotificationService`).

## Endpoint
- `GET /api/v1/core/notifications` → list (paginate), field `read_at`.
- `POST /api/v1/core/notifications/{id}/read` → tandai baca.
- `POST /api/v1/core/notifications/read-all` → tandai semua.
- `GET /api/v1/core/notifications/unread-count` → badge count.

## Push (FCM)
- Backend dapat mengirim FCM saat `NotificationService` dipanggil (tambahkan `fcm_token` per user/device di tabel user_meta).
- Flutter: `firebase_messaging` → onMessage → refresh list notif + update badge.

## Idempotensi
- `core_notifications` punya `idempotency_key` — backend mencegah notif duplikat. Flutter cukup poll/stream, tidak perlu dedupe agresif.

## Scope
- Notifikasi mengikuti scope user (own/department/site/company/all) — backend filter otomatis.
