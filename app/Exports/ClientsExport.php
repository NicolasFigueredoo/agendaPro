<?php

namespace App\Exports;

use App\Models\Client;
use App\Queries\ClientFilters;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClientsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(protected array $filters = [])
    {
    }

    public function query(): Builder
    {
        return ClientFilters::query($this->filters);
    }

    public function headings(): array
    {
        return [
            'N° cliente', 'Nombre', 'Apellido', 'DNI', 'Email', 'Teléfono', 'Fecha de nacimiento',
            'Género', 'Dirección', 'Provincia', 'Notas', 'Turnos', 'Última visita', 'Alta',
        ];
    }

    /** @param  Client  $client */
    public function map($client): array
    {
        return [
            $client->client_number,
            $client->first_name,
            $client->last_name,
            $client->dni,
            $client->email,
            $client->phone,
            $client->birth_date?->format('d/m/Y'),
            $client->gender?->label(),
            $client->address,
            $client->province,
            $client->notes,
            $client->bookings_count,
            $client->last_visit ? Carbon::parse($client->last_visit)->format('d/m/Y') : null,
            $client->created_at?->format('d/m/Y'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
