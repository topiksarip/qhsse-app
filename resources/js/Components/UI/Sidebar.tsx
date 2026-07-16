import ApplicationLogoImage from '@/Components/ApplicationLogoImage';
import { menuGroups } from '@/Components/UI/navConfig';
import { Link, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import { useEffect, useMemo, useState } from 'react';

type MenuItem = { label: string; routeName: string; active: string; permission?: string };
type MenuGroup = { label: string; items: MenuItem[] };

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
                                    className={`flex w-full items-center justify-between rounded-md px-3 py-2 text-[15px] font-bold uppercase tracking-wider transition focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 ${
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
                                                    className={`block rounded-md py-2 pl-9 pr-3 text-[13px] transition focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 ${
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
