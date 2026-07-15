#!/usr/bin/env python3
ROOT = "/home/qhsse/qhsse-app-v3/"

def read(p):
    with open(ROOT+p, encoding="utf-8") as f:
        return f.read()

def write(p, s):
    with open(ROOT+p, "w", encoding="utf-8") as f:
        f.write(s)

# 1) Fix RiskRegisterPolicy broken delete block
p = "app/Policies/Modules/RiskManagement/RiskRegisterPolicy.php"
s = read(p)
bad_start = "    public function delete(User $user, RiskRegister $riskRegister): bool { return $user->can('risk.registers.delete'); }"
assert bad_start in s, "risk delete already fixed?"
# remove from bad_start through the orphaned closing '}' before 'public function export'
end_marker = "\n    public function export"
end_idx = s.index(end_marker)
# find the '}' that closes the orphaned block: the last '}' before end_marker
block_end = s.rfind("    }\n", 0, end_idx) + len("    }\n")
new_method = (
    "    public function delete(User $user, RiskRegister $riskRegister): bool\n"
    "    {\n"
    "        return $user->can('risk.registers.delete');\n"
    "    }\n"
)
s = s[:s.index(bad_start)] + new_method + s[block_end:]
write(p, s)
print("risk policy fixed")

# 2) Fix missing braces in routes
fixes = [
    ("routes/modules/audit.php", "Route::delete('/audit', [\\App\\Http\\Controllers\\Modules\\Audit\\AuditController::class, 'destroy'])", "Route::delete('/{audit}', [\\App\\Http\\Controllers\\Modules\\Audit\\AuditController::class, 'destroy']"),
    ("routes/modules/security.php", "Route::delete('/security_incident', [\\App\\Http\\Controllers\\Modules\\Security\\SecurityIncidentController::class, 'destroy'])", "Route::delete('/{security_incident}', [\\App\\Http\\Controllers\\Modules\\Security\\SecurityIncidentController::class, 'destroy']"),
    ("routes/modules/environment.php", "Route::delete('/environmental_record', [\\App\\Http\\Controllers\\Modules\\Environment\\EnvironmentalRecordController::class, 'destroy'])", "Route::delete('/{environmental_record}', [\\App\\Http\\Controllers\\Modules\\Environment\\EnvironmentalRecordController::class, 'destroy']"),
]
for path, old, new in fixes:
    s = read(path)
    assert old in s, f"[{path}] malformed delete not found"
    s = s.replace(old, new, 1)
    write(path, s)
    print(f"route fixed: {path}")

print("DONE")
