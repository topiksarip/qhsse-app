# Offline-First & Sync (Penting untuk Lapangan)

Inspeksi/insiden sering dilakukan di area tanpa sinyal. Flutter harus mendukung offline.

## Strategi
1. **Local-first write**: semua create/update disimpan ke `drift` lokal + tandai `sync_status = pending`.
2. **Outbox queue**: tabel `outbox` menyimpan payload request yang belum terkirim.
3. **Auto-sync**: saat koneksi kembali (konektivitas listener), kirim antrean via Dio dengan retry eksponensial.
4. **Conflict**: gunakan `updated_at` server sebagai otoritas; client abaikan edit lama.
5. **Numbering**: `incident_number` dibuat server → client pakai `local_uuid` sementara sampai server merespons.

## Tabel Lokal (contoh)
| Tabel | Pakai untuk |
|-------|-------------|
| `incident_local` | draft insiden offline |
| `inspection_result_local` | hasil inspeksi per-unit offline (foto disimpan ke app dir) |
| `outbox` | antrean request `{ method, path, body, attempts }` |

## Upload Foto Offline
- Simpan foto ke app documents dir; saat sync, `FormData` upload via `POST /evidence`.
- Tampilkan thumbnail dari file lokal dulu, ganti dengan URL server setelah sync.

## Keamanan Data Lokal
- DB lokal terenkripsi (`drift` + `flutter_secure_storage` key, atau `sqflite_sqlcipher`).
