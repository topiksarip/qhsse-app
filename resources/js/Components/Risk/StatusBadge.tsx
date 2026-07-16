import { RiskRegisterStatus } from '@/types';

const config: Record<RiskRegisterStatus, { label: string; color: string }> = {
    identified: { label: 'Teridentifikasi', color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' },
    assessed: { label: 'Dinilai', color: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200' },
    controls_needed: { label: 'Perlu Kontrol', color: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' },
    controls_in_place: { label: 'Kontrol Terpasang', color: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200' },
    monitored: { label: 'Dipantau', color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' },
    obsolete: { label: 'Tidak Berlaku', color: 'bg-gray-300 text-gray-700 dark:bg-gray-600 dark:text-gray-200' },
};

export default function StatusBadge({ status }: { status: RiskRegisterStatus }) {
    const c = config[status] ?? config.identified;
    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${c.color}`}>
            {c.label}
        </span>
    );
}
