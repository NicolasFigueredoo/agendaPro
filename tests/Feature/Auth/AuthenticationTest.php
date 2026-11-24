<?php

use App\Models\User;
use Livewire\Volt\Volt;

test('se muestra la pantalla de login', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSeeVolt('pages.auth.login');
});

test('un usuario puede loguearse', function () {
    $user = User::factory()->create();

    Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('home', absolute: false));

    $this->assertAuthenticated();
});

test('no se loguea con contraseña incorrecta', function () {
    $user = User::factory()->create();

    Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'incorrecta')
        ->call('login')
        ->assertHasErrors('form.email')
        ->assertNoRedirect();

    $this->assertGuest();
});

test('un usuario desactivado no puede loguearse', function () {
    $user = User::factory()->inactive()->create();

    Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password')
        ->call('login')
        ->assertHasErrors('form.email');

    $this->assertGuest();
});

test('un usuario desactivado con sesión abierta es deslogueado', function () {
    $user = User::factory()->inactive()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('se muestra el menú de navegación', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertSeeVolt('layout.navigation');
});

test('un usuario puede cerrar sesión', function () {
    $this->actingAs(User::factory()->create());

    Volt::test('layout.navigation')
        ->call('logout')
        ->assertHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
});

test('no existe el registro público', function () {
    $this->get('/register')->assertNotFound();
});
