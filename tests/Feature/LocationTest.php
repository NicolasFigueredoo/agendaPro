<?php

use App\Livewire\Location\Edit;
use App\Models\Location;
use App\Models\User;
use App\Support\Time;
use App\Support\Weekdays;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('solo el admin edita el local', function () {
    $this->actingAs($this->admin)->get(route('location.edit'))->assertOk();
    $this->actingAs(User::factory()->recepcion()->create())->get(route('location.edit'))->assertForbidden();
});

test('guarda datos, horario y logo', function () {
    Storage::fake('public');

    Livewire::actingAs($this->admin)
        ->test(Edit::class)
        ->set('name', 'Nails & Lashes')
        ->set('address', 'Calle 123')
        ->set('hours.1.start', '10:00')
        ->set('hours.1.end', '20:00')
        ->set('hours.7.active', false)
        ->set('logo', UploadedFile::fake()->image('logo.png', 200, 200))
        ->call('save')
        ->assertHasNoErrors();

    $location = Location::query()->oldest('id')->first()->load('hours');

    expect($location->name)->toBe('Nails & Lashes')
        ->and($location->logo_path)->not->toBeNull()
        ->and($location->hours)->toHaveCount(7)
        ->and(Time::hm($location->hours->firstWhere('weekday', 1)->start_time))->toBe('10:00')
        ->and($location->hours->firstWhere('weekday', 7)->active)->toBeFalse();

    Storage::disk('public')->assertExists($location->logo_path);
});

test('copiar horario del local en todos los días', function () {
    Livewire::actingAs($this->admin)
        ->test(Edit::class)
        ->set('hours.1.start', '08:00')
        ->set('hours.1.end', '20:00')
        ->call('copyToAll', 1)
        ->assertSet('hours.6.start', '08:00')
        ->assertSet('hours.7.active', true);
});

test('valida horario inválido', function () {
    Livewire::actingAs($this->admin)
        ->test(Edit::class)
        ->set('hours.2.start', '20:00')
        ->set('hours.2.end', '10:00')
        ->call('save')
        ->assertHasErrors(['hours.2.end']);
});

test('helpers de horas y días', function () {
    expect(Time::toMinutes('09:30:00'))->toBe(570)
        ->and(Time::fromMinutes(570))->toBe('09:30')
        ->and(Time::durationLabel(90))->toBe('1 h 30 min')
        ->and(Time::durationLabel(45))->toBe('45 min')
        ->and(Weekdays::summary([1, 2, 3, 4, 5, 6]))->toBe('Lun a Sáb')
        ->and(Weekdays::summary([1, 3, 5]))->toBe('Lun, Mié, Vie');
});
