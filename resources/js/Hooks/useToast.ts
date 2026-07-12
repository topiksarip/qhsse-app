/**
 * useToast Hook
 * 
 * Global toast notification hook.
 * Usage:
 * 
 * const toast = useToast();
 * toast.success('Operation completed!');
 * toast.error('Failed to save', 'Please try again');
 */

import { Toast, ToastType } from '@/Components/UI/ToastContainer';
import { router } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';

let toastIdCounter = 0;
const listeners = new Set<(toasts: Toast[]) => void>();
let globalToasts: Toast[] = [];

function notifyListeners() {
    listeners.forEach((listener) => listener([...globalToasts]));
}

function addToast(toast: Toast) {
    globalToasts.push(toast);
    notifyListeners();
}

function removeToast(id: string) {
    globalToasts = globalToasts.filter((t) => t.id !== id);
    notifyListeners();
}

export function useToast() {
    const [toasts, setToasts] = useState<Toast[]>(globalToasts);

    useEffect(() => {
        listeners.add(setToasts);
        return () => {
            listeners.delete(setToasts);
        };
    }, []);

    const show = useCallback((type: ToastType, title: string, message?: string, duration = 5000) => {
        const id = `toast-${++toastIdCounter}`;
        addToast({ id, type, title, message, duration });
        return id;
    }, []);

    const dismiss = useCallback((id: string) => {
        removeToast(id);
    }, []);

    const success = useCallback((title: string, message?: string, duration?: number) => {
        return show('success', title, message, duration);
    }, [show]);

    const error = useCallback((title: string, message?: string, duration?: number) => {
        return show('error', title, message, duration);
    }, [show]);

    const warning = useCallback((title: string, message?: string, duration?: number) => {
        return show('warning', title, message, duration);
    }, [show]);

    const info = useCallback((title: string, message?: string, duration?: number) => {
        return show('info', title, message, duration);
    }, [show]);

    return {
        toasts,
        show,
        dismiss,
        success,
        error,
        warning,
        info,
    };
}

// Auto-dismiss Inertia flash messages as toasts
if (typeof window !== 'undefined') {
    router.on('finish', () => {
        const page = (router as any).page;
        if (!page?.props?.flash) return;

        const { success, error, warning, info } = page.props.flash;

        if (success) {
            const id = `toast-${++toastIdCounter}`;
            addToast({ id, type: 'success', title: success, duration: 5000 });
        }

        if (error) {
            const id = `toast-${++toastIdCounter}`;
            addToast({ id, type: 'error', title: error, duration: 7000 });
        }

        if (warning) {
            const id = `toast-${++toastIdCounter}`;
            addToast({ id, type: 'warning', title: warning, duration: 6000 });
        }

        if (info) {
            const id = `toast-${++toastIdCounter}`;
            addToast({ id, type: 'info', title: info, duration: 5000 });
        }
    });
}
