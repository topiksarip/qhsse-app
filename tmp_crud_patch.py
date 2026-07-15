#!/usr/bin/env python3
ROOT = "/home/qhsse/qhsse-app-v3/"

def read(p):
    with open(ROOT+p, encoding="utf-8") as f:
        return f.read()

def write(p, s):
    with open(ROOT+p, "w", encoding="utf-8") as f:
        f.write(s)

changes = []

def lc(model):
    return model[0].lower() + model[1:]

# ---------------------------------------------------------------------------
# 1) CONTROLLERS
# ---------------------------------------------------------------------------
controllers = {
"app/Http/Controllers/Modules/Incident/IncidentReportController.php": (
'''    public function export(Request $request, ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
''',
'''    public function destroy(Request $request, IncidentReport $incidentReport): RedirectResponse
    {
        $actor = $request->user();
        $this->access->ensureVisible($actor, $incidentReport);
        $this->authorize('delete', $incidentReport);
        abort_unless($incidentReport->status === 'draft', 409, 'Hanya insiden draft yang dapat dihapus.');

        DB::transaction(function () use ($incidentReport, $actor) {
            $this->auditService->deleted($incidentReport, $actor, 'incident', $incidentReport->id);
            $this->activityService->log('incident', $incidentReport->id, 'incident.deleted', 'Laporan insiden dihapus', $actor);
            $incidentReport->delete();
        });

        return redirect()->route('incident.reports.index')->with('success', 'Laporan insiden berhasil dihapus.');
    }

'''),
"app/Http/Controllers/Modules/Audit/AuditController.php": (
'''    public function updateFinding(UpdateAuditFindingRequest $request, Audit $audit, AuditFinding $finding): RedirectResponse
''',
'''    public function destroy(Request $request, Audit $audit): RedirectResponse
    {
        $this->authorize('delete', $audit);
        abort_if($audit->status !== 'planned', 403, 'Hanya audit dengan status Direncanakan yang dapat dihapus.');

        $actor = $request->user();
        DB::transaction(function () use ($audit, $actor) {
            $this->audit->deleted($audit, $actor, 'audit', $audit->id);
            $this->activity->log('audit', $audit->id, 'audit.deleted', "Audit {$audit->audit_number} dihapus", $actor);
            $audit->delete();
        });

        return redirect()->route('audits.index')->with('success', 'Audit berhasil dihapus.');
    }

'''),
"app/Http/Controllers/Modules/Security/SecurityIncidentController.php": (
'''    public function export(Request $request)
''',
'''    public function destroy(Request $request, SecurityIncident $securityIncident): RedirectResponse
    {
        $this->authorize('delete', $securityIncident);
        abort_if($securityIncident->status === 'closed', 403, 'Closed security incidents cannot be deleted.');

        $user = $request->user();
        DB::transaction(function () use ($securityIncident, $user) {
            $this->auditService->deleted($securityIncident, $user, 'security', $securityIncident->id);
            $this->activityService->log('security', $securityIncident->id, 'security.incident.deleted', "Security incident {$securityIncident->incident_number} deleted", $user);
            $securityIncident->delete();
        });

        return redirect()->route('security.incidents.index')->with('success', 'Security incident deleted.');
    }

'''),
"app/Http/Controllers/Modules/Permit/PermitController.php": (
'''    public function export(Request $request)
''',
'''    public function destroy(Request $request, Permit $permit): RedirectResponse
    {
        $this->authorize('delete', $permit);
        abort_unless(in_array($permit->status, ['draft', 'cancelled', 'rejected'], true), 403, 'Permit hanya dapat dihapus jika masih draft/cancelled/rejected.');

        $user = $request->user();
        DB::transaction(function () use ($permit, $user) {
            $this->auditService->deleted($permit, $user, 'permit', $permit->id);
            $this->activityService->log('permit', $permit->id, 'permit.deleted', "Permit {$permit->permit_number} deleted", $user);
            $permit->delete();
        });

        return redirect()->route('permit.work.index')->with('success', 'Permit deleted.');
    }

'''),
"app/Http/Controllers/Modules/Environment/EnvironmentalRecordController.php": (
'''    public function export(Request $request)
''',
'''    public function destroy(Request $request, EnvironmentalRecord $environmentalRecord): RedirectResponse
    {
        $this->authorize('delete', $environmentalRecord);

        $user = $request->user();
        DB::transaction(function () use ($environmentalRecord, $user) {
            $this->auditService->deleted($environmentalRecord, $user, 'environment', $environmentalRecord->id);
            $this->activityService->log('environment', $environmentalRecord->id, 'environment.record.deleted', "Environmental record deleted", $user);
            $environmentalRecord->delete();
        });

        return redirect()->route('environment.records.index')->with('success', 'Environmental record deleted.');
    }

'''),
"app/Http/Controllers/Modules/RiskManagement/RiskRegisterController.php": (
'''    public function assess(AssessRiskRegisterRequest $request, RiskRegister $riskRegister): RedirectResponse
''',
'''    public function destroy(Request $request, RiskRegister $riskRegister): RedirectResponse
    {
        $this->authorize('delete', $riskRegister);

        $actor = $request->user();
        DB::transaction(function () use ($riskRegister, $actor) {
            $this->auditService->deleted($riskRegister, $actor, 'risk', $riskRegister->id);
            $this->activityService->log('risk', $riskRegister->id, 'risk.deleted', "Risk register {$riskRegister->register_number} dihapus", $actor);
            $riskRegister->delete();
        });

        return redirect()->route('risk.registers.index')->with('success', 'Risk register berhasil dihapus.');
    }

'''),
"app/Http/Controllers/Modules/DocumentControl/DocumentControlController.php": (
'''    public function submitReview(Request $request, ControlledDocument $controlledDocument): RedirectResponse
''',
'''    public function destroy(Request $request, ControlledDocument $controlledDocument): RedirectResponse
    {
        $this->authorize('delete', $controlledDocument);
        abort_unless(in_array($controlledDocument->status, ['draft', 'rejected'], true), 403);

        $actor = $request->user();
        DB::transaction(function () use ($controlledDocument, $actor) {
            $this->audit->deleted($controlledDocument, $actor, 'document', $controlledDocument->id);
            $this->activity->log('document', $controlledDocument->id, 'document.deleted', 'Dokumen dihapus', $actor);
            $controlledDocument->delete();
        });

        return redirect()->route('document.control.index')->with('success', 'Dokumen berhasil dihapus.');
    }

'''),
"app/Http/Controllers/Modules/Investigation/InvestigationController.php": (
'''    public function start(Investigation $investigation, Request $request): RedirectResponse
''',
'''    public function destroy(Request $request, Investigation $investigation): RedirectResponse
    {
        $actor = $request->user();
        $this->authorize('delete', $investigation);
        abort_unless(in_array($investigation->status, ['draft', 'cancelled'], true), 409, 'Investigasi hanya dapat dihapus jika draft/cancelled.');

        DB::transaction(function () use ($investigation, $actor) {
            $this->auditService->deleted($investigation, $actor, 'investigation', $investigation->id);
            $this->activityService->log('investigation', $investigation->id, 'investigation.deleted', 'Investigasi dihapus', $actor);
            $investigation->delete();
        });

        return redirect()->route('investigation.reports.index')->with('success', 'Investigasi berhasil dihapus.');
    }

'''),
"app/Http/Controllers/Modules/Training/TrainingRecordController.php": (
'''    public function export(Request $request, CsvExporter $exporter)
''',
'''    public function destroy(Request $request, TrainingRecord $record): RedirectResponse
    {
        $this->authorize('delete', $record);

        $actor = $request->user();
        DB::transaction(function () use ($record, $actor) {
            $this->auditService->deleted($record, $actor, 'training', $record->id);
            $this->activityService->log('training', $record->id, 'training.record.deleted', "Training record {$record->training_number} deleted", $actor);
            $record->delete();
        });

        return redirect()->route('training.records.index')->with('success', 'Training record deleted.');
    }

'''),
"app/Http/Controllers/Modules/Inspection/InspectionController.php": (
'''    public function start(Inspection $inspection, Request $request): RedirectResponse
''',
'''    public function destroy(Request $request, Inspection $inspection): RedirectResponse
    {
        $actor = $request->user();
        $this->authorize('delete', $inspection);
        abort_unless(in_array($inspection->status, ['draft', 'in_progress', 'cancelled'], true), 409, 'Inspeksi hanya dapat dihapus jika draft/in_progress/cancelled.');

        DB::transaction(function () use ($inspection, $actor) {
            $this->auditService->deleted($inspection, $actor, 'inspection', $inspection->id);
            $this->activityService->log('inspection', $inspection->id, 'inspection.deleted', 'Inspeksi dihapus', $actor);
            $inspection->delete();
        });

        return redirect()->route('inspection.checklists.index')->with('success', 'Inspeksi berhasil dihapus.');
    }

'''),
}

