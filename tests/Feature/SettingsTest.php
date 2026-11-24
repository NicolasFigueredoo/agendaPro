<?php

use App\Models\Setting;
use App\Support\Money;
use App\Support\Settings;

test('devuelve los valores por defecto', function () {
    expect(Settings::get('agenda.slot_minutes'))->toBe(30)
        ->and(Settings::bool('agenda.lock_past'))->toBeTrue()
        ->and(Settings::bool('agenda.allow_overlap'))->toBeFalse()
        ->and(Settings::get('notifications.reminder_time'))->toBe('07:30')
        ->and(Settings::get('mp.deposit_value'))->toBe(50);
});

test('guarda y lee respetando el tipo', function () {
    Settings::set('agenda.allow_overlap', true);
    Settings::set('agenda.slot_minutes', 15);

    expect(Settings::get('agenda.allow_overlap'))->toBeTrue()
        ->and(Settings::get('agenda.slot_minutes'))->toBe(15);
});

test('las claves sensibles se guardan encriptadas', function () {
    Settings::set('mp.access_token', 'APP_USR-123456');

    $raw = Setting::where('key', 'mp.access_token')->value('value');

    expect($raw)->not->toContain('APP_USR-123456')
        ->and(Settings::get('mp.access_token'))->toBe('APP_USR-123456');
});

test('formatea y parsea pesos', function () {
    expect(Money::format(12500))->toBe('$ 12.500')
        ->and(Money::format(0))->toBe('$ 0')
        ->and(Money::parse('$ 12.500'))->toBe(12500)
        ->and(Money::parse(800))->toBe(800);
});
