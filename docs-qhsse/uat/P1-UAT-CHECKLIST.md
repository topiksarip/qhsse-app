# P1 Production UAT Checklist

**Executor:** Requires production login credentials
**Date:** 2026-07-13
**Prerequisite:** Deployment runbook completed successfully

## UAT Environment

- **URL:** http://18.192.98.211:8000
- **Test Accounts Required:**
  - Super Admin (full access)
  - QHSSE Officer (incident/security/quality permissions)
  - Quality Officer (quality-only permissions)
  - Security Officer (security-only permissions)
  - Site-scoped user (limited to one site)
  - Regular employee (minimal permissions)

## Pre-UAT Verification

- [ ] Deployment runbook completed without errors
- [ ] Services active: nginx, php8.4-fpm, postgresql, redis-server, qhsse-queue
- [ ] Asset manifest timestamp updated
- [ ] `/admin` route accessible (redirects to login for anonymous)
- [ ] `/core/roles` route accessible (redirects to login)
- [ ] No critical errors in last 50 log lines

## 1. Incident Evidence (Private File Upload/Download)

**Login as:** QHSSE Officer with `incident.reports.evidence` permission

**Test Case 1.1:** Upload evidence to existing draft incident
- [ ] Navigate to existing draft incident report
- [ ] Click "Add Evidence" or evidence upload button
- [ ] Select a file (PDF, image, or document <10MB)
- [ ] Submit upload
- [ ] **VERIFY:** Success message displayed
- [ ] **VERIFY:** File appears in evidence list with checksum
- [ ] **VERIFY:** File stored in private storage (not `/public/uploads`)

**Test Case 1.2:** Download evidence with proper authorization
- [ ] While viewing incident with evidence
- [ ] Click download link for uploaded file
- [ ] **VERIFY:** File downloads successfully
- [ ] **VERIFY:** File content matches uploaded file
- [ ] **VERIFY:** Direct URL access without auth returns 403/401

**Test Case 1.3:** Evidence access control
- [ ] Logout, login as user without `incident.reports.view` permission
- [ ] Attempt to access incident detail URL directly
- [ ] **VERIFY:** Access denied
- [ ] **VERIFY:** Evidence download endpoint returns 403

## 2. Incident Reject Workflow

**Login as:** QHSSE Supervisor with `incident.reports.review` permission

**Test Case 2.1:** Reject incident with mandatory reason
- [ ] Navigate to submitted incident (status: Under Review)
- [ ] Click "Reject" button
- [ ] **VERIFY:** Reason field is displayed and required
- [ ] Submit without reason
- [ ] **VERIFY:** Validation error shown
- [ ] Enter rejection reason (e.g., "Insufficient details")
- [ ] Submit reject
- [ ] **VERIFY:** Status changes to "Rejected"
- [ ] **VERIFY:** Reason is displayed on detail page
- [ ] **VERIFY:** Activity log shows reject action with reason
- [ ] **VERIFY:** Reporter receives notification

**Test Case 2.2:** Rejected incident cannot be closed
- [ ] While viewing rejected incident
- [ ] **VERIFY:** "Close" button not available
- [ ] **VERIFY:** Edit/resubmit options available to reporter

## 3. Incident Involved Persons

**Login as:** QHSSE Officer with `incident.reports.create` permission

**Test Case 3.1:** Add involved persons during create
- [ ] Navigate to Create Incident Report
- [ ] Fill required fields: title, date, time, location, site, area, department
- [ ] Scroll to "Involved Persons" section
- [ ] Click "Add Involved Person"
- [ ] Select employee from dropdown (should be filtered by selected site)
- [ ] Add note/description for involvement
- [ ] Click "Add Involved Person" again
- [ ] Add second employee
- [ ] Submit form as draft
- [ ] **VERIFY:** Both employees saved
- [ ] **VERIFY:** Notes displayed on detail page

**Test Case 3.2:** Update involved persons on existing incident
- [ ] Navigate to existing draft incident
- [ ] Click Edit
- [ ] Remove one involved person
- [ ] Add a different involved person
- [ ] Save changes
- [ ] **VERIFY:** Changes persisted correctly
- [ ] **VERIFY:** Activity log shows involved person update

**Test Case 3.3:** Site-scoped employee filter
- [ ] Start creating incident, select Site A
- [ ] Open involved person employee dropdown
- [ ] **VERIFY:** Only employees from Site A displayed
- [ ] Change site to Site B
- [ ] **VERIFY:** Employee dropdown resets
- [ ] **VERIFY:** Only Site B employees displayed

## 4. Incident Print Report

**Login as:** QHSSE Officer with `incident.reports.export` permission

**Test Case 4.1:** Generate print-ready report
- [ ] Navigate to completed incident with evidence and workflow history
- [ ] Click "Print Report" or PDF icon
- [ ] **VERIFY:** Browser opens print-ready page in new tab
- [ ] **VERIFY:** Report contains: incident details, involved persons, workflow history, evidence list with checksums
- [ ] Use browser "Save as PDF" or Print
- [ ] **VERIFY:** PDF generated successfully
- [ ] **VERIFY:** All data readable and properly formatted

