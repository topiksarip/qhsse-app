# Pagination, Sorting & Filtering

## Request Query
| Param | Default | Keterangan |
|-------|---------|------------|
| `page` | 1 | halaman |
| `per_page` | 15 | max 100 |
| `sort` | `created_at` | kolom sort |
| `direction` | `desc` | `asc`/`desc` |
| `q` | null | global search (backend pakai `LOWER(col) LIKE LOWER(?)`) |
| `filters[site_id]` | null | filter spesifik kolom |
| `filters[status]` | null | filter status workflow |

## Response Meta
```json
{ "meta": { "current_page": 1, "last_page": 5, "per_page": 15, "total": 73, "from": 1, "to": 15 } }
```

## Catatan Backend
- Backend memakai `App\Core\Query\ListQuery` untuk membangun query terpusat.
- Search DB-agnostic: `LOWER(col) LIKE LOWER(?)` (bukan `ILIKE`) — kompatibel SQLite test & Postgres prod.
- Filter diperbolehkan hanya pada kolom yang di-whitelist (cegah SQL injection).

## Flutter
- `flutter_infinite_list` / `infinite_scroll_pagination` untuk list panjang.
- Simpan `meta` untuk menentukan halaman berikutnya.
