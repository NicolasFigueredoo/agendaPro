<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Recepcion = 'recepcion';
    case Profesional = 'profesional';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Recepcion => 'Recepción',
            self::Profesional => 'Profesional',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Admin => 'bg-purple-100 text-purple-700',
            self::Recepcion => 'bg-sky-100 text-sky-700',
            self::Profesional => 'bg-brand-100 text-brand-700',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }
}
