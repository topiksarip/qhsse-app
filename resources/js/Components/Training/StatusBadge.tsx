type BadgeColor = 'gray' | 'blue' | 'yellow' | 'green' | 'red' | 'orange';

interface StatusBadgeProps {
    status: 'scheduled' | 'in_progress' | 'completed' | 'expired' | 'cancelled';
}

const statusConfig: Record<string, { label: string; color: BadgeColor }> = {
    scheduled: { label: 'Scheduled', color: 'blue' },
    in_progress: { label: 'In Progress', color: 'yellow' },
    completed: { label: 'Completed', color: 'green' },
    expired: { label: 'Expired', color: 'red' },
    cancelled: { label: 'Cancelled', color: 'gray' },
};

const colorClasses: Record<BadgeColor, string> = {
    gray: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    blue: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    green: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    red: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    orange: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
};

export default function StatusBadge({ status }: StatusBadgeProps) {
    const config = statusConfig[status] || { label: status, color: 'gray' };
    const colorClass = colorClasses[config.color];

    return (
        <span
            className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${colorClass}`}
        >
            {config.label}
        </span>
    );
}
