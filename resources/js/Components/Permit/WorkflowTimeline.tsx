interface WorkflowStep {
    id: number;
    from_status: string | null;
    to_status: string;
    action_key: string;
    action_label: string;
    reason: string | null;
    actor_name: string | null;
    created_at: string;
}

const statusLabels: Record<string, string> = {
    draft: 'Draft',
    submitted: 'Submitted',
    under_review: 'Under Review',
    approved: 'Approved',
    active: 'Active',
    closed: 'Closed',
    rejected: 'Rejected',
};

export default function WorkflowTimeline({ history }: { history: WorkflowStep[] }) {
    if (history.length === 0) {
        return (
            <p className="text-sm text-gray-500 dark:text-gray-400">Belum ada riwayat workflow.</p>
        );
    }

    return (
        <ol className="space-y-3">
            {history.map((step) => (
                <li key={step.id} className="flex gap-3">
                    <span className="mt-1 flex h-3 w-3 shrink-0 rounded-full bg-indigo-500" />
                    <div className="flex-1">
                        <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {step.action_label}
                        </p>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            {step.actor_name ?? '-'} · {new Date(step.created_at).toLocaleString('id-ID')}
                        </p>
                        {step.reason && (
                            <p className="mt-1 text-xs italic text-gray-400 dark:text-gray-500">
                                Alasan: {step.reason}
                            </p>
                        )}
                    </div>
                </li>
            ))}
        </ol>
    );
}
