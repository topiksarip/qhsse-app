import ApplicationLogo from '@/Components/ApplicationLogo';
import ThemeToggle from '@/Components/UI/ThemeToggle';
import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="flex min-h-screen flex-col bg-slate-100 text-slate-900 dark:bg-gray-950 dark:text-gray-100">
            <header className="flex items-center justify-between px-4 py-4 sm:px-6">
                <Link href="/" className="flex items-center gap-2">
                    <ApplicationLogo className="block h-8 w-auto fill-current text-slate-800 dark:text-slate-200" />
                    <span className="text-sm font-bold uppercase tracking-[0.24em] text-slate-700 dark:text-slate-200">
                        QHSSE
                    </span>
                </Link>
                <ThemeToggle />
            </header>

            <main className="flex flex-1 items-center justify-center px-4 py-6 sm:py-10">
                <div className="w-full max-w-md overflow-hidden rounded-xl border border-slate-200 bg-white px-6 py-8 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:px-8">
                    {children}
                </div>
            </main>

            <footer className="py-6 text-center text-xs text-slate-400 dark:text-slate-500">
                &copy; {new Date().getFullYear()} QHSSE Management System
            </footer>
        </div>
    );
}
