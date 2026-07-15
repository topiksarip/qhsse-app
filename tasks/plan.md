# Plan: QHSSE App — UI Overhaul (Frontend Only)

> Intent: `docs/intent/ui-overhaul.md` (CONFIRMED 2026-07-15)
> Constraint HARD: hanya kode frontend (`resources/js`, `resources/css`, komponen UI).
> Backend Laravel TIDAK diubah.

## Tujuan
Romawk seluruh tampilan frontend agar rapih, compact, konsisten (button/form),
responsif (mobile/tablet/desktop), dengan sidebar auto-hide (semua ukuran),
tabel horizontal-scroll, dan light/dark mode (default ikut OS + toggle persist).

## Keputusan Spec (konfirmasi / refine)
- [x] Sidebar: tombol buka + **auto-hide di semua ukuran** (tutup saat klik luar / pilih menu).
- [x] Tabel: **horizontal scroller** (`overflow-x-auto`), tidak melebar viewport.
- [x] Dark mode: **default = sistem OS** (`prefers-color-scheme`), toggle manual **tersimpan** (localStorage).
- [x] Konsistensi: **konvensi kelas Tailwind** (tanpa design-token system baru).
- [ ] 🔲 Palet warna pasti (light & dark): asumsi = putih/abu netral + 1 aksen biru/indigo; perlu Anda setujui atau kasih hex.
- [ ] 🔲 Urutan prioritas rombak 12 modul: asumsi = ikut urutan modul现有 (Incident → Audit → ... → NCR).
- [ ] 🔲 Apakah Landing page konten ikut dirapih atau hanya layout? asumsi = layout + sedikit penyederhanaan visual, tidak ubah copy/teks.

## Phases (urutan eksekusi)

### Phase 0 — Foundation (shell + theming)
- [ ] Buat `ThemeProvider`/hook dark mode: baca `prefers-color-scheme` → fallback, simpan ke localStorage, toggle manual, apply class `dark` di `<html>`.
- [ ] Overhaul `AuthenticatedLayout`:
  - Sidebar: tombol toggle, overlay/drawer di semua ukuran, auto-hide (klik luar / link).
  - Nav grup collapsible agar "baris banyak di mobile" teratasi.
  - Header konsisten (logo, user menu, theme toggle).
  - Pastikan tidak melebar di mobile/tablet/desktop.
- [ ] Setup `dark:` variants di Tailwind (pastikan `darkMode: 'class'` di tailwind.config).

### Phase 1 — Shared UI Primitives (konsistensi)
- [ ] `Button` / `DangerButton` ukuran seragam (sm/md/lg), padding & radius konsisten.
- [ ] `Card` / panel compact.
- [ ] `TableWrapper` horizontal-scroll + header sticky (gunakan di semua tabel).
- [ ] Input/Select/Textarea/Label seragam (sudah ada di `Components/UI`? validasi & samakan).
- [ ] `Badge`/`StatusBadge` konsisten di light & dark.
- [ ] Pastikan semua primitif punya `dark:` style.

### Phase 2 — Public Pages
- [ ] Landing page (responsif + light/dark).
- [ ] Login page (responsif, compact, theme toggle).
- [ ] Register page (jika aktif).

### Phase 3 — Dashboard
- [ ] Layout KPI cards responsif (grid yang tidak melebar), tema terang/gelap.

### Phase 4 — 12 Module Pages (Index / Show / Form)
Terapkan primitif Phase 1 + TableWrapper + sidebar baru. Urutan:
1. Incident (Index/Show/Form)
2. Audit
3. Security Incidents
4. Permit to Work
5. Environmental
6. Risk Management
7. Document Control
8. Investigation
9. Training Records
10. Inspection
11. CAPA
12. Quality / NCR
- Setiap modul: cek tombol Delete (sudah ada) tetap jalan & konsisten.

## HARD Rules selama eksekusi
- Tidak ubah Controller/Model/Policy/Route/Seeder/Migration/Service.
- Tidak ubah respons data server / prop Inertia (hanya cara menampilkan).
- Jika ternyata butuh data baru dari backend → STOP & laporkan (bukan ubah sendiri).

## Verifikasi
- `npm run build` bersih (tsc + vite) setelah setiap phase.
- Cek manual responsif (mobile/tablet/desktop) lewat browser preview.
- Dark mode: default ikut OS, toggle persist (reload tetap pilihannya).
- Sidebar auto-hide di semua ukuran.
- Tabel tidak melebar (horizontal scroll muncul di layar kecil).
- Tombol Delete & alur CRUD tetap berfungsi (regresi visual only).
- (Opsional) deploy ke Ubuntu-5 setelah semua phase + build hijau.

## Out of Scope
- Backend, API, DB, permission, role, logic bisnis.
- Fitur baru.

## Status
- Intent: CONFIRMED.
- Spec: draft di plan ini (🔲 butuh konfirmasi 3 poin di atas).
- Plan: DRAFT — menunggu konfirmasi user.
- Execution: BELUM dimulai.
