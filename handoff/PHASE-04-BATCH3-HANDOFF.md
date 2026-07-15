# Phase 4 — Batch 3 + Consistency Pass — Handoff

**Date:** 2026-07-16
**Commit:** `8e7f1b5` (develop → origin/develop)
**Deployed to:** 18.192.98.211 (ubuntu-5) `/var/www/qhsse-app` — verified live.

## Scope
Frontend overhaul of remaining module **Index** pages (Phase 4 continued). Backend untouched.

## Files rewritten (19 Index pages)
Batch 3 planned (15):
- Modules/Incident/Index (also fixed nested-`<td>` bug)
- Modules/LegalCompliance/Index
- Modules/RiskManagement/Index (also fixed nested-`<td>` bug)
- Modules/Contractor/Index (kept export + structure)
- Modules/Training/Programs/Index
- Modules/Training/Records/Index
- Modules/Training/Matrix/Index
- Modules/EmergencyPreparedness/Plans/Index (red domain accent kept, CTAs emerald)
- Modules/EmergencyPreparedness/Contacts/Index
- Modules/EmergencyPreparedness/Drills/Index
- Modules/Security/Patrols/Index
- Modules/Security/VisitorLog/Index
- Modules/Communication/Campaign/Index
- Modules/Reporting/ReportTemplate/Index
- Modules/DocumentControl/Index (fixed nested-`<td>` bug)

Consistency pass (4 more found with indigo/blue CTAs + `py-12` legacy spacing):
- Modules/Inspection/Templates/Index
- Modules/Asset/Inspection/Index (kept literal `/assets/...` routes)
- Modules/Asset/Certificate/Index (kept literal `/assets/...` routes)
- Modules/Quality/CustomerComplaint/Index

## Conventions applied (all pages)
- Eyebrow + `<h2>` title header in `AuthenticatedLayout` `header` slot (no duplicate in-page h1).
- CTAs via `PrimaryButton`/`SecondaryButton` (emerald-600) with `href` → Inertia `<Link>`.
- Tables wrapped in `TableWrapper` (`TableHead`/`TableBody`, horizontal scroller).
- Split action column (View / Edit / Publish / Check-Out / etc.) — no nested-`<td>` delete.
- Emerald focus rings (`focus:border-emerald-500 focus:ring-emerald-500`).
- Dark-mode classes (`dark:bg-gray-900`, `dark:border-gray-700`, `dark:text-...`).
- Emergency module keeps red/amber domain accents for badges/CTAs (semantic).
- Pagination: `Pagination` component (emerald active page) or per-page number links (emergency = red active to match domain).

## Bugs fixed
- Incident/Index, DocumentControl/Index, RiskManagement/Index: delete button was nested inside another `<td>` (reporter/owner/show-link cell) — broke table render. Fixed by giving action its own column.
- Inspection/Templates, Asset/Inspection, Asset/Certificate, Quality/CustomerComplaint: replaced `bg-indigo-600`/`bg-blue-600` CTAs + `py-12` legacy spacing with emerald + `py-6`/`TableWrapper`.

## Verification
- `npx tsc --noEmit`: clean (no TS errors).
- `npm run build`: ✓ 1485 modules transformed, clean.
- Grep residual scan for `bg-indigo-600|bg-blue-600|focus:ring-indigo|text-indigo-600`: 0 matches across all module Index pages.
- Deploy: `git pull --ff-only` + `npm ci` + `npm run build` + bootstrap/cache safeguard + `optimize:clear` + restart php8.3-fpm + qhsse-queue → exit 0.
- Code-splitting intact: each Batch-3 page body present in exactly 1 dedicated `Index-*.js` chunk.

## Remaining (not done in Phase 4)
- **Show / Form pages** of all 12 modules still use the old emerald-ish/slate style and are NOT yet restyled to the new convention. That is the next frontend batch if requested.
- Landing / Login / Register / Dashboard were done in earlier phases.
