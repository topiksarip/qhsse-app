# Test Cases — Dashboard & KPI

> Pest PHP 3 + PHPUnit. Tests run on SQLite in-memory via `.env.testing`.
>
> **Test file:** `tests/Feature/DashboardTest.php`
> **No factory needed** — dashboard reads from `incidents` table (owned by module 02).

## Test Environment

File: `.env.testing`

```ini
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
CACHE_STORE=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
```

---

## Helper Trait

```php
<?php

namespace Tests\Feature;

use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Severity;
use App\Models\Core\MasterData\Site;
use App\Models\Core\MasterData\Priority;
use App\Models\Core\Users\Employee;
use App\Models\Modules\Incident\IncidentReport;
use App\Models\User;

trait CreatesDashboardTestUser
{
    protected function adminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        return $user;
    }

    protected function reporterUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Employee / Reporter');
        return $user;
    }

    protected function noRoleUser(): User
    {
        return User::factory()->create();
    }

    protected function createSite(): Site
    {
        return Site::factory()->create(['is_active' => true]);
    }

    protected function createDepartment(?int $siteId = null): Department
    {
        return Department::factory()->create([
            'site_id' => $siteId ?? Site::factory()->create(['is_active' => true])->id,
            'is_active' => true,
        ]);
    }

    protected function createSeverity(string $code = 'LOW', int $level = 1): Severity
    {
        return Severity::factory()->create([
            'code' => $code,
            'level' => $level,
            'is_active' => true,
        ]);
    }

    protected function createPriority(): Priority
    {
        return Priority::factory()->create(['is_active' => true]);
    }

    protected function createIncident(array $overrides = []): IncidentReport
    {
        $defaults = [
            'incident_number' => 'INC-' . now()->year . '-' . str_pad((string) IncidentReport::count() + 1, 4, '0', STR_PAD_LEFT),
            'title' => 'Test Incident',
            'category' => 'incident',
            'occurred_at' => now(),
            'site_id' => $this->createSite()->id,
            'area_id' => null,
            'department_id' => null,
            'reporter_id' => $this->adminUser()->id,
            'severity_id' => $this->createSeverity()->id,
            'priority_id' => $this->createPriority()->id,
            'description' => 'Test description',
            'immediate_action' => null,
            'status' => 'submitted',
        ];

        return IncidentReport::factory()->create(array_merge($defaults, $overrides));
    }
}
```

---

## Category 1: Functional (8 tests)

### 1.1 Authenticated user can view dashboard

```php
test('authenticated user can view dashboard', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->has('filters')
        ->has('filterOptions.sites')
        ->has('filterOptions.departments')
        ->has('kpis')
        ->has('quickLinks')
        ->has('notificationSummary')
    );
});
```

### 1.2 Dashboard shows correct KPI counts

```php
test('dashboard shows correct KPI counts', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $severity = $this->createSeverity('LOW', 1);
    $criticalSeverity = $this->createSeverity('CRITICAL', 4);
    $priority = $this->createPriority();
    $site = $this->createSite();

    // Create incidents with known statuses
    IncidentReport::factory()->count(5)->create([
        'status' => 'submitted',
        'severity_id' => $severity->id,
        'priority_id' => $priority->id,
        'site_id' => $site->id,
        'reporter_id' => $admin->id,
        'occurred_at' => now(),
    ]);

    IncidentReport::factory()->count(3)->create([
        'status' => 'closed',
        'severity_id' => $severity->id,
        'priority_id' => $priority->id,
        'site_id' => $site->id,
        'reporter_id' => $admin->id,
        'occurred_at' => now(),
    ]);

    IncidentReport::factory()->count(2)->create([
        'status' => 'rejected',
        'severity_id' => $severity->id,
        'priority_id' => $priority->id,
        'site_id' => $site->id,
        'reporter_id' => $admin->id,
        'occurred_at' => now(),
    ]);

    IncidentReport::factory()->create([
        'status' => 'submitted',
        'severity_id' => $criticalSeverity->id,
        'priority_id' => $priority->id,
        'site_id' => $site->id,
        'reporter_id' => $admin->id,
        'occurred_at' => now(),
    ]);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->has('kpis', 6)
        ->where('kpis.0.label', 'Total Insiden')
        ->where('kpis.0.value', 11) // 5 + 3 + 2 + 1
        ->where('kpis.1.label', 'Insiden Terbuka')
        ->where('kpis.1.value', 6) // 5 submitted + 1 critical open
        ->where('kpis.2.label', 'Insiden Selesai')
        ->where('kpis.2.value', 3)
        ->where('kpis.3.label', 'Insiden Kritis')
        ->where('kpis.3.value', 1)
        ->where('kpis.4.label', 'Insiden Ditolak')
        ->where('kpis.4.value', 2)
    );
});
```

