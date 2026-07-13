# Master Data Bulk Import Guide

**Version:** 1.0  
**Date:** 2026-07-13  
**Target:** Super Admin  
**Prerequisites:** Admin access, CSV templates

---

## Overview

Bulk import allows you to add multiple records at once via CSV files. This is much faster than manual entry for large datasets.

**What You Can Import:**
- Sites (locations)
- Departments
- Positions
- Employees

**Benefits:**
- ✅ Fast: Import 100s of records in seconds
- ✅ Accurate: Validate before commit
- ✅ Safe: Atomic (all-or-nothing)
- ✅ Auditable: Full audit trail

---

## Before You Start

### 1. Prepare Your Data

**Gather Information:**
- Organization structure (sites, departments)
- Position hierarchy
- Employee roster with contact details

**Data Quality Check:**
- ✅ No duplicate employee numbers
- ✅ Email addresses unique and valid
- ✅ Phone numbers in consistent format
- ✅ All required fields populated

### 2. Download Templates

**Steps:**
1. Login as Super Admin
2. Navigate to **Admin** → **Import**
3. Click **"Download Template"** for each entity type
4. Save templates to your computer

**Available Templates:**
- `sites-template.csv`
- `departments-template.csv`
- `positions-template.csv`
- `employees-template.csv`

**Sample Data Included:**
Sample CSV files are available in:
- `docs-qhsse/onboarding/templates/`
- Use these as reference for data format

---

## Import Sequence (IMPORTANT!)

**Always import in this order:**

1. **Sites** (locations)
2. **Departments** (references sites via `site_code`)
3. **Positions** (references site+department via codes)
4. **Employees** (references site+department+position via codes)

**Why?** Each entity references the previous ones. Import out of order will fail validation.

---

## Step-by-Step Import Process

### Step 1: Import Sites

**CSV Format:**
```csv
name,code,address,phone,is_active
Headquarters,HQ,"Jl. Sudirman 123, Jakarta",+62 21 1234 5678,true
Plant Jakarta,JKT,"Jl. Industri 45, Jakarta Timur",+62 21 8765 4321,true
```

**Field Requirements:**
- `name`: Site name (required, max 255 chars)
- `code`: Unique site code (required, 2-10 chars, uppercase)
- `address`: Full address (optional, max 500 chars)
- `phone`: Contact number (optional, format: +62 xxx xxxx xxxx)
- `is_active`: true or false (required)

**Tips:**
- Use short, memorable codes (HQ, JKT, BKS)
- Codes must be unique across all sites
- Use quotes for addresses with commas

**Import Steps:**
1. Open template, fill data
2. Save as CSV (UTF-8 encoding)
3. Go to **Admin** → **Import** → **Sites**
4. Click **"Choose File"**, select your CSV
5. Click **"Validate"**
6. Review validation results
7. If OK, click **"Import"**
8. Wait for confirmation

**Validation Checks:**
- ✓ All required fields present
- ✓ Site codes unique
- ✓ Data types correct

---

### Step 2: Import Departments

**CSV Format:**
```csv
site_code,name,code,is_active
HQ,QHSSE Department,QHSSE,true
HQ,Human Resources,HR,true
JKT,Production,PROD,true
```

**Field Requirements:**
- `site_code`: Must match existing site code (required)
- `name`: Department name (required, max 255 chars)
- `code`: Unique department code within site (required, 2-10 chars)
- `is_active`: true or false (required)

**Tips:**
- Same department code can exist in multiple sites
- Uniqueness is per site (HQ-PROD and JKT-PROD both OK)
- Site code must exist before import

**Import Steps:**
Same as sites, but select **Departments** import

**Validation Checks:**
- ✓ Site codes exist
- ✓ Department codes unique per site
- ✓ All required fields present

---

### Step 3: Import Positions

**CSV Format:**
```csv
site_code,department_code,name,code,level,is_active
HQ,QHSSE,Manager,MGR,manager,true
HQ,QHSSE,Officer,OFF,staff,true
JKT,PROD,Operator,OPR,staff,true
```

**Field Requirements:**
- `site_code`: Must match existing site (required)
- `department_code`: Must match existing department in that site (required)
- `name`: Position title (required, max 255 chars)
- `code`: Position code (required, 2-10 chars)
- `level`: manager, supervisor, or staff (required)
- `is_active`: true or false (required)

**Tips:**
- Position references both site AND department
- Same code can exist across different departments
- Levels: manager > supervisor > staff

**Import Steps:**
Same process, select **Positions** import

**Validation Checks:**
- ✓ Site + department combination exists
- ✓ Position codes unique per department
- ✓ Level is valid value

---

### Step 4: Import Employees

**CSV Format:**
```csv
site_code,department_code,position_code,employee_no,name,email,phone,is_active
HQ,QHSSE,MGR,EMP001,Budi Santoso,budi@company.com,+62 811 1234 5678,true
JKT,PROD,OPR,EMP002,Siti Nurhaliza,siti@company.com,+62 812 2345 6789,true
```

**Field Requirements:**
- `site_code`: Must exist (required)
- `department_code`: Must exist in site (required)
- `position_code`: Must exist in department (required)
- `employee_no`: Unique employee number (required, max 50 chars)
- `name`: Full name (required, max 255 chars)
- `email`: Unique email address (required, valid format)
- `phone`: Phone number (optional, format: +62 xxx xxxx xxxx)
- `is_active`: true or false (required)

