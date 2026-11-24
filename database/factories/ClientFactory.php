<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstNameFemale(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->optional()->dateTimeBetween('-60 years', '-16 years')?->format('Y-m-d'),
            'dni' => (string) fake()->unique()->numberBetween(20000000, 45999999),
            'gender' => 'femenino',
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+54911'.fake()->unique()->numerify('########'),
            'address' => null,
            'province' => 'Buenos Aires',
            'notes' => null,
        ];
    }
}