**Test Case 4.2:** Print authorization
- [ ] Logout, login as user with only `incident.reports.view` (no export)
- [ ] Navigate to incident detail
- [ ] **VERIFY:** Print button not visible or returns 403

## 5. Visitor Log Check-In/Checkout

**Login as:** Security Officer with `security.visitor.log` permission

**Test Case 5.1:** Check-in visitor
- [ ] Navigate to Visitor Log → Create
- [ ] Fill: visitor name, company, identity type (KTP), identity number, purpose, site, host employee
- [ ] **VERIFY:** Host employee dropdown filtered by selected site
- [ ] Submit check-in
- [ ] **VERIFY:** Check-in time recorded, status "In Premises"
- [ ] **VERIFY:** Activity log shows check-in

**Test Case 5.2:** Check-out visitor
- [ ] Navigate to Visitor Log list
- [ ] Find checked-in visitor (status: In Premises)
- [ ] Click "Check Out" button
- [ ] **VERIFY:** Check-out confirmation modal
- [ ] Confirm check-out
- [ ] **VERIFY:** Check-out time recorded, status "Checked Out"
- [ ] **VERIFY:** Activity log shows check-out with exact timestamp
- [ ] **VERIFY:** Audit trail created

**Test Case 5.3:** Duplicate check-out prevention
- [ ] Navigate to already checked-out visitor
- [ ] **VERIFY:** Check-out button disabled or not visible
- [ ] Attempt direct API call to check-out endpoint (if testing tools available)
- [ ] **VERIFY:** Backend returns error "already checked out"

**Test Case 5.4:** Site-scoped visitor list
- [ ] Login as site-scoped security officer (Site A only)
- [ ] Navigate to Visitor Log list
- [ ] **VERIFY:** Only Site A visitors displayed
- [ ] **VERIFY:** Cannot see visitors from Site B
- [ ] Navigate to Create Visitor
- [ ] **VERIFY:** Site dropdown shows only Site A or is pre-selected

## 6. Customer Complaint Creation and Close

**Login as:** Quality Officer with `quality.complaints.create` and `quality.complaints.close` permissions

**Test Case 6.1:** Create numbered complaint
- [ ] Navigate to Customer Complaints → Create
- [ ] Fill: customer name, complaint description (>50 chars), severity, priority, site, area, department
- [ ] Submit
- [ ] **VERIFY:** Complaint created with auto-generated CCR number
- [ ] **VERIFY:** Status "Open"
- [ ] **VERIFY:** Activity log shows creation

**Test Case 6.2:** Close complaint with resolution
- [ ] Navigate to open complaint
- [ ] Click "Close" button
- [ ] **VERIFY:** Resolution field displayed and required
- [ ] Submit without resolution
- [ ] **VERIFY:** Validation error
- [ ] Enter resolution text (e.g., "Replaced defective product")
- [ ] Submit close
- [ ] **VERIFY:** Status "Closed", closed_at timestamp set
- [ ] **VERIFY:** Resolution displayed on detail page
- [ ] **VERIFY:** Audit trail shows close action

**Test Case 6.3:** Closed complaint cannot be edited or re-closed
- [ ] While viewing closed complaint
- [ ] **VERIFY:** Edit button disabled or not visible
- [ ] **VERIFY:** Close button not visible
- [ ] Attempt direct edit URL (if testing tools available)
- [ ] **VERIFY:** Backend returns error

**Test Case 6.4:** Least-privilege permission check
- [ ] Logout, login as user with only `quality.complaints.view`
- [ ] Navigate to Complaints list
- [ ] **VERIFY:** Can view list
- [ ] Navigate to complaint detail
- [ ] **VERIFY:** Can view detail
- [ ] **VERIFY:** Create button not visible
- [ ] **VERIFY:** Edit button not visible
- [ ] **VERIFY:** Close button not visible

## 7. Role-Permission Matrix

**Login as:** Super Admin with `core.roles.manage` permission

**Test Case 7.1:** View matrix and select role
- [ ] Navigate to `/core/roles`
- [ ] **VERIFY:** Role selector displayed with all roles
- [ ] **VERIFY:** Permission list grouped by module
- [ ] Select "QHSSE Officer" role
- [ ] **VERIFY:** Current permissions checked
- [ ] **VERIFY:** Search/filter permissions works

**Test Case 7.2:** Update role permissions
- [ ] Select a non-Super Admin role
- [ ] Check/uncheck several permissions (e.g., add `quality.ncrs.export`)
- [ ] Click "Save Changes"
- [ ] **VERIFY:** Success message displayed
- [ ] **VERIFY:** Audit trail shows permission sync with role name and permission delta
- [ ] Reload page
- [ ] **VERIFY:** Changes persisted

**Test Case 7.3:** Super Admin immutability
- [ ] Select "Super Admin" role
- [ ] **VERIFY:** Permission checkboxes disabled or read-only
- [ ] **VERIFY:** "Super Admin is immutable" badge/message displayed
- [ ] **VERIFY:** Save button disabled or not shown

