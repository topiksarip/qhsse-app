<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            QhsseMasterDataSeeder::class,
            NumberingFormatSeeder::class,
            WorkflowSeeder::class,
            NotificationTemplateSeeder::class,
        ]);

        // User::factory(10)->create();

        $user = User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            User::factory()->make(['name' => 'Test User'])->toArray(),
        );

        $user->assignRole('Super Admin');
    }
}
