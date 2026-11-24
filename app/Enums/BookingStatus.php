<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Reservado = 'reservado';
    case Confirmado = 'confirmado';
    case Asistio = 'asistio';
    case NoAsistio = 'no_asistio';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Reservado => 'Reservado',
            self::Confirmado => 'Confirmado',
            self::Asistio => 'Asistió',
            self::NoAsistio => 'No asistió',
            self::Cancelado => 'Cancelado',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Reservado => 'bg-sky-100 text-sky-700',
            self::Confirmado => 'bg-emerald-100 text-emerald-700',
            self::Asistio => 'bg-violet-100 text-violet-700',
            self::NoAsistio => 'bg-red-100 text-red-700',
            self::Cancelado => 'bg-gray-200 text-gray-600',
        };
    }

    /** Clases del bloque en la agenda. */
    public function blockClasses(): string
    {
        return match ($this) {
            self::Reservado => 'bg-sky-50 border-sky-400 text-sky-900',
            self::Confirmado => 'bg-emerald-50 border-emerald-500 text-emerald-900',
            self::Asistio => 'bg-violet-50 border-violet-500 text-violet-900',
            self::NoAsistio => 'bg-red-50 border-red-400 text-red-900',
            self::Cancelado => 'bg-gray-100 border-gray-300 text-gray-500 line-through',
        };
    }

    /** Estados que ocupan el horario del profesional. */
    public function occupiesSlot(): bool
    {
        return $this !== self::Cancelado;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
