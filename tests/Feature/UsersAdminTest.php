<?php

use App\Enums\Role;
use App\Livewire\Admin\Users\Index;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('el admin crea un usuario', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('create')
        ->set('name', 'Sofía Recepción')
        ->set('email', 'sofia@centro.test')
        ->set('role', 'recepcion')
        ->set('password', 'secreto123')
        ->set('password_confirmation', 'secreto123')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $user = User::where('email', 'sofia@centro.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(Role::Recepcion)
        ->and(Hash::check('secreto123', $user->password))->toBeTrue();
});

test('el email tiene que ser único', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'repetido@centro.test']);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('create')
        ->set('name', 'Otra')
        ->set('email', 'repetido@centro.test')
        ->set('password', 'secreto123')
        ->set('password_confirmation', 'secreto123')
        ->call('save')
        ->assertHasErrors('email');
});

test('editar sin contraseña no la cambia', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $hash = $user->password;

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('edit', $user->id)
        ->set('name', 'Nombre nuevo')
        ->call('save')
        ->assertHasNoErrors();

    expect($user->fresh()->name)->toBe('Nombre nuevo')
        ->and($user->fresh()->password)->toBe($hash);
});

test('el admin no puede desactivarse ni quitarse el rol', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('edit', $admin->id)
        ->set('active', false)
        ->call('save')
        ->assertHasErrors('role');

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('edit', $admin->id)
        ->set('role', 'recepcion')
        ->call('save')
        ->assertHasErrors('role');

    expect($admin->fresh()->active)->toBeTrue()
        ->and($admin->fresh()->role)->toBe(Role::Admin);
});

test('el admin no puede eliminarse a sí mismo', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('delete', $admin->id);

    expect(User::find($admin->id))->not->toBeNull();
});

test('activar y desactivar usuarios', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    Livewire::actingAs($admin)->test(Index::class)->call('toggleActive', $user->id);

    expect($user->fresh()->active)->toBeFalse();
});
