<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Professional;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

/**
 * Profesionales, servicios y horarios tomados de AgendaPro (Frida Nails).
 * No se crean usuarios: si alguna tiene que ver su agenda, se crea desde Configuración → Usuarios
 * y se vincula desde Profesionales.
 */
class ProfessionalSeeder extends Seeder
{
    public function run(): void
    {
        $location = Location::current();

        $professionals = [
            [
                'name' => 'Cami',
                'color' => '#e11d48',
                'accepts_online' => true,
                'categories' => ['Esmaltado semipermanente', 'Capping', 'Esculpidas'],
                'except' => ['Soft gel'],
                'services' => [],
                // weekday => [inicio, fin, [[descanso_inicio, descanso_fin], ...]]
                'schedule' => [
                    1 => ['09:00', '18:00', []],
                    2 => ['09:00', '18:00', []],
                    3 => ['09:00', '18:00', []],
                    4 => ['09:00', '18:00', []],
                    6 => ['09:00', '18:00', []],
                ],
            ],
            [
                'name' => 'Cintia',
                'color' => '#7c3aed',
                'accepts_online' => false,
                'categories' => ['Pies'],
                'except' => [],
                'services' => [
                    'Belleza de manos con esmaltado semipermanente liso',
                    'Esmaltado semipermanente con una deco',
                    'Retirado de semi',
                    'Capping gel',
                    'Retirado de capping',
                    'Soft gel',
                    'Retirado de esculpidas',
                ],
                'schedule' => [
                    1 => ['09:00', '18:00', []],
                    2 => ['09:00', '18:00', []],
                    3 => ['09:00', '18:00', []],
                    4 => ['09:00', '18:00', []],
                    5 => ['09:00', '18:00', []],
                    6 => ['09:00', '18:00', []],
                ],
            ],
            [
                'name' => 'Romi',
                'color' => '#0891b2',
                'accepts_online' => false,
                'categories' => ['Cejas y pestañas'],
                'except' => [],
                'services' => [],
                'schedule' => [
                    1 => ['13:30', '17:00', []],
                    3 => ['13:30', '17:00', []],
                    5 => ['13:30', '17:00', []],
                ],
            ],
        ];

        foreach ($professionals as $order => $data) {
            $professional = Professional::updateOrCreate(
                ['name' => $data['name'], 'location_id' => $location->id],
                [
                    'color' => $data['color'],
                    'active' => true,
                    'accepts_online' => $data['accepts_online'],
                    'sort_order' => $order + 1,
                ]
            );

            $byCategory = Service::whereIn(
                'service_category_id',
                ServiceCategory::whereIn('name', $data['categories'])->pluck('id')
            )->whereNotIn('name', $data['except'])->pluck('id');

            $byName = Service::whereIn('name', $data['services'])->pluck('id');

            $professional->services()->sync($byCategory->merge($byName)->unique()->values()->all());

            $professional->schedules()->delete();
            $professional->breaks()->delete();

            foreach (range(1, 7) as $day) {
                $row = $data['schedule'][$day] ?? null;

                $professional->schedules()->create([
                    'weekday' => $day,
                    'active' => $row !== null,
                    'start_time' => $row[0] ?? '09:00',
                    'end_time' => $row[1] ?? '18:00',
                ]);

                foreach ($row[2] ?? [] as [$start, $end]) {
                    $professional->breaks()->create([
                        'weekday' => $day,
                        'start_time' => $start,
                        'end_time' => $end,
                    ]);
                }
            }
        }
    }
}
