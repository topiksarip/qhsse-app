# Handoff — Phase 0 Task 0.13 Notification Core

## 1. Status

- Phase: 0 — Core Foundation
- Task: 0.13 — Notification Core
- Status: Completed
- Date: 2026-07-09
- Executor: AI Agent

## 2. Scope Dikerjakan

- Implemented core in-app notification table.
- Implemented notification templates table.
- Implemented `CoreNotification` and `NotificationTemplate` models.
- Implemented `NotificationService` for single/multiple recipient delivery.
- Implemented template rendering with `{{key}}` context replacements.
- Implemented mark read, mark unread, and mark all read.
- Implemented notification template seeder.
- Implemented notification center UI.
- Implemented test notification route for foundation verification.
- Added notification permissions, routes, controller, tests, changelog, decision log, and handoff.

## 3. Scope Tidak Dikerjakan

- No real email delivery yet.
- No queue worker integration yet.
- No WhatsApp/Telegram/Teams integration.
- No automatic mention notifications from `CommentService` yet.
- No realtime/broadcast notifications.
- No notification preferences per user.

## 4. File/Folder Dibuat

- `database/migrations/2026_07_09_105000_create_core_notifications_table.php`
- `database/migrations/2026_07_09_105001_create_notification_templates_table.php`
- `app/Models/Core/Notifications/CoreNotification.php`
- `app/Models/Core/Notifications/NotificationTemplate.php`
- `app/Core/Notifications/NotificationService.php`
- `database/seeders/NotificationTemplateSeeder.php`
- `app/Http/Controllers/Core/NotificationController.php`
- `resources/js/Pages/Core/Notifications/Index.tsx`
- `tests/Feature/Core/NotificationCoreTest.php`
- `handoff/PHASE-00-task-0.13-notification-core-HANDOFF.md`

## 5. File/Folder Diubah

- `database/seeders/DatabaseSeeder.php`
- `app/Core/Permissions/CorePermissions.php`
- `routes/core.php`
- `resources/js/Layouts/AuthenticatedLayout.tsx`
- `docs-qhsse/19_DECISION_LOG.md`
- `docs-qhsse/20_CHANGELOG.md`

## 6. Database/Migration/Model

- Table: `core_notifications`
  - `recipient_id`, `actor_id`, `type`, `title`, `message`, `module_name`, `reference_id`, `action_url`, `data`, `read_at`
- Table: `notification_templates`
  - `type`, `title_template`, `message_template`, `channels`, `is_active`
- Models:
  - `App\Models\Core\Notifications\CoreNotification`
  - `App\Models\Core\Notifications\NotificationTemplate`

## 7. API/Backend

- `GET /core/notifications` — list current user's notifications, permission `core.notifications.view`
- `POST /core/notifications/test` — send test notification, permission `core.notifications.manage`
- `PATCH /core/notifications/{notification}/read` — mark own notification read, permission `core.notifications.view`
- `PATCH /core/notifications/{notification}/unread` — mark own notification unread, permission `core.notifications.view`
- `PATCH /core/notifications/read-all` — mark all own notifications read, permission `core.notifications.view`

## 8. UI/Frontend

- `resources/js/Pages/Core/Notifications/Index.tsx`
  - Notification center.
  - Unread count.
  - Send test notification utility.
  - Mark read/unread.
  - Mark all read.
- `resources/js/Layouts/AuthenticatedLayout.tsx`
  - Added Notifications navigation link.

## 9. Permission Ditambahkan

- `core.notifications.view`
- `core.notifications.manage`

## 10. Master Data/Seed Ditambahkan

- `NotificationTemplateSeeder` adds templates:
  - `core.test`
  - `comment.mentioned`
  - `workflow.transitioned`

## 11. Workflow/Status Ditambahkan

- No workflow/status definitions added.

## 12. Notification Ditambahkan

- In-app notification storage and UI.
- Template-based notification rendering.
- Email-ready channel metadata through `notification_templates.channels`, but email sending is not active yet.

## 13. Report/Export Ditambahkan

- No report/export added.

## 14. Test Dijalankan

- `php artisan test tests/Feature/Core/NotificationCoreTest.php`
- `php artisan test`
- `npm run build`

## 15. Hasil Test

- Passed: `php artisan test tests/Feature/Core/NotificationCoreTest.php` — 5 passed, 16 assertions.
- Passed: `php artisan test` — 73 passed, 222 assertions.
- Passed: `npm run build`.
- Failed: none.
- Not tested: browser manual notification center.

## 16. Known Issues

- Notification center only shows current user's notifications.
- `core.notifications.manage` can send a test notification to any user and should remain admin-only.
- Email channel is represented as template metadata only; no mail job is sent.

## 17. Deferred Items

- Email delivery implementation.
- Queue-based notification sending.
- Mention notification integration from comments.
- Workflow notification integration.
- User notification preferences.
- Realtime notification badge.

## 18. Decision Log Update

- Added decision: implement custom in-app notifications first and keep email as metadata-ready until queue/mail policy is finalized.

## 19. Breaking Changes

- None.

## 20. Next Phase Readiness

- Ready for Phase 0 Task 0.14 — Search, Filter, Pagination, Export Base.
- Reason: Notification Core is implemented, verified, and available for future modules.

## 21. Rekomendasi Prompt Berikutnya

```text
Lanjutkan Phase 0 Task 0.14 — Search, Filter, Pagination, Export Base.
Project path: /home/ubuntu/qhsse-app-v3.
Baca AGENTS.md, docs-qhsse/27_PHASE_0_BUILD_PLAN.md, dan handoff/PHASE-00-task-0.13-notification-core-HANDOFF.md.
Kerjakan hanya search/filter/pagination/export base: shared query/filter pattern where safe, CSV export for at least one master list, export permission, tests, changelog, decision log if needed, and handoff.
Jangan kerjakan dashboard atau modul bisnis.
```
