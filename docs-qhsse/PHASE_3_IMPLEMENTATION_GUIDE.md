# Phase 3 Implementation Guide — Loading/Empty/Error States

**Date:** 2026-07-12  
**Status:** 🚧 IN PROGRESS  
**Components Available:** LoadingSkeleton, EmptyState, ErrorState, ToastContainer

---

## Overview

Phase 3 implements consistent loading, empty, and error states across all list/index pages using the reusable components created in Phase 1.

---

## Components Available

### 1. LoadingSkeleton
**Path:** `resources/js/Components/UI/LoadingSkeleton.tsx`

**Usage:**
```tsx
import LoadingSkeleton from '@/Components/UI/LoadingSkeleton';

// While data is loading
{isLoading && <LoadingSkeleton rows={5} />}
```

**Props:**
- `rows?: number` — Number of skeleton rows (default: 3)
- `height?: string` — Row height (default: 'h-12')
- `className?: string` — Additional classes

---

### 2. EmptyState
**Path:** `resources/js/Components/UI/EmptyState.tsx`

**Usage:**
```tsx
import EmptyState from '@/Components/UI/EmptyState';
import { Link } from '@inertiajs/react';

// When no data exists
{!isLoading && data.length === 0 && (
    <EmptyState
        title="No incidents found"
        description="Start by creating your first incident report"
        action={
            <Link
                href={route('incident.reports.create')}
                className="btn-primary"
            >
                Create Incident
            </Link>
        }
    />
)}
```

**Props:**
- `icon?: ReactNode` — Custom icon (optional, has default)
- `title: string` — Main message
- `description: string` — Supporting text
- `action?: ReactNode` — Action button/link
- `className?: string` — Additional classes

---

### 3. ErrorState
**Path:** `resources/js/Components/UI/ErrorState.tsx`

**Usage:**
```tsx
import ErrorState from '@/Components/UI/ErrorState';

// When fetch fails
{error && (
    <ErrorState
        message={error.message}
        retry={() => window.location.reload()}
    />
)}
```

**Props:**
- `title?: string` — Error title (default: "Error Loading Data")
- `message: string` — Error details
- `retry?: () => void` — Retry callback
- `action?: ReactNode` — Custom action
- `className?: string` — Additional classes

---

### 4. Toast Notifications
**Path:** `resources/js/Hooks/useToast.ts`

**Usage:**
```tsx
import { useToast } from '@/Hooks/useToast';

function MyComponent() {
    const toast = useToast();

    const handleSuccess = () => {
        toast.success('Operation completed!');
    };

    const handleError = () => {
        toast.error('Failed to save', 'Please try again later');
    };

    return (
        <button onClick={handleSuccess}>Save</button>
    );
}
```

**Methods:**
- `toast.success(title, message?, duration?)` — Green success toast
- `toast.error(title, message?, duration?)` — Red error toast
- `toast.warning(title, message?, duration?)` — Amber warning toast
- `toast.info(title, message?, duration?)` — Blue info toast

---

## Implementation Pattern for Index Pages

### Step 1: Add State Management

```tsx
import { useState } from 'react';
import LoadingSkeleton from '@/Components/UI/LoadingSkeleton';
import EmptyState from '@/Components/UI/EmptyState';
import ErrorState from '@/Components/UI/ErrorState';

export default function Index({ items }: { items: Item[] }) {
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // ... rest of component
}
```

### Step 2: Implement Conditional Rendering

```tsx
return (
    <AuthenticatedLayout>
        <Head title="Items" />

        <div className="py-10">
            <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                {/* Loading State */}
                {isLoading && (
                    <div className="rounded-lg border border-slate-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                        <LoadingSkeleton rows={5} height="h-16" />
                    </div>
                )}

                {/* Error State */}
                {!isLoading && error && (
                    <div className="rounded-lg border border-slate-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                        <ErrorState
                            message={error}
                            retry={() => window.location.reload()}
                        />
                    </div>
                )}

                {/* Empty State */}
                {!isLoading && !error && items.length === 0 && (
                    <div className="rounded-lg border border-slate-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                        <EmptyState
                            title="No items found"
                            description="Create your first item to get started"
                            action={
                                <Link
                                    href={route('items.create')}
                                    className="btn-primary"
                                >
                                    Create Item
                                </Link>
                            }
                        />
                    </div>
                )}

                {/* Data State */}
                {!isLoading && !error && items.length > 0 && (
                    <div className="space-y-4">
                        {items.map((item) => (
                            <ItemCard key={item.id} item={item} />
                        ))}
                    </div>
                )}
            </div>
        </div>
    </AuthenticatedLayout>
);
```

---

## Backend Flash Messages (Auto-Toast)

Laravel flash messages are automatically captured as toasts via `useToast` hook.

**Backend Example:**
```php
// IncidentController.php
public function store(Request $request)
{
    $incident = Incident::create($request->validated());
    
    return redirect()
        ->route('incident.reports.show', $incident)
        ->with('success', 'Incident report created successfully');
}

public function destroy(Incident $incident)
{
    $incident->delete();
    
    return redirect()
        ->route('incident.reports.index')
        ->with('success', 'Incident report deleted');
}
```

**Result:** Automatic toast notification appears (no frontend code needed!)

---

## Priority Pages for Implementation

### High Priority (Core Operations):
1. ✅ Dashboard — Already has states
2. 🚧 Inspection/Index.tsx — Implement loading/empty
3. 🚧 Investigation/Index.tsx — Implement loading/empty
4. 🚧 Capa/Index.tsx — Implement loading/empty

### Medium Priority:
5. Quality/Ncrs/Index.tsx
6. Environmental/Index.tsx
7. Permit/Index.tsx

### Lower Priority:
8. Emergency Preparedness pages
9. Admin pages (already have basic states)

---

## Testing Checklist

### For Each Page:
- [ ] Loading state shows skeleton while data loads
- [ ] Empty state shows when no data exists
- [ ] Empty state action button works (navigates to create page)
- [ ] Error state shows when fetch fails
- [ ] Error state retry button reloads page
- [ ] Toast notifications appear for CRUD operations
- [ ] Toast auto-dismisses after duration
- [ ] Toast can be manually dismissed

---

## Phase 3 Status

### ✅ Completed (Phase 3.1):
- ToastContainer component (135 lines)
- useToast hook with global state (110 lines)
- Integration with AuthenticatedLayout
- Auto-capture Inertia flash messages
- Build verified

### 🚧 In Progress (Phase 3.2):
- Implementation guide documented
- Example patterns provided
- Awaiting surgical edits on priority pages

### 📋 Remaining:
- Apply patterns to 8-10 key Index pages
- Test all states on each page
- Verify toast integration with backend

---

**Next Step:** Apply surgical edits to priority Index pages using the patterns above.
