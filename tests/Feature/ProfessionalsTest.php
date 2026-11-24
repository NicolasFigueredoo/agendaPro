<?php

use App\Livewire\Professionals\Form;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('solo el admin entra a profesionales', function () {
    $pro = Professional::factory()->create();

    $this->actingAs($this->admin)->get(route('professionals.index'))->assertOk();
    $this->actingAs($this->admin)->get(route('professionals.create'))->assertOk();
    $this->actingAs($this->admin)->get(route('professionals.edit', $pro))->assertOk();

    $this->actingAs(User::factory()->recepcion()->create())->get(route('professionals.index'))->assertForbidden();
});

test('crea un profesional con servicios, horario y descansos', function () {
    $services = Service::factory()->count(3)->create();
    $user = User::factory()->profesional()->create();

    Livewire::actingAs($this->admin)
        ->test(Form::class)
        ->set('name', 'Camila')
        ->set('color', '#7c3aed')
        ->set('userId', (string) $user->id)
        ->set('commission', 40)
        ->set('serviceIds', $services->take(2)->pluck('id')->map(fn ($id) => (string) $id)->all())
        ->set('schedule.1.start', '09:00')
        ->set('schedule.1.end', '18:00')
        ->call('addBreak', 1)
        ->set('schedule.7.active', false)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('professionals.index'));

    $pro = Professional::where('name', 'Camila')->firstOrFail();

    expect($pro->user_id)->toBe($user->id)
        ->and($pro->commission_percent)->toBe(40)
        ->and($pro->services()->count())->toBe(2)
        ->and($pro->schedules()->count())->toBe(7)
        ->and($pro->breaks()->where('weekday', 1)->count())->toBe(1)
        ->and($pro->workingIntervals(1))->toBe([[540, 780], [840, 1080]])
        ->and($pro->workingIntervals(7))->toBe([]);
});

test('copiar en todos replica el horario y los descansos', function () {
    Livewire::actingAs($this->admin)
        ->test(Form::class)
        ->set('schedule.1.start', '10:00')
        ->set('schedule.1.end', '19:00')
        ->call('addBreak', 1)
        ->call('copyToAll', 1)
        ->assertSet('schedule.3.start', '10:00')
        ->assertSet('schedule.7.end', '19:00')
        ->assertCount('schedule.5.breaks', 1)
        ->assertSet('schedule.7.active', true);
});

test('rechaza descansos fuera del horario o superpuestos', function () {
    Livewire::actingAs($this->admin)
        ->test(Form::class)
        ->set('name', 'Lucía')
        ->set('schedule.1.start', '09:00')
        ->set('schedule.1.end', '13:00')
        ->set('schedule.1.breaks', [['start' => '14:00', 'end' => '15:00']])
        ->set('schedule.2.breaks', [['start' => '12:00', 'end' => '13:00'], ['start' => '12:30', 'end' => '13:30']])
        ->call('save')
        ->assertHasErrors(['schedule.1.breaks.0.start', 'schedule.2.breaks.1.start']);

    expect(Professional::where('name', 'Lucía')->exists())->toBeFalse();
});

test('rechaza hora de fin anterior al inicio', function () {
    Livewire::actingAs($this->admin)
        ->test(Form::class)
        ->set('name', 'Lucía')
        ->set('schedule.2.start', '18:00')
        ->set('schedule.2.end', '09:00')
        ->call('save')
        ->assertHasErrors(['schedule.2.end']);
});

test('un usuario no se puede vincular a dos profesionales', function () {
    $user = User::factory()->profesional()->create();
    Professional::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($this->admin)
        ->test(Form::class)
        ->set('name', 'Otra')
        ->set('userId', (string) $user->id)
        ->call('save')
        ->assertHasErrors(['userId']);
});

test('solo se vinculan usuarios con rol profesional', function () {
    $recepcion = User::factory()->recepcion()->create();

    Livewire::actingAs($this->admin)
        ->test(Form::class)
        ->set('name', 'Otra')
        ->set('userId', (string) $recepcion->id)
        ->call('save')
        ->assertHasErrors(['userId']);
});

test('editar mantiene los datos cargados', function () {
    $pro = Professional::factory()->withSchedule('10:00', '19:00', [2, 3], [['14:00', '15:00']])->create(['name' => 'Agus']);

    Livewire::actingAs($this->admin)
        ->test(Form::class, ['professional' => $pro])
        ->assertSet('name', 'Agus')
        ->assertSet('schedule.2.active', true)
        ->assertSet('schedule.2.start', '10:00')
        ->assertSet('schedule.1.active', false)
        ->assertCount('schedule.3.breaks', 1);
});

test('minutos disponibles por día', function () {
    $pro = Professional::factory()->withSchedule('09:00', '18:00', [1], [['13:00', '14:00']])->create();

    expect($pro->workingMinutes(1))->toBe(480)
        ->and($pro->workingMinutes(2))->toBe(0);
});
