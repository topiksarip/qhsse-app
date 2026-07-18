# API / Route Specification

Sumber: `php artisan route:list`. Pola: resource RESTful + aksi state (`/{id}/{action}`).

## Modul Routes (ringkasan prefix)

| Prefix | Contoh endpoint |
|--------|----------------|
| `incident` → `incidents` | GET/POST `/incidents`, `/incidents/{id}`, `/incidents/{id}/submit` |
| `investigation` | `/investigations`, `/investigations/{id}/review` |
| `capa-actions` | `/capa-actions/{id}/verify-close`, `/reject`, `/restart` |
| `inspection` | `/inspections`, `/inspections/{id}/units/{unit}/save`, `/export-units` |
| `documents` | `/documents/{id}/approve`, `/make-effective`, `/obsolete` |
| `audits` | `/audits/{id}/findings`, `/generate-report` |
| `assets` | `/assets/{id}/certificates`, `/inspections`, `/decommission` |
| `apd` | `/apd/catalogs`, `/apd/issuances/{id}/issue`, `/apd/inspections` |
| `core` | `/core/sites`, `/core/files`, `/core/comments`, `/core/workflow`, `/core/roles` |
| `core` | `/core/sites`, `/core/files`, `/core/comments`, `/core/workflow`, `/core/roles` |

## Otorisasi
Semua route terikat middleware `permission:{module}.{action}`. File/comment generik via `ParentAuthorizationRegistry`.

## Response
Inertia responses (Inertia+React). API data lewat props; tidak ada JSON API terbuka kecuali endpoint internal.
