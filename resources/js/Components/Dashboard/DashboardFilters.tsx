/**
 * DashboardFilters Component
 * 
 * Reusable filter form for dashboard pages.
 * Supports date range, site, and department filtering with proper accessibility.
 */

import { router } from '@inertiajs/react';
import { FormEvent, useMemo, useState } from 'react';

type Option = {
    id: number;
    name: string;
    site_id?: number | null;
};

type DashboardFiltersProps = {
    filters: {
        from: string;
        to: string;
        site_id?: number | null;
        department_id?: number | null;
    };
    filterOptions: {
        sites: Option[];
        departments: Option[];
    };
    route: string;
    className?: string;
};

export default function DashboardFilters({
    filters,
    filterOptions,
    route: filterRoute,
    className = '',
}: DashboardFiltersProps) {
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);
    const [siteId, setSiteId] = useState(filters.site_id?.toString() ?? '');
    const [departmentId, setDepartmentId] = useState(filters.department_id?.toString() ?? '');

    // Filter departments by selected site
    const departments = useMemo(
        () => filterOptions.departments.filter((d) => !siteId || d.site_id?.toString() === siteId),
        [filterOptions.departments, siteId]
    );

    function submit(event: FormEvent) {
        event.preventDefault();
        router.get(
            route(filterRoute),
            {
                from,
                to,
                site_id: siteId,
                department_id: departmentId,
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    }

    return (
        <form onSubmit={submit} className={`rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-gray-700 dark:bg-gray-800 ${className}`}>
            <div className="grid gap-4 sm:grid-cols-2">
                <div>
                    <label
                        htmlFor="filter-from"
                        className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"
                    >
                        From Date
                    </label>
                    <input
                        id="filter-from"
                        type="date"
                        value={from}
                        onChange={(e) => setFrom(e.target.value)}
                        className="w-full rounded-md border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-emerald-500 focus:ring-emerald-500"
                        aria-label="Filter start date"
                    />
                </div>
                <div>
                    <label
                        htmlFor="filter-to"
                        className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"
                    >
                        To Date
                    </label>
                    <input
                        id="filter-to"
                        type="date"
                        value={to}
                        onChange={(e) => setTo(e.target.value)}
                        className="w-full rounded-md border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-emerald-500 focus:ring-emerald-500"
                        aria-label="Filter end date"
                    />
                </div>
                <div>
                    <label
                        htmlFor="filter-site"
                        className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"
                    >
                        Site
                    </label>
                    <select
                        id="filter-site"
                        value={siteId}
                        onChange={(e) => {
                            setSiteId(e.target.value);
                            setDepartmentId(''); // Reset department when site changes
                        }}
                        className="w-full rounded-md border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-emerald-500 focus:ring-emerald-500"
                        aria-label="Filter by site"
                    >
                        <option value="">All Sites</option>
                        {filterOptions.sites.map((s) => (
                            <option key={s.id} value={s.id}>
                                {s.name}
                            </option>
                        ))}
                    </select>
                </div>
                <div>
                    <label
                        htmlFor="filter-department"
                        className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"
                    >
                        Department
                    </label>
                    <select
                        id="filter-department"
                        value={departmentId}
                        onChange={(e) => setDepartmentId(e.target.value)}
                        className="w-full rounded-md border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-emerald-500 focus:ring-emerald-500"
                        aria-label="Filter by department"
                    >
                        <option value="">All Departments</option>
                        {departments.map((d) => (
                            <option key={d.id} value={d.id}>
                                {d.name}
                            </option>
                        ))}
                    </select>
                </div>
            </div>
            <button
                type="submit"
                className="mt-4 w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
            >
                Apply Filters
            </button>
        </form>
    );
}
