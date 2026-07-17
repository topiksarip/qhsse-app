# Intent: Inspeksi Multi-Unit (Mode Sesi)

Status: CONFIRMED (2026-07-17, wawancara)

## Outcome
Satu "Inspeksi" bisa menampung banyak unit fisik (misal 94 wire rope sling)
dalam 1 sesi, bukan 94 form terpisah. Tiap unit punya hasil checklist sendiri.

## User
Inspector, QHSSE, operator, DAN foreman — siapa pun di lapangan yang berwenang
menjalankan inspeksi terhadap kumpulan aset. (Relevan untuk permission/role.)

## Mode
- (1) Inspeksi per-1 unit TETAP didukung (sesi berisi 1 unit).
- (2) Sesi berisi >1 unit dari jenis/template sama juga didukung.

## Pemilihan Unit
- Daftar unit dibuat PER SESI (enum per-sesi, tidak global).
- User input list unit (misal 94) lalu pilih ke inspeksi via dropdown
  SEARCHABLE + MULTI-SELECT.
- (Sumber daftar: user input list unit di form sesi, bukan master Asset global.)

## Eksekusi
- Satu unit per halaman: pilih unit -> isi checklist -> "Simpan Hasil".
- Unit tersimpan mendapat TANDA (✓) di dropdown pemilihan.

## Penyelesaian
- Tombol "Selesaikan Inspeksi" AKTIF hanya jika SEMUA unit sudah punya hasil
  ATAU status "cancelled".
- Unit terlewat harus diklik "Cancel Inspeksi" agar tidak memblokir penyelesaian.

## Constraint (implikasi teknis)
- Butuh struktur data baru: Inspection punya banyak "Inspection Unit"
  (bukan langsung ke results). Results checklist melekat per-unit.
- Checklist questions tetap diambil dari InspectionTemplate (template.items).
- Foto per-unit sudah didukung (fitur sebelumnya, kolom inspection_results.photo).

## Out of Scope (untuk wawancara ini)
- Master Asset individual global TIDAK dibuat sekarang (daftar unit per-sesi saja).
- Report / export belum bagian wawancara ini.
- RCA / CAPA per-unit belum dibahas.

## Catatan Wawancara
Urutan pertanyaan & jawaban:
1. "Item" = aset/equipment fisik nyata (bukan pertanyaan checklist). [A]
2. Hasil per-unit berbeda (tiap sling hasil sendiri, ketertelusuran). [B]
3. Identifikasi unit = enum list per-sesi, multi-select + search di dropdown. [B]
4. Daftar unit sifatnya per-sesi (tidak global, tidak per-template). [C]
5. Input daftar unit = enum per-sesi (user bangun list di form, lalu pilih). [B]
6. Eksekusi = satu unit per halaman, tanda ✓ setelah simpan. [B]
7. Penyelesaian terbuka kalau semua unit punya hasil/cancelled; "Cancel Inspeksi"
   untuk unit terlewat. [explicit]
8. User role = inspector, QHSSE, operator, foreman. [explicit]
