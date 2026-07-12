import { SecurityIncidentType } from '@/types';

const config: Record<SecurityIncidentType, { label: string; color: string }> = {
    unauthorized_access: { label: 'Akses Tidak Sah', color: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' },
    theft: { label: 'Pencurian', color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
    vandalism: { label: 'Vandalisme', color: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' },
    trespass: { label: 'Penyusupan', color: 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200' },
    suspicious_activity: { label: 'Aktivitas Mencurigakan', color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' },
    other: { label: 'Lainnya', color: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' },
};

export default function TypeBadge({ type }: { type: SecurityIncidentType }) {
    const c = config[type] ?? config.other;
    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${c.color}`}>
            {c.label}
        </span>
    );
}