for path, (anchor, method) in controllers.items():
    s = read(path)
    if "function destroy" in s:
        changes.append(f"controller= {path} (skip, exists)")
        continue
    assert s.count(anchor) == 1, f"[{path}] anchor count={s.count(anchor)}"
    s = s.replace(anchor, method + anchor, 1)
    write(path, s)
    changes.append(f"controller+ {path}")

# ---------------------------------------------------------------------------
# 2) POLICIES (fix existing + create missing)
# ---------------------------------------------------------------------------
# (path, perm, model, var, create_if_missing)
policy_specs = {
"app/Policies/Modules/Incident/IncidentReportPolicy.php": ("incident.reports.delete", "IncidentReport", "incidentReport", True),
"app/Policies/Modules/Audit/AuditPolicy.php": ("audit.management.delete", "Audit", "audit", True),
"app/Policies/Modules/Security/SecurityIncidentPolicy.php": ("security.incidents.delete", "SecurityIncident", "securityIncident", False),
"app/Policies/Modules/Permit/PermitPolicy.php": ("permit.work.delete", "Permit", "permit", False),
"app/Policies/Modules/Environment/EnvironmentalRecordPolicy.php": ("environment.records.delete", "EnvironmentalRecord", "environmentalRecord", False),
"app/Policies/Modules/RiskManagement/RiskRegisterPolicy.php": ("risk.registers.delete", "RiskRegister", "riskRegister", False),
"app/Policies/Modules/DocumentControl/DocumentControlPolicy.php": ("document.control.delete", "ControlledDocument", "controlledDocument", True),
"app/Policies/Modules/Investigation/InvestigationPolicy.php": ("investigation.reports.delete", "Investigation", "investigation", True),
"app/Policies/Modules/Training/TrainingRecordPolicy.php": ("training.records.delete", "TrainingRecord", "record", False),
"app/Policies/Modules/Inspection/InspectionPolicy.php": ("inspection.checklists.delete", "Inspection", "inspection", True),
}