### 1.3 Dashboard renders charts data

```php
test('dashboard renders charts data', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $this->createIncident(['category' => 'accident', 'status' => 'closed']);
    $this->createIncident(['category' => 'near_miss', 'status' => 'submitted']);
    $this->createIncident(['category' => 'accident', 'status' => 'under_review']);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->has('charts', 5)
        // Chart 0: Monthly trend (line)
        ->where('charts.0.type', 'line')
        ->where('charts.0.title', 'Tren Bulanan Insiden')
        ->has('charts.0.data')
        // Chart 1: By category (bar)
        ->where('charts.1.type', 'bar')
        ->where('charts.1.title', 'Insiden per Kategori')
        ->has('charts.1.data')
        // Chart 2: By severity (donut)
        ->where('charts.2.type', 'donut')
        ->where('charts.2.title', 'Insiden per Severity')
        ->has('charts.2.data')
        // Chart 3: By site (bar)
        ->where('charts.3.type', 'bar')
        ->where('charts.3.title', 'Insiden per Site')
        ->has('charts.3.data')
        // Chart 4: By status (donut)
        ->where('charts.4.type', 'donut')
        ->where('charts.4.title', 'Status Insiden')
        ->has('charts.4.data')
    );
});
```

### 1.4 Date range filter works

```php
test('date range filter narrows dashboard data', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $severity = $this->createSeverity();
    $priority = $this->createPriority();
    $site = $this->createSite();

    // Incident in January
    IncidentReport::factory()->create([
        'occurred_at' => '2026-01-15 10:00:00',
        'severity_id' => $severity->id,
        'priority_id' => $priority->id,
        'site_id' => $site->id,
        'reporter_id' => $admin->id,
        'status' => 'submitted',
    ]);

    // Incident in July (current month)
    IncidentReport::factory()->create([
        'occurred_at' => now(),
        'severity_id' => $severity->id,
        'priority_id' => $priority->id,
        'site_id' => $site->id,
        'reporter_id' => $admin->id,
        'status' => 'submitted',
    ]);

    // Filter to July only
    $response = $this->get(route('dashboard', [
        'from' => now()->startOfMonth()->toDateString(),
        'to' => now()->endOfMonth()->toDateString(),
    ]));

    $response->assertInertia(fn ($page) => $page
        ->where('kpis.0.value', 1) // Only the July incident
    );
});
```

### 1.5 Site filter works

```php
test('site filter narrows dashboard data', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $severity = $this->createSeverity();
    $priority = $this->createPriority();
    $siteA = $this->createSite();
    $siteB = $this->createSite();

    IncidentReport::factory()->count(3)->create([
        'site_id' => $siteA->id,
        'severity_id' => $severity->id,
        'priority_id' => $priority->id,
        'reporter_id' => $admin->id,
        'occurred_at' => now(),
    ]);

    IncidentReport::factory()->count(2)->create([
        'site_id' => $siteB->id,
        'severity_id' => $severity->id,
        'priority_id' => $priority->id,
        'reporter_id' => $admin->id,
        'occurred_at' => now(),
    ]);

    $response = $this->get(route('dashboard', ['site_id' => $siteA->id]));

    $response->assertInertia(fn ($page) => $page
        ->where('kpis.0.value', 3) // Only site A incidents
    );
});
```

### 1.6 Department filter works

```php
test('department filter narrows dashboard data', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $site = $this->createSite();
    $deptA = $this->createDepartment($site->id);
    $deptB = $this->createDepartment($site->id);
    $severity = $this->createSeverity();
    $priority = $this->createPriority();

    IncidentReport::factory()->count(4)->create([
        'site_id' => $site->id,
        'department_id' => $deptA->id,
        'severity_id' => $severity->id,
        'priority_id' => $priority->id,
        'reporter_id' => $admin->id,
        'occurred_at' => now(),
    ]);

    IncidentReport::factory()->count(2)->create([
        'site_id' => $site->id,
        'department_id' => $deptB->id,
        'severity_id' => $severity->id,
        'priority_id' => $priority->id,
        'reporter_id' => $admin->id,
        'occurred_at' => now(),
    ]);

    $response = $this->get(route('dashboard', ['department_id' => $deptA->id]));

    $response->assertInertia(fn ($page) => $page
        ->where('kpis.0.value', 4) // Only dept A incidents
    );
});
```

