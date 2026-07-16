import { ReactNode, useState } from 'react';

type FilterPanelProps = {
    children: ReactNode;
    /** Number of currently active filters; auto-expands the panel and shows a badge. */
    activeCount?: number;
    /** Panel title. */
    title?: string;
    /** Force expanded on first render regardless of activeCount. */
    defaultOpen?: boolean;
};

/**
 * Collapsible container for search + filter controls.
 * Hidden by default; a button toggles visibility. Auto-expands when filters are active
 * so the user never loses sight of an applied filter set.
 */
export default function FilterPanel({
    children,
    activeCount = 0,
    title = 'Pencarian & Filter',
    defaultOpen = false,
}: FilterPanelProps) {
    const [open, setOpen] = useState(defaultOpen || activeCount > 0);

    return (
        <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <button
                type="button"
                onClick={() => setOpen((o) => !o)}
                aria-expanded={open}
                className="flex w-full items-center justify-between gap-3 px-4 py-3 text-left transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 dark:hover:bg-gray-800"
            >
                <span className="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                    <svg className="h-4 w-4 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M3 4h18M7 9h10M11 14h2M9 19h6" />
                    </svg>
                    {title}
                    {activeCount > 0 && (
                        <span className="inline-flex items-center rounded-full bg-emerald-600 px-2 py-0.5 text-xs font-semibold text-white">
                            {activeCount}
                        </span>
                    )}
                </span>
                <svg
                    className={`h-4 w-4 text-slate-400 transition-transform ${open ? 'rotate-180' : ''}`}
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    aria-hidden="true"
                >
                    <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            {open && <div className="border-t border-slate-200 p-4 dark:border-gray-700">{children}</div>}
        </div>
    );
}
