import { PermitType } from '@/types';

const typeConfig: Record<PermitType, { label: string; icon: string; color: string }> = {
    hot_work: { label: 'Hot Work', icon: '🔥', color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
    working_at_height: { label: 'Working at Height', icon: '🧗', color: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' },
    confined_space: { label: 'Confined Space', icon: '🕳️', color: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' },
    electrical: { label: 'Electrical', icon: '⚡', color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' },
    excavation: { label: 'Excavation', icon: '⛏️', color: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' },
    lifting: { label: 'Lifting', icon: '🏗️', color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' },
    other: { label: 'Other', icon: '📋', color: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' },
};

export default function PermitTypeBadge({ type }: { type: PermitType }) {
    const config = typeConfig[type] || { label: type, icon: '📋', color: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' };
    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${config.color}`}>
            <span className="mr-1">{config.icon}</span>
            {config.label}
        </span>
    );
}
