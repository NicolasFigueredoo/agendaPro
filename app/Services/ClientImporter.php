<?php

namespace App\Services;

use App\Enums\Gender;
use App\Models\Client;
use App\Support\Phone;
use App\Support\Provinces;
use Carbon\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Importa clientes desde filas ya leídas del Excel/CSV.
 *
 * $rows     → filas sin la cabecera, cada una como array indexado por número de columna
 * $mapping  → [campo => índice de columna] (ver FIELDS)
 * $mode     → 'skip' (saltea duplicados) | 'update' (completa los datos del existente)
 *
 * Duplicados: mismo DNI o mismo teléfono (normalizado).
 */
class ClientImporter
{
    public const FIELDS = [
        'full_name' => 'Nombre completo (se separa en nombre y apellido)',
        'first_name' => 'Nombre',
        'last_name' => 'Apellido',
        'phone' => 'Teléfono',
        'email' => 'Email',
        'dni' => 'DNI',
        'birth_date' => 'Fecha de nacimiento',
        'gender' => 'Género',
        'client_number' => 'Número de cliente',
        'address' => 'Dirección',
        'province' => 'Provincia',
        'notes' => 'Notas',
    ];

    /** Sinónimos para adivinar el mapeo por el nombre de la columna. */
    protected const SYNONYMS = [
        'full_name' => ['nombre completo', 'cliente', 'nombre y apellido', 'apellido y nombre'],
        'first_name' => ['nombre', 'nombres', 'first name', 'name'],
        'last_name' => ['apellido', 'apellidos', 'last name', 'surname'],
        'phone' => ['telefono', 'tel', 'celular', 'movil', 'whatsapp', 'phone', 'telefono movil', 'numero de telefono'],
        'email' => ['email', 'e mail', 'mail', 'correo', 'correo electronico'],
        'dni' => ['dni', 'documento', 'nro documento', 'numero de documento', 'rut', 'identificacion'],
        'birth_date' => ['fecha de nacimiento', 'nacimiento', 'cumpleanos', 'fecha nacimiento', 'birthday'],
        'gender' => ['genero', 'sexo', 'gender'],
        'client_number' => ['numero de cliente', 'nro cliente', 'n cliente', 'cliente nro', 'id cliente', 'codigo'],
        'address' => ['direccion', 'domicilio', 'address'],
        'province' => ['provincia', 'estado', 'region'],
        'notes' => ['notas', 'observaciones', 'comentarios', 'nota', 'obs'],
    ];

    /**
     * @param  array<int, string|null>  $headers
     * @return array<string, int|null>
     */
    public static function guessMapping(array $headers): array
    {
        $normalized = array_map(fn ($h) => static::normalizeHeader((string) $h), $headers);
        $mapping = array_fill_keys(array_keys(self::FIELDS), null);
        $used = [];

        foreach (self::SYNONYMS as $field => $synonyms) {
            foreach ($normalized as $index => $header) {
                if (! in_array($index, $used, true) && in_array($header, $synonyms, true)) {
                    $mapping[$field] = $index;
                    $used[] = $index;

                    break;
                }
            }
        }

        // Si hay nombre y apellido separados no hace falta "nombre completo", y viceversa
        if ($mapping['first_name'] !== null && $mapping['last_name'] !== null) {
            $mapping['full_name'] = null;
        }

        return $mapping;
    }

    /**
     * @return array{created:int, updated:int, skipped:int, errors: array<int, array{row:int, message:string}>}
     */
    public function import(array $rows, array $mapping, string $mode = 'skip'): array
    {
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($rows as $i => $row) {
            $rowNumber = $i + 2; // +1 por la cabecera, +1 porque Excel arranca en 1

            if ($this->isEmptyRow($row)) {
                continue;
            }

            try {
                $data = $this->extract($row, $mapping);
            } catch (Throwable $e) {
                $result['errors'][] = ['row' => $rowNumber, 'message' => $e->getMessage()];

                continue;
            }

            if ($data['first_name'] === '' || $data['last_name'] === '') {
                $result['errors'][] = ['row' => $rowNumber, 'message' => 'Falta el nombre o el apellido.'];

                continue;
            }

            $existing = $this->findDuplicate($data['dni'], $data['phone']);

            if ($existing) {
                if ($mode === 'update') {
                    $existing->fill(array_filter($data, fn ($v) => $v !== null && $v !== ''));
                    $existing->isDirty() ? $existing->save() : null;
                    $result['updated']++;
                } else {
                    $result['skipped']++;
                }

                continue;
            }

            if ($data['client_number'] && Client::withTrashed()->where('client_number', $data['client_number'])->exists()) {
                $data['client_number'] = null; // se asigna el siguiente
            }

            Client::create($data);
            $result['created']++;
        }

        return $result;
    }

