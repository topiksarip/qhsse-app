import { Severity } from '@/types';

function colorForLevel(level: number): string {
    if (level >= 4) return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    if (level === 3) return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200';
    if (level === 2) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
    return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
}

export default function SeverityBadge({ severity }: { severity?: Severity | null }) {
    if (!severity) return <span className="text-sm text-gray-400">—</span>;
    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${colorForLevel(severity.level)}`}>
            {severity.name}
        </span>
    );
}
