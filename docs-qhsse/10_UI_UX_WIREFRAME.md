# UI/UX Wireframe Specification

## 1. Layout Utama

- Sidebar menu modular.
- Topbar berisi search, notification, profile.
- Breadcrumb.
- Content area.
- Responsive mobile layout.

## 2. Page Pattern

Setiap modul minimal punya:

- List page
- Create/edit form page
- Detail page
- Approval/review panel
- Attachment panel
- Comment/activity panel
- Export action

## 3. List Page

Komponen:

- Title
- Primary action button
- Search
- Filter
- Table
- Status badge
- Pagination
- Export dropdown

## 4. Detail Page

Komponen:

- Header nomor/status
- Summary card
- Detail tabs
- Workflow action buttons
- Attachment
- Comments
- Activity timeline

## 5. Mobile Rule

- Form laporan incident harus nyaman di mobile.
- Table berubah menjadi card list pada layar kecil.
- Tombol utama sticky di bawah untuk form panjang.

## 6. Empty/Error State

- Empty list menampilkan CTA sesuai permission.
- Error validasi dekat field.
- Loading state untuk submit dan upload.
