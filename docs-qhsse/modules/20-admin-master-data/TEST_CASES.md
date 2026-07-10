# Test Cases — Admin & Master Data Hardening

## Functional

1. Admin can view admin dashboard
2. Non-admin user gets 403 on dashboard
3. Admin can view role manager
4. Admin can assign permissions to role
5. Admin can revoke permissions from role
6. Bulk import employees with valid CSV succeeds
7. Bulk import employees with invalid CSV shows errors
8. Bulk import sites succeeds

## Permission

1. Non-admin cannot access import page
2. Non-admin cannot access settings
3. Non-admin cannot access role manager
4. QHSSE Manager can view but not edit settings

## Integration

1. Role permission change reflects in user access immediately
2. Audit log records role permission change
3. Imported employees appear in employee list
4. Dashboard stats are correct

## Negative

1. Invalid CSV format rejected
2. Duplicate email in import rejected
3. Non-existent company_code in import fails

```php
test('admin can view admin dashboard', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);
    $response = $this->get(route('admin.dashboard'));
    $response->assertStatus(200);
});

test('non-admin gets 403 on dashboard', function () {
    $reporter = User::factory()->create();
    $reporter->assignRole('Employee / Reporter');
    $this->actingAs($reporter);
    $response = $this->get(route('admin.dashboard'));
    $response->assertForbidden();
});

test('admin can assign permissions to role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);
    $role = Role::findByName('Employee / Reporter');
    $response = $this->put(route('admin.roles.update', $role), [
        'permissions' => ['core.sites.view', 'incident.reports.view'],
    ]);
    $response->assertRedirect();
    expect($role->hasPermissionTo('incident.reports.view'))->toBeTrue();
});
```
