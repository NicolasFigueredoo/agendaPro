<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

/**
 * Lee la planilla tal cual (sin procesar). El mapeo de columnas lo hace ClientImporter.
 * Para CSV detecta el separador (, ; o tab) y la codificación (UTF-8 o Latin-1, típico de Excel en Windows).
 */
class RawSheetImport implements WithCalculatedFormulas, WithCustomCsvSettings
{
    protected string $delimiter = ',';

    protected string $encoding = 'UTF-8';

    public function __construct(?string $path = null)
    {
        if ($path && is_readable($path)) {
            $sample = (string) file_get_contents($path, false, null, 0, 65536);

            $counts = [
                ',' => substr_count($sample, ','),
                ';' => substr_count($sample, ';'),
                "\t" => substr_count($sample, "\t"),
            ];
            arsort($counts);
            $this->delimiter = (string) array_key_first($counts);

            $this->encoding = mb_check_encoding($sample, 'UTF-8') ? 'UTF-8' : 'ISO-8859-1';
        }
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => $this->delimiter,
            'input_encoding' => $this->encoding,
        ];
    }
}
