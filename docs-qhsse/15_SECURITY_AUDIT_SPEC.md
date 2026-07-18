# Security & Audit Specification

## Authorization
- Server-side selalu: middleware `permission:*` + policy per modul.
- Generic file/comment endpoints fail-closed via `ParentAuthorizationRegistry`.

## Audit Trail
- `AuditService` → `audit_logs` untuk perubahan kritis.
- `ActivityService` → `activity_logs` (comment/transition).

## File Privacy
- `ManagedFileService` simpan di disk `local` (private). Download via `core/files/{file}/download` terotorisasi.
- Tidak ada file publik via public path.

## Secrets
- `.env` tidak di-commit. SSH key hanya di `.hermes/desktop-attachments` (gitignored).
