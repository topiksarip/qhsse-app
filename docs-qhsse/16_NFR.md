# Non-Functional Requirements

## 1. Performance

- List pages memakai pagination.
- Dashboard query dioptimasi dan dapat dicache.
- File upload memiliki batas ukuran.

## 2. Availability

- Backup database terjadwal.
- Backup file storage.
- Restore procedure diuji berkala.

## 3. Compatibility

- Browser modern: Chrome, Edge, Firefox, Safari.
- Responsive untuk desktop dan mobile browser.

## 4. Accessibility

- Form label jelas.
- Keyboard accessible.
- Contrast cukup.
- Error message informatif.

## 5. Maintainability

- Struktur modular.
- Core reusable.
- Business rule penting terdokumentasi.
- Test/UAT per modul.

## 6. Scalability

- Tidak perlu microservice di awal.
- Gunakan modular monolith sampai kompleksitas benar-benar menuntut pemisahan.
