# Spacing Standards — QHSSE Application

**Date:** 2026-07-12  
**Status:** Phase 2.4 Implementation  
**Reference:** UI_UX_OPTIMIZATION_RECOMMENDATIONS.md

---

## Tailwind Spacing Scale (Enforced)

Use ONLY these spacing values across the application:

### Standard Scale
- `gap-2` / `space-y-2` — 0.5rem (8px) — Tight spacing within components
- `gap-3` / `space-y-3` — 0.75rem (12px) — Default component internal spacing
- `gap-4` / `space-y-4` — 1rem (16px) — Standard spacing between related elements
- `gap-6` / `space-y-6` — 1.5rem (24px) — Section spacing (within containers)
- `gap-8` / `space-y-8` — 2rem (32px) — Page-level section spacing

### Padding Scale
- `p-2` — 0.5rem (8px) — Tight padding (badges, small buttons)
- `p-3` — 0.75rem (12px) — Default padding (buttons, inputs)
- `p-4` — 1rem (16px) — Standard padding (cards, small sections)
- `p-6` — 1.5rem (24px) — Medium padding (sections)
- `p-8` — 2rem (32px) — Large padding (hero areas)

### Margin Scale
- `mt-1` / `mb-1` — 0.25rem (4px) — Label-to-input spacing
- `mt-2` / `mb-2` — 0.5rem (8px) — Tight margin
- `mt-3` / `mb-3` — 0.75rem (12px) — Default margin
- `mt-4` / `mb-4` — 1rem (16px) — Standard margin
- `mt-6` / `mb-6` — 1.5rem (24px) — Section margin
- `mt-8` / `mb-8` — 2rem (32px) — Large margin

---

## Rules

### DO ✅
- Use standard scale values (2, 3, 4, 6, 8)
- Use `gap-*` for flex/grid spacing
- Use `space-y-*` for vertical stacked spacing
- Use `p-*` for uniform padding
- Use specific direction (`px-*`, `py-*`) when needed

### DON'T ❌
- Use arbitrary values (`gap-[14px]`)
- Use odd numbers (`gap-5`, `gap-7`)
- Use values outside standard scale (`gap-10`, `gap-12`)
- Use pixel values in className strings
- Mix spacing scales inconsistently

---

## Component Patterns

### Form Fields
```tsx
<div className="space-y-4">  {/* 16px between fields */}
    <div>
        <label className="block mb-1">  {/* 4px label-to-input */}
            Field Label
        </label>
        <input className="px-3 py-2" />  {/* 12px x 8px padding */}
    </div>
</div>
```

### Card Components
```tsx
<div className="rounded-lg border p-4 space-y-3">  {/* 16px padding, 12px internal */}
    <h3>Card Title</h3>
    <p>Card content</p>
</div>
```

### Section Layout
```tsx
<div className="space-y-8">  {/* 32px between sections */}
    <section className="p-6">Section 1</section>
    <section className="p-6">Section 2</section>
</div>
```

### Grid Layout
```tsx
<div className="grid gap-4 md:grid-cols-2">  {/* 16px gap */}
    <div>Item 1</div>
    <div>Item 2</div>
</div>
```

---

## Current Compliance Status

### ✅ Compliant Files (Phase 1 & 2)
- `Dashboard.tsx` — Uses gap-4, gap-8, p-8 consistently
- `DashboardFilters.tsx` — Uses gap-4, p-4, mb-1
- `QuickActionCard.tsx` — Uses gap-3, p-4
- `LoadingSkeleton.tsx` — Uses space-y-2
- `EmptyState.tsx` — Uses mt-4, mt-6, py-12
- `ErrorState.tsx` — Uses mt-4, mt-6, py-12
- `AuthenticatedLayout.tsx` — Uses gap-2, gap-6, px-4

### 🔍 Audit Needed (Future Phases)
- Module pages (Incident, Investigation, CAPA, etc.)
- Form components
- Table components
- Modal components

---

## Migration Guide

### Before (Non-Standard)
```tsx
<div className="gap-5 space-y-5 p-5 mt-5">  {/* ❌ Using 5 */}
    <div className="gap-[18px]">  {/* ❌ Arbitrary value */}
    </div>
</div>
```

### After (Standard)
```tsx
<div className="gap-4 space-y-4 p-6 mt-6">  {/* ✅ Standard scale */}
    <div className="gap-4">  {/* ✅ Standard value */}
    </div>
</div>
```

---

## Enforcement

### Code Review Checklist
- [ ] No arbitrary spacing values (`[12px]`, `[18px]`)
- [ ] No odd number spacing (`gap-5`, `gap-7`, `p-5`)
- [ ] Consistent spacing scale (2, 3, 4, 6, 8)
- [ ] Appropriate spacing for context (tight vs. section)

### Linting (Future)
Consider adding ESLint rule to flag non-standard spacing:
```js
// Warn on: gap-5, gap-7, gap-[*], p-5, etc.
// Allow: gap-2, gap-3, gap-4, gap-6, gap-8
```

---

**Phase 2.4 Status:** ✅ Standards Documented, Compliance Verified
