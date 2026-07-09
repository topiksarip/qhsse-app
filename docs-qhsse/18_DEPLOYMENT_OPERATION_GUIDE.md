# Deployment & Operation Guide

## 1. Environment

- Development
- Staging/UAT
- Production

## 2. Configuration

- App URL
- Database connection
- File storage path/bucket
- Mail server
- Queue worker optional
- Backup schedule

## 3. Deployment Steps

1. Pull/build application.
2. Install dependencies.
3. Run migration.
4. Seed master data baseline.
5. Build frontend assets.
6. Restart service/worker.
7. Run smoke test.

## 4. Backup

- Database daily.
- File storage daily/incremental.
- Retain backup sesuai kebijakan.
- Test restore berkala.

## 5. Monitoring

- Application error log.
- Failed job/notification.
- Disk usage.
- Database size.
- Backup status.

## 6. Rollback

- Simpan artifact release sebelumnya.
- Database migration harus punya rollback plan.
- Backup sebelum release production.
