<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

/**
 * Servicios tomados de AgendaPro (Frida Nails).
 * [nombre, duración en minutos, precio en pesos]
 */
class ServiceSeeder extends Seeder
{
    public const CATALOG = [
        'Esmaltado semipermanente' => [
            ['Belleza de manos con esmaltado semipermanente liso', 60, 24000],
            ['Esmaltado semipermanente con una deco', 60, 25000],
            ['Esmaltado semipermanente full deco', 60, 27000],
            ['Retirado de semi', 20, 10000],
        ],
        'Capping' => [
            ['Capping gel', 90, 27000],
            ['Capping de polygel', 90, 30000],
            ['Capping de acrílico', 90, 31500],
            ['Capping full acrílico', 90, 33500],
            ['Retirado de capping', 30, 11000],
        ],
        'Esculpidas' => [
            ['Esculpidas de polygel', 90, 32000],
            ['Esculpidas cover/babyboomer', 90, 37000],
            ['Soft gel', 120, 17000],
            ['Esculpidas en acrílico con semi', 90, 34000],
            ['Esculpidas full acrílico', 90, 37000],
            ['Retirado de esculpidas', 40, 13000],
        ],
        'Cejas y pestañas' => [
            ['Laminado de cejas', 60, 15000],
            ['Laminado de cejas + lifting de pestañas', 75, 28000],
            ['Lifting de pestañas', 60, 15000],
            ['Lifting de pestañas + perfilado', 60, 20000],
            ['Nutrición de cejas y/o pestañas', 20, 5000],
            ['Perfilado de cejas', 25, 6000],
            ['Perfilado de cejas con tinte', 35, 9000],
        ],
        'Pies' => [
            ['Belleza de pies sin esmalte', 60, 20000],
            ['Pedicuría', 60, 22000],
            ['Pedicuría con belleza de pies y esmaltado', 90, 25000],
            ['Belleza de pies con esmaltado semipermanente', 80, 22000],
        ],
        'Otros' => [],
    ];

    public function run(): void
    {
        $categoryOrder = 0;

        foreach (self::CATALOG as $categoryName => $services) {
            $category = ServiceCategory::updateOrCreate(
                ['name' => $categoryName],
                ['sort_order' => ++$categoryOrder, 'active' => true]
            );

            foreach ($services as $order => [$name, $minutes, $price]) {
                Service::updateOrCreate(
                    ['service_category_id' => $category->id, 'name' => $name],
                    ['duration_minutes' => $minutes, 'price' => $price, 'sort_order' => $order + 1, 'active' => true]
                );
            }
        }
    }
}
