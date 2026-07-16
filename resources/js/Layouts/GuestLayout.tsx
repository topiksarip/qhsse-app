import ApplicationLogoImage from '@/Components/ApplicationLogoImage';
import ThemeToggle from '@/Components/UI/ThemeToggle';
import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="flex min-h-screen flex-col bg-slate-100 text-slate-900 dark:bg-gray-950 dark:text-gray-100">
            <header className="flex items-center justify-between px-4 py-4 sm:px-6">
                <Link href="/" className="flex items-center gap-2">
                    <ApplicationLogoImage className="block h-8 w-auto" />
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
