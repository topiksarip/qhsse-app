<?php
namespace Database\Factories\Core\MasterData;
use App\Models\Core\MasterData\Status; use Illuminate\Database\Eloquent\Factories\Factory;
/** @extends Factory<Status> */ class StatusFactory extends Factory { public function definition(): array { return ['module'=>'incident','code'=>fake()->unique()->bothify('STS-##'),'name'=>fake()->word(),'sequence'=>fake()->numberBetween(1,10),'is_terminal'=>false,'is_active'=>true]; } public function inactive(): static { return $this->state(fn()=>['is_active'=>false]); } }
