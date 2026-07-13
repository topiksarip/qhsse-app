# User Onboarding Guide - QHSSE App P1

**Version:** 1.0  
**Date:** 2026-07-13  
**Target:** End Users (All Roles)  
**Duration:** 2-4 hours training per role

---

## Overview

Welcome to the QHSSE App! This guide will help you get started with the system based on your role.

**Application URL:** http://18.192.98.211:8000

---

## 1. Getting Your Account

### Receiving Your Credentials

Your administrator will provide:
- ✉️ Email address (your login username)
- 🔑 Temporary password
- 👤 Your assigned role

**First Login Steps:**
1. Open browser and navigate to application URL
2. Enter your email and temporary password
3. **Change your password immediately** (recommended)
4. Verify your employee information is correct

---

## 2. Understanding Your Role

### Role Overview

| Role | Primary Responsibilities | Key Features |
|------|-------------------------|--------------|
| **Super Admin** | System configuration, user management | Full access to all features |
| **QHSSE Officer** | Incident management, compliance tracking | Create/review incidents, manage complaints |
| **Security Officer** | Visitor management, site security | Check-in visitors, security patrols |
| **Regular Employee** | Report incidents, view own records | Create incident reports (own data only) |

---

## 3. Navigation Basics

### Main Menu Structure

**Dashboard** (All Users)
- View your KPIs and recent activity
- Quick links to your frequent tasks

**Incidents** (Employees, Officers, Admins)
- Report new incidents
- View incident history
- Upload evidence

**Security → Visitors** (Security Officers, Admins)
- Check-in visitors
- Check-out visitors
- View visitor logs

**Quality → Complaints** (QHSSE Officers, Admins)
- Log customer complaints
- Track resolution
- Close resolved complaints

**Admin** (Super Admin only)
- User management
- Master data management
- Bulk imports
- Role & permission matrix

---

## 4. Core Workflows by Role

### For All Users

#### Changing Your Password
1. Click your name (top right)
2. Select "Profile" or "Settings"
3. Click "Change Password"
4. Enter current password
5. Enter new password (min 8 characters)
6. Confirm new password
7. Click "Update Password"

#### Viewing Notifications
1. Click bell icon (top right)
2. Review unread notifications
3. Click notification to view details
4. Mark as read when done

---

### For Regular Employees

#### Reporting an Incident

**When to Report:**
- Accidents (injuries, property damage)
- Near misses (close calls, no injury)
- Unsafe conditions (hazards)
- Unsafe acts (violations)
- Environmental spills

**Steps:**
1. Navigate to **Incidents** → **Create New**
2. Fill required fields:
   - **Incident Date & Time** (when it happened)
   - **Location** (where it happened)
   - **Category** (Accident, Near Miss, etc.)
   - **Severity** (Low, Medium, High, Critical)
   - **Description** (what happened - be specific)
   - **Immediate Action** (what was done immediately)
3. Add involved persons if any:
   - Click "Add Involved Person"
   - Select employee from list
   - Specify involvement type
4. Upload evidence (photos, documents):
   - Click "Upload Evidence"
   - Select file (max 10MB)
   - Add description
5. **Save as Draft** (if not complete) or **Submit** (if ready)

**Tips:**
- Be specific in descriptions (who, what, when, where, how)
- Include witness names in involved persons
- Take photos before cleanup if safe to do so
- Submit within 24 hours of incident

#### Viewing Your Incidents
1. Navigate to **Incidents** → **My Reports**
2. See list of your submitted incidents
3. Click incident number to view details
4. Check status: Draft, Submitted, Under Review, Closed

---

### For QHSSE Officers

#### Reviewing Incident Reports

**Your Responsibilities:**
- Review submitted incidents
- Approve or reject with feedback
- Track corrective actions
- Ensure proper closure

**Steps:**
1. Navigate to **Incidents** → **Pending Review**
2. Click incident to review
3. Read description and evidence
4. Make decision:
   - **Approve:** Click "Approve" → incident moves to investigation
   - **Reject:** Click "Reject" → enter reason → reporter gets notified
5. Add comments if needed
6. Assign for investigation if approved

#### Managing Customer Complaints

**Steps to Create:**
1. Navigate to **Quality** → **Complaints** → **Create New**
2. Fill required fields:
   - **Customer Name & Contact**
   - **Complaint Date**
   - **Description** (detailed issue)
   - **Priority** (Low, Medium, High, Urgent)
   - **Immediate Action** (temporary fix)
3. Assign to responsible person
4. Click "Submit"

**Steps to Close:**
1. Open complaint from list
2. Click "Close Complaint"
3. Enter **Resolution** (permanent fix applied)
4. Upload evidence of resolution if available
5. Click "Close"

---

### For Security Officers

#### Checking In Visitors

**When:**
- All external visitors entering premises
- Contractors, vendors, guests, delivery personnel

