# Handoff — Phase 0 Task 0.4 User, Employee, Company Core

## 1. Status

- Phase: 0 Core Foundation
- Task: 0.4 User, Employee, Company Core
- Status: Completed
- Project path: `/home/ubuntu/qhsse-app-v3`
- Executor: AI Agent

## 2. Scope Dikerjakan

- Menambahkan company/contractor/vendor core model.
- Menambahkan employee core model.
- Menambahkan link user ke company dan employee.
- Menambahkan admin CRUD baseline untuk companies.
- Menambahkan admin CRUD baseline untuk employees.
- Menambahkan admin CRUD baseline untuk users.
- Menambahkan activate/deactivate baseline via `is_active` pada company, employee, user.
- Menambahkan Inertia pages minimal untuk list/form company, employee, user.
- Menambahkan navigation link untuk Companies, Employees, Users.
- Menambahkan tests untuk company creation, employee creation, user linkage, active state update.
- Menjalankan targeted test, full test, dan frontend build.
- Mengupdate changelog dan decision log.

## 3. Scope Tidak Dikerjakan

- Belum menambahkan spatie/laravel-permission.
- Belum membuat role/permission matrix runtime.
- Belum membuat policy/authorization granular.
- Belum membuat site/area/department/position master penuh.
- Belum membuat audit trail.
- Belum membuat notification.
- Belum membuat delete permanen; destroy melakukan inactivation.
- Belum membuat advanced admin UI polish.

## 4. File/Folder Dibuat

- `app/Models/Core/MasterData/Company.php`
- `app/Models/Core/Users/Employee.php`
- `database/migrations/2026_07_09_081426_create_companies_table.php`
- `database/migrations/2026_07_09_081426_create_employees_table.php`
- `database/migrations/2026_07_09_081426_add_employee_and_company_to_users_table.php`
- `database/factories/Core/MasterData/CompanyFactory.php`
- `database/factories/Core/Users/EmployeeFactory.php`
- `app/Http/Requests/Core/CompanyRequest.php`
- `app/Http/Requests/Core/EmployeeRequest.php`
- `app/Http/Requests/Core/UserAdminRequest.php`
- `app/Http/Controllers/Core/CompanyController.php`
- `app/Http/Controllers/Core/EmployeeController.php`
- `app/Http/Controllers/Core/UserAdminController.php`
- `resources/js/types/core.ts`
- `resources/js/Components/Qhsse/Pagination.tsx`
- `resources/js/Components/Qhsse/StatusBadge.tsx`
- `resources/js/Pages/Core/Companies/Index.tsx`
- `resources/js/Pages/Core/Companies/Form.tsx`
- `resources/js/Pages/Core/Employees/Index.tsx`
- `resources/js/Pages/Core/Employees/Form.tsx`
- `resources/js/Pages/Core/Users/Index.tsx`
- `resources/js/Pages/Core/Users/Form.tsx`
- `tests/Feature/Core/IdentityCoreTest.php`
- `handoff/PHASE-00-task-0.4-user-employee-company-HANDOFF.md`

## 5. File/Folder Diubah

- `app/Models/User.php`
- `database/factories/UserFactory.php`
- `routes/core.php`
- `resources/js/Layouts/AuthenticatedLayout.tsx`
- `docs-qhsse/19_DECISION_LOG.md`
- `docs-qhsse/20_CHANGELOG.md`

## 6. Database/Migration/Model

Tables added:

- `companies`
- `employees`

User table links added:

- `users.company_id` nullable FK
- `users.employee_id` nullable FK

Company fields:

- `code`
- `name`
- `type`: internal, contractor, vendor
- `email`
- `phone`
- `address`
- `is_active`

Employee fields:

- `company_id`
- `employee_no`
- `name`
- `email`
- `phone`
- `department`
- `position`
- `is_active`

Relationships:

- Company has many employees.
- Company has many users.
- Employee belongs to company.
- Employee has one user.
- User belongs to company.
- User belongs to employee.

## 7. API/Backend

Auth-protected core resource routes added:

- `core.companies.*`
- `core.employees.*`
- `core.users.*`

Controllers:

- CompanyController
- EmployeeController
- UserAdminController

Validation requests:

- CompanyRequest
- EmployeeRequest
- UserAdminRequest

Destroy behavior:

- Soft operational delete by setting `is_active = false`.

## 8. UI/Frontend

Inertia pages added:

- Companies index/form
- Employees index/form
- Users index/form

Shared components added:

- Pagination
- StatusBadge

Navigation updated:

- Dashboard
- Companies
- Employees
- Users

## 9. Permission Ditambahkan

No RBAC permission yet.

Current protection:

- Routes are protected by `auth` and `verified` middleware.

RBAC/spatie permission remains Task 0.5.

## 10. Master Data/Seed Ditambahkan

No seeders added.

Factories added:

- CompanyFactory
- EmployeeFactory
- UserFactory states for company/employee linkage

## 11. Workflow/Status Ditambahkan

No workflow engine status added.

Operational active/inactive flags added for company, employee, user.

## 12. Notification Ditambahkan

No notification added.

## 13. Report/Export Ditambahkan

No report/export added.

## 14. Test Dijalankan

Targeted:

```bash
php artisan test tests/Feature/Core/IdentityCoreTest.php
```

Frontend:

```bash
npm run build
```

Full backend:

```bash
php artisan test
```

## 15. Hasil Test

Targeted identity core test:

- 4 passed
- 14 assertions

Frontend build:

- `npm run build` passed
- Vite production build completed successfully

Full backend test:

- 30 passed
- 78 assertions

## 16. Known Issues

- User admin pages are auth-protected but not RBAC-protected yet.
- Department and position are temporary strings until organization master task adds structured tables.
- Company type is validated as `internal`, `contractor`, or `vendor` but not yet moved to configurable master data.
- There is no audit trail yet for activation/deactivation.
- No import/export yet for identity records.

## 17. Deferred Items

- RBAC/spatie permission for user management.
- Company/employee audit trail.
- Structured department/position master.
- Site/area linkage.
- Bulk import/export.
- Better admin UI polish.

## 18. Decision Log Update

Added decisions:

- Use nullable company/employee links on users.
- Use string placeholders for employee department/position in Task 0.4.

## 19. Breaking Changes

- Existing databases need to run the new migrations.
- Tests now expect company/employee/user linkage migrations to exist.

## 20. Next Phase Readiness

Ready for Task 0.5 Role, Permission, and Scope.

Before continuing, read:

- `AGENTS.md`
- `docs-qhsse/27_PHASE_0_BUILD_PLAN.md`
- `tasks/todo.md`
- `handoff/PHASE-00-task-0.4-user-employee-company-HANDOFF.md`

## 21. Rekomendasi Prompt Berikutnya

```text
Lanjutkan Phase 0 Task 0.5 — Role, Permission, and Scope.
Project path: /home/ubuntu/qhsse-app-v3.
Baca AGENTS.md, docs-qhsse/27_PHASE_0_BUILD_PLAN.md, tasks/todo.md, dan handoff/PHASE-00-task-0.4-user-employee-company-HANDOFF.md.
Kerjakan hanya RBAC/spatie permission dan data scope baseline; jangan buat organization master atau QHSSE master data dulu.
Jalankan test/build, update changelog/decision log jika perlu, lalu buat handoff Task 0.5.
```
