import ChartPlaceholder from '@/Components/Dashboard/ChartPlaceholder';
import DashboardFilters from '@/Components/Dashboard/DashboardFilters';
import KpiCard from '@/Components/Dashboard/KpiCard';
import QuickActionCard from '@/Components/Dashboard/QuickActionCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/react';
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

    const quickLinkIcons: Record<string, JSX.Element> = {
        'incident.reports.index': (
            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        ),
        'investigation.reports.index': (
            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        ),
        'capa.actions.index': (
            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
        ),
        'inspection.checklists.index': (
            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        ),
        'assets.index': (
            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 17v-6h6v6m-9 4h12a2 2 0 002-2V9l-8-6-8 6v10a2 2 0 002 2z" />
            </svg>
        ),
    };

    const sectionTitle = useMemo(
        () => ({
            quick: 'Tindakan Cepat',
            quickSub: 'Akses cepat ke modul operasional utama',
            kpi: 'Indikator Kinerja Utama',
            kpiSub: 'Ringkasan metrik QHSSE terkini',
            charts: 'Tren & Distribusi',
            chartsSub: 'Visualisasi data operasional',
        }),
        [],
    );

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">QHSSE Operations</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Dashboard & KPI</h2>
                    </div>
                    <div className="text-sm text-slate-500 dark:text-slate-400">
                        {notificationSummary.unread} notifikasi belum dibaca
                    </div>
                </div>
            }
        >
            <Head title="Dashboard" />

            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    {/* Filters */}
                    <section className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-5">
                        <DashboardFilters filters={filters} filterOptions={filterOptions} route="dashboard" />
                    </section>

                    {/* Quick Actions */}
                    {visibleQuickLinks.length > 0 && (
                        <section>
                            <div className="mb-3">
                                <h3 className="text-base font-bold text-slate-950 dark:text-white">{sectionTitle.quick}</h3>
                                <p className="text-sm text-slate-500 dark:text-slate-400">{sectionTitle.quickSub}</p>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                                {visibleQuickLinks.slice(0, 5).map((item) => (
                                    <QuickActionCard
                                        key={item.route}
                                        label={item.label}
                                        route={route(item.route)}
                                        icon={
                                            quickLinkIcons[item.route] || (
                                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                            )
                                        }
                                    />
                                ))}
                            </div>
                        </section>
                    )}

                    {/* KPI Cards */}
                    <section>
                        <div className="mb-3">
                            <h3 className="text-base font-bold text-slate-950 dark:text-white">{sectionTitle.kpi}</h3>
                            <p className="text-sm text-slate-500 dark:text-slate-400">{sectionTitle.kpiSub}</p>
                        </div>
                        <div className="grid grid-cols-2 gap-4 lg:grid-cols-4 xl:grid-cols-5">
                            {kpis.map((kpi) => (
                                <KpiCard key={kpi.label} {...kpi} />
                            ))}
                        </div>
                    </section>

                    {/* Charts */}
                    {widgets.length > 0 && (
                        <section>
                            <div className="mb-3">
                                <h3 className="text-base font-bold text-slate-950 dark:text-white">{sectionTitle.charts}</h3>
                                <p className="text-sm text-slate-500 dark:text-slate-400">{sectionTitle.chartsSub}</p>
                            </div>
                            <div className="grid gap-4 lg:grid-cols-2">
                                {widgets.map((widget) => (
                                    <ChartPlaceholder key={widget.title} {...widget} />
                                ))}
                            </div>
                        </section>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
