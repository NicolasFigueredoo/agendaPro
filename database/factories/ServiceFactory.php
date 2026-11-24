<?php

namespace Database\Factories;

use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service_category_id' => ServiceCategory::factory(),
            'name' => ucfirst(fake()->unique()->words(3, true)),
            'description' => null,
            'price' => fake()->randomElement([8000, 12000, 15000, 20000, 30000]),
            'duration_minutes' => fake()->randomElement([30, 45, 60, 90, 120]),
            'active' => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }
}