**Test Case 7.4:** Privilege escalation protection
- [ ] Select any non-Super Admin role
- [ ] **VERIFY:** `core.roles.manage` permission not in the list or grayed out
- [ ] Attempt to check it (if enabled)
- [ ] **VERIFY:** Backend rejects update

## 8. Bulk Import (CSV)

**Login as:** Super Admin with `core.employees.create` permission

**Test Case 8.1:** Download employee template
- [ ] Navigate to `/admin/import`
- [ ] Select import type "Employees"
- [ ] Click "Download Template"
- [ ] **VERIFY:** CSV file downloaded
- [ ] **VERIFY:** Headers: employee_no, name, email, phone, company_code, site_code, department_code, position_code, is_active

**Test Case 8.2:** Import valid employee CSV
- [ ] Prepare CSV with 3-5 valid employee rows (use existing company/site/department/position codes)
- [ ] Select file in import form
- [ ] Click "Import"
- [ ] **VERIFY:** Progress indicator shown
- [ ] **VERIFY:** Success message with count (e.g., "5 employees imported")
- [ ] **VERIFY:** Audit trail shows import summary (type, row count, filename)
- [ ] Navigate to Employees list
- [ ] **VERIFY:** New employees appear

**Test Case 8.3:** Atomic validation (all-or-nothing)
- [ ] Prepare CSV with 3 valid rows and 1 invalid row (e.g., missing email)
- [ ] Submit import
- [ ] **VERIFY:** Error message displayed
- [ ] **VERIFY:** Error specifies row number and issue (e.g., "Row 4: email is required")
- [ ] **VERIFY:** Zero employees imported (transaction rolled back)
- [ ] Navigate to Employees list
- [ ] **VERIFY:** None of the 4 rows imported

**Test Case 8.4:** 1,000 row limit
- [ ] Prepare CSV with 1,001 rows (or test with smaller limit if documented)
- [ ] Submit import
- [ ] **VERIFY:** Error message "Maximum 1,000 rows exceeded"
- [ ] **VERIFY:** No import attempted

## 9. Admin Dashboard

**Login as:** Super Admin with `core.sites.view` permission

**Test Case 9.1:** View KPI cards
- [ ] Navigate to `/admin`
- [ ] **VERIFY:** 5 KPI cards displayed: Total Users, Active Users, Total Employees, Total Sites, Total Companies
- [ ] **VERIFY:** Numbers match approximate expected counts
- [ ] **VERIFY:** Each card has icon and label

**Test Case 9.2:** Recent audit entries
- [ ] While on Admin Dashboard
- [ ] **VERIFY:** Table shows 10 most recent audit entries
- [ ] **VERIFY:** Columns: timestamp, user, action, module
- [ ] **VERIFY:** Entries sorted newest first
- [ ] Perform an action (e.g., edit a site) in another tab
- [ ] Reload Admin Dashboard
- [ ] **VERIFY:** New audit entry appears at top

**Test Case 9.3:** Permission-gated quick links
- [ ] While on Admin Dashboard as Super Admin
- [ ] **VERIFY:** Quick links section displays
- [ ] **VERIFY:** Links present: Companies, Sites, Departments, Positions, Employees, Users, Roles, Bulk Import
- [ ] Logout, login as user with only `core.sites.view`
- [ ] Navigate to `/admin`
- [ ] **VERIFY:** Dashboard accessible
- [ ] **VERIFY:** Quick links filtered (only links user has permission for)

## 10. Inactive User Session Termination

**Setup:** Create or identify test user account

**Test Case 10.1:** Active user can access protected routes
- [ ] Login as test user
- [ ] Navigate to `/dashboard`
- [ ] **VERIFY:** Dashboard loads successfully
- [ ] Navigate to any module page (e.g., `/incident-reports`)
- [ ] **VERIFY:** Page loads successfully

**Test Case 10.2:** Deactivate user mid-session
- [ ] Keep test user logged in (session active)
- [ ] In separate admin session, navigate to Users management
- [ ] Edit test user, set `is_active = false` or click "Deactivate"
- [ ] Save changes
- [ ] **Return to test user browser session** (still has valid session cookie)
- [ ] Navigate to any protected route (e.g., `/dashboard` or `/core/sites`)
- [ ] **VERIFY:** User redirected to login page
- [ ] **VERIFY:** Session invalidated
- [ ] **VERIFY:** Message displayed (e.g., "Your account has been deactivated")

**Test Case 10.3:** Inactive user cannot login
- [ ] Logout from test user (if still logged in)
- [ ] Attempt to login with deactivated user credentials
- [ ] **VERIFY:** Login rejected with appropriate message
- [ ] **VERIFY:** User not authenticated

## UAT Sign-Off

All test cases must PASS before marking P1 as Released.

**Executed By:** _________________
**Date:** _________________
**Signature:** _________________

**Critical Issues Found:** _________________

**Acceptable Minor Issues:** _________________

**Approved for Release:** [ ] YES [ ] NO

If NO, document blocking issues and required remediation before retry.
