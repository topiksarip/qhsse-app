import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import { PageProps, RiskRegister } from '@/types';
import TypeBadge from '@/Components/Risk/TypeBadge';
import StatusBadge from '@/Components/Risk/StatusBadge';
import RiskLevelBadge from '@/Components/Risk/RiskLevelBadge';
import RiskMatrixGrid from '@/Components/Risk/RiskMatrixGrid';

interface ShowProps extends PageProps {
    riskRegister: RiskRegister & {
        comments?: { id: number; content: string; internal: boolean; created_at: string; author?: { name: string } }[];
        activities?: { id: number; description: string; created_at: string; actor?: { name: string } }[];
        apdRequirements?: {
            id: number;
            apd_catalog_id: number;
            quantity: number;
            notes?: string | null;
            catalog?: { id: number; name: string; catalog_code?: string };
        }[];
    };
    apdCatalogs?: { id: number; name: string; catalog_code?: string; site_id?: number | null; department_id?: number | null }[];
    canManageApdRequirements?: boolean;
}

const probabilityLabels: Record<number, string> = {
    1: 'Jarang',
    2: 'Tidak Mungkin',
    3: 'Mungkin',
    4: 'Kemungkinan Besar',
    5: 'Hampir Pasti',
};

export default function Show({ auth, riskRegister, apdCatalogs = [], canManageApdRequirements = false }: ShowProps) {
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
<>                                <Link href={route('risk.registers.edit', riskRegister.id)} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">
                                    ✏ Edit
                                </Link>
                        <DeleteWithConfirm
                            routeName="risk.registers.destroy"
                            id={riskRegister.id}
                            permission="risk.registers.delete"
                            itemLabel={riskRegister.register_number}
                            redirectTo="risk.registers.index"
                            className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 dark:bg-red-700 dark:text-white"
                        >
                            Hapus
                        </DeleteWithConfirm></>
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

                        <ApdRequirementsPanel
                            riskRegister={riskRegister}
                            catalogs={apdCatalogs ?? []}
                            canManage={canManageApdRequirements ?? false}
                        />
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

interface ApdCatalogOption {
    id: number;
    name: string;
    catalog_code?: string;
    site_id?: number | null;
    department_id?: number | null;
}

function ApdRequirementsPanel({
    riskRegister,
    catalogs,
    canManage,
}: {
    riskRegister: RiskRegister & {
        apdRequirements?: {
            id: number;
            apd_catalog_id: number;
            quantity: number;
            notes?: string | null;
            catalog?: { id: number; name: string; catalog_code?: string };
        }[];
    };
    catalogs: ApdCatalogOption[];
    canManage: boolean;
}) {
    const [catalogId, setCatalogId] = useState<string>('');
    const [quantity, setQuantity] = useState<string>('1');
    const [notes, setNotes] = useState<string>('');
    const [error, setError] = useState<string | null>(null);

    const requirements = riskRegister.apdRequirements ?? [];

    function submit(e: React.FormEvent) {
        e.preventDefault();
        setError(null);
        if (!catalogId) {
            setError('Pilih katalog APD.');
            return;
        }
        router.post(
            route('apd.requirements.store'),
            {
                risk_register_id: riskRegister.id,
                apd_catalog_id: catalogId,
                quantity: Number(quantity),
                notes: notes || null,
            },
            { preserveScroll: true, onError: (err) => setError(Object.values(err).join(' ')) },
        );
        setCatalogId('');
        setQuantity('1');
        setNotes('');
    }

    function remove(id: number) {
        if (!confirm('Hapus kebutuhan APD ini?')) return;
        router.delete(route('apd.requirements.destroy', id), { preserveScroll: true });
    }

    return (
        <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Kebutuhan APD (Link ke Risk Register)
            </h3>

            {requirements.length === 0 ? (
                <p className="text-sm text-gray-500 dark:text-gray-400">Belum ada kebutuhan APD yang ditautkan.</p>
            ) : (
                <ul className="mb-4 space-y-2">
                    {requirements.map((req) => (
                        <li key={req.id} className="flex items-center justify-between rounded-md border border-gray-200 px-3 py-2 text-sm dark:border-gray-700">
                            <span>
                                <Link href={route('apd.catalogs.show', req.apd_catalog_id)} className="font-medium text-indigo-600 hover:underline dark:text-indigo-400">
                                    {req.catalog?.name ?? 'APD'}
                                </Link>
                                <span className="ml-2 text-gray-500 dark:text-gray-400">× {req.quantity}</span>
                                {req.notes && <span className="ml-2 text-gray-400">{req.notes}</span>}
                            </span>
                            {canManage && (
                                <button onClick={() => remove(req.id)} className="text-xs font-medium text-red-600 hover:underline dark:text-red-400">
                                    Hapus
                                </button>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            {canManage && (
                <form onSubmit={submit} className="grid grid-cols-1 gap-2 sm:grid-cols-4">
                    <select value={catalogId} onChange={(e) => setCatalogId(e.target.value)} className="rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700">
                        <option value="">Pilih APD…</option>
                        {catalogs.map((c) => (
                            <option key={c.id} value={c.id}>
                                {c.name} {c.catalog_code ? `(${c.catalog_code})` : ''}
                            </option>
                        ))}
                    </select>
                    <input type="number" min={1} value={quantity} onChange={(e) => setQuantity(e.target.value)} className="rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700" placeholder="Qty" />
                    <input type="text" value={notes} onChange={(e) => setNotes(e.target.value)} className="rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700" placeholder="Catatan" />
                    <button type="submit" className="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        Tambah
                    </button>
                    {error && <p className="sm:col-span-4 text-xs text-red-600">{error}</p>}
                </form>
            )}
        </div>
    );
}
