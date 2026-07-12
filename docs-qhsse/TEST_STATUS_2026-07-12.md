# Test Status Report — 2026-07-12

## Ringkasan

Debugging total terhadap baseline **19 failure / 292 passing** telah diselesaikan.

Status akhir setelah regression test Audit detail dan authenticated business-index smoke test ditambahkan:

- **340 test lulus**
- **0 failure**
- **1.212 assertion**
- Durasi full parallel suite: **127,50 detik** pada 32 proses
- Frontend production build: **lulus** dalam 12,00 detik
- Authenticated business-index smoke: **25/25 lulus** pada test environment dan live UAT port 8080.

## Akar Masalah dan Perbaikan

### Audit Management

- Mengembalikan format nomor audit ke kontrak Phase 7: `AUD-YYYY-00001` (padding lima digit).
- Membuat nomor audit sebelum INSERT agar kolom `audit_number` yang `NOT NULL` selalu valid.
- Menyelaraskan klasifikasi finding menjadi `major_nc`, `minor_nc`, `observation`, dan `ofi`.
- Menyimpan `capa_action_id` pada create/update finding.
- Menambahkan endpoint evidence upload/download yang menggunakan private `ManagedFileService`.
- Menambahkan relasi shared files dan comments pada model Audit.
- Mencatat event audit bisnis `audit.created` dengan actor yang benar.
- Mengoreksi guard workflow dan FormRequest authorization agar invalid state menghasilkan 403.
- Mengganti pemanggilan workflow method yang tidak tersedia dengan `WorkflowService::getWorkflow()`.
- Menyelaraskan payload Inertia controller dengan halaman Index/Form/Show.
- Menormalkan route UI ke route aktual `audits.*` dan menghapus action UI yang tidak memiliki endpoint.
- Menambahkan form inline finding dan evidence yang benar-benar berfungsi.
- Menyelaraskan enum audit UI `regulatory`, field `audit_type`, `start_date`, dan `close_date`.

### Risk Management

- Mengganti query kolom matrix yang tidak ada (`severity_level`, `probability_level`) dengan schema aktual (`consequence`, `likelihood`).
- Menggunakan atribut level aktual (`level`) pada activity message.
- Memindahkan guard obsolete dari policy ke business controller supaya response state konsisten.
- Mengubah fixture test agar memakai 25 kombinasi master risk matrix hasil seeder, bukan membuat duplikat kombinasi unik.
- Menyesuaikan export dengan API shared `CsvExporter::stream()`.

### Emergency Preparedness

- Mengambil string dari `GeneratedNumber::number` untuk Emergency Plan dan Emergency Drill.
- Menyelaraskan cast emergency contacts menjadi array.
- Memindahkan guard status drill dari policy ke controller agar drill yang sudah executed ditolak dengan feedback domain yang benar.
- Menambahkan halaman detail Emergency Contact yang sebelumnya hilang.

## Targeted Verification

- Audit Management: **27/27 lulus**, 152 assertions.
- Risk Management: **21/21 lulus**, 82 assertions.
- Emergency Drill: **25/25 lulus**, 67 assertions.
- Audit trail regression: **lulus**.
- Production build: **lulus**, zero TypeScript errors.

## Catatan

Angka 93,9% adalah **pass rate**, bukan code coverage. Dokumen versi sebelumnya yang menyatakan aplikasi production-ready dengan 19 known failures telah digantikan oleh hasil debugging total ini.
