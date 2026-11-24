<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Models\MpPaymentLink;
use App\Models\Payment;
use App\Services\MercadoPagoService;
use App\Support\Settings;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

    $this->booking = Booking::factory()->create([
        'price' => 24000,
        'source' => 'online',
        'hold_expires_at' => now()->addMinutes(30),
    ]);

    $this->link = MpPaymentLink::create([
        'booking_id' => $this->booking->id,
        'type' => MpPaymentLink::TYPE_SENA,
        'amount' => 12000,
        'status' => MpPaymentLink::STATUS_PENDING,
        'expires_at' => now()->addMinutes(30),
    ]);

    $this->mpStatus = 'approved';

    $mp = Mockery::mock(MercadoPagoService::class)->makePartial();
    $mp->shouldReceive('getPayment')->andReturnUsing(fn ($id) => [
        'id' => $id,
        'status' => $this->mpStatus,
        'status_detail' => 'accredited',
        'external_reference' => (string) $this->link->id,
        'amount' => 12000.0,
        'raw' => ['id' => $id],
    ]);
    $this->instance(MercadoPagoService::class, $mp);
});

function notifyPayment($test, string $id = '123456789')
{
    return $test->postJson('/webhooks/mercadopago?data.id='.$id.'&type=payment', [
        'action' => 'payment.updated',
        'type' => 'payment',
        'data' => ['id' => $id],
    ]);
}

test('un pago aprobado registra la seña y confirma el turno', function () {
    notifyPayment($this)->assertOk();

    $booking = $this->booking->fresh();
    $payment = Payment::first();

    expect($payment->amount)->toBe(12000)
        ->and($payment->method)->toBe(PaymentMethod::MercadoPago)
        ->and($payment->mp_payment_id)->toBe('123456789')
        ->and($booking->payment_status)->toBe(PaymentStatus::Sena)
        ->and($booking->status)->toBe(BookingStatus::Confirmado)
        ->and($booking->hold_expires_at)->toBeNull()
        ->and($this->link->fresh()->status)->toBe(MpPaymentLink::STATUS_PAID)
        ->and($booking->statusLogs()->where('to_status', 'confirmado')->exists())->toBeTrue();

    Mail::assertQueued(BookingConfirmed::class);
});

test('es idempotente: el mismo payment_id dos veces no duplica el pago', function () {
    notifyPayment($this)->assertOk();
    notifyPayment($this)->assertOk();
    notifyPayment($this)->assertOk();

    expect(Payment::count())->toBe(1);
    Mail::assertQueuedCount(1);
});

test('un pago rechazado no registra nada', function () {
    $this->mpStatus = 'rejected';

    notifyPayment($this)->assertOk();

    expect(Payment::count())->toBe(0)
        ->and($this->link->fresh()->status)->toBe(MpPaymentLink::STATUS_REJECTED)
        ->and($this->booking->fresh()->status)->toBe(BookingStatus::Reservado);
});

test('ignora notificaciones que no son de pagos', function () {
    $this->postJson('/webhooks/mercadopago', ['type' => 'merchant_order', 'data' => ['id' => '1']])
        ->assertOk()
        ->assertJson(['ignored' => true]);
});

test('con clave secreta valida la firma x-signature', function () {
    Settings::set('mp.webhook_secret', 'mi-secreto');

    $this->postJson('/webhooks/mercadopago?data.id=123456789&type=payment', ['type' => 'payment', 'data' => ['id' => '123456789']], [
        'x-signature' => 'ts=123,v1=firmatrucha',
        'x-request-id' => 'req-abc',
    ])->assertStatus(401);

    expect(Payment::count())->toBe(0);

    $ts = (string) time();
    $requestId = 'req-abc';
    $manifest = "id:123456789;request-id:{$requestId};ts:{$ts};";
    $signature = hash_hmac('sha256', $manifest, 'mi-secreto');

    $this->postJson('/webhooks/mercadopago?data.id=123456789&type=payment', ['type' => 'payment', 'data' => ['id' => '123456789']], [
        'x-signature' => "ts={$ts},v1={$signature}",
        'x-request-id' => $requestId,
    ])->assertOk();

    expect(Payment::count())->toBe(1);
});

test('si la reserva ya se había liberado pero el horario sigue libre, la recupera', function () {
    $pro = \App\Models\Professional::factory()->withSchedule('00:00', '23:45', [1, 2, 3, 4, 5, 6, 7])->create();
    $this->booking->update(['professional_id' => $pro->id]);
    $this->booking->changeStatus(BookingStatus::Cancelado);
    $this->booking->update(['hold_expires_at' => null]);

    notifyPayment($this)->assertOk();

    expect($this->booking->fresh()->status)->toBe(BookingStatus::Confirmado)
        ->and(Payment::count())->toBe(1);
});

test('la página de retorno procesa el pago sin esperar al webhook', function () {
    $this->get(route('booking.status', $this->link->token).'?resultado=exito&payment_id=555&status=approved')
        ->assertOk()
        ->assertSee('confirmado');

    expect(Payment::where('mp_payment_id', '555')->exists())->toBeTrue();
});
