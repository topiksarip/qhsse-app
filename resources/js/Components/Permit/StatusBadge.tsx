import { PermitStatus } from '@/types';

const statusConfig: Record<PermitStatus, { label: string; icon: string; color: string }> = {
    draft: { label: 'Draft', icon: '⚪', color: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' },
    submitted: { label: 'Submitted', icon: '🔵', color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' },
    under_review: { label: 'Under Review', icon: '🟡', color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' },
    approved: { label: 'Approved', icon: '🟣', color: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200' },
    active: { label: 'Active', icon: '🟢', color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' },
    closed: { label: 'Closed', icon: '✅', color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' },
    rejected: { label: 'Rejected', icon: '🔴', color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
};

export default function StatusBadge({ status }: { status: PermitStatus }) {
    const config = statusConfig[status] || { label: status, icon: '', color: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' };
    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${config.color}`}>
            <span className="mr-1">{config.icon}</span>
            {config.label}
        </span>
    );
}
