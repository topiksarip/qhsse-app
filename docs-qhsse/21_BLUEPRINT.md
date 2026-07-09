# QHSSE Application Blueprint

## 1. Product Blueprint

Aplikasi dibangun sebagai modular monolith: satu aplikasi, satu database utama, banyak module boundary di level folder/domain. Pendekatan ini cukup kuat untuk enterprise internal dan jauh lebih sederhana daripada microservice.

## 2. Layer

```text
Presentation Layer
  Web UI, mobile responsive pages

Application Layer
  Use cases, workflow actions, validations

Domain/Module Layer
  Incident, CAPA, Inspection, Document, etc.

Core Layer
  Auth, users, roles, files, notifications, audit, numbering, comments

Data Layer
  Relational database + file storage
```

## 3. Core Services

- AuthService
- PermissionService
- NumberingService
- FileService
- NotificationService
- AuditTrailService
- CommentService
- WorkflowService
- ExportService
- MasterDataService

## 4. Module Contract

Setiap modul harus mendefinisikan:

- Module code/prefix.
- Status list.
- Permission keys.
- Data model.
- Workflow transitions.
- Notification events.
- Export/report.
- Dashboard metrics.

## 5. Data Flow Umum

```text
User action -> Validation -> Permission check -> Business rule -> DB transaction -> Audit trail -> Notification -> Response
```

## 6. Cross-Module Integration

- Incident dapat membuat Investigation dan CAPA.
- Inspection failed item dapat membuat CAPA.
- Audit finding dapat membuat CAPA.
- Document dapat dipakai PTW/Risk/Training.
- Training menentukan eligibility PTW/Contractor.
- Risk/JSA dipakai PTW dan Incident review.
- Legal obligation dapat membuat compliance action.
- Asset inspection/certificate memicu notification/action.
- Semua modul menyumbang data ke Dashboard dan Reporting.

## 7. Build Strategy

- Build core reusable once.
- Build first module end-to-end.
- Reuse pattern untuk modul berikutnya.
- Jangan membuat abstraction sebelum pola dipakai minimal 2-3 modul.

ponytail: modular monolith dipilih; upgrade ke service terpisah hanya jika scaling/team boundary benar-benar memaksa.
