# Execution Plan

1. Setiap phase baca docs & handoff sebelumnya.
2. Gunakan core service yang ada (jangan duplikasi).
3. Vertical slice DB→UI, dengan test (happy/permission/edge).
4. `npm run build` + `make test` sebelum klaim selesai.
5. Update changelog + decision log + handoff.
6. Deploy urutan aman (push → pull → build → migrate --force → restart).
