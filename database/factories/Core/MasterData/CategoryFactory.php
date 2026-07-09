<?php
namespace Database\Factories\Core\MasterData;
use App\Models\Core\MasterData\Category; use Illuminate\Database\Eloquent\Factories\Factory;
/** @extends Factory<Category> */ class CategoryFactory extends Factory { public function definition(): array { return ['parent_id'=>null,'module'=>'incident','code'=>fake()->unique()->bothify('CAT-##'),'name'=>fake()->word(),'is_active'=>true]; } public function inactive(): static { return $this->state(fn()=>['is_active'=>false]); } }
