import { PropsWithChildren } from 'react';

/**
 * Wraps wide tables so they scroll horizontally on small screens instead of
 * overflowing the viewport (or squishing into an unreadable mess). On screens
 * narrower than `tableClassName`'s min-width the container scrolls; on larger
 * screens the table fills 100% of the container. Header stays visible while
 * scrolling vertically. Pass `className` to size the container (e.g. max height)
 * and `tableClassName` to override the min-width (e.g. very wide matrix tables).
 */
export default function TableWrapper({ children, className = '', tableClassName = 'min-w-[max(100%,640px)]' }: PropsWithChildren<{ className?: string; tableClassName?: string }>) {
    return (
        <div className={`w-full overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900 ${className}`}>
            <table className={`${tableClassName} divide-y divide-slate-200 text-sm dark:divide-gray-700`}>{children}</table>
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
