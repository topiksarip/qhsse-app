<?php

use App\Core\Query\ListQuery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

test('phase zero list query service contract remains supported', function () {
    User::factory()->create(['name' => 'Needle User']);
    User::factory()->create(['name' => 'Other User']);

    $request = Request::create('/users', 'GET', [
        'search' => 'Needle',
        'sort' => 'name',
        'direction' => 'asc',
        'per_page' => 5,
    ]);

    $items = (new ListQuery($request))->paginate(
        User::query(),
        ['name', 'email'],
        ['name', 'created_at'],
        'name',
    );

    expect($items->total())->toBe(1)
        ->and($items->first()->name)->toBe('Needle User');
});

test('fluent list query contract supports filters sorting and pagination', function () {
    User::factory()->create(['name' => 'Older Active', 'is_active' => true, 'created_at' => now()->subDay()]);
    User::factory()->create(['name' => 'Newest Active', 'is_active' => true, 'created_at' => now()]);
    User::factory()->create(['name' => 'Inactive User', 'is_active' => false]);

    $request = Request::create('/users', 'GET', ['per_page' => 5]);

    $items = ListQuery::for(User::query(), $request)
        ->filter('is_active', true)
        ->defaultSort('-created_at')
        ->paginate(5);

    expect($items->total())->toBe(2)
        ->and($items->first()->name)->toBe('Newest Active');
});

test('fluent list query contract supports search sort and collection retrieval', function () {
    User::factory()->create(['name' => 'Zulu Operator']);
    User::factory()->create(['name' => 'Alpha Operator']);
    User::factory()->create(['name' => 'Unrelated User']);

    $request = Request::create('/users', 'GET');

    $items = ListQuery::for(User::query(), $request)
        ->search(['name'], 'Operator')
        ->sort('name')
        ->get();

    expect($items->pluck('name')->all())->toBe(['Alpha Operator', 'Zulu Operator']);
});
