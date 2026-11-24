<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            LocationSeeder::class,
            ServiceSeeder::class,
            ProfessionalSeeder::class,
        ]);

        // Clientes de prueba solo en local (en producción se importan los reales)
        if (app()->isLocal() && Client::count() === 0) {
            Client::factory()->count(40)->create();
        }
    }
}
