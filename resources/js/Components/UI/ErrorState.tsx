/**
 * ErrorState Component
 * 
 * Displays error state with retry action.
 * Use when data fetching fails to provide clear feedback and recovery option.
 */

import { ReactNode } from 'react';

type ErrorStateProps = {
    title?: string;
    message: string;
    retry?: () => void;
    action?: ReactNode;
    className?: string;
};

export default function ErrorState({
    title = 'Error Loading Data',
    message,
    retry,
    action,
    className = '',
}: ErrorStateProps) {
    return (
        <div
            className={`text-center py-12 ${className}`}
            role="alert"
            aria-live="assertive"
        >
            <svg
                className="mx-auto h-12 w-12 text-red-500 dark:text-red-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                aria-hidden="true"
            >
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                />
            </svg>
            <h3 className="mt-4 text-sm font-medium text-slate-900 dark:text-white">
                {title}
            </h3>
            <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">
                {message}
            </p>
            {(retry || action) && (
                <div className="mt-6">
                    {retry && (
                        <button
                            onClick={retry}
                            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-emerald-500 transition"
                        >
                            Try Again
                        </button>
                    )}
                    {action}
                </div>
            )}
        </div>
    );
}
