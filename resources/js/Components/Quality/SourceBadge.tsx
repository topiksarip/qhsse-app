import { NcrSource } from '@/types';

const config: Record<NcrSource, { label: string; color: string }> = {
    internal: { label: 'Internal', color: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200' },
    external: { label: 'Eksternal', color: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200' },
    customer_complaint: { label: 'Keluhan Pelanggan', color: 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200' },
    audit: { label: 'Audit', color: 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200' },
    supplier: { label: 'Pemasok', color: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' },
};

export default function SourceBadge({ source }: { source: NcrSource }) {
    const c = config[source] ?? config.internal;
    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${c.color}`}>
            {c.label}
        </span>
    );
}
