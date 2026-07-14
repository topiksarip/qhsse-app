import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatusPill from '@/Components/Qhsse/StatusPill';
import { IncidentReport } from '@/types/modules';
import { Head, Link, router } from '@inertiajs/react';

const ACTIONS: { label: string; route: string; method: 'post'; enabled: string[]; color: string }[] = [
  { label: 'Submit', route: 'submit', method: 'post', enabled: ['draft', 'rejected', 'cancelled'], color: 'bg-indigo-600' },
  { label: 'Review', route: 'review', method: 'post', enabled: ['submitted'], color: 'bg-blue-600' },
  { label: 'Approve', route: 'approve', method: 'post', enabled: ['under_review'], color: 'bg-emerald-600' },
  { label: 'Verify', route: 'verify', method: 'post', enabled: ['approved'], color: 'bg-purple-600' },
  { label: 'Close', route: 'close', method: 'post', enabled: ['waiting_verification', 'in_progress'], color: 'bg-gray-900' },
  { label: 'Reopen', route: 'reopen', method: 'post', enabled: ['closed'], color: 'bg-amber-600' },
];

export default function Show({ item }: { item: IncidentReport }) {
  function run(routeName: string) {
    router.post(route(`modules.incident.${routeName}`, item.id));
  }

  return (
    <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Incident {item.number}</h2>}>
      <Head title={`Incident ${item.number}`} />
      <div className="py-12">
        <div className="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between">
            <Link href={route('modules.incident.index')} className="text-indigo-600 dark:text-indigo-400">← Back</Link>
            <StatusPill status={item.status} />
          </div>
          <div className="bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800 space-y-4">
            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">{item.title}</h3>
            <p className="text-sm text-gray-600 dark:text-gray-300">{item.description}</p>
            <dl className="grid grid-cols-2 gap-4 text-sm">
              <div><dt className="text-gray-500">Site</dt><dd className="text-gray-900 dark:text-gray-100">{item.site?.name ?? '-'}</dd></div>
              <div><dt className="text-gray-500">Department</dt><dd className="text-gray-900 dark:text-gray-100">{item.department?.name ?? '-'}</dd></div>
              <div><dt className="text-gray-500">Severity</dt><dd className="text-gray-900 dark:text-gray-100">{item.severity?.name ?? '-'}</dd></div>
              <div><dt className="text-gray-500">Event Date</dt><dd className="text-gray-900 dark:text-gray-100">{item.event_date ?? '-'}</dd></div>
            </dl>
          </div>
          <div className="flex flex-wrap gap-3">
            {ACTIONS.filter((a) => a.enabled.includes(item.status)).map((a) => (
              <button
                key={a.route}
                onClick={() => run(a.route)}
                className={`rounded-md px-4 py-2 text-white ${a.color}`}
              >
                {a.label}
              </button>
            ))}
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
