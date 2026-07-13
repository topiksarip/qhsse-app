import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { FormEvent, useMemo, useState } from 'react';

type RoleSummary = {
    id: number;
    name: string;
    permissions_count: number;
    users_count: number;
};

type Permission = { id: number; name: string };
type PermissionGroup = { key: string; label: string; permissions: Permission[] };
type SelectedRole = { id: number; name: string; permissions: string[]; immutable: boolean };

type Props = {
    roles: RoleSummary[];
    selectedRole: SelectedRole | null;
    permissionGroups: PermissionGroup[];
};

export default function Index({ roles, selectedRole, permissionGroups }: Props) {
    const [search, setSearch] = useState('');
    const { data, setData, put, processing, errors, isDirty } = useForm({
        permissions: selectedRole?.permissions ?? [],
    });
    const selected = new Set(data.permissions);
    const filteredGroups = useMemo(() => {
        const term = search.toLowerCase().trim();
        if (!term) return permissionGroups;

        return permissionGroups
            .map((group) => ({
                ...group,
                permissions: group.permissions.filter((permission) => permission.name.toLowerCase().includes(term)),
            }))
            .filter((group) => group.permissions.length > 0);
    }, [permissionGroups, search]);

    function changeRole(roleId: string) {
        router.get(route('core.roles.index'), { role: roleId }, { preserveScroll: false });
    }

    function toggle(permission: string) {
        setData('permissions', selected.has(permission)
            ? data.permissions.filter((item) => item !== permission)
            : [...data.permissions, permission]);
    }

    function setGroup(group: PermissionGroup, enabled: boolean) {
        const names = group.permissions.map((permission) => permission.name);
        const next = enabled
            ? Array.from(new Set([...data.permissions, ...names]))
            : data.permissions.filter((permission) => !names.includes(permission));
        setData('permissions', next);
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        if (!selectedRole || selectedRole.immutable) return;
        put(route('core.roles.permissions.update', selectedRole.id), { preserveScroll: true });
    }

    return (
        <AuthenticatedLayout>
            <Head title="Role & Permission Matrix" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div>
                        <h1 className="text-2xl font-semibold text-slate-900 dark:text-white">Role & Permission Matrix</h1>
                        <p className="mt-1 text-sm text-slate-600 dark:text-slate-400">Kelola hak akses backend per role. Perubahan tercatat pada audit trail.</p>
                    </div>

                    <div className="grid gap-6 lg:grid-cols-[18rem_minmax(0,1fr)]">
                        <aside className="rounded-lg bg-white p-4 shadow-sm dark:bg-slate-800">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-300" htmlFor="role">Role</label>
                            <select id="role" value={selectedRole?.id ?? ''} onChange={(event) => changeRole(event.target.value)} className="mt-2 w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                                {roles.map((role) => <option key={role.id} value={role.id}>{role.name}</option>)}
                            </select>
                            <div className="mt-4 space-y-2">
                                {roles.map((role) => (
                                    <button key={role.id} type="button" onClick={() => changeRole(String(role.id))} className={`w-full rounded-md border p-3 text-left text-sm ${selectedRole?.id === role.id ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950' : 'border-slate-200 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700'}`}>
                                        <span className="block font-medium text-slate-900 dark:text-white">{role.name}</span>
                                        <span className="mt-1 block text-xs text-slate-500 dark:text-slate-400">{role.users_count} user · {role.permissions_count} permission</span>
                                    </button>
                                ))}
                            </div>
                        </aside>

                        <form onSubmit={submit} className="space-y-4">
                            <div className="rounded-lg bg-white p-5 shadow-sm dark:bg-slate-800">
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <h2 className="text-lg font-semibold text-slate-900 dark:text-white">{selectedRole?.name ?? 'Tidak ada role'}</h2>
                                        <p className="text-sm text-slate-500 dark:text-slate-400">{data.permissions.length} permission dipilih</p>
                                    </div>
                                    {selectedRole?.immutable && <span className="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Protected · read only</span>}
                                </div>
                                <input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Cari permission..." className="mt-4 w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" />
                                {errors.permissions && <p className="mt-2 text-sm text-red-600">{errors.permissions}</p>}
                            </div>

                            {filteredGroups.map((group) => {
                                const allSelected = group.permissions.every((permission) => selected.has(permission.name));
                                return (
                                    <section key={group.key} className="rounded-lg bg-white p-5 shadow-sm dark:bg-slate-800">
                                        <div className="mb-4 flex items-center justify-between gap-3 border-b border-slate-200 pb-3 dark:border-slate-700">
                                            <div>
                                                <h3 className="font-semibold text-slate-900 dark:text-white">{group.label}</h3>
                                                <p className="text-xs text-slate-500">{group.permissions.length} permission</p>
                                            </div>
                                            {!selectedRole?.immutable && <button type="button" onClick={() => setGroup(group, !allSelected)} className="text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">{allSelected ? 'Kosongkan grup' : 'Pilih semua'}</button>}
                                        </div>
                                        <div className="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                                            {group.permissions.map((permission) => (
                                                <label key={permission.id} className={`flex items-start gap-3 rounded-md border p-3 text-sm ${selected.has(permission.name) ? 'border-indigo-300 bg-indigo-50 dark:border-indigo-800 dark:bg-indigo-950' : 'border-slate-200 dark:border-slate-700'}`}>
                                                    <input type="checkbox" checked={selected.has(permission.name)} disabled={selectedRole?.immutable} onChange={() => toggle(permission.name)} className="mt-0.5 rounded border-slate-300 text-indigo-600" />
                                                    <span className="break-all text-slate-700 dark:text-slate-200">{permission.name}</span>
                                                </label>
                                            ))}
                                        </div>
                                    </section>
                                );
                            })}

                            {!selectedRole?.immutable && (
                                <div className="sticky bottom-4 flex items-center justify-between rounded-lg border border-slate-200 bg-white p-4 shadow-lg dark:border-slate-700 dark:bg-slate-800">
                                    <span className="text-sm text-slate-500">{isDirty ? 'Ada perubahan belum disimpan' : 'Tidak ada perubahan'}</span>
                                    <button type="submit" disabled={processing || !isDirty} className="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">Simpan Permission</button>
                                </div>
                            )}
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
