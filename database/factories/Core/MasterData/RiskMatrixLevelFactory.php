<?php
namespace Database\Factories\Core\MasterData;
use App\Models\Core\MasterData\RiskMatrixLevel; use Illuminate\Database\Eloquent\Factories\Factory;
/** @extends Factory<RiskMatrixLevel> */ class RiskMatrixLevelFactory extends Factory { public function definition(): array { return ['likelihood'=>fake()->numberBetween(1,5),'consequence'=>fake()->numberBetween(1,5),'score'=>fake()->numberBetween(1,25),'level'=>fake()->randomElement(['Low','Medium','High','Extreme']),'color'=>'gray','description'=>fake()->sentence(),'is_active'=>true]; } public function inactive(): static { return $this->state(fn()=>['is_active'=>false]); } }
