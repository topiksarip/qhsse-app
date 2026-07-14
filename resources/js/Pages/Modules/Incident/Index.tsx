import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Pagination from '@/Components/Qhsse/Pagination';
import StatusBadge from '@/Components/Qhsse/StatusBadge';
import { IncidentReport, Paginated } from '@/types/modules';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

const STATUS_LABELS: Record<string, string> = {
  draft: 'Draft', submitted: 'Submitted', under_review: 'Under Review',
  in_progress: 'In Progress', approved: 'Approved', waiting_verification: 'Waiting Verification',
  closed: 'Closed', rejected: 'Rejected', cancelled: 'Cancelled',
};

export default function Index({
  items, filters, statuses,
}: {
  items: Paginated<IncidentReport>;
  filters: { search?: string; status?: string };
  statuses: string[];
}) {
  const [search, setSearch] = useState(filters.search ?? '');
  const [status, setStatus] = useState(filters.status ?? '');

  function submit(e: FormEvent) {
    e.preventDefault();
    router.get(route('modules.incident.index'), { search, status }, { preserveState: true });
  }

  return (
    <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Incident Reports</h2>}>
      <Head title="Incident Reports" />
      <div className="py-12">
        <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
          <div className="flex flex-col justify-between gap-4 sm:flex-row">
            <form onSubmit={submit} className="flex gap-2">
              <input
                className="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search title or number"
              />
              <select
                className="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                value={status} onChange={(e) => setStatus(e.target.value)}
              >
                <option value="">All statuses</option>
                {statuses.map((s) => <option key={s} value={s}>{STATUS_LABELS[s] ?? s}</option>)}
              </select>
              <button className="rounded-md bg-gray-900 px-4 py-2 text-white dark:bg-gray-100 dark:text-gray-900">Search</button>
            </form>
            <Link href={route('modules.incident.create')} className="rounded-md bg-indigo-600 px-4 py-2 text-white">
              New Incident
            </Link>
          </div>
          <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead className="bg-gray-50 dark:bg-gray-900">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Number</th>
                  <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Title</th>
                  <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Site</th>
                  <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Severity</th>
                  <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                  <th className="px-6 py-3" />
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                {items.data.length === 0 && (
                  <tr><td colSpan={6} className="px-6 py-8 text-center text-sm text-gray-500">No incident reports found.</td></tr>
                )}
                {items.data.map((item: any) => (
                  <tr key={item.id}>
                    <td className="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{item.number}</td>
                    <td className="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{item.title}</td>
                    <td className="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{item.site?.name ?? '-'}</td>
                    <td className="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{item.severity?.name ?? '-'}</td>
                    <td className="px-6 py-4"><StatusBadge status={item.status} /></td>
                    <td className="px-6 py-4 text-right text-sm">
                      <Link href={route('modules.incident.show', item.id)} className="text-indigo-600 dark:text-indigo-400">View</Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <Pagination links={items.links} />
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
