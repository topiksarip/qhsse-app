/**
 * EmptyState Component
 * 
 * Displays user-friendly empty state with optional action.
 * Use when lists/tables have no data instead of showing blank space.
 */

import { ReactNode } from 'react';

type EmptyStateProps = {
    icon?: ReactNode;
    title: string;
    description: string;
    action?: ReactNode;
    className?: string;
};

export default function EmptyState({
    icon,
    title,
    description,
    action,
    className = '',
}: EmptyStateProps) {
    return (
        <div
            className={`text-center py-12 ${className}`}
            role="status"
        >
            {icon ? (
                <div className="mx-auto h-12 w-12 text-slate-400 dark:text-slate-600" aria-hidden="true">
                    {icon}
                </div>
            ) : (
                <svg
                    className="mx-auto h-12 w-12 text-slate-400 dark:text-slate-600"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    aria-hidden="true"
                >
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                    />
                </svg>
            )}
            <h3 className="mt-4 text-sm font-medium text-slate-900 dark:text-white">
                {title}
            </h3>
            <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">
                {description}
            </p>
            {action && (
                <div className="mt-6">
                    {action}
                </div>
            )}
        </div>
    );
}
