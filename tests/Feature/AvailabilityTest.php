<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Professional;
use App\Models\ScheduleBlock;
use App\Services\Availability;
use Carbon\Carbon;

beforeEach(function () {
    Carbon::setTestNow('2026-11-30 08:00'); // lunes
    $this->availability = app(Availability::class);
    $this->pro = Professional::factory()
        ->withSchedule('09:00', '18:00', [1, 2, 3, 4, 5], [['13:00', '14:00']])
        ->create();
    $this->monday = Carbon::parse('2026-11-30');
});

afterEach(fn () => Carbon::setTestNow());

test('respeta jornada y descanso', function () {
    $slots = $this->availability->slots($this->pro, $this->monday, 60, 30);

    expect($slots)->toContain('09:00', '12:00', '14:00', '17:00')
        ->not->toContain('12:30', '13:00', '13:30', '17:30', '08:30');
});

test('un día sin jornada no tiene horarios', function () {
    expect($this->availability->slots($this->pro, Carbon::parse('2026-12-06'), 60))->toBe([]); // domingo
});

test('los turnos existentes ocupan el horario', function () {
    Booking::factory()->for($this->pro)->at('2026-11-30 10:00', 90)->create();

    $slots = $this->availability->slots($this->pro, $this->monday, 60, 30);

    expect($slots)->toContain('09:00', '11:30')->not->toContain('09:30', '10:00', '10:30', '11:00');
});

test('los cancelados no ocupan', function () {
    Booking::factory()->for($this->pro)->at('2026-11-30 10:00', 60)->status(BookingStatus::Cancelado)->create();

    expect($this->availability->slots($this->pro, $this->monday, 60))->toContain('10:00');
});

test('una reserva online sin seña ocupa solo mientras no vence', function () {
    $booking = Booking::factory()->for($this->pro)->at('2026-11-30 10:00', 60)->create([
        'source' => 'online',
        'hold_expires_at' => now()->addMinutes(30),
        'payment_status' => PaymentStatus::SinPagar,
    ]);

    expect($this->availability->slots($this->pro, $this->monday, 60))->not->toContain('10:00');

    $booking->update(['hold_expires_at' => now()->subMinute()]);

    expect($this->availability->slots($this->pro, $this->monday, 60))->toContain('10:00');
});

test('los bloqueos sacan disponibilidad', function () {
    ScheduleBlock::create([
        'professional_id' => $this->pro->id,
        'start_at' => '2026-11-30 15:00',
        'end_at' => '2026-11-30 18:00',
        'reason' => 'Trámite',
    ]);

    $slots = $this->availability->slots($this->pro, $this->monday, 60);

    expect($slots)->toContain('14:00')->not->toContain('14:30', '15:00', '16:00');
});

test('un bloqueo de varios días (vacaciones) bloquea todo el día', function () {
    ScheduleBlock::create([
        'professional_id' => $this->pro->id,
        'start_at' => '2026-11-29 00:00',
        'end_at' => '2026-12-02 00:00',
    ]);

    expect($this->availability->slots($this->pro, $this->monday, 30))->toBe([]);
});

test('no ofrece horarios antes de la anticipación mínima', function () {
    $slots = $this->availability->slots($this->pro, $this->monday, 60, 30, Carbon::parse('2026-11-30 11:10'));

    expect($slots)->not->toContain('09:00', '11:00')->toContain('11:30');
});

test('isAvailable valida jornada, descanso y superposición', function () {
    Booking::factory()->for($this->pro)->at('2026-11-30 10:00', 60)->create();

    expect($this->availability->isAvailable($this->pro, Carbon::parse('2026-11-30 09:00'), Carbon::parse('2026-11-30 10:00')))->toBeTrue()
        ->and($this->availability->isAvailable($this->pro, Carbon::parse('2026-11-30 09:30'), Carbon::parse('2026-11-30 10:30')))->toBeFalse()
        ->and($this->availability->isAvailable($this->pro, Carbon::parse('2026-11-30 12:30'), Carbon::parse('2026-11-30 13:30')))->toBeFalse()
        ->and($this->availability->isAvailable($this->pro, Carbon::parse('2026-11-30 17:30'), Carbon::parse('2026-11-30 18:30')))->toBeFalse();
});
