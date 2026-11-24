<?php

namespace App\Livewire\Clients;

use App\Imports\RawSheetImport;
use App\Services\ClientImporter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

#[Layout('layouts.app')]
#[Title('Importar clientes')]
class Import extends Component
{
    use WithFileUploads;

    public int $step = 1; // 1: subir · 2: mapear · 3: resultado

    public $file = null;

    /** @var array<int, string> */
    public array $headers = [];

    /** @var array<int, array<int, mixed>> primeras filas para la vista previa */
    public array $preview = [];

    public int $totalRows = 0;

    /** @var array<string, int|string|null> */
    public array $mapping = [];

    public string $mode = 'skip';

    public array $result = [];

    public function updatedFile(): void
    {
        $this->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ], [], ['file' => 'archivo']);

        try {
            $rows = $this->readRows();
        } catch (Throwable $e) {
            report($e);
            $this->addError('file', 'No se pudo leer el archivo. Probá guardarlo como .xlsx o .csv.');

            return;
        }

        if (count($rows) < 2) {
            $this->addError('file', 'El archivo no tiene filas para importar (la primera fila tiene que ser la cabecera).');

            return;
        }

        $this->headers = array_map(fn ($h) => trim((string) $h), $rows[0]);
        $this->preview = array_slice($rows, 1, 5);
        $this->totalRows = count(array_filter(array_slice($rows, 1), fn ($r) => collect($r)->filter(fn ($v) => trim((string) $v) !== '')->isNotEmpty()));
        $this->mapping = ClientImporter::guessMapping($this->headers);
        $this->step = 2;
    }

    public function import(ClientImporter $importer): void
    {
        $hasNames = ($this->mapped('first_name') && $this->mapped('last_name')) || $this->mapped('full_name');

        if (! $hasNames) {
            $this->addError('mapping', 'Tenés que elegir las columnas de Nombre y Apellido, o la de Nombre completo.');

            return;
        }

        $mapping = collect($this->mapping)
            ->map(fn ($v) => $v === '' || $v === null ? null : (int) $v)
            ->all();

        $rows = array_slice($this->readRows(), 1);

        $this->result = $importer->import($rows, $mapping, $this->mode === 'update' ? 'update' : 'skip');
        $this->step = 3;
    }

    public function restart(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    protected function mapped(string $field): bool
    {
        return isset($this->mapping[$field]) && $this->mapping[$field] !== '' && $this->mapping[$field] !== null;
    }

    protected function readRows(): array
    {
        $path = $this->file->getRealPath();
        $sheets = Excel::toArray(new RawSheetImport($path), $path, null, $this->readerType());

        return $sheets[0] ?? [];
    }

    protected function readerType(): string
    {
        return match (strtolower($this->file->getClientOriginalExtension())) {
            'xls' => \Maatwebsite\Excel\Excel::XLS,
            'csv', 'txt' => \Maatwebsite\Excel\Excel::CSV,
            default => \Maatwebsite\Excel\Excel::XLSX,
        };
    }

    public function render()
    {
        return view('livewire.clients.import', [
            'fields' => ClientImporter::FIELDS,
        ]);
    }
}
