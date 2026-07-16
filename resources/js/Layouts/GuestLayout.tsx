import ApplicationLogoImage from '@/Components/ApplicationLogoImage';
import ThemeToggle from '@/Components/UI/ThemeToggle';
import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="relative flex min-h-screen flex-col text-slate-900 dark:text-gray-100">
            {/* Branded background: photo (40%) + subtle natural surface texture + translucent scrim */}
            <div
                className="fixed inset-0 -z-20 bg-cover bg-center opacity-40"
                style={{ backgroundImage: "url('/img/websamudera-bg.jpg')" }}
            />
            <div className="fixed inset-0 -z-10 bg-white/65 bg-[radial-gradient(circle_at_1px_1px,rgba(15,23,42,0.07)_1px,transparent_0)] bg-[size:22px_22px] dark:bg-gray-950/80 dark:bg-[radial-gradient(circle_at_1px_1px,rgba(226,232,240,0.06)_1px,transparent_0)]" />

            <header className="flex items-center justify-between border-b border-[#fdb913]/50 bg-[#fdb913]/80 px-4 py-4 backdrop-blur sm:px-6">
                <Link href="/" className="flex items-center gap-2">
                    <ApplicationLogoImage className="block h-8 w-auto" />
                </Link>
                <ThemeToggle className="!border-slate-900/30 !text-slate-900 !hover:bg-slate-900/10" />
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
