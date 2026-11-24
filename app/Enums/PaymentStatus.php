<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case SinPagar = 'sin_pagar';
    case Sena = 'sena';
    case Pagado = 'pagado';

    public function label(): string
    {
        return match ($this) {
            self::SinPagar => 'Sin pagar',
            self::Sena => 'Seña',
            self::Pagado => 'Pagado',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::SinPagar => 'bg-gray-100 text-gray-600',
            self::Sena => 'bg-amber-100 text-amber-700',
            self::Pagado => 'bg-emerald-100 text-emerald-700',
        };
    }

    /** Estado según lo pagado vs. el precio del turno. */
    public static function fromAmounts(int $price, int $paid): self
    {
        return match (true) {
            $paid <= 0 => self::SinPagar,
            $paid >= $price => self::Pagado,
            default => self::Sena,
        };
    }
}
