<?php

namespace App\Support;

/**
 * Teléfonos argentinos. Se guardan como "+549" + código de área + número (10 dígitos),
 * que es el formato que necesita wa.me (sin el "+").
 *
 *   "11 1234-5678"        → "+5491112345678"
 *   "011 15 1234-5678"    → "+5491112345678"
 *   "+54 9 351 555-1234"  → "+5493515551234"
 *   "0351 15 555-1234"    → "+5493515551234"
 */
class Phone
{
    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        if ($digits === '') {
            return null;
        }

        $digits = preg_replace('/^00/', '', $digits);

        if (str_starts_with($digits, '54')) {
            $digits = substr($digits, 2);
        }

        $digits = ltrim($digits, '0');

        // 9 + 10 dígitos (formato internacional de celular)
        if (strlen($digits) === 11 && str_starts_with($digits, '9')) {
            $digits = substr($digits, 1);
        }

        // Área (2 a 4 dígitos) + "15" + número: 12 dígitos en total
        if (strlen($digits) === 12) {
            foreach ([2, 3, 4] as $areaLength) {
                if (substr($digits, $areaLength, 2) === '15') {
                    $digits = substr($digits, 0, $areaLength).substr($digits, $areaLength + 2);

                    break;
                }
            }
        }

        if (strlen($digits) === 10) {
            return '+549'.$digits;
        }

        // Formato no reconocido: se guarda igual con el prefijo del país
        return '+54'.$digits;
    }

    /** Parte local para mostrar en el input con el prefijo "+54" fijo. */
    public static function local(?string $phone): string
    {
        if (! $phone) {
            return '';
        }

        if (str_starts_with($phone, '+549')) {
            return substr($phone, 4);
        }

        return str_starts_with($phone, '+54') ? substr($phone, 3) : $phone;
    }

    /** Solo dígitos, para wa.me/<numero> */
    public static function forWhatsapp(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        return $digits !== '' ? $digits : null;
    }

    public static function whatsappUrl(?string $phone, string $text = ''): ?string
    {
        $number = self::forWhatsapp($phone);

        if (! $number) {
            return null;
        }

        return 'https://wa.me/'.$number.($text !== '' ? '?text='.rawurlencode($text) : '');
    }
}
