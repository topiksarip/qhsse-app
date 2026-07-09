# Handoff — Phase 0 Task 0.5 Role, Permission, and Scope

## 1. Status

- Phase: 0 Core Foundation
- Task: 0.5 Role, Permission, and Scope
- Status: Completed
- Project path: `/home/ubuntu/qhsse-app-v3`
- Executor: AI Agent

## 2. Scope Dikerjakan

- Menginstall `spatie/laravel-permission`.
- Publish config dan migration permission package.
- Menambahkan `HasRoles` trait pada `User` model.
- Mendaftarkan middleware alias Spatie di Laravel 12 bootstrap config.
- Membuat permission registry baseline untuk Core Foundation.
- Membuat role-permission map baseline.
- Membuat seeder roles dan permissions.
- Mengupdate DatabaseSeeder agar role/permission ikut seed.
- Memberi default role `Super Admin` pada default seeded user.
- Melindungi route companies/employees/users dengan permission middleware.
- Menambahkan user role assignment baseline di admin user create/edit.
- Menampilkan roles pada user list.
- Menambahkan tests RBAC dan scope baseline.
- Mengupdate IdentityCoreTest agar memakai Super Admin setelah route diproteksi.
- Menjalankan full backend test dan frontend build.
- Mengupdate changelog dan decision log.

## 3. Scope Tidak Dikerjakan

- Belum membuat UI Role Management terpisah.
- Belum membuat Permission Matrix UI.
- Belum membuat site/department/area master.
- Belum menegakkan data scope row-level secara penuh.
- Belum membuat policies per model.
- Belum membuat audit trail untuk permission/role changes.
- Belum membuat role-based navigation hiding; links masih tampil dan backend yang enforce.

## 4. File/Folder Dibuat

- `config/permission.php`
- `database/migrations/2026_07_09_083039_create_permission_tables.php`
- `app/Core/Permissions/CorePermissions.php`
- `database/seeders/RolesAndPermissionsSeeder.php`
- `tests/Feature/Core/RbacCoreTest.php`
- `handoff/PHASE-00-task-0.5-role-permission-scope-HANDOFF.md`

## 5. File/Folder Diubah

- `composer.json`
- `composer.lock`
- `app/Models/User.php`
- `bootstrap/app.php`
- `routes/core.php`
- `database/seeders/DatabaseSeeder.php`
- `app/Http/Requests/Core/UserAdminRequest.php`
- `app/Http/Controllers/Core/UserAdminController.php`
- `resources/js/types/core.ts`
- `resources/js/Pages/Core/Users/Index.tsx`
- `resources/js/Pages/Core/Users/Form.tsx`
- `tests/Feature/Core/IdentityCoreTest.php`
- `docs-qhsse/19_DECISION_LOG.md`
- `docs-qhsse/20_CHANGELOG.md`

## 6. Database/Migration/Model

Spatie permission tables added by published migration:

- `permissions`
- `roles`
- `model_has_permissions`
- `model_has_roles`
- `role_has_permissions`

User model:

- Added `Spatie\Permission\Traits\HasRoles`.

## 7. API/Backend

Core routes are now permission-protected:

Companies:

- `core.companies.view`
- `core.companies.create`
- `core.companies.update`
- `core.companies.deactivate`

Employees:

- `core.employees.view`
- `core.employees.create`
- `core.employees.update`
- `core.employees.deactivate`

Users:

- `core.users.view`
- `core.users.create`
- `core.users.update`
- `core.users.deactivate`

Role management baseline:

- `core.roles.manage`

Scope baseline permissions:

- `core.scope.own`
- `core.scope.department`
- `core.scope.site`
- `core.scope.company`
- `core.scope.all`

## 8. UI/Frontend

User admin UI updated:

- Create/edit user includes role checkboxes.
- User list displays assigned roles.

No separate role management UI yet.

## 9. Permission Ditambahkan

Roles seeded:

- Super Admin
- Admin
- QHSSE Manager
- QHSSE Officer
- Supervisor
- Department Head
- Employee / Reporter
- Contractor
- Auditor
- Top Management

Permissions are defined in:

- `app/Core/Permissions/CorePermissions.php`

## 10. Master Data/Seed Ditambahkan

Seeder added:

- `RolesAndPermissionsSeeder`

DatabaseSeeder now:

- Calls `RolesAndPermissionsSeeder`.
- Assigns default `test@example.com` user the `Super Admin` role.

## 11. Workflow/Status Ditambahkan

No workflow engine status added.

## 12. Notification Ditambahkan

No notification added.

## 13. Report/Export Ditambahkan

No report/export added.

## 14. Test Dijalankan

Targeted:

```bash
php artisan test tests/Feature/Core/RbacCoreTest.php
```

Full verification:

```bash
php artisan test
npm run build
```

## 15. Hasil Test

Targeted RBAC test:

- 5 passed
- 20 assertions

Full backend test:

- 35 passed
- 98 assertions

Frontend build:

- `npm run build` passed
- Vite production build completed successfully

## 16. Known Issues

- Data scope is represented as permissions only; actual row-level filtering is deferred until site/department/area masters exist.
- Role assignment is available on user admin forms, but role CRUD UI is deferred.
- Navigation links are not yet hidden based on permissions.
- Permission changes are not audited yet because audit trail is Task 0.11.

## 17. Deferred Items

- Role management UI.
- Permission matrix UI.
- Permission-based navigation visibility.
- Row-level data scope enforcement.
- Audit trail for role/permission changes.
- Policies for model-level authorization if needed.

## 18. Decision Log Update

Added decisions:

- Use spatie/laravel-permission for RBAC baseline.
- Represent data scope as permissions in Task 0.5.

## 19. Breaking Changes

- Core identity routes now return 403 unless the authenticated user has the required permissions.
- Existing tests/users must seed roles and assign appropriate roles before accessing protected core routes.
- Existing databases must run Spatie permission migration.

## 20. Next Phase Readiness

Ready for Task 0.6 Organization Master.

Before continuing, read:

- `AGENTS.md`
- `docs-qhsse/27_PHASE_0_BUILD_PLAN.md`
- `tasks/todo.md`
- `handoff/PHASE-00-task-0.5-role-permission-scope-HANDOFF.md`

## 21. Rekomendasi Prompt Berikutnya

```text
Lanjutkan Phase 0 Task 0.6 — Organization Master.
Project path: /home/ubuntu/qhsse-app-v3.
Baca AGENTS.md, docs-qhsse/27_PHASE_0_BUILD_PLAN.md, tasks/todo.md, dan handoff/PHASE-00-task-0.5-role-permission-scope-HANDOFF.md.
Kerjakan hanya site, area, department, and position master sesuai Phase 0.
Integrasikan permission dan scope baseline secukupnya, jangan buat general QHSSE master data dulu.
Jalankan test/build, update changelog/decision log jika perlu, lalu buat handoff Task 0.6.
```
