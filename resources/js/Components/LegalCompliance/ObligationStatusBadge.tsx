import { LegalObligation } from '@/types';

export function getObligationStatus(o: LegalObligation): 'completed' | 'overdue' | 'due_soon' | 'pending' {
    if (o.status === 'completed') return 'completed';
    if (o.next_due && new Date(o.next_due) < new Date()) return 'overdue';
    if (o.next_due && new Date(o.next_due) <= new Date(Date.now() + 7 * 24 * 60 * 60 * 1000)) return 'due_soon';
    return 'pending';
}

const config: Record<string, { label: string; color: string }> = {
    completed: { label: 'Selesai', color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' },
    overdue: { label: 'Terlambat', color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
    due_soon: { label: 'Segera', color: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' },
    pending: { label: 'Menunggu', color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' },
};

export default function ObligationStatusBadge({ obligation }: { obligation: LegalObligation }) {
    const key = getObligationStatus(obligation);
    const c = config[key];
    return <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${c.color}`}>{c.label}</span>;
}
