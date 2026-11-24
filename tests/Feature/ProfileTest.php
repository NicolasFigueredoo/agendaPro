<?php

use App\Models\User;
use Livewire\Volt\Volt;

test('se muestra la página de perfil', function () {
    $this->actingAs(User::factory()->create())
        ->get('/profile')
        ->assertOk()
        ->assertSeeVolt('profile.update-profile-information-form')
        ->assertSeeVolt('profile.update-password-form');
});

test('se pueden actualizar los datos del perfil', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('profile.update-profile-information-form')
        ->set('name', 'Nombre Prueba')
        ->set('email', 'prueba@centro.test')
        ->call('updateProfileInformation')
        ->assertHasNoErrors()
        ->assertNoRedirect();

    $user->refresh();

    expect($user->name)->toBe('Nombre Prueba')
        ->and($user->email)->toBe('prueba@centro.test')
        ->and($user->email_verified_at)->toBeNull();
});

test('si no cambia el email no se pierde la verificación', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('profile.update-profile-information-form')
        ->set('name', 'Nombre Prueba')
        ->set('email', $user->email)
        ->call('updateProfileInformation')
        ->assertHasNoErrors()
        ->assertNoRedirect();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});
