# API Specification

## 1. API Style

REST JSON API.

## 2. Standard Response

```json
{
  "data": {},
  "meta": {},
  "message": "OK"
}
```

## 3. Error Response

```json
{
  "message": "Validation failed",
  "errors": {
    "field": ["Error message"]
  }
}
```

## 4. Pagination

Query:

- page
- per_page
- search
- sort
- direction

Response meta:

- current_page
- per_page
- total
- last_page

## 5. Filter Convention

- site_id
- department_id
- status
- date_from
- date_to
- category_id

## 6. Standard Endpoints Per Module

- GET `/api/{module}`
- POST `/api/{module}`
- GET `/api/{module}/{id}`
- PUT `/api/{module}/{id}`
- DELETE `/api/{module}/{id}`
- POST `/api/{module}/{id}/submit`
- POST `/api/{module}/{id}/approve`
- POST `/api/{module}/{id}/reject`
- POST `/api/{module}/{id}/close`
- GET `/api/{module}/{id}/comments`
- POST `/api/{module}/{id}/comments`
- GET `/api/{module}/{id}/files`
- POST `/api/{module}/{id}/files`
- GET `/api/{module}/export`

## 7. Security

- Semua endpoint butuh auth kecuali login/forgot password.
- Authorization dicek server-side.
- File download wajib cek permission reference record.
