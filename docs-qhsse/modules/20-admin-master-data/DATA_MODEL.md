# Data Model — Admin & Master Data Hardening

## Overview

Module 20 does NOT create new tables. It provides admin UI for existing Phase 0 tables.

## Existing Tables Managed by This Module

### Organization Master
- `sites(id, code, name, address, is_active, timestamps)`
- `areas(id, site_id FK, code, name, is_active, timestamps)`
- `departments(id, site_id FK nullable, code, name, is_active, timestamps)`
- `positions(id, department_id FK nullable, code, name, is_active, timestamps)`

### People & Companies
- `companies(id, code, name, type, is_active, timestamps)`
- `employees(id, company_id FK, name, email, phone, site_id FK, department_id FK, position_id FK, is_active, timestamps)`
- `users(id, name, email, password, is_active, company_id FK, employee_id FK, timestamps)`

### QHSSE Master Data
- `severities(id, code, name, level, color, is_active, timestamps)`
- `priorities(id, code, name, level, color, is_active, timestamps)`
- `statuses(id, code, name, is_active, timestamps)`
- `categories(id, code, name, is_active, timestamps)`
- `risk_matrix_levels(id, code, name, severity_level, probability_level, risk_level, is_active, timestamps)`

### System Tables
- `numbering_formats(id, module_name, prefix, padding, separator, reset_frequency, include_year, include_site_code, sample, is_active, timestamps)`
- `numbering_counters(id, module_name, site_code, year, current_number, timestamps)`
- `workflow_definitions(id, module_name, code, name, initial_status, is_active, timestamps)`
- `workflow_transitions(id, workflow_definition_id FK, from_status, to_status, action_key, action_label, requires_reason, required_permission, is_active, timestamps)`
- `notification_templates(id, type, title_template, message_template, channels, is_active, timestamps)`
- `audit_logs(id, event, auditable_type, auditable_id, module_name, reference_id, actor_id, actor_name, ip_address, user_agent, old_values, new_values, metadata, timestamps)`

### Spatie Permission
- `roles(id, name, guard_name, timestamps)`
- `permissions(id, name, guard_name, timestamps)`
- `model_has_roles(role_id, model_id, model_type)`
- `model_has_permissions(permission_id, model_id, model_type)`
- `role_has_permissions(permission_id, role_id)`

## New Table (only if settings feature is implemented)

### `system_settings`

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint PK | NO | | |
| key | string(255) | NO | | Setting key (e.g., app.locale, app.timezone) |
| value | text | YES | NULL | Setting value |
| type | string(50) | NO | 'string' | string/boolean/integer/json |
| description | string(500) | YES | NULL | |
| is_public | boolean | NO | false | Visible to non-admin? |
| timestamps | | | | |

Index: `key` (unique)

## ERD (Existing Tables)

```
sites ──┬── areas
         ├── departments ── positions
         └── employees ──── users

companies ── employees ── users

severities    priorities    statuses    categories
risk_matrix_levels

numbering_formats ── numbering_counters
workflow_definitions ── workflow_transitions
notification_templates
audit_logs

roles ←── model_has_roles ──→ users
permissions ←── role_has_permissions ──→ roles
permissions ←── model_has_permissions ──→ users
```

## Migration (if system_settings is implemented)

```text
2026_07_15_000001_create_system_settings_table.php
```