    protected function extract(array $row, array $mapping): array
    {
        $get = fn (string $field) => isset($mapping[$field]) && $mapping[$field] !== null && $mapping[$field] !== ''
            ? trim((string) ($row[(int) $mapping[$field]] ?? ''))
            : '';

        $firstName = $get('first_name');
        $lastName = $get('last_name');

        if (($firstName === '' || $lastName === '') && $get('full_name') !== '') {
            [$splitFirst, $splitLast] = static::splitFullName($get('full_name'));
            $firstName = $firstName ?: $splitFirst;
            $lastName = $lastName ?: $splitLast;
        }

        $dni = preg_replace('/\D/', '', $get('dni'));
        $email = Str::lower($get('email'));
        $number = preg_replace('/\D/', '', $get('client_number'));

        return [
            'first_name' => Str::title(Str::lower($firstName)),
            'last_name' => Str::title(Str::lower($lastName)),
            'phone' => Phone::normalize($get('phone')),
            'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
            'dni' => $dni !== '' ? $dni : null,
            'birth_date' => static::parseDate($row[$mapping['birth_date'] ?? -1] ?? null),
            'gender' => Gender::guess($get('gender'))?->value,
            'client_number' => $number !== '' ? (int) $number : null,
            'address' => $get('address') ?: null,
            'province' => Provinces::guess($get('province')),
            'notes' => $get('notes') ?: null,
        ];
    }

    protected function findDuplicate(?string $dni, ?string $phone): ?Client
    {
        if (! $dni && ! $phone) {
            return null;
        }

        return Client::query()
            ->where(function ($q) use ($dni, $phone) {
                $q->when($dni, fn ($w) => $w->orWhere('dni', $dni))
                    ->when($phone, fn ($w) => $w->orWhere('phone', $phone));
            })
            ->first();
    }

    /** "María José Pérez" → ["María José", "Pérez"]; "Pérez, María" → ["María", "Pérez"] */
    public static function splitFullName(string $name): array
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));

        if (str_contains($name, ',')) {
            [$last, $first] = array_map('trim', explode(',', $name, 2));

            return [$first, $last];
        }

        $parts = explode(' ', $name);

        if (count($parts) === 1) {
            return [$parts[0], ''];
        }

        $last = array_pop($parts);

        return [implode(' ', $parts), $last];
    }

    /** Acepta número de serie de Excel, dd/mm/aaaa, dd-mm-aaaa, aaaa-mm-dd y dd/mm/aa. */
    public static function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        if (is_numeric($value) && (float) $value > 1000 && (float) $value < 80000) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
        }

        $value = trim((string) $value);
        // "1990-05-03 00:00:00" → "1990-05-03"
        $value = preg_replace('/\s+\d{1,2}:\d{2}(:\d{2})?$/', '', $value);

        if (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/', $value, $m)) {
            [$y, $mo, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{2}|\d{4})$/', $value, $m)) {
            [$d, $mo, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];

            if ($y < 100) {
                $y += $y > (int) now()->format('y') ? 1900 : 2000;
            }
        } else {
            throw new \InvalidArgumentException("Fecha de nacimiento inválida: \"{$value}\".");
        }

        if (! checkdate($mo, $d, $y) || $y < 1900 || Carbon::create($y, $mo, $d)->isFuture()) {
            throw new \InvalidArgumentException("Fecha de nacimiento inválida: \"{$value}\".");
        }

        return Carbon::create($y, $mo, $d)->toDateString();
    }

    protected static function normalizeHeader(string $header): string
    {
        return Str::of($header)->ascii()->lower()->replaceMatches('/[^a-z0-9 ]/', ' ')->squish()->toString();
    }

    protected function isEmptyRow(array $row): bool
    {
        return collect($row)->every(fn ($v) => trim((string) $v) === '');
    }
}