# namespace per policy
ns_map = {
"app/Policies/Modules/Incident/IncidentReportPolicy.php": "Incident",
"app/Policies/Modules/Audit/AuditPolicy.php": "Audit",
"app/Policies/Modules/Security/SecurityIncidentPolicy.php": "Security",
"app/Policies/Modules/Permit/PermitPolicy.php": "Permit",
"app/Policies/Modules/Environment/EnvironmentalRecordPolicy.php": "Environment",
"app/Policies/Modules/RiskManagement/RiskRegisterPolicy.php": "RiskManagement",
"app/Policies/Modules/DocumentControl/DocumentControlPolicy.php": "DocumentControl",
"app/Policies/Modules/Investigation/InvestigationPolicy.php": "Investigation",
"app/Policies/Modules/Training/TrainingRecordPolicy.php": "Training",
"app/Policies/Modules/Inspection/InspectionPolicy.php": "Inspection",
}
# model FQCN
model_fqcn = {
"IncidentReport": "App\\Models\\Modules\\Incident\\IncidentReport",
"Audit": "App\\Models\\Modules\\Audit\\Audit",
"SecurityIncident": "App\\Models\\Modules\\Security\\SecurityIncident",
"Permit": "App\\Models\\Modules\\Permit\\Permit",
"EnvironmentalRecord": "App\\Models\\Modules\\Environment\\EnvironmentalRecord",
"RiskRegister": "App\\Models\\Modules\\RiskManagement\\RiskRegister",
"ControlledDocument": "App\\Models\\Modules\\DocumentControl\\ControlledDocument",
"Investigation": "App\\Models\\Modules\\Investigation\\Investigation",
"TrainingRecord": "App\\Models\\Modules\\Training\\TrainingRecord",
"Inspection": "App\\Models\\Modules\\Inspection\\Inspection",
}

