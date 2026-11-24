<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Efectivo = 'efectivo';
    case Transferencia = 'transferencia';
    case Debito = 'debito';
    case Credito = 'credito';
    case MercadoPago = 'mercado_pago';

    public function label(): string
    {
        return match ($this) {
            self::Efectivo => 'Efectivo',
            self::Transferencia => 'Transferencia',
            self::Debito => 'Débito',
            self::Credito => 'Crédito',
            self::MercadoPago => 'Mercado Pago',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $m) => [$m->value => $m->label()])->all();
    }
}