**Tips:**
- Employee numbers must be globally unique
- Email addresses must be globally unique
- Use consistent phone format
- Inactive employees can be imported (for historical data)

**Import Steps:**
Same process, select **Employees** import

**Validation Checks:**
- ✓ Site + department + position exists
- ✓ Employee numbers unique
- ✓ Email addresses unique
- ✓ Email format valid

---

## Common Issues & Solutions

### Issue 1: "Site code not found"

**Cause:** Importing departments/positions/employees before sites exist

**Solution:**
1. Import sites first
2. Verify sites imported successfully
3. Then retry department/position/employee import

---

### Issue 2: "Duplicate employee number"

**Cause:** Employee number already exists in database OR duplicate in CSV

**Solution:**
1. Check database for existing employee with that number
2. Check CSV file for duplicate rows
3. Use unique employee numbers
4. Update CSV and retry

---

### Issue 3: "Invalid email format"

**Cause:** Email address doesn't match email pattern

**Solution:**
1. Ensure format: name@domain.com
2. No spaces before/after
3. Valid domain extension (.com, .co.id, etc.)
4. Fix in CSV and retry

---

### Issue 4: "CSV encoding error"

**Cause:** CSV file not saved as UTF-8

**Solution:**
1. Open CSV in text editor or Excel
2. Save As → Choose UTF-8 encoding
3. Retry import

---

### Issue 5: "Some rows failed, all rejected"

**Cause:** Atomic validation - if ANY row fails, NOTHING imports

**Solution:**
1. Review validation error messages
2. Fix ALL errors in CSV
3. Retry import
4. All rows must be valid for import to succeed

---

## Best Practices

### Data Preparation

**Excel Tips:**
- Use Excel for easy editing
- Save as "CSV UTF-8 (Comma delimited)" format
- Don't use formulas in CSV
- Remove empty rows

**Data Validation:**
- Check for duplicates before import
- Verify all codes match (site→dept→pos)
- Test with small batch first (5-10 rows)
- Import full dataset after successful test

### Staging Approach

**Recommended Process:**
1. **Pilot:** Import 5-10 test records
2. **Verify:** Check imported data in app
3. **Adjust:** Fix any issues in template
4. **Scale:** Import full dataset
5. **Audit:** Review audit log after import

### Backup

**Before Large Imports:**
- Request database backup from IT
- Keep original CSV files
- Document what was imported when

---

## Verification After Import

### Check Import Success

**Immediately After:**
1. Go to respective admin page (Sites/Departments/Positions/Employees)
2. Verify record count matches CSV row count
3. Spot-check random records for accuracy
4. Check audit log: **Admin** → **Audit Trail**

### Audit Trail

**What's Recorded:**
- Import timestamp
- Who imported (your user)
- Entity type (Sites, Employees, etc.)
- Number of records imported
- Operation: "Bulk Import"

**How to Check:**
1. **Admin** → **Audit Trail**
2. Filter by today's date
3. Look for "Bulk Import" operations
4. Verify counts match your CSV

---

## Troubleshooting

### Import Button Disabled

**Possible Causes:**
- File not selected
- File not CSV format
- Validation not run yet

**Solution:**
1. Ensure CSV file selected
2. Click "Validate" first
3. Wait for validation to complete
4. Import button enables if validation passes

### Import Takes Too Long

**Normal Duration:**
- 10-50 records: < 5 seconds
- 100-500 records: 5-30 seconds
- 1000+ records: 30-120 seconds

**If Stuck:**
- Wait 2 minutes
- Don't refresh page
- If no progress, check with IT support
- May need to retry import

### Partial Import

**Note:** The system does NOT do partial imports.

**Behavior:**
- Either ALL rows import successfully
- OR NOTHING imports (if any row fails)
- This prevents data inconsistency

---

## Sample Data

**Location:** `docs-qhsse/onboarding/templates/`

**Files:**
- `sites-import-sample.csv` (10 sites)
- `departments-import-sample.csv` (30 departments)
- `positions-import-sample.csv` (60 positions)
- `employees-import-sample.csv` (50 employees)

**Use These For:**
- Learning CSV format
- Testing import process
- Reference for your real data

**Do NOT Use As-Is:**
- Sample data is generic
- Replace with your actual organization data

---

## Quick Reference

### Import Checklist

**Before Import:**
- [ ] Data gathered and verified
- [ ] Template downloaded
- [ ] CSV file prepared (UTF-8, comma-delimited)
- [ ] Previous entities imported (for dependencies)
- [ ] Small test batch ready

**During Import:**
- [ ] File selected
- [ ] Validation run
- [ ] Errors reviewed and fixed
- [ ] Import confirmed

**After Import:**
- [ ] Record count verified
- [ ] Spot checks completed
- [ ] Audit log reviewed
- [ ] Backup CSV saved

---

**Need Help?**
- Contact: qhsse@company.com
- IT Support: itsupport@company.com
- Phone: +62 xxx xxxx xxxx

---

**Document Version:** 1.0  
**Last Updated:** 2026-07-13  
**Next Review:** 2026-10-13