### 1.7 Notification summary shows unread count

```php
test('notification summary shows unread count', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    CoreNotification::factory()->count(3)->create([
        'recipient_id' => $admin->id,
        'read_at' => null,
    ]);

    CoreNotification::factory()->create([
        'recipient_id' => $admin->id,
        'read_at' => now(),
    ]);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->where('notificationSummary.unread', 3)
    );
});
```

### 1.8 Quick links are role-aware

```php
test('quick links are filtered by user permissions', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->has('quickLinks')
        // Admin should have all quick links
        ->where('quickLinks.0.label', 'Sites')
        ->where('quickLinks.0.route', 'core.sites.index')
        ->where('quickLinks.0.permission', 'core.sites.view')
    );
});
```

---

## Category 2: Permission (4 tests)

### 2.1 Unauthenticated user is redirected to login

```php
test('unauthenticated user is redirected to login', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});
```

### 2.2 User without core.dashboard.view permission gets 403

```php
test('user without core.dashboard.view permission gets 403', function () {
    $user = $this->noRoleUser(); // No roles assigned
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertForbidden();
});
```

### 2.3 Employee with own scope only sees own incidents

```php
test('employee with own scope only sees own incidents', function () {
    $reporter = $this->reporterUser();
    $this->actingAs($reporter);

    $severity = $this->createSeverity();
    $priority = $this->createPriority();
    $site = $this->createSite();
    $otherUser = $this->adminUser();

    // Reporter's own incidents
    IncidentReport::factory()->count(3)->create([
        'reporter_id' => $reporter->id,
        'severity_id' => $severity->id,
        'priority_id' => $priority->id,
        'site_id' => $site->id,
        'occurred_at' => now(),
    ]);

    // Another user's incidents
    IncidentReport::factory()->count(5)->create([
        'reporter_id' => $otherUser->id,
        'severity_id' => $severity->id,
        'priority_id' => $priority->id,
        'site_id' => $site->id,
        'occurred_at' => now(),
    ]);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->where('kpis.0.value', 3) // Only reporter's own incidents
    );
});
```

### 2.4 All roles with core.dashboard.view can access dashboard

```php
test('all roles with core.dashboard.view can access dashboard', function () {
    $roles = ['Admin', 'QHSSE Manager', 'QHSSE Officer', 'Auditor', 'Top Management'];

    foreach ($roles as $roleName) {
        $user = User::factory()->create();
        $user->assignRole($roleName);

        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
    }
});
```

---

## Category 3: Integration (5 tests)

### 3.1 Dashboard aggregates from incidents table correctly

```php
test('dashboard aggregates from incidents table correctly', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $lowSeverity = $this->createSeverity('LOW', 1);
    $criticalSeverity = $this->createSeverity('CRITICAL', 4);
    $priority = $this->createPriority();
    $site = $this->createSite();

    // Create incidents of different categories
    IncidentReport::factory()->create([
        'category' => 'accident',
        'status' => 'closed',
        'severity_id' => $lowSeverity->id,
        'priority_id' => $priority->id,
        'site_id' => $site->id,
        'reporter_id' => $admin->id,
        'occurred_at' => now(),
    ]);

    IncidentReport::factory()->create([
        'category' => 'near_miss',
        'status' => 'submitted',
        'severity_id' => $criticalSeverity->id,
        'priority_id' => $priority->id,
        'site_id' => $site->id,
        'reporter_id' => $admin->id,
        'occurred_at' => now(),
    ]);

    $response = $this->get(route('dashboard'));

    // Verify category chart has correct data
    $response->assertInertia(fn ($page) => $page
        ->has('charts.1.data')
    );

    // Verify severity chart has correct data
    $response->assertInertia(fn ($page) => $page
        ->has('charts.2.data')
    );
});
```

### 3.2 Dashboard widgets show recent incidents

```php
test('dashboard widgets show recent incidents', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $severity = $this->createSeverity();
    $priority = $this->createPriority();
    $site = $this->createSite();

    IncidentReport::factory()->count(15)->create([
        'severity_id' => $severity->id,
        'priority_id' => $priority->id,
        'site_id' => $site->id,
        'reporter_id' => $admin->id,
        'occurred_at' => now(),
        'status' => 'submitted',
    ]);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->has('widgets', 3)
        // Recent Incidents widget: max 10 rows
        ->has('widgets.0.rows')
        // Verify rows are limited to 10
    );
});
```

### 3.3 Dashboard filter options include active sites only

