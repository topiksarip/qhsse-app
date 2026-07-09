# Handoff — Phase 0 Task 0.3 Authentication

## 1. Status

- Phase: 0 Core Foundation
- Task: 0.3 Authentication
- Status: Completed
- Project path: `/home/ubuntu/qhsse-app-v3`
- Executor: AI Agent

## 2. Scope Dikerjakan

- Menginspeksi auth default Laravel Breeze.
- Menambahkan status aktif/inaktif pada user melalui `users.is_active`.
- Menambahkan cast dan fillable `is_active` pada model User.
- Menambahkan default factory user aktif.
- Menambahkan factory state `inactive()`.
- Mengubah login flow agar inactive user tidak bisa authenticate.
- Menambahkan test inactive user cannot authenticate.
- Menjalankan targeted auth test.
- Menjalankan full backend test suite.
- Menjalankan frontend production build.
- Mengupdate changelog.
- Mengupdate decision log.

## 3. Scope Tidak Dikerjakan

- Belum membuat UI/admin untuk activate/deactivate user.
- Belum membuat user management CRUD.
- Belum membuat employee/company model.
- Belum menginstall spatie/laravel-permission.
- Belum membuat role/permission/scope.
- Belum mengubah registration policy.
- Belum membuat SSO.

## 4. File/Folder Dibuat

- `database/migrations/2026_07_09_080828_add_is_active_to_users_table.php`
- `handoff/PHASE-00-task-0.3-authentication-HANDOFF.md`

## 5. File/Folder Diubah

- `app/Http/Requests/Auth/LoginRequest.php`
- `app/Models/User.php`
- `database/factories/UserFactory.php`
- `tests/Feature/Auth/AuthenticationTest.php`
- `docs-qhsse/19_DECISION_LOG.md`
- `docs-qhsse/20_CHANGELOG.md`

## 6. Database/Migration/Model

Migration added:

- Table: `users`
- Column: `is_active`
- Type: boolean
- Default: true
- Index: yes

Model changes:

- `User::$fillable` includes `is_active`.
- `User::casts()` casts `is_active` to boolean.

Factory changes:

- Default `is_active` is true.
- Added `inactive()` state.

## 7. API/Backend

Login flow changed in:

- `app/Http/Requests/Auth/LoginRequest.php`

Behavior:

- Unknown email or invalid password still returns `auth.failed`.
- Valid credentials for inactive user return validation error:
  - `This account is inactive. Please contact your administrator.`
- Active user with valid credentials logs in normally.

## 8. UI/Frontend

No custom frontend UI added.

The existing Breeze login page displays the validation error through existing form error handling.

## 9. Permission Ditambahkan

No RBAC permission added yet.

## 10. Master Data/Seed Ditambahkan

No QHSSE master data added.

## 11. Workflow/Status Ditambahkan

No workflow engine status added.

User active/inactive is an account control flag, not a workflow status.

## 12. Notification Ditambahkan

No notification added.

## 13. Report/Export Ditambahkan

No report/export added.

## 14. Test Dijalankan

Targeted:

```bash
php artisan test tests/Feature/Auth/AuthenticationTest.php
```

Full verification:

```bash
php artisan test
npm run build
```

## 15. Hasil Test

Targeted auth test:

- 5 passed
- 11 assertions

Full backend test:

- 26 passed
- 64 assertions

Frontend build:

- `npm run build` passed
- Vite production build completed successfully

## 16. Known Issues

- Login now manually retrieves user and checks password before account status. This preserves invalid-password behavior and adds inactive-account blocking.
- UI/admin control for `is_active` is pending Task 0.4 User, Employee, Company Core.
- Registration still creates active users by default through factory/model behavior and existing Breeze registration. Production registration policy should be revisited when user management is built.

## 17. Deferred Items

- Admin activate/deactivate UI.
- Audit trail for user activation/deactivation.
- Role-based permission for deactivation.
- Optional localization for inactive account error message.
- Optional event/log for inactive login attempt.

## 18. Decision Log Update

Added decision:

- Add `users.is_active` and block inactive login.

## 19. Breaking Changes

- Existing databases need to run the new migration.
- Authentication code now depends on `users.is_active` existing.

## 20. Next Phase Readiness

Ready for Task 0.4 User, Employee, Company Core.

Before continuing, read:

- `AGENTS.md`
- `docs-qhsse/27_PHASE_0_BUILD_PLAN.md`
- `tasks/todo.md`
- `handoff/PHASE-00-task-0.3-authentication-HANDOFF.md`

## 21. Rekomendasi Prompt Berikutnya

```text
Lanjutkan Phase 0 Task 0.4 — User, Employee, Company Core.
Project path: /home/ubuntu/qhsse-app-v3.
Baca AGENTS.md, docs-qhsse/27_PHASE_0_BUILD_PLAN.md, tasks/todo.md, dan handoff/PHASE-00-task-0.3-authentication-HANDOFF.md.
Kerjakan hanya user/employee/company core sesuai Phase 0; jangan buat RBAC/spatie permission atau master data umum dulu.
Jalankan test/build, update changelog/decision log jika perlu, lalu buat handoff Task 0.4.
```
