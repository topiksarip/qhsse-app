import KpiCard from '@/Components/Dashboard/KpiCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

type Activity = {
    id: number;
    event: string;
    module_name: string | null;
    actor_name: string | null;
    created_at: string;
    actor?: { name: string } | null;
};

type Props = {
    stats: { users: number; activeUsers: number; employees: number; sites: number; companies: number };
    recentActivity: Activity[];
};

const links = [
    ['Sites', 'core.sites.index', 'core.sites.view'], ['Departments', 'core.departments.index', 'core.departments.view'],
    ['Employees', 'core.employees.index', 'core.employees.view'], ['Users', 'core.users.index', 'core.users.view'],
    ['Severities', 'core.severities.index', 'core.severities.view'], ['Priorities', 'core.priorities.index', 'core.priorities.view'],
    ['Categories', 'core.categories.index', 'core.categories.view'], ['Risk Matrix', 'core.risk-matrix.index', 'core.risk-matrix.view'],
    ['Numbering', 'core.numbering.index', 'core.numbering.view'], ['Workflow', 'core.workflow.index', 'core.workflow.view'],
    ['Audit Logs', 'core.audit-logs.index', 'core.audit.view'], ['Roles', 'core.roles.index', 'core.roles.manage'],
] as const;

export default function Dashboard({ stats, recentActivity }: Props) {
    const permissions = new Set(usePage<PageProps>().props.auth.permissions ?? []);
    const canImport = permissions.has('core.employees.create') || permissions.has('core.sites.create') || permissions.has('core.departments.create');
    const cards = [
        { label: 'Total Users', value: stats.users, sub: `${stats.activeUsers} aktif`, tone: 'sky' as const },
        { label: 'Active Users', value: stats.activeUsers, sub: 'Akun dapat login', tone: 'emerald' as const },
        { label: 'Employees', value: stats.employees, sub: 'Data karyawan', tone: 'indigo' as const },
        { label: 'Sites', value: stats.sites, sub: 'Lokasi terdaftar', tone: 'amber' as const },
        { label: 'Companies', value: stats.companies, sub: 'Internal & contractor', tone: 'sky' as const },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Admin Dashboard" />
            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-xs font-bold uppercase tracking-[0.24em] text-indigo-600">System Admin</p>
                            <h1 className="mt-1 text-3xl font-bold text-slate-950 dark:text-white">Admin Dashboard</h1>
                            <p className="mt-1 text-sm text-slate-500">Ringkasan identity, organisasi, dan aktivitas administrasi.</p>
                        </div>
                        {canImport && <Link href={route('admin.import.create')} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Import CSV</Link>}
                    </div>

                    <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                        {cards.map((card) => <KpiCard key={card.label} {...card} />)}
                    </section>

                    <div className="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
                        <section className="rounded-lg bg-white p-5 shadow-sm dark:bg-slate-800">
                            <h2 className="text-lg font-semibold text-slate-900 dark:text-white">Aktivitas Audit Terbaru</h2>
                            <div className="mt-4 divide-y divide-slate-200 dark:divide-slate-700">
                                {recentActivity.length === 0 && <p className="py-6 text-sm text-slate-500">Belum ada aktivitas audit.</p>}
                                {recentActivity.map((activity) => (
                                    <div key={activity.id} className="flex gap-3 py-3 text-sm">
                                        <div className="min-w-0 flex-1">
                                            <p className="font-medium text-slate-800 dark:text-slate-200">{activity.event.replaceAll('_', ' ')}</p>
                                            <p className="text-xs text-slate-500">{activity.actor?.name ?? activity.actor_name ?? 'System'} · {activity.module_name ?? 'core'}</p>
                                        </div>
                                        <time className="shrink-0 text-xs text-slate-400">{new Date(activity.created_at).toLocaleString('id-ID')}</time>
                                    </div>
                                ))}
                            </div>
                        </section>

                        <section className="rounded-lg bg-white p-5 shadow-sm dark:bg-slate-800">
                            <h2 className="text-lg font-semibold text-slate-900 dark:text-white">Quick Links</h2>
                            <div className="mt-4 grid gap-2 sm:grid-cols-2">
                                {links.filter(([, , permission]) => permissions.has(permission)).map(([label, routeName]) => (
                                    <Link key={routeName} href={route(routeName)} className="rounded-md border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-indigo-300 hover:bg-indigo-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-indigo-950">{label}</Link>
                                ))}
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