import re, os
for path, (perm, model, var, create) in policy_specs.items():
    if not os.path.exists(ROOT+path):
        assert create, f"[{path}] missing but create=False"
        ns = ns_map[path]
        fqcn = model_fqcn[model]
        content = f"""<?php
namespace App\\Policies\\Modules\\{ns};
use {fqcn}; use App\\Models\\User;
class {model}Policy {{
    public function viewAny(User $user): bool {{ return $user->can('{perm}'.replace('.delete','.view')); }}
    public function view(User $user, {model} ${var}): bool {{ return $user->can('{perm}'.replace('.delete','.view')); }}
    public function create(User $user): bool {{ return $user->can('{perm}'.replace('.delete','.create')); }}
    public function update(User $user, {model} ${var}): bool {{ return $user->can('{perm}'.replace('.delete','.update')); }}
    public function delete(User $user, {model} ${var}): bool {{ return $user->can('{perm}'); }}
    public function export(User $user): bool {{ return $user->can('{perm}'.replace('.delete','.export')); }}
}}
"""
        os.makedirs(os.path.dirname(ROOT+path), exist_ok=True)
        write(path, content)
        changes.append(f"policy+ {path}")
        continue
    s = read(path)
    if f"can('{perm}')" in s:
        changes.append(f"policy= {path} (skip, enabled)")
        continue
    # Match the entire delete() method and replace its body with can(perm)
    pat = re.compile(
        r"public function delete\(User \$user, " + re.escape(model) + r" \$\w+\): bool\s*\{.*?\}\n",
        re.DOTALL,
    )
    m = pat.search(s)
    if m:
        new = f"public function delete(User $user, {model} ${var}): bool {{ return $user->can('{perm}'); }}\n"
        s = s[:m.start()] + new + s[m.end():]
        write(path, s)
        changes.append(f"policy~ {path}")
        continue
    # exists but no delete() method -> append before final closing brace
    if "function delete" not in s:
        # ensure closing brace present
        assert s.rstrip().endswith("}"), f"[{path}] no closing brace"
        insert = f"    public function delete(User $user, {model} ${var}): bool {{ return $user->can('{perm}'); }}\n"
        s = s.rstrip()
        if s.endswith("}"):
            s = s[:-1].rstrip() + "\n" + insert + "}\n"
        write(path, s)
        changes.append(f"policy+delete {path}")
        continue
    raise AssertionError(f"[{path}] unexpected delete state")


# ---------------------------------------------------------------------------
# 3) ROUTES — robust: insert destroy after the update route statement
# ---------------------------------------------------------------------------
# (path, param_in_route, name, perm, ctrl_fqcn)
route_specs = [
("routes/modules/audit.php", "audit", "audits.destroy", "audit.management.delete", "Audit\\AuditController"),
("routes/modules/security.php", "security_incident", "security.incidents.destroy", "security.incidents.delete", "Security\\SecurityIncidentController"),
("routes/modules/permit.php", "permit", "permit.work.destroy", "permit.work.delete", "Permit\\PermitController"),
("routes/modules/environment.php", "environmental_record", "environment.records.destroy", "environment.records.delete", "Environment\\EnvironmentalRecordController"),
("routes/modules.php", "incidentReport", "incident.reports.destroy", "incident.reports.delete", "Incident\\IncidentReportController"),
("routes/modules.php", "investigation", "investigation.reports.destroy", "investigation.reports.delete", "Investigation\\InvestigationController"),
("routes/modules.php", "controlledDocument", "document.control.destroy", "document.control.delete", "DocumentControl\\DocumentControlController"),
("routes/modules.php", "inspection", "inspection.checklists.destroy", "inspection.checklists.delete", "Inspection\\InspectionController"),
("routes/modules/risk.php", "riskRegister", "risk.registers.destroy", "risk.registers.delete", "RiskManagement\\RiskRegisterController"),
("routes/modules/training.php", "record", "training.records.destroy", "training.records.delete", "Training\\TrainingRecordController"),
]

for path, param, name, perm, ctrl in route_specs:
    s = read(path)
    if f"name('destroy')->middleware('permission:{perm}')" in s:
        changes.append(f"route= {path}::{name} (skip, exists)")
        continue
    marker = f"Route::put('/{{{param}}}'"
    idx = s.find(marker)
    assert idx != -1, f"[{path}] cannot find put route for {param}"
    # find end of this route statement (next ';' at line end)
    semi = s.find(";", idx)
    assert semi != -1, f"[{path}] no ';' after put route for {param}"
    # determine indentation of the Route::put line
    line_start = s.rfind("\n", 0, idx) + 1
    indent = ""
    j = line_start
    while j < idx and s[j] in " \t":
        indent += s[j]
        j += 1
    line = f"{indent}Route::delete('/{{{param}}}', [\\App\\Http\\Controllers\\Modules\\{ctrl}::class, 'destroy'])->name('destroy')->middleware('permission:{perm}');"
    s = s[:semi+1] + "\n" + line + s[semi+1:]
    write(path, s)
    changes.append(f"route+ {path}::{name}")


print("DONE")
for c in changes:
    print(c)
