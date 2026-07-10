import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode, useMemo, useState } from 'react';

type MenuItem = {
    label: string;
    routeName: string;
    active: string;
    permission?: string;
};

const menuGroups: { label: string; items: MenuItem[] }[] = [
    {
        label: 'Core',
        items: [
            { label: 'Dashboard', routeName: 'dashboard', active: 'dashboard' },
            { label: 'Sites', routeName: 'core.sites.index', active: 'core.sites.*', permission: 'core.sites.view' },
            { label: 'Departments', routeName: 'core.departments.index', active: 'core.departments.*', permission: 'core.departments.view' },
            { label: 'Files', routeName: 'core.files.index', active: 'core.files.*', permission: 'core.files.view' },
            { label: 'Notifications', routeName: 'core.notifications.index', active: 'core.notifications.*', permission: 'core.notifications.view' },
        ],
    },
    {
        label: 'Masters',
        items: [
            { label: 'Severities', routeName: 'core.severities.index', active: 'core.severities.*', permission: 'core.severities.view' },
            { label: 'Priorities', routeName: 'core.priorities.index', active: 'core.priorities.*', permission: 'core.priorities.view' },
            { label: 'Statuses', routeName: 'core.statuses.index', active: 'core.statuses.*', permission: 'core.statuses.view' },
            { label: 'Categories', routeName: 'core.categories.index', active: 'core.categories.*', permission: 'core.categories.view' },
            { label: 'Risk Matrix', routeName: 'core.risk-matrix.index', active: 'core.risk-matrix.*', permission: 'core.risk-matrix.view' },
            { label: 'Areas', routeName: 'core.areas.index', active: 'core.areas.*', permission: 'core.areas.view' },
            { label: 'Positions', routeName: 'core.positions.index', active: 'core.positions.*', permission: 'core.positions.view' },
        ],
    },
    {
        label: 'Modul QHSSE',
        items: [
            { label: 'Laporan Insiden', routeName: 'incident.reports.index', active: 'incident.reports.*', permission: 'incident.reports.view' },
        ],
    },
    {
        label: 'Admin',
        items: [
            { label: 'Companies', routeName: 'core.companies.index', active: 'core.companies.*', permission: 'core.companies.view' },
            { label: 'Employees', routeName: 'core.employees.index', active: 'core.employees.*', permission: 'core.employees.view' },
            { label: 'Users', routeName: 'core.users.index', active: 'core.users.*', permission: 'core.users.view' },
            { label: 'Numbering', routeName: 'core.numbering.index', active: 'core.numbering.*', permission: 'core.numbering.view' },
            { label: 'Workflow', routeName: 'core.workflow.index', active: 'core.workflow.*', permission: 'core.workflow.view' },
            { label: 'Audit Logs', routeName: 'core.audit-logs.index', active: 'core.audit-logs.*', permission: 'core.audit.view' },
            { label: 'Comments', routeName: 'core.comments-activity.index', active: 'core.comments-activity.*', permission: 'core.comments.view' },
        ],
    },
];

export default function Authenticated({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const { auth } = usePage<PageProps>().props;
    const user = auth.user;
    const permissions = new Set(auth.permissions ?? []);
    const [showingNavigationDropdown, setShowingNavigationDropdown] = useState(false);

    const visibleGroups = useMemo(() => menuGroups
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => !item.permission || permissions.has(item.permission)),
        }))
        .filter((group) => group.items.length > 0), [auth.permissions]);

    return (
        <div className="min-h-screen bg-slate-100 dark:bg-gray-950">
            <nav className="border-b border-slate-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex min-w-0 items-center gap-6">
                            <Link href={route('dashboard')} className="flex shrink-0 items-center gap-3">
                                <ApplicationLogo className="block h-9 w-auto fill-current text-slate-800 dark:text-slate-200" />
                                <span className="hidden text-sm font-bold uppercase tracking-[0.24em] text-slate-700 dark:text-slate-200 lg:block">QHSSE</span>
                            </Link>

                            <div className="hidden items-center gap-2 overflow-x-auto lg:flex">
                                {visibleGroups.map((group) => (
                                    <Dropdown key={group.label}>
                                        <Dropdown.Trigger>
                                            <button type="button" className="rounded-full px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-gray-800 dark:hover:text-white">
                                                {group.label}
                                            </button>
                                        </Dropdown.Trigger>
                                        <Dropdown.Content align="left" width="48">
                                            {group.items.map((item) => (
                                                <Dropdown.Link key={item.routeName} href={route(item.routeName)}>
                                                    {item.label}
                                                </Dropdown.Link>
                                            ))}
                                        </Dropdown.Content>
                                    </Dropdown>
                                ))}
                            </div>
                        </div>

                        <div className="hidden sm:ms-6 sm:flex sm:items-center">
                            <div className="relative ms-3">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <span className="inline-flex rounded-md">
                                            <button type="button" className="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-slate-500 transition hover:text-slate-700 focus:outline-none dark:bg-gray-900 dark:text-slate-400 dark:hover:text-slate-200">
                                                {user.name}
                                                <svg className="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
                                                </svg>
                                            </button>
                                        </span>
                                    </Dropdown.Trigger>
                                    <Dropdown.Content>
                                        <Dropdown.Link href={route('profile.edit')}>Profile</Dropdown.Link>
                                        <Dropdown.Link href={route('logout')} method="post" as="button">Log Out</Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>

                        <div className="-me-2 flex items-center lg:hidden">
                            <button onClick={() => setShowingNavigationDropdown((previousState) => !previousState)} className="inline-flex items-center justify-center rounded-md p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-500 focus:bg-slate-100 focus:text-slate-500 focus:outline-none dark:text-slate-500 dark:hover:bg-gray-800 dark:hover:text-slate-300">
                                <svg className="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path className={!showingNavigationDropdown ? 'inline-flex' : 'hidden'} strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path className={showingNavigationDropdown ? 'inline-flex' : 'hidden'} strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div className={(showingNavigationDropdown ? 'block' : 'hidden') + ' lg:hidden'}>
                    <div className="space-y-4 pb-3 pt-2">
                        {visibleGroups.map((group) => (
                            <div key={group.label}>
                                <div className="px-4 pb-1 text-xs font-semibold uppercase tracking-widest text-slate-500">{group.label}</div>
                                {group.items.map((item) => (
                                    <ResponsiveNavLink key={item.routeName} href={route(item.routeName)} active={route().current(item.active)}>
                                        {item.label}
                                    </ResponsiveNavLink>
                                ))}
                            </div>
                        ))}
                    </div>

                    <div className="border-t border-slate-200 pb-1 pt-4 dark:border-gray-700">
                        <div className="px-4">
                            <div className="text-base font-medium text-slate-800 dark:text-slate-200">{user.name}</div>
                            <div className="text-sm font-medium text-slate-500">{user.email}</div>
                        </div>
                        <div className="mt-3 space-y-1">
                            <ResponsiveNavLink href={route('profile.edit')}>Profile</ResponsiveNavLink>
                            <ResponsiveNavLink method="post" href={route('logout')} as="button">Log Out</ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="border-b border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">{header}</div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}
