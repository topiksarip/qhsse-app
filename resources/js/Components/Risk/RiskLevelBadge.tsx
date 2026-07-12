import { RiskMatrixLevel } from '@/types';

const colorMap: Record<string, { bg: string; text: string; ring: string }> = {
    red: { bg: 'bg-red-100', text: 'text-red-800 dark:bg-red-900 dark:text-red-200', ring: 'ring-red-300' },
    orange: { bg: 'bg-orange-100', text: 'text-orange-800 dark:bg-orange-900 dark:text-orange-200', ring: 'ring-orange-300' },
    yellow: { bg: 'bg-yellow-100', text: 'text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200', ring: 'ring-yellow-300' },
    green: { bg: 'bg-green-100', text: 'text-green-800 dark:bg-green-900 dark:text-green-200', ring: 'ring-green-300' },
    gray: { bg: 'bg-gray-100', text: 'text-gray-500 dark:bg-gray-700 dark:text-gray-300', ring: 'ring-gray-300' },
};

export default function RiskLevelBadge({ level }: { level?: RiskMatrixLevel | null }) {
    if (!level || !level.color) {
        return (
            <span className="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-500 ring-1 ring-gray-300">
                —
            </span>
        );
    }
    const c = colorMap[level.color] ?? colorMap.gray;
    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ${c.bg} ${c.text} ${c.ring}`}>
            {level.level?.toUpperCase()}
        </span>
    );
}
