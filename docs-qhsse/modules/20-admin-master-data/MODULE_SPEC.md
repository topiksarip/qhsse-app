# Module Spec — Admin & Master Data Hardening

> Module 20 — Final polish for admin master data. Tidak membuat tabel baru, melainkan melengkapi admin UI untuk semua core master data yang sudah ada di Phase 0.

## 1. Tujuan Modul

Memberikan UI admin lengkap untuk mengelola semua master data QHSSE: sites, areas, departments, positions, companies, employees, users, roles, permissions, severities, priorities, statuses, categories, risk matrix levels, numbering formats, workflow definitions, notification templates. Juga menyediakan system settings, import/export bulk, dan audit log viewer.

## 2. Dependency

Core Foundation (Phase 0). Tidak ada modul bisnis baru.

## 3. User Roles

| Role | Akses |
|---|---|
| Admin / Super Admin | Full CRUD semua master data |
| QHSSE Manager | View semua master data, limited update (severities, priorities, categories) |
| Lainnya | Tidak ada akses admin |

## 4. Fitur

1. Dashboard admin (statistik: total users, employees, sites, dll)
2. Bulk import CSV untuk employees, sites, departments
3. Bulk export CSV semua master data
4. Role & permission manager (assign/revoke)
5. Numbering format viewer (lihat format aktif + sample)
6. Workflow definition viewer (lihat states + transitions)
7. Notification template manager (CRUD templates)
8. Audit log viewer (filter by module, user, date, event)
9. System settings (app name, locale, timezone, maintenance mode)
10. User activation/deactivation bulk

## 5. Permission Keys

Tidak ada permission baru — semua menggunakan `core.*` permissions yang sudah ada:
- `core.sites.{view,create,update,deactivate}`
- `core.areas.{view,create,update,deactivate}`
- `core.departments.{view,create,update,deactivate}`
- `core.positions.{view,create,update,deactivate}`
- `core.companies.{view,create,update,deactivate}`
- `core.employees.{view,create,update,deactivate}`
- `core.users.{view,create,update,deactivate}`
- `core.severities.{view,create,update,deactivate}`
- `core.priorities.{view,create,update,deactivate}`
- `core.statuses.{view,create,update,deactivate}`
- `core.categories.{view,create,update,deactivate}`
- `core.risk-matrix.{view,create,update,deactivate}`
- `core.numbering.{view,create,update,generate}`
- `core.workflow.{view,manage}`
- `core.audit.view`
- `core.notifications.{view,manage}`
- `core.roles.manage`
- `core.export.csv`

## 6. Business Rules

1. Hanya Admin/Super Admin yang bisa CRUD master data
2. Deactivate (soft delete) tidak hapus permanen — data tetap untuk audit
3. Bulk import wajib validasi semua row sebelum commit
4. Role assignment mencatat audit trail
5. System settings perlu restart queue worker untuk apply changes

## 7. UI Pages

### Admin Dashboard
- Card: Total Users, Active Users, Total Employees, Total Sites, Total Companies
- Recent activity log (last 10 audit entries)
- Quick links ke setiap master data section

### Existing Pages (already in Phase 0)
- Sites Index/Form — `core.sites.*`
- Areas Index/Form — `core.areas.*`
- Departments Index/Form — `core.departments.*`
- Positions Index/Form — `core.positions.*`
- Companies Index/Form — `core.companies.*`
- Employees Index/Form — `core.employees.*`
- Users Index/Form — `core.users.*`
- Severities Index/Form — `core.severities.*`
- Priorities Index/Form — `core.priorities.*`
- Statuses Index/Form — `core.statuses.*`
- Categories Index/Form — `core.categories.*`
- Risk Matrix Index/Form — `core.risk-matrix.*`
- Numbering Index — `core.numbering.view`
- Workflow Index — `core.workflow.view`
- Audit Logs Index — `core.audit.view`
- Notifications Index — `core.notifications.view`

### New Pages (Module 20 additions)
- Admin Dashboard — `GET /admin`
- Bulk Import — `GET /admin/import` (upload CSV)
- System Settings — `GET /admin/settings`
- Role Manager — `GET /admin/roles` (assign permissions to roles)

## 8. API

| Method | URI | Permission | Description |
|---|---|---|---|
| GET | `/admin` | `core.sites.view` | Admin dashboard |
| GET | `/admin/import` | `core.employees.create` | Bulk import page |
| POST | `/admin/import/employees` | `core.employees.create` | Import employees CSV |
| POST | `/admin/import/sites` | `core.sites.create` | Import sites CSV |
| GET | `/admin/settings` | `core.roles.manage` | System settings |
| PUT | `/admin/settings` | `core.roles.manage` | Update settings |
| GET | `/admin/roles` | `core.roles.manage` | Role manager |
| PUT | `/admin/roles/{role}` | `core.roles.manage` | Update role permissions |

## 9. Dashboard Metrics

- Total users by role (pie chart)
- Active vs inactive users
- Employees by site
- Master data completeness (which sites have areas, departments, positions)

## 10. Export Specification

CSV export for each master data type with appropriate columns.

## 11. Acceptance Criteria

1. Admin dashboard shows correct counts
2. Bulk import validates before commit
3. Role manager can assign/revoke permissions
4. System settings page works
5. All existing master data pages still functional
6. Audit log viewer filters work
7. Permission enforced on all admin routes

## 12. Open Questions

- Should non-admin roles have view-only access to some admin pages? → Default: QHSSE Manager can view all
- Bulk import limit? → Default: 1000 rows per import
- Should settings be in DB or config file? → Default: DB (key-value table)
