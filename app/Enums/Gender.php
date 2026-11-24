<?php

namespace App\Enums;

enum Gender: string
{
    case Femenino = 'femenino';
    case Masculino = 'masculino';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Femenino => 'Femenino',
            self::Masculino => 'Masculino',
            self::Otro => 'Otro',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $g) => [$g->value => $g->label()])->all();
    }

    /** "F", "fem", "Mujer" → Femenino, etc. Para importaciones. */
    public static function guess(?string $value): ?self
    {
        $v = mb_strtolower(trim((string) $value));

        if ($v === '') {
            return null;
        }

        return match (true) {
            in_array($v, ['f', 'fem', 'femenino', 'mujer', 'female'], true) => self::Femenino,
            in_array($v, ['m', 'masc', 'masculino', 'hombre', 'varon', 'varón', 'male'], true) => self::Masculino,
            default => self::Otro,
        };
    }
}
