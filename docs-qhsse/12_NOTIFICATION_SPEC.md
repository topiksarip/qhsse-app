# Notification Specification

## 1. Channel

Awal:

- In-app
- Email

Lanjutan:

- WhatsApp
- Telegram
- Microsoft Teams

## 2. Event Matrix

| Event | Recipient | Timing | Channel |
|---|---|---|---|
| Incident submitted | QHSSE Officer, Supervisor | Immediate | In-app/email |
| Incident rejected | Reporter | Immediate | In-app/email |
| Action assigned | PIC | Immediate | In-app/email |
| Action due soon | PIC | H-7/H-3/H-1 | In-app/email |
| Action overdue | PIC, Supervisor | Daily/Configured | In-app/email |
| Document expiring | Owner | H-30/H-14/H-7 | In-app/email |
| Certificate expiring | Employee, Supervisor | H-60/H-30/H-7 | In-app/email |
| Permit approval requested | Approver | Immediate | In-app/email |
| Audit finding assigned | PIC | Immediate | In-app/email |

## 3. Template Rule

Setiap pesan harus berisi:

- Nomor record
- Judul
- Status
- Due date jika ada
- Link detail
- Required action

## 4. Anti-Spam Rule

- Reminder overdue digabung per user per hari bila terlalu banyak.
- User dapat melihat semua notifikasi di notification center.
