import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps, RiskRegister } from '@/types';
import TypeBadge from '@/Components/Risk/TypeBadge';
import StatusBadge from '@/Components/Risk/StatusBadge';
import RiskLevelBadge from '@/Components/Risk/RiskLevelBadge';
import RiskMatrixGrid from '@/Components/Risk/RiskMatrixGrid';

interface ShowProps extends PageProps {
    riskRegister: RiskRegister & {
        comments?: { id: number; content: string; internal: boolean; created_at: string; author?: { name: string } }[];
        activities?: { id: number; description: string; created_at: string; actor?: { name: string } }[];
    };
}

const probabilityLabels: Record<number, string> = {
    1: 'Jarang',
    2: 'Tidak Mungkin',
    3: 'Mungkin',
    4: 'Kemungkinan Besar',
    5: 'Hampir Pasti',
};

export default function Show({ auth, riskRegister }: ShowProps) {
    const permissions = new Set(auth.permissions ?? []);
    const canUpdate = permissions.has('risk.registers.update');
    const canAssess = permissions.has('risk.registers.assess');
    const status = riskRegister.status;
    const canEdit = canUpdate && status !== 'obsolete';

    function postAction(action: string) {
        router.post(route(`risk.registers.${action}`, riskRegister.id), {}, { preserveScroll: true });
    }

    const allSeverities = [riskRegister.severity, riskRegister.residualSeverity].filter(Boolean) as any[];
    const allLevels = [riskRegister.riskLevel, riskRegister.residualRiskLevel].filter(Boolean) as any[];

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <Link href={route('risk.registers.index')} className="text-sm text-indigo-600 hover:underline dark:text-indigo-400">← Kembali ke Daftar</Link>
                    <h2 className="mt-1 text-xl font-semibold text-gray-800 dark:text-gray-200">Risk Register</h2>
                </div>
            }
        >
            <Head title={`Risk Register ${riskRegister.register_number}`} />
            <div className="py-6">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="font-mono text-lg font-semibold text-gray-900 dark:text-gray-100">{riskRegister.register_number}</span>
                                    <TypeBadge type={riskRegister.type} />
                                    <RiskLevelBadge level={riskRegister.riskLevel} />
                                    <StatusBadge status={status} />
                                </div>
                                <h3 className="mt-2 text-lg font-medium text-gray-900 dark:text-gray-100">{riskRegister.title}</h3>
                            </div>
                            {canEdit && (
                                <Link href={route('risk.registers.edit', riskRegister.id)} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">
                                    ✏ Edit
                                </Link>
                            )}
                        </div>
                    </div>

                    <div className="mt-6 space-y-6">
                        <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                            <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Informasi Umum</h3>
                            <dl className="grid grid-cols-1 gap-3 border-t border-gray-200 pt-4 text-sm sm:grid-cols-2 dark:border-gray-700">
                                <Row label="Site" value={riskRegister.site?.name ?? '-'} />
                                <Row label="Area" value={riskRegister.area?.name ?? '-'} />
                                <Row label="Department" value={riskRegister.department?.name ?? '-'} />
                                <Row label="Owner" value={riskRegister.owner?.name ?? '-'} />
                                <Row label="Aktivitas" value={riskRegister.activity} />
                                <Row label="Review Date" value={riskRegister.review_date ?? '-'} />
                                <div className="sm:col-span-2">
                                    <dt className="text-gray-500 dark:text-gray-400">Hazard</dt>
                                    <dd className="mt-1 whitespace-pre-wrap text-gray-900 dark:text-gray-100">{riskRegister.hazard}</dd>
                                </div>
                                {riskRegister.existing_controls && (
                                    <div className="sm:col-span-2">
                                        <dt className="text-gray-500 dark:text-gray-400">Existing Controls</dt>
                                        <dd className="mt-1 whitespace-pre-wrap text-gray-900 dark:text-gray-100">{riskRegister.existing_controls}</dd>
                                    </div>
                                )}
                            </dl>
                        </div>

                        <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                            <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Before / After Risk Comparison</h3>
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <ComparisonCard title="INITIAL RISK" subtitle="Sebelum Kontrol" level={riskRegister.riskLevel} severity={riskRegister.severity} probabilityId={riskRegister.probability_id} />
                                <ComparisonCard title="RESIDUAL RISK" subtitle="Setelah Kontrol" level={riskRegister.residualRiskLevel} severity={riskRegister.residualSeverity} probabilityId={riskRegister.residual_probability_id} />
                            </div>
                            {riskRegister.additional_controls && (
                                <div className="mt-4">
                                    <dt className="text-sm text-gray-500 dark:text-gray-400">Additional Controls</dt>
                                    <dd className="mt-1 whitespace-pre-wrap text-sm text-gray-900 dark:text-gray-100">{riskRegister.additional_controls}</dd>
                                </div>
                            )}
                        </div>

                        <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                            <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Risk Matrix Visualization</h3>
                            <div className="flex flex-wrap items-start gap-8">
                                <div>
                                    <p className="mb-1 text-xs text-gray-500 dark:text-gray-400">● Initial</p>
                                    <RiskMatrixGrid
                                        severities={allSeverities}
                                        matrixLevels={allLevels}
                                        selectedSeverityId={riskRegister.severity_id ?? null}
                                        selectedProbabilityId={riskRegister.probability_id ?? null}
                                    />
                                </div>
                                <div>
                                    <p className="mb-1 text-xs text-gray-500 dark:text-gray-400">● Residual</p>
                                    <RiskMatrixGrid
                                        severities={allSeverities}
                                        matrixLevels={allLevels}
                                        selectedSeverityId={riskRegister.residual_severity_id ?? null}
                                        selectedProbabilityId={riskRegister.residual_probability_id ?? null}
                                    />
                                </div>
                            </div>
                        </div>

                        {canAssess && (
                            <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                                <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status & Actions</h3>
                                <p className="mb-3 text-sm text-gray-600 dark:text-gray-400">Status saat ini: <StatusBadge status={status} /></p>
                                <div className="flex flex-wrap gap-2">
                                    {status === 'identified' && <ActionBtn label="Assess" onClick={() => postAction('assess')} />}
                                    {status === 'assessed' && <ActionBtn label="Needs Controls" onClick={() => postAction('needs_controls')} />}
                                    {status === 'controls_needed' && <ActionBtn label="Implement Controls" onClick={() => postAction('implement_controls')} />}
                                    {status === 'controls_in_place' && <ActionBtn label="Monitor" onClick={() => postAction('monitor')} />}
                                    {status !== 'obsolete' && <ActionBtn label="Obsolete" onClick={() => postAction('obsolete')} danger />}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function Row({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-gray-500 dark:text-gray-400">{label}</dt>
            <dd className="text-gray-900 dark:text-gray-100">{value}</dd>
        </div>
    );
}

function ComparisonCard({ title, subtitle, level, severity, probabilityId }: { title: string; subtitle: string; level: any; severity?: any; probabilityId?: number | null }) {
    return (
        <div className="rounded-lg border border-gray-200 p-4 text-center dark:border-gray-700">
            <p className="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{title}</p>
            <p className="mb-2 text-xs text-gray-400">{subtitle}</p>
            {level ? (
                <RiskLevelBadge level={level} />
            ) : (
                <span className="text-3xl">⬤ —</span>
            )}
            <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">Severity: {severity?.name ?? '-'}</p>
            <p className="text-sm text-gray-600 dark:text-gray-300">Probability: {probabilityId ? `${probabilityId} — ${probabilityLabels[probabilityId]}` : '-'}</p>
        </div>
    );
}

function ActionBtn({ label, onClick, danger }: { label: string; onClick: () => void; danger?: boolean }) {
    return (
        <button onClick={onClick} className={`rounded-md px-4 py-2 text-sm font-medium ${danger ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-indigo-600 text-white hover:bg-indigo-700'}`}>
            {label}
        </button>
    );
}
