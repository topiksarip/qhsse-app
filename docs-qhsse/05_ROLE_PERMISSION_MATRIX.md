# Role & Permission Matrix

## Role Standar

1. Super Admin
2. Admin
3. Employee / Reporter
4. Supervisor
5. QHSSE Officer
6. QHSSE Manager
7. Department Head
8. Contractor
9. Auditor
10. Top Management

## Permission Action Standar

- view_own
- view_department
- view_site
- view_all
- create
- update_own_draft
- update_any
- submit
- review
- approve
- reject
- verify
- close
- reopen
- delete
- export
- manage_master
- manage_user
- manage_setting

## Matrix Umum

| Role | View | Create | Update | Review/Approve | Close/Verify | Export | Admin |
|---|---|---:|---:|---:|---:|---:|---:|
| Super Admin | All | Yes | Yes | Yes | Yes | Yes | Yes |
| Admin | All system data | Limited | Yes | No | No | Yes | Yes |
| Employee/Reporter | Own | Yes | Own draft | No | Own action submit | No | No |
| Supervisor | Department/Site | Yes | Related | Review | Verify dept action | Yes | No |
| QHSSE Officer | Site/All assigned | Yes | Yes | Review | Verify/Close | Yes | No |
| QHSSE Manager | All | Yes | Yes | Approve | Close | Yes | No |
| Department Head | Department | No | No | Approve related | Monitor | Yes | No |
| Contractor | Own company | Yes limited | Own draft/action | No | Submit evidence | No | No |
| Auditor | Assigned scope | No | No | No | No | Yes | No |
| Top Management | All dashboard/report | No | No | No | No | Yes | No |

## Visibility Rule

- `view_own`: data dibuat oleh user atau action PIC user.
- `view_department`: data dalam department user.
- `view_site`: data dalam site user.
- `view_all`: seluruh site/company.
- Contractor hanya melihat data milik company/contractor-nya kecuali diberi akses eksplisit.

ponytail: matrix ini role-action level; upgrade ke field-level access untuk medical/confidential record bila diwajibkan audit/legal.
