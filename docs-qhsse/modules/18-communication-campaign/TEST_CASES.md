# Test Cases — Communication & Campaign

> Pest PHP 3 + PHPUnit. Tests run on SQLite in-memory via `.env.testing`.

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

Test file: `tests/Feature/Modules/Communication/CampaignTest.php`

## Factory Definitions

### CampaignFactory

File: `database/factories/Modules/Communication/CampaignFactory.php`

```php
public function definition(): array
{
    $type = fake()->randomElement([
        'safety_alert', 'lesson_learned', 'campaign', 'announcement', 'newsletter',
    ]);

    $targetAudience = fake()->randomElement([
        'all', 'specific_site', 'specific_department', 'specific_role',
    ]);

    return [
        'campaign_number' => 'COM-' . now()->year . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
        'title' => fake()->sentence(4),
        'type' => $type,
        'content' => '<p>' . fake()->paragraph(3) . '</p>',
        'target_audience' => $targetAudience,
        'site_id' => $targetAudience === 'specific_site' ? Site::factory() : null,
        'department_id' => $targetAudience === 'specific_department' ? Department::factory() : null,
        'target_role' => $targetAudience === 'specific_role'
            ? fake()->randomElement(['QHSSE Manager', 'QHSSE Officer', 'Supervisor', 'Employee / Reporter'])
            : null,
        'published_at' => null,
        'expires_at' => fake()->optional(0.3)->dateTimeBetween('+1 month', '+6 months')?->format('Y-m-d H:i:s'),
        'status' => 'draft',
        'author_id' => User::factory(),
        'view_count' => 0,
    ];
}
```

### CampaignAcknowledgmentFactory

File: `database/factories/Modules/Communication/CampaignAcknowledgmentFactory.php`

```php
public function definition(): array
{
    return [
        'campaign_id' => Campaign::factory(),
        'user_id' => User::factory(),
        'acknowledged_at' => fake()->dateTimeBetween('-1 month', 'now'),
        'ip_address' => fake()->optional(0.8)->ipv4(),
    ];
}
```

## Helper Trait

```php
trait CreatesCommunicationTestUser
{
    protected function adminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        return $user;
    }

    protected function officerUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('QHSSE Officer');
        return $user;
    }

    protected function managerUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('QHSSE Manager');
        return $user;
    }

    protected function employeeUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Employee / Reporter');
        return $user;
    }

    protected function auditorUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Auditor');
        return $user;
    }

    protected function createCampaign(array $overrides = []): Campaign
    {
        return Campaign::factory()->create($overrides);
    }

    protected function createPublishedCampaign(array $overrides = []): Campaign
    {
        return Campaign::factory()->create(array_merge([
            'status' => 'published',
            'published_at' => now(),
        ], $overrides));
    }

    protected function createSite(): Site
    {
        return Site::factory()->create();
    }

    protected function createDepartment(): Department
    {
        return Department::factory()->create();
    }
}
```

---

## Category 1: Functional (8 tests)

### 1.1 Authorized user can view campaign list

```php
test('authorized user can view campaign list', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->get(route('communication.campaigns.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Modules/Communication/Campaign/Index')
        ->has('items')
        ->has('filters')
    );
});
```

### 1.2 Authorized user can create campaign

```php
test('authorized user can create campaign', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->post(route('communication.campaigns.store'), [
        'title' => 'Safety Alert: Kebocoran Pipa Gas',
        'type' => 'safety_alert',
        'content' => '<p>Konten safety alert</p>',
        'target_audience' => 'all',
        'expires_at' => now()->addMonth()->format('Y-m-d H:i:s'),
    ]);

    $response->assertRedirect(route('communication.campaigns.show', Campaign::first()));

    $campaign = Campaign::first();
    expect($campaign)->not->toBeNull();
    expect($campaign->title)->toBe('Safety Alert: Kebocoran Pipa Gas');
    expect($campaign->type)->toBe('safety_alert');
    expect($campaign->status)->toBe('draft');
    expect($campaign->target_audience)->toBe('all');
    expect($campaign->author_id)->toBe($admin->id);
});
```

