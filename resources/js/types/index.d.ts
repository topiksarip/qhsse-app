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
    mobile?: string;
    email?: string;
    address?: string;
    notes?: string;
    display_order: number;
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
    employee_no: string;
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

export interface Area {
    id: number;
    site_id: number;
    name: string;
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
    training_type?: 'general' | 'induction' | 'safety' | 'ppe_fit_test';
    apd_item_id?: number | null;
    apd_item?: { id: number; item_number: string; catalog?: { name: string } } | null;
    fit_test_result?: 'pass' | 'fail' | null;
    created_at: string;
    updated_at: string;
    employee?: Employee;
    training_program?: TrainingProgram;
    certificate_file?: ManagedFile;
}

export type PermitType =
    | 'hot_work'
    | 'working_at_height'
    | 'confined_space'
    | 'electrical'
    | 'excavation'
    | 'lifting'
    | 'other';

export type PermitStatus =
    | 'draft'
    | 'submitted'
    | 'under_review'
    | 'approved'
    | 'active'
    | 'closed'
    | 'rejected';

export type RiskLevel = 'low' | 'medium' | 'high' | 'critical';

export type ValidityStatus = 'active' | 'expired' | 'expiring_soon' | 'not_started';

export interface PermitChecklist {
    id: number;
    permit_id: number;
    item_text: string;
    is_checked: boolean;
    checked_by?: number | null;
    checked_at?: string | null;
    checker?: { id: number; name: string } | null;
}

export interface Permit {
    id: number;
    permit_number: string;
    type: PermitType;
    title: string;
    description?: string;
    site_id: number;
    area_id?: number | null;
    department_id?: number | null;
    contractor_id?: number | null;
    work_location: string;
    work_description?: string;
    start_datetime: string;
    end_datetime: string;
    validity_hours: number;
    status: PermitStatus;
    risk_level?: RiskLevel | null;
    jsa_reference?: string | null;
    approved_by?: number | null;
    approved_at?: string | null;
    closed_by?: number | null;
    closed_at?: string | null;
    cancellation_reason?: string | null;
    created_by: number;
    created_at: string;
    updated_at: string;
    site?: Site;
    area?: Area | null;
    department?: Department | null;
    contractor?: Company | null;
    creator?: User | null;
    approver?: User | null;
    closer?: User | null;
    checklists?: PermitChecklist[];
}

export interface Company {
    id: number;
    name: string;
    type?: string;
}

export type EnvironmentalType =
    | 'waste'
    | 'spill'
    | 'emission'
    | 'noise'
    | 'water_monitoring'
    | 'other';

export type EnvironmentalStatus =
    | 'recorded'
    | 'investigated'
    | 'action_open'
    | 'closed';

export interface EnvironmentalRecord {
    id: number;
    record_number: string;
    type: EnvironmentalType;
    title: string;
    description?: string;
    site_id: number;
    area_id?: number | null;
    occurred_at?: string | null;
    measured_value?: number | null;
    unit?: string | null;
    limit_value?: number | null;
    is_exceedance: boolean;
    waste_type?: string | null;
    quantity?: number | null;
    disposal_method?: string | null;
    material?: string | null;
    volume?: number | null;
    containment?: string | null;
    parameter?: string | null;
    location?: string | null;
    reporter_id: number;
    status: EnvironmentalStatus;
    capa_action_id?: number | null;
    created_at: string;
    updated_at: string;
    site?: Site | null;
    area?: Area | null;
    reporter?: User | null;
    capa_action?: CapaAction | null;
}

export interface CapaAction {
    id: number;
    number: string;
    title: string;
    status: string;
}

export type SecurityIncidentType =
    | 'unauthorized_access'
    | 'theft'
    | 'vandalism'
    | 'trespass'
    | 'suspicious_activity'
    | 'other';

export type SecurityIncidentStatus = 'reported' | 'under_investigation' | 'closed';

export interface Severity {
    id: number;
    name: string;
    level: number;
    color?: string | null;
}

export type NcrSource = 'internal' | 'external' | 'customer_complaint' | 'audit' | 'supplier';
export type NcrStatus = 'open' | 'under_review' | 'in_progress' | 'closed' | 'rejected';

