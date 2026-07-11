import { TrainingRecord } from '@/types';
import { Link } from '@inertiajs/react';

interface MatrixCellProps {
    record?: TrainingRecord;
    employeeId: number;
    programId: number;
}

export default function MatrixCell({ record, employeeId, programId }: MatrixCellProps) {
    if (!record) {
        // Not Started
        return (
            <div
                className="
                    p-2 text-center rounded
                    bg-gray-100 dark:bg-gray-700/30
                    text-gray-400 dark:text-gray-500
                "
                title="No training record"
            >
                ⚪
            </div>
        );
    }

    // Determine color and icon based on status
    let bgClass = '';
    let icon = '';
    let tooltip = '';

    switch (record.status) {
        case 'completed':
            // Check if expired
            if (record.expiry_date) {
                const expiry = new Date(record.expiry_date);
                const now = new Date();
                if (expiry < now) {
                    // Expired
                    bgClass = 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300';
                    icon = '🔴';
                    tooltip = `Expired on ${record.expiry_date}. Please re-schedule.`;
                } else {
                    // Valid
                    bgClass = 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300';
                    icon = '🟢';
                    tooltip = `Completed on ${record.end_date || record.start_date}. Expires ${record.expiry_date}.`;
                }
            } else {
                // Completed without expiry
                bgClass = 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300';
                icon = '🟢';
                tooltip = `Completed on ${record.end_date || record.start_date}.`;
            }
            break;

        case 'in_progress':
            bgClass = 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300';
            icon = '🟡';
            tooltip = `Started ${record.start_date}. In progress.`;
            break;

        case 'scheduled':
            bgClass = 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300';
            icon = '🔵';
            tooltip = `Scheduled for ${record.start_date}.`;
            break;

        case 'expired':
            bgClass = 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300';
            icon = '🔴';
            tooltip = `Expired on ${record.expiry_date}. Please re-schedule.`;
            break;

        case 'cancelled':
            bgClass = 'bg-gray-100 dark:bg-gray-700/30 text-gray-500 dark:text-gray-400';
            icon = '⚪';
            tooltip = 'Cancelled';
            break;

        default:
            bgClass = 'bg-gray-100 dark:bg-gray-700/30 text-gray-500 dark:text-gray-400';
            icon = '⚪';
            tooltip = record.status;
    }

    return (
        <Link
            href={route('training.records.show', record.id)}
            className={`
                block p-2 text-center rounded
                transition-all duration-200
                hover:ring-2 hover:ring-indigo-500
                cursor-pointer
                ${bgClass}
            `}
            title={tooltip}
        >
            {icon}
        </Link>
    );
}
