# Numbering Format Specification

## 1. Format Default

```text
{PREFIX}-{YYYY}-{0001}
```

## 2. Prefix

- INC: Incident
- INV: Investigation
- ACT: CAPA/Action
- INS: Inspection
- AUD: Audit
- DOC: Document
- TRN: Training
- PTW: Permit to Work
- ENV: Environmental
- SEC: Security
- NCR: Quality Non-Conformance
- RSK: Risk
- LEG: Legal
- EMR: Emergency
- CTR: Contractor
- AST: Asset
- COM: Communication
- RPT: Report

## 3. Optional Site Format

```text
{PREFIX}-{SITE}-{YYYY}-{0001}
```

## 4. Rule

- Nomor unik per module + year + optional site.
- Reset tahunan.
- Nomor dibuat saat submit, bukan draft, kecuali modul membutuhkan draft number.
