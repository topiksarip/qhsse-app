<?php
namespace Database\Factories\Core\MasterData;
use App\Models\Core\MasterData\Priority; use Illuminate\Database\Eloquent\Factories\Factory;
/** @extends Factory<Priority> */ class PriorityFactory extends Factory { public function definition(): array { return ['code'=>fake()->unique()->bothify('PRI-##'),'name'=>fake()->word(),'sla_days'=>fake()->numberBetween(1,30),'color'=>'gray','is_active'=>true]; } public function inactive(): static { return $this->state(fn()=>['is_active'=>false]); } }
