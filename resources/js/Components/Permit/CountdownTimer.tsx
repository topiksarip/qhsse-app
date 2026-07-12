import { useEffect, useState } from 'react';

interface CountdownTimerProps {
    endDatetime: string;
}

function fmt(ms: number): string {
    if (ms <= 0) return '00:00:00';
    const total = Math.floor(ms / 1000);
    const h = Math.floor(total / 3600);
    const m = Math.floor((total % 3600) / 60);
    const s = total % 60;
    return [h, m, s].map((v) => String(v).padStart(2, '0')).join(':');
}

export default function CountdownTimer({ endDatetime }: CountdownTimerProps) {
    const end = new Date(endDatetime).getTime();
    const [now, setNow] = useState(Date.now());

    useEffect(() => {
        const t = setInterval(() => setNow(Date.now()), 1000);
        return () => clearInterval(t);
    }, []);

    const remaining = end - now;
    const totalDuration = 100; // relative visual only
    const elapsed = Math.min(100, Math.max(0, 100 - (remaining / (1000 * 60 * 60 * 8)) * 100));
    const pct = Math.round(elapsed);

    return (
        <div className="rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
            <p className="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">⏰ Berakhir dalam</p>
            <p className="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">{fmt(remaining)}</p>
            <div className="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                <div className={`h-full ${pct > 80 ? 'bg-red-500' : pct > 60 ? 'bg-yellow-500' : 'bg-green-500'}`} style={{ width: `${pct}%` }} />
            </div>
            <p className="mt-1 text-xs text-gray-400">{pct}% berlalu</p>
        </div>
    );
}
