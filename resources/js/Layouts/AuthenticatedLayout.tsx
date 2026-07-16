import Dropdown from '@/Components/Dropdown';
import Sidebar from '@/Components/UI/Sidebar';
import ThemeToggle from '@/Components/UI/ThemeToggle';
import ToastContainer from '@/Components/UI/ToastContainer';
import { useToast } from '@/Hooks/useToast';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode, useState } from 'react';

export default function Authenticated({ header, children }: PropsWithChildren<{ header?: ReactNode }>) {
    const { auth } = usePage<PageProps>().props;
    const user = auth.user;
    const { toasts, dismiss } = useToast();
    const [sidebarOpen, setSidebarOpen] = useState(false);

    return (
        <div className="relative min-h-screen text-slate-900 dark:text-gray-100">
            {/* Branded background: photo (40%) + translucent scrim */}
            <div
                className="fixed inset-0 -z-20 bg-cover bg-center opacity-40"
                style={{ backgroundImage: "url('/img/websamudera-bg.jpg')" }}
            />
            <div className="fixed inset-0 -z-10 bg-white/40 dark:bg-gray-950/70" />

            <ToastContainer toasts={toasts} onDismiss={dismiss} />

            <Sidebar open={sidebarOpen} onClose={() => setSidebarOpen(false)} />

            {/* Top bar */}
            <div className="sticky top-0 z-30 border-b border-[#fdb913]/50 bg-[#fdb913]/80 backdrop-blur dark:border-[#fdb913]/50 dark:bg-[#fdb913]/80">
                <div className="flex h-16 items-center gap-3 px-4 sm:px-6">
                    <button
                        type="button"
                        onClick={() => setSidebarOpen(true)}
                        aria-label="Buka navigasi"
                        aria-expanded={sidebarOpen}
                        className="inline-flex h-9 w-9 items-center justify-center rounded-md text-slate-900 transition hover:bg-slate-900/10 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 dark:text-slate-900 dark:hover:bg-slate-900/10 dark:hover:text-slate-900"
                    >
                        <svg className="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <div className="min-w-0 flex-1 truncate text-[16px] font-semibold text-slate-900 dark:text-slate-900">
                        QHSSE Management System
                    </div>

                    <ThemeToggle className="!border-slate-900/30 !text-slate-900 !hover:bg-slate-900/10" />

                    <div className="relative">
                        <Dropdown>
                            <Dropdown.Trigger>
                                <span className="inline-flex rounded-md">
                                    <button
                                        type="button"
                                        className="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-slate-500 transition hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 dark:bg-gray-900 dark:text-slate-400 dark:hover:text-slate-200"
                                    >
                                        {user.name}
                                        <svg className="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
                                        </svg>
                                    </button>
                                </span>
                            </Dropdown.Trigger>
                            <Dropdown.Content>
                                <Dropdown.Link href={route('profile.edit')}>Profile</Dropdown.Link>
                                <Dropdown.Link href={route('logout')} method="post" as="button">
                                    Log Out
                                </Dropdown.Link>
                            </Dropdown.Content>
                        </Dropdown>
                    </div>
                </div>
            </div>

            {header && (
                <header className="border-b border-slate-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                    <div className="px-4 py-5 sm:px-6 lg:px-8">
                        <div className="text-xl font-semibold text-slate-800 dark:text-slate-100">{header}</div>
                    </div>
                </header>
            )}

            <main className="px-4 py-6 sm:px-6 lg:px-8">{children}</main>
        </div>
    );
}
