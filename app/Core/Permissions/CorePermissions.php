<?php

namespace App\Core\Permissions;

final class CorePermissions
{
    public static function all(): array
    {
        return [
            'core.sites.view',
            'core.sites.create',
            'core.sites.update',
            'core.sites.deactivate',
            'core.areas.view',
            'core.areas.create',
            'core.areas.update',
            'core.areas.deactivate',
            'core.departments.view',
            'core.departments.create',
            'core.departments.update',
            'core.departments.deactivate',
            'core.positions.view',
            'core.positions.create',
            'core.positions.update',
            'core.positions.deactivate',
            'core.companies.view',
            'core.companies.create',
            'core.companies.update',
            'core.companies.deactivate',
            'core.employees.view',
            'core.employees.create',
            'core.employees.update',
            'core.employees.deactivate',
            'core.users.view',
            'core.users.create',
            'core.users.update',
            'core.users.deactivate',
            'core.severities.view',
            'core.severities.create',
            'core.severities.update',
            'core.severities.deactivate',
            'core.priorities.view',
            'core.priorities.create',
            'core.priorities.update',
            'core.priorities.deactivate',
            'core.statuses.view',
            'core.statuses.create',
            'core.statuses.update',
            'core.statuses.deactivate',
            'core.categories.view',
            'core.categories.create',
            'core.categories.update',
            'core.categories.deactivate',
            'core.risk-matrix.view',
            'core.risk-matrix.create',
            'core.risk-matrix.update',
            'core.risk-matrix.deactivate',
            'core.files.view',
            'core.files.upload',
            'core.files.download',
            'core.files.delete',
            'core.numbering.view',
            'core.numbering.create',
            'core.numbering.update',
            'core.numbering.generate',
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
            // Training & Competency
            'training.programs.view',
            'training.programs.create',
            'training.programs.update',
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

            // Contractor Management
            'contractor.management.view',
            'contractor.management.create',
            'contractor.management.update',
            'contractor.management.delete',
            'contractor.management.export',
            'contractor.management.approve',

            // Asset & Equipment Safety
            'asset.management.view',
            'asset.management.create',
            'asset.management.update',
            'asset.management.export',
            'asset.certificates.view',
            'asset.certificates.create',
            'asset.certificates.update',
            'asset.inspections.view',
            'asset.inspections.create',

            // Communication & Campaign
            'communication.campaigns.view',
            'communication.campaigns.create',
            'communication.campaigns.update',
            'communication.campaigns.publish',
            'communication.campaigns.export',
            'communication.acknowledgments.view',

            // Advanced Reporting & Export
            'reporting.templates.view',
            'reporting.templates.create',
            'reporting.templates.update',
            'reporting.reports.view',
            'reporting.reports.generate',
            'reporting.reports.download',

            // Emergency Preparedness
            'emergency.plans.view',
            'emergency.plans.create',
            'emergency.plans.update',
            'emergency.plans.export',
            'emergency.drills.view',
            'emergency.drills.create',
            'emergency.drills.update',
            'emergency.drills.execute',
            'emergency.drills.export',
            'emergency.contacts.view',
            'emergency.contacts.create',
            'emergency.contacts.update',
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
            'security.patrols.view',
            'security.patrols.create',
            'security.patrols.execute',
            'security.patrols.export',
            // Quality NCR
            'quality.ncrs.view',
            'quality.ncrs.create',
            'quality.ncrs.update',
            'quality.ncrs.close',
            'quality.ncrs.export',
            'quality.complaints.view',
        ];
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
        ];

        $incidentViewExport = [
            'incident.reports.view',
            'incident.reports.export',
        ];

        $incidentBasic = [
            'incident.reports.view',
            'incident.reports.create',
            'incident.reports.submit',
        ];

        $incidentSupervisor = [
            'incident.reports.view',
            'incident.reports.create',
            'incident.reports.update',
            'incident.reports.submit',
        ];

