import { LegalComplianceStatus } from '@/types';

const config: Record<LegalComplianceStatus, { label: string; color: string }> = {
    compliant: { label: 'Patuh', color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' },
    non_compliant: { label: 'Tidak Patuh', color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
    in_progress: { label: 'Dalam Proses', color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' },
    not_applicable: { label: 'Tidak Berlaku', color: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' },
};

export default function ComplianceStatusBadge({ status }: { status: LegalComplianceStatus }) {
    const c = config[status] ?? config.not_applicable;
    return <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${c.color}`}>{c.label}</span>;
}
