import { EnvironmentalType } from '@/types';

const config: Record<EnvironmentalType, { label: string; color: string }> = {
    waste: { label: 'Limbah', color: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' },
    spill: { label: 'Tumpahan', color: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' },
    emission: { label: 'Emisi', color: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' },
    noise: { label: 'Kebisingan', color: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200' },
    water_monitoring: { label: 'Monitoring Air', color: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200' },
    other: { label: 'Lainnya', color: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' },
};

export default function TypeBadge({ type }: { type: EnvironmentalType }) {
    const c = config[type] ?? config.other;
    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${c.color}`}>
            {c.label}
        </span>
    );
}
