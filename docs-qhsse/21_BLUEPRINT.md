# Architecture Blueprint

## Pola Umum
- App key: `module_name` + `reference_id` untuk file, comment, activity, workflow, audit.
- Vertical slice: migration → model → request → controller → route → Inertia page → component.
- Semua create punya delete; permission CRUD lengkap; SoftDeletes pada entity utama.

## Layer
1. **Core** (`app/Core`): platform services (auth, RBAC, master data, file, numbering, workflow, audit, comment, notification, dashboard, export, scope).
2. **Modules** (`app/Modules/{Module}`): controllers & logic tiap modul bisnis.
3. **Models** (`app/Models/Modules/{Module}` + `app/Models/Core`): Eloquent.
4. **Frontend** (`resources/js/Pages/Modules/{Module}`, `resources/js/Components`): Inertia+React.

## Core Services

- **Authentication & UserAdmin**: Core\UserAdminController, Auth (session), inactive-user blocking
- **RBAC**: CorePermissions (Spatie laravel-permission), roleMap() seeder idempoten
- **Organization Master**: Site / Area / Department / Position / Company / Employee
- **Master Data**: Severity / Priority / Status / Category / RiskMatrixLevel
- **Private File Service**: ManagedFileService + FileReference (disk 'local', fail-closed ParentAuthorizationRegistry)
- **Numbering Service**: NumberingService + numbering_formats / counters / generated_numbers
- **Workflow Core**: WorkflowService + definitions / transitions / instances / histories
- **Audit Trail**: AuditService + audit_logs
- **Comments & Activity**: CommentService + Comments/ActivityLog (shared across modules)
- **Notification Core**: NotificationService + core_notifications / templates; idempotency key
- **Search/Filter/Pagination/Export**: ListQuery + CsvExporter + ScopeService (per-site/dept scoping)
- **Dashboard Shell**: DashboardController (KPI KPI cards per module)
