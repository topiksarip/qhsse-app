# Handoff — Phase 0 Task 0.12 Comments and Activity Log

## 1. Status

- Phase: 0 — Core Foundation
- Task: 0.12 — Comments and Activity Log
- Status: Completed
- Date: 2026-07-09
- Executor: AI Agent

## 2. Scope Dikerjakan

- Implemented generic comments using `module_name + reference_id`.
- Implemented comment mention extraction from `@name` syntax.
- Implemented comment soft-delete metadata.
- Implemented generic activity log using `module_name + reference_id`.
- Implemented `CommentService` and `ActivityService`.
- Integrated workflow transitions with activity timeline event `workflow.transitioned`.
- Implemented minimal comments/activity UI.
- Added comments/activity permissions, routes, controller, request, tests, changelog, decision log, and handoff.

## 3. Scope Tidak Dikerjakan

- No real mention notification yet; notification core is Task 0.13.
- No rich text editor.
- No file attachments inside comments.
- No edit comment flow.
- No reusable embedded React component for business module detail pages yet.
- No per-module row-level comment visibility rules beyond route permissions.

## 4. File/Folder Dibuat

- `database/migrations/2026_07_09_104000_create_comments_table.php`
- `database/migrations/2026_07_09_104001_create_activity_logs_table.php`
- `app/Models/Core/Comments/Comment.php`
- `app/Models/Core/Activity/ActivityLog.php`
- `app/Core/Comments/CommentService.php`
- `app/Core/Activity/ActivityService.php`
- `app/Http/Controllers/Core/CommentActivityController.php`
- `app/Http/Requests/Core/CommentRequest.php`
- `resources/js/Pages/Core/CommentsActivity/Index.tsx`
- `tests/Feature/Core/CommentsActivityTest.php`
- `handoff/PHASE-00-task-0.12-comments-activity-HANDOFF.md`

## 5. File/Folder Diubah

- `app/Core/Workflow/WorkflowService.php`
- `app/Core/Permissions/CorePermissions.php`
- `routes/core.php`
- `resources/js/Layouts/AuthenticatedLayout.tsx`
- `docs-qhsse/19_DECISION_LOG.md`
- `docs-qhsse/20_CHANGELOG.md`

## 6. Database/Migration/Model

- Table: `comments`
  - `module_name`, `reference_id`, `parent_id`, `author_id`, `body`, `mentions`, `is_internal`, `edited_at`, `deleted_at`, `deleted_by`
- Table: `activity_logs`
  - `module_name`, `reference_id`, `event`, `description`, `actor_id`, `actor_name`, `properties`
- Models:
  - `App\Models\Core\Comments\Comment`
  - `App\Models\Core\Activity\ActivityLog`

## 7. API/Backend

- `GET /core/comments-activity` — list comments and activity for optional module/reference filter, permission `core.comments.view`
- `POST /core/comments` — create comment, permission `core.comments.create`
- `DELETE /core/comments/{comment}` — soft-delete comment, permission `core.comments.delete`

## 8. UI/Frontend

- `resources/js/Pages/Core/CommentsActivity/Index.tsx`
  - Filter by module/reference.
  - Add comment.
  - Show comments.
  - Show activity timeline.
- `resources/js/Layouts/AuthenticatedLayout.tsx`
  - Added Comments navigation link.

## 9. Permission Ditambahkan

- `core.comments.view`
- `core.comments.create`
- `core.comments.delete`
- `core.activity.view`

## 10. Master Data/Seed Ditambahkan

- No master data added.
- Role/permission seeder includes comments/activity permissions via `CorePermissions`.

## 11. Workflow/Status Ditambahkan

- No workflow/status definitions added.
- `WorkflowService` now emits `workflow.transitioned` activity log entries.

## 12. Notification Ditambahkan

- No notifications added.
- Mentions are stored as extracted text handles in `comments.mentions`; notification is deferred to Task 0.13.

## 13. Report/Export Ditambahkan

- No report/export added.

## 14. Test Dijalankan

- `php artisan test tests/Feature/Core/CommentsActivityTest.php`
- `php artisan test`
- `npm run build`

## 15. Hasil Test

- Passed: `php artisan test tests/Feature/Core/CommentsActivityTest.php` — 5 passed, 16 assertions.
- Passed: `php artisan test` — 68 passed, 206 assertions.
- Passed: `npm run build`.
- Failed: none.
- Not tested: browser manual comments/activity usage.

## 16. Known Issues

- Comments UI is a generic foundation page, not yet embedded in module detail pages.
- Mentions are extracted as strings only; they are not resolved to user IDs yet.
- Delete is soft-delete metadata only.

## 17. Deferred Items

- Mention-to-user resolution.
- Mention notifications.
- Embedded comments/activity component for module detail pages.
- Comment edit flow.
- Rich text/markdown support.
- Comment attachments.

## 18. Decision Log Update

- Added decision: comments and activity use shared `module_name + reference_id` timeline primitives.

## 19. Breaking Changes

- None.

## 20. Next Phase Readiness

- Ready for Phase 0 Task 0.13 — Notification Core.
- Reason: Comments and Activity Log are implemented, verified, and available for future modules.

## 21. Rekomendasi Prompt Berikutnya

```text
Lanjutkan Phase 0 Task 0.13 — Notification Core.
Project path: /home/ubuntu/qhsse-app-v3.
Baca AGENTS.md, docs-qhsse/27_PHASE_0_BUILD_PLAN.md, dan handoff/PHASE-00-task-0.12-comments-activity-HANDOFF.md.
Kerjakan hanya notification core: in-app notifications, mark read/unread, notification service/event interface, optional email-ready structure, UI, tests, changelog, decision log if needed, and handoff.
Jangan kerjakan export, dashboard, atau modul bisnis.
```