**Steps:**
1. Navigate to **Security** → **Visitors** → **Check In**
2. Fill visitor information:
   - **Name** (full name)
   - **Company** (if applicable)
   - **Purpose of Visit** (meeting, delivery, maintenance, etc.)
   - **Host Employee** (who they're visiting)
   - **Identity Type** (KTP / SIM / Passport / Lainnya)
   - **Identity Number**
   - **Phone Number**
   - **Vehicle Plate** (if applicable)
3. Click "Check In"
4. Print visitor badge if required
5. Note check-in time is recorded automatically

**Tips:**
- Verify identity document before check-in
- Ensure host employee is notified
- Issue visitor badge/pass
- Brief visitor on safety rules

#### Checking Out Visitors
1. Navigate to **Security** → **Visitors** → **Checked In**
2. Find visitor in list
3. Click "Check Out"
4. Confirm check-out
5. Collect visitor badge

---

### For Super Admin

#### Creating New Users

**Steps:**
1. Navigate to **Admin** → **Users** → **Create New**
2. Fill user details:
   - Email (will be username)
   - Name
   - Initial password
   - Link to employee record
3. Assign role(s)
4. Set status (Active/Inactive)
5. Click "Create User"
6. **Provide credentials to user securely**

#### Bulk Importing Master Data

**See separate guide:** "Master Data Import Instructions"

**Available Imports:**
- Sites
- Departments
- Positions
- Employees

#### Managing Roles & Permissions

**Steps:**
1. Navigate to **Admin** → **Role Matrix**
2. See all roles and their permissions
3. To update permissions:
   - Find role in list
   - Check/uncheck permissions
   - Click "Update Role"
4. Changes take effect immediately

**Important:**
- Super Admin role cannot be modified (protected)
- Cannot grant `core.roles.manage` via matrix
- Each role can have only one data scope

---

## 5. Common Tasks

### Uploading Evidence/Files

**Supported Formats:**
- Images: JPG, PNG, GIF (max 10MB)
- Documents: PDF, DOC, DOCX, XLS, XLSX (max 10MB)
- Videos: MP4 (max 50MB)

**Steps:**
1. Click "Upload" or "Add Evidence"
2. Choose file from your computer
3. Add description (what the file shows)
4. Click "Upload"
5. Wait for confirmation

### Downloading/Viewing Evidence

**Steps:**
1. Open incident/complaint with evidence
2. Click "Evidence" tab
3. Click file name or "Download" icon
4. File downloads to your computer

**Note:** You can only download evidence for records you have permission to view.

### Searching Records

**Quick Search:**
1. Use search box at top of list
2. Type keywords (incident number, name, location)
3. Press Enter
4. Results filter automatically

**Advanced Filters:**
1. Click "Filter" icon
2. Select criteria:
   - Date range
   - Status
   - Severity/Priority
   - Site/Department
3. Click "Apply Filters"
4. Results update

### Exporting Data (If Authorized)

**Steps:**
1. Navigate to list view (Incidents, Visitors, Complaints)
2. Apply filters if needed
3. Click "Export" → "Export CSV"
4. File downloads to your computer
5. Open in Excel or similar

---

## 6. Tips for Success

### Best Practices

**For Everyone:**
- ✅ Report incidents promptly (within 24 hours)
- ✅ Be specific in descriptions
- ✅ Upload clear photos/evidence
- ✅ Keep your profile updated
- ✅ Change password regularly
- ✅ Log out when done (shared computers)

**For QHSSE Officers:**
- ✅ Review incidents within 48 hours
- ✅ Provide clear feedback on rejections
- ✅ Follow up on corrective actions
- ✅ Document lessons learned

**For Security Officers:**
- ✅ Verify identity before check-in
- ✅ Brief visitors on safety rules
- ✅ Ensure all visitors check out
- ✅ Report suspicious activity

### Common Mistakes to Avoid

❌ Using weak passwords (use strong, unique passwords)  
❌ Sharing login credentials (each user needs own account)  
❌ Vague incident descriptions (be specific)  
❌ Forgetting to upload evidence (photos help investigation)  
❌ Not following up on your assigned actions  
❌ Reporting non-urgent issues as critical

---

## 7. Getting Help

### In-App Help
- Click **"?"** icon (top right) for context help
- Check tooltips (hover over field labels)

### Contact Support
- **QHSSE Team Email:** qhsse@yourcompany.com
- **IT Support:** itsupport@yourcompany.com
- **Phone:** +62 xxx xxxx xxxx (office hours)

### Feedback
- We want to hear from you!
- Use **"Feedback"** button (bottom right)
- Or email: qhsse-feedback@yourcompany.com

---

## 8. Training Checklist

### For Trainers

Use this checklist during training sessions:

**General (All Users):**
- [ ] Provide login credentials
- [ ] Demonstrate first login
- [ ] Show password change
- [ ] Explain main navigation
- [ ] Show dashboard overview
- [ ] Explain notifications
- [ ] Show profile settings

**Role-Specific:**

**Employees:**
- [ ] Walk through incident reporting
- [ ] Show evidence upload
- [ ] Demonstrate "My Reports" view
- [ ] Explain incident lifecycle

**QHSSE Officers:**
- [ ] Show pending review queue
- [ ] Walk through approve/reject
- [ ] Demonstrate complaint creation
- [ ] Show complaint close process

**Security Officers:**
- [ ] Walk through visitor check-in
- [ ] Show identity verification
- [ ] Demonstrate check-out
- [ ] Show visitor log/history

**Super Admin:**
- [ ] Show user creation
- [ ] Demonstrate role assignment
- [ ] Walk through role matrix
- [ ] Show bulk import process

**Wrap-Up:**
- [ ] Answer questions
- [ ] Provide this guide (printed/PDF)
- [ ] Share support contact info
- [ ] Schedule follow-up check-in

---

## 9. Quick Reference

### Login Information
- **URL:** http://18.192.98.211:8000
- **Username:** Your email address
- **Password:** Provided by admin (change on first login)

### Key Shortcuts
- **Dashboard:** Click logo (top left)
- **Notifications:** Bell icon (top right)
- **Profile:** Your name (top right)
- **Logout:** Profile menu → Logout

### Important Numbers
- **Emergency:** 112 (medical/fire/police)
- **Security Desk:** [Your site number]
- **QHSSE Hotline:** [Your hotline]

---

**Remember:** This system helps us create a safer workplace. Your participation matters!

**Questions?** Contact your QHSSE team or IT support.

---

**Document Version:** 1.0  
**Last Updated:** 2026-07-13  
**Next Review:** 2026-10-13
