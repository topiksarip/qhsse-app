<?php

namespace Database\Factories\Modules\Security;

use App\Models\Core\MasterData\Site;
use App\Models\Core\Users\Employee;
use App\Models\Modules\Security\VisitorLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisitorLog>
 */
class VisitorLogFactory extends Factory
{
    protected $model = VisitorLog::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['KTP', 'SIM', 'Passport', 'Lainnya']);
        $status = fake()->randomElement(['checked_in', 'checked_out']);
        $checkedIn = fake()->dateTimeBetween('-7 days', 'now');

        $base = [
            'visitor_name' => fake()->name(),
            'visitor_type' => $type,
            'visitor_id_number' => fake()->numerify('################'),
            'visitor_company' => fake()->optional()->company(),
            'visitor_phone' => fake()->phoneNumber(),
            'host_employee_id' => Employee::factory(),
            'site_id' => Site::factory(),
            'purpose' => fake()->randomElement(['Meeting', 'Delivery', 'Installation', 'Inspection', 'Training']),
            'vehicle_number' => fake()->optional()->regexify('[A-Z]{2} [0-9]{4} [A-Z]{2}'),
            'checked_in_at' => $checkedIn,
            'checked_in_by' => User::factory(),
            'checked_out_at' => null,
            'checked_out_by' => null,
            'status' => $status,
            'notes' => fake()->optional()->sentence(),
        ];

        if ($status === 'checked_out') {
            $base['checked_out_at'] = fake()->dateTimeBetween($checkedIn, 'now');
            $base['checked_out_by'] = User::factory();
        }

        return $base;
    }

    public function checkedIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'checked_in',
            'checked_out_at' => null,
            'checked_out_by' => null,
        ]);
    }

    public function checkedOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'checked_out',
            'checked_out_at' => fake()->dateTimeBetween($attributes['checked_in_at'] ?? '-3 hours', 'now'),
            'checked_out_by' => User::factory(),
        ]);
    }
}
