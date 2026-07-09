export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
}


export interface Site { id: number; code: string; name: string; address?: string | null; is_active: boolean; }
export interface Area { id: number; site_id: number; code: string; name: string; type?: string | null; is_active: boolean; site?: Pick<Site, 'id' | 'name'> | null; }
export interface Department { id: number; site_id?: number | null; code: string; name: string; is_active: boolean; site?: Pick<Site, 'id' | 'name'> | null; }
export interface Position { id: number; department_id?: number | null; code: string; name: string; is_active: boolean; department?: Pick<Department, 'id' | 'name'> | null; }


export interface Severity { id: number; code: string; name: string; level: number; color: string; description?: string | null; is_active: boolean; }
export interface Priority { id: number; code: string; name: string; sla_days: number; color: string; is_active: boolean; }
export interface Status { id: number; module: string; code: string; name: string; sequence: number; is_terminal: boolean; is_active: boolean; }
export interface Category { id: number; parent_id?: number | null; module: string; code: string; name: string; is_active: boolean; }
export interface RiskMatrixLevel { id: number; likelihood: number; consequence: number; score: number; level: string; color: string; description?: string | null; is_active: boolean; }

export interface Company {
    id: number;
    code: string;
    name: string;
    type: 'internal' | 'contractor' | 'vendor';
    email?: string | null;
    phone?: string | null;
    address?: string | null;
    is_active: boolean;
}

export interface Employee {
    id: number;
    company_id?: number | null;
    employee_no: string;
    name: string;
    email?: string | null;
    phone?: string | null;
    department?: string | null;
    position?: string | null;
    is_active: boolean;
    company?: Pick<Company, 'id' | 'name'> | null;
}

export interface CoreUser {
    id: number;
    company_id?: number | null;
    employee_id?: number | null;
    name: string;
    email: string;
    is_active: boolean;
    company?: Pick<Company, 'id' | 'name'> | null;
    employee?: Pick<Employee, 'id' | 'name' | 'employee_no'> | null;
    roles?: { id: number; name: string }[];
}
