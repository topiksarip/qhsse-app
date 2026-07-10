# Workflow — Dashboard & KPI

> **Module ID:** `01-dashboard-kpi`

## No workflow — dashboard is read-only aggregation.

The Dashboard & KPI module does not have a workflow. It is a read-only aggregation layer that queries data from other modules (Incident Reporting, and future modules). There are no state transitions, no status changes, no approvals, no submissions, and no lifecycle management.

### Why No Workflow

| Property | Other Modules | Dashboard Module |
|---|---|---|
| Has workflow definition | Yes (e.g., `INCIDENT_WORKFLOW`) | **No** |
| Has workflow instances | Yes (`workflow_instances` rows) | **No** |
| Has workflow histories | Yes (`workflow_histories` rows) | **No** |
| Has status field | Yes (draft, submitted, closed, etc.) | **No** |
| Has transitions | Yes (submit, review, close, reject) | **No** |
| Creates records | Yes | **No** (read-only SELECT queries only) |
| Requires audit trail | Yes | **No** (read operations are not audited) |

### What Dashboard Does Instead

1. **Reads** data from `incidents` table (and future module tables).
2. **Aggregates** counts and groupings via Eloquent query builder.
3. **Returns** pre-computed KPIs, chart data, and table widgets as Inertia props.
4. **Respects** user data scope (own / department / site / company / all) at the query level.
5. **Applies** date range, site, and department filters server-side before aggregation.

No WorkflowService, NumberingService, AuditService, CommentService, or ActivityService integration is needed.
