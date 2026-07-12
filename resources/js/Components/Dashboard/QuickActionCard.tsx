/**
 * QuickActionCard Component
 * 
 * Action card with icon, title, description, and optional badge.
 * Use for prominent action buttons in hero areas.
 */

import { Link } from '@inertiajs/react';
import { ReactNode } from 'react';

type QuickActionCardProps = {
    label: string;
    description?: string;
    route: string;
    icon: ReactNode;
    badge?: number | string;
    badgeColor?: 'red' | 'amber' | 'emerald' | 'blue';
    className?: string;
};

const badgeColors = {
    red: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    amber: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    emerald: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    blue: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
};

export default function QuickActionCard({
    label,
    description,
    route,
    icon,
    badge,
    badgeColor = 'emerald',
    className = '',
}: QuickActionCardProps) {
    return (
        <Link
            href={route}
            className={`group relative flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4 transition hover:border-emerald-500 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-emerald-400 ${className}`}
        >
            {/* Icon & Badge */}
            <div className="flex items-start justify-between">
                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 transition group-hover:bg-emerald-600 group-hover:text-white dark:bg-emerald-900/30 dark:text-emerald-400 dark:group-hover:bg-emerald-600 dark:group-hover:text-white">
                    {icon}
                </div>
                {badge !== undefined && (
                    <span
                        className={`rounded-full px-2 py-0.5 text-xs font-semibold ${badgeColors[badgeColor]}`}
                        aria-label={`${badge} items`}
                    >
                        {badge}
                    </span>
                )}
            </div>

            {/* Label & Description */}
            <div>
                <h3 className="text-sm font-semibold text-slate-900 group-hover:text-emerald-700 dark:text-white dark:group-hover:text-emerald-300">
                    {label}
                </h3>
                {description && (
                    <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {description}
                    </p>
                )}
            </div>
        </Link>
    );
}
