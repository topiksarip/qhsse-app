type BadgeColor = 'gray' | 'blue' | 'yellow' | 'green' | 'red' | 'orange';

interface ResultBadgeProps {
    result?: 'pass' | 'fail' | 'pending';
}

const resultConfig: Record<string, { label: string; color: BadgeColor; icon: string }> = {
    pass: { label: 'Pass', color: 'green', icon: '✅' },
    fail: { label: 'Fail', color: 'red', icon: '❌' },
    pending: { label: 'Pending', color: 'yellow', icon: '⏳' },
};

const colorClasses: Record<BadgeColor, string> = {
    gray: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    blue: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    green: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    red: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    orange: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
};

export default function ResultBadge({ result }: ResultBadgeProps) {
    if (!result) {
        return <span className="text-gray-400 dark:text-gray-500">—</span>;
    }

    const config = resultConfig[result] || { label: result, color: 'gray', icon: '' };
    const colorClass = colorClasses[config.color];

    return (
        <span
            className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ${colorClass}`}
        >
            <span>{config.icon}</span>
            <span>{config.label}</span>
        </span>
    );
}
