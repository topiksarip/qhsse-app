import { ValidityStatus } from '@/types';

const validityConfig: Record<ValidityStatus, { label: string; icon: string; color: string }> = {
    active: { label: 'Aktif', icon: '🟢', color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' },
    expired: { label: 'Kedaluwarsa', icon: '🔴', color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
    expiring_soon: { label: 'Akan Berakhir', icon: '🟡', color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' },
    not_started: { label: 'Belum Aktif', icon: '⚪', color: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' },
};

export default function ValidityBadge({ status }: { status: ValidityStatus }) {
    const config = validityConfig[status] || validityConfig.not_started;
    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${config.color}`}>
            <span className="mr-1">{config.icon}</span>
            {config.label}
        </span>
    );
}
