# API Contract — Admin & Master Data Hardening

## Routes

All routes behind `auth`, `verified`, permission middleware.

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/admin` | `AdminDashboardController@index` | `admin.dashboard` | `core.sites.view` | Admin dashboard |
| GET | `/admin/import` | `BulkImportController@create` | `admin.import.create` | `core.employees.create` | Import page |
| POST | `/admin/import/employees` | `BulkImportController@importEmployees` | `admin.import.employees` | `core.employees.create` | Import employees CSV |
| POST | `/admin/import/sites` | `BulkImportController@importSites` | `admin.import.sites` | `core.sites.create` | Import sites CSV |
| GET | `/admin/settings` | `SystemSettingsController@index` | `admin.settings.index` | `core.roles.manage` | Settings page |
| PUT | `/admin/settings` | `SystemSettingsController@update` | `admin.settings.update` | `core.roles.manage` | Update settings |
| GET | `/admin/roles` | `RoleManagerController@index` | `admin.roles.index` | `core.roles.manage` | Role list |
| GET | `/admin/roles/{role}` | `RoleManagerController@edit` | `admin.roles.edit` | `core.roles.manage` | Edit role permissions |
| PUT | `/admin/roles/{role}` | `RoleManagerController@update` | `admin.roles.update` | `core.roles.manage` | Save role permissions |

## Request Payloads

### POST `/admin/import/employees`
```multipart
file: CSV file
columns required: name, email, phone, company_code
```

### PUT `/admin/settings`
```json
{
  "app.locale": "id",
  "app.timezone": "Asia/Jakarta",
  "app.maintenance": false
}
```

### PUT `/admin/roles/{role}`
```json
{
  "permissions": ["core.sites.view", "incident.reports.view", "incident.reports.create"]
}
```

## Inertia Response Props

### Admin Dashboard
```typescript
{
  stats: { users: number, employees: number, sites: number, companies: number },
  recentActivity: AuditLog[],
}
```

### Role Manager Edit
```typescript
{
  role: { id, name },
  allPermissions: string[],
  rolePermissions: string[],
}
```
