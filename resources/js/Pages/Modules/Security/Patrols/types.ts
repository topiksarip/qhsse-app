export interface Option {
    id: number;
    name: string;
    site_id?: number;
    email?: string;
}

export interface PatrolResult {
    id: number;
    checkpoint: string;
    result: 'ok' | 'issue' | 'na' | null;
    findings: string | null;
    checked_at: string | null;
}

export interface Patrol {
    id: number;
    patrol_number: string;
    title: string;
    description: string | null;
    site_id: number;
    area_id: number | null;
    assigned_to: number | Option;
    scheduled_at: string;
    status: 'scheduled' | 'in_progress' | 'completed';
    started_at: string | null;
    completed_at: string | null;
    notes: string | null;
    site: Option;
    area: Option | null;
    completed_by?: number | Option | null;
    results: PatrolResult[];
    results_count?: number;
    issue_count?: number;
    pending_count?: number;
}

export const statusLabels: Record<Patrol['status'], string> = {
    scheduled: 'Terjadwal',
    in_progress: 'Sedang Berjalan',
    completed: 'Selesai',
};

export const statusClasses: Record<Patrol['status'], string> = {
    scheduled: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
    in_progress: 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200',
    completed: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200',
};
