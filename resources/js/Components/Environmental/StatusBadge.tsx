import { EnvironmentalStatus } from '@/types';

const config: Record<EnvironmentalStatus, { label: string; color: string }> = {
    recorded: { label: 'Tercatat', color: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' },
    investigated: { label: 'Diinvestigasi', color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' },
    action_open: { label: 'Aksi Dibuka', color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' },
    closed: { label: 'Ditutup', color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' },
};

export default function StatusBadge({ status }: { status: EnvironmentalStatus }) {
    const c = config[status] ?? config.recorded;
    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${c.color}`}>
            {c.label}
        </span>
    );
}
