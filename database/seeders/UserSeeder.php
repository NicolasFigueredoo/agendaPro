<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            // `?:` porque una variable vacía en .env (ADMIN_PASSWORD=) devuelve "" y no el default.
            ['email' => env('ADMIN_EMAIL') ?: 'admin@centro.test'],
            [
                'name' => 'Administrador',
                'password' => env('ADMIN_PASSWORD') ?: 'password',
                'role' => Role::Admin,
                'active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'recepcion@centro.test'],
            [
                'name' => 'Recepción',
                'password' => 'password',
                'role' => Role::Recepcion,
                'active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
