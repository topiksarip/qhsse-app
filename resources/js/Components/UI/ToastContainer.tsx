/**
 * Toast Notification System
 * 
 * Global toast notification component with success, error, warning, and info variants.
 * Displays notifications in top-right corner with auto-dismiss and manual close.
 */

import { useEffect, useState } from 'react';

export type ToastType = 'success' | 'error' | 'warning' | 'info';

export type Toast = {
    id: string;
    type: ToastType;
    title: string;
    message?: string;
    duration?: number;
};

type ToastContainerProps = {
    toasts: Toast[];
    onDismiss: (id: string) => void;
};

const toastStyles: Record<ToastType, string> = {
    success: 'bg-emerald-50 border-emerald-200 text-emerald-900 dark:bg-emerald-900/20 dark:border-emerald-800 dark:text-emerald-100',
    error: 'bg-red-50 border-red-200 text-red-900 dark:bg-red-900/20 dark:border-red-800 dark:text-red-100',
    warning: 'bg-amber-50 border-amber-200 text-amber-900 dark:bg-amber-900/20 dark:border-amber-800 dark:text-amber-100',
    info: 'bg-blue-50 border-blue-200 text-blue-900 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-100',
};

const toastIcons: Record<ToastType, JSX.Element> = {
    success: (
        <svg className="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    ),
    error: (
        <svg className="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    ),
    warning: (
        <svg className="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
    ),
    info: (
        <svg className="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    ),
};

function ToastItem({ toast, onDismiss }: { toast: Toast; onDismiss: (id: string) => void }) {
    useEffect(() => {
        if (toast.duration === 0) return;

        const timer = setTimeout(() => {
            onDismiss(toast.id);
        }, toast.duration ?? 5000);

        return () => clearTimeout(timer);
    }, [toast.id, toast.duration, onDismiss]);

    return (
        <div
            className={`flex items-start gap-3 rounded-lg border p-4 shadow-lg transition-all ${toastStyles[toast.type]}`}
            role="alert"
            aria-live="assertive"
        >
            <div className="shrink-0" aria-hidden="true">
                {toastIcons[toast.type]}
            </div>
            <div className="flex-1 min-w-0">
                <h4 className="text-sm font-semibold">{toast.title}</h4>
                {toast.message && (
                    <p className="mt-1 text-sm opacity-90">{toast.message}</p>
                )}
            </div>
            <button
                onClick={() => onDismiss(toast.id)}
                className="shrink-0 rounded-md p-1 hover:bg-black/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-current dark:hover:bg-white/10"
                aria-label="Dismiss notification"
            >
                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    );
}

export default function ToastContainer({ toasts, onDismiss }: ToastContainerProps) {
    return (
        <div
            className="fixed top-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"
            aria-live="polite"
            aria-atomic="false"
        >
            {toasts.map((toast) => (
                <div key={toast.id} className="pointer-events-auto">
                    <ToastItem toast={toast} onDismiss={onDismiss} />
                </div>
            ))}
        </div>
    );
}
