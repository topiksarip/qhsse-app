import ChartPlaceholder from '@/Components/Dashboard/ChartPlaceholder';
import DashboardFilters from '@/Components/Dashboard/DashboardFilters';
import KpiCard from '@/Components/Dashboard/KpiCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { useMemo } from 'react';

type Option = { id: number; name: string; site_id?: number | null };
type Kpi = { label: string; value: number | string; sub?: string; tone?: 'emerald' | 'sky' | 'amber' | 'indigo' | 'red' };
type Widget = { title: string; description: string; points: number[]; labels?: string[] };
type QuickLink = { label: string; route: string; permission: string };
type Filters = { from: string; to: string; site_id?: number | null; department_id?: number | null };

type DashboardProps = PageProps<{
    filters: Filters;
    filterOptions: { sites: Option[]; departments: Option[] };
    kpis: Kpi[];
    widgets: Widget[];
    quickLinks: QuickLink[];
    notificationSummary: { unread: number };
}>;

export default function Dashboard({ filters, filterOptions, kpis, widgets, quickLinks, notificationSummary }: DashboardProps) {
    const { auth } = usePage<PageProps>().props;
    const permissions = new Set(auth.permissions ?? []);
    const visibleQuickLinks = quickLinks.filter((item) => permissions.has(item.permission));

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">QHSSE Operations</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Dashboard & KPI</h2>
                    </div>
                    <div className="text-sm text-slate-500 dark:text-slate-400">{notificationSummary.unread} unread notifications</div>
                </div>
            }
        >
            <Head title="Dashboard" />

            <div className="py-10">
                <div className="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
                    {/* Filters */}
                    <section className="rounded-xl bg-white border border-slate-200 shadow-sm dark:bg-gray-900 dark:border-gray-700">
                        <div className="p-8">
                            <div className="grid gap-8 lg:grid-cols-[1.25fr_0.75fr]">
                                <div>
                                    <p className="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-600 dark:text-emerald-400">Live Dashboard</p>
                                    <h1 className="mt-4 max-w-3xl text-4xl font-black tracking-tight text-slate-900 dark:text-white sm:text-5xl">Real-time KPI dari 4 modul operasional.</h1>
                                    <p className="mt-4 max-w-2xl text-base text-slate-600 dark:text-slate-300">Insident, Investigasi, CAPA, dan Inspeksi — filter by site, department, dan date range.</p>
                                </div>
                                <DashboardFilters
                                    filters={filters}
                                    filterOptions={filterOptions}
                                    route="dashboard"
                                />
                            </div>
                        </div>
                    </section>

                    {/* KPI Cards */}
                    <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        {kpis.map((kpi) => <KpiCard key={kpi.label} {...kpi} />)}
                    </section>

                    {/* Charts */}
                    <section className="grid gap-6 xl:grid-cols-2">
                        {widgets.map((widget) => <ChartPlaceholder key={widget.title} {...widget} />)}
                    </section>

                    {/* Quick Links */}
                    <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 className="text-lg font-bold text-slate-950 dark:text-white">Quick Access</h3>
                                <p className="text-sm text-slate-500 dark:text-slate-400">Role-aware shortcuts to operational modules.</p>
                            </div>
                        </div>
                        <div className="mt-5 flex flex-wrap gap-3">
                            {visibleQuickLinks.map((item) => <Link key={item.route} href={route(item.route)} className="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-emerald-500 hover:text-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 dark:border-gray-700 dark:text-slate-200 dark:hover:border-emerald-400 dark:hover:text-emerald-300">{item.label}</Link>)}
                            {visibleQuickLinks.length === 0 && <span className="text-sm text-slate-500">No quick links available for this role.</span>}
                        </div>
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
