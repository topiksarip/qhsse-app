import { LegalRegisterStatus } from '@/types';

const config: Record<LegalRegisterStatus, { label: string; color: string }> = {
    active: { label: 'Aktif', color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' },
    inactive: { label: 'Tidak Aktif', color: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' },
};

export default function RegisterStatusBadge({ status }: { status: LegalRegisterStatus }) {
    const c = config[status] ?? config.active;
    return <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${c.color}`}>{c.label}</span>;
}
