<?php

namespace App\Livewire\Forms;

use App\Enums\Gender;
use App\Models\Client;
use App\Support\Phone;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Formulario de cliente reutilizable (listado, ficha y alta rápida desde la agenda).
 */
class ClientForm extends Form
{
    public ?int $clientId = null;

    public string $first_name = '';

    public string $last_name = '';

    public string $birth_date = '';

    public string $dni = '';

    public string $gender = '';

    public $client_number = '';

    public string $email = '';

    public string $phone = ''; // parte local, sin +54

    public string $address = '';

    public string $province = '';

    public string $notes = '';

    public function setClient(Client $client): void
    {
        $this->clientId = $client->id;
        $this->first_name = $client->first_name;
        $this->last_name = $client->last_name;
        $this->birth_date = $client->birth_date?->toDateString() ?? '';
        $this->dni = (string) $client->dni;
        $this->gender = $client->gender?->value ?? '';
        $this->client_number = $client->client_number ?? '';
        $this->email = (string) $client->email;
        $this->phone = Phone::local($client->phone);
        $this->address = (string) $client->address;
        $this->province = (string) $client->province;
        $this->notes = (string) $client->notes;
    }

    public function clear(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'birth_date' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'dni' => [
                'nullable', 'string', 'max:20',
                Rule::unique('clients', 'dni')->ignore($this->clientId)->whereNull('deleted_at'),
            ],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'client_number' => [
                'nullable', 'integer', 'min:1',
                Rule::unique('clients', 'client_number')->ignore($this->clientId),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[\d\s\-\+\(\)]+$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'dni.unique' => 'Ya hay un cliente con ese DNI.',
            'client_number.unique' => 'Ese número de cliente ya está usado.',
            'phone.regex' => 'El teléfono solo puede tener números.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'first_name' => 'nombre',
            'last_name' => 'apellido',
            'birth_date' => 'fecha de nacimiento',
            'dni' => 'DNI',
            'gender' => 'género',
            'client_number' => 'número de cliente',
            'phone' => 'teléfono',
            'address' => 'dirección',
            'province' => 'provincia',
            'notes' => 'notas',
        ];
    }

    public function save(): Client
    {
        // Normalizar antes de validar el DNI único
        $this->dni = preg_replace('/\D/', '', $this->dni);

        $this->validate();

        $data = [
            'first_name' => trim($this->first_name),
            'last_name' => trim($this->last_name),
            'birth_date' => $this->birth_date ?: null,
            'dni' => $this->dni ?: null,
            'gender' => $this->gender ?: null,
            'email' => $this->email ? mb_strtolower(trim($this->email)) : null,
            'phone' => Phone::normalize($this->phone),
            'address' => $this->address ?: null,
            'province' => $this->province ?: null,
            'notes' => $this->notes ?: null,
        ];

        if ($this->client_number !== '' && $this->client_number !== null) {
            $data['client_number'] = (int) $this->client_number;
        }

        if ($this->clientId) {
            $client = Client::findOrFail($this->clientId);
            $client->update($data);
        } else {
            $client = Client::create($data);
        }

        return $client;
    }
}
