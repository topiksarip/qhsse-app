<?php
namespace Database\Factories\Core\MasterData;
use App\Models\Core\MasterData\Severity; use Illuminate\Database\Eloquent\Factories\Factory;
/** @extends Factory<Severity> */ class SeverityFactory extends Factory { public function definition(): array { return ['code'=>fake()->unique()->bothify('SEV-##'),'name'=>fake()->word(),'level'=>fake()->numberBetween(1,4),'color'=>'gray','description'=>fake()->sentence(),'is_active'=>true]; } public function inactive(): static { return $this->state(fn()=>['is_active'=>false]); } }
