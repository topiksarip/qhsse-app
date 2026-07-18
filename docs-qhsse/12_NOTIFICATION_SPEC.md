# Notification Specification

- Tabel: `core_notifications`, `notification_templates`.
- Service: `App\Core\Notifications\NotificationService`.
- Fitur: idempotency key (`add_idempotency_key_to_core_notifications`), read/unread, read-all.
- Endpoint: `core/notifications`, `core/notifications/{id}/read`, `core/notifications/read-all`.
- Permission: `core.notifications.{view,manage}`.
- Channel: in-app (database). Email/Slack opsional via template.
