export type MenuItem = {
    label: string;
    routeName: string;
    active: string;
    permission?: string;
};

export type MenuGroup = {
    label: string;
    items: MenuItem[];
};

export const menuGroups: MenuGroup[] = [
    {
        label: 'Core & Master',
        items: [
            { label: 'Dashboard', routeName: 'dashboard', active: 'dashboard' },
            { label: 'Pencarian', routeName: 'search.index', active: 'search.*' },
            { label: 'Sites', routeName: 'core.sites.index', active: 'core.sites.*', permission: 'core.sites.view' },
            { label: 'Departments', routeName: 'core.departments.index', active: 'core.departments.*', permission: 'core.departments.view' },
            { label: 'Areas', routeName: 'core.areas.index', active: 'core.areas.*', permission: 'core.areas.view' },
            { label: 'Positions', routeName: 'core.positions.index', active: 'core.positions.*', permission: 'core.positions.view' },
            { label: 'Files', routeName: 'core.files.index', active: 'core.files.*', permission: 'core.files.view' },
            { label: 'Notifications', routeName: 'core.notifications.index', active: 'core.notifications.*', permission: 'core.notifications.view' },
            { label: 'Severities', routeName: 'core.severities.index', active: 'core.severities.*', permission: 'core.severities.view' },
            { label: 'Priorities', routeName: 'core.priorities.index', active: 'core.priorities.*', permission: 'core.priorities.view' },
            { label: 'Statuses', routeName: 'core.statuses.index', active: 'core.statuses.*', permission: 'core.statuses.view' },
            { label: 'Categories', routeName: 'core.categories.index', active: 'core.categories.*', permission: 'core.categories.view' },
            { label: 'Risk Matrix', routeName: 'core.risk-matrix.index', active: 'core.risk-matrix.*', permission: 'core.risk-matrix.view' },
        ],
    },
    {
        label: 'QHSSE Modules',
        items: [
            { label: 'Laporan Insiden', routeName: 'incident.reports.index', active: 'incident.reports.*', permission: 'incident.reports.view' },
            { label: 'Investigasi & RCA', routeName: 'investigation.reports.index', active: 'investigation.reports.*', permission: 'investigation.reports.view' },
            { label: 'CAPA / Action', routeName: 'capa.actions.index', active: 'capa.actions.*', permission: 'capa.actions.view' },
            { label: 'Inspeksi', routeName: 'inspection.checklists.index', active: 'inspection.checklists.*', permission: 'inspection.checklists.view' },
            { label: 'Template Inspeksi', routeName: 'inspection.templates.index', active: 'inspection.templates.*', permission: 'inspection.checklists.view' },
            { label: 'Document Control', routeName: 'document.control.index', active: 'document.control.*', permission: 'document.control.view' },
            { label: 'Izin Kerja', routeName: 'permit.work.index', active: 'permit.work.*', permission: 'permit.work.view' },
            { label: 'Catatan Lingkungan', routeName: 'environment.records.index', active: 'environment.records.*', permission: 'environment.records.view' },
            { label: 'Insiden Keamanan', routeName: 'security.incidents.index', active: 'security.incidents.*', permission: 'security.incidents.view' },
            { label: 'Log Pengunjung', routeName: 'security.visitors.index', active: 'security.visitors.*', permission: 'security.visitor.view' },
            { label: 'Patroli Keamanan', routeName: 'security.patrols.index', active: 'security.patrols.*', permission: 'security.patrols.view' },
            { label: 'NCR (Non-Conformance)', routeName: 'quality.ncrs.index', active: 'quality.ncrs.*', permission: 'quality.ncrs.view' },
            { label: 'Complaint Customer', routeName: 'quality.complaints.index', active: 'quality.complaints.*', permission: 'quality.complaints.view' },
            { label: 'Risk Register', routeName: 'risk.registers.index', active: 'risk.registers.*', permission: 'risk.registers.view' },
            { label: 'Legal & Compliance', routeName: 'legal.registers.index', active: 'legal.registers.*', permission: 'legal.register.view' },
        ],
    },
    {
        label: 'Operasional & Support',
        items: [
            { label: 'Audit Management', routeName: 'audits.index', active: 'audits.*', permission: 'audit.management.view' },
            { label: 'Program Pelatihan', routeName: 'training.programs.index', active: 'training.programs.*', permission: 'training.programs.view' },
            { label: 'Record Pelatihan', routeName: 'training.records.index', active: 'training.records.*', permission: 'training.records.view' },
            { label: 'Matriks Kompetensi', routeName: 'training.matrix.index', active: 'training.matrix.*', permission: 'training.records.view' },
            { label: 'Rencana Darurat', routeName: 'emergency.plans.index', active: 'emergency.plans.*', permission: 'emergency.plans.view' },
            { label: 'Latihan Darurat', routeName: 'emergency.drills.index', active: 'emergency.drills.*', permission: 'emergency.drills.view' },
            { label: 'Kontak Darurat', routeName: 'emergency.contacts.index', active: 'emergency.contacts.*', permission: 'emergency.contacts.view' },
            { label: 'Contractor Management', routeName: 'contractors.index', active: 'contractors.*', permission: 'contractor.management.view' },
            { label: 'Asset & Equipment Safety', routeName: 'assets.index', active: 'assets.*', permission: 'asset.management.view' },
            { label: 'APD / PPE', routeName: 'apd.catalogs.index', active: 'apd.*', permission: 'apd.view' },
            { label: 'Communication & Campaign', routeName: 'campaigns.index', active: 'campaigns.*', permission: 'communication.campaigns.view' },
            { label: 'Report Templates', routeName: 'report-templates.index', active: 'report-templates.*', permission: 'reporting.templates.view' },
            { label: 'Saved Reports', routeName: 'saved-reports.index', active: 'saved-reports.*', permission: 'reporting.reports.view' },
        ],
    },
    {
        label: 'System Admin',
        items: [
            { label: 'Admin Dashboard', routeName: 'admin.dashboard', active: 'admin.dashboard', permission: 'core.sites.view' },
            { label: 'Bulk Import', routeName: 'admin.import.create', active: 'admin.import.*', permission: 'core.employees.create' },
            { label: 'Companies', routeName: 'core.companies.index', active: 'core.companies.*', permission: 'core.companies.view' },
            { label: 'Employees', routeName: 'core.employees.index', active: 'core.employees.*', permission: 'core.employees.view' },
            { label: 'Users', routeName: 'core.users.index', active: 'core.users.*', permission: 'core.users.view' },
            { label: 'Role & Permission', routeName: 'core.roles.index', active: 'core.roles.*', permission: 'core.roles.manage' },
            { label: 'Numbering', routeName: 'core.numbering.index', active: 'core.numbering.*', permission: 'core.numbering.view' },
            { label: 'Workflow', routeName: 'core.workflow.index', active: 'core.workflow.*', permission: 'core.workflow.view' },
            { label: 'Audit Logs', routeName: 'core.audit-logs.index', active: 'core.audit-logs.*', permission: 'core.audit.view' },
            { label: 'Comments', routeName: 'core.comments-activity.index', active: 'core.comments-activity.*', permission: 'core.comments.view' },
        ],
    },
];
