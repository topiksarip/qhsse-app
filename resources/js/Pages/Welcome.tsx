import ApplicationLogoImage from '@/Components/ApplicationLogoImage';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import ThemeToggle from '@/Components/UI/ThemeToggle';
import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';

const features = [
    {
        title: 'Insiden & Investigasi',
        desc: 'Lapor insiden, near-miss, dan temuan; telusuri root cause dengan alur investigasi terstruktur.',
        icon: (
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v4m0 4h.01M10.3 3.86l-8.1 14a1.5 1.5 0 001.3 2.25h16.2a1.5 1.5 0 001.3-2.25l-8.1-14a1.5 1.5 0 00-2.6 0z" />
        ),
    },
    {
        title: 'CAPA & Tindakan',
        desc: 'Kelola corrective & preventive action dengan tenggat, penanggung jawab, dan bukti penyelesaian.',
        icon: (
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        ),
    },
    {
        title: 'Inspeksi & Audit',
        desc: 'Jalankan checklist inspeksi area kerja dan audit kepatuhan dengan temuan yang dapat ditindaklanjuti.',
        icon: (
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
        ),
    },
    {
        title: 'Dokumen & Kepatuhan',
        desc: 'Kontrol dokumen, izin kerja, pelatihan, dan kepatuhan lingkungan dalam satu platform teraudit.',
        icon: (
            <path strokeLinecap="round" strokeLinejoin="round" d="M7 21h10a2 2 0 002-2V9.4l-5.6-5.4H7a2 2 0 00-2 2V19a2 2 0 002 2z" />
        ),
    },
];

export default function Welcome({ auth }: PageProps) {
    const isAuthed = !!auth.user;

    return (
        <>
            <Head title="QHSSE Management System" />

            <div className="flex min-h-screen flex-col bg-slate-50 text-slate-900 dark:bg-gray-950 dark:text-gray-100">
                <header className="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur dark:border-gray-800 dark:bg-gray-900/90">
                    <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
                        <Link href="/" className="flex items-center gap-2">
                            <ApplicationLogoImage className="block h-8 w-auto" />
                            <span className="text-sm font-bold uppercase tracking-[0.24em] text-slate-700 dark:text-slate-200">
                                QHSSE
                            </span>
                        </Link>

                        <nav className="flex items-center gap-2 sm:gap-3">
                            <ThemeToggle />
                            {isAuthed ? (
                                <Link href={route('dashboard')}>
                                    <PrimaryButton size="sm">Dashboard</PrimaryButton>
                                </Link>
                            ) : (
                                <>
                                    <Link href={route('login')} className="hidden sm:block">
                                        <SecondaryButton size="sm">Masuk</SecondaryButton>
                                    </Link>
                                    <Link href={route('register')}>
                                        <PrimaryButton size="sm">Daftar</PrimaryButton>
                                    </Link>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-6xl flex-1 px-4 sm:px-6">
                    {/* Hero */}
                    <section className="grid items-center gap-8 py-14 sm:py-20 lg:grid-cols-2">
                        <div>
                            <span className="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300">
                                Quality · Health · Safety · Security · Environment
                            </span>
                            <h1 className="mt-4 text-3xl font-bold leading-tight text-slate-900 dark:text-white sm:text-4xl">
                                Sistem Manajemen QHSSE Terpadu
                            </h1>
                            <p className="mt-4 max-w-prose text-sm leading-relaxed text-slate-600 dark:text-slate-300 sm:text-base">
                                Kelola laporan insiden, investigasi, tindakan perbaikan, inspeksi, audit, dan
                                kepatuhan lingkungan dalam satu platform yang terstruktur, terdokumentasi, dan
                                dapat diaudit.
                            </p>
                            <div className="mt-6 flex flex-wrap gap-3">
                                {isAuthed ? (
                                    <Link href={route('dashboard')}>
                                        <PrimaryButton>Buka Dashboard</PrimaryButton>
                                    </Link>
                                ) : (
                                    <>
                                        <Link href={route('login')}>
                                            <PrimaryButton>Masuk</PrimaryButton>
                                        </Link>
                                        <Link href={route('register')}>
                                            <SecondaryButton>Daftar</SecondaryButton>
                                        </Link>
                                    </>
                                )}
                            </div>
                        </div>

                        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-8">
                            <div className="flex items-center gap-3">
                                <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-600 text-white dark:bg-emerald-500">
                                    <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.8">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7l8-4z" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 className="text-base font-semibold text-slate-800 dark:text-slate-100">Teraudit & Terkendali</h2>
                                    <p className="text-xs text-slate-500 dark:text-slate-400">Setiap tindakan tercatat dengan jejak audit.</p>
                                </div>
                            </div>
                            <dl className="mt-6 grid grid-cols-2 gap-4 text-sm">
                                <div className="rounded-lg bg-slate-50 p-3 dark:bg-gray-800">
                                    <dt className="text-xs text-slate-500 dark:text-slate-400">Modul</dt>
                                    <dd className="mt-1 text-lg font-semibold text-slate-800 dark:text-slate-100">12+</dd>
                                </div>
                                <div className="rounded-lg bg-slate-50 p-3 dark:bg-gray-800">
                                    <dt className="text-xs text-slate-500 dark:text-slate-400">Alur Kerja</dt>
                                    <dd className="mt-1 text-lg font-semibold text-slate-800 dark:text-slate-100">Terstandar</dd>
                                </div>
                                <div className="rounded-lg bg-slate-50 p-3 dark:bg-gray-800">
                                    <dt className="text-xs text-slate-500 dark:text-slate-400">Akses</dt>
                                    <dd className="mt-1 text-lg font-semibold text-slate-800 dark:text-slate-100">Berbasis Peran</dd>
                                </div>
                                <div className="rounded-lg bg-slate-50 p-3 dark:bg-gray-800">
                                    <dt className="text-xs text-slate-500 dark:text-slate-400">Bukti</dt>
                                    <dd className="mt-1 text-lg font-semibold text-slate-800 dark:text-slate-100">Terkelola</dd>
                                </div>
                            </dl>
                        </div>
                    </section>

                    {/* Features */}
                    <section className="pb-16">
                        <h2 className="text-lg font-semibold text-slate-800 dark:text-slate-100">Yang dapat Anda kelola</h2>
                        <div className="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {features.map((f) => (
                                <div
                                    key={f.title}
                                    className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-gray-900"
                                >
                                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-300">
                                        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.8">
                                            {f.icon}
                                        </svg>
                                    </div>
                                    <h3 className="mt-3 text-sm font-semibold text-slate-800 dark:text-slate-100">{f.title}</h3>
                                    <p className="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{f.desc}</p>
                                </div>
                            ))}
                        </div>
                    </section>
                </main>

                <footer className="border-t border-slate-200 py-6 text-center text-xs text-slate-400 dark:border-gray-800 dark:text-slate-500">
                    &copy; {new Date().getFullYear()} QHSSE Management System. All rights reserved.
                </footer>
            </div>
        </>
    );
}
