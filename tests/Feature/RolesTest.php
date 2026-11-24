<?php

use App\Enums\Role;
use App\Models\User;

test('el admin entra a usuarios', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.users'))
        ->assertOk();
});

test('recepción y profesional no entran a usuarios', function (Role $role) {
    $this->actingAs(User::factory()->create(['role' => $role]))
        ->get(route('admin.users'))
        ->assertForbidden();
})->with([Role::Recepcion, Role::Profesional]);

test('admin y recepción ven el dashboard', function (Role $role) {
    $this->actingAs(User::factory()->create(['role' => $role]))
        ->get(route('dashboard'))
        ->assertOk();
})->with([Role::Admin, Role::Recepcion]);

test('el profesional no ve el dashboard', function () {
    $this->actingAs(User::factory()->profesional()->create())
        ->get(route('dashboard'))
        ->assertForbidden();
});

test('inicio redirige según el rol', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('home'))
        ->assertRedirect(route('dashboard'));

    $this->actingAs(User::factory()->profesional()->create())
        ->get(route('home'))
        ->assertRedirect(); // agenda (o perfil si todavía no existe el módulo)
});

test('los gates responden según el rol', function () {
    $admin = User::factory()->admin()->create();
    $recepcion = User::factory()->recepcion()->create();
    $profesional = User::factory()->profesional()->create();

    expect($admin->can('manage-settings'))->toBeTrue()
        ->and($recepcion->can('manage-settings'))->toBeFalse()
        ->and($recepcion->can('manage-agenda'))->toBeTrue()
        ->and($recepcion->can('manage-payments'))->toBeTrue()
        ->and($profesional->can('manage-agenda'))->toBeFalse()
        ->and($profesional->can('manage-clients'))->toBeFalse();
});

test('invitados van al login', function () {
    $this->get('/dashboard')->assertRedirect(route('login'));
});
