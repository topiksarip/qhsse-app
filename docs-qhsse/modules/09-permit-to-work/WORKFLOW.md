# Workflow — Permit to Work

## Standard State

- Draft
- Submitted
- Under Review
- Approved
- In Progress
- Waiting Verification
- Closed
- Rejected
- Cancelled
- Overdue

## Transition Rule

| From | To | Actor | Required |
|---|---|---|---|
| Draft | Submitted | Creator | Mandatory fields valid |
| Submitted | Under Review | Reviewer/QHSSE | Permission review |
| Under Review | Rejected | Reviewer/Approver | Rejection reason |
| Under Review | Approved/In Progress | Reviewer/Approver | Review complete |
| In Progress | Waiting Verification | PIC | Evidence/note if required |
| Waiting Verification | Closed | Verifier | Verification note |
| Waiting Verification | Rejected | Verifier | Rejection reason |
| Rejected | In Progress/Draft | Creator/PIC | Correction |
| Any non-terminal | Cancelled | Authorized role | Cancel reason |

## Audit

All transitions create workflow history and audit trail.

ponytail: transition table generik; hardcode per modul dulu, upgrade ke configurable workflow bila variasi approval antar site tinggi.
