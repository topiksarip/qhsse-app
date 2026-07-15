import { PropsWithChildren } from 'react';

/**
 * Wraps wide tables so they scroll horizontally on small screens instead of
 * overflowing the viewport. Header stays visible while scrolling vertically.
 * Pass `className` to size the container (e.g. max height).
 */
export default function TableWrapper({ children, className = '' }: PropsWithChildren<{ className?: string }>) {
    return (
        <div className={`w-full overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900 ${className}`}>
            <table className="min-w-full divide-y divide-slate-200 text-sm dark:divide-gray-700">{children}</table>
        </div>
    );
}

/** Sticky header row helper — keeps column labels visible during vertical scroll. */
export function TableHead({ children }: PropsWithChildren) {
    return (
        <thead className="sticky top-0 z-10 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-gray-800 dark:text-gray-300">
            {children}
        </thead>
    );
}

export function TableBody({ children }: PropsWithChildren) {
    return <tbody className="divide-y divide-slate-100 dark:divide-gray-800">{children}</tbody>;
}
