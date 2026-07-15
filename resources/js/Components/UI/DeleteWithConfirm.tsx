import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import Modal from '@/Components/Modal';
import DangerButton from '@/Components/DangerButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface DeleteWithConfirmProps {
    routeName: string;
    id: number | string;
    permission: string;
    itemLabel?: string;
    /** Optional route to redirect to after successful deletion (e.g. index). If omitted, stays on current page. */
    redirectTo?: string;
    /** Extra inertia options passed to router.delete (e.g. onSuccess). */
    deleteOptions?: Record<string, unknown>;
    className?: string;
    /** Render as a compact text link (for table action columns). */
    asLink?: boolean;
    children?: React.ReactNode;
}

export default function DeleteWithConfirm({
    routeName,
    id,
    permission,
    itemLabel,
    redirectTo,
    deleteOptions,
    className = '',
    asLink = false,
    children,
}: DeleteWithConfirmProps) {
    const { auth } = usePage<PageProps>().props;
    const permissions = new Set<string>(auth.permissions);

    const [confirming, setConfirming] = useState(false);
    const [processing, setProcessing] = useState(false);

    if (!permissions.has(permission)) {
        return null;
    }

    const open = () => setConfirming(true);
    const close = () => setConfirming(false);

    const handleDelete = () => {
        setProcessing(true);
        router.delete(route(routeName, id), {
            preserveScroll: true,
            ...deleteOptions,
            onSuccess: (...args: unknown[]) => {
                setProcessing(false);
                setConfirming(false);
                if (redirectTo) {
                    router.visit(route(redirectTo));
                }
                const caller = deleteOptions?.onSuccess as ((...a: unknown[]) => void) | undefined;
                caller?.(...args);
            },
            onError: () => setProcessing(false),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <>
            {asLink ? (
                <button type="button" onClick={open} className={`text-red-600 hover:text-red-900 ${className}`}>
                    {children ?? 'Delete'}
                </button>
            ) : (
                <DangerButton type="button" onClick={open} className={className}>
                    {children ?? 'Delete'}
                </DangerButton>
            )}

            <Modal show={confirming} onClose={close} maxWidth="md">
                <div className="p-6">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Konfirmasi Hapus</h3>
                    <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        {itemLabel
                            ? `Apakah Anda yakin ingin menghapus "${itemLabel}"? Tindakan ini tidak dapat dibatalkan.`
                            : 'Apakah Anda yakin ingin menghapus item ini? Tindakan ini tidak dapat dibatalkan.'}
                    </p>
                    <div className="mt-6 flex justify-end gap-2">
                        <SecondaryButton onClick={close} disabled={processing}>
                            Batal
                        </SecondaryButton>
                        <DangerButton onClick={handleDelete} disabled={processing}>
                            {processing ? 'Menghapus...' : 'Hapus'}
                        </DangerButton>
                    </div>
                </div>
            </Modal>
        </>
    );
}
