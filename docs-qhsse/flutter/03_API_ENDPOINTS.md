# API Endpoints (JSON Contract)

> Kontrak ini **diturunkan dari `php artisan route:list`** (route Inertia) dan skema DB aktual.
> Backend harus membuka padanan JSON-nya di `/api/v1/...`.

## Konvensi Resource
- `GET /{resource}` → list (paginate+filter+sort).
- `POST /{resource}` → create (auto-number via NumberingService).
- `GET /{resource}/{id}` → detail.
- `PUT/PATCH /{resource}/{id}` → update.
- `DELETE /{resource}/{id}` → soft-delete.
- Aksi state: `POST /{resource}/{id}/{action}` (submit/review/close/approve/...).


## Incident

| Endpoint | Method/Path |
|----------|------------|
| GET | `/incidents` |
| POST | `/incidents` |
| GET | `/incidents/{id}` |
| PUT | `/incidents/{id}` |
| DELETE | `/incidents/{id}` |
| POST | `/incidents/{id}/submit` |
| POST | `/incidents/{id}/review` |
| POST | `/incidents/{id}/close` |
| POST | `/incidents/{id}/evidence (file)` |
| GET | `/incidents/export` |

## Investigation

| Endpoint | Method/Path |
|----------|------------|
| GET | `/investigations` |
| POST | `/investigations` |
| GET | `/investigations/{id}` |
| POST | `/investigations/{id}/submit` |
| POST | `/investigations/{id}/review` |
| POST | `/investigations/{id}/close` |

## CAPA

| Endpoint | Method/Path |
|----------|------------|
| GET | `/capa-actions` |
| POST | `/capa-actions` |
| GET | `/capa-actions/{id}` |
| POST | `/capa-actions/{id}/start` |
| POST | `/capa-actions/{id}/submit-verification` |
| POST | `/capa-actions/{id}/verify-close` |
| POST | `/capa-actions/{id}/reject` |
| POST | `/capa-actions/{id}/restart` |

## Inspection (multi-unit)

| Endpoint | Method/Path |
|----------|------------|
| GET | `/inspections` |
| POST | `/inspections` |
| GET | `/inspections/{id}` |
| POST | `/inspections/{id}/units/{unit}/save` |
| GET | `/inspections/{id}/export-units` |
| POST | `/inspections/{id}/complete` |

## Document Control

| Endpoint | Method/Path |
|----------|------------|
| GET | `/documents` |
| POST | `/documents` |
| GET | `/documents/{id}` |
| POST | `/documents/{id}/submit-review` |
| POST | `/documents/{id}/approve` |
| POST | `/documents/{id}/make-effective` |
| POST | `/documents/{id}/obsolete` |

## Audit

| Endpoint | Method/Path |
|----------|------------|
| GET | `/audits` |
| POST | `/audits` |
| GET | `/audits/{id}` |
| POST | `/audits/{id}/start` |
| POST | `/audits/{id}/findings` |
| PUT | `/audits/{id}/findings/{finding}` |
| POST | `/audits/{id}/findings/{finding}/close` |
| POST | `/audits/{id}/generate-report` |

## Training

| Endpoint | Method/Path |
|----------|------------|
| GET | `/training/programs` |
| POST | `/training/programs` |
| GET | `/training/records` |
| POST | `/training/records` |

## Risk

| Endpoint | Method/Path |
|----------|------------|
| GET | `/risk` |
| POST | `/risk` |
| POST | `/risk/{id}/assess` |

## Legal

| Endpoint | Method/Path |
|----------|------------|
| GET | `/legal/register` |
| GET | `/legal/obligations` |
| POST | `/legal/obligations` |

## Contractor

| Endpoint | Method/Path |
|----------|------------|
| GET | `/contractors` |
| POST | `/contractors` |
| POST | `/contractors/{id}/prequalify` |
| POST | `/contractors/{id}/evaluations` |

## Asset

| Endpoint | Method/Path |
|----------|------------|
| GET | `/assets` |
| POST | `/assets` |
| GET | `/assets/{id}/certificates` |
| GET | `/assets/{id}/inspections` |
| POST | `/assets/{id}/decommission` |

## APD/PPE

| Endpoint | Method/Path |
|----------|------------|
| GET | `/apd/catalogs` |
| POST | `/apd/catalogs` |
| GET | `/apd/items` |
| GET | `/apd/issuances` |
| POST | `/apd/issuances/{id}/request` |
| POST | `/apd/issuances/{id}/approve` |
| POST | `/apd/issuances/{id}/issue` |
| GET | `/apd/inspections` |

## Campaign

| Endpoint | Method/Path |
|----------|------------|
| GET | `/campaigns` |
| POST | `/campaigns` |
| POST | `/campaigns/{id}/publish` |
| POST | `/campaigns/{id}/acknowledge` |

## Reporting

| Endpoint | Method/Path |
|----------|------------|
| GET | `/reports/templates` |
| POST | `/reports/generate` |
| GET | `/reports/saved/{id}/download` |

## Emergency

| Endpoint | Method/Path |
|----------|------------|
| GET | `/emergency/plans` |
| GET | `/emergency/drills` |
| POST | `/emergency/drills/{id}/execute` |
| GET | `/emergency/contacts` |

## Permit

| Endpoint | Method/Path |
|----------|------------|
| GET | `/permits` |
| POST | `/permits` |
| POST | `/permits/{id}/approve` |
| POST | `/permits/{id}/close` |
| POST | `/permits/{id}/cancel` |

## Environment

| Endpoint | Method/Path |
|----------|------------|
| GET | `/environment` |
| POST | `/environment` |
| POST | `/environment/{id}/approve` |
| POST | `/environment/{id}/close` |

## Security

| Endpoint | Method/Path |
|----------|------------|
| GET | `/security/incidents` |
| POST | `/security/incidents` |
| POST | `/security/incidents/{id}/close` |
| GET | `/security/visitors` |
| POST | `/security/visitors/{id}/check-out` |
| GET | `/security/patrols` |
| POST | `/security/patrols/{id}/execute` |

## Quality

| Endpoint | Method/Path |
|----------|------------|
| GET | `/quality/ncrs` |
| POST | `/quality/ncrs` |
| POST | `/quality/ncrs/{id}/close` |
| GET | `/quality/complaints` |
| POST | `/quality/complaints/{id}/close` |

## Core

| Endpoint | Method/Path |
|----------|------------|
| GET | `/core/sites` |
| GET | `/core/areas` |
| GET | `/core/departments` |
| GET | `/core/employees` |
| GET | `/core/severities` |
| GET | `/core/priorities` |
| GET | `/core/statuses` |
| GET | `/core/categories` |
| GET | `/core/risk-matrix` |
| GET | `/core/notifications` |
| POST | `/core/notifications/{id}/read` |
| GET | `/core/files/{id}/download` |
| POST | `/core/comments` |

## Contoh Respons Detail (Incident)
```json
{
  "data": {
    "id": 12,
    "incident_number": "INC-2026-0012",
    "title": "Near miss forklift",
    "status": "submitted",
    "severity_id": 2,
    "site_id": 1,
    "occurred_at": "2026-07-18T08:30:00Z",
    "ppe_involved": true,
    "created_at": "2026-07-18T08:31:00Z"
  },
  "message": null
}
```
