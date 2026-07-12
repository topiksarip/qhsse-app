import { PermitChecklist } from '@/types';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface ChecklistItemProps {
    item: PermitChecklist;
    permitId: number;
    canSign: boolean;
}

export default function ChecklistItem({ item, permitId, canSign }: ChecklistItemProps) {
    const [processing, setProcessing] = useState(false);

    function toggle() {
        setProcessing(true);
        router.post(
            route('permit.work.checklist.sign', permitId),
            { checklist_id: item.id, is_checked: !item.is_checked },
            {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            },
        );
    }

    return (
        <div className="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <div className="flex items-start gap-3">
                <input
                    type="checkbox"
                    checked={item.is_checked}
                    disabled={!canSign || processing}
                    onChange={toggle}
                    className="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-50"
                />
                <div className="flex-1">
                    <p className={`text-sm ${item.is_checked ? 'text-gray-500 line-through dark:text-gray-400' : 'text-gray-900 dark:text-gray-100'}`}>
                        {item.item_text}
                    </p>
                    {item.is_checked && item.checker && item.checked_at && (
                        <p className="mt-1 text-xs text-gray-400 dark:text-gray-500">
                            Signed by: {item.checker.name} — {new Date(item.checked_at).toLocaleString('id-ID')}
                        </p>
                    )}
                    {canSign && !item.is_checked && (
                        <button
                            onClick={toggle}
                            disabled={processing}
                            className="mt-2 inline-flex items-center rounded-md bg-indigo-600 px-3 py-1 text-xs font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            ✍ Tanda Tangani
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}
