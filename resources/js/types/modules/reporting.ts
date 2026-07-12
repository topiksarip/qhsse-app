// Phase 19: Advanced Reporting & Export Types

export interface ReportTemplate {
    id: number;
    name: string;
    type: string;
    type_label?: string;
    description: string | null;
    is_predefined: boolean;
    is_active: boolean;
    config: ReportTemplateConfig;
    created_by: number;
    updated_by: number | null;
    created_at: string;
    updated_at: string;
    saved_reports_count?: number;
    creator?: {
        id: number;
        name: string;
        email: string;
    };
}

export interface ReportTemplateConfig {
    sections?: ReportSection[];
    default_parameters?: Record<string, any>;
}

export interface ReportSection {
    key: string;
    label: string;
    enabled: boolean;
    data_source?: string;
}

export interface SavedReport {
    id: number;
    template_id: number;
    name: string;
    date_from: string;
    date_to: string;
    site_id: number | null;
    department_id: number | null;
    format: 'csv' | 'pdf' | 'excel';
    include_charts: boolean;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    file_path: string | null;
    file_size: number | null;
    error_message: string | null;
    generated_at: string | null;
    generated_by: number;
    created_at: string;
    updated_at: string;
    template?: ReportTemplate;
    site?: {
        id: number;
        name: string;
        code: string;
    };
    department?: {
        id: number;
        name: string;
        code: string;
    };
    generator?: {
        id: number;
        name: string;
        email: string;
    };
}