### 1.3 Campaign number is auto-generated on create

```php
test('campaign number is auto-generated on create', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $this->post(route('communication.campaigns.store'), [
        'title' => 'Test Campaign',
        'type' => 'announcement',
        'content' => '<p>Test content</p>',
        'target_audience' => 'all',
    ]);

    $campaign = Campaign::first();
    expect($campaign->campaign_number)->not->toBeNull();
    expect($campaign->campaign_number)->toMatch('/^COM-\d{4}-\d{4}$/');
});
```

### 1.4 Authorized user can publish campaign

```php
test('authorized user can publish campaign', function () {
    $manager = $this->managerUser();
    $this->actingAs($manager);

    $campaign = $this->createCampaign([
        'status' => 'draft',
        'author_id' => $manager->id,
    ]);

    $response = $this->post(route('communication.campaigns.publish', $campaign));

    $response->assertRedirect(route('communication.campaigns.show', $campaign));

    $campaign->refresh();
    expect($campaign->status)->toBe('published');
    expect($campaign->published_at)->not->toBeNull();
});
```

### 1.5 Published campaign sends notification blast

```php
test('published campaign sends notification blast', function () {
    $manager = $this->managerUser();
    $this->actingAs($manager);

    // Create some target audience users
    $employee1 = $this->employeeUser();
    $employee2 = $this->employeeUser();

    $campaign = $this->createCampaign([
        'status' => 'draft',
        'target_audience' => 'all',
        'author_id' => $manager->id,
    ]);

    $this->post(route('communication.campaigns.publish', $campaign));

    expect(
        \DB::table('core_notifications')
            ->where('type', 'communication.campaign_published')
            ->where('module_name', 'communication')
            ->where('reference_id', $campaign->id)
            ->count()
    )->toBeGreaterThanOrEqual(2); // at least 2 recipients
});
```

### 1.6 User can acknowledge published campaign

```php
test('user can acknowledge published campaign', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $campaign = $this->createPublishedCampaign([
        'target_audience' => 'all',
    ]);

    $response = $this->post(route('communication.campaigns.acknowledge', $campaign));

    $response->assertRedirect();

    expect(CampaignAcknowledgment::where('campaign_id', $campaign->id)
        ->where('user_id', $admin->id)
        ->exists()
    )->toBeTrue();
});
```

### 1.7 User cannot acknowledge twice

```php
test('user cannot acknowledge twice', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $campaign = $this->createPublishedCampaign([
        'target_audience' => 'all',
    ]);

    // First acknowledgment
    $this->post(route('communication.campaigns.acknowledge', $campaign));

    // Second acknowledgment should fail
    $response = $this->post(route('communication.campaigns.acknowledge, $campaign));

    $response->assertSessionHasErrors(['acknowledgment']);

    expect(CampaignAcknowledgment::where('campaign_id', $campaign->id)
        ->where('user_id', $admin->id)
        ->count()
    )->toBe(1);
});
```

### 1.8 View count increments on show page

```php
test('view count increments on show page', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $campaign = $this->createPublishedCampaign([
        'target_audience' => 'all',
        'view_count' => 0,
    ]);

    $this->get(route('communication.campaigns.show', $campaign));

    $campaign->refresh();
    expect($campaign->view_count)->toBe(1);

    // Second view by same user should NOT increment (deduplication)
    $this->get(route('communication.campaigns.show', $campaign));

    $campaign->refresh();
    expect($campaign->view_count)->toBe(1);
});
```

---

## Category 2: Permission (4 tests)

### 2.1 User without campaigns.view gets 403 on list

```php
test('user without campaigns.view gets 403 on list', function () {
    $employee = $this->employeeUser();
    $employee->revokePermissionTo('communication.campaigns.view');
    $this->actingAs($employee);

    $response = $this->get(route('communication.campaigns.index'));

    $response->assertForbidden();
});
```

