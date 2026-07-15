<?php

namespace App\Core\Permissions;

final class CorePermissions
{
    public static function all(): array
    {
        $permissions = [
            'core.sites.view',
            'core.sites.create',
            'core.sites.update',
            'core.sites.deactivate',
            'core.sites.delete',
            'core.areas.view',
            'core.areas.create',
            'core.areas.update',
            'core.areas.deactivate',
            'core.areas.delete',
            'core.departments.view',
            'core.departments.create',
            'core.departments.update',
            'core.departments.deactivate',
            'core.departments.delete',
            'core.positions.view',
            'core.positions.create',
            'core.positions.update',
            'core.positions.deactivate',
            'core.positions.delete',
            'core.companies.view',
            'core.companies.create',
            'core.companies.update',
            'core.companies.deactivate',
            'core.companies.delete',
            'core.employees.view',
            'core.employees.create',
            'core.employees.update',
            'core.employees.deactivate',
            'core.employees.delete',
            'core.users.view',
            'core.users.create',
            'core.users.update',
            'core.users.deactivate',
            'core.users.delete',
            'core.severities.view',
            'core.severities.create',
            'core.severities.update',
            'core.severities.deactivate',
            'core.severities.delete',
            'core.priorities.view',
            'core.priorities.create',
            'core.priorities.update',
            'core.priorities.deactivate',
            'core.priorities.delete',
            'core.statuses.view',
            'core.statuses.create',
            'core.statuses.update',
            'core.statuses.deactivate',
            'core.statuses.delete',
            'core.categories.view',
            'core.categories.create',
            'core.categories.update',
            'core.categories.deactivate',
            'core.categories.delete',
            'core.risk-matrix.view',
            'core.risk-matrix.create',
            'core.risk-matrix.update',
            'core.risk-matrix.deactivate',
            'core.risk-matrix.delete',
            'core.files.view',
            'core.files.upload',
            'core.files.download',
            'core.files.delete',
            'core.numbering.view',
            'core.numbering.create',
            'core.numbering.update',
            'core.numbering.generate',
            'core.numbering.delete',
            'core.workflow.view',
            'core.workflow.manage',
            'core.workflow.transition',
            'core.audit.view',
            'core.comments.view',
            'core.comments.create',
            'core.comments.delete',
            'core.activity.view',
            'core.notifications.view',
            'core.notifications.manage',
            'core.export.csv',
            'core.roles.manage',
            'core.scope.own',
            'core.scope.department',
            'core.scope.site',
            'core.scope.company',
            'core.scope.all',
            // Incident Reporting
            'incident.reports.view',
            'incident.reports.create',
            'incident.reports.update',
            'incident.reports.submit',
            'incident.reports.review',
            'incident.reports.close',
            'incident.reports.export',
            'incident.reports.evidence',
            // Investigation & RCA
            'investigation.reports.view',
            'investigation.reports.create',
            'investigation.reports.update',
            'investigation.reports.submit',
            'investigation.reports.review',
            'investigation.reports.close',
            'investigation.reports.export',
            // CAPA / Action Tracking
            'capa.actions.view',
            'capa.actions.create',
            'capa.actions.update',
            'capa.actions.submit',
            'capa.actions.verify',
            'capa.actions.close',
            'capa.actions.reject',
            'capa.actions.export',
            // Inspection Checklist
            'inspection.checklists.view',
            'inspection.checklists.create',
            'inspection.checklists.update',
            'inspection.checklists.execute',
            'inspection.checklists.export',
            // Document Control
            'document.control.view',
            'document.control.create',
            'document.control.update',
            'document.control.submit_review',
            'document.control.approve',
            'document.control.make_effective',
            'document.control.obsolete',
            'document.control.export',
            // Audit Management
            'audit.management.view',
            'audit.management.create',
            'audit.management.update',
            'audit.management.execute',
            'audit.management.close',
            'audit.management.export',
            'audit.findings.create',
            'audit.findings.update',
            'audit.findings.close',
            'audit.findings.view',
            'audit.findings.delete',
            // Training & Competency
            'training.programs.view',
            'training.programs.create',
            'training.programs.update',
            'training.programs.delete',
            'training.records.view',
            'training.records.create',
            'training.records.update',
            'training.records.export',
            // Risk Management (HIRADC/JSA)
            'risk.registers.view',
            'risk.registers.create',
            'risk.registers.update',
            'risk.registers.assess',
            'risk.registers.export',
            // Legal & Compliance Register
            'legal.register.view',
            'legal.register.create',
            'legal.register.update',
            'legal.register.export',
            'legal.obligations.view',
            'legal.obligations.create',
            'legal.obligations.update',
            'legal.obligations.delete',

            // Contractor Management
            'contractor.management.view',
            'contractor.management.create',
            'contractor.management.update',
            'contractor.management.delete',
            'contractor.management.export',
            'contractor.management.approve',
            'contractor.management.evaluate',

            // Asset & Equipment Safety
            'asset.management.view',
            'asset.management.create',
            'asset.management.update',
            'asset.management.delete',
            'asset.management.export',
            'asset.certificates.view',
            'asset.certificates.create',
            'asset.certificates.update',
            'asset.certificates.delete',
            'asset.certificates.export',
            'asset.inspections.view',
            'asset.inspections.create',
            'asset.inspections.update',
            'asset.inspections.delete',
            'asset.inspections.export',

            // Communication & Campaign
            'communication.campaigns.view',
            'communication.campaigns.create',
            'communication.campaigns.update',
            'communication.campaigns.delete',
            'communication.campaigns.publish',
            'communication.campaigns.export',
            'communication.acknowledgments.view',

            // Advanced Reporting & Export
            'reporting.templates.view',
            'reporting.templates.create',
            'reporting.templates.update',
            'reporting.templates.delete',
            'reporting.reports.view',
            'reporting.reports.generate',
            'reporting.reports.download',

            // Emergency Preparedness
            'emergency.plans.view',
            'emergency.plans.create',
            'emergency.plans.update',
            'emergency.plans.delete',
            'emergency.plans.export',
            'emergency.drills.view',
            'emergency.drills.create',
            'emergency.drills.update',
            'emergency.drills.delete',
            'emergency.drills.execute',
            'emergency.drills.export',
            'emergency.contacts.view',
            'emergency.contacts.create',
            'emergency.contacts.update',
            'emergency.contacts.delete',
            // Permit to Work
            'permit.work.view',
            'permit.work.create',
            'permit.work.update',
            'permit.work.approve',
            'permit.work.close',
            'permit.work.cancel',
            'permit.work.export',
            'permit.checklist.sign',
            // Environmental Monitoring
            'environment.records.view',
            'environment.records.create',
            'environment.records.update',
            'environment.records.approve',
            'environment.records.close',
            'environment.records.export',
            // Security Management
            'security.incidents.view',
            'security.incidents.create',
            'security.incidents.update',
            'security.incidents.close',
            'security.incidents.export',
            'security.visitor.view',
            'security.visitor.log',
            'security.visitors.view',
            'security.visitors.create',
            'security.visitors.update',
            'security.visitors.delete',
            'security.visitors.check_out',
            'security.patrols.view',
            'security.patrols.create',
            'security.patrols.update',
            'security.patrols.delete',
            'security.patrols.export',
            'security.patrols.execute',
            // Quality NCR
            'quality.ncrs.view',
            'quality.ncrs.create',
            'quality.ncrs.update',
            'quality.ncrs.close',
            'quality.ncrs.export',
            'quality.complaints.view',
            'quality.complaints.create',
            'quality.complaints.update',
            'quality.complaints.delete',
            'quality.complaints.close',
            'quality.complaints.export',

            // Delete permissions (full CRUD enablement)
            'incident.reports.delete',
            'investigation.reports.delete',
            'capa.actions.delete',
            'inspection.checklists.delete',
            'document.control.delete',
            'audit.management.delete',
            'training.records.delete',
            'training.programs.delete',
            'risk.registers.delete',
            'legal.register.delete',
            'asset.management.delete',
            'asset.certificates.delete',
            'asset.inspections.delete',
            'permit.work.delete',
            'environment.records.delete',
            'security.incidents.delete',
            'security.patrols.delete',
            'security.visitors.delete',
            'quality.ncrs.delete',
            'quality.complaints.delete',
            'emergency.plans.delete',
            'emergency.drills.delete',
            'emergency.contacts.delete',
            'communication.campaigns.delete',
            'reporting.templates.delete',
        ];

        // Defensive: ensure no duplicate permission strings (keeps seeder idempotent).
        return array_values(array_unique($permissions));
    }

