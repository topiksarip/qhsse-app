# Workflow Specification

## 1. Workflow Core

Field standar:

- status
- submitted_by/submitted_at
- reviewed_by/reviewed_at
- approved_by/approved_at
- rejected_by/rejected_at
- rejected_reason
- verified_by/verified_at
- closed_by/closed_at

## 2. Status Standar

- Draft
- Submitted
- Under Review
- Approved
- Rejected
- In Progress
- Waiting Verification
- Closed
- Cancelled
- Overdue

## 3. Workflow Incident

Draft -> Submitted -> Under Review -> Investigation Required / Action Required -> Closed
Rejected dapat terjadi dari Under Review.

## 4. Workflow CAPA

Open -> In Progress -> Waiting Verification -> Closed
Waiting Verification -> Rejected -> In Progress
Open/In Progress -> Overdue jika lewat due date.

## 5. Workflow Document

Draft -> Under Review -> Approved -> Effective -> Obsolete/Archived
Rejected kembali ke Draft.

## 6. Workflow Permit

Draft -> Submitted -> Area Owner Review -> HSE Review -> Approved/Active -> Closed
Permit dapat Suspended/Cancelled.

## 7. Rule Umum

- Terminal status tidak boleh diedit kecuali reopen oleh role berwenang.
- Semua transisi status masuk audit trail.
- Reject wajib alasan.
- Close wajib validasi mandatory action/evidence jika modul mensyaratkan.

ponytail: status engine sederhana; upgrade ke configurable transition table jika approval path berbeda antar site.