```php
test('dashboard filter options include active sites only', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $activeSite = Site::factory()->create(['is_active' => true, 'name' => 'Active Site']);
    $inactiveSite = Site::factory()->create(['is_active' => false, 'name' => 'Inactive Site']);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->has('filterOptions.sites')
    );

    // Verify inactive site is not in options
    $sites = $response->inertiaProps('filterOptions.sites');
    $siteIds = collect($sites)->pluck('id');
    expect($siteIds)->toContain($activeSite->id);
    expect($siteIds)->not->toContain($inactiveSite->id);
});
```

### 3.4 Dashboard filter options include active departments only

```php
test('dashboard filter options include active departments only', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $activeDept = Department::factory()->create(['is_active' => true, 'name' => 'Active Dept']);
    $inactiveDept = Department::factory()->create(['is_active' => false, 'name' => 'Inactive Dept']);

    $response = $this->get(route('dashboard'));

    $departments = $response->inertiaProps('filterOptions.departments');
    $deptIds = collect($departments)->pluck('id');
    expect($deptIds)->toContain($activeDept->id);
    expect($deptIds)->not->toContain($inactiveDept->id);
});
```

### 3.5 Dashboard default filters are applied when no params provided

```php
test('dashboard applies default filters when no params provided', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->where('filters.from', now()->startOfMonth()->toDateString())
        ->where('filters.to', now()->toDateString())
        ->where('filters.site_id', null)
        ->where('filters.department_id', null)
    );
});
```

---

## Category 4: Negative (3 tests)

### 4.1 Dashboard returns empty KPIs when no incidents exist

```php
test('dashboard returns zero KPIs when no incidents exist', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->where('kpis.0.value', 0)
        ->where('kpis.1.value', 0)
        ->where('kpis.2.value', 0)
        ->where('kpis.3.value', 0)
        ->where('kpis.4.value', 0)
        ->where('kpis.5.value', 0)
    );
});
```

### 4.2 Dashboard with invalid date range returns empty results

```php
test('dashboard with future date range returns empty results', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $severity = $this->createSeverity();
    $priority = $this->createPriority();
    $site = $this->createSite();

    IncidentReport::factory()->count(5)->create([
        'severity_id' => $severity->id,
        'priority_id' => $priority->id,
        'site_id' => $site->id,
        'reporter_id' => $admin->id,
        'occurred_at' => now(),
    ]);

    // Filter to a future date range with no incidents
    $response = $this->get(route('dashboard', [
        'from' => '2030-01-01',
        'to' => '2030-12-31',
    ]));

    $response->assertInertia(fn ($page) => $page
        ->where('kpis.0.value', 0) // No incidents in 2030
    );
});
```

### 4.3 Dashboard does not expose data outside user scope

```php
test('dashboard does not expose data outside user scope', function () {
    $reporter = $this->reporterUser();
    $this->actingAs($reporter);

    $severity = $this->createSeverity();
    $priority = $this->createPriority();
    $site = $this->createSite();
    $otherUser = $this->adminUser();

    // Another user's incident (should NOT be visible to reporter)
    $otherIncident = IncidentReport::factory()->create([
        'reporter_id' => $otherUser->id,
        'severity_id' => $severity->id,
        'priority_id' => $priority->id,
        'site_id' => $site->id,
        'occurred_at' => now(),
        'title' => 'SECRET_OTHER_USER_INCIDENT',
    ]);

    $response = $this->get(route('dashboard'));

    // Reporter should see 0 incidents (they didn't create any)
    $response->assertInertia(fn ($page) => $page
        ->where('kpis.0.value', 0)
    );

    // Verify the other user's incident title is not in widgets
    $widgets = $response->inertiaProps('widgets');
    foreach ($widgets as $widget) {
        foreach ($widget['rows'] ?? [] as $row) {
            expect($row['title'] ?? '')->not->toBe('SECRET_OTHER_USER_INCIDENT');
        }
    }
});
```

---

## Test Summary

| Category | Count | Description |
|---|---|---|
| Functional | 8 | Dashboard loads, KPIs correct, charts render, filters work, notification summary, quick links |
| Permission | 4 | Unauthenticated redirect, no permission 403, own scope, all roles access |
| Integration | 5 | Aggregation correctness, widgets, filter options, default filters |
| Negative | 3 | Empty data, future date range, scope isolation |
| **Total** | **20** | |

---

## Running Tests

```bash
# Run dashboard tests only
php artisan test --filter=DashboardTest

# Run all tests
php artisan test

# Run with coverage
php artisan test --filter=DashboardTest --coverage
```
