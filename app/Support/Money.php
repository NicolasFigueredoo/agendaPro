<?php

namespace App\Support;

/**
 * Todos los montos del sistema son enteros (pesos, sin decimales).
 */
class Money
{
    public static function format(int|float|string|null $amount): string
    {
        return '$ '.number_format((int) $amount, 0, ',', '.');
    }

    /** Convierte "12.500", "$ 12.500" o "12500" en 12500. */
    public static function parse(string|int|null $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        return (int) preg_replace('/[^\d]/', '', (string) $value);
    }
}
