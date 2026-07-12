import { NcrStatus } from '@/types';

const config: Record<NcrStatus, { label: string; color: string }> = {
    open: { label: 'Open', color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' },
    under_review: { label: 'Under Review', color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' },
    in_progress: { label: 'In Progress', color: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' },
    closed: { label: 'Closed', color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' },
    rejected: { label: 'Rejected', color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
};

export default function StatusBadge({ status }: { status: NcrStatus }) {
    const c = config[status] ?? config.open;
    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${c.color}`}>
            {c.label}
        </span>
    );
}