        $investigationFull = [
            'investigation.reports.view',
            'investigation.reports.create',
            'investigation.reports.update',
            'investigation.reports.submit',
            'investigation.reports.review',
            'investigation.reports.close',
            'investigation.reports.export',
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
            'capa.actions.reject', 'capa.actions.export',
        ];

        $capaViewExport = ['capa.actions.view', 'capa.actions.export'];

        $capaView = ['capa.actions.view'];

        $capaAssign = ['capa.actions.view', 'capa.actions.create', 'capa.actions.update'];

        $inspectionFull = ['inspection.checklists.view', 'inspection.checklists.create', 'inspection.checklists.update', 'inspection.checklists.execute', 'inspection.checklists.export'];
        $inspectionView = ['inspection.checklists.view'];

        $documentFull = ['core.workflow.transition', 'document.control.view', 'document.control.create', 'document.control.update', 'document.control.submit_review', 'document.control.approve', 'document.control.make_effective', 'document.control.obsolete', 'document.control.export'];
        $documentView = ['document.control.view'];
        $documentViewExport = ['document.control.view', 'document.control.export'];
        $documentCreate = ['core.workflow.transition', 'document.control.view', 'document.control.create', 'document.control.update', 'document.control.submit_review'];

        $auditFull = ['core.workflow.transition', 'audit.management.view', 'audit.management.create', 'audit.management.update', 'audit.management.execute', 'audit.management.close', 'audit.management.export', 'audit.findings.create', 'audit.findings.update', 'audit.findings.close'];
        $auditView = ['audit.management.view'];
        $auditViewExport = ['audit.management.view', 'audit.management.export'];
        $auditExecute = ['core.workflow.transition', 'audit.management.view', 'audit.management.execute', 'audit.findings.create', 'audit.findings.update', 'audit.findings.close'];

        $riskFull = ['risk.registers.view', 'risk.registers.create', 'risk.registers.update', 'risk.registers.assess', 'risk.registers.export'];
        $riskViewExport = ['risk.registers.view', 'risk.registers.export'];
        $riskView = ['risk.registers.view'];
        $riskCreate = ['risk.registers.view', 'risk.registers.create', 'risk.registers.update'];

        $legalFull = ['legal.register.view', 'legal.register.create', 'legal.register.update', 'legal.register.export', 'legal.obligations.view', 'legal.obligations.create', 'legal.obligations.update'];
        $legalViewExport = ['legal.register.view', 'legal.register.export', 'legal.obligations.view'];
        $legalView = ['legal.register.view', 'legal.obligations.view'];
        $legalCreate = ['legal.register.view', 'legal.register.create', 'legal.register.update', 'legal.obligations.view'];

        $emergencyFull = ['emergency.plans.view', 'emergency.plans.create', 'emergency.plans.update', 'emergency.plans.export', 'emergency.drills.view', 'emergency.drills.create', 'emergency.drills.update', 'emergency.drills.execute', 'emergency.drills.export', 'emergency.contacts.view', 'emergency.contacts.create', 'emergency.contacts.update'];
        $emergencyViewExport = ['emergency.plans.view', 'emergency.plans.export', 'emergency.drills.view', 'emergency.drills.export', 'emergency.contacts.view'];
        $emergencyView = ['emergency.plans.view', 'emergency.drills.view', 'emergency.contacts.view'];
        $emergencyCreate = ['emergency.plans.view', 'emergency.plans.create', 'emergency.plans.update', 'emergency.drills.view', 'emergency.drills.create', 'emergency.drills.update', 'emergency.contacts.view', 'emergency.contacts.create', 'emergency.contacts.update'];

        $permitFull = ['permit.work.view', 'permit.work.create', 'permit.work.update', 'permit.work.approve', 'permit.work.close', 'permit.work.cancel', 'permit.work.export', 'permit.checklist.sign'];
        $permitViewExport = ['permit.work.view', 'permit.work.export'];
        $permitView = ['permit.work.view'];
        $permitCreate = ['permit.work.view', 'permit.work.create', 'permit.work.update'];

