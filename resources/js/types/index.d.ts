export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
        permissions: string[];
        roles: string[];
    };
};

export interface Site {
    id: number;
    code: string;
    name: string;
    address?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface EmergencyContact {
    id: number;
    site_id: number;
    name: string;
    role: string;
    phone: string;
    email?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    site?: Site;
}

export interface EmergencyPlan {
    id: number;
    plan_number: string;
    site_id: number;
    contact_person_id?: number;
    type: string;
    name: string;
    description?: string;
    response_procedure?: string;
    escalation_procedure?: string;
    equipment_needed?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    site?: Site;
    contact_person?: User;
    emergency_contacts?: { name: string; role: string; phone: string; }[];
}

export interface EmergencyDrill {
    id: number;
    drill_number: string;
    emergency_plan_id: number;
    site_id: number;
    scheduled_date: string;
    executed_date?: string;
    observer_id: number;
    participants_count?: number;
    result?: 'pass' | 'fail' | 'needs_improvement';
    findings?: string;
    recommendations?: string;
    status: 'scheduled' | 'executed';
    created_at: string;
    updated_at: string;
}

export interface ActivityLog {
    id: number;
    description: string;
    created_at: string;
    updated_at: string;
}

export interface ManagedFile {
    id: number;
    file_name: string;
    file_path: string;
    file_size: number;
    mime_type: string;
    uploaded_by: number;
    created_at: string;
    updated_at: string;
}

export interface Comment {
    id: number;
    user_id: number;
    comment_text: string;
    created_at: string;
    updated_at: string;
    user?: User;
}

export interface Employee {
    id: number;
    employee_number: string;
    name: string;
    site_id: number;
    department_id: number;
    position_id?: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    site?: Site;
}

export interface Department {
    id: number;
    code: string;
    name: string;
    site_id: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface TrainingProgram {
    id: number;
    code: string;
    name: string;
    category: string;
    duration_hours: number;
    description?: string;
    is_certification: boolean;
    validity_months?: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface TrainingRecord {
    id: number;
    training_number: string;
    employee_id: number;
    training_program_id: number;
    provider?: string;
    start_date: string;
    end_date?: string;
    status: 'scheduled' | 'in_progress' | 'completed' | 'expired' | 'cancelled';
    score?: number;
    result?: 'pass' | 'fail' | 'pending';
    certificate_number?: string;
    certificate_file_id?: number;
    expiry_date?: string;
    notes?: string;
    created_at: string;
    updated_at: string;
    employee?: Employee;
    program?: TrainingProgram;
    certificate_file?: ManagedFile;
}
