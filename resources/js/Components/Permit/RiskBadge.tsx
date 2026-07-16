import { RiskLevel } from '@/types';

const riskConfig: Record<RiskLevel, { label: string; color: string }> = {
    low: { label: 'Low', color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' },
    medium: { label: 'Medium', color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' },
    high: { label: 'High', color: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' },
    critical: { label: 'Critical', color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
};

export default function RiskBadge({ level }: { level?: RiskLevel | null }) {
    if (!level) return <span className="text-gray-400 dark:text-gray-500">—</span>;
    const config = riskConfig[level] || riskConfig.low;
    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${config.color}`}>
            {config.label}
        </span>
    );
}
