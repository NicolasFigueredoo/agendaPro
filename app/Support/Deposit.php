<?php

namespace App\Support;

/**
 * Seña según la configuración de Mercado Pago:
 * - percent: porcentaje del precio, redondeado a $100
 * - fixed:   monto fijo (nunca más que el precio)
 */
class Deposit
{
    public static function amountFor(int $price): int
    {
        $value = (int) Settings::get('mp.deposit_value');

        $amount = Settings::get('mp.deposit_type') === 'fixed'
            ? $value
            : (int) (round(($price * $value / 100) / 100) * 100);

        return max(0, min($amount, $price));
    }

    public static function description(): string
    {
        $value = (int) Settings::get('mp.deposit_value');

        return Settings::get('mp.deposit_type') === 'fixed'
            ? Money::format($value)
            : "{$value}% del servicio";
    }
}
