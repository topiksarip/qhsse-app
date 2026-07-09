# Handoff Protocol

## 1. Tujuan

Handoff memastikan setiap hasil generating dapat dilanjutkan oleh sesi/agent/developer berikutnya tanpa kehilangan konteks, tanpa keluar jalur, dan tanpa mengulang analisis dari awal.

## 2. Lokasi Handoff

Semua handoff disimpan di:

```text
handoff/
```

Format nama:

```text
PHASE-{number}-{module-slug}-HANDOFF.md
```

Contoh:

```text
handoff/PHASE-00-core-foundation-HANDOFF.md
handoff/PHASE-01-incident-reporting-HANDOFF.md
```

## 3. Template Handoff

```markdown
# Handoff — Phase [N] [Module Name]

## 1. Status

- Phase:
- Status: Completed / Partial / Blocked
- Date:
- Executor:

## 2. Scope Dikerjakan

- ...

## 3. Scope Tidak Dikerjakan

- ...

## 4. File/Folder Dibuat

- `path/to/file`

## 5. File/Folder Diubah

- `path/to/file`

## 6. Database/Migration/Model

- ...

## 7. API/Backend

- Method + endpoint + purpose.

## 8. UI/Frontend

- Page/component + purpose.

## 9. Permission Ditambahkan

- `module.action`

## 10. Master Data/Seed Ditambahkan

- ...

## 11. Workflow/Status Ditambahkan

- ...

## 12. Notification Ditambahkan

- Event -> recipient -> channel.

## 13. Report/Export Ditambahkan

- ...

## 14. Test Dijalankan

- Command/manual scenario.

## 15. Hasil Test

- Passed:
- Failed:
- Not tested:

## 16. Known Issues

- ...

## 17. Deferred Items

- ...

## 18. Decision Log Update

- Decision added/not added.

## 19. Breaking Changes

- ...

## 20. Next Phase Readiness

- Ready / Not Ready.
- Reason.

## 21. Rekomendasi Prompt Berikutnya

```text
Lanjutkan Phase [N+1] — [Module].
Baca docs-qhsse/23_EXECUTION_PLAN.md dan handoff terakhir: handoff/[file].
Kerjakan hanya scope phase tersebut dan buat handoff setelah selesai.
```
```

## 4. Handoff Minimum Acceptance

Handoff valid jika menjawab:

1. Apa yang berubah?
2. Di file mana?
3. Bagaimana menjalankan/test?
4. Apa yang belum selesai?
5. Apa risiko/known issue?
6. Apa langkah berikutnya?

## 5. Rule

- Tidak boleh lanjut phase tanpa handoff phase sebelumnya.
- Jika phase partial, phase berikutnya hanya boleh mulai jika blocker tidak mempengaruhi dependency.
- Semua deferred item harus masuk backlog.
