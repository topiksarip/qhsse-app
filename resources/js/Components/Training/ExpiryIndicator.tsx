import { differenceInDays, parseISO, format } from 'date-fns';

interface ExpiryIndicatorProps {
    expiryDate?: string;
    status: 'scheduled' | 'in_progress' | 'completed' | 'expired' | 'cancelled';
}

export default function ExpiryIndicator({ expiryDate, status }: ExpiryIndicatorProps) {
    if (!expiryDate) {
        return <span className="text-gray-400 dark:text-gray-500">—</span>;
    }

    const expiry = parseISO(expiryDate);
    const now = new Date();
    const daysRemaining = differenceInDays(expiry, now);

    // Format tanggal
    const formattedDate = format(expiry, 'dd/MM/yyyy');

    // Tentukan warna dan status text
    let colorClass = '';
    let statusText = '';
    let icon = '';

    if (status === 'expired' || daysRemaining < 0) {
        // Expired
        colorClass = 'text-red-600 dark:text-red-400 font-semibold';
        statusText = `Kedaluwarsa ${Math.abs(daysRemaining)} hari yang lalu`;
        icon = '🔴';
    } else if (daysRemaining === 0) {
        // Expires today
        colorClass = 'text-amber-600 dark:text-amber-400 font-semibold';
        statusText = 'Kedaluwarsa hari ini!';
        icon = '⚠️';
    } else if (daysRemaining <= 30) {
        // Expiring soon (within 30 days)
        colorClass = 'text-amber-600 dark:text-amber-400 font-medium';
        statusText = `${daysRemaining} hari lagi`;
        icon = '🟠';
    } else if (daysRemaining <= 90) {
        // Expiring medium term (30-90 days)
        colorClass = 'text-yellow-600 dark:text-yellow-400';
        statusText = `${daysRemaining} hari lagi`;
        icon = '🟡';
    } else {
        // Valid for long time (>90 days)
        colorClass = 'text-green-600 dark:text-green-400';
        statusText = `${daysRemaining} hari lagi`;
        icon = '🟢';
    }

    return (
        <div className="flex flex-col items-center gap-0.5">
            <span className="text-sm text-gray-700 dark:text-gray-300">{formattedDate}</span>
            <span className={`text-xs ${colorClass} flex items-center gap-1`}>
                <span>{icon}</span>
                <span>{statusText}</span>
            </span>
        </div>
    );
}
