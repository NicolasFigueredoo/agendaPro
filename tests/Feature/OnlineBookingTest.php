<?php

use App\Enums\BookingStatus;
use App\Exceptions\SlotUnavailableException;
use App\Livewire\Booking\PublicBooking;
use App\Models\Booking;
use App\Models\Client;
use App\Models\MpPaymentLink;
use App\Models\Professional;
use App\Models\Service;
use App\Services\MercadoPagoService;
use App\Services\OnlineBookingService;
use App\Support\Deposit;
use App\Support\Settings;
use Carbon\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    Carbon::setTestNow('2026-11-30 08:00'); // lunes

    $mp = Mockery::mock(MercadoPagoService::class)->makePartial();
    $mp->shouldReceive('isConfigured')->andReturn(true);
    $mp->shouldReceive('createPreference')->andReturnUsing(fn ($link) => [
        'id' => 'PREF-'.$link->id,
        'init_point' => 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=PREF-'.$link->id,
        'raw' => [],
    ]);
    $this->instance(MercadoPagoService::class, $mp);

    $this->service = Service::factory()->create(['price' => 24000, 'duration_minutes' => 60]);
    $this->pro = Professional::factory()->withSchedule('09:00', '18:00', [1, 2, 3, 4, 5, 6])->create(['name' => 'Cami']);
    $this->pro->services()->attach($this->service);

    $this->online = app(OnlineBookingService::class);
    $this->clientData = ['first_name' => 'Ana', 'last_name' => 'Pérez', 'phone' => '11 4444-5555', 'email' => 'ana@mail.com'];
});

afterEach(fn () => Carbon::setTestNow());

test('calcula la seña según la configuración', function () {
    expect(Deposit::amountFor(24000))->toBe(12000)
        ->and(Deposit::amountFor(31500))->toBe(15800); // 15750 → redondeo a $100

    Settings::setMany(['mp.deposit_type' => 'fixed', 'mp.deposit_value' => 5000]);
    expect(Deposit::amountFor(24000))->toBe(5000)
        ->and(Deposit::amountFor(3000))->toBe(3000); // nunca más que el precio
});

test('reserva retiene el horario y genera el link de la seña', function () {
    $result = $this->online->reserve($this->service, null, Carbon::parse('2026-11-30 11:00'), $this->clientData);

    $booking = $result['booking'];
    $link = $result['link'];

    expect($booking->source)->toBe('online')
        ->and($booking->status)->toBe(BookingStatus::Reservado)
        ->and($booking->professional_id)->toBe($this->pro->id)
        ->and($booking->hold_expires_at->eq(now()->addMinutes(30)))->toBeTrue()
        ->and($link->amount)->toBe(12000)
        ->and($link->preference_id)->toBe('PREF-'.$link->id)
        ->and($link->init_point)->toContain('mercadopago');

    $client = Client::first();
    expect($client->phone)->toBe('+5491144445555')
        ->and($client->email)->toBe('ana@mail.com');
});

test('el horario retenido ya no se ofrece ni se puede tomar', function () {
    $this->online->reserve($this->service, null, Carbon::parse('2026-11-30 11:00'), $this->clientData);

    expect($this->online->slotsFor($this->service, Carbon::parse('2026-11-30')))->not->toHaveKey('11:00');

    $this->online->reserve($this->service, null, Carbon::parse('2026-11-30 11:00'), [...$this->clientData, 'phone' => '11 9999-0000', 'email' => 'otra@mail.com']);
})->throws(SlotUnavailableException::class);

test('respeta la anticipación mínima', function () {
    // Son las 08:00 y la anticipación mínima es 2 h
    expect($this->online->slotsFor($this->service, Carbon::parse('2026-11-30')))
        ->not->toHaveKey('09:00')
        ->toHaveKey('10:00');

    $this->online->reserve($this->service, null, Carbon::parse('2026-11-30 09:00'), $this->clientData);
})->throws(SlotUnavailableException::class);

test('solo muestra profesionales que aceptan reservas online', function () {
    $offline = Professional::factory()->withSchedule()->create(['accepts_online' => false]);
    $offline->services()->attach($this->service);

    expect($this->online->professionalsFor($this->service)->pluck('id')->all())->toBe([$this->pro->id]);
});

test('reconoce a un cliente existente por teléfono', function () {
    $existing = Client::factory()->create(['phone' => '+5491144445555', 'email' => null]);

    $this->online->reserve($this->service, null, Carbon::parse('2026-11-30 11:00'), $this->clientData);

    expect(Client::count())->toBe(1)
        ->and($existing->fresh()->email)->toBe('ana@mail.com');
});

test('el comando libera las reservas que no pagaron', function () {
    $result = $this->online->reserve($this->service, null, Carbon::parse('2026-11-30 11:00'), $this->clientData);

    Carbon::setTestNow(now()->addMinutes(31));
    $this->artisan('bookings:release-expired')->assertSuccessful();

    expect($result['booking']->fresh()->status)->toBe(BookingStatus::Cancelado)
        ->and($result['link']->fresh()->status)->toBe(MpPaymentLink::STATUS_EXPIRED)
        ->and($this->online->slotsFor($this->service, Carbon::parse('2026-11-30')))->toHaveKey('11:00');
});

test('flujo completo en la página pública redirige a Mercado Pago', function () {
    $component = Livewire::test(PublicBooking::class)
        ->assertSee($this->service->name)
        ->call('selectService', $this->service->id)
        ->assertSet('step', 3) // una sola profesional: salta el paso 2
        ->call('selectDate', '2026-11-30')
        ->assertSee('11:00')
        ->call('selectTime', '11:00')
        ->assertSet('step', 4)
        ->set('first_name', 'Ana')
        ->set('last_name', 'Pérez')
        ->set('phone', '11 4444-5555')
        ->set('email', 'ana@mail.com')
        ->call('toSummary')
        ->assertSet('step', 5)
        ->assertSee('$ 12.000')
        ->call('confirm')
        ->assertHasErrors('acceptTerms')
        ->set('acceptTerms', true)
        ->call('confirm');

    $link = MpPaymentLink::firstOrFail();

    $component->assertRedirect($link->init_point);

    expect(Booking::count())->toBe(1)
        ->and($link->amount)->toBe(12000);
});

test('sin credenciales de Mercado Pago no se puede reservar online', function () {
    $mp = Mockery::mock(MercadoPagoService::class)->makePartial();
    $mp->shouldReceive('isConfigured')->andReturn(false);
    $this->instance(MercadoPagoService::class, $mp);

    Livewire::test(PublicBooking::class)->assertSee('no están disponibles');
});
