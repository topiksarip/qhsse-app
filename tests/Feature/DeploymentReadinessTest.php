<?php

use App\Models\Modules\Communication\Campaign;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

it('has unique route names and can build the production route cache', function () {
    $names = collect(Route::getRoutes())
        ->map(fn ($route) => $route->getName())
        ->filter();

    expect($names->duplicates()->values()->all())->toBe([]);

    expect(Artisan::call('route:cache'))->toBe(0);
    Artisan::call('route:clear');
});

it('seeds the baseline without requiring development-only factories', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();

    expect($user->is_active)->toBeTrue()
        ->and($user->hasRole('Super Admin'))->toBeTrue()
        ->and(Campaign::query()->count())->toBe(3);
});
