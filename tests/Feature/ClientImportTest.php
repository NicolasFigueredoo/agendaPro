<?php

use App\Enums\Gender;
use App\Livewire\Clients\Import;
use App\Models\Client;
use App\Models\User;
use App\Services\ClientImporter;
use App\Support\Phone;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

test('normaliza teléfonos argentinos', function (string $input, string $expected) {
    expect(Phone::normalize($input))->toBe($expected);
})->with([
    ['11 1234-5678', '+5491112345678'],
    ['011 15 1234-5678', '+5491112345678'],
    ['+54 9 11 1234 5678', '+5491112345678'],
    ['5491112345678', '+5491112345678'],
    ['0351 15 555-1234', '+5493515551234'],
    ['351 555 1234', '+5493515551234'],
    ['2954 15 41-2233', '+5492954412233'],
]);

test('adivina el mapeo por el nombre de las columnas', function () {
    $mapping = ClientImporter::guessMapping(['Nombre', 'Apellido', 'Teléfono Móvil', 'E-mail', 'Documento', 'Fecha de Nacimiento', 'Sexo']);

    expect($mapping['first_name'])->toBe(0)
        ->and($mapping['last_name'])->toBe(1)
        ->and($mapping['phone'])->toBe(2)
        ->and($mapping['email'])->toBe(3)
        ->and($mapping['dni'])->toBe(4)
        ->and($mapping['birth_date'])->toBe(5)
        ->and($mapping['gender'])->toBe(6)
        ->and($mapping['full_name'])->toBeNull();
});

test('importa clientes, separa nombre completo y parsea fechas', function () {
    $rows = [
        ['MARÍA JOSÉ PÉREZ', '11 4444-5555', '30111222', '03/05/1990', 'F'],
        ['Gómez, Laura', '', '', '45000', 'mujer'],         // fecha como número de serie de Excel
        ['Sofía', '351 555 1234', '', '1988-12-24', ''],   // sin apellido → error
    ];

    $mapping = ['full_name' => 0, 'phone' => 1, 'dni' => 2, 'birth_date' => 3, 'gender' => 4];

    $result = (new ClientImporter)->import($rows, $mapping);

    expect($result['created'])->toBe(2)
        ->and($result['errors'])->toHaveCount(1)
        ->and($result['errors'][0]['row'])->toBe(4);

    $maria = Client::where('dni', '30111222')->first();
    expect($maria->first_name)->toBe('María José')
        ->and($maria->last_name)->toBe('Pérez')
        ->and($maria->phone)->toBe('+5491144445555')
        ->and($maria->birth_date->toDateString())->toBe('1990-05-03')
        ->and($maria->gender)->toBe(Gender::Femenino);

    $laura = Client::where('last_name', 'Gómez')->first();
    expect($laura->first_name)->toBe('Laura')
        ->and($laura->birth_date->toDateString())->toBe('2023-03-15');
});

test('detecta duplicados por teléfono o DNI y los saltea', function () {
    Client::factory()->create(['dni' => '30111222', 'phone' => '+5491100000000']);
    Client::factory()->create(['dni' => '99999999', 'phone' => '+5491144445555']);

    $rows = [
        ['Ana', 'Uno', '30111222', ''],            // mismo DNI
        ['Ana', 'Dos', '', '011 15 4444-5555'],    // mismo teléfono
        ['Ana', 'Tres', '', ''],                   // nueva
        ['Ana', 'Tres bis', '', '11 7777-8888'],   // nueva
        ['Ana', 'Repetida', '', '1177778888'],     // repetida dentro del mismo archivo
    ];

    $result = (new ClientImporter)->import($rows, ['first_name' => 0, 'last_name' => 1, 'dni' => 2, 'phone' => 3]);

    expect($result['created'])->toBe(2)
        ->and($result['skipped'])->toBe(3)
        ->and(Client::count())->toBe(4);
});

test('en modo actualizar completa los datos del existente', function () {
    $client = Client::factory()->create(['dni' => '30111222', 'email' => null, 'notes' => 'VIP']);

    $result = (new ClientImporter)->import(
        [['Ana', 'Pérez', '30111222', 'ana@nuevo.com', '']],
        ['first_name' => 0, 'last_name' => 1, 'dni' => 2, 'email' => 3, 'notes' => 4],
        'update'
    );

    expect($result['updated'])->toBe(1)
        ->and($client->fresh()->email)->toBe('ana@nuevo.com')
        ->and($client->fresh()->notes)->toBe('VIP'); // vacío en el archivo no borra
});

test('fecha inválida se reporta como error de la fila', function () {
    $result = (new ClientImporter)->import(
        [['Ana', 'Pérez', '31/02/1990']],
        ['first_name' => 0, 'last_name' => 1, 'birth_date' => 2]
    );

    expect($result['created'])->toBe(0)
        ->and($result['errors'][0]['message'])->toContain('Fecha de nacimiento inválida');
});

test('flujo completo con un CSV separado por punto y coma', function () {
    $csv = "Nombre;Apellido;Celular;DNI\nLucía;Fernández;11 2222-3333;35111222\nPaula;Ruiz;;\n";
    $file = UploadedFile::fake()->createWithContent('clientes.csv', $csv);

    Livewire::actingAs(User::factory()->recepcion()->create())
        ->test(Import::class)
        ->set('file', $file)
        ->assertSet('step', 2)
        ->assertSet('totalRows', 2)
        ->assertSet('mapping.first_name', 0)
        ->assertSet('mapping.phone', 2)
        ->call('import')
        ->assertSet('step', 3)
        ->assertSet('result.created', 2);

    expect(Client::where('dni', '35111222')->first()->phone)->toBe('+5491122223333');
});
