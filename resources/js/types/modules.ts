import { Paginated } from './core';

export interface Site { id: number; name: string; code?: string; }
export interface Department { id: number; name: string; code?: string; }
export interface Severity { id: number; name: string; code?: string; }
export interface Category { id: number; name: string; code?: string; }
export interface User { id: number; name: string; email?: string; }

export interface IncidentReport {
  id: number;
  number: string;
  title: string;
  description?: string | null;
  status: string;
  event_date?: string | null;
  due_date?: string | null;
  site_id?: number | null;
  area_id?: number | null;
  department_id?: number | null;
  category_id?: number | null;
  severity_id?: number | null;
  priority_id?: number | null;
  reporter_id?: number | null;
  assigned_to?: number | null;
  created_by?: number | null;
  updated_by?: number | null;
  site?: Site | null;
  department?: Department | null;
  severity?: Severity | null;
  category?: Category | null;
  reporter?: User | null;
  created_at?: string;
  updated_at?: string;
}

export type IncidentReportPaginated = Paginated<IncidentReport>;
