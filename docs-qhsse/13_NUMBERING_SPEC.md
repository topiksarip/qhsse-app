# Numbering Specification

- Tabel: `numbering_formats`, `numbering_counters`, `generated_numbers`.
- Service: `App\Core\Numbering\NumberingService`.
- Format: prefix + year + sequence (mis. `INC-2026-0001`, `PPE-xxxx`).
- Endpoint: `core/numbering` (CRUD), `core/numbering/generate`.
- Setiap modul memanggil service saat create (bukan random).
