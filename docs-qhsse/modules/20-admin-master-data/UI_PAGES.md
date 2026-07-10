# UI Pages — Admin & Master Data Hardening

## 1. Admin Dashboard

```
┌──────────────────────────────────────────────────────────┐
│  Admin Dashboard                                          │
├──────────────────────────────────────────────────────────┤
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐             │
│  │ Users  │ │Employees│ │ Sites  │ │Companies│            │
│  │   42   │ │   156  │ │   8    │ │   12   │             │
│  └────────┘ └────────┘ └────────┘ └────────┘             │
│                                                          │
│  Recent Activity                          [Import CSV]    │
│  ┌──────────────────────────────────────────────────┐    │
│  │ 11/07 15:30 — Admin created site "Plant B"        │    │
│  │ 11/07 14:20 — Admin updated severity "Critical"  │    │
│  │ 11/07 12:00 — Admin deactivated user "john@..."   │    │
│  └──────────────────────────────────────────────────┘    │
│                                                          │
│  Quick Links                                             │
│  [Sites] [Departments] [Employees] [Users]               │
│  [Severities] [Priorities] [Categories] [Risk Matrix]    │
│  [Numbering] [Workflow] [Audit Logs] [Roles]             │
└──────────────────────────────────────────────────────────┘
```

## 2. Bulk Import Page

```
┌──────────────────────────────────────────────────────────┐
│  Bulk Import                                             │
├──────────────────────────────────────────────────────────┤
│  [▼ Employees]  [▼ Sites]  [▼ Departments]              │
│                                                          │
│  ┌────────────────────────────────────────┐              │
│  │      Drop CSV file here                 │              │
│  │      or click to browse                 │              │
│  └────────────────────────────────────────┘              │
│                                                          │
│  Required columns: name, email, phone, company_code       │
│                                                          │
│  Preview:                                                │
│  ┌──────┬───────────┬───────────┬─────────────┐          │
│  │ Row  │ Name       │ Email     │ Status      │          │
│  │ 1    │ Budi       │ budi@...  │ ✓ Valid     │          │
│  │ 2    │ Andi       │ andi@...  │ ✓ Valid     │          │
│  │ 3    │ (empty)    │ x@        │ ✗ Name req  │          │
│  └──────┴───────────┴───────────┴─────────────┘          │
│  2 valid, 1 error. Fix errors or import valid only.      │
│                                                          │
│  [Import 2 Valid]  [Cancel]                              │
└──────────────────────────────────────────────────────────┘
```

## 3. Role Manager

```
┌──────────────────────────────────────────────────────────┐
│  Role Manager                                            │
├──────────────────────────────────────────────────────────┤
│  [▼ QHSSE Officer]                                      │
│                                                          │
│  Permissions:                                            │
│  ☑ core.sites.view        ☐ core.sites.create            │
│  ☑ core.areas.view        ☐ core.areas.create            │
│  ☑ incident.reports.view  ☑ incident.reports.create      │
│  ☑ incident.reports.review ☑ incident.reports.close      │
│  ☐ capa.actions.verify   ☑ capa.actions.view             │
│  ...                                                     │
│                                                          │
│  [Save Changes]                                          │
└──────────────────────────────────────────────────────────┘
```

## 4. Navigation

Admin group already exists in `AuthenticatedLayout.tsx` with items for Companies, Employees, Users, Numbering, Workflow, Audit Logs, Comments. Add:
- Admin Dashboard link (first item in Admin group)
- Role Manager link