export interface Ncr {
    id: number;
    ncr_number: string;
    title: string;
    source: NcrSource;
    description?: string;
    site_id: number;
    department_id?: number | null;
    product_service?: string | null;
    batch_lot?: string | null;
    customer_name?: string | null;
    severity_id?: number | null;
    status: NcrStatus;
    root_cause?: string | null;
    corrective_action?: string | null;
    preventive_action?: string | null;
    capa_action_id?: number | null;
    closed_at?: string | null;
    created_at: string;
    updated_at: string;
    site?: Site | null;
    department?: { id: number; name: string } | null;
    severity?: Severity | null;
    capaAction?: CapaAction | null;
}

export interface SecurityIncident {
    id: number;
    security_number: string;
    type: SecurityIncidentType;
    title: string;
    description?: string;
    site_id: number;
    area_id?: number | null;
    occurred_at?: string | null;
    reported_by: number;
    severity_id?: number | null;
    status: SecurityIncidentStatus;
    resolution?: string | null;
    resolved_at?: string | null;
    created_at: string;
    updated_at: string;
    site?: Site | null;
    area?: Area | null;
    reporter?: User | null;
    severity?: Severity | null;
}

// ── Risk Management ──────────────────────────────────────────────────────────
export type RiskRegisterType = 'hazard_identification' | 'jsa' | 'hiradc' | 'risk_assessment';
export type RiskRegisterStatus =
    | 'identified'
    | 'assessed'
    | 'controls_needed'
    | 'controls_in_place'
    | 'monitored'
    | 'obsolete';

export interface RiskMatrixLevel {
    id: number;
    // The seeder/migration uses `likelihood` (probability) and `consequence` (severity level).
    likelihood: number;
    consequence: number;
    score: number;
    level: string;
    color: string;
    description?: string | null;
    is_active: boolean;
}

export interface RiskRegister {
    id: number;
    register_number: string;
    title: string;
    type: RiskRegisterType;
    site_id: number;
    area_id?: number | null;
    department_id?: number | null;
    activity: string;
    hazard: string;
    existing_controls?: string | null;
    severity_id?: number | null;
    probability_id?: number | null;
    risk_level_id?: number | null;
    additional_controls?: string | null;
    residual_severity_id?: number | null;
    residual_probability_id?: number | null;
    residual_risk_level_id?: number | null;
    owner_id: number;
    status: RiskRegisterStatus;
    review_date?: string | null;
    created_at: string;
    updated_at: string;
    site?: Site | null;
    area?: Area | null;
    department?: { id: number; name: string } | null;
    owner?: User | null;
    severity?: Severity | null;
    riskLevel?: RiskMatrixLevel | null;
    residualSeverity?: Severity | null;
    residualRiskLevel?: RiskMatrixLevel | null;
}

// ── Legal & Compliance ───────────────────────────────────────────────────────
export type LegalRegisterCategory = 'national' | 'regional' | 'industry' | 'internal';
export type LegalComplianceStatus = 'compliant' | 'non_compliant' | 'in_progress' | 'not_applicable';
export type LegalRegisterStatus = 'active' | 'inactive';
export type LegalObligationFrequency = 'monthly' | 'quarterly' | 'annual';
export type LegalObligationStatus = 'pending' | 'completed';

export interface LegalRegister {
    id: number;
    register_number: string;
    title: string;
    regulation_name: string;
    regulation_number: string;
    issuing_body: string;
    category: LegalRegisterCategory;
    category_label?: string;
    compliance_status: LegalComplianceStatus;
    compliance_status_label?: string;
    compliance_status_color?: string;
    site_id: number | null;
    department_id: number | null;
    owner_id: number;
    next_review_date: string | null;
    document_id: number | null;
    notes: string | null;
    status: LegalRegisterStatus;
    created_at: string;
    updated_at: string;
    site?: { id: number; name: string } | null;
    department?: { id: number; name: string } | null;
    owner?: { id: number; name: string } | null;
    document?: { id: number; document_number: string; title: string } | null;
    obligations?: LegalObligation[];
    files?: any[];
    comments?: { id: number; content: string; internal: boolean; created_at: string; author?: { name: string } }[];
    activities?: { id: number; description: string; created_at: string; actor?: { name: string } }[];
}

export interface LegalObligation {
    id: number;
    legal_register_id: number;
    obligation_description: string;
    frequency: LegalObligationFrequency;
    frequency_label?: string;
    last_completed: string | null;
    next_due: string | null;
    evidence_file_id: number | null;
    status: LegalObligationStatus;
    status_label?: string;
    status_color?: string;
    created_at: string;
    updated_at: string;
    evidenceFile?: any | null;
}

// ── Reporting & Export ────────────────────────────────────────────────────────
export * from './modules/reporting';