    public static function roleMap(): array
    {
        $viewOnly = [
            'core.sites.view',
            'core.areas.view',
            'core.departments.view',
            'core.positions.view',
            'core.companies.view',
            'core.employees.view',
            'core.users.view',
            'core.severities.view',
            'core.priorities.view',
            'core.statuses.view',
            'core.categories.view',
            'core.risk-matrix.view',
            'core.files.view',
            'core.files.download',
            'core.numbering.view',
            'core.workflow.view',
            'core.audit.view',
            'core.comments.view',
            'core.activity.view',
            'core.notifications.view',
        ];

        $incidentFull = [
            'incident.reports.view',
            'incident.reports.create',
            'incident.reports.update',
            'incident.reports.submit',
            'incident.reports.review',
            'incident.reports.close',
            'incident.reports.export',
            'incident.reports.evidence',
            'incident.reports.delete',
        ];

        $incidentViewExport = [
            'incident.reports.view',
            'incident.reports.export',
        ];

        $incidentBasic = [
            'incident.reports.view',
            'incident.reports.create',
            'incident.reports.submit',
            'incident.reports.evidence',
        ];

        $incidentSupervisor = [
            'incident.reports.view',
            'incident.reports.create',
            'incident.reports.update',
            'incident.reports.submit',
            'incident.reports.evidence',
        ];

        $investigationFull = [
            'investigation.reports.view',
            'investigation.reports.create',
            'investigation.reports.update',
            'investigation.reports.submit',
            'investigation.reports.review',
            'investigation.reports.close',
            'investigation.reports.export',
            'investigation.reports.delete',
        ];

        $investigationViewExport = [
            'investigation.reports.view',
            'investigation.reports.export',
        ];

        $investigationView = [
            'investigation.reports.view',
        ];

        $capaFull = [
            'capa.actions.view', 'capa.actions.create', 'capa.actions.update',
            'capa.actions.submit', 'capa.actions.verify', 'capa.actions.close',
            'capa.actions.reject', 'capa.actions.export', 'capa.actions.delete',
            'core.workflow.transition',
        ];

        $capaViewExport = ['capa.actions.view', 'capa.actions.export'];

        $capaView = ['capa.actions.view'];

        $capaAssign = ['capa.actions.view', 'capa.actions.create', 'capa.actions.update'];

        $inspectionFull = ['inspection.checklists.view', 'inspection.checklists.create', 'inspection.checklists.update', 'inspection.checklists.execute', 'inspection.checklists.export', 'inspection.checklists.delete'];
        $inspectionView = ['inspection.checklists.view'];

        $documentFull = ['core.workflow.transition', 'document.control.view', 'document.control.create', 'document.control.update', 'document.control.submit_review', 'document.control.approve', 'document.control.make_effective', 'document.control.obsolete', 'document.control.export', 'document.control.delete'];
        $documentView = ['document.control.view'];
        $documentViewExport = ['document.control.view', 'document.control.export'];
        $documentCreate = ['core.workflow.transition', 'document.control.view', 'document.control.create', 'document.control.update', 'document.control.submit_review'];

        $auditFull = ['core.workflow.transition', 'audit.management.view', 'audit.management.create', 'audit.management.update', 'audit.management.execute', 'audit.management.close', 'audit.management.export', 'audit.management.delete', 'audit.findings.view', 'audit.findings.create', 'audit.findings.update', 'audit.findings.close', 'audit.findings.delete'];
        $auditView = ['audit.management.view'];
        $auditViewExport = ['audit.management.view', 'audit.management.export'];
        $auditExecute = ['core.workflow.transition', 'audit.management.view', 'audit.management.execute', 'audit.findings.create', 'audit.findings.update', 'audit.findings.close'];

        $riskFull = ['risk.registers.view', 'risk.registers.create', 'risk.registers.update', 'risk.registers.assess', 'risk.registers.export', 'risk.registers.delete'];
        $riskViewExport = ['risk.registers.view', 'risk.registers.export'];
        $riskView = ['risk.registers.view'];
        $riskCreate = ['risk.registers.view', 'risk.registers.create', 'risk.registers.update'];

        $legalFull = ['legal.register.view', 'legal.register.create', 'legal.register.update', 'legal.register.export', 'legal.register.delete', 'legal.obligations.view', 'legal.obligations.create', 'legal.obligations.update', 'legal.obligations.delete'];
        $legalViewExport = ['legal.register.view', 'legal.register.export', 'legal.obligations.view'];
        $legalView = ['legal.register.view', 'legal.obligations.view'];
        $legalCreate = ['legal.register.view', 'legal.register.create', 'legal.register.update', 'legal.obligations.view'];

        $emergencyFull = ['emergency.plans.view', 'emergency.plans.create', 'emergency.plans.update', 'emergency.plans.delete', 'emergency.plans.export', 'emergency.drills.view', 'emergency.drills.create', 'emergency.drills.update', 'emergency.drills.delete', 'emergency.drills.execute', 'emergency.drills.export', 'emergency.contacts.view', 'emergency.contacts.create', 'emergency.contacts.update', 'emergency.contacts.delete'];
        $emergencyViewExport = ['emergency.plans.view', 'emergency.plans.export', 'emergency.drills.view', 'emergency.drills.export', 'emergency.contacts.view'];
        $emergencyView = ['emergency.plans.view', 'emergency.drills.view', 'emergency.contacts.view'];
        $emergencyCreate = ['emergency.plans.view', 'emergency.plans.create', 'emergency.plans.update', 'emergency.drills.view', 'emergency.drills.create', 'emergency.drills.update', 'emergency.contacts.view', 'emergency.contacts.create', 'emergency.contacts.update'];

        $permitFull = ['permit.work.view', 'permit.work.create', 'permit.work.update', 'permit.work.approve', 'permit.work.close', 'permit.work.cancel', 'permit.work.export', 'permit.work.delete', 'permit.checklist.sign'];
        $permitViewExport = ['permit.work.view', 'permit.work.export'];
        $permitView = ['permit.work.view'];
        $permitCreate = ['permit.work.view', 'permit.work.create', 'permit.work.update'];

        $environmentFull = ['environment.records.view', 'environment.records.create', 'environment.records.update', 'environment.records.approve', 'environment.records.close', 'environment.records.export', 'environment.records.delete'];
        $environmentViewExport = ['environment.records.view', 'environment.records.export'];
        $environmentView = ['environment.records.view'];
        $environmentCreate = ['environment.records.view', 'environment.records.create', 'environment.records.update'];

        $securityFull = ['security.incidents.view', 'security.incidents.create', 'security.incidents.update', 'security.incidents.close', 'security.incidents.export', 'security.incidents.delete', 'security.visitor.view', 'security.visitor.log', 'security.visitors.view', 'security.visitors.create', 'security.visitors.update', 'security.visitors.delete', 'security.visitors.check_out', 'security.patrols.view', 'security.patrols.create', 'security.patrols.execute', 'security.patrols.update', 'security.patrols.delete', 'security.patrols.export'];
        $securityOfficer = ['security.incidents.view', 'security.incidents.create', 'security.incidents.update', 'security.incidents.export', 'security.visitor.view', 'security.visitor.log', 'security.patrols.view', 'security.patrols.create', 'security.patrols.execute', 'security.patrols.export'];
        $securityViewExport = ['security.incidents.view', 'security.incidents.export', 'security.visitor.view', 'security.patrols.view', 'security.patrols.export'];
        $securityView = ['security.incidents.view', 'security.visitor.view'];
        $securityCreate = ['security.incidents.view', 'security.incidents.create', 'security.incidents.update', 'security.visitor.view', 'security.visitor.log', 'security.patrols.view'];

        $qualityFull = ['quality.ncrs.view', 'quality.ncrs.create', 'quality.ncrs.update', 'quality.ncrs.close', 'quality.ncrs.export', 'quality.ncrs.delete', 'quality.complaints.view', 'quality.complaints.create', 'quality.complaints.update', 'quality.complaints.close', 'quality.complaints.delete', 'quality.complaints.export'];
        $qualityViewExport = ['quality.ncrs.view', 'quality.ncrs.export', 'quality.complaints.view', 'quality.complaints.export'];
        $qualityView = ['quality.ncrs.view', 'quality.complaints.view'];
        $qualityCreate = ['quality.ncrs.view', 'quality.ncrs.create', 'quality.ncrs.update', 'quality.complaints.view', 'quality.complaints.create', 'quality.complaints.update'];

        $contractorFull = ['contractor.management.view', 'contractor.management.create', 'contractor.management.update', 'contractor.management.delete', 'contractor.management.export', 'contractor.management.approve', 'contractor.management.evaluate'];
        $contractorViewExport = ['contractor.management.view', 'contractor.management.export'];
        $contractorView = ['contractor.management.view'];
        $contractorCreate = ['contractor.management.view', 'contractor.management.create', 'contractor.management.update', 'contractor.management.evaluate'];

        $assetFull = ['core.comments.create', 'asset.management.view', 'asset.management.create', 'asset.management.update', 'asset.management.export', 'asset.management.delete', 'asset.certificates.view', 'asset.certificates.create', 'asset.certificates.update', 'asset.certificates.export', 'asset.certificates.delete', 'asset.inspections.view', 'asset.inspections.create', 'asset.inspections.update', 'asset.inspections.export', 'asset.inspections.delete'];
        $assetViewExport = ['asset.management.view', 'asset.management.export', 'asset.certificates.view', 'asset.inspections.view'];
        $assetView = ['asset.management.view', 'asset.certificates.view', 'asset.inspections.view'];
        $assetCreate = ['asset.management.view', 'asset.management.create', 'asset.management.update'];

        $communicationFull = ['communication.campaigns.view', 'communication.campaigns.create', 'communication.campaigns.update', 'communication.campaigns.delete', 'communication.campaigns.publish', 'communication.campaigns.export', 'communication.acknowledgments.view'];
        $communicationViewExport = ['communication.campaigns.view', 'communication.campaigns.export', 'communication.acknowledgments.view'];
        $communicationView = ['communication.campaigns.view'];

        $reportingFull = ['reporting.templates.view', 'reporting.templates.create', 'reporting.templates.update', 'reporting.templates.delete', 'reporting.reports.view', 'reporting.reports.generate', 'reporting.reports.download'];
        $reportingViewDownload = ['reporting.templates.view', 'reporting.reports.view', 'reporting.reports.download'];
        $reportingGenerate = ['reporting.templates.view', 'reporting.reports.view', 'reporting.reports.generate', 'reporting.reports.download'];

        $trainingFull = ['training.programs.view', 'training.programs.create', 'training.programs.update', 'training.programs.delete', 'training.records.view', 'training.records.create', 'training.records.update', 'training.records.export', 'training.records.delete'];
        $trainingViewExport = ['training.programs.view', 'training.records.view', 'training.records.export'];
        $trainingView = ['training.programs.view', 'training.records.view'];

        return [
            'Super Admin' => self::all(),
            'Admin' => self::all(),
            'QHSSE Manager' => [...$viewOnly, 'core.scope.all', ...$incidentFull, ...$investigationFull, ...$capaFull, ...$inspectionFull, ...$documentFull, ...$auditFull, ...$trainingFull, ...$riskFull, ...$legalFull, ...$emergencyFull, ...$permitFull, ...$environmentFull, ...$securityFull, ...$qualityFull, ...$contractorFull, ...$assetFull, ...$communicationFull, ...$reportingFull],
            'QHSSE Officer' => [...$viewOnly, 'core.scope.site', ...$incidentFull, ...$investigationFull, ...$capaFull, ...$inspectionFull, ...$documentCreate, ...$auditExecute, ...$trainingFull, ...$riskFull, ...$legalFull, ...$emergencyFull, ...$permitFull, ...$environmentFull, ...$securityFull, ...$qualityFull, ...$contractorFull, ...$assetFull, ...$communicationFull, ...$reportingGenerate],
            'Security Officer' => ['core.scope.site', ...$securityOfficer],
            'Supervisor' => ['core.companies.view', 'core.employees.view', 'core.departments.view', 'core.positions.view', 'core.scope.department', ...$incidentSupervisor, ...$investigationView, ...$capaAssign, ...$inspectionView, ...$documentCreate, ...$auditView, ...$trainingViewExport, ...$riskCreate, ...$legalView, ...$emergencyCreate, ...$permitCreate, ...$environmentCreate, ...$securityCreate, ...$qualityCreate, ...$contractorCreate, ...$assetViewExport, ...$communicationView, ...$reportingGenerate],
            'Department Head' => ['core.companies.view', 'core.employees.view', 'core.departments.view', 'core.positions.view', 'core.scope.department', ...$incidentSupervisor, ...$investigationView, ...$capaView, ...$inspectionView, ...$documentView, 'core.workflow.transition', 'document.control.submit_review', ...$auditView, ...$riskView, ...$legalView, ...$emergencyView, ...$permitView, ...$environmentView, ...$securityView, ...$qualityView, ...$assetViewExport, ...$communicationViewExport, ...$reportingViewDownload],
            'Employee / Reporter' => ['core.scope.own', ...$incidentBasic, ...$investigationView, ...$capaView, ...$inspectionView, ...$documentView, ...$auditView, ...$riskView, ...$legalView, ...$emergencyView, ...$permitView, ...$environmentView, ...$securityView, ...$qualityView, ...$assetView, ...$communicationView],
            'Contractor' => ['core.scope.company', ...$incidentBasic, ...$documentView, 'asset.management.view', ...$communicationView],
            'Auditor' => [...$viewOnly, 'core.scope.all', ...$incidentViewExport, ...$investigationViewExport, ...$capaViewExport, ...$inspectionView, ...$documentViewExport, ...$auditViewExport, ...$riskViewExport, ...$legalViewExport, ...$emergencyViewExport, ...$permitViewExport, ...$environmentViewExport, ...$securityViewExport, ...$qualityViewExport, ...$assetViewExport, ...$communicationViewExport, ...$reportingViewDownload],
            'Top Management' => [...$viewOnly, 'core.scope.all', ...$incidentViewExport, ...$investigationViewExport, ...$capaViewExport, ...$inspectionView, ...$documentViewExport, ...$auditViewExport, ...$riskViewExport, ...$legalViewExport, ...$emergencyViewExport, ...$permitViewExport, ...$environmentViewExport, ...$securityViewExport, ...$qualityViewExport, ...$assetViewExport, ...$communicationViewExport, ...$reportingViewDownload],
        ];
    }
}
