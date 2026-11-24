<?php

namespace App\Support;

use Illuminate\Support\Str;

class Provinces
{
    public const ALL = [
        'Buenos Aires',
        'Ciudad Autónoma de Buenos Aires',
        'Catamarca',
        'Chaco',
        'Chubut',
        'Córdoba',
        'Corrientes',
        'Entre Ríos',
        'Formosa',
        'Jujuy',
        'La Pampa',
        'La Rioja',
        'Mendoza',
        'Misiones',
        'Neuquén',
        'Río Negro',
        'Salta',
        'San Juan',
        'San Luis',
        'Santa Cruz',
        'Santa Fe',
        'Santiago del Estero',
        'Tierra del Fuego',
        'Tucumán',
    ];

    /** "caba", "Capital Federal", "cordoba" → nombre oficial. Si no matchea, devuelve el valor original. */
    public static function guess(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $key = Str::of($value)->ascii()->lower()->replaceMatches('/[^a-z ]/', '')->squish()->toString();

        $aliases = [
            'caba' => 'Ciudad Autónoma de Buenos Aires',
            'capital federal' => 'Ciudad Autónoma de Buenos Aires',
            'capital' => 'Ciudad Autónoma de Buenos Aires',
            'ciudad de buenos aires' => 'Ciudad Autónoma de Buenos Aires',
            'pba' => 'Buenos Aires',
            'provincia de buenos aires' => 'Buenos Aires',
            'bs as' => 'Buenos Aires',
            'bsas' => 'Buenos Aires',
        ];

        if (isset($aliases[$key])) {
            return $aliases[$key];
        }

        foreach (self::ALL as $province) {
            if (Str::of($province)->ascii()->lower()->toString() === $key) {
                return $province;
            }
        }

        return $value;
    }
}
