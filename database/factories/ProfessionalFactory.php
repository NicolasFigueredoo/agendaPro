<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Professional;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Professional>
 */
class ProfessionalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location_id' => fn () => Location::current()->id,
            'user_id' => null,
            'name' => fake()->firstNameFemale(),
            'color' => fake()->randomElement(Professional::COLORS),
            'active' => true,
            'accepts_online' => true,
            'commission_percent' => null,
            'sort_order' => 0,
        ];
    }

    /**
     * Horario de lunes a viernes (o los días indicados), con descanso opcional.
     *
     * @param  array<int>  $days
     * @param  array<int, array{0:string, 1:string}>  $breaks
     */
    public function withSchedule(string $start = '09:00', string $end = '18:00', array $days = [1, 2, 3, 4, 5], array $breaks = []): static
    {
        return $this->afterCreating(function (Professional $professional) use ($start, $end, $days, $breaks) {
            foreach (range(1, 7) as $day) {
                $professional->schedules()->create([
                    'weekday' => $day,
                    'active' => in_array($day, $days, true),
                    'start_time' => $start,
                    'end_time' => $end,
                ]);

                if (in_array($day, $days, true)) {
                    foreach ($breaks as [$bs, $be]) {
                        $professional->breaks()->create(['weekday' => $day, 'start_time' => $bs, 'end_time' => $be]);
                    }
                }
            }
        });
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }
}
