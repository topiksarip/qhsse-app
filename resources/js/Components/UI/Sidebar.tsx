import ApplicationLogoImage from '@/Components/ApplicationLogoImage';
import { Link, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import { useEffect, useMemo, useState } from 'react';

type MenuItem = { label: string; routeName: string; active: string; permission?: string };
type MenuGroup = { label: string; items: MenuItem[] };

const menuGroups: MenuGroup[] = [
    {
        label: 'Core & Master',
        items: [
            { label: 'Dashboard', routeName: 'dashboard', active: 'dashboard' },
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

type SidebarProps = {
    open: boolean;
    onClose: () => void;
};

export default function Sidebar({ open, onClose }: SidebarProps) {
    const { auth } = usePage<PageProps>().props;
    const permissions = new Set(auth.permissions ?? []);
    const [openGroups, setOpenGroups] = useState<Record<string, boolean>>({});

    const visibleGroups = useMemo(
        () =>
            menuGroups
                .map((g) => ({ ...g, items: g.items.filter((i) => !i.permission || permissions.has(i.permission)) }))
                .filter((g) => g.items.length > 0),
        [auth.permissions],
    );

    // Auto-hide: close on Escape.
    useEffect(() => {
        if (!open) return;
        const onKey = (e: KeyboardEvent) => {
            if (e.key === 'Escape') onClose();
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [open, onClose]);

    const toggleGroup = (label: string) =>
        setOpenGroups((prev) => ({ ...prev, [label]: !prev[label] }));

    return (
        <>
            {/* Overlay (all sizes) — clicking outside closes the drawer */}
            <div
                onClick={onClose}
                aria-hidden="true"
                className={`fixed inset-0 z-40 bg-slate-900/40 transition-opacity duration-200 dark:bg-black/50 ${
                    open ? 'opacity-100' : 'pointer-events-none opacity-0'
                }`}
            />

            <aside
                className={`fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] flex-col bg-white shadow-xl transition-transform duration-200 ease-out dark:bg-gray-900 ${
                    open ? 'translate-x-0' : '-translate-x-full'
                }`}
                aria-label="Navigasi utama"
            >
                <div className="flex h-16 shrink-0 items-center border-b border-slate-200 px-4 dark:border-gray-800">
                    <Link href={route('dashboard')} onClick={onClose} className="flex items-center">
                        <ApplicationLogoImage className="h-7 w-auto max-w-[200px]" />
                    </Link>
                </div>

                <nav className="flex-1 space-y-2 overflow-y-auto px-3 py-4">
                    {visibleGroups.map((group) => {
                        const expanded = openGroups[group.label] ?? false;
                        const groupActive = group.items.some((i) => route().current(i.active));
                        return (
                            <div key={group.label}>
                                <button
                                    type="button"
                                    onClick={() => toggleGroup(group.label)}
                                    aria-expanded={expanded}
                                    className={`flex w-full items-center justify-between rounded-md px-3 py-2 text-xs font-semibold uppercase tracking-wider transition focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 ${
                                        groupActive
                                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-gray-800 dark:hover:text-gray-100'
                                    }`}
                                >
                                    <span>{group.label}</span>
                                    <svg
                                        className={`h-4 w-4 transition-transform ${expanded ? 'rotate-180' : ''}`}
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
                                    </svg>
                                </button>

                                {expanded && (
                                    <div className="mt-1 space-y-0.5 pb-1">
                                        {group.items.map((item) => {
                                            const active = route().current(item.active);
                                            return (
                                                <Link
                                                    key={item.routeName}
                                                    href={route(item.routeName)}
                                                    onClick={onClose}
                                                    aria-current={active ? 'page' : undefined}
                                                    className={`block rounded-md px-3 py-2 text-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 ${
                                                        active
                                                            ? 'bg-emerald-100 font-medium text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-200'
                                                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-gray-800 dark:hover:text-white'
                                                    }`}
                                                >
                                                    {item.label}
                                                </Link>
                                            );
                                        })}
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </nav>

                <div className="shrink-0 border-t border-slate-200 px-4 py-3 text-xs text-slate-400 dark:border-gray-800 dark:text-slate-500">
                    {auth.user.name}
                </div>
            </aside>
        </>
    );
}
