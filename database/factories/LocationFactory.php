<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Centro de Estética',
            'address' => fake()->streetAddress(),
            'phone' => '+54 9 11 '.fake()->numerify('####-####'),
            'email' => fake()->safeEmail(),
        ];
    }
}