        $environmentFull = ['environment.records.view', 'environment.records.create', 'environment.records.update', 'environment.records.approve', 'environment.records.close', 'environment.records.export'];
        $environmentViewExport = ['environment.records.view', 'environment.records.export'];
        $environmentView = ['environment.records.view'];
        $environmentCreate = ['environment.records.view', 'environment.records.create', 'environment.records.update'];

        $securityFull = ['security.incidents.view', 'security.incidents.create', 'security.incidents.update', 'security.incidents.close', 'security.incidents.export', 'security.visitor.view', 'security.visitor.log', 'security.patrols.view', 'security.patrols.create', 'security.patrols.execute', 'security.patrols.export'];
        $securityOfficer = ['security.incidents.view', 'security.incidents.create', 'security.incidents.update', 'security.incidents.export', 'security.visitor.view', 'security.visitor.log', 'security.patrols.view', 'security.patrols.create', 'security.patrols.execute', 'security.patrols.export'];
        $securityViewExport = ['security.incidents.view', 'security.incidents.export', 'security.visitor.view', 'security.patrols.view', 'security.patrols.export'];
        $securityView = ['security.incidents.view', 'security.visitor.view'];
        $securityCreate = ['security.incidents.view', 'security.incidents.create', 'security.incidents.update', 'security.visitor.view', 'security.visitor.log', 'security.patrols.view'];

        $qualityFull = ['quality.ncrs.view', 'quality.ncrs.create', 'quality.ncrs.update', 'quality.ncrs.close', 'quality.ncrs.export', 'quality.complaints.view'];
        $qualityViewExport = ['quality.ncrs.view', 'quality.ncrs.export', 'quality.complaints.view'];
        $qualityView = ['quality.ncrs.view', 'quality.complaints.view'];
        $qualityCreate = ['quality.ncrs.view', 'quality.ncrs.create', 'quality.ncrs.update', 'quality.complaints.view'];

        $contractorFull = ['contractor.management.view', 'contractor.management.create', 'contractor.management.update', 'contractor.management.delete', 'contractor.management.export', 'contractor.management.approve'];
        $contractorViewExport = ['contractor.management.view', 'contractor.management.export'];
        $contractorView = ['contractor.management.view'];
        $contractorCreate = ['contractor.management.view', 'contractor.management.create', 'contractor.management.update'];

        $assetFull = ['asset.management.view', 'asset.management.create', 'asset.management.update', 'asset.management.export', 'asset.certificates.view', 'asset.certificates.create', 'asset.certificates.update', 'asset.inspections.view', 'asset.inspections.create'];
        $assetViewExport = ['asset.management.view', 'asset.management.export', 'asset.certificates.view', 'asset.inspections.view'];
        $assetView = ['asset.management.view', 'asset.certificates.view', 'asset.inspections.view'];
        $assetCreate = ['asset.management.view', 'asset.management.create', 'asset.management.update'];

        $communicationFull = ['communication.campaigns.view', 'communication.campaigns.create', 'communication.campaigns.update', 'communication.campaigns.publish', 'communication.campaigns.export', 'communication.acknowledgments.view'];
        $communicationViewExport = ['communication.campaigns.view', 'communication.campaigns.export', 'communication.acknowledgments.view'];
        $communicationView = ['communication.campaigns.view'];

        $reportingFull = ['reporting.templates.view', 'reporting.templates.create', 'reporting.templates.update', 'reporting.reports.view', 'reporting.reports.generate', 'reporting.reports.download'];
        $reportingViewDownload = ['reporting.templates.view', 'reporting.reports.view', 'reporting.reports.download'];
        $reportingGenerate = ['reporting.templates.view', 'reporting.reports.view', 'reporting.reports.generate', 'reporting.reports.download'];

