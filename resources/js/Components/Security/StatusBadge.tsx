import { SecurityIncidentStatus } from '@/types';

const config: Record<SecurityIncidentStatus, { label: string; color: string }> = {
    reported: { label: 'Reported', color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' },
    under_investigation: { label: 'Investigasi', color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' },
    closed: { label: 'Closed', color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' },
};

export default function StatusBadge({ status }: { status: SecurityIncidentStatus }) {
    const c = config[status] ?? config.reported;
    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${c.color}`}>
            {c.label}
        </span>
    );
}