### 2.2 Employee cannot create campaigns

```php
test('employee cannot create campaigns', function () {
    $employee = $this->employeeUser();
    $this->actingAs($employee);

    $response = $this->post(route('communication.campaigns.store'), [
        'title' => 'Test Campaign',
        'type' => 'announcement',
        'content' => '<p>Test</p>',
        'target_audience' => 'all',
    ]);

    $response->assertForbidden();
    expect(Campaign::count())->toBe(0);
});
```

### 2.3 Auditor cannot publish campaigns

```php
test('auditor cannot publish campaigns', function () {
    $auditor = $this->auditorUser();
    $this->actingAs($auditor);

    $campaign = $this->createCampaign(['status' => 'draft']);

    $response = $this->post(route('communication.campaigns.publish', $campaign));

    $response->assertForbidden();
    $campaign->refresh();
    expect($campaign->status)->toBe('draft');
});
```

### 2.4 Employee cannot view acknowledgment list

```php
test('employee cannot view acknowledgment list', function () {
    $employee = $this->employeeUser();
    $this->actingAs($employee);

    $campaign = $this->createPublishedCampaign(['target_audience' => 'all']);

    $response = $this->get(route('communication.campaigns.show', $campaign));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('can.viewAcknowledgments', false)
        ->where('acknowledgments', null)
    );
});
```

---

## Category 3: Integration (5 tests)

### 3.1 Audit trail records campaign creation

```php
test('audit trail records campaign creation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $this->post(route('communication.campaigns.store'), [
        'title' => 'Test Campaign',
        'type' => 'announcement',
        'content' => '<p>Test</p>',
        'target_audience' => 'all',
    ]);

    $campaign = Campaign::first();

    expect(
        AuditLog::where('module_name', 'communication')
            ->where('reference_id', $campaign->id)
            ->where('event', 'created')
            ->exists()
    )->toBeTrue();
});
```

### 3.2 Activity log records publish action

```php
test('activity log records publish action', function () {
    $manager = $this->managerUser();
    $this->actingAs($manager);

    $campaign = $this->createCampaign([
        'status' => 'draft',
        'author_id' => $manager->id,
    ]);

    $this->post(route('communication.campaigns.publish', $campaign));

    expect(
        ActivityLog::where('module_name', 'communication')
            ->where('reference_id', $campaign->id)
            ->where('event', 'campaign.published')
            ->exists()
    )->toBeTrue();
});
```

### 3.3 Notification created when campaign is published

```php
test('notification created when campaign is published', function () {
    $manager = $this->managerUser();
    $this->actingAs($manager);

    $employee = $this->employeeUser();

    $campaign = $this->createCampaign([
        'status' => 'draft',
        'target_audience' => 'all',
        'author_id' => $manager->id,
    ]);

    $this->post(route('communication.campaigns.publish', $campaign));

    expect(
        \DB::table('core_notifications')
            ->where('type', 'communication.campaign_published')
            ->where('module_name', 'communication')
            ->where('reference_id', $campaign->id)
            ->exists()
    )->toBeTrue();
});
```

### 3.4 Target audience filtering works for specific_site

```php
test('target audience filtering works for specific_site', function () {
    $site = $this->createSite();
    $otherSite = $this->createSite();

    $userAtSite = User::factory()->create();
    $userAtSite->assignRole('Employee / Reporter');
    $userAtSite->employee()->update(['site_id' => $site->id]);

    $userAtOtherSite = User::factory()->create();
    $userAtOtherSite->assignRole('Employee / Reporter');
    $userAtOtherSite->employee()->update(['site_id' => $otherSite->id]);

    $campaign = $this->createPublishedCampaign([
        'target_audience' => 'specific_site',
        'site_id' => $site->id,
    ]);

    // User at the target site should be able to see and acknowledge
    $this->actingAs($userAtSite);
    $response = $this->get(route('communication.campaigns.show', $campaign));
    $response->assertStatus(200);

    $ackResponse = $this->post(route('communication.campaigns.acknowledge', $campaign));
    $ackResponse->assertRedirect();

    // User at other site should not be able to acknowledge
    $this->actingAs($userAtOtherSite);
    $ackResponse2 = $this->post(route('communication.campaigns.acknowledge', $campaign));
    $ackResponse2->assertSessionHasErrors(['target_audience']);
});
```