        $trainingFull = ['training.programs.view', 'training.programs.create', 'training.programs.update', 'training.records.view', 'training.records.create', 'training.records.update', 'training.records.export'];
        $trainingViewExport = ['training.programs.view', 'training.records.view', 'training.records.export'];
        $trainingView = ['training.programs.view', 'training.records.view'];

        return [
            'Super Admin' => self::all(),
            'Admin' => self::all(),
            'QHSSE Manager' => [...$viewOnly, 'core.scope.all', ...$incidentFull, ...$investigationFull, ...$capaFull, ...$inspectionFull, ...$documentFull, ...$auditFull, ...$trainingFull, ...$riskFull, ...$legalFull, ...$emergencyFull, ...$permitFull, ...$environmentFull, ...$securityFull, ...$qualityFull, ...$contractorFull, ...$assetFull, ...$communicationFull, ...$reportingFull],
            'QHSSE Officer' => [...$viewOnly, 'core.scope.site', ...$incidentFull, ...$investigationFull, ...$capaFull, ...$inspectionFull, ...$documentCreate, ...$auditExecute, ...$trainingFull, ...$riskFull, ...$legalFull, ...$emergencyFull, ...$permitFull, ...$environmentFull, ...$securityFull, ...$qualityFull, ...$contractorFull, ...$assetFull, ...$communicationFull, ...$reportingGenerate],
            'Security Officer' => ['core.scope.site', ...$securityOfficer],
            'Supervisor' => ['core.companies.view', 'core.employees.view', 'core.departments.view', 'core.positions.view', 'core.scope.department', ...$incidentSupervisor, ...$investigationView, ...$capaAssign, ...$inspectionView, ...$documentCreate, ...$auditView, ...$trainingViewExport, ...$riskCreate, ...$legalView, ...$emergencyCreate, ...$permitCreate, ...$environmentCreate, ...$securityCreate, ...$qualityCreate, ...$contractorCreate, ...$assetCreate, ...$communicationView, ...$reportingGenerate],
            'Department Head' => ['core.companies.view', 'core.employees.view', 'core.departments.view', 'core.positions.view', 'core.scope.department', ...$incidentSupervisor, ...$investigationView, ...$capaView, ...$inspectionView, ...$documentView, 'core.workflow.transition', 'document.control.submit_review', ...$auditView, ...$riskView, ...$legalView, ...$emergencyView, ...$permitView, ...$environmentView, ...$securityView, ...$qualityView, ...$assetViewExport, ...$communicationViewExport, ...$reportingViewDownload],
            'Employee / Reporter' => ['core.scope.own', ...$incidentBasic, ...$investigationView, ...$capaView, ...$inspectionView, ...$documentView, ...$auditView, ...$riskView, ...$legalView, ...$emergencyView, ...$permitView, ...$environmentView, ...$securityView, ...$qualityView, ...$assetView, ...$communicationView],
            'Contractor' => ['core.scope.company', ...$incidentBasic, ...$documentView, ...$communicationView],
            'Auditor' => [...$viewOnly, 'core.scope.all', ...$incidentViewExport, ...$investigationViewExport, ...$capaViewExport, ...$inspectionView, ...$documentViewExport, ...$auditViewExport, ...$riskViewExport, ...$legalViewExport, ...$emergencyViewExport, ...$permitViewExport, ...$environmentViewExport, ...$securityViewExport, ...$qualityViewExport, ...$assetViewExport, ...$communicationViewExport, ...$reportingViewDownload],
            'Top Management' => [...$viewOnly, 'core.scope.all', ...$incidentViewExport, ...$investigationViewExport, ...$capaViewExport, ...$inspectionView, ...$documentViewExport, ...$auditViewExport, ...$riskViewExport, ...$legalViewExport, ...$emergencyViewExport, ...$permitViewExport, ...$environmentViewExport, ...$securityViewExport, ...$qualityViewExport, ...$assetViewExport, ...$communicationViewExport, ...$reportingViewDownload],
        ];
    }
}
