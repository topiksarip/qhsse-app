# Localization (id / en)

## Pendekatan
- `flutter_localizations` + `intl` / `easy_localization`.
- File ARB (`app_en.arb`, `app_id.arb`) di `lib/l10n`.

## Istilah QHSSE (mapping)
| id | en |
|----|----|
| Insiden | Incident |
| Investigasi | Investigation |
| Tindakan Perbaikan | CAPA |
| Inspeksi | Inspection |
| Daftar Unit | Unit List |
| Dokumen Terkendali | Controlled Document |
| Izin Kerja | Permit to Work |
| Alat Pelindung Diri | PPE / APD |
| Temuan | Finding |
| Near-miss | Near-miss |

## Aturan
- Seluruh label UI dari ARB, bukan hardcode.
- Format tanggal/waktu mengikuti locale + `intl`.
- Pesan error dari API tetap pakai bahasa server (id), tidak diterjemahkan client.