### 3.5 Attachment file can be uploaded to campaign

```php
test('attachment file can be uploaded to campaign', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $file = UploadedFile::fake()->create('safety_report.pdf', 1024, 'application/pdf');

    $response = $this->post(route('communication.campaigns.store'), [
        'title' => 'Test Campaign with Attachment',
        'type' => 'safety_alert',
        'content' => '<p>Test content</p>',
        'target_audience' => 'all',
        'attachments' => [$file],
    ]);

    $response->assertRedirect();

    $campaign = Campaign::first();
    expect(
        ManagedFile::where('module_name', 'communication')
            ->where('reference_id', $campaign->id)
            ->where('collection', 'attachment')
            ->count()
    )->toBe(1);
});
```

---

## Category 4: Negative (3 tests)

### 4.1 Campaign with invalid type fails validation

```php
test('campaign with invalid type fails validation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->post(route('communication.campaigns.store'), [
        'title' => 'Test Campaign',
        'type' => 'invalid_type',
        'content' => '<p>Test</p>',
        'target_audience' => 'all',
    ]);

    $response->assertSessionHasErrors(['type']);
    expect(Campaign::count())->toBe(0);
});
```

### 4.2 Campaign with specific_site but no site_id fails validation

```php
test('campaign with specific_site but no site_id fails validation', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $response = $this->post(route('communication.campaigns.store'), [
        'title' => 'Test Campaign',
        'type' => 'announcement',
        'content' => '<p>Test</p>',
        'target_audience' => 'specific_site',
        'site_id' => null,
    ]);

    $response->assertSessionHasErrors(['site_id']);
    expect(Campaign::count())->toBe(0);
});
```

### 4.3 Cannot edit published campaign

```php
test('cannot edit published campaign', function () {
    $admin = $this->adminUser();
    $this->actingAs($admin);

    $campaign = $this->createPublishedCampaign([
        'title' => 'Original Title',
    ]);

    $response = $this->put(route('communication.campaigns.update', $campaign), [
        'title' => 'Updated Title',
        'type' => $campaign->type,
        'content' => '<p>Updated</p>',
        'target_audience' => 'all',
    ]);

    $response->assertSessionHasErrors(['status']);
    $campaign->refresh();
    expect($campaign->title)->toBe('Original Title');
});
```

---

## Test Summary

| # | Category | Test Name |
|---|---|---|
| 1 | Functional | Authorized user can view campaign list |
| 2 | Functional | Authorized user can create campaign |
| 3 | Functional | Campaign number is auto-generated on create |
| 4 | Functional | Authorized user can publish campaign |
| 5 | Functional | Published campaign sends notification blast |
| 6 | Functional | User can acknowledge published campaign |
| 7 | Functional | User cannot acknowledge twice |
| 8 | Functional | View count increments on show page |
| 9 | Permission | User without campaigns.view gets 403 on list |
| 10 | Permission | Employee cannot create campaigns |
| 11 | Permission | Auditor cannot publish campaigns |
| 12 | Permission | Employee cannot view acknowledgment list |
| 13 | Integration | Audit trail records campaign creation |
| 14 | Integration | Activity log records publish action |
| 15 | Integration | Notification created when campaign is published |
| 16 | Integration | Target audience filtering works for specific_site |
| 17 | Integration | Attachment file can be uploaded to campaign |
| 18 | Negative | Campaign with invalid type fails validation |
| 19 | Negative | Campaign with specific_site but no site_id fails validation |
| 20 | Negative | Cannot edit published campaign |
