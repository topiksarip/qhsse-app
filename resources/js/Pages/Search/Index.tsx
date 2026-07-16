import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import EmptyState from '@/Components/UI/EmptyState';
import { PageProps } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type SearchResultItem = {
    id: number;
    title: string;
    snippet: string;
    href: string;
};

type SearchGroup = {
    module: string;
    route: string;
    items: SearchResultItem[];
};

type Props = PageProps<{
    query: string;
    scope: string;
    moduleOptions: Record<string, string>;
    results: SearchGroup[];
    total: number;
    elapsedMs: number;
    searched: boolean;
}>;

export default function Index({ query, scope, moduleOptions, results, total, elapsedMs, searched }: Props) {
    const [q, setQ] = useState(query);
    const [module, setModule] = useState(scope);

    function submit(e: FormEvent) {
        e.preventDefault();
        router.get(
            route('search.index'),
            { q: q.trim(), module },
            { preserveState: true, replace: true },
        );
    }

    function reset() {
        setQ('');
        setModule('all');
        router.get(route('search.index'), { q: '', module: 'all' }, { preserveState: true, replace: true });
    }

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">Global</p>
                    <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Pencarian</h2>
                </div>
            }
        >
            <Head title="Pencarian" />
            <div className="py-6">
                <div className="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <form onSubmit={submit} className="grid gap-3 md:grid-cols-3">
                            <div className="relative md:col-span-2">
                                <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                                    <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                                        <circle cx="11" cy="11" r="7" />
                                        <path strokeLinecap="round" d="m21 21-4.3-4.3" />
                                    </svg>
                                </span>
                                <input
                                    type="text"
                                    value={q}
                                    autoFocus
                                    onChange={(e) => setQ(e.target.value)}
                                    placeholder="Cari nomor, judul, nama, deskripsi..."
                                    className="w-full rounded-md border-slate-300 pl-9 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                                />
                            </div>
                            <select
                                value={module}
                                onChange={(e) => setModule(e.target.value)}
                                className="rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            >
                                {Object.entries(moduleOptions).map(([value, label]) => (
                                    <option key={value} value={value}>
                                        {label}
                                    </option>
                                ))}
                            </select>
                            <div className="flex gap-2 md:col-span-3">
                                <button
                                    type="submit"
                                    className="inline-flex items-center justify-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                                >
                                    Cari
                                </button>
                                <button
                                    type="button"
                                    onClick={reset}
                                    className="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                                >
                                    Reset
                                </button>
                            </div>
                        </form>
                    </div>

                    {searched && (
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            {total > 0 ? (
                                <>
                                    Ditemukan <span className="font-semibold text-slate-700 dark:text-slate-200">{total}</span> hasil
                                    untuk “<span className="font-semibold text-slate-700 dark:text-slate-200">{query}</span>”
                                    {' '}• {elapsedMs} ms
                                </>
                            ) : (
                                <>
                                    Tidak ada hasil untuk “<span className="font-semibold text-slate-700 dark:text-slate-200">{query}</span>”
                                </>
                            )}
                        </p>
                    )}

                    {!searched && (
                        <EmptyState
                            title="Mulai pencarian"
                            description="Ketik kata kunci untuk mencari lintas modul: Insiden, CAPA, Audit, Inspeksi, Dokumen, Izin Kerja, Keamanan, Risk, Asset, dan Pelatihan."
                        />
                    )}

                    {searched && total === 0 && (
                        <EmptyState
                            title="Tidak ada hasil"
                            description="Coba kata kunci lain atau pilih modul yang berbeda."
                        />
                    )}

                    <div className="space-y-5">
                        {results.map((group) => (
                            <section
                                key={group.module}
                                className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
                            >
                                <div className="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-gray-700">
                                    <h3 className="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                                        {group.module}
                                    </h3>
                                    <span className="inline-flex items-center rounded-full bg-emerald-600 px-2 py-0.5 text-xs font-semibold text-white">
                                        {group.items.length}
                                    </span>
                                </div>
                                <ul className="divide-y divide-slate-100 dark:divide-gray-800">
                                    {group.items.map((item) => (
                                        <li key={item.id}>
                                            <Link
                                                href={item.href}
                                                className="block px-4 py-3 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 dark:hover:bg-gray-800"
                                            >
                                                <p className="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">
                                                    {item.title}
                                                </p>
                                                {item.snippet && (
                                                    <p className="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">
                                                        {item.snippet}
                                                    </p>
                                                )}
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        ))}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
