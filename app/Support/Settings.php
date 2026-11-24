<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Configuración clave/valor guardada en la tabla settings.
 *
 * - Los valores se guardan como JSON (respeta bool / int / string / array).
 * - Las claves de ENCRYPTED se guardan encriptadas con APP_KEY.
 * - Se cachean los valores crudos (encriptados); se desencripta al leer.
 *
 * Uso: Settings::get('agenda.slot_minutes'), Settings::set('agenda.lock_past', true)
 */
class Settings
{
    public const CACHE_KEY = 'app.settings';

    public const DEFAULTS = [
        // Reglas de agenda
        'agenda.allow_overlap' => false,
        'agenda.allow_client_overlap' => false,
        'agenda.require_contact' => false,
        'agenda.lock_past' => true,
        'agenda.allow_outside_schedule' => false,
        'agenda.slot_minutes' => 30,
        'agenda.day_start' => '09:00',
        'agenda.day_end' => '21:00',

        // Reservas online
        'online.enabled' => true,
        'online.min_hours_ahead' => 2,
        'online.max_days_ahead' => 30,
        'online.hold_minutes' => 30,
        'online.terms' => 'Para confirmar el turno se abona una seña por Mercado Pago, que se descuenta del total del servicio. Si no podés asistir, avisanos con al menos 24 horas de anticipación.',

        // Notificaciones
        'notifications.enabled' => true,
        'notifications.reminder_time' => '07:30',

        // Mercado Pago
        'mp.access_token' => null,
        'mp.public_key' => null,
        'mp.webhook_secret' => null,
        'mp.sandbox' => true,
        'mp.deposit_type' => 'percent', // percent | fixed
        'mp.deposit_value' => 50,
        'mp.link_expiration_hours' => 24,
    ];

    public const ENCRYPTED = [
        'mp.access_token',
        'mp.public_key',
        'mp.webhook_secret',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $raw = static::raw();

        if (! array_key_exists($key, $raw)) {
            return $default ?? (static::DEFAULTS[$key] ?? null);
        }

        return static::decode($key, $raw[$key]);
    }

    public static function bool(string $key): bool
    {
        return (bool) static::get($key);
    }

    public static function int(string $key): int
    {
        return (int) static::get($key);
    }

    public static function set(string $key, mixed $value): void
    {
        $encoded = json_encode($value);

        if (static::isEncrypted($key) && $value !== null && $value !== '') {
            $encoded = Crypt::encryptString($encoded);
        }

        Setting::updateOrCreate(['key' => $key], ['value' => $encoded]);

        Cache::forget(self::CACHE_KEY);
    }

    /** @param array<string, mixed> $values */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::set($key, $value);
        }
    }

    /** Todas las claves conocidas con su valor actual (default si no está guardada). */
    public static function all(): array
    {
        $values = [];

        foreach (array_keys(static::DEFAULTS) as $key) {
            $values[$key] = static::get($key);
        }

        return $values;
    }

    public static function isEncrypted(string $key): bool
    {
        return in_array($key, static::ENCRYPTED, true);
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, string|null> */
    protected static function raw(): array
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            fn () => Setting::query()->pluck('value', 'key')->all()
        );
    }

    protected static function decode(string $key, ?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (static::isEncrypted($key) && $value !== 'null') {
            try {
                $value = Crypt::decryptString($value);
            } catch (DecryptException) {
                return null;
            }
        }

        return json_decode($value, true);
    }
}
