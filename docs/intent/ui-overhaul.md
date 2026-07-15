# Intent: QHSSE App UI Overhaul (Frontend Only)

> Confirmed via interview-me on 2026-07-15. Source of truth for any UI overhaul work.

## Outcome
Overhaul total tampilan frontend QHSSE app — rapih, compact, ukuran button/form
konsisten, responsif mobile/tablet/desktop.

## User
Semua pengguna (operator → manajemen), akses via mobile, tablet, maupun desktop.

## Why now
Keluhan menu mobile "barisnya banyak" + keinginan upgrade visual agar lebih
menarik & presisi.

## Success Criteria
- Sidebar: buka dengan tombol, **auto-hide di semua ukuran layar** (tutup otomatis
  saat klik luar / pilih menu). Berlaku desktop + laptop + tablet + mobile.
- Tabel data: **horizontal scroller** (tidak melebar melampaui viewport, tidak
  menumpuk).
- Light/Dark mode: **default ikut sistem OS** (`prefers-color-scheme`), dengan
  toggle manual yang **tersimpan** (preferensi user persist antar sesi).
- Konsistensi dicapai lewat **konvensi kelas Tailwind** (tanpa design-token
  system baru).
- Layout tidak melebar / tidak menumpuk di layar kecil.
- Seluruh halaman dirombak: Landing, Login, Register, Dashboard, + 12 modul
  (Index, Show, Form/Create-Edit).

## Constraint (HARD)
HANYA kode frontend/UI yang diubah:
- `resources/js/**` (React/TSX)
- `resources/css/**` (Tailwind)
- Komponen UI di `resources/js/Components/**`

Backend Laravel SAMA SEKALI TIDAK diubah:
- Tidak ubah Controller, Model, Policy, Route, Seeder, Migration, Service.
- Tidak ubah perilaku API/Inertia, permission, logic bisnis.
- Tidak ubah respons data dari server.

## Out of Scope
- Perubahan backend, API, database, permission, role.
- Perubahan logic bisnis / perilaku fungsional selain visual & layout.
- Penambahan fitur baru (ini murni perombakan tampilan).

## Konvensi Teknis (dari interview)
- Dark mode: default = sistem OS, toggle manual persist (localStorage/cookie).
- Responsif: Tailwind breakpoints standar (sm/md/lg/xl).
- Konsistensi: kelas utilitas Tailwind yang disepakati, bukan token system baru.
- Scroller: wrapper `overflow-x-auto` pada tabel agar tidak melebar.

## Status
- Intent: CONFIRMED (explicit yes, 2026-07-15).
- Spec: belum ditulis (akan disusun setelah intent disepakati).
- Plan: akan dibuat setelah spec lengkap (lihat tasks/plan.md).
- Execution: BELUM dimulai.
