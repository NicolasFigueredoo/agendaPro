<?php

use App\Enums\BookingStatus;
use App\Livewire\Clients\Index;
use App\Livewire\Clients\Show;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Professional;
use App\Models\User;
use App\Queries\ClientFilters;
use Carbon\Carbon;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function () {
    $this->staff = User::factory()->recepcion()->create();
});

test('recepción y admin entran a clientes, el profesional no', function () {
    $client = Client::factory()->create();

    $this->actingAs($this->staff)->get(route('clients.index'))->assertOk();
    $this->actingAs($this->staff)->get(route('clients.show', $client))->assertOk();
    $this->actingAs($this->staff)->get(route('clients.import'))->assertOk();
    $this->actingAs(User::factory()->admin()->create())->get(route('clients.index'))->assertOk();

    $this->actingAs(User::factory()->profesional()->create())->get(route('clients.index'))->assertForbidden();
});

test('crea un cliente normalizando teléfono y DNI y asigna número', function () {
    Client::factory()->create(['client_number' => 41]);

    Livewire::actingAs($this->staff)
        ->test(Index::class)
        ->call('create')
        ->set('form.first_name', 'María')
        ->set('form.last_name', 'Gómez')
        ->set('form.phone', '011 15 4444-5555')
        ->set('form.dni', '30.123.456')
        ->call('save')
        ->assertHasNoErrors();

    $client = Client::where('last_name', 'Gómez')->first();

    expect($client->phone)->toBe('+5491144445555')
        ->and($client->dni)->toBe('30123456')
        ->and($client->client_number)->toBe(42);
});

test('nombre y apellido son obligatorios y el DNI único', function () {
    Client::factory()->create(['dni' => '30123456']);

    Livewire::actingAs($this->staff)
        ->test(Index::class)
        ->call('create')
        ->set('form.dni', '30123456')
        ->call('save')
        ->assertHasErrors(['form.first_name', 'form.last_name', 'form.dni']);
});

test('busca por nombre completo, DNI, teléfono y email', function () {
    $ana = Client::factory()->create(['first_name' => 'Ana', 'last_name' => 'Pérez', 'dni' => '28111222', 'phone' => '+5491155556666', 'email' => 'ana@mail.com']);
    Client::factory()->create(['first_name' => 'Laura', 'last_name' => 'Díaz']);

    foreach (['Ana Pérez', '28111222', '5555 6666', 'ana@mail'] as $term) {
        $ids = ClientFilters::query(['search' => $term])->pluck('id');
        expect($ids->all())->toBe([$ana->id], "Falló buscando: {$term}");
    }
});

test('filtra por cumpleaños aunque el rango cruce el fin de año', function () {
    $dec = Client::factory()->create(['birth_date' => '1990-12-28']);
    $jan = Client::factory()->create(['birth_date' => '1985-01-03']);
    $jun = Client::factory()->create(['birth_date' => '1992-06-15']);

    $ids = ClientFilters::query(['birthday_from' => '2026-12-20', 'birthday_to' => '2027-01-10'])->pluck('id');

    expect($ids)->toContain($dec->id, $jan->id)->not->toContain($jun->id);

    $ids = ClientFilters::query(['birthday_from' => '2026-06-01', 'birthday_to' => '2026-06-30'])->pluck('id');

    expect($ids->all())->toBe([$jun->id]);
});

test('filtra por profesional, estado y si reservó', function () {
    $pro = Professional::factory()->create();
    $conTurno = Client::factory()->create();
    $sinTurno = Client::factory()->create();
    $soloCancelado = Client::factory()->create();

    Booking::factory()->for($conTurno)->for($pro)->status(BookingStatus::NoAsistio)->create();
    Booking::factory()->for($soloCancelado)->status(BookingStatus::Cancelado)->create();

    expect(ClientFilters::query(['professional' => $pro->id])->pluck('id')->all())->toBe([$conTurno->id])
        ->and(ClientFilters::query(['status' => 'no_asistio'])->pluck('id')->all())->toBe([$conTurno->id])
        ->and(ClientFilters::query(['booked' => 'yes'])->pluck('id')->all())->toBe([$conTurno->id])
        ->and(ClientFilters::query(['booked' => 'no'])->pluck('id')->sort()->values()->all())
        ->toBe(collect([$sinTurno->id, $soloCancelado->id])->sort()->values()->all());
});

test('filtra por fecha de alta', function () {
    Carbon::setTestNow('2026-03-10 12:00');
    $viejo = Client::factory()->create();
    Carbon::setTestNow('2026-11-10 12:00');
    $nuevo = Client::factory()->create();

    expect(ClientFilters::query(['created_from' => '2026-11-01', 'created_to' => '2026-11-30'])->pluck('id')->all())
        ->toBe([$nuevo->id]);

    Carbon::setTestNow();
});

test('ordena por cantidad de turnos', function () {
    $uno = Client::factory()->create();
    $tres = Client::factory()->create();
    Booking::factory()->for($uno)->create();
    Booking::factory()->count(3)->for($tres)->create();

    $ids = ClientFilters::query(['sort' => 'bookings_count', 'direction' => 'desc'])->pluck('id')->all();

    expect($ids[0])->toBe($tres->id);
});

test('la ficha muestra total gastado e inasistencias', function () {
    $client = Client::factory()->create();
    $b1 = Booking::factory()->for($client)->at('2026-10-01 10:00')->status(BookingStatus::Asistio)->create(['price' => 20000]);
    Booking::factory()->for($client)->at('2026-10-15 10:00')->status(BookingStatus::NoAsistio)->create();
    Payment::factory()->for($b1)->create(['amount' => 10000]);
    Payment::factory()->for($b1)->create(['amount' => 10000]);

    Livewire::actingAs($this->staff)
        ->test(Show::class, ['client' => $client])
        ->assertViewHas('stats', fn ($s) => $s['spent'] === 20000 && $s['no_shows'] === 1 && $s['attended'] === 1)
        ->assertSee('$ 20.000');
});

test('editar cliente desde la ficha', function () {
    $client = Client::factory()->create(['first_name' => 'Ana']);

    Livewire::actingAs($this->staff)
        ->test(Show::class, ['client' => $client])
        ->call('edit')
        ->assertSet('form.first_name', 'Ana')
        ->set('form.first_name', 'Anabella')
        ->call('save')
        ->assertHasNoErrors();

    expect($client->fresh()->first_name)->toBe('Anabella');
});

test('eliminar cliente es soft delete', function () {
    $client = Client::factory()->create();
    Booking::factory()->for($client)->create();

    Livewire::actingAs($this->staff)
        ->test(Show::class, ['client' => $client])
        ->call('delete')
        ->assertRedirect(route('clients.index'));

    expect(Client::find($client->id))->toBeNull()
        ->and(Client::withTrashed()->find($client->id))->not->toBeNull()
        ->and(Booking::where('client_id', $client->id)->count())->toBe(1);
});

test('exporta a Excel con los filtros', function () {
    Excel::fake();
    Client::factory()->count(3)->create();

    Livewire::actingAs($this->staff)->test(Index::class)->call('export');

    Excel::assertDownloaded('clientes-'.now()->format('Y-m-d').'.xlsx', fn ($export) => $export->query()->count() === 3);
});
