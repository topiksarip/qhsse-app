import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { IncidentReport } from '@/types/modules';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Form({ item }: { item: IncidentReport | null }) {
  const isEdit = !!item;
  const { data, setData, post, put, processing, errors } = useForm({
    title: item?.title ?? '',
    description: item?.description ?? '',
    site_id: (item?.site_id as unknown as string) ?? '',
    area_id: (item?.area_id as unknown as string) ?? '',
    department_id: (item?.department_id as unknown as string) ?? '',
    category_id: (item?.category_id as unknown as string) ?? '',
    severity_id: (item?.severity_id as unknown as string) ?? '',
    priority_id: (item?.priority_id as unknown as string) ?? '',
    event_date: item?.event_date ?? '',
    due_date: item?.due_date ?? '',
    status: item?.status ?? 'draft',
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    if (isEdit && item) {
      put(route('modules.incident.update', item.id));
    } else {
      post(route('modules.incident.store'));
    }
  }

  const field = 'rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 w-full';

  return (
    <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{isEdit ? 'Edit' : 'New'} Incident Report</h2>}>
      <Head title={isEdit ? 'Edit Incident' : 'New Incident'} />
      <div className="py-12">
        <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
          <form onSubmit={submit} className="space-y-6 bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
              <input className={field} value={data.title} onChange={(e) => setData('title', e.target.value)} />
              {errors.title && <p className="mt-1 text-sm text-red-600">{errors.title}</p>}
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
              <textarea className={field} rows={4} value={data.description} onChange={(e) => setData('description', e.target.value)} />
            </div>
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Severity ID</label>
                <input className={field} value={data.severity_id} onChange={(e) => setData('severity_id', e.target.value)} placeholder="optional" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Priority ID</label>
                <input className={field} value={data.priority_id} onChange={(e) => setData('priority_id', e.target.value)} placeholder="optional" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Site ID</label>
                <input className={field} value={data.site_id} onChange={(e) => setData('site_id', e.target.value)} placeholder="optional" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Department ID</label>
                <input className={field} value={data.department_id} onChange={(e) => setData('department_id', e.target.value)} placeholder="optional" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Event Date</label>
                <input type="date" className={field} value={data.event_date} onChange={(e) => setData('event_date', e.target.value)} />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Due Date</label>
                <input type="date" className={field} value={data.due_date} onChange={(e) => setData('due_date', e.target.value)} />
              </div>
            </div>
            <div className="flex justify-end gap-3">
              <Link href={route('modules.incident.index')} className="rounded-md px-4 py-2 text-gray-700 dark:text-gray-300">Cancel</Link>
              <button disabled={processing} className="rounded-md bg-indigo-600 px-4 py-2 text-white disabled:opacity-50">
                {isEdit ? 'Update' : 'Create'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
