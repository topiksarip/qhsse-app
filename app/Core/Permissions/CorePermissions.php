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

        return [
            'Super Admin' => self::all(),
            'Admin' => self::all(),
            'QHSSE Manager' => [...$viewOnly, 'core.scope.all', ...$incidentFull, ...$investigationFull, ...$capaFull, ...$inspectionFull, ...$documentFull, ...$auditFull, ...$riskFull],
            'QHSSE Officer' => [...$viewOnly, 'core.scope.site', ...$incidentFull, ...$investigationFull, ...$capaFull, ...$inspectionFull, ...$documentCreate, ...$auditExecute, ...$riskFull],
            'Supervisor' => ['core.companies.view', 'core.employees.view', 'core.departments.view', 'core.positions.view', 'core.scope.department', ...$incidentSupervisor, ...$investigationView, ...$capaAssign, ...$inspectionView, ...$documentCreate, ...$auditView, ...$riskCreate],
            'Department Head' => ['core.companies.view', 'core.employees.view', 'core.departments.view', 'core.positions.view', 'core.scope.department', ...$incidentSupervisor, ...$investigationView, ...$capaView, ...$inspectionView, ...$documentView, 'core.workflow.transition', 'document.control.submit_review', ...$auditView, ...$riskView],
            'Employee / Reporter' => ['core.scope.own', ...$incidentBasic, ...$investigationView, ...$capaView, ...$inspectionView, ...$documentView, ...$auditView, ...$riskView],
            'Contractor' => ['core.scope.company', ...$incidentBasic, ...$documentView],
            'Auditor' => [...$viewOnly, 'core.scope.all', ...$incidentViewExport, ...$investigationViewExport, ...$capaViewExport, ...$inspectionView, ...$documentViewExport, ...$auditViewExport, ...$riskViewExport],
            'Top Management' => [...$viewOnly, 'core.scope.all', ...$incidentViewExport, ...$investigationViewExport, ...$capaViewExport, ...$inspectionView, ...$documentViewExport, ...$auditViewExport, ...$riskViewExport],
        ];
    }
}
