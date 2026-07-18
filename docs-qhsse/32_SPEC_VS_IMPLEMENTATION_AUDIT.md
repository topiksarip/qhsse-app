# Spec vs Implementation Audit

| Area | Spec | Implementasi | Status |
|------|------|-------------|--------|
| RBAC | 14 role | 14 role di CorePermissions | Match |
| Modul | 19 | 19 route prefix | Match |
| File privat | fail-closed | ParentAuthorizationRegistry | Match |
| Numbering | unik | NumberingService | Match |
| Inspection multi-unit | asset source | inspection_units.asset_id | Match |
| Dashboard 500 | - | scopeLowStock fixed | Resolved |
