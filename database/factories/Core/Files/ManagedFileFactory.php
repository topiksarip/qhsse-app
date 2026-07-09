<?php

namespace Database\Factories\Core\Files;

use App\Models\Core\Files\ManagedFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ManagedFile>
 */
class ManagedFileFactory extends Factory
{
    protected $model = ManagedFile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module_name' => 'core.test',
            'reference_id' => $this->faker->numberBetween(1, 1000),
            'collection' => 'default',
            'disk' => 'local',
            'path' => 'managed-files/core.test/'.$this->faker->uuid.'.txt',
            'original_name' => 'evidence.txt',
            'stored_name' => $this->faker->uuid.'.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 128,
            'checksum' => hash('sha256', $this->faker->sentence()),
            'metadata' => ['source' => 'factory'],
            'uploaded_by' => User::factory(),
            'deleted_at' => null,
            'deleted_by' => null,
        ];
    }
}
