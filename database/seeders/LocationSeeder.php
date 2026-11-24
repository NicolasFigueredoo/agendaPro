<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

/**
 * Datos del local tomados de AgendaPro (Frida Nails).
 */
class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $location = Location::query()->oldest('id')->first() ?? new Location;

        $location->fill([
            'name' => 'Frida Nails',
            'address' => 'Barracas 1208, Ingeniero J. Allan',
            'phone' => '+5491140694183',
            'email' => 'cami-star00@hotmail.com',
        ])->save();

        $hours = [
            1 => ['09:00', '21:00'],
            2 => ['09:00', '21:00'],
            3 => ['09:00', '21:00'],
            4 => ['09:00', '21:00'],
            5 => ['09:00', '21:00'],
            6 => ['09:00', '21:00'],
            7 => ['09:00', '20:00'],
        ];

        foreach ($hours as $day => [$start, $end]) {
            $location->hours()->updateOrCreate(['weekday' => $day], [
                'active' => true,
                'start_time' => $start,
                'end_time' => $end,
            ]);
        }
    }
}
