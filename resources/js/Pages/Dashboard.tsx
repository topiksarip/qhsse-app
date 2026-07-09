import ChartPlaceholder from '@/Components/Dashboard/ChartPlaceholder';
import KpiCard from '@/Components/Dashboard/KpiCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { FormEvent, useMemo, useState } from 'react';

type Option = { id: number; name: string; site_id?: number | null };
type Kpi = { label: string; value: number | string; tone?: 'emerald' | 'sky' | 'amber' | 'indigo' };
type Widget = { title: string; description: string; points: number[] };
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
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);
    const [siteId, setSiteId] = useState(filters.site_id?.toString() ?? '');
    const [departmentId, setDepartmentId] = useState(filters.department_id?.toString() ?? '');

    const departments = useMemo(() => filterOptions.departments.filter((department) => !siteId || department.site_id?.toString() === siteId), [filterOptions.departments, siteId]);
    const visibleQuickLinks = quickLinks.filter((item) => permissions.has(item.permission));

    function submit(event: FormEvent) {
        event.preventDefault();
        router.get(route('dashboard'), { from, to, site_id: siteId, department_id: departmentId }, { preserveState: true, replace: true });
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">Core Foundation</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">QHSSE Dashboard Shell</h2>
                    </div>
                    <div className="text-sm text-slate-500 dark:text-slate-400">{notificationSummary.unread} unread notifications</div>
                </div>
            }
        >
            <Head title="Dashboard" />

            <div className="py-10">
                <div className="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
                    <section className="overflow-hidden rounded-3xl bg-slate-950 text-white shadow-2xl dark:bg-black">
                        <div className="relative p-8">
                            <div className="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-emerald-400/20 blur-3xl" />
                            <div className="absolute bottom-0 left-1/2 h-48 w-48 rounded-full bg-cyan-400/10 blur-3xl" />
                            <div className="relative grid gap-8 lg:grid-cols-[1.25fr_0.75fr]">
                                <div>
                                    <p className="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-300">Phase 0 Dashboard</p>
                                    <h1 className="mt-4 max-w-3xl text-4xl font-black tracking-tight sm:text-5xl">A control-room shell for QHSSE operations.</h1>
                                    <p className="mt-4 max-w-2xl text-base text-slate-300">This dashboard wires the layout, filters, role-aware navigation, and reusable widgets before Phase 1 business metrics exist.</p>
                                </div>
                                <form onSubmit={submit} className="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                                    <div className="grid gap-3 sm:grid-cols-2">
                                        <label className="text-sm text-slate-200">From<input type="date" value={from} onChange={(event) => setFrom(event.target.value)} className="mt-1 w-full rounded-md border-white/10 bg-slate-950/80 text-white" /></label>
                                        <label className="text-sm text-slate-200">To<input type="date" value={to} onChange={(event) => setTo(event.target.value)} className="mt-1 w-full rounded-md border-white/10 bg-slate-950/80 text-white" /></label>
                                        <label className="text-sm text-slate-200">Site<select value={siteId} onChange={(event) => { setSiteId(event.target.value); setDepartmentId(''); }} className="mt-1 w-full rounded-md border-white/10 bg-slate-950/80 text-white"><option value="">All Sites</option>{filterOptions.sites.map((site) => <option key={site.id} value={site.id}>{site.name}</option>)}</select></label>
                                        <label className="text-sm text-slate-200">Department<select value={departmentId} onChange={(event) => setDepartmentId(event.target.value)} className="mt-1 w-full rounded-md border-white/10 bg-slate-950/80 text-white"><option value="">All Departments</option>{departments.map((department) => <option key={department.id} value={department.id}>{department.name}</option>)}</select></label>
                                    </div>
                                    <button className="mt-4 w-full rounded-xl bg-emerald-400 px-4 py-3 text-sm font-black uppercase tracking-widest text-slate-950 transition hover:bg-emerald-300">Apply Filters</button>
                                </form>
                            </div>
                        </div>
                    </section>

                    <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        {kpis.map((kpi) => <KpiCard key={kpi.label} {...kpi} />)}
                    </section>

                    <section className="grid gap-6 xl:grid-cols-2">
                        {widgets.map((widget) => <ChartPlaceholder key={widget.title} {...widget} />)}
                    </section>

                    <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 className="text-lg font-bold text-slate-950 dark:text-white">Role-aware quick access</h3>
                                <p className="text-sm text-slate-500 dark:text-slate-400">Only links allowed by the user's permissions are rendered.</p>
                            </div>
                        </div>
                        <div className="mt-5 flex flex-wrap gap-3">
                            {visibleQuickLinks.map((item) => <Link key={item.route} href={route(item.route)} className="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-emerald-400 hover:text-emerald-700 dark:border-gray-700 dark:text-slate-200 dark:hover:border-emerald-400 dark:hover:text-emerald-300">{item.label}</Link>)}
                            {visibleQuickLinks.length === 0 && <span className="text-sm text-slate-500">No quick links available for this role.</span>}
                        </div>
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
